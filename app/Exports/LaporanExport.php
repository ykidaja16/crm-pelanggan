<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class LaporanExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithCustomStartCell
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

    public function startCell(): string { return 'A3'; }

    public function collection()
    {
        $usePeriode = $this->usePeriodeBiaya;

        return $this->pelanggan->map(function ($item, $index) use ($usePeriode) {
            $biaya      = $usePeriode
                ? ($item->biaya_periode      ?? $item->total_biaya      ?? 0)
                : ($item->total_biaya        ?? 0);
            $kedatangan = $usePeriode
                ? ($item->kedatangan_periode ?? $item->total_kedatangan ?? 0)
                : ($item->total_kedatangan   ?? 0);

            $kelas = $item->class_at_period ?? $item->class ?? 'Umum';

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
        return ['No','PID','Nama Pasien','NIK','Cabang','No Telpon','DOB','Alamat','Kota','Total Kunjungan','Kunjungan Terakhir','Total Biaya','Kelas'];
    }

    public function title(): string { return 'Laporan Pelanggan'; }

    public function columnWidths(): array
    {
        return ['A'=>5,'B'=>15,'C'=>25,'D'=>20,'E'=>15,'F'=>15,'G'=>12,'H'=>30,'I'=>15,'J'=>15,'K'=>18,'L'=>15,'M'=>12];
    }

    public function styles(Worksheet $sheet): array
    {
        $last = $sheet->getHighestRow();

        // Baris 1: Judul
        $sheet->mergeCells('A1:M1');
        $sheet->setCellValue('A1', 'LAPORAN PELANGGAN');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A56A4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Baris 2: Filter aktif + tanggal cetak
        $filterText = is_array($this->filters) ? implode('   |   ', array_filter($this->filters)) : '';
        $subtitle   = ($filterText ? $filterText . '   |   ' : '') . 'Dicetak: ' . now()->format('d-m-Y H:i');
        $sheet->mergeCells('A2:M2');
        $sheet->setCellValue('A2', $subtitle);
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '555555']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCE6F1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(16);

        // Baris 3: Header kolom
        $sheet->getStyle('A3:M3')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E75B6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(20);

        // Alignment per-kolom (bukan per-baris)
        if ($last >= 4) {
            foreach (['A', 'B', 'D', 'G', 'J', 'K', 'M'] as $col) {
                $sheet->getStyle("{$col}4:{$col}{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            $sheet->getStyle("L4:L{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("L4:L{$last}")->getNumberFormat()->setFormatCode('#,##0');
        }

        // Outline border saja (bukan allBorders — hemat memory)
        $sheet->getStyle("A3:M{$last}")->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '2E75B6']]],
        ]);

        $sheet->freezePane('A4');
        return [];
    }
}
