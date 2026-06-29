<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SinkronisasiResultExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected Collection $histories;
    protected string $sheetTitle;

    public function __construct(Collection $histories, string $sheetTitle = 'Hasil Sinkronisasi')
    {
        $this->histories   = $histories;
        $this->sheetTitle  = $sheetTitle;
    }

    public function collection(): Collection
    {
        return $this->histories->map(function ($history, $index) {
            $p = $history->pelanggan;
            return [
                'No'                 => $index + 1,
                'PID'                => $p?->pid ?? '-',
                'Nama Pasien'        => $p?->nama ?? '-',
                'NIK'                => $p?->nik ?? '-',
                'Cabang'             => $p?->cabang?->nama ?? '-',
                'No Telp'            => $p?->no_telp ?? '-',
                'DOB'                => $p?->dob?->format('d-m-Y') ?? '-',
                'Alamat'             => $p?->alamat ?? '-',
                'Total Kunjungan'    => $p?->total_kedatangan ?? 0,
                'Kunjungan Terakhir' => $p?->latestKunjungan?->tanggal_kunjungan?->format('d-m-Y') ?? '-',
                'Total Biaya'        => $p?->total_biaya ?? 0,
                'Kelas Lama'         => $history->previous_class ?? '-',
                'Kelas Baru'         => $history->new_class ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No', 'PID', 'Nama Pasien', 'NIK', 'Cabang', 'No Telp',
            'DOB', 'Alamat', 'Total Kunjungan', 'Kunjungan Terakhir',
            'Total Biaya', 'Kelas Lama', 'Kelas Baru',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();
        $lastCol = 'M';

        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);

        $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => '000000']]],
        ]);

        // Format Total Biaya (kolom K)
        $sheet->getStyle("K2:K{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("K2:K{$lastRow}")->getAlignment()->setHorizontal('right');

        // Center beberapa kolom
        $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("I2:I{$lastRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("J2:J{$lastRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("L2:L{$lastRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("M2:M{$lastRow}")->getAlignment()->setHorizontal('center');

        return [];
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }
}
