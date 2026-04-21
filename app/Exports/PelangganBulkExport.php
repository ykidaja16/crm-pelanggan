<?php

namespace App\Exports;

use App\Models\Pelanggan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PelangganBulkExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected array $ids;

    public function __construct(array $ids)
    {
        $this->ids = $ids;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $pelanggan = Pelanggan::with('cabang')
            ->whereIn('id', $this->ids)
            ->selectRaw('pelanggans.*, (SELECT MAX(tanggal_kunjungan) FROM kunjungans WHERE kunjungans.pelanggan_id = pelanggans.id) as tgl_kunjungan_terakhir')
            ->orderBy('nama', 'asc')
            ->get();

        return $pelanggan->map(function ($p, $index) {
            return [
                'no'                    => $index + 1,
                'pid'                   => $p->pid,
                'nama'                  => $p->nama,
                'nik'                   => $p->nik ?? '-',
                'cabang'                => $p->cabang?->nama ?? '-',
                'no_telp'               => $p->no_telp ?? '-',
                'dob'                   => $p->dob ? $p->dob->format('d-m-Y') : '-',
                'alamat'                => $p->alamat ?? '-',
                'kota'                  => $p->kota ?? '-',
                'total_kedatangan'      => $p->total_kedatangan,
                'class'                 => $p->class,
                'total_biaya'           => $p->total_biaya,
                'tgl_kunjungan_terakhir'=> $p->tgl_kunjungan_terakhir
                    ? \Carbon\Carbon::parse($p->tgl_kunjungan_terakhir)->format('d-m-Y')
                    : '-',
            ];
        });
    }

    /**
     * @return array
     */
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
            'Kota',
            'Total Kedatangan',
            'Kelas',
            'Total Biaya',
            'Tanggal Kunjungan Terakhir',
        ];
    }

    /**
     * Style header row
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2EFDA'],
                ],
            ],
        ];
    }
}
