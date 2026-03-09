<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Models\Kunjungan;
use App\Models\ActivityLog;
use App\Models\KelompokPelanggan;

/**
 * Controller untuk mengelola data kunjungan pelanggan
 * Menangani: tampil form edit, update, dan hapus kunjungan
 */
class KunjunganController extends Controller
{
    /**
     * Menampilkan form edit kunjungan
     * Method: GET /kunjungan/{kunjungan}/edit
     */
    public function edit($id)
    {
        $kunjungan = Kunjungan::with('pelanggan')->findOrFail($id);

        return view('pelanggan.edit-kunjungan', compact('kunjungan'));
    }

    /**
     * Update data kunjungan
     * Method: PUT /kunjungan/{kunjungan}
     * Setelah update, update biaya dan class pelanggan (total_kedatangan tetap)
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'tanggal_kunjungan' => 'required|date',
            'biaya'             => 'required|numeric|min:0',
            'alasan_perubahan'  => 'required|string|max:500',
            'kelompok_pelanggan' => 'required|in:mandiri,klinisi',
        ], [
            'tanggal_kunjungan.required' => 'Tanggal kunjungan wajib diisi.',
            'tanggal_kunjungan.date'     => 'Format tanggal tidak valid.',
            'biaya.required'             => 'Biaya wajib diisi.',
            'biaya.numeric'              => 'Biaya harus berupa angka.',
            'biaya.min'                  => 'Biaya tidak boleh negatif.',
            'alasan_perubahan.required'  => 'Alasan perubahan wajib diisi.',
            'alasan_perubahan.string'    => 'Alasan perubahan harus berupa teks.',
            'alasan_perubahan.max'       => 'Alasan perubahan maksimal 500 karakter.',
        ]);

        $kunjungan = Kunjungan::with('pelanggan')->findOrFail($id);
        $pelanggan = $kunjungan->pelanggan;

        // Simpan data lama untuk log
        $oldData = [
            'tanggal' => $kunjungan->tanggal_kunjungan,
            'biaya'   => $kunjungan->biaya,
        ];

        // Hitung selisih biaya untuk update total_biaya pelanggan
        $biayaDifference = $request->biaya - $kunjungan->biaya;

        DB::transaction(function () use ($kunjungan, $pelanggan, $request, $biayaDifference) {
            $kelompok = KelompokPelanggan::where('kode', $request->kelompok_pelanggan)->first();

            $kunjungan->update([
                'tanggal_kunjungan'     => $request->tanggal_kunjungan,
                'biaya'                 => $request->biaya,
                'kelompok_pelanggan_id' => $kelompok?->id ?? $kunjungan->kelompok_pelanggan_id,
            ]);

            // Update biaya dan class pelanggan (total_kedatangan tetap, tidak dihitung ulang)
            $pelanggan->updateBiayaAndClass(
                $biayaDifference,
                \Carbon\Carbon::parse($request->tanggal_kunjungan),
                'Perubahan dari edit kunjungan. Alasan user: ' . $request->alasan_perubahan
            );
        });

        // Catat di activity log
        ActivityLog::record(
            'update',
            'Kunjungan',
            "Mengubah kunjungan {$pelanggan->pid}: tanggal {$oldData['tanggal']} → {$request->tanggal_kunjungan}, biaya "
                . number_format($oldData['biaya'], 0, ',', '.') . " → "
                . number_format($request->biaya, 0, ',', '.') . ". Alasan: {$request->alasan_perubahan}",
            Auth::id(),
            Auth::user()->username ?? 'unknown',
            Auth::user()->role->name ?? '-',
            $request->ip(),
            $request->userAgent()
        );

        return redirect()->route('pelanggan.show', $pelanggan->id)
            ->with('success', 'Data kunjungan berhasil diperbarui.');
    }

    /**
     * Hapus kunjungan
     * Method: DELETE /kunjungan/{kunjungan}
     * Setelah hapus, recalculate stats pelanggan
     */
    public function destroy(Request $request, $id)
    {
        $request->validate([
            'alasan_hapus' => 'required|string|max:500',
        ], [
            'alasan_hapus.required' => 'Alasan hapus wajib diisi.',
            'alasan_hapus.string'   => 'Alasan hapus harus berupa teks.',
            'alasan_hapus.max'      => 'Alasan hapus maksimal 500 karakter.',
        ]);

        $kunjungan = Kunjungan::with('pelanggan')->findOrFail($id);
        $pelanggan = $kunjungan->pelanggan;

        // Simpan data untuk log
        $logData = [
            'pid'     => $pelanggan->pid,
            'tanggal' => $kunjungan->tanggal_kunjungan,
            'biaya'   => $kunjungan->biaya,
        ];

        $deletedVisitDate = \Carbon\Carbon::parse($kunjungan->tanggal_kunjungan);

        DB::transaction(function () use ($kunjungan, $pelanggan, $request, $deletedVisitDate) {
            $kunjungan->delete();

            // Recalculate stats pelanggan setelah hapus
            $pelanggan->updateStats(
                $deletedVisitDate,
                'Perubahan dari hapus kunjungan. Alasan user: ' . $request->alasan_hapus
            );
        });

        // Catat di activity log
        ActivityLog::record(
            'delete',
            'Kunjungan',
            "Menghapus kunjungan {$logData['pid']} tanggal {$logData['tanggal']} (Rp "
                . number_format($logData['biaya'], 0, ',', '.') . "). Alasan: {$request->alasan_hapus}",
            Auth::id(),
            Auth::user()->username ?? 'unknown',
            Auth::user()->role->name ?? '-',
            request()->ip(),
            request()->userAgent()
        );

        return redirect()->route('pelanggan.show', $pelanggan->id)
            ->with('success', 'Data kunjungan berhasil dihapus.');
    }
}
