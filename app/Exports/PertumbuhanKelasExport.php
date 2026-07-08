<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Collection;

class PertumbuhanKelasExport implements WithMultipleSheets
{
    public function __construct(
        private array $chartData,
        private array $summaryData,
        private string $periodLabel
    ) {}

    public function sheets(): array
    {
        return [
            new PertumbuhanKelasSummarySheet($this->chartData, $this->summaryData, $this->periodLabel),
        ];
    }
}

class PertumbuhanKelasSummarySheet implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithCustomStartCell
{
    private array $kelasColors = [
        'Prioritas' => '8B5CF6',
        'Loyal'     => '3B82F6',
        'Potensial' => 'F59E0B',
        'Umum'      => '6B7280',
    ];

    public function __construct(
        private array $chartData,
        private array $summaryData,
        private string $periodLabel
    ) {}

    public function collection(): Collection
    {
        $rows = collect();

        // ── SECTION 1: Summary per Kelas ──────────────────────────────
        foreach ($this->summaryData as $item) {
            $rows->push([
                $item['kelas'],
                $item['total'],
                $item['aktif'],
                $item['pct'] . '%',
            ]);
        }

        // Grand total row
        $grandTotal = array_sum(array_column($this->summaryData, 'total'));
        $grandAktif = array_sum(array_column($this->summaryData, 'aktif'));
        $rows->push(['TOTAL', $grandTotal, $grandAktif, '100%']);

        $rows->push(['', '', '', '']);

        // ── SECTION 2: Trend aktif per periode ────────────────────────
        // Keterangan section
        $rows->push(['DETAIL TREN PER PERIODE', '', '', '']);
        $rows->push(['Catatan: Angka di bawah menunjukkan JUMLAH PELANGGAN AKTIF (berkunjung) per periode per kelas.', '', '', '']);
        $rows->push(['', '', '', '']);

        // Header kolom tren — dengan suffix (Aktif) agar jelas
        $datasets   = $this->chartData['datasets'] ?? [];
        $kelasNames = array_column($datasets, 'label');
        $headerRow  = array_merge(
            ['Periode'],
            array_map(fn($k) => $k . ' (Aktif)', $kelasNames),
            ['Total Aktif']
        );
        $rows->push($headerRow);

        // Data baris tren
        $labels = $this->chartData['labels'] ?? [];
        foreach ($labels as $i => $label) {
            $dataRow  = [$label];
            $rowTotal = 0;
            foreach ($datasets as $ds) {
                $val      = $ds['data'][$i] ?? 0;
                $dataRow[] = $val;
                $rowTotal += $val;
            }
            $dataRow[] = $rowTotal; // kolom Total Aktif
            $rows->push($dataRow);
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['Kelas', 'Total Pelanggan', 'Aktif di Periode', '% dari Total'];
    }

    public function startCell(): string { return 'A3'; }
    public function title(): string { return 'Pertumbuhan Kelas'; }

    public function columnWidths(): array
    {
        // A=Kelas/Periode, B-E=kelas data, last=total
        return ['A' => 22, 'B' => 20, 'C' => 20, 'D' => 20, 'E' => 20, 'F' => 16];
    }

    public function styles(Worksheet $sheet): array
    {
        $last     = $sheet->getHighestRow();
        $lastCol  = $sheet->getHighestColumn();
        $colRange = "A3:{$lastCol}{$last}";

        // ── Row 1: Title ─────────────────────────────────────────────
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', 'PERTUMBUHAN KELAS PELANGGAN — ' . strtoupper($this->periodLabel));
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A56A4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // ── Row 2: Print date ─────────────────────────────────────────
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A2', 'Dicetak: ' . now()->format('d-m-Y H:i'));
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '555555']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCE6F1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // ── Row 3: Header Ringkasan ───────────────────────────────────
        $sheet->getStyle('A3:D3')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E75B6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(22);

        // ── Summary data rows (4 kelas + total) ───────────────────────
        // Find grand total row (row 8 = 3 header + 4 kelas + 1 total)
        $summaryEndRow = 3 + count($this->summaryData);
        $totalRow      = $summaryEndRow + 1;

        // Color per kelas row
        $kelasRows = ['Prioritas' => '8B5CF6', 'Loyal' => '3B82F6', 'Potensial' => 'F59E0B', 'Umum' => '6B7280'];
        $rowIdx = 4;
        foreach ($this->summaryData as $item) {
            $kColor = $this->kelasColors[$item['kelas']] ?? '6B7280';
            $sheet->getStyle("A{$rowIdx}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $kColor]],
            ]);
            $sheet->getStyle("B{$rowIdx}:D{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $rowIdx++;
        }

        // Grand total row style
        $sheet->getStyle("A{$totalRow}:D{$totalRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '1e293b']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // ── Find and style the tren section ──────────────────────────
        // Section header row (DETAIL TREN)
        $trenSectionRow = $totalRow + 2;
        $sheet->mergeCells("A{$trenSectionRow}:{$lastCol}{$trenSectionRow}");
        $sheet->getStyle("A{$trenSectionRow}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a5f']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 1],
        ]);
        $sheet->getRowDimension($trenSectionRow)->setRowHeight(22);

        // Catatan row
        $noteRow = $trenSectionRow + 1;
        $sheet->mergeCells("A{$noteRow}:{$lastCol}{$noteRow}");
        $sheet->getStyle("A{$noteRow}")->applyFromArray([
            'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '444444']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF9C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 1, 'wrapText' => true],
        ]);
        $sheet->getRowDimension($noteRow)->setRowHeight(20);

        // Tren header row (Periode | Prioritas (Aktif) | ...)
        $trenHeaderRow = $noteRow + 2;
        $sheet->getStyle("A{$trenHeaderRow}:{$lastCol}{$trenHeaderRow}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '374151']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($trenHeaderRow)->setRowHeight(22);

        // Tren data rows — alternate background
        for ($r = $trenHeaderRow + 1; $r <= $last; $r++) {
            $bg = ($r % 2 === 0) ? 'F8FAFC' : 'FFFFFF';
            $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            // Periode column left-align
            $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        // Outer border all data
        $sheet->getStyle($colRange)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
        ]);

        // Thick border around tren header
        $sheet->getStyle("A{$trenHeaderRow}:{$lastCol}{$last}")->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '374151']]],
        ]);

        $sheet->freezePane('A4');
        return [];
    }
}
