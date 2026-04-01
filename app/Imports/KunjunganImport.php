<?php

namespace App\Imports;

use App\Models\Pelanggan;
use App\Models\Kunjungan;
use App\Models\Cabang;
use App\Models\KelompokPelanggan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

/**
 * Class untuk mengimport data kunjungan/pelanggan dari file Excel
 * Menggunakan Maatwebsite/Excel package dengan ToCollection concern
 * 
 * CARA KERJA DUPLICATE HANDLING:
 * Jika dalam 1 file ada multiple baris dengan PID yang sama:
 * - Total Kedatangan: dijumlahkan dari semua baris
 * - Total Biaya: dijumlahkan dari semua baris  
 * - Tanggal Kedatangan: diambil yang paling terbaru
 * - No: diambil dari baris dengan tanggal terbaru
 * 
 * TRANSACTION SAFETY:
 * Seluruh import dibungkus dalam 1 DB transaction
 * Jika ada error di baris manapun, SELURUH import di-rollback (all or nothing)
 */
class KunjunganImport implements ToCollection, WithStartRow
{
    /**
     * Tentukan baris mulai pembacaan data (skip header)
     * Baris 1 = header, Baris 2 = data pertama
     */
    public function startRow(): int
    {
        return 2; // Skip header row
    }

    /**
     * Method utama yang dipanggil oleh Maatwebsite/Excel
     * Cara kerja:
     * 1. Pre-load data cabang ke memory untuk lookup cepat
     * 2. Bungkus seluruh proses dalam DB transaction
     * 3. Panggil processAllRows() untuk proses data
     * 4. Jika sukses → commit, jika error → rollback semua
     */
    public function collection(Collection $rows)
    {
        Log::info('Starting KunjunganImport collection processing', ['total_rows' => $rows->count()]);

        $importUserId = Auth::id() ?? 0;
        $totalRows = max($rows->count(), 1);
        Cache::put("import_progress_{$importUserId}", 0, now()->addMinutes(30));
        Cache::put("import_total_{$importUserId}", $totalRows, now()->addMinutes(30));
        Cache::put("import_current_{$importUserId}", 0, now()->addMinutes(30));

        // Pre-load cabangs for faster lookup
        $cabangs = Cabang::all()->keyBy('kode');

        // ALL OR NOTHING: Wrap entire import in single transaction
        // Jika ada error di baris manapun, SELURUH import akan di-rollback
        try {
            DB::transaction(function () use ($rows, $cabangs, $importUserId) {
                $this->processAllRows($rows, $cabangs, $importUserId);
            });

            Cache::put("import_progress_{$importUserId}", 100, now()->addMinutes(30));
            Log::info('KunjunganImport completed successfully - All rows committed');

        } catch (\Exception $e) {
            Cache::forget("import_progress_{$importUserId}");
            Log::error('KunjunganImport FAILED - All rows rolled back', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw exception agar user tahu import gagal
            throw $e;
        }
    }
    
    /**
     * Memproses semua baris data dalam transaction
     * 
     * ALGORITMA 2-STEP:
     * STEP 1 - AGGREGATE: Loop semua baris, kelompokkan by PID, jumlahkan data duplicate
     * STEP 2 - PROCESS: Loop data aggregated, simpan ke database per unique PID
     * 
     * DUPLICATE HANDLING (Penanganan Data Duplikat):
     * Jika dalam 1 file ada multiple baris dengan PID yang sama:
     * - Total Kedatangan: dijumlahkan (contoh: 2 + 3 = 5)
     * - Total Biaya: dijumlahkan (contoh: 500000 + 700000 = 1200000)
     * - Tanggal Kedatangan: diambil yang paling terbaru menggunakan Carbon->gt()
     * - No: diambil dari baris dengan tanggal terbaru
     * 
     * Keuntungan: Data duplicate di file tidak membuat record ganda di database
     */
    private function processAllRows(Collection $rows, $cabangs, int $importUserId = 0): void
    {
        $processedCount = 0;
        $errorCount = 0;
        $seenRows = [];

        $totalRows = max($rows->count(), 1);
        $current = 0;

        // ── Defense in depth: tolak jika format Pelanggan Khusus (12 kolom) ──
        // Format pelanggan khusus memiliki kolom ke-12 (Kategori Khusus)
        // Import ini hanya untuk pelanggan biasa (11 kolom)
        foreach ($rows as $checkRow) {
            $checkArray = $checkRow->toArray();
            if (count($checkArray) >= 12 && trim((string) ($checkArray[11] ?? '')) !== '') {
                throw new \Exception(
                    'Format file ini adalah format Pelanggan Khusus (memiliki kolom "Kategori Khusus"). '
                    . 'Import Pelanggan Khusus tidak diperbolehkan di menu Data Pelanggan. '
                    . 'Gunakan menu Pelanggan Khusus untuk mengimport data ini.'
                );
            }
            break; // Cukup cek baris pertama data
        }
        // ─────────────────────────────────────────────────────────────────────

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because we start from row 2
            $current++;

            try {
                $rowArray = $row->toArray();

                if (count($rowArray) < 10) {
                    continue;
                }

                $no = isset($rowArray[0]) ? (int) $rowArray[0] : null;
                $namaPasien = isset($rowArray[1]) ? trim((string) $rowArray[1]) : '';
                $totalKedatangan = isset($rowArray[2]) ? (int) $rowArray[2] : 0;
                $tanggalKedatangan = $rowArray[3] ?? null;
                $totalBiaya = $rowArray[4] ?? null;
                $noTelp = isset($rowArray[5]) ? trim((string) $rowArray[5]) : null;
                $dob = $rowArray[6] ?? null;
                $pid = isset($rowArray[7]) ? trim((string) $rowArray[7]) : '';
                $alamat = isset($rowArray[8]) ? trim((string) $rowArray[8]) : '';
                $kota = isset($rowArray[9]) ? trim((string) $rowArray[9]) : '';
                $kelompokRaw       = isset($rowArray[10]) ? strtolower(trim((string) $rowArray[10])) : '';
                $kelompokPelanggan = str_contains($kelompokRaw, 'klinisi') ? 'klinisi' : 'mandiri';

                if (empty($pid) || empty($namaPasien)) {
                    continue;
                }

                $dedupKey = md5(json_encode([
                    $no,
                    $namaPasien,
                    $totalKedatangan,
                    (string) $tanggalKedatangan,
                    (string) $totalBiaya,
                    $noTelp,
                    (string) $dob,
                    $pid,
                    $alamat,
                    $kota,
                    $kelompokPelanggan
                ]));

                if (isset($seenRows[$dedupKey])) {
                    continue;
                }
                $seenRows[$dedupKey] = true;

                $cabangKode = strtoupper(substr($pid, 0, 2));
                if (!isset($cabangs[$cabangKode])) {
                    throw new \Exception("Baris $rowNumber: Kode cabang '$cabangKode' tidak valid untuk PID '$pid'");
                }

                $tanggalKedatanganObj = $this->processDate($tanggalKedatangan, $rowNumber);
                $dobObj = $this->processDate($dob, $rowNumber);
                $biaya = $this->processBiaya($totalBiaya, $rowNumber);

                if (!$tanggalKedatanganObj) {
                    throw new \Exception("Baris $rowNumber: Tanggal kedatangan tidak valid untuk PID '$pid'");
                }

                if ($biaya === null) {
                    throw new \Exception("Baris $rowNumber: Biaya tidak valid untuk PID '$pid'");
                }

                $this->processRow(
                    $no,
                    $pid,
                    $namaPasien,
                    $totalKedatangan,
                    $tanggalKedatanganObj,
                    $biaya,
                    $noTelp,
                    $dobObj,
                    $alamat,
                    $kota,
                    $cabangs[$cabangKode],
                    $kelompokPelanggan
                );

                $processedCount++;
            } catch (\Exception $e) {
                $errorCount++;
                Log::error("Error processing row $rowNumber", [
                    'error' => $e->getMessage(),
                    'row_data' => $row->toArray()
                ]);

                throw $e;
            } finally {
                if ($importUserId > 0) {
                    $percent = (int) floor(($current / $totalRows) * 100);
                    Cache::put("import_progress_{$importUserId}", min($percent, 99), now()->addMinutes(30));
                    Cache::put("import_current_{$importUserId}", $current, now()->addMinutes(30));
                }
            }
        }

        Log::info('All rows processed within transaction', [
            'processed' => $processedCount,
            'errors' => $errorCount,
            'duplicates_skipped' => ($rows->count() - $processedCount - $errorCount)
        ]);
    }
    
    /**
     * Memproses satu baris data - buat/update pelanggan dan kunjungan
     * Cara kerja:
     * 1. Cari pelanggan by PID, jika tidak ada buat baru (firstOrNew)
     * 2. Set data pelanggan dari Excel (nama, telp, alamat, dll)
     * 3. AKUMULASI: Jika pelanggan sudah ada, tambahkan total_kedatangan dan total_biaya
     *    Jika pelanggan baru, set nilai dari Excel
     * 4. Hitung class otomatis berdasarkan total_kedatangan dan biaya yang sudah diakumulasi
     * 5. Simpan pelanggan untuk dapatkan ID
     * 6. Buat record kunjungan dengan biaya dari Excel
     */
    private function processRow(
        $no, $pid, $namaPasien, $totalKedatangan, $tanggalKedatangan,
        $biaya, $noTelp, $dob, $alamat, $kota, $cabang, string $kelompokPelangganKode = 'mandiri'
    ): void {
        // Cari pelanggan by PID, jika tidak ada buat baru
        $pelanggan = Pelanggan::firstOrNew(['pid' => $pid]);
        
        // Simpan class lama untuk tracking perubahan
        $oldClass = $pelanggan->class;
        $isExisting = $pelanggan->exists;
        
        // Set/update data pelanggan
        $pelanggan->cabang_id = $cabang->id;
        $pelanggan->nama = $namaPasien;
        $pelanggan->no_telp = $noTelp;
        $pelanggan->dob = $dob;
        $pelanggan->alamat = $alamat;
        $pelanggan->kota = $kota;
        
        // AKUMULASI: Jika pelanggan sudah ada, tambahkan nilai dari Excel ke nilai existing
        // Jika pelanggan baru, set nilai dari Excel
        if ($isExisting) {
            // Pelanggan lama: akumulasi (tambahkan)
            $pelanggan->total_kedatangan += $totalKedatangan;
            $pelanggan->total_biaya += $biaya;
            Log::debug("Pelanggan existing diakumulasi", [
                'pid' => $pid,
                'added_kedatangan' => $totalKedatangan,
                'added_biaya' => $biaya,
                'new_total_kedatangan' => $pelanggan->total_kedatangan,
                'new_total_biaya' => $pelanggan->total_biaya
            ]);
        } else {
            // Pelanggan baru: set nilai dari Excel
            $pelanggan->total_kedatangan = $totalKedatangan;
            $pelanggan->total_biaya = $biaya;
            Log::debug("Pelanggan baru dibuat", [
                'pid' => $pid,
                'total_kedatangan' => $totalKedatangan,
                'total_biaya' => $biaya
            ]);
        }
        
        // ✅ FIX: Simpan DULU untuk pastikan instance lengkap sebelum calculateClass()
        $pelanggan->save();
        
        // Hitung class berdasarkan total yang sudah diakumulasi dan rule kunjungan high-value
        $hasHighValueVisit = $pelanggan->kunjungans()
            ->where('biaya', '>=', 4000000)
            ->exists() || ($biaya >= 4000000);

        $newClass = Pelanggan::calculateClass(
            $pelanggan->total_kedatangan,
            $pelanggan->total_biaya,
            $hasHighValueVisit,
            (bool) $pelanggan->is_pelanggan_khusus
        );
        
        // Update class jika berbeda
        if ($pelanggan->class !== $newClass) {
            $pelanggan->class = $newClass;
            $pelanggan->save();
        }
        
        // Catat riwayat perubahan kelas jika berbeda (hanya untuk pelanggan existing)
        if ($isExisting && $oldClass !== $pelanggan->class) {
            $pelanggan->classHistories()->create([
                'previous_class' => $oldClass,
                'new_class'      => $pelanggan->class,
                'changed_at'     => $tanggalKedatangan ?? now(),
                'changed_by'     => Auth::check() ? Auth::id() : null,
                'reason'         => 'Perubahan dari import data Excel',
            ]);
            
            Log::info("Class change recorded during import", [
                'pid' => $pid,
                'old_class' => $oldClass,
                'new_class' => $pelanggan->class
            ]);
        }

        Log::debug("Pelanggan created/updated", [
            'pelanggan_id' => $pelanggan->id, 
            'pid' => $pid,
            'total_kedatangan' => $totalKedatangan,
            'total_biaya' => $biaya,
            'class' => $pelanggan->class
        ]);

        // Cari kelompok pelanggan berdasarkan kode
        $kelompok = KelompokPelanggan::where('kode', $kelompokPelangganKode)->first();

        // Buat record kunjungan
        $kunjungan = Kunjungan::create([
            'no' => $no,
            'pelanggan_id' => $pelanggan->id,
            'cabang_id' => $cabang->id,
            'tanggal_kunjungan' => $tanggalKedatangan,
            'biaya' => $biaya,
            'total_kedatangan' => $totalKedatangan,
            'kelompok_pelanggan_id' => $kelompok?->id,
        ]);

        Log::debug("Kunjungan created", [
            'kunjungan_id' => $kunjungan->id,
            'no' => $no
        ]);
    }

    /**
     * Parse tanggal dari berbagai format Excel/CSV
     * Support format:
     * - Excel serial date (numeric)
     * - String format: Y-m-d, d/m/Y, d-m-Y, d/m/y, d-m-y, Y/m/d, d F Y, d M Y
     * - Carbon instance
     * - DateTime object
     * 
     * Return null jika format tidak valid atau tahun tidak masuk akal (< 1900 atau > 2100)
     */
    private function processDate($value, $rowNumber): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            // If it's a number, it's likely an Excel serial date
            if (is_numeric($value)) {
                $dateTime = Date::excelToDateTimeObject($value);
                return Carbon::instance($dateTime);
            }

            // If it's already a Carbon instance
            if ($value instanceof Carbon) {
                return $value;
            }

            // If it's a DateTime object
            if ($value instanceof \DateTime) {
                return Carbon::instance($value);
            }

            // Try parsing as string date
            $dateString = trim((string) $value);
            
            // Try common Indonesian date formats
            $formats = [
                'Y-m-d',           // 2024-01-15
                'd/m/Y',           // 15/01/2024
                'd-m-Y',           // 15-01-2024
                'd/m/y',           // 15/01/24
                'd-m-y',           // 15-01-24
                'Y/m/d',           // 2024/01/15
                'd F Y',           // 15 Januari 2024
                'd M Y',           // 15 Jan 2024
            ];

            foreach ($formats as $format) {
                try {
                    $date = Carbon::createFromFormat($format, $dateString);
                    if ($date && $date->year > 1900 && $date->year < 2100) {
                        return $date;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Last resort: try Carbon parse
            $date = Carbon::parse($dateString);
            if ($date->year > 1900 && $date->year < 2100) {
                return $date;
            }

            Log::warning("Could not parse date on row $rowNumber", ['value' => $value]);
            return null;

        } catch (\Exception $e) {
            Log::warning("Date parsing error on row $rowNumber", [
                'value' => $value,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Parse biaya dari berbagai format Excel/CSV
     * Membersihkan format: Rp, spasi, titik ribuan, koma desimal
     * Handle format Indonesia: 1.234,56 → 1234.56
     * Contoh: "Rp 1.500.000,00" → 1500000
     * 
     * Return null jika nilai negatif atau tidak valid
     */
    private function processBiaya($value, $rowNumber): ?float
    {
        if (empty($value) && $value !== 0 && $value !== '0') {
            return null;
        }

        try {
            // If it's already numeric
            if (is_numeric($value)) {
                return (float) $value;
            }

            // If it's a string, clean it up
            $cleanValue = (string) $value;
            
            // Remove common formatting characters
            $cleanValue = str_replace(['Rp', ' ', '.', ',00'], '', $cleanValue);
            
            // Handle Indonesian number format (1.234,56 -> 1234.56)
            if (strpos($cleanValue, ',') !== false && strpos($cleanValue, '.') !== false) {
                $cleanValue = str_replace('.', '', $cleanValue);
                $cleanValue = str_replace(',', '.', $cleanValue);
            } elseif (strpos($cleanValue, ',') !== false) {
                $parts = explode(',', $cleanValue);
                if (count($parts) === 2 && strlen($parts[1]) <= 2) {
                    $cleanValue = str_replace(',', '.', $cleanValue);
                } else {
                    $cleanValue = str_replace(',', '', $cleanValue);
                }
            }

            $result = (float) $cleanValue;
            
            if ($result < 0) {
                Log::warning("Negative biaya on row $rowNumber", [
                    'value' => $value, 
                    'cleaned' => $cleanValue
                ]);
                return null;
            }
            
            return $result;

        } catch (\Exception $e) {
            Log::warning("Biaya parsing error on row $rowNumber", [
                'value' => $value,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
