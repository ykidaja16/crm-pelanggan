<?php

namespace App\Exports;

use App\Models\Pelanggan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

// ═══════════════════════════════════════════════════════════════════════════
// MAIN EXPORT — mendelegasikan ke sheet-sheet berikut:
//   1. Ringkasan Eksekutif
//   2. Trend 12 Bulan
//   3. Retensi per Klasifikasi  (Admin / Super Admin / Direktur)
//   4. Analisis per Cabang      (Direktur, multi-cabang)
//   5. Detail At Risk           (61–90 hari)
//   6. Detail Dormant           (91–180 hari)
//   7. Detail Lost              (> 180 hari)
// ═══════════════════════════════════════════════════════════════════════════

class RetentionExport implements WithMultipleSheets
{
    public function __construct(private array $data) {}

    public function sheets(): array
    {
        $d = $this->data;

        $sheets   = [];
        $sheets[] = new RetentionSummarySheet($d);
        $sheets[] = new RetentionTrendSheet($d);

        if ($d['isAdminOrAbove'] && !empty($d['retByKlasifikasi'])) {
            $sheets[] = new RetentionKlasifikasiSheet($d);
        }

        if ($d['isDirektur'] && !empty($d['analisisCabang'])) {
            $sheets[] = new RetentionCabangSheet($d);
        }

        $sheets[] = new RetentionDetailSheet('At Risk', 60,  90,  $d['accessibleCabangIds'], $d['cabangId']);
        $sheets[] = new RetentionDetailSheet('Dormant', 90,  180, $d['accessibleCabangIds'], $d['cabangId']);
        $sheets[] = new RetentionDetailSheet('Lost',    180, null, $d['accessibleCabangIds'], $d['cabangId']);

        return $sheets;
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// HELPER — format label periode
// ═══════════════════════════════════════════════════════════════════════════

function retentionPeriodLabel(array $d): string
{
    return $d['period'] === 'monthly'
        ? Carbon::create($d['year'], $d['month'], 1)->translatedFormat('F Y')
        : (string) $d['year'];
}

function retentionCabangLabel(array $d): string
{
    if (!$d['cabangId']) return 'Semua Cabang';
    return $d['cabangs']->firstWhere('id', $d['cabangId'])?->nama ?? 'Cabang tertentu';
}

// ═══════════════════════════════════════════════════════════════════════════
// SHEET 1 — RINGKASAN EKSEKUTIF
// ═══════════════════════════════════════════════════════════════════════════

class RetentionSummarySheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    public function __construct(private array $d) {}

    public function title(): string { return 'Ringkasan'; }

    public function columnWidths(): array
    {
        return ['A' => 42, 'B' => 22, 'C' => 42];
    }

    public function array(): array
    {
        $d           = $this->d;
        $period      = retentionPeriodLabel($d);
        $cabang      = retentionCabangLabel($d);
        $printedAt   = now()->format('d/m/Y H:i');

        $rows   = [];

        // ── Title + subtitle ──────────────────────────────────────────────
        $rows[] = ['LAPORAN RETENTION CUSTOMER', '', ''];
        $rows[] = ["Periode: {$period}   |   Cabang: {$cabang}   |   Dicetak: {$printedAt}", '', ''];
        $rows[] = ['', '', ''];

        // ── Seksi 1: Metrik retensi periode ──────────────────────────────
        $rows[] = ['METRIK RETENSI PERIODE', '', ''];
        $rows[] = ['Indikator', 'Nilai', 'Keterangan'];
        $rows[] = [
            'Pelanggan Awal (sebelum periode)',
            number_format((int) $d['pelangganAwal']),
            'Basis perhitungan retention rate',
        ];
        $rows[] = [
            'Pelanggan Baru (dalam periode)',
            number_format((int) $d['pelangganBaru']),
            'Pertama kali datang di periode ini',
        ];
        $rows[] = [
            'Pelanggan Retained',
            number_format((int) $d['pelangganRetained']),
            'Pelanggan yang pernah kembali s.d. akhir periode (kumulatif)',
        ];
        $rows[] = [
            'Retention Rate',
            is_null($d['retentionRate']) ? 'Belum ada data' : $d['retentionRate'] . '%',
            'Target ideal ≥ 70%',
        ];
        $rows[] = ['', '', ''];

        // ── Seksi 2: Status retention real-time ──────────────────────────
        $rows[] = ['STATUS RETENTION (Real-time per Hari Ini)', '', ''];
        $rows[] = ['Status', 'Jumlah Pelanggan', 'Definisi'];
        $rows[] = [
            'At Risk',
            number_format((int) ($d['statusCounts']->at_risk_only ?? 0)),
            'Tidak datang 61–90 hari — perlu tindakan segera',
        ];
        $rows[] = [
            'Dormant',
            number_format((int) ($d['statusCounts']->dormant_only ?? 0)),
            'Tidak datang 91–180 hari — butuh re-engagement aktif',
        ];
        $rows[] = [
            'Lost',
            number_format((int) ($d['statusCounts']->lost_total ?? 0)),
            'Tidak datang lebih dari 180 hari — risiko churn tinggi',
        ];
        $rows[] = ['', '', ''];

        // ── Seksi 3: Revenue (opsional) ───────────────────────────────────
        if (!empty($d['revenueData']) && ($d['revenueData']['prev'] ?? 0) > 0) {
            $rv     = $d['revenueData'];
            $rows[] = ['REVENUE RETENTION', '', ''];
            $rows[] = ['Indikator', 'Nilai (Rp)', 'Keterangan'];
            $rows[] = [
                'Total Revenue Periode',
                'Rp ' . number_format($rv['total'], 0, ',', '.'),
                'Seluruh revenue di periode ini',
            ];
            $rows[] = [
                'Revenue dari Pelanggan Retained',
                'Rp ' . number_format($rv['retained'], 0, ',', '.'),
                'Revenue dari pelanggan lama yang kembali',
            ];
            $rows[] = [
                'Revenue dari Pelanggan Baru',
                'Rp ' . number_format($rv['baru'], 0, ',', '.'),
                'Revenue dari pelanggan yang baru pertama kali datang',
            ];
            $rows[] = [
                'Revenue Retention Rate',
                is_null($rv['ret_rate']) ? '-' : $rv['ret_rate'] . '%',
                'Dibanding periode ' . ($rv['prev_label'] ?? '-'),
            ];
            $rows[] = ['', '', ''];
        }

        // ── Seksi 4: Catatan & panduan baca ──────────────────────────────
        $rows[] = ['PANDUAN MEMBACA LAPORAN', '', ''];
        $rows[] = ['Sheet', 'Isi', 'Untuk Siapa'];
        $rows[] = ['Trend 12 Bulan',       'Aktivitas pelanggan 12 bulan terakhir',                       'Semua Role'];
        $rows[] = ['Per Klasifikasi',       'Breakdown retention per kelas pelanggan',                     'Admin ke atas'];
        $rows[] = ['Per Cabang',            'Perbandingan performa antar cabang',                          'Direktur'];
        $rows[] = ['Detail At Risk',        'Daftar pelanggan yang tidak datang 61–90 hari (butuh follow-up)', 'Semua Role'];
        $rows[] = ['Detail Dormant',        'Daftar pelanggan yang tidak datang 91–180 hari',              'Semua Role'];
        $rows[] = ['Detail Lost',           'Daftar pelanggan yang tidak datang > 180 hari',               'Semua Role'];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();

        // Baris 1: Judul utama
        $sheet->mergeCells("A1:C1");
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A56A4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // Baris 2: Subtitle
        $sheet->mergeCells("A2:C2");
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '444444']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCE6F1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(16);

        // Deteksi baris section header dan column header
        $sectionBg  = '2E75B6';
        $colHeaderBg = '4472C4';

        for ($r = 3; $r <= $lastRow; $r++) {
            $val = (string) ($sheet->getCell("A{$r}")->getValue() ?? '');

            // Section header: semua huruf kapital dan panjang > 4
            $isSection = $val && $val === strtoupper($val) && mb_strlen($val) > 4
                && $sheet->getCell("B{$r}")->getValue() === '';

            if ($isSection) {
                $sheet->mergeCells("A{$r}:C{$r}");
                $sheet->getStyle("A{$r}:C{$r}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $sectionBg]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 1],
                ]);
                $sheet->getRowDimension($r)->setRowHeight(22);
                continue;
            }

            // Column header rows
            if (in_array($val, ['Indikator', 'Status', 'Sheet'])) {
                $sheet->getStyle("A{$r}:C{$r}")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colHeaderBg]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension($r)->setRowHeight(18);
                continue;
            }

            // Retention Rate highlight
            if (str_contains($val, 'Retention Rate')) {
                $rateStr = (string) ($sheet->getCell("B{$r}")->getValue() ?? '');
                $isGood  = str_ends_with($rateStr, '%') && (float) $rateStr >= 70;
                $sheet->getStyle("B{$r}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => $isGood ? '198754' : 'DC3545']],
                ]);
            }

            // Zebra: baris data ganjil/genap (hanya yang bukan blank)
            if ($val && $r % 2 === 0) {
                $sheet->getStyle("A{$r}:C{$r}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F8FC']],
                ]);
            }
        }

        // Border outline keseluruhan
        $sheet->getStyle("A1:C{$lastRow}")->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '2E75B6']]],
        ]);

        // Nilai rata kanan
        $sheet->getStyle("B1:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [];
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// SHEET 2 — TREND 12 BULAN
// ═══════════════════════════════════════════════════════════════════════════

class RetentionTrendSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths, WithCustomStartCell
{
    public function __construct(private array $d) {}

    public function title(): string    { return 'Trend 12 Bulan'; }
    public function startCell(): string { return 'A3'; }

    public function columnWidths(): array
    {
        return ['A' => 16, 'B' => 26, 'C' => 22];
    }

    public function array(): array
    {
        $rows = [['Bulan', 'Total Pelanggan Aktif', 'Total Kunjungan']];
        foreach ($this->d['trendLabels'] as $i => $label) {
            $rows[] = [
                $label,
                (int) ($this->d['trendPelanggan'][$i] ?? 0),
                (int) ($this->d['trendKunjungan'][$i]  ?? 0),
            ];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $last = $sheet->getHighestRow();

        $sheet->mergeCells('A1:C1');
        $sheet->setCellValue('A1', 'TREND KUNJUNGAN 12 BULAN TERAKHIR');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A56A4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->mergeCells('A2:C2');
        $sheet->setCellValue('A2', 'Jumlah pelanggan aktif & kunjungan per bulan (12 bulan terakhir)   |   Dicetak: ' . now()->format('d/m/Y H:i'));
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '555555']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCE6F1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(15);

        // Header row (A3)
        $sheet->getStyle('A3:C3')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E75B6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(20);

        if ($last >= 4) {
            for ($r = 4; $r <= $last; $r++) {
                if ($r % 2 === 0) {
                    $sheet->getStyle("A{$r}:C{$r}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F7FC']],
                    ]);
                }
            }
            $sheet->getStyle("B4:C{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("B4:C{$last}")->getNumberFormat()->setFormatCode('#,##0');
        }

        $sheet->getStyle("A3:C{$last}")->applyFromArray([
            'borders' => [
                'outline'    => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '2E75B6']],
                'allBorders' => ['borderStyle' => Border::BORDER_THIN,   'color' => ['rgb' => 'CCCCCC']],
            ],
        ]);

        $sheet->freezePane('A4');
        return [];
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// SHEET 3 — RETENSI PER KLASIFIKASI
// ═══════════════════════════════════════════════════════════════════════════

class RetentionKlasifikasiSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths, WithCustomStartCell
{
    public function __construct(private array $d) {}

    public function title(): string    { return 'Per Klasifikasi'; }
    public function startCell(): string { return 'A3'; }

    public function columnWidths(): array
    {
        return ['A' => 18, 'B' => 18, 'C' => 18, 'D' => 18, 'E' => 18];
    }

    public function array(): array
    {
        $rows = [['Klasifikasi', 'Pelanggan Awal', 'Retained', 'Pelanggan Baru', 'Retention Rate']];
        foreach ($this->d['retByKlasifikasi'] as $rk) {
            $rows[] = [
                ucfirst($rk['kelas']),
                (int) $rk['awal'],
                (int) $rk['retained'],
                (int) $rk['baru'],
                is_null($rk['rate']) ? '-' : $rk['rate'] . '%',
            ];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $last   = $sheet->getHighestRow();
        $period = retentionPeriodLabel($this->d);
        $cabang = retentionCabangLabel($this->d);

        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'RETENSI PER KLASIFIKASI PELANGGAN');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A56A4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->mergeCells('A2:E2');
        $sheet->setCellValue('A2', "Periode: {$period}   |   Cabang: {$cabang}   |   Dicetak: " . now()->format('d/m/Y H:i'));
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '555555']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCE6F1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(15);

        $sheet->getStyle('A3:E3')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E75B6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(20);

        if ($last >= 4) {
            for ($r = 4; $r <= $last; $r++) {
                if ($r % 2 === 0) {
                    $sheet->getStyle("A{$r}:E{$r}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F7FC']],
                    ]);
                }
                // Warna retention rate
                $rateStr = (string) ($sheet->getCell("E{$r}")->getValue() ?? '');
                if (str_ends_with($rateStr, '%')) {
                    $rate  = (float) $rateStr;
                    $color = $rate >= 70 ? '198754' : ($rate >= 40 ? 'B45309' : 'DC3545');
                    $sheet->getStyle("E{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => $color]],
                    ]);
                }
            }
            $sheet->getStyle("B4:D{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("B4:D{$last}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("A4:A{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E4:E{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $sheet->getStyle("A3:E{$last}")->applyFromArray([
            'borders' => [
                'outline'    => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '2E75B6']],
                'allBorders' => ['borderStyle' => Border::BORDER_THIN,   'color' => ['rgb' => 'CCCCCC']],
            ],
        ]);

        $sheet->freezePane('A4');
        return [];
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// SHEET 4 — ANALISIS PER CABANG (Direktur)
// ═══════════════════════════════════════════════════════════════════════════

class RetentionCabangSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths, WithCustomStartCell
{
    public function __construct(private array $d) {}

    public function title(): string    { return 'Per Cabang'; }
    public function startCell(): string { return 'A3'; }

    public function columnWidths(): array
    {
        return ['A' => 28, 'B' => 16, 'C' => 16, 'D' => 16, 'E' => 16, 'F' => 18, 'G' => 15];
    }

    public function array(): array
    {
        $rows = [['Cabang', 'Pelanggan Awal', 'Retained', 'Pelanggan Baru', 'Lost (>180hr)', 'Retention Rate', 'Growth']];
        foreach ($this->d['analisisCabang'] as $c) {
            $growth = is_null($c['growth']) ? '-' : (($c['growth'] > 0 ? '+' : '') . $c['growth'] . '%');
            $rows[] = [
                $c['nama'],
                (int) $c['awal'],
                (int) $c['retained'],
                (int) $c['baru'],
                (int) $c['lost'],
                is_null($c['retRate']) ? '-' : $c['retRate'] . '%',
                $growth,
            ];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $last   = $sheet->getHighestRow();
        $period = retentionPeriodLabel($this->d);

        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'ANALISIS RETENTION PER CABANG');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A56A4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', "Periode: {$period}   |   Diurutkan: Retention Rate tertinggi   |   Dicetak: " . now()->format('d/m/Y H:i'));
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '555555']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCE6F1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(15);

        $sheet->getStyle('A3:G3')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E75B6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(20);

        if ($last >= 4) {
            for ($r = 4; $r <= $last; $r++) {
                $sheet->getStyle("A{$r}:G{$r}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $r % 2 === 0 ? 'F2F7FC' : 'FFFFFF']],
                ]);

                // Warna retention rate cabang
                $rateStr = (string) ($sheet->getCell("F{$r}")->getValue() ?? '');
                if (str_ends_with($rateStr, '%')) {
                    $rate  = (float) $rateStr;
                    $color = $rate >= 70 ? '198754' : ($rate >= 40 ? 'B45309' : 'DC3545');
                    $sheet->getStyle("F{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => $color]],
                    ]);
                }

                // Warna growth
                $growthStr = (string) ($sheet->getCell("G{$r}")->getValue() ?? '');
                if ($growthStr !== '-') {
                    $isPos = str_starts_with($growthStr, '+');
                    $sheet->getStyle("G{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => $isPos ? '198754' : 'DC3545']],
                    ]);
                }
            }
            $sheet->getStyle("B4:E{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("B4:E{$last}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("F4:G{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $sheet->getStyle("A3:G{$last}")->applyFromArray([
            'borders' => [
                'outline'    => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '2E75B6']],
                'allBorders' => ['borderStyle' => Border::BORDER_THIN,   'color' => ['rgb' => 'CCCCCC']],
            ],
        ]);

        $sheet->freezePane('A4');
        return [];
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// SHEET 5/6/7 — DETAIL PELANGGAN (At Risk / Dormant / Lost)
// ═══════════════════════════════════════════════════════════════════════════

class RetentionDetailSheet implements FromCollection, WithTitle, WithStyles, WithColumnWidths, WithCustomStartCell, WithHeadings
{
    private static array $colorMap = [
        'At Risk' => ['bg' => 'FFC107', 'fg' => '212529'],
        'Dormant' => ['bg' => 'C0392B', 'fg' => 'FFFFFF'],
        'Lost'    => ['bg' => '495057', 'fg' => 'FFFFFF'],
    ];

    public function __construct(
        private string $label,
        private int    $minDays,
        private ?int   $maxDays,
        private array  $accessibleCabangIds,
        private ?int   $cabangId,
    ) {}

    public function title(): string    { return "Detail {$this->label}"; }
    public function startCell(): string { return 'A3'; }

    public function headings(): array
    {
        return ['PID', 'Nama Pelanggan', 'No HP', 'Cabang', 'Klasifikasi', 'Kota', 'Kunjungan Terakhir', 'Hari Tidak Datang'];
    }

    public function collection()
    {
        $lastVisitSub = DB::table('kunjungans')
            ->selectRaw('pelanggan_id, MAX(tanggal_kunjungan) as last_visit')
            ->groupBy('pelanggan_id');

        $query = Pelanggan::query()
            ->joinSub($lastVisitSub, 'lv', 'lv.pelanggan_id', '=', 'pelanggans.id')
            ->with('cabang')
            ->whereNull('pelanggans.deleted_at')
            ->select('pelanggans.*', 'lv.last_visit', DB::raw('DATEDIFF(CURDATE(), lv.last_visit) as days_since'))
            ->whereRaw('DATEDIFF(CURDATE(), lv.last_visit) > ?', [$this->minDays]);

        if ($this->maxDays) {
            $query->whereRaw('DATEDIFF(CURDATE(), lv.last_visit) <= ?', [$this->maxDays]);
        }

        if ($this->cabangId) {
            $query->where('pelanggans.cabang_id', $this->cabangId);
        } elseif (!empty($this->accessibleCabangIds)) {
            $query->whereIn('pelanggans.cabang_id', $this->accessibleCabangIds);
        }

        return $query->orderByRaw('days_since DESC')->get()->map(fn($p) => [
            $p->pid,
            $p->nama,
            $p->no_telp  ?? '-',
            $p->cabang?->nama ?? '-',
            $p->class    ?: 'Lainnya',
            $p->kota     ?? '-',
            $p->last_visit ? Carbon::parse($p->last_visit)->format('d/m/Y') : '-',
            (int) $p->days_since,
        ]);
    }

    public function columnWidths(): array
    {
        return ['A' => 14, 'B' => 28, 'C' => 16, 'D' => 18, 'E' => 14, 'F' => 16, 'G' => 20, 'H' => 20];
    }

    public function styles(Worksheet $sheet): array
    {
        $last  = $sheet->getHighestRow();
        $total = max(0, $last - 3);
        $c     = self::$colorMap[$this->label] ?? ['bg' => '2E75B6', 'fg' => 'FFFFFF'];
        $def   = match ($this->label) {
            'At Risk' => 'Tidak datang 61–90 hari — Perlu follow-up segera',
            'Dormant' => 'Tidak datang 91–180 hari — Butuh re-engagement',
            'Lost'    => 'Tidak datang > 180 hari — Risiko kehilangan permanen',
            default   => '',
        };

        // Row 1: Title berwarna sesuai status
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', "DETAIL PELANGGAN {$this->label}  —  {$def}");
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => $c['fg']]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $c['bg']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Row 2: Subtitle
        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue('A2', "Total: " . number_format($total) . " pelanggan   |   Diurutkan: Hari Tidak Datang (terlama)   |   Dicetak: " . now()->format('d/m/Y H:i'));
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '444444']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCE6F1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(15);

        // Row 3: Header
        $sheet->getStyle('A3:H3')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E75B6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(20);

        if ($last >= 4) {
            for ($r = 4; $r <= $last; $r++) {
                if ($r % 2 === 0) {
                    $sheet->getStyle("A{$r}:H{$r}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']],
                    ]);
                }
            }
            // Center: PID, No HP, Cabang, Klasifikasi, Kota, Tanggal, Hari
            foreach (['A', 'C', 'D', 'E', 'F', 'G', 'H'] as $col) {
                $sheet->getStyle("{$col}4:{$col}{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            // Format hari tidak datang: angka ribuan
            $sheet->getStyle("H4:H{$last}")->getNumberFormat()->setFormatCode('#,##0');
        }

        $sheet->getStyle("A3:H{$last}")->applyFromArray([
            'borders' => [
                'outline'    => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '2E75B6']],
                'allBorders' => ['borderStyle' => Border::BORDER_THIN,   'color' => ['rgb' => 'DDDDDD']],
            ],
        ]);

        $sheet->freezePane('A4');
        return [];
    }
}
