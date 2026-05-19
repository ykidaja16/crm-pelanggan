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

class SearchByPhoneNotFoundExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Tidak Ditemukan';
    }

    public function headings(): array
    {
        return ['No', 'Nama (dari File Excel)', 'Nomer Telepon (dari File Excel)'];
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->data as $i => $item) {
            $rows[] = [
                $i + 1,
                $item['nama'],
                $item['no_telp_raw'],
            ];
        }
        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 30,
            'C' => 25,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->data) + 1;

        $sheet->getStyle('A1:C1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DC3545']],
            'alignment' => ['horizontal' => 'center'],
        ]);

        $sheet->getStyle("A1:C{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']],
            ],
        ]);

        return [];
    }
}
