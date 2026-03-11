<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SpecialDayExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $pelanggans;
    protected $filter;

    public function __construct($pelanggans, string $filter = 'birthday')
    {
        $this->pelanggans = $pelanggans;
        $this->filter     = $filter;
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

        $filterLabel = match ($this->filter) {
            'birthday'       => 'Ulang Tahun Hari Ini',
            'birthday_month' => 'Ulang Tahun Bulan Ini',
            'anniversary'    => '1 Tahun Kunjungan Terakhir',
            default          => $this->filter,
        };

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
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF4F81BD']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
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
}
