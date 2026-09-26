<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use App\Models\Pelanggan;
use App\Models\Kunjungan;
use App\Models\ActivityLog;

class KunjunganPemeriksaanUpdateController extends Controller
{
    /**
     * Tampilkan halaman Update Pemeriksaan khusus role IT
     */
    public function index()
    {
        return view('kunjungan.update-pemeriksaan');
    }

    /**
     * Download template Excel untuk update pemeriksaan (3 kolom: PID, Tanggal Kunjungan, Pemeriksaan)
     */
    public function downloadTemplate()
    {
        $headers = [
            'PID',
            'Tanggal Kunjungan',
            'Pemeriksaan',
        ];

        $data = [
            ['LX001', '2021-04-09', 'Diabetes, Kolestrol, Urine Lengkap'],
            ['BD00002', '2022-06-15', 'Darah Lengkap, SGOT, SGPT, Asam Urat'],
            ['SB00003', '2023-01-20', 'Urine Lengkap, Glukosa Puasa'],
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($headers as $col => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . '1', $header);
        }

        foreach ($data as $row => $rowData) {
            foreach ($rowData as $col => $value) {
                $cellCoordinate = Coordinate::stringFromColumnIndex($col + 1) . ($row + 2);
                $sheet->setCellValueExplicit($cellCoordinate, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }

        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
        }

        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9E1F2'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];
        $sheet->getStyle('A1:C1')->applyFromArray($headerStyle);

        $writer = new Xlsx($spreadsheet);
        $filename = 'template_update_pemeriksaan.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'tpl_pemeriksaan_');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Memproses import Excel update pemeriksaan
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt',
        ], [
            'file.required' => 'File Excel wajib diunggah.',
            'file.file'     => 'File harus berupa berkas dokumen.',
            'file.mimes'    => 'File harus berformat .xlsx, .xls, atau .csv.',
        ]);

        $file = $request->file('file');

        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);

            if (empty($rows) || count($rows) < 2) {
                return back()->with('error', 'File Excel kosong atau tidak memiliki baris data.');
            }

            $updatedCount = 0;
            $notFoundCount = 0;
            $skippedCount = 0;
            $details = [];

            DB::transaction(function () use ($rows, &$updatedCount, &$notFoundCount, &$skippedCount, &$details) {
                for ($i = 0; $i < count($rows); $i++) {
                    $row = $rows[$i] ?? [];
                    $rowNum = $i + 1;

                    // Skip header jika baris pertama adalah judul kolom
                    if ($i === 0) {
                        $col0 = strtolower(trim((string) ($row[0] ?? '')));
                        if (str_contains($col0, 'pid') || str_contains($col0, 'no')) {
                            continue;
                        }
                    }

                    $pid = strtoupper(trim((string) ($row[0] ?? '')));
                    $rawDate = trim((string) ($row[1] ?? ''));
                    $pemeriksaan = trim((string) ($row[2] ?? ''));

                    if ($pid === '' && $rawDate === '') {
                        $skippedCount++;
                        continue;
                    }

                    if ($pid === '' || $rawDate === '') {
                        $notFoundCount++;
                        $details[] = "Baris {$rowNum}: PID atau Tanggal Kunjungan kosong.";
                        continue;
                    }

                    $parsedDate = $this->parseDate($rawDate);
                    if (!$parsedDate) {
                        $notFoundCount++;
                        $details[] = "Baris {$rowNum}: Format tanggal '{$rawDate}' untuk PID '{$pid}' tidak valid.";
                        continue;
                    }

                    // Cari pelanggan berdasarkan PID
                    $pelanggan = Pelanggan::where('pid', $pid)->first();
                    if (!$pelanggan) {
                        $notFoundCount++;
                        $details[] = "Baris {$rowNum}: Pelanggan dengan PID '{$pid}' tidak ditemukan.";
                        continue;
                    }

                    // Cari kunjungan pada tanggal tersebut
                    $kunjungans = Kunjungan::where('pelanggan_id', $pelanggan->id)
                        ->whereDate('tanggal_kunjungan', $parsedDate)
                        ->get();

                    if ($kunjungans->isEmpty()) {
                        $notFoundCount++;
                        $details[] = "Baris {$rowNum}: Kunjungan tanggal '{$parsedDate}' untuk PID '{$pid}' ({$pelanggan->nama}) tidak ditemukan.";
                        continue;
                    }

                    foreach ($kunjungans as $kunjungan) {
                        $kunjungan->pemeriksaan = $pemeriksaan !== '' ? $pemeriksaan : null;
                        $kunjungan->save();
                        $updatedCount++;
                    }
                }
            });

            // Catat log aktivitas
            ActivityLog::record(
                'update',
                'Kunjungan',
                "IT melakukan Update Pemeriksaan via Excel: {$updatedCount} kunjungan berhasil diupdate, {$notFoundCount} data tidak ditemukan.",
                Auth::id(),
                Auth::user()->username ?? 'unknown',
                Auth::user()->role?->name ?? 'IT',
                $request->ip(),
                $request->userAgent()
            );

            $message = "Proses selesai: {$updatedCount} data pemeriksaan kunjungan berhasil diperbarui.";
            if ($notFoundCount > 0) {
                $message .= " ({$notFoundCount} baris tidak ditemukan/gagal dicocokkan).";
            }

            return back()->with('success', $message)->with('import_details', array_slice($details, 0, 50));

        } catch (\Exception $e) {
            Log::error('Update Pemeriksaan Import Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat memproses file: ' . $e->getMessage());
        }
    }

    /**
     * Helper untuk parse berbagai variasi format tanggal Excel
     */
    private function parseDate($dateValue): ?string
    {
        if (empty($dateValue)) {
            return null;
        }

        // Jika numeric, kemungkinan Excel serial date
        if (is_numeric($dateValue)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $dateValue))->format('Y-m-d');
            } catch (\Exception $e) {
                // Lanjut coba cara lain
            }
        }

        $clean = trim((string) $dateValue);

        $formats = [
            'Y-m-d',
            'd-m-Y',
            'd/m/Y',
            'Y/m/d',
            'm/d/Y',
            'd.m.Y',
            'Y.m.d',
        ];

        foreach ($formats as $format) {
            try {
                $d = Carbon::createFromFormat($format, $clean);
                if ($d && $d->format($format) === $clean) {
                    return $d->format('Y-m-d');
                }
            } catch (\Exception $e) {
                // Coba format berikutnya
            }
        }

        try {
            return Carbon::parse($clean)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
