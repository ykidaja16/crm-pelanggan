<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use App\Models\ImportBatch;
use App\Models\ImportBatchPelangganSnapshot;
use App\Models\Pelanggan;
use App\Models\Kunjungan;
use App\Models\ActivityLog;

/**
 * Controller untuk mengelola riwayat import dan rollback data.
 * Hanya dapat diakses oleh role IT.
 */
class ImportBatchController extends Controller
{
    /**
     * Tampilkan daftar riwayat import
     */
    public function index(Request $request)
    {
        $query = ImportBatch::with(['user', 'cabang', 'rolledBackByUser'])
            ->orderBy('imported_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by cabang
        if ($request->filled('cabang_id')) {
            $query->where('cabang_id', $request->cabang_id);
        }

        // Filter by tanggal
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('imported_at', '>=', $request->tanggal_mulai);
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('imported_at', '<=', $request->tanggal_selesai);
        }

        $batches = $query->paginate(20)->withQueryString();

        $cabangs = \App\Models\Cabang::orderBy('nama')->get();

        return view('import-batch.index', compact('batches', 'cabangs'));
    }

    /**
     * Rollback satu sesi import berdasarkan batch_id.
     *
     * Mendukung DUA mode rollback:
     *
 * MODE A – Snapshot-based (import baru, ada snapshot):
     *   1. Hapus kunjungan dari batch ini
     *   2. Restore pelanggan dari snapshot (nilai sebelum import)
     *   3. Hard-delete (forceDelete) pelanggan baru yang dibuat saat import
     *      → Agar re-import file yang sama tidak kena duplicate PID error
     *
     * MODE B – Recalculate (import lama, tidak ada snapshot):
     *   1. Kumpulkan pelanggan_id yang terdampak dari kunjungan batch ini
     *   2. Hapus kunjungan dari batch ini
     *   3. Recalculate total_kedatangan, total_biaya, class dari kunjungan yang tersisa
     *   4. Hard-delete (forceDelete) pelanggan yang tidak punya kunjungan tersisa
     */
    public function rollback(Request $request, string $batchId)
    {
        $batch = ImportBatch::where('batch_id', $batchId)->firstOrFail();

        // Cegah rollback ganda
        if ($batch->isRolledBack()) {
            return back()->with('error', 'Import ini sudah pernah di-rollback sebelumnya dan tidak dapat di-rollback lagi.');
        }

        $snapshots    = ImportBatchPelangganSnapshot::where('import_batch_id', $batchId)->get();
        $hasSnapshots = $snapshots->isNotEmpty();

        // Cek kunjungan yang ter-tag dengan import_batch_id
        $taggedKunjunganCount = Kunjungan::where('import_batch_id', $batchId)->count();

        // ── Deteksi mode rollback ────────────────────────────────────────────
        // Mode A: ada tagged kunjungan + ada snapshot → rollback penuh via snapshot
        // Mode B: ada tagged kunjungan, tidak ada snapshot → recalculate dari sisa kunjungan
        // Mode C: tidak ada tagged kunjungan, ada snapshot → cari kunjungan via timestamp + pelanggan_id
        // Mode X: tidak ada tagged kunjungan, tidak ada snapshot → tidak bisa rollback
        // ─────────────────────────────────────────────────────────────────────

        if ($taggedKunjunganCount === 0 && !$hasSnapshots) {
            return back()->with('error',
                'Tidak dapat melakukan rollback: batch ini tidak memiliki data kunjungan yang ter-tag maupun snapshot. ' .
                'Kemungkinan import dilakukan sebelum fitur rollback diaktifkan dan data tidak dapat diidentifikasi.'
            );
        }

        // Mode C: tidak ada tagged kunjungan tapi ada snapshot
        // → cari kunjungan via pelanggan_id dari snapshot + window waktu import
        $modeC_kunjunganIds = [];
        if ($taggedKunjunganCount === 0 && $hasSnapshots) {
            $affectedPelangganIds = $snapshots->pluck('pelanggan_id')->unique()->values()->toArray();
            $importedAt           = $batch->imported_at;

            // Cari batch sebelumnya (cabang sama) untuk dijadikan batas bawah window
            // Ini mencegah kunjungan dari batch sebelumnya ikut terhapus
            $previousBatch = ImportBatch::where('cabang_id', $batch->cabang_id)
                ->where('imported_at', '<', $importedAt)
                ->orderBy('imported_at', 'desc')
                ->first();

            // Window start: tepat setelah batch sebelumnya selesai (exclusive)
            // Jika tidak ada batch sebelumnya, gunakan 60 menit sebelum imported_at
            $windowStart = $previousBatch
                ? $previousBatch->imported_at   // strictly > ini (lihat query di bawah)
                : $importedAt->copy()->subMinutes(60);

            // Window end: 60 menit setelah imported_at (toleransi import lambat)
            $windowEnd = $importedAt->copy()->addMinutes(60);

            // Gunakan strictly > windowStart agar kunjungan batch sebelumnya tidak ikut terhapus
            $query = Kunjungan::whereIn('pelanggan_id', $affectedPelangganIds)
                ->where('created_at', '<=', $windowEnd)
                ->whereNull('import_batch_id');

            if ($previousBatch) {
                // Strictly greater than: kunjungan batch sebelumnya (created_at = previousBatch.imported_at) tidak ikut
                $query->where('created_at', '>', $windowStart);
            } else {
                // Tidak ada batch sebelumnya: gunakan >= windowStart
                $query->where('created_at', '>=', $windowStart);
            }

            $modeC_kunjunganIds = $query->pluck('id')->toArray();

            Log::info("Rollback MODE C: found " . count($modeC_kunjunganIds) . " kunjungan via timestamp for batch {$batchId}", [
                'window_start'          => $windowStart,
                'window_end'            => $windowEnd,
                'previous_batch'        => $previousBatch?->batch_id,
                'affected_pelanggan'    => count($affectedPelangganIds),
                'kunjungan_found'       => count($modeC_kunjunganIds),
            ]);
        }

        try {
            DB::transaction(function () use ($batch, $batchId, $snapshots, $hasSnapshots, $taggedKunjunganCount, $modeC_kunjunganIds) {

                if ($taggedKunjunganCount > 0 && $hasSnapshots) {
                    // ── MODE A: Snapshot-based rollback (import baru, lengkap) ─────────
                    Log::info("Rollback MODE A (snapshot + tagged kunjungan) for batch {$batchId}");

                    $deletedKunjungan = Kunjungan::where('import_batch_id', $batchId)->delete();
                    Log::info("Rollback MODE A: deleted {$deletedKunjungan} tagged kunjungan");

                    foreach ($snapshots as $snap) {
                        if ($snap->is_new_pelanggan) {
                            // forceDelete: hapus permanen agar re-import tidak kena duplicate PID
                            $pelanggan = Pelanggan::withTrashed()->find($snap->pelanggan_id);
                            if ($pelanggan) {
                                $pelanggan->forceDelete();
                                Log::info("Rollback MODE A: hard-deleted new pelanggan ID {$snap->pelanggan_id}");
                            }
                        } else {
                            Pelanggan::where('id', $snap->pelanggan_id)->update([
                                'total_kedatangan' => $snap->total_kedatangan_before,
                                'total_biaya'      => $snap->total_biaya_before,
                                'class'            => $snap->class_before,
                            ]);
                            Log::info("Rollback MODE A: restored pelanggan ID {$snap->pelanggan_id}");
                        }
                    }
                    // ─────────────────────────────────────────────────────────────────

                } elseif ($taggedKunjunganCount > 0 && !$hasSnapshots) {
                    // ── MODE B: Recalculate (ada tagged kunjungan, tidak ada snapshot) ─
                    Log::info("Rollback MODE B (recalculate, no snapshot) for batch {$batchId}");

                    $affectedPelangganIds = Kunjungan::where('import_batch_id', $batchId)
                        ->pluck('pelanggan_id')->unique()->values()->toArray();

                    $deletedKunjungan = Kunjungan::where('import_batch_id', $batchId)->delete();
                    Log::info("Rollback MODE B: deleted {$deletedKunjungan} tagged kunjungan");

                    foreach ($affectedPelangganIds as $pelangganId) {
                        $pelanggan = Pelanggan::find($pelangganId);
                        if (!$pelanggan) continue;

                        $remainingStats = Kunjungan::where('pelanggan_id', $pelangganId)
                            ->selectRaw('SUM(total_kedatangan) as total_kd, SUM(biaya) as total_biaya_sum, COUNT(*) as kunjungan_count')
                            ->first();

                        $remainingCount = (int) ($remainingStats->kunjungan_count ?? 0);

                        if ($remainingCount === 0) {
                            // forceDelete: hapus permanen agar re-import tidak kena duplicate PID
                            $pelanggan->forceDelete();
                            Log::info("Rollback MODE B: hard-deleted pelanggan ID {$pelangganId}");
                        } else {
                            $newTotalKedatangan = (int) ($remainingStats->total_kd ?? 0);
                            $newTotalBiaya      = (float) ($remainingStats->total_biaya_sum ?? 0);
                            $hasHighValue       = Kunjungan::where('pelanggan_id', $pelangganId)->where('biaya', '>=', 4000000)->exists();
                            $newClass           = Pelanggan::calculateClass($newTotalKedatangan, $newTotalBiaya, $hasHighValue, (bool) $pelanggan->is_pelanggan_khusus);

                            $pelanggan->update([
                                'total_kedatangan' => $newTotalKedatangan,
                                'total_biaya'      => $newTotalBiaya,
                                'class'            => $newClass,
                            ]);
                            Log::info("Rollback MODE B: recalculated pelanggan ID {$pelangganId}");
                        }
                    }
                    // ─────────────────────────────────────────────────────────────────

                } else {
                    // ── MODE C: Snapshot ada, kunjungan tidak ter-tag (import lama) ───
                    Log::info("Rollback MODE C (snapshot + timestamp-based kunjungan) for batch {$batchId}");

                    // Hapus kunjungan yang ditemukan via timestamp + pelanggan_id
                    if (!empty($modeC_kunjunganIds)) {
                        $deletedKunjungan = Kunjungan::whereIn('id', $modeC_kunjunganIds)->delete();
                        Log::info("Rollback MODE C: deleted {$deletedKunjungan} kunjungan via timestamp lookup");
                    } else {
                        Log::warning("Rollback MODE C: no kunjungan found via timestamp, proceeding with snapshot restore only");
                    }

                    // Restore pelanggan dari snapshot
                    foreach ($snapshots as $snap) {
                        if ($snap->is_new_pelanggan) {
                            // forceDelete: hapus permanen agar re-import tidak kena duplicate PID
                            $pelanggan = Pelanggan::withTrashed()->find($snap->pelanggan_id);
                            if ($pelanggan) {
                                $pelanggan->forceDelete();
                                Log::info("Rollback MODE C: hard-deleted new pelanggan ID {$snap->pelanggan_id}");
                            }
                        } else {
                            Pelanggan::where('id', $snap->pelanggan_id)->update([
                                'total_kedatangan' => $snap->total_kedatangan_before,
                                'total_biaya'      => $snap->total_biaya_before,
                                'class'            => $snap->class_before,
                            ]);
                            Log::info("Rollback MODE C: restored pelanggan ID {$snap->pelanggan_id} from snapshot");
                        }
                    }
                    // ─────────────────────────────────────────────────────────────────
                }

                // STEP FINAL: Tandai batch sebagai rolled_back
                $batch->update([
                    'status'         => 'rolled_back',
                    'rolled_back_at' => now(),
                    'rolled_back_by' => Auth::id(),
                ]);

                // Catat ke activity log
                $mode = $taggedKunjunganCount > 0
                    ? ($hasSnapshots ? 'A-snapshot' : 'B-recalculate')
                    : 'C-timestamp';

                ActivityLog::record(
                    action: 'rollback',
                    module: 'import',
                    description: "Rollback import batch '{$batch->filename}' ({$batch->total_rows} baris) mode {$mode} oleh " . (Auth::user()->name ?? 'IT'),
                    userId: Auth::id(),
                    username: Auth::user()->username ?? Auth::user()->name ?? 'IT',
                    role: Auth::user()->role?->name ?? 'IT',
                    ipAddress: request()->ip(),
                    userAgent: request()->userAgent()
                );
            });

            Log::info("Rollback completed for batch {$batchId}", ['user' => Auth::id()]);

            $modeInfo = $taggedKunjunganCount > 0
                ? ($hasSnapshots ? 'Data dikembalikan dari snapshot.' : 'Data dihitung ulang dari kunjungan tersisa.')
                : 'Data dikembalikan dari snapshot (kunjungan diidentifikasi via timestamp import).';

            return back()->with('success',
                "Rollback berhasil! Data import '{$batch->filename}' ({$batch->total_rows} baris) telah dikembalikan. {$modeInfo}"
            );

        } catch (\Exception $e) {
            Log::error("Rollback failed for batch {$batchId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Rollback gagal: ' . $e->getMessage());
        }
    }
}
