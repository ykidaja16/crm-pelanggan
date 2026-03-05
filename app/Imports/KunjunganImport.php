<?php

namespace App\Imports;

use App\Models\Pelanggan;
use App\Models\Kunjungan;
use App\Models\Cabang;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
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
        
        // Pre-load cabangs for faster lookup
        $cabangs = Cabang::all()->keyBy('kode');
        
        // ALL OR NOTHING: Wrap entire import in single transaction
        // Jika ada error di baris manapun, SELURUH import akan di-rollback
        try {
            DB::transaction(function () use ($rows, $cabangs) {
                $this->processAllRows($rows, $cabangs);
            });
            
            Log::info('KunjunganImport completed successfully - All rows committed');
            
        } catch (\Exception $e) {
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
    private function processAllRows(Collection $rows, $cabangs): void
    {
        $processedCount = 0;
        $errorCount = 0;
        
        // STEP 1: Aggregate data by PID (handle duplicates within file)
        // Array untuk menyimpan data yang sudah digabung per PID
        $aggregatedData = []; // Key: PID, Value: aggregated data
        
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because we start from row 2
            
            try {
                // Convert row to array for safe access
                $rowArray = $row->toArray();
                
                // New format requires at least 10 columns
                if (count($rowArray) < 10) {
                    Log::debug("Row $rowNumber skipped: insufficient columns", [
                        'column_count' => count($rowArray), 
                        'data' => $rowArray
                    ]);
                    continue;
                }

                // Parse new 10-column format:
                // 0: No, 1: Nama Pasien, 2: Total Kedatangan, 3: Tanggal Kedatangan, 
                // 4: Total (biaya), 5: No Telpon, 6: DOB, 7: PID, 8: Alamat, 9: Kota
                
                $no = isset($rowArray[0]) ? (int) $rowArray[0] : null;
                $namaPasien = isset($rowArray[1]) ? trim((string)$rowArray[1]) : '';
                $totalKedatangan = isset($rowArray[2]) ? (int) $rowArray[2] : 0;
                $tanggalKedatangan = $rowArray[3] ?? null;
                $totalBiaya = $rowArray[4] ?? null;
                $noTelp = isset($rowArray[5]) ? trim((string)$rowArray[5]) : null;
                $dob = $rowArray[6] ?? null;
                $pid = isset($rowArray[7]) ? trim((string)$rowArray[7]) : '';
                $alamat = isset($rowArray[8]) ? trim((string)$rowArray[8]) : '';
                $kota = isset($rowArray[9]) ? trim((string)$rowArray[9]) : '';

                // Skip empty rows - PID and Nama are required
                if (empty($pid) || empty($namaPasien)) {
                    Log::debug("Row $rowNumber skipped: empty PID or Nama", [
                        'pid' => $pid, 
                        'nama' => $namaPasien
                    ]);
                    continue;
                }

                // Extract cabang kode from PID (first 2 characters)
                $cabangKode = strtoupper(substr($pid, 0, 2));
                
                // Validate cabang kode
                if (!isset($cabangs[$cabangKode])) {
                    Log::warning("Row $rowNumber error: invalid cabang code in PID", [
                        'pid' => $pid,
                        'cabang_kode' => $cabangKode
                    ]);
                    throw new \Exception("Baris $rowNumber: Kode cabang '$cabangKode' tidak valid untuk PID '$pid'");
                }
                
                // Process dates and biaya for aggregation
                $tanggalKedatanganObj = $this->processDate($tanggalKedatangan, $rowNumber);
                $dobObj = $this->processDate($dob, $rowNumber);
                $biaya = $this->processBiaya($totalBiaya, $rowNumber);

                // Validate required fields
                if (!$tanggalKedatanganObj) {
                    Log::warning("Row $rowNumber error: invalid tanggal kedatangan", [
                        'pid' => $pid,
                        'tanggal_raw' => $rowArray[3] ?? null
                    ]);
                    throw new \Exception("Baris $rowNumber: Tanggal kedatangan tidak valid untuk PID '$pid'");
                }

                if ($biaya === null) {
                    Log::warning("Row $rowNumber error: invalid biaya", [
                        'pid' => $pid,
                        'biaya_raw' => $rowArray[4] ?? null
                    ]);
                    throw new \Exception("Baris $rowNumber: Biaya tidak valid untuk PID '$pid'");
                }

                // AGGREGATE DATA: Jika PID sudah ada, jumlahkan data; jika belum, buat entry baru
                if (isset($aggregatedData[$pid])) {
                    // Jumlahkan Total Kedatangan dari semua baris dengan PID sama
                    $aggregatedData[$pid]['total_kedatangan'] += $totalKedatangan;
                    
                    // Jumlahkan Total Biaya dari semua baris dengan PID sama
                    $aggregatedData[$pid]['total_biaya'] += $biaya;
                    
                    // Ambil tanggal yang paling terbaru menggunakan Carbon->gt() (greater than)
                    if ($tanggalKedatanganObj->gt($aggregatedData[$pid]['tanggal_kunjungan'])) {
                        $aggregatedData[$pid]['tanggal_kunjungan'] = $tanggalKedatanganObj;
                        // Update 'no' ke yang terbaru juga
                        $aggregatedData[$pid]['no'] = $no;
                    }
                    
                    // Catat jumlah duplicate untuk logging
                    $aggregatedData[$pid]['duplicate_count']++;
                    
                    Log::debug("Aggregating duplicate PID: $pid", [
                        'row' => $rowNumber,
                        'total_kedatangan_sum' => $aggregatedData[$pid]['total_kedatangan'],
                        'total_biaya_sum' => $aggregatedData[$pid]['total_biaya'],
                        'latest_tanggal' => $aggregatedData[$pid]['tanggal_kunjungan']->format('Y-m-d')
                    ]);
                } else {
                    // Entry pertama untuk PID ini - simpan data awal
                    $aggregatedData[$pid] = [
                        'no' => $no,
                        'pid' => $pid,
                        'nama' => $namaPasien,
                        'total_kedatangan' => $totalKedatangan,
                        'tanggal_kunjungan' => $tanggalKedatanganObj,
                        'total_biaya' => $biaya,
                        'no_telp' => $noTelp,
                        'dob' => $dobObj,
                        'alamat' => $alamat,
                        'kota' => $kota,
                        'cabang_kode' => $cabangKode,
                        'duplicate_count' => 1
                    ];
                }

            } catch (\Exception $e) {
                $errorCount++;
                Log::error("Error processing row $rowNumber", [
                    'error' => $e->getMessage(),
                    'row_data' => $row->toArray()
                ]);
                
                // Re-throw untuk trigger rollback seluruh transaction
                throw $e;
            }
        }
        
        // STEP 2: Process aggregated data
        // Proses data yang sudah digabung, simpan ke database
        Log::info('Processing aggregated data', [
            'unique_pids' => count($aggregatedData),
            'duplicates_found' => array_sum(array_column($aggregatedData, 'duplicate_count')) - count($aggregatedData)
        ]);
        
        foreach ($aggregatedData as $pid => $data) {
            try {
                $cabang = $cabangs[$data['cabang_kode']];
                
                // Proses baris aggregated - simpan ke database
                $this->processRow(
                    $data['no'],
                    $data['pid'],
                    $data['nama'],
                    $data['total_kedatangan'],
                    $data['tanggal_kunjungan'],
                    $data['total_biaya'],
                    $data['no_telp'],
                    $data['dob'],
                    $data['alamat'],
                    $data['kota'],
                    $cabang
                );
                
                $processedCount++;

                Log::info("Aggregated row processed successfully", [
                    'pid' => $pid,
                    'total_kedatangan' => $data['total_kedatangan'],
                    'total_biaya' => $data['total_biaya'],
                    'duplicate_count' => $data['duplicate_count']
                ]);
                
            } catch (\Exception $e) {
                $errorCount++;
                Log::error("Error processing aggregated PID: $pid", [
                    'error' => $e->getMessage(),
                    'data' => $data
                ]);
                
                throw $e;
            }
        }
        
        Log::info('All rows processed within transaction', [
            'processed' => $processedCount,
            'errors' => $errorCount,
            'duplicates_merged' => array_sum(array_column($aggregatedData, 'duplicate_count')) - count($aggregatedData)
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
        $biaya, $noTelp, $dob, $alamat, $kota, $cabang
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
        
        // Hitung class berdasarkan total yang sudah diakumulasi
        $newClass = Pelanggan::calculateClass($pelanggan->total_kedatangan, $pelanggan->total_biaya);
        $pelanggan->class = $newClass;
        
        // Simpan pelanggan untuk dapatkan ID
        $pelanggan->save();
        
        // Catat riwayat perubahan kelas jika berbeda (hanya untuk pelanggan existing)
        if ($isExisting && $oldClass !== $newClass) {
            $pelanggan->classHistories()->create([
                'previous_class' => $oldClass,
                'new_class'      => $newClass,
                'changed_at'     => $tanggalKedatangan ?? now(),
                'changed_by'     => Auth::check() ? Auth::id() : null,
                'reason'         => 'Perubahan dari import data Excel',
            ]);
            
            Log::info("Class change recorded during import", [
                'pid' => $pid,
                'old_class' => $oldClass,
                'new_class' => $newClass
            ]);
        }

        Log::debug("Pelanggan created/updated", [
            'pelanggan_id' => $pelanggan->id, 
            'pid' => $pid,
            'total_kedatangan' => $totalKedatangan,
            'total_biaya' => $biaya,
            'class' => $pelanggan->class
        ]);

        // Buat record kunjungan
        $kunjungan = Kunjungan::create([
            'no' => $no,
            'pelanggan_id' => $pelanggan->id,
            'cabang_id' => $cabang->id,
            'tanggal_kunjungan' => $tanggalKedatangan,
            'biaya' => $biaya,
            'total_kedatangan' => $totalKedatangan,
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
