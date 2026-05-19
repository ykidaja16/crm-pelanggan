<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SearchByPhoneFoundExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Ditemukan';
    }

    public function headings(): array
    {
        return ['No', 'PID', 'Cabang', 'Nama (DB)', 'Nomer Telepon', 'Alamat', 'Kunjungan Terakhir', 'Kelas', 'Nama di File Excel'];
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->data as $i => $item) {
            $rows[] = [
                $i + 1,
                $item['pids'],
                $item['cabang_names'],
                $item['nama_db'],
                $item['no_telp'],
                $item['alamat'],
                $item['latest_visit'] ?: '-',
                $item['class_str'],
                $item['nama_excel'],
            ];
        }
        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 22,
            'C' => 30,
            'D' => 25,
            'E' => 18,
            'F' => 35,
            'G' => 20,
            'H' => 12,
            'I' => 25,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->data) + 1;

        $sheet->getStyle('A1:I1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '198754']],
            'alignment' => ['horizontal' => 'center'],
        ]);

        $sheet->getStyle("A1:I{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']],
            ],
        ]);

        // Wrap text pada data rows untuk kolom yang bisa berisi lebih dari 1 nilai
        if ($lastRow > 1) {
            foreach ($this->data as $rowIndex => $item) {
                $row = $rowIndex + 2; // baris data mulai dari 2
                $multiCols = ['B', 'C', 'D', 'F', 'G', 'H']; // PID, Cabang, Nama, Alamat, Kunjungan, Kelas
                foreach ($multiCols as $col) {
                    $cellValue = (string) $sheet->getCell("{$col}{$row}")->getValue();
                    if (str_contains($cellValue, ',')) {
                        $sheet->getStyle("{$col}{$row}")
                            ->getAlignment()
                            ->setWrapText(true)
                            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                    }
                }
            }
        }

        return [];
    }
}
