<?php

namespace App\Imports;

use App\Models\Pelanggan;
use App\Models\Kunjungan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class KunjunganImport implements ToCollection, WithStartRow
{
    public function startRow(): int
    {
        return 2;
    }

    public function collection(Collection $rows)
    {
        $processedCount = 0;
        $errorCount = 0;
        
        Log::info('Starting KunjunganImport collection processing', ['total_rows' => $rows->count()]);
        
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because we start from row 2
            
            try {
                // Convert row to array for safe access
                $rowArray = $row->toArray();
                
                // Skip rows with insufficient data (need at least 5 columns)
                if (count($rowArray) < 5) {
                    Log::debug("Row $rowNumber skipped: insufficient columns", ['column_count' => count($rowArray), 'data' => $rowArray]);
                    continue;
                }

                // Safely get values with null coalescing
                $nik = isset($rowArray[0]) ? trim((string)$rowArray[0]) : '';
                $nama = isset($rowArray[1]) ? trim((string)$rowArray[1]) : '';
                $alamat = isset($rowArray[2]) ? trim((string)$rowArray[2]) : '';
                $tanggalKunjungan = $rowArray[3] ?? null;
                $biaya = $rowArray[4] ?? null;

                // Skip empty rows
                if (empty($nik) || empty($nama)) {
                    Log::debug("Row $rowNumber skipped: empty NIK or nama", ['nik' => $nik, 'nama' => $nama]);
                    continue;
                }

                Log::debug("Processing row $rowNumber", [
                    'nik' => $nik,
                    'nama' => $nama,
                    'tanggal_raw' => $tanggalKunjungan,
                    'biaya_raw' => $biaya
                ]);

                // Process date - handle Excel serial date or string date
                $tanggalKunjungan = $this->processDate($tanggalKunjungan, $rowNumber);
                
                // Process biaya - handle various number formats
                $biaya = $this->processBiaya($biaya, $rowNumber);

                // Skip if date or biaya is invalid
                if (!$tanggalKunjungan) {
                    Log::warning("Row $rowNumber skipped: invalid date", [
                        'nik' => $nik,
                        'tanggal_raw' => $rowArray[3] ?? null
                    ]);
                    $errorCount++;
                    continue;
                }

                if ($biaya === null) {
                    Log::warning("Row $rowNumber skipped: invalid biaya", [
                        'nik' => $nik,
                        'biaya_raw' => $rowArray[4] ?? null
                    ]);
                    $errorCount++;
                    continue;
                }

                // Create or update pelanggan
                $pelanggan = Pelanggan::updateOrCreate(
                    ['nik' => $nik],
                    [
                        'nama' => $nama,
                        'alamat' => $alamat,
                    ]
                );

                Log::debug("Pelanggan created/updated", ['pelanggan_id' => $pelanggan->id, 'nik' => $nik]);

                // Create kunjungan with correct field name
                $kunjungan = Kunjungan::create([
                    'pelanggan_id' => $pelanggan->id,
                    'tanggal_kunjungan' => $tanggalKunjungan,
                    'biaya' => $biaya
                ]);

                Log::debug("Kunjungan created", ['kunjungan_id' => $kunjungan->id]);

                // Recalculate class
                $total = $pelanggan->kunjungans()->sum('biaya');
                $class = $this->getClass($total);
                $pelanggan->update(['class' => $class]);
                
                $processedCount++;
                Log::info("Row $rowNumber processed successfully", ['nik' => $nik, 'pelanggan_id' => $pelanggan->id]);

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
                // Excel serial date to PHP DateTime
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
                    if ($date && $date->year > 2000 && $date->year < 2100) {
                        return $date;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Last resort: try Carbon parse
            $date = Carbon::parse($dateString);
            if ($date->year > 2000 && $date->year < 2100) {
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
                // Has both comma and dot - Indonesian format: 1.234,56
                $cleanValue = str_replace('.', '', $cleanValue);
                $cleanValue = str_replace(',', '.', $cleanValue);
            } elseif (strpos($cleanValue, ',') !== false) {
                // Only comma - could be decimal separator
                $parts = explode(',', $cleanValue);
                if (count($parts) === 2 && strlen($parts[1]) <= 2) {
                    // Likely decimal: 1234,56 -> 1234.56
                    $cleanValue = str_replace(',', '.', $cleanValue);
                } else {
                    // Likely thousand separator: 1,234 -> 1234
                    $cleanValue = str_replace(',', '', $cleanValue);
                }
            }

            $result = (float) $cleanValue;
            
            if ($result < 0) {
                Log::warning("Negative biaya on row $rowNumber", ['value' => $value, 'cleaned' => $cleanValue]);
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

    /**
     * Get class based on total spending
     */
    private function getClass($total): string
    {
        if ($total >= 5000000) return 'Platinum';
        if ($total >= 1000000) return 'Gold';
        if ($total >= 100000) return 'Silver';
        return 'Basic';
    }
}
