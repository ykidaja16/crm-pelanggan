<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class DashboardDetailExport implements FromQuery, WithMapping, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithCustomStartCell
{
    protected Builder $query;
    protected string $title;
    protected string $cabangNama;
    protected string $type;
    private int $rowNum = 0;

    public function __construct(Builder $query, string $title, string $cabangNama = '', string $type = '')
    {
        $this->query      = $query;
        $this->title      = $title;
        $this->cabangNama = $cabangNama ?: 'Semua Cabang';
        $this->type       = $type;
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function map($p): array
    {
        $isPeriod = in_array($this->type, ['kunjungan_bulan_kemarin', 'kunjungan_tahun_ini']);

        return [
            ++$this->rowNum,
            $p->pid,
            $p->nama,
            $p->nik ?? '-',
            $p->cabang?->nama ?? '-',
            $p->no_telp ?? '-',
            $p->dob ? Carbon::parse($p->dob)->format('d-m-Y') : '-',
            $p->alamat ?? '-',
            $isPeriod ? (int) ($p->kunjungan_periode ?? 0) : (int) $p->total_kedatangan,
            $p->tgl_kunjungan_terakhir ? Carbon::parse($p->tgl_kunjungan_terakhir)->format('d-m-Y') : '-',
            $isPeriod ? (float) ($p->biaya_periode ?? 0) : (float) $p->total_biaya,
            $p->class ?? '-',
        ];
    }

    public function headings(): array
    {
        $kunjunganLabel = match($this->type) {
            'kunjungan_bulan_kemarin' => 'Jml Kunjungan Bulan Kemarin',
            'kunjungan_tahun_ini'     => 'Jml Kunjungan Tahun Ini',
            default                   => 'Jml Kunjungan',
        };

        $biayaLabel = match($this->type) {
            'kunjungan_bulan_kemarin' => 'Total Biaya Bulan Kemarin',
            'kunjungan_tahun_ini'     => 'Total Biaya Tahun Ini',
            default                   => 'Total Biaya',
        };

        return ['No','PID','Nama','NIK','Cabang','No. Telepon','DOB','Alamat',$kunjunganLabel,'Tgl Kunjungan Terakhir',$biayaLabel,'Kelas'];
    }

    public function title(): string { return 'Detail'; }

    public function columnWidths(): array
    {
        return ['A'=>5,'B'=>16,'C'=>28,'D'=>20,'E'=>18,'F'=>16,'G'=>13,'H'=>35,'I'=>16,'J'=>22,'K'=>18,'L'=>13];
    }

    public function styles(Worksheet $sheet): array
    {
        $last = $sheet->getHighestRow();

        // Baris 1: Judul
        $sheet->mergeCells('A1:L1');
        $sheet->setCellValue('A1', strtoupper($this->title) . ' — ' . $this->cabangNama);
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A56A4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Baris 2: Tanggal cetak
        $sheet->mergeCells('A2:L2');
        $sheet->setCellValue('A2', 'Dicetak: ' . now()->format('d-m-Y H:i'));
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '555555']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCE6F1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(16);

        // Baris 3: Header
        $sheet->getStyle('A3:L3')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E75B6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(20);

        // Data alignment (tidak loop per baris — hanya per kolom)
        if ($last >= 4) {
            foreach (['A','B','G','I','J','L'] as $col) {
                $sheet->getStyle("{$col}4:{$col}{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            $sheet->getStyle("K4:K{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("K4:K{$last}")->getNumberFormat()->setFormatCode('#,##0');
        }

        // Outline border saja (bukan per sel)
        $sheet->getStyle("A3:L{$last}")->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '2E75B6']]],
        ]);

        $sheet->freezePane('A4');
        return [];
    }
}
