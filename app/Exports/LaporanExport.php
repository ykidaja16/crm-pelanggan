<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, ShouldAutoSize
{
    protected $pelanggan;
    protected $filters;

    public function __construct($pelanggan, $filters)
    {
        $this->pelanggan = $pelanggan;
        $this->filters = $filters;
    }

    public function collection()
    {
        return $this->pelanggan->map(function ($item, $index) {
            return [
                'No' => $index + 1,
                'PID' => $item->pid,
                'Nama Pasien' => $item->nama,
                'Cabang' => $item->cabang?->nama ?? '-',
                'No Telpon' => $item->no_telp ?? '-',
                'DOB' => $item->dob ? $item->dob->format('d-m-Y') : '-',
                'Alamat' => $item->alamat ?? '-',
                'Kota' => $item->kota ?? '-',
                'Total Kunjungan' => $item->total_kedatangan ?? 0,
                'Kunjungan Terakhir' => $item->tgl_kunjungan_terakhir 
                    ? \Carbon\Carbon::parse($item->tgl_kunjungan_terakhir)->format('d-m-Y') 
                    : '-',


                'Total Biaya' => $item->total_biaya ?? 0,
                'Kelas' => $item->class ?? 'Potensial',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No',
            'PID',
            'Nama Pasien',
            'Cabang',
            'No Telpon',
            'DOB',
            'Alamat',
            'Kota',
            'Total Kunjungan',
            'Kunjungan Terakhir',
            'Total Biaya',
            'Kelas',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header style
        $sheet->getStyle('A1:L1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
        ]);

        // Border for all cells
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A1:L' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Center alignment for No, PID, DOB, Kunjungan, Kelas, Status
        $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('B2:B' . $lastRow)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('F2:F' . $lastRow)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('I2:I' . $lastRow)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('J2:J' . $lastRow)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('L2:L' . $lastRow)->getAlignment()->setHorizontal('center');

        // Right alignment for Total Biaya
        $sheet->getStyle('K2:K' . $lastRow)->getAlignment()->setHorizontal('right');

        // Format Total Biaya as currency
        $sheet->getStyle('K2:K' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');

        // Auto height for all rows
        for ($row = 1; $row <= $lastRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(-1);
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,   // No
            'B' => 15,  // PID
            'C' => 25,  // Nama Pasien
            'D' => 15,  // Cabang
            'E' => 15,  // No Telpon
            'F' => 12,  // DOB
            'G' => 30,  // Alamat
            'H' => 15,  // Kota
            'I' => 15,  // Total Kunjungan
            'J' => 18,  // Kunjungan Terakhir
            'K' => 15,  // Total Biaya
            'L' => 12,  // Kelas
        ];
    }

    public function title(): string
    {
        return 'Laporan Pelanggan';
    }
}
