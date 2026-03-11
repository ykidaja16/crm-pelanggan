<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

use App\Models\Pelanggan;
use App\Models\Kunjungan;
use App\Models\Cabang;
use App\Models\KelompokPelanggan;
use App\Imports\KunjunganImport;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller untuk mengelola import dan export data pelanggan
 * Menangani: import Excel/CSV, export Excel, download template, bulk export, progress import
 */
class PelangganImportExportController extends Controller
{
    /**
     * Mengimport data pelanggan dari file Excel/CSV
     * Cara kerja:
     * 1. Validasi file (harus xlsx, xls, csv, atau txt)
     * 2. Baca file sesuai extension (CSV pakai readCsvFile, Excel pakai Excel::toArray)
     * 3. Validasi setiap baris: cek kolom cukup, PID valid, nama tidak mismatch dengan DB
     * 4. Jika ada error → return error tanpa import apa pun
     * 5. Jika valid → panggil processCsvImport atau KunjunganImport untuk proses data
     * 6. Return pesan sukses dengan jumlah data yang diproses
     */
    public function import(Request $request)
    {
        // Catat user yang melakukan import untuk keperluan log
        $userId = Auth::check() ? Auth::user()->id : 'guest';
        Log::info('Import process started', ['user' => $userId]);

        // Deteksi apakah request dari AJAX (fetch) atau form biasa
        $isAjax = $request->ajax() || $request->wantsJson();

        // ── Validasi akses cabang user ──────────────────────────────────────
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        $accessibleCabangIds = $authUser->getAccessibleCabangIds();

        $selectedCabangId = $request->input('import_cabang_id');
        if (!$selectedCabangId) {
            $msg = 'Pilih cabang terlebih dahulu sebelum melakukan import.';
            if ($isAjax) return response()->json(['success' => false, 'message' => $msg], 422);
            return back()->with('error', $msg);
        }

        if (!empty($accessibleCabangIds) && !in_array((int)$selectedCabangId, $accessibleCabangIds)) {
            $msg = 'Anda tidak memiliki akses ke cabang yang dipilih.';
            if ($isAjax) return response()->json(['success' => false, 'message' => $msg], 403);
            return back()->with('error', $msg);
        }

        $selectedCabang = Cabang::find($selectedCabangId);
        if (!$selectedCabang) {
            $msg = 'Cabang yang dipilih tidak ditemukan.';
            if ($isAjax) return response()->json(['success' => false, 'message' => $msg], 422);
            return back()->with('error', $msg);
        }
        // ───────────────────────────────────────────────────────────────────

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt'
        ], [
            'file.required' => 'File Excel/CSV wajib diupload.',
            'file.file' => 'File harus berupa file.',
            'file.mimes' => 'File harus berupa format Excel (xlsx, xls) atau CSV.'
        ]);

        try {
            Cache::put("import_progress_{$userId}", 0, now()->addMinutes(30));

            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension() ?? '');
            Log::info('File uploaded', ['filename' => $file->getClientOriginalName() ?? 'unknown', 'size' => $file->getSize(), 'extension' => $extension]);

            if ($extension === 'csv' || $extension === 'txt') {
                $rows = $this->readCsvFile($file);
            } else {
                $rows = Excel::toArray(null, $file);
            }

            $errors = [];
            $rowNumber = 0;
            $totalRows = 0;
            $validRows = 0;

            if (empty($rows) || empty($rows[0])) {
                Log::warning('Empty or invalid file');
                if ($isAjax) {
                    return response()->json(['success' => false, 'message' => 'File kosong atau tidak valid.'], 422);
                }
                return back()->with('error', 'File kosong atau tidak valid.');
            }

            Log::info('Excel file read successfully', ['sheet_count' => count($rows), 'first_sheet_rows' => count($rows[0])]);

            $cabangs = Cabang::all()->keyBy('kode');

            foreach ($rows[0] as $row) {
                $rowNumber++;

                if ($rowNumber == 1 && count($row) > 0 && strtolower(trim($row[0] ?? '')) == 'no') {
                    continue;
                }

                if (count($row) < 10) {
                    Log::debug("Row $rowNumber skipped: insufficient columns", ['column_count' => count($row)]);
                    continue;
                }

                $no   = trim($row[0] ?? '');
                $nama = trim($row[1] ?? '');
                $pid  = trim($row[7] ?? '');

                if (empty($pid) || empty($nama)) {
                    Log::debug("Row {$rowNumber} skipped: empty PID or nama");
                    continue;
                }

                $cabangKode = strtoupper(substr($pid, 0, 2));
                if (!isset($cabangs[$cabangKode])) {
                    $errors[] = "Baris {$rowNumber}: Kode cabang '{$cabangKode}' dalam PID '{$pid}' tidak valid.";
                    continue;
                }

                // Validasi PID prefix harus sesuai cabang yang dipilih
                if (strtoupper($cabangKode) !== strtoupper($selectedCabang->kode)) {
                    $errors[] = "Baris {$rowNumber}: PID '{$pid}' (prefix '{$cabangKode}') tidak sesuai dengan cabang yang dipilih '{$selectedCabang->nama}' (kode '{$selectedCabang->kode}').";
                    continue;
                }

                $totalRows++;

                $pelanggan = Pelanggan::where('pid', $pid)->first();

                if ($pelanggan) {
                    $dbNama = trim($pelanggan->nama ?? '');

                    if (strtolower($nama) !== strtolower($dbNama)) {
                        $errors[] = "Baris {$rowNumber}: PID {$pid} sudah terdaftar dengan nama '{$dbNama}'. Data Excel nama '{$nama}' tidak sesuai.";
                    } else {
                        $validRows++;
                    }
                } else {
                    $validRows++;
                }
            }

            Log::info('Validation completed', ['total_rows' => $totalRows, 'valid_rows' => $validRows, 'errors' => count($errors)]);

            if (!empty($errors)) {
                Log::warning('Import failed due to validation errors', ['error_count' => count($errors)]);
                if ($isAjax) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Import gagal! Beberapa data tidak sesuai dengan database.',
                        'errors'  => $errors
                    ], 422);
                }
                return back()->with('error', 'Import gagal! Beberapa data tidak sesuai dengan database.')
                             ->with('import_errors', $errors);
            }

            if ($validRows === 0) {
                Log::warning('No valid rows to import');
                if ($isAjax) {
                    return response()->json(['success' => false, 'message' => 'Tidak ada data valid untuk diimport.'], 422);
                }
                return back()->with('error', "Tidak ada data valid untuk diimport.");
            }

            Log::info('Starting import', ['valid_rows' => $validRows, 'file_type' => $extension]);

            if ($extension === 'csv' || $extension === 'txt') {
                $this->processCsvImport($rows[0], (int) $userId);
            } else {
                Excel::import(new KunjunganImport, $file);
            }

            Cache::put("import_progress_{$userId}", 100, now()->addMinutes(30));

            $filename = $file->getClientOriginalName() ?? 'unknown';
            Log::info('Import completed successfully', ['filename' => $filename]);

            $successMessage = "Import berhasil! File '$filename' dengan $validRows data telah diproses.";
            if ($isAjax) {
                return response()->json(['success' => true, 'message' => $successMessage]);
            }
            return back()->with('success', $successMessage);

        } catch (\Exception $e) {
            Cache::put("import_progress_{$userId}", 100, now()->addMinutes(5));

            Log::error('Import exception', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);

            $errorMessage = 'Terjadi kesalahan saat import: ' . $e->getMessage();
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => $errorMessage], 500);
            }
            return back()->with('error', $errorMessage);
        }
    }

    /**
     * Export data pelanggan ke Excel berdasarkan filter yang aktif
     * Filename dibuat dinamis sesuai filter (contoh: pelanggan_01_2024_Potensial.xlsx)
     */
    public function export(Request $request)
    {
        $bulan           = $request->bulan ?? date('m');
        $tahun           = $request->tahun ?? date('Y');
        $type            = $request->type ?? 'perbulan';
        $search          = $request->search;
        $cabangId        = $request->cabang_id;
        $omsetRange      = $request->omset_range;
        $kedatanganRange = $request->kedatangan_range;
        $kelas           = $request->kelas;

        // Build filename based on filters
        $filename = 'pelanggan';
        if ($search) {
            $filename .= '_search_' . preg_replace('/[^a-zA-Z0-9]/', '_', $search);
        } else {
            if ($type == 'perbulan') {
                $filename .= '_' . $bulan . '_' . $tahun;
            } elseif ($type == 'pertahun') {
                $filename .= '_tahun_' . $tahun;
            } else {
                $filename .= '_semua';
            }
        }
        if ($kelas) {
            $filename .= '_' . $kelas;
        }
        if ($cabangId) {
            $cabang    = Cabang::find($cabangId);
            $filename .= '_' . ($cabang ? preg_replace('/[^a-zA-Z0-9]/', '_', $cabang->nama) : 'cabang');
        }
        $filename .= '.xlsx';

        return Excel::download(
            new \App\Exports\PelangganExport($bulan, $tahun, $type, $search, $cabangId, $omsetRange, $kedatanganRange, $kelas),
            $filename
        );
    }

    /**
     * Download template Excel untuk import pelanggan
     * Template berisi header dan 5 baris contoh data
     * Memudahkan user mengisi data dengan format yang benar
     */
    public function downloadTemplate()
    {
        // Header kolom sesuai format import
        $headers = [
            'No',
            'Nama Pasien',
            'Total Kedatangan',
            'Tanggal Kedatangan Terakhir',
            'Total (Biaya)',
            'No Telpon',
            'DOB',
            'PID',
            'Alamat',
            'Kota',
            'Kelompok Pelanggan (mandiri/klinisi)'
        ];

        // Data dummy sebagai contoh
        $data = [
            [1, 'Budi Santoso',  3, '2024-01-15', 2500000, '081234567890', '1990-05-20', 'JK00001', 'Jl. Sudirman No. 123',    'Jakarta',    'mandiri'],
            [2, 'Siti Aminah',   5, '2024-02-10', 4500000, '082345678901', '1985-08-12', 'BD00002', 'Jl. Ahmad Yani No. 45',   'Bandung',    'klinisi'],
            [3, 'Ahmad Wijaya',  2, '2024-03-05', 1200000, '083456789012', '1992-11-03', 'SB00003', 'Jl. Gatot Subroto No. 78','Surabaya',   'mandiri'],
            [4, 'Dewi Kusuma',   4, '2024-01-28', 3800000, '084567890123', '1988-04-25', 'YK00004', 'Jl. Malioboro No. 12',    'Yogyakarta', 'klinisi'],
            [5, 'Eko Prasetyo',  1, '2024-02-20',  850000, '085678901234', '1995-09-18', 'ML00005', 'Jl. Ijen No. 56',         'Malang',     'mandiri'],
        ];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        // Set header
        foreach ($headers as $col => $header) {
            $sheet->setCellValue(
                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . '1',
                $header
            );
        }

        // Set data
        foreach ($data as $row => $rowData) {
            foreach ($rowData as $col => $value) {
                $sheet->setCellValue(
                    \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . ($row + 2),
                    $value
                );
            }
        }

        // Auto resize columns
        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimension(
                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col)
            )->setAutoSize(true);
        }

        // Style header
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2EFDA']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                ]
            ]
        ];
        $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);

        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'template_import_pelanggan.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'template_');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Export multiple pelanggan terpilih ke Excel
     * Menerima array ID dari checkbox, export hanya pelanggan yang dipilih
     */
    public function bulkExport(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->back()->with('error', 'Tidak ada pelanggan yang dipilih untuk diexport.');
        }

        // Pastikan semua ID valid (integer)
        $ids = array_filter(array_map('intval', $ids));

        if (empty($ids)) {
            return redirect()->back()->with('error', 'ID pelanggan tidak valid.');
        }

        $filename = 'pelanggan_terpilih_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(new \App\Exports\PelangganBulkExport($ids), $filename);
    }

    /**
     * Mengembalikan progress import saat ini (untuk polling AJAX)
     */
    public function importProgress(Request $request)
    {
        $userId   = Auth::id() ?? 0;
        $progress = Cache::get("import_progress_{$userId}", 0);
        $total    = Cache::get("import_total_{$userId}", 0);
        $current  = Cache::get("import_current_{$userId}", 0);

        return response()->json([
            'percent' => (int) $progress,
            'current' => (int) $current,
            'total'   => (int) $total,
            'status'  => $progress >= 100 ? 'done' : 'processing',
        ]);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Memproses import data dari file CSV/Excel
     *
     * DUPLICATE HANDLING (Penanganan Data Duplikat):
     * Jika dalam 1 file ada multiple baris dengan PID yang sama:
     * - Total Kedatangan: dijumlahkan dari semua baris (contoh: 2 + 3 = 5)
     * - Total Biaya: dijumlahkan dari semua baris (contoh: 500000 + 700000 = 1200000)
     * - Tanggal Kedatangan: diambil yang paling terbaru
     * - No: diambil dari baris yang tanggalnya terbaru
     *
     * Cara kerja:
     * 1. STEP 1 - AGGREGATE: Loop semua baris, kelompokkan by PID, jumlahkan data
     * 2. STEP 2 - PROCESS: Loop data yang sudah di-aggregate, simpan ke database
     * 3. Setiap pelanggan baru dicatat riwayat kelas awalnya
     */
    private function processCsvImport(array $rows, int $importUserId = 0): void
    {
        $processedCount   = 0;
        $duplicateSkipped = 0;
        $seenRows         = [];
        $totalDataRows    = count($rows);
        $currentRow       = 0;

        // Load semua cabang ke memory untuk lookup cepat (hindari query berulang)
        $cabangs = Cabang::all()->keyBy('kode');

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $currentRow++;

            if ($index === 0 && count($row) > 0 && strtolower(trim($row[0] ?? '')) === 'no') {
                continue;
            }

            if (count($row) < 10) {
                continue;
            }

            $no               = isset($row[0]) ? (int) $row[0] : null;
            $nama             = trim($row[1] ?? '');
            $totalKedatangan  = isset($row[2]) ? (int) $row[2] : 0;
            $tanggalKedatangan = $row[3] ?? null;
            $biaya            = $row[4] ?? null;
            $noTelp           = trim($row[5] ?? '');
            $dob              = $row[6] ?? null;
            $pid              = trim($row[7] ?? '');
            $alamat           = trim($row[8] ?? '');
            $kota             = trim($row[9] ?? '');
            $kelompokPelanggan = isset($row[10]) ? strtolower(trim((string) $row[10])) : 'mandiri';
            $kelompokPelanggan = in_array($kelompokPelanggan, ['mandiri', 'klinisi']) ? $kelompokPelanggan : 'mandiri';

            if (empty($pid) || empty($nama)) {
                continue;
            }

            // Dedup strict by full row content (semua kolom sama)
            $dedupKey = md5(json_encode([
                $no,
                $nama,
                $totalKedatangan,
                (string) $tanggalKedatangan,
                (string) $biaya,
                $noTelp,
                (string) $dob,
                $pid,
                $alamat,
                $kota,
                $kelompokPelanggan
            ]));

            if (isset($seenRows[$dedupKey])) {
                $duplicateSkipped++;
                continue;
            }
            $seenRows[$dedupKey] = true;

            $cabangKode = strtoupper(substr($pid, 0, 2));
            if (!isset($cabangs[$cabangKode])) {
                continue;
            }

            $tanggal = $this->parseCsvDate($tanggalKedatangan);
            if (!$tanggal) {
                continue;
            }

            $dobDate    = $this->parseCsvDate($dob);
            $biayaValue = $this->parseCsvBiaya($biaya);
            if ($biayaValue === null) {
                continue;
            }

            $cabang = $cabangs[$cabangKode];

            DB::transaction(function () use (
                $pid,
                $nama,
                $noTelp,
                $dobDate,
                $alamat,
                $kota,
                $totalKedatangan,
                $biayaValue,
                $cabang,
                $tanggal,
                $no,
                $kelompokPelanggan,
                &$processedCount
            ) {
                $pelanggan    = Pelanggan::firstOrNew(['pid' => $pid]);
                $isNewPelanggan = !$pelanggan->exists;

                $pelanggan->cabang_id = $cabang->id;
                $pelanggan->nama      = $nama;
                $pelanggan->no_telp   = $noTelp;
                $pelanggan->dob       = $dobDate;
                $pelanggan->alamat    = $alamat;
                $pelanggan->kota      = $kota;

                if ($pelanggan->exists) {
                    $pelanggan->total_kedatangan += $totalKedatangan;
                    $pelanggan->total_biaya      += $biayaValue;
                } else {
                    $pelanggan->total_kedatangan = $totalKedatangan;
                    $pelanggan->total_biaya      = $biayaValue;
                }

                $hasHighValueVisit = $pelanggan->kunjungans()
                    ->where('biaya', '>=', 4000000)
                    ->exists() || ($biayaValue >= 4000000);

                $pelanggan->class = Pelanggan::calculateClass(
                    $pelanggan->total_kedatangan,
                    $pelanggan->total_biaya,
                    $hasHighValueVisit,
                    (bool) $pelanggan->is_pelanggan_khusus
                );

                $pelanggan->save();

                $kelompok = KelompokPelanggan::where('kode', $kelompokPelanggan)->first();

                Kunjungan::create([
                    'no'                   => $no,
                    'pelanggan_id'         => $pelanggan->id,
                    'cabang_id'            => $cabang->id,
                    'tanggal_kunjungan'    => $tanggal,
                    'biaya'                => $biayaValue,
                    'total_kedatangan'     => $totalKedatangan,
                    'kelompok_pelanggan_id' => $kelompok?->id,
                ]);

                if ($isNewPelanggan) {
                    $pelanggan->recordInitialClass($tanggal);
                } else {
                    $pelanggan->updateStats($tanggal, 'Perubahan dari import data CSV');
                }

                $processedCount++;
            });

            if ($importUserId > 0 && $totalDataRows > 0) {
                $percent = (int) floor(($currentRow / $totalDataRows) * 100);
                Cache::put("import_progress_{$importUserId}", min($percent, 99), now()->addMinutes(30));
            }
        }

        Log::info('CSV import completed', [
            'processed'          => $processedCount,
            'duplicates_skipped' => $duplicateSkipped
        ]);
    }

    /**
     * Parse tanggal dari berbagai format CSV/Excel
     * Support format: Y-m-d, d/m/Y, d-m-Y, d/m/y, d-m-y, Y/m/d
     * Return null jika format tidak valid
     */
    private function parseCsvDate($value): ?\Carbon\Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            $dateString = trim((string) $value);

            $formats = [
                'Y-m-d',
                'd/m/Y',
                'd-m-Y',
                'd/m/y',
                'd-m-y',
                'Y/m/d',
            ];

            foreach ($formats as $format) {
                try {
                    $date = \Carbon\Carbon::createFromFormat($format, $dateString);
                    if ($date && $date->year > 1900 && $date->year < 2100) {
                        return $date;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            $date = \Carbon\Carbon::parse($dateString);
            if ($date->year > 1900 && $date->year < 2100) {
                return $date;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse biaya dari berbagai format CSV/Excel
     * Membersihkan format: Rp, spasi, titik ribuan, koma desimal
     * Contoh: "Rp 1.500.000,00" → 1500000
     * Return null jika nilai negatif atau tidak valid
     */
    private function parseCsvBiaya($value): ?float
    {
        if (empty($value) && $value !== 0 && $value !== '0') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return (float) $value;
            }

            $cleanValue = (string) $value;
            $cleanValue = str_replace(['Rp', ' ', '.', ',00'], '', $cleanValue);

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
            return $result >= 0 ? $result : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Membaca file CSV dengan auto-detect delimiter
     * Cara kerja:
     * 1. Baca file dan hapus BOM (Byte Order Mark) jika ada
     * 2. Convert encoding ke UTF-8 jika perlu
     * 3. Deteksi delimiter (koma, titik koma, atau tab) berdasarkan jumlah kolom
     * 4. Parse setiap baris menjadi array
     * Return: Array 2D dengan format [ [baris1], [baris2], ... ]
     */
    private function readCsvFile($file)
    {
        $path    = $file->getPathname();
        $content = file_get_contents($path);

        $bom = pack('CCC', 0xEF, 0xBB, 0xBF);
        if (substr($content, 0, 3) === $bom) {
            $content = substr($content, 3);
        }

        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
        }

        $delimiters   = [',', ';', "\t"];
        $bestDelimiter = ',';
        $maxCols      = 0;

        $lines     = explode("\n", $content);
        $firstLine = $lines[0] ?? '';

        foreach ($delimiters as $delimiter) {
            $cols = count(str_getcsv($firstLine, $delimiter));
            if ($cols > $maxCols) {
                $maxCols       = $cols;
                $bestDelimiter = $delimiter;
            }
        }

        Log::info('CSV delimiter detected', ['delimiter' => $bestDelimiter, 'columns' => $maxCols]);

        $rows = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $row    = str_getcsv($line, $bestDelimiter);
            $rows[] = $row;
        }

        return [$rows];
    }
}
