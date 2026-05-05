<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PelangganNikUpdateController extends Controller
{
    /**
     * Halaman Update NIK via import Excel.
     */
    public function index()
    {
        return view('pelanggan.update-nik');
    }

    /**
     * Import file Excel berisi 2 kolom: PID dan NIK.
     * Atomic: jika ada 1 PID tidak ditemukan / data invalid, semua update dibatalkan.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt',
        ], [
            'file.required' => 'File wajib diupload.',
            'file.mimes' => 'Format file harus xlsx, xls, csv, atau txt.',
        ]);

        $file = $request->file('file');

        try {
            $extension = strtolower($file->getClientOriginalExtension() ?? '');

            if ($extension === 'csv' || $extension === 'txt') {
                $rows = $this->readCsvFile($file);
            } else {
                $rows = Excel::toArray(null, $file);
            }

            if (empty($rows) || empty($rows[0]) || count($rows[0]) < 2) {
                return back()->with('error', 'File kosong atau format tidak valid.');
            }

            $sheetRows = $rows[0];
            $errors = [];
            $updates = [];
            $rowNumber = 0;

            foreach ($sheetRows as $row) {
                $rowNumber++;

                $col1 = isset($row[0]) ? trim((string) $row[0]) : '';
                $col2 = isset($row[1]) ? trim((string) $row[1]) : '';

                // Skip header (PID, NIK)
                if ($rowNumber === 1 && strtolower($col1) === 'pid' && strtolower($col2) === 'nik') {
                    continue;
                }

                // Skip row kosong total
                if ($col1 === '' && $col2 === '') {
                    continue;
                }

                if ($col1 === '') {
                    $errors[] = "Baris {$rowNumber}: PID kosong.";
                    continue;
                }

                if ($col2 === '') {
                    $errors[] = "Baris {$rowNumber}: NIK kosong untuk PID '{$col1}'.";
                    continue;
                }

                $pelanggan = Pelanggan::where('pid', $col1)->first();
                if (!$pelanggan) {
                    $errors[] = "Baris {$rowNumber}: PID '{$col1}' tidak ditemukan di database.";
                    continue;
                }

                $updates[] = [
                    'row' => $rowNumber,
                    'pid' => $col1,
                    'nik' => $col2,
                    'pelanggan_id' => $pelanggan->id,
                ];
            }

            if (!empty($errors)) {
                return back()
                    ->with('error', 'Import gagal. Terdapat data yang tidak valid.')
                    ->with('import_errors', $errors);
            }

            if (empty($updates)) {
                return back()->with('error', 'Tidak ada data valid untuk diproses.');
            }

            DB::transaction(function () use ($updates) {
                foreach ($updates as $item) {
                    Pelanggan::where('id', $item['pelanggan_id'])->update([
                        'nik' => $item['nik'],
                        'updated_at' => now(),
                    ]);
                }
            });

            return back()->with('success', 'Update NIK berhasil. Total ' . count($updates) . ' data diperbarui.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Terjadi kesalahan saat import: ' . $e->getMessage());
        }
    }

    /**
     * Download template Excel untuk Update NIK (2 kolom: PID, NIK).
     */
    public function downloadTemplate()
    {
        $headers = ['PID', 'NIK'];
        $data = [
            ['JK00000001', '1234567890123456'],
            ['BD00000002', 'TIDAK ADA IDENTITAS'],
            ['SB00000003', '9876543210987654'],
        ];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($headers as $col => $header) {
            $sheet->setCellValue(
                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . '1',
                $header
            );
        }

        foreach ($data as $row => $rowData) {
            foreach ($rowData as $col => $value) {
                $sheet->setCellValue(
                    \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . ($row + 2),
                    $value
                );
            }
        }

        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimension(
                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col)
            )->setAutoSize(true);
        }

        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2EFDA'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];

        $sheet->getStyle('A1:B1')->applyFromArray($headerStyle);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'template_update_nik_pelanggan.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'template_update_nik_');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Reader CSV sederhana dengan auto-detect delimiter.
     */
    private function readCsvFile($file): array
    {
        $path = $file->getPathname();
        $content = file_get_contents($path);

        $bom = pack('CCC', 0xEF, 0xBB, 0xBF);
        if (substr($content, 0, 3) === $bom) {
            $content = substr($content, 3);
        }

        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
        }

        $delimiters = [',', ';', "\t"];
        $bestDelimiter = ',';
        $maxCols = 0;

        $lines = explode("\n", $content);
        $firstLine = $lines[0] ?? '';

        foreach ($delimiters as $delimiter) {
            $cols = count(str_getcsv($firstLine, $delimiter));
            if ($cols > $maxCols) {
                $maxCols = $cols;
                $bestDelimiter = $delimiter;
            }
        }

        $rows = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $rows[] = str_getcsv($line, $bestDelimiter);
        }

        return [$rows];
    }
}
