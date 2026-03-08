<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CabangController extends Controller
{
    /**
     * Tampilkan daftar cabang + form tambah cabang baru
     */
    public function index()
    {
        $cabangs = Cabang::orderBy('nama')->get();
        return view('cabang.index', compact('cabangs'));
    }

    /**
     * Simpan cabang baru
     * Kode cabang (prefix PID) tidak bisa diubah setelah dibuat
     */
    public function store(Request $request)
    {
        $request->validate([
            'kode' => [
                'required',
                'string',
                'size:2',
                'regex:/^[A-Za-z]{2}$/',
                'unique:cabangs,kode',
            ],
            'nama'       => 'required|string|max:100',
            'tipe'       => 'required|in:cabang,regional',
            'keterangan' => 'nullable|string|max:500',
        ], [
            'kode.required'  => 'Kode cabang wajib diisi.',
            'kode.size'      => 'Kode cabang harus tepat 2 karakter.',
            'kode.regex'     => 'Kode cabang hanya boleh berisi huruf (A-Z).',
            'kode.unique'    => 'Kode cabang sudah digunakan.',
            'nama.required'  => 'Nama cabang wajib diisi.',
            'tipe.required'  => 'Tipe cabang wajib dipilih.',
        ]);

        $cabang = Cabang::create([
            'kode'       => strtoupper($request->kode),
            'nama'       => $request->nama,
            'tipe'       => $request->tipe,
            'keterangan' => $request->keterangan,
        ]);

        ActivityLog::record(
            'create',
            'Cabang',
            "Membuka cabang baru: {$cabang->nama} (Kode: {$cabang->kode})",
            Auth::id(),
            Auth::user()->username ?? 'unknown',
            Auth::user()->role->name ?? '-',
            request()->ip(),
            request()->userAgent()
        );

        return redirect()->route('cabang.index')
            ->with('success', "Cabang \"{$cabang->nama}\" (Kode: {$cabang->kode}) berhasil ditambahkan.");
    }

    /**
     * Update data cabang (hanya nama, tipe, keterangan — kode tidak bisa diubah)
     */
    public function update(Request $request, $id)
    {
        $cabang = Cabang::findOrFail($id);

        $request->validate([
            'nama'       => 'required|string|max:100',
            'tipe'       => 'required|in:cabang,regional',
            'keterangan' => 'nullable|string|max:500',
        ], [
            'nama.required' => 'Nama cabang wajib diisi.',
            'tipe.required' => 'Tipe cabang wajib dipilih.',
        ]);

        $oldNama = $cabang->nama;

        $cabang->update([
            'nama'       => $request->nama,
            'tipe'       => $request->tipe,
            'keterangan' => $request->keterangan,
        ]);

        ActivityLog::record(
            'update',
            'Cabang',
            "Mengubah cabang: {$oldNama} → {$cabang->nama} (Kode: {$cabang->kode})",
            Auth::id(),
            Auth::user()->username ?? 'unknown',
            Auth::user()->role->name ?? '-',
            request()->ip(),
            request()->userAgent()
        );

        return redirect()->route('cabang.index')
            ->with('success', "Cabang \"{$cabang->nama}\" berhasil diperbarui.");
    }

    /**
     * Hapus cabang (hanya jika tidak ada pelanggan yang terdaftar)
     */
    public function destroy($id)
    {
        $cabang = Cabang::findOrFail($id);

        // Cek apakah ada pelanggan yang terdaftar di cabang ini
        $pelangganCount = \App\Models\Pelanggan::where('cabang_id', $id)->count();
        if ($pelangganCount > 0) {
            return redirect()->route('cabang.index')
                ->with('error', "Cabang \"{$cabang->nama}\" tidak dapat dihapus karena masih memiliki {$pelangganCount} pelanggan terdaftar.");
        }

        $namaCabang = $cabang->nama;
        $kodeCabang = $cabang->kode;
        $cabang->delete();

        ActivityLog::record(
            'delete',
            'Cabang',
            "Menghapus cabang: {$namaCabang} (Kode: {$kodeCabang})",
            Auth::id(),
            Auth::user()->username ?? 'unknown',
            Auth::user()->role->name ?? '-',
            request()->ip(),
            request()->userAgent()
        );

        return redirect()->route('cabang.index')
            ->with('success', "Cabang \"{$namaCabang}\" berhasil dihapus.");
    }
}
