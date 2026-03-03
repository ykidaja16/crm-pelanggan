<?php

namespace App\Exports;

use App\Models\ActivityLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ActivityLogExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected Collection $logs;

    public function __construct(Collection $logs)
    {
        $this->logs = $logs;
    }

    public function title(): string
    {
        return 'Log Aktivitas';
    }

    public function collection(): Collection
    {
        return $this->logs->map(function (ActivityLog $log) {
            return [
                'no'          => $this->logs->search(fn($l) => $l->id === $log->id) + 1,
                'waktu'       => $log->created_at?->format('d/m/Y H:i:s') ?? '-',
                'username'    => $log->username,
                'role'        => $log->role,
                'action'      => ActivityLog::actionLabel($log->action),
                'module'      => ucfirst($log->module),
                'description' => $log->description,
                'ip_address'  => $log->ip_address ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No',
            'Waktu',
            'Username',
            'Role',
            'Aksi',
            'Modul',
            'Deskripsi',
            'IP Address',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold'  => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                    'size'  => 11,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF0056B3'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 20,
            'C' => 18,
            'D' => 15,
            'E' => 12,
            'F' => 14,
            'G' => 45,
            'H' => 18,
        ];
    }
}
