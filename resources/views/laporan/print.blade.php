<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pelanggan - Print</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 11pt;
            line-height: 1.3;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        
        .header h1 {
            font-size: 16pt;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .header .subtitle {
            font-size: 12pt;
            color: #333;
            margin-bottom: 3px;
        }
        
        .header .date {
            font-size: 10pt;
            color: #666;
        }
        
        .filters {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
        }
        
        .filters-title {
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 10pt;
        }
        
        .filter-item {
            display: inline-block;
            margin-right: 20px;
            margin-bottom: 5px;
            font-size: 9pt;
        }
        
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        table.data th {
            background-color: #333;
            color: white;
            padding: 8px 6px;
            text-align: center;
            font-size: 9pt;
            border: 1px solid #000;
            font-weight: bold;
        }
        
        table.data td {
            padding: 6px;
            border: 1px solid #000;
            font-size: 9pt;
            vertical-align: top;
        }
        
        table.data tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        .text-left   { text-align: left; }
        
        .summary-box {
            margin-top: 20px;
            padding: 15px;
            background-color: #f5f5f5;
            border: 2px solid #333;
        }
        
        .summary-title {
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 10px;
            text-align: center;
            text-transform: uppercase;
        }
        
        .summary-grid {
            display: flex;
            justify-content: space-between;
        }
        
        .summary-item {
            text-align: center;
            flex: 1;
        }
        
        .summary-label {
            font-size: 9pt;
            color: #666;
            margin-bottom: 5px;
        }
        
        .summary-value {
            font-size: 12pt;
            font-weight: bold;
            color: #000;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9pt;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }
        
        .badge-prioritas { background-color: #dc3545; color: white; }
        .badge-loyal     { background-color: #28a745; color: white; }
        .badge-potensial { background-color: #ffc107; color: #000; }
        
        @media print {
            body {
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print { display: none !important; }
            .header, .filters, .summary-box { page-break-inside: avoid; }
            table.data { page-break-inside: auto; }
            table.data tr { page-break-inside: avoid; page-break-after: auto; }
            table.data thead { display: table-header-group; }
        }
        
        .print-controls {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
        }
        
        .btn-print {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 30px;
            font-size: 12pt;
            cursor: pointer;
            border-radius: 5px;
        }
        
        .btn-print:hover { background-color: #0056b3; }
        
        .print-info {
            margin-top: 10px;
            font-size: 9pt;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Print Controls (hidden when printing) -->
    <div class="print-controls no-print">
        <button class="btn-print" onclick="window.print()">
            &#128438; Cetak Laporan
        </button>
        <div class="print-info">
            Klik tombol di atas untuk mencetak laporan. Pastikan printer sudah terhubung.
        </div>
    </div>

    <!-- Header -->
    <div class="header">
        <h1>Laporan Pelanggan</h1>
        <div class="subtitle">Medical Lab CRM</div>
        <div class="date">Dicetak pada: {{ date('d F Y, H:i:s') }}</div>
    </div>

    <!-- Filters -->
    @if(count($filters) > 0)
    <div class="filters">
        <div class="filters-title">Filter yang Digunakan:</div>
        <div>
            @foreach($filters as $key => $value)
                <span class="filter-item">
                    <strong>{{ $key }}:</strong> {{ $value }}
                </span>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Data Table -->
    <table class="data">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 10%;">PID</th>
                <th style="width: 18%;">Nama Pasien</th>
                <th style="width: 10%;">Cabang</th>
                <th style="width: 10%;">No Telp</th>
                <th style="width: 8%;">DOB</th>
                <th style="width: 8%;">Kunjungan</th>
                <th style="width: 10%;">Kunjungan Terakhir</th>
                <th style="width: 12%;">Total Biaya</th>
                <th style="width: 10%;">Kelas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pelanggan as $index => $p)
            @php
                // Pilih kolom period-specific jika filter periode aktif
                $biaya      = $usePeriodeBiaya
                    ? ($p->biaya_periode      ?? $p->total_biaya      ?? 0)
                    : ($p->total_biaya        ?? 0);
                $kedatangan = $usePeriodeBiaya
                    ? ($p->kedatangan_periode ?? $p->total_kedatangan ?? 0)
                    : ($p->total_kedatangan   ?? 0);
                $kelas      = $p->class_at_period ?? $p->class ?? 'Potensial';
                $badgeClass = match($kelas) {
                    'Prioritas' => 'badge-prioritas',
                    'Loyal'     => 'badge-loyal',
                    default     => 'badge-potensial',
                };
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center">{{ $p->pid }}</td>
                <td class="text-left">{{ $p->nama }}</td>
                <td class="text-center">{{ $p->cabang?->nama ?? '-' }}</td>
                <td class="text-center">{{ $p->no_telp ?? '-' }}</td>
                <td class="text-center">{{ $p->dob ? $p->dob->format('d-m-Y') : '-' }}</td>
                <td class="text-center">{{ (int) $kedatangan }} kali</td>
                <td class="text-center">
                    {{ $p->tgl_kunjungan_terakhir
                        ? \Carbon\Carbon::parse($p->tgl_kunjungan_terakhir)->format('d-m-Y')
                        : '-' }}
                </td>
                <td class="text-right">Rp {{ number_format((float)$biaya, 0, ',', '.') }}</td>
                <td class="text-center">
                    <span class="badge {{ $badgeClass }}">{{ $kelas }}</span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center">Tidak ada data yang sesuai dengan filter.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Summary -->
    @php
        $totalBiayaPrint      = $pelanggan->sum(fn($p) => $usePeriodeBiaya ? ($p->biaya_periode ?? $p->total_biaya ?? 0) : ($p->total_biaya ?? 0));
        $totalKunjunganPrint  = $pelanggan->sum(fn($p) => $usePeriodeBiaya ? ($p->kedatangan_periode ?? $p->total_kedatangan ?? 0) : ($p->total_kedatangan ?? 0));
    @endphp
    <div class="summary-box">
        <div class="summary-title">Ringkasan Laporan</div>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">Total Pelanggan</div>
                <div class="summary-value">{{ $pelanggan->count() }} orang</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Kunjungan</div>
                <div class="summary-value">{{ number_format((int)$totalKunjunganPrint) }} kali</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Biaya</div>
                <div class="summary-value">Rp {{ number_format((float)$totalBiayaPrint, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Medical Lab CRM - Sistem Informasi Pelanggan</p>
        <p>&copy; {{ date('Y') }} - All Rights Reserved</p>
    </div>
</body>
</html>
