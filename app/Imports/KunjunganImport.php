<?php

namespace App\Imports;

use App\Models\Pelanggan;
use App\Models\Kunjungan;
use App\Models\Cabang;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class KunjunganImport implements ToCollection, WithStartRow
{
    public function startRow(): int
    {
        return 2; // Skip header row
    }

    public function collection(Collection $rows)
    {
        $processedCount = 0;
        $errorCount = 0;
        
        Log::info('Starting KunjunganImport collection processing', ['total_rows' => $rows->count()]);
        
        // Pre-load cabangs for faster lookup
        $cabangs = Cabang::all()->keyBy('kode');
        
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
                    Log::warning("Row $rowNumber skipped: invalid cabang code in PID", [
                        'pid' => $pid,
                        'cabang_kode' => $cabangKode
                    ]);
                    $errorCount++;
                    continue;
                }
                
                $cabang = $cabangs[$cabangKode];

                Log::debug("Processing row $rowNumber", [
                    'no' => $no,
                    'pid' => $pid,
                    'nama' => $namaPasien,
                    'cabang' => $cabangKode,
                    'tanggal_raw' => $tanggalKedatangan,
                    'biaya_raw' => $totalBiaya
                ]);

                // Process dates
                $tanggalKedatangan = $this->processDate($tanggalKedatangan, $rowNumber);
                $dob = $this->processDate($dob, $rowNumber);
                
                // Process biaya
                $biaya = $this->processBiaya($totalBiaya, $rowNumber);

                // Skip if date or biaya is invalid
                if (!$tanggalKedatangan) {
                    Log::warning("Row $rowNumber skipped: invalid tanggal kedatangan", [
                        'pid' => $pid,
                        'tanggal_raw' => $rowArray[3] ?? null
                    ]);
                    $errorCount++;
                    continue;
                }

                if ($biaya === null) {
                    Log::warning("Row $rowNumber skipped: invalid biaya", [
                        'pid' => $pid,
                        'biaya_raw' => $rowArray[4] ?? null
                    ]);
                    $errorCount++;
                    continue;
                }

                // Process in transaction
                DB::transaction(function () use (
                    $no, $pid, $namaPasien, $totalKedatangan, $tanggalKedatangan,
                    $biaya, $noTelp, $dob, $alamat, $kota, $cabang, &$processedCount
                ) {
                    // Find or create pelanggan by PID
                    $pelanggan = Pelanggan::firstOrNew(['pid' => $pid]);
                    
                    // Set/update pelanggan data
                    $pelanggan->cabang_id = $cabang->id;
                    $pelanggan->nama = $namaPasien;
                    $pelanggan->no_telp = $noTelp;
                    $pelanggan->dob = $dob;
                    $pelanggan->alamat = $alamat;
                    $pelanggan->kota = $kota;
                    
                    // Save pelanggan first to get ID
                    $pelanggan->save();

                    Log::debug("Pelanggan created/updated", [
                        'pelanggan_id' => $pelanggan->id, 
                        'pid' => $pid
                    ]);

                    // Create kunjungan record
                    $kunjungan = Kunjungan::create([
                        'no' => $no,
                        'pelanggan_id' => $pelanggan->id,
                        'cabang_id' => $cabang->id,
                        'tanggal_kunjungan' => $tanggalKedatangan,
                        'biaya' => $biaya
                    ]);

                    Log::debug("Kunjungan created", [
                        'kunjungan_id' => $kunjungan->id,
                        'no' => $no
                    ]);

                    // Recalculate and update pelanggan stats
                    $pelanggan->updateStats();
                    
                    $processedCount++;
                });

                Log::info("Row $rowNumber processed successfully", [
                    'pid' => $pid, 
                    'pelanggan_id' => $pelanggan->id ?? null
                ]);

            } catch (\Exception $e) {
                $errorCount++;
                Log::error("Error processing row $rowNumber", [
                    'error' => $e->getMessage(),
                    'row_data' => $row->toArray(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        Log::info('KunjunganImport completed', [
            'processed' => $processedCount,
            'errors' => $errorCount
        ]);
    }

    /**
     * Process date from Excel - handle serial dates and various string formats
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
     * Process biaya - handle various number formats from Excel
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
