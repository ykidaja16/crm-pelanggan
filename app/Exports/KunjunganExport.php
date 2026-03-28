<?php

namespace App\Exports;

use App\Models\Pelanggan;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KunjunganExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, ShouldAutoSize
{
    protected Pelanggan $pelanggan;
    protected Collection $kunjungans;

    public function __construct(Pelanggan $pelanggan, Collection $kunjungans)
    {
        $this->pelanggan  = $pelanggan;
        $this->kunjungans = $kunjungans;
    }

    public function collection()
    {
        return $this->kunjungans->map(function ($k, $index) {
            return [
                'No'                 => $index + 1,
                'PID'                => $this->pelanggan->pid,
                'Nama Pasien'        => $this->pelanggan->nama,
                'Cabang'             => $this->pelanggan->cabang?->nama ?? '-',
                'No Telpon'          => $this->pelanggan->no_telp ?? '-',
                'DOB'                => $this->pelanggan->dob
                                            ? $this->pelanggan->dob->format('d-m-Y')
                                            : '-',
                'Alamat'             => $this->pelanggan->alamat ?? '-',
                'Kota'               => $this->pelanggan->kota ?? '-',
                'Tanggal Kunjungan'  => $k->tanggal_kunjungan
                                            ? \Carbon\Carbon::parse($k->tanggal_kunjungan)->format('d-m-Y')
                                            : '-',
                'Biaya'              => $k->biaya ?? 0,
                'Kelompok Pelanggan' => $k->kelompokPelanggan?->nama ?? '-',
                'Kelas'              => $this->pelanggan->class ?? 'Potensial',
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
            'Tanggal Kunjungan',
            'Biaya',
            'Kelompok Pelanggan',
            'Kelas',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();

        // Header style
        $sheet->getStyle('A1:L1')->applyFromArray([
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
        $sheet->getStyle('A1:L' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                    'color'       => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Center alignment
        $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal('center'); // No
        $sheet->getStyle('B2:B' . $lastRow)->getAlignment()->setHorizontal('center'); // PID
        $sheet->getStyle('F2:F' . $lastRow)->getAlignment()->setHorizontal('center'); // DOB
        $sheet->getStyle('I2:I' . $lastRow)->getAlignment()->setHorizontal('center'); // Tgl Kunjungan
        $sheet->getStyle('K2:K' . $lastRow)->getAlignment()->setHorizontal('center'); // Kelompok
        $sheet->getStyle('L2:L' . $lastRow)->getAlignment()->setHorizontal('center'); // Kelas

        // Right alignment + currency format for Biaya
        $sheet->getStyle('J2:J' . $lastRow)->getAlignment()->setHorizontal('right');
        $sheet->getStyle('J2:J' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');

        // Auto height
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
            'I' => 18,  // Tanggal Kunjungan
            'J' => 15,  // Biaya
            'K' => 20,  // Kelompok Pelanggan
            'L' => 12,  // Kelas
        ];
    }

    public function title(): string
    {
        return 'Riwayat Kunjungan ' . $this->pelanggan->pid;
    }
}
