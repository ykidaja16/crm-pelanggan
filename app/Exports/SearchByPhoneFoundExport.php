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
use PhpOffice\PhpSpreadsheet\Style\Alignment;

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
        return [
            'No', 'PID', 'Cabang', 'Nama (DB)', 'Nomer Telepon',
            'Alamat', 'Kunjungan Terakhir', 'Total Kedatangan', 'Kelas', 'Nama di File Excel',
        ];
    }

    public function array(): array
    {
        $rows = [];
        $no   = 1;

        foreach ($this->data as $item) {
            foreach ($item['records'] as $rec) {
                $rows[] = [
                    $no++,
                    $rec['pid'],
                    $rec['cabang'],
                    $rec['nama_db'],
                    $rec['no_telp'],
                    $rec['alamat'],
                    $rec['latest_visit'],
                    $rec['total_kedatangan'],
                    $rec['class'],
                    $rec['nama_excel'],
                ];
            }
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 16,
            'C' => 22,
            'D' => 28,
            'E' => 18,
            'F' => 38,
            'G' => 20,
            'H' => 18,
            'I' => 12,
            'J' => 28,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->array()) + 1;

        // Header
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '198754']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Border seluruh tabel
        if ($lastRow > 1) {
            $sheet->getStyle("A1:J{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']],
                ],
            ]);
        }

        // Wrap text + vertical top untuk kolom Alamat (F) dan Nama DB (D)
        for ($row = 2; $row <= $lastRow; $row++) {
            foreach (['D', 'F'] as $col) {
                $sheet->getStyle("{$col}{$row}")
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(Alignment::VERTICAL_TOP);
            }
        }

        // Warna selang-seling per grup nomor telepon agar mudah dibaca
        $this->applyGroupColors($sheet);

        return [];
    }

    private function applyGroupColors(Worksheet $sheet): void
    {
        $colors = ['FFFFFF', 'F0FFF4']; // putih & hijau sangat muda
        $colorIndex = 0;
        $row = 2;

        foreach ($this->data as $group) {
            $count = count($group['records']);
            $fillColor = $colors[$colorIndex % 2];

            if ($fillColor !== 'FFFFFF') {
                $sheet->getStyle("A{$row}:J" . ($row + $count - 1))->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fillColor]],
                ]);
            }

            $row        += $count;
            $colorIndex += 1;
        }
    }
}
