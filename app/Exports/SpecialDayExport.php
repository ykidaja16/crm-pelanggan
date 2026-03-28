<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SpecialDayExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    protected $pelanggans;
    protected $filter;
    protected $tglMulai;
    protected $tglAkhir;

    public function __construct($pelanggans, string $filter = 'birthday', ?string $tglMulai = null, ?string $tglAkhir = null)
    {
        $this->pelanggans = $pelanggans;
        $this->filter     = $filter;
        $this->tglMulai   = $tglMulai;
        $this->tglAkhir   = $tglAkhir;
    }

    public function collection()
    {
        return $this->pelanggans;
    }

    public function headings(): array
    {
        return [
            'No',
            'PID',
            'Nama',
            'Cabang',
            'No. Telepon',
            'Tanggal Lahir',
            'Kota',
            'Kelas',
            'Tipe Pelanggan',
            'Kunjungan Terakhir',
            'Kelompok',
        ];
    }

    public function map($row): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $row->pid,
            $row->nama,
            $row->cabang?->nama ?? '-',
            $row->no_telp ?? '-',
            $row->dob ? \Carbon\Carbon::parse($row->dob)->format('d-m-Y') : '-',
            $row->kota ?? '-',
            $row->class ?? '-',
            $row->is_pelanggan_khusus ? 'Pelanggan Khusus' : 'Pelanggan Biasa',
            $row->kunjungans_max_tanggal_kunjungan
                ? \Carbon\Carbon::parse($row->kunjungans_max_tanggal_kunjungan)->format('d-m-Y')
                : '-',
            $row->latestKunjungan?->kelompokPelanggan?->nama ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = max($sheet->getHighestRow(), 1);

        $sheet->getStyle('A1:K1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF4F81BD']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);

        $sheet->getStyle('A1:K' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => '000000']],
            ],
        ]);

        // Center alignment
        foreach (['A', 'B', 'F', 'H', 'I', 'J', 'K'] as $col) {
            $sheet->getStyle($col . '2:' . $col . $lastRow)->getAlignment()->setHorizontal('center');
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 15,
            'C' => 25,
            'D' => 20,
            'E' => 18,
            'F' => 16,
            'G' => 18,
            'H' => 12,
            'I' => 20,
            'J' => 20,
            'K' => 15,
        ];
    }

    public function title(): string
    {
        return match ($this->filter) {
            'birthday_range'    => 'Birthday Reminder',
            'kunjungan_terakhir' => 'Kunjungan Terakhir',
            default              => 'Special Day Member',
        };
    }
}
