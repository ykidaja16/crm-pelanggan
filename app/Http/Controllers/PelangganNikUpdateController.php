<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class PelangganNikUpdateController extends Controller
{
    public function index()
    {
        return view('pelanggan.update-nik');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt',
        ]);

        $file = $request->file('file');

        try {
            $extension = strtolower($file->getClientOriginalExtension() ?? '');

            if ($extension === 'csv' || $extension === 'txt') {
                $rows = $this->readCsvFile($file);
            } else {
                $rows = Excel::toArray(null, $file);
            }

            if (empty($rows) || empty($rows[0])) {
                return back()->with('error', 'File kosong.');
            }

            $sheetRows = $rows[0];
            $errors = [];
            $updates = [];
            $rowNumber = 0;

            // ambil semua PID
            $pids = [];
            foreach ($sheetRows as $row) {
                $pid = trim((string)($row[0] ?? ''));
                if ($pid !== '') $pids[] = $pid;
            }

            $pids = array_unique($pids);

            $pelangganMap = Pelanggan::whereIn('pid', $pids)
                ->get()
                ->keyBy('pid');

            foreach ($sheetRows as $row) {
                $rowNumber++;

                $pid = trim((string)($row[0] ?? ''));
                $nik = trim((string)($row[1] ?? ''));

                if ($rowNumber === 1 && strtolower($pid) === 'pid') continue;

                if ($pid === '' && $nik === '') continue;

                if ($pid === '') {
                    $errors[] = "Baris {$rowNumber}: PID kosong.";
                    continue;
                }

                if ($nik === '') {
                    $errors[] = "Baris {$rowNumber}: NIK kosong untuk {$pid}.";
                    continue;
                }

                $pelanggan = $pelangganMap[$pid] ?? null;

                if (!$pelanggan) {
                    $errors[] = "Baris {$rowNumber}: PID {$pid} tidak ditemukan.";
                    continue;
                }

                $updates[$pelanggan->id] = [
                    'id' => $pelanggan->id,
                    'nik' => $nik,
                ];
            }

            if (!empty($errors)) {
                return back()->with('error', 'Import gagal')->with('import_errors', $errors);
            }

            if (empty($updates)) {
                return back()->with('error', 'Tidak ada data valid.');
            }

            $table = (new Pelanggan)->getTable();

            DB::transaction(function () use ($updates, $table) {

                $chunks = array_chunk($updates, 1000);

                foreach ($chunks as $chunk) {

                    $cases = '';
                    $ids = [];
                    $bindings = [];

                    foreach ($chunk as $item) {
                        $cases .= "WHEN ? THEN ? ";
                        $bindings[] = $item['id'];
                        $bindings[] = $item['nik'];
                        $ids[] = $item['id'];
                    }

                    $placeholders = implode(',', array_fill(0, count($ids), '?'));

                    $sql = "
                        UPDATE {$table}
                        SET nik = CASE id
                            {$cases}
                        END,
                        updated_at = NOW()
                        WHERE id IN ({$placeholders})
                    ";

                    DB::update($sql, array_merge($bindings, $ids));
                }
            });

            return back()->with('success', 'Berhasil update ' . count($updates) . ' data');

        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ✅ DOWNLOAD TEMPLATE (SUDAH FIX DEPRECATED)
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

        // HEADER
        foreach ($headers as $col => $header) {
            $cell = Coordinate::stringFromColumnIndex($col + 1) . '1';
            $sheet->setCellValue($cell, $header);
        }

        // DATA
        foreach ($data as $row => $rowData) {
            foreach ($rowData as $col => $value) {
                $cell = Coordinate::stringFromColumnIndex($col + 1) . ($row + 2);
                $sheet->setCellValue($cell, $value);
            }
        }

        // AUTO WIDTH
        foreach (range(1, count($headers)) as $col) {
            $column = Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        $filename = 'template_update_nik.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'template_');

        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    private function readCsvFile($file): array
    {
        $content = file_get_contents($file->getPathname());
        $lines = explode("\n", $content);

        $rows = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $rows[] = str_getcsv($line);
        }

        return [$rows];
    }
}