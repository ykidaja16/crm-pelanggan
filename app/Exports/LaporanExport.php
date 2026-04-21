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
    protected $usePeriodeBiaya;

    public function __construct($pelanggan, $filters, bool $usePeriodeBiaya = false)
    {
        $this->pelanggan       = $pelanggan;
        $this->filters         = $filters;
        $this->usePeriodeBiaya = $usePeriodeBiaya;
    }

    public function collection()
    {
        $usePeriode = $this->usePeriodeBiaya;

        return $this->pelanggan->map(function ($item, $index) use ($usePeriode) {
            // Pilih kolom biaya & kedatangan sesuai filter periode
            $biaya      = $usePeriode
                ? ($item->biaya_periode      ?? $item->total_biaya      ?? 0)
                : ($item->total_biaya        ?? 0);
            $kedatangan = $usePeriode
                ? ($item->kedatangan_periode ?? $item->total_kedatangan ?? 0)
                : ($item->total_kedatangan   ?? 0);

            // Kelas: gunakan class_at_period jika tersedia (filter periode aktif)
            $kelas = $item->class_at_period ?? $item->class ?? 'Umum';

            // Kunjungan terakhir: sudah period-specific dari subquery
            $kunjunganTerakhir = $item->tgl_kunjungan_terakhir
                ? \Carbon\Carbon::parse($item->tgl_kunjungan_terakhir)->format('d-m-Y')
                : '-';

            return [
                'No'               => $index + 1,
                'PID'              => $item->pid,
                'Nama Pasien'      => $item->nama,
                'NIK'              => $item->nik ?? '-',
                'Cabang'           => $item->cabang?->nama ?? '-',
                'No Telpon'        => $item->no_telp ?? '-',
                'DOB'              => $item->dob ? $item->dob->format('d-m-Y') : '-',
                'Alamat'           => $item->alamat ?? '-',
                'Kota'             => $item->kota ?? '-',
                'Total Kunjungan'  => (int) $kedatangan,
                'Kunjungan Terakhir' => $kunjunganTerakhir,
                'Total Biaya'      => (float) $biaya,
                'Kelas'            => $kelas,
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
        $sheet->getStyle('A1:M1')->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType'   => 'solid',
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical'   => 'center',
            ],
        ]);

        // Border for all cells
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A1:M' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                    'color'       => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Center alignment for No, PID, NIK, DOB, Kunjungan, Kelas
        $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('B2:B' . $lastRow)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('D2:D' . $lastRow)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('G2:G' . $lastRow)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('J2:J' . $lastRow)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('K2:K' . $lastRow)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('M2:M' . $lastRow)->getAlignment()->setHorizontal('center');

        // Right alignment for Total Biaya
        $sheet->getStyle('L2:L' . $lastRow)->getAlignment()->setHorizontal('right');

        // Format Total Biaya as currency
        $sheet->getStyle('L2:L' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');

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
            'D' => 20,  // NIK
            'E' => 15,  // Cabang
            'F' => 15,  // No Telpon
            'G' => 12,  // DOB
            'H' => 30,  // Alamat
            'I' => 15,  // Kota
            'J' => 15,  // Total Kunjungan
            'K' => 18,  // Kunjungan Terakhir
            'L' => 15,  // Total Biaya
            'M' => 12,  // Kelas
        ];
    }

    public function title(): string
    {
        return 'Laporan Pelanggan';
    }
}
