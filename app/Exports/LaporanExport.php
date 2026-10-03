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
    protected $pemeriksaan;

    public function __construct($pelanggan, $filters, bool $usePeriodeBiaya = false, ?string $pemeriksaan = null)
    {
        $this->pelanggan       = $pelanggan;
        $this->filters         = $filters;
        $this->usePeriodeBiaya = $usePeriodeBiaya;
        $this->pemeriksaan     = $pemeriksaan;
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

            $row = [
                'No'                 => $index + 1,
                'PID'                => $item->pid,
                'Nama Pasien'        => $item->nama,
                'NIK'                => $item->nik ?? '-',
                'Cabang'             => $item->cabang?->nama ?? '-',
                'No Telpon'          => $item->no_telp ?? '-',
                'DOB'                => $item->dob ? $item->dob->format('d-m-Y') : '-',
                'Alamat'             => $item->alamat ?? '-',
                'Kota'               => $item->kota ?? '-',
                'Total Kunjungan'    => (int) $kedatangan,
                'Kunjungan Terakhir' => $kunjunganTerakhir,
            ];

            if ($this->pemeriksaan) {
                $row['Total Terkait Pemeriksaan']                   = $item->total_terkait_pemeriksaan ?? 0;
                $row['Pemeriksaan Terakhir']                        = $item->pemeriksaan_terakhir_terkait ?? '-';
                $row['Tanggal Kunjungan Terakhir Terkait Pemeriksaan'] = $item->tgl_kunjungan_terakhir_terkait ?? '-';
            }

            $row['Total Biaya'] = (float) $biaya;
            $row['Kelas']       = $kelas;

            return $row;
        });
    }

    public function headings(): array
    {
        $h = ['No','PID','Nama Pasien','NIK','Cabang','No Telpon','DOB','Alamat','Kota','Total Kunjungan','Kunjungan Terakhir'];
        if ($this->pemeriksaan) {
            $h[] = 'Total Terkait Pemeriksaan';
            $h[] = 'Pemeriksaan Terakhir';
            $h[] = 'Tanggal Kunjungan Terakhir Terkait Pemeriksaan';
        }
        $h[] = 'Total Biaya';
        $h[] = 'Kelas';
        return $h;
    }

    public function title(): string { return 'Laporan Pelanggan'; }

    public function columnWidths(): array
    {
        $w = ['A'=>5,'B'=>15,'C'=>25,'D'=>20,'E'=>15,'F'=>15,'G'=>12,'H'=>30,'I'=>15,'J'=>15,'K'=>18];
        if ($this->pemeriksaan) {
            $w['L'] = 16;
            $w['M'] = 30;
            $w['N'] = 25;
            $w['O'] = 15;
            $w['P'] = 12;
        } else {
            $w['L'] = 15;
            $w['M'] = 12;
        }
        return $w;
    }

    public function styles(Worksheet $sheet): array
    {
        $last = $sheet->getHighestRow();
        $lastCol = $this->pemeriksaan ? 'P' : 'M';

        // Baris 1: Judul
        $sheet->mergeCells("A1:{$lastCol}1");
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
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A2', $subtitle);
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '555555']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCE6F1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(16);

        // Baris 3: Header kolom
        $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E75B6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(20);

        // Alignment per-kolom (bukan per-baris)
        if ($last >= 4) {
            $centerCols = ['A', 'B', 'D', 'G', 'J', 'K'];
            if ($this->pemeriksaan) {
                $centerCols[] = 'L';
                $centerCols[] = 'N';
                $centerCols[] = 'P'; // Kelas
                $biayaCol = 'O';
            } else {
                $centerCols[] = 'M'; // Kelas
                $biayaCol = 'L';
            }
            foreach ($centerCols as $col) {
                $sheet->getStyle("{$col}4:{$col}{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            $sheet->getStyle("{$biayaCol}4:{$biayaCol}{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("{$biayaCol}4:{$biayaCol}{$last}")->getNumberFormat()->setFormatCode('#,##0');
        }

        // Outline border saja (bukan allBorders — hemat memory)
        $sheet->getStyle("A3:{$lastCol}{$last}")->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '2E75B6']]],
        ]);

        $sheet->freezePane('A4');
        return [];
    }
}
