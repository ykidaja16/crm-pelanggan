<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EvaluasiPromoExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected Collection $rows;
    protected array $filters;

    public function __construct(Collection $rows, array $filters = [])
    {
        $this->rows = $rows;
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        return $this->rows->map(function ($row, $index) {
            return [
                'No'                          => $index + 1,
                'PID'                         => $row['pid'] ?? '-',
                'Nama Pasien'                 => $row['nama'] ?? '-',
                'NIK'                         => $row['nik'] ? "'" . $row['nik'] : '-',
                'Cabang'                      => $row['cabang'] ?? '-',
                'No Telp'                     => $row['no_telp'] ? "'" . $row['no_telp'] : '-',
                'DOB'                         => $row['dob'] ?? '-',
                'Alamat'                      => $row['alamat'] ?? '-',
                'Total Kunjungan'             => $row['total_kunjungan'] ?? 0,
                'Kunjungan Terakhir'          => $row['kunjungan_terakhir'] ?? '-',
                'MOU/Agreement Terakhir'      => $row['mou_terakhir'] ?? '-',
                'Pemeriksaan Terakhir'        => $row['pemeriksaan_terakhir'] ?? '-',
                'Tanggal Pemeriksaan Terakhir'=> $row['tgl_pemeriksaan_terakhir'] ?? '-',
                'Kelas'                       => $row['class'] ?? '-',
                'Status Pelanggan'            => $row['status_pelanggan'] ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No',
            'PID',
            'Nama Pasien',
            'NIK',
            'Cabang',
            'No Telp',
            'DOB',
            'Alamat',
            'Total Kunjungan',
            'Kunjungan Terakhir',
            'MOU/Agreement Terakhir',
            'Pemeriksaan Terakhir',
            'Tanggal Pemeriksaan Terakhir',
            'Kelas',
            'Status Pelanggan',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = max($sheet->getHighestRow(), 1);

        // Header style
        $sheet->getStyle('A1:O1')->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType'   => 'solid',
                'startColor' => ['rgb' => '1E40AF'], // Royal Navy Blue
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical'   => 'center',
            ],
        ]);

        if ($lastRow > 1) {
            // Borders
            $sheet->getStyle('A1:O' . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => 'thin',
                        'color'       => ['rgb' => 'D1D5DB'],
                    ],
                ],
            ]);

            // Alignment
            $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal('center'); // No
            $sheet->getStyle('B2:B' . $lastRow)->getAlignment()->setHorizontal('center'); // PID
            $sheet->getStyle('D2:D' . $lastRow)->getAlignment()->setHorizontal('center'); // NIK
            $sheet->getStyle('E2:E' . $lastRow)->getAlignment()->setHorizontal('center'); // Cabang
            $sheet->getStyle('F2:F' . $lastRow)->getAlignment()->setHorizontal('center'); // No Telp
            $sheet->getStyle('G2:G' . $lastRow)->getAlignment()->setHorizontal('center'); // DOB
            $sheet->getStyle('I2:I' . $lastRow)->getAlignment()->setHorizontal('center'); // Total Kunjungan
            $sheet->getStyle('J2:J' . $lastRow)->getAlignment()->setHorizontal('center'); // Kunjungan Terakhir
            $sheet->getStyle('M2:M' . $lastRow)->getAlignment()->setHorizontal('center'); // Tgl Pemeriksaan
            $sheet->getStyle('N2:N' . $lastRow)->getAlignment()->setHorizontal('center'); // Kelas
            $sheet->getStyle('O2:O' . $lastRow)->getAlignment()->setHorizontal('center'); // Status Pelanggan
        }

        // Auto row height
        for ($r = 1; $r <= $lastRow; $r++) {
            $sheet->getRowDimension($r)->setRowHeight(-1);
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,   // No
            'B' => 14,  // PID
            'C' => 26,  // Nama Pasien
            'D' => 20,  // NIK
            'E' => 15,  // Cabang
            'F' => 16,  // No Telp
            'G' => 14,  // DOB
            'H' => 30,  // Alamat
            'I' => 15,  // Total Kunjungan
            'J' => 18,  // Kunjungan Terakhir
            'K' => 25,  // MOU/Agreement Terakhir
            'L' => 28,  // Pemeriksaan Terakhir
            'M' => 22,  // Tgl Pemeriksaan Terakhir
            'N' => 14,  // Kelas
            'O' => 18,  // Status Pelanggan
        ];
    }

    public function title(): string
    {
        return 'Evaluasi Event Promo';
    }
}
