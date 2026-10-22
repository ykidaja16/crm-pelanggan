<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use App\Models\Pelanggan;
use App\Models\Kunjungan;
use App\Models\Dokter;
use App\Models\Marketing;
use App\Models\KelompokPelanggan;
use App\Models\ActivityLog;

class KunjunganKolomTerbaruUpdateController extends Controller
{
    /**
     * Tampilkan halaman Update Kolom Terbaru khusus role IT
     */
    public function index()
    {
        return view('kunjungan.update-kolom-terbaru');
    }

    /**
     * Download template Excel untuk update kolom terbaru (18 kolom)
     */
    public function downloadTemplate()
    {
        $headers = [
            'PID',
            'TANGGAL KUNJUNGAN',
            'JK',
            'Kecamatan',
            'Tipe Omset',
            'Kode Kel.Pelanggan',
            'KODE DOKTER',
            'DOKTER',
            'MOU',
            'MARKETING',
            'BRUTO',
            'DISC',
            'PAY',
            'JML TEST',
            'PEMERIKSAAN',
            'NIP',
            'JABATAN',
            'LOCATION',
        ];

        $data = [
            [
                'LX001',
                '2021-04-09',
                'L',
                'Coblong',
                'Klinis',
                'MND',
                'DR01',
                'dr. Budi Santoso, Sp.PK',
                'MOU-2021-01',
                'Ahmad Marketing',
                '500000',
                '50000',
                '450000',
                '3',
                'Diabetes, Kolestrol, Urine Lengkap',
                '198501012010011001',
                'Manager IT',
                'Gedung A Lantai 2',
            ],
            [
                'BD00002',
                '2022-06-15',
                'P',
                'Cicendo',
                'Rujukan',
                'KLN',
                'DR02',
                'dr. Siti Aminah, Sp.A',
                'MOU-2022-05',
                'Rina Marketing',
                '750000',
                '0',
                '750000',
                '4',
                'Darah Lengkap, SGOT, SGPT, Asam Urat',
                '199002022015022002',
                'Staff Administrasi',
                'Kantor Pusat',
            ],
            [
                'SB00003',
                '2023-01-20',
                'L',
                'Sukajadi',
                'Medical Checkup',
                'MND',
                'DR03',
                'dr. Hendra Wijaya',
                '',
                'Doni Marketing',
                '350000',
                '35000',
                '315000',
                '2',
                'Urine Lengkap, Glukosa Puasa',
                '',
                '',
                'Cabang Utama',
            ],
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Update Kolom');

        // Set Headers
        foreach ($headers as $col => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . '1', $header);
        }

        // Set Sample Data
        foreach ($data as $row => $rowData) {
            foreach ($rowData as $col => $value) {
                $cellCoordinate = Coordinate::stringFromColumnIndex($col + 1) . ($row + 2);
                $sheet->setCellValueExplicit($cellCoordinate, (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }

        // Auto width
        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
        }

        $lastColLetter = Coordinate::stringFromColumnIndex(count($headers));

        // Header style
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1A56A4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'CCCCCC'],
                ],
            ],
        ];
        $sheet->getStyle("A1:{$lastColLetter}1")->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(24);

        $writer = new Xlsx($spreadsheet);
        $filename = 'template_update_kolom_terbaru.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'tpl_kolom_terbaru_');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Memproses import Excel update kolom terbaru
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt',
        ], [
            'file.required' => 'File Excel wajib diunggah.',
            'file.file'     => 'File harus berupa berkas dokumen.',
            'file.mimes'    => 'File harus berformat .xlsx, .xls, atau .csv.',
        ]);

        $file = $request->file('file');

        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);

            if (empty($rows) || count($rows) < 2) {
                return back()->with('error', 'File Excel kosong atau tidak memiliki baris data.');
            }

            // In-memory caches to minimize DB queries
            $dokterCache    = [];
            $marketingCache = [];
            $kelompokCache  = [];

            // Detect column mapping from header row
            $colMap = [
                'pid'           => 0,
                'tanggal'       => 1,
                'jk'            => 2,
                'kecamatan'     => 3,
                'tipe_omset'    => 4,
                'kode_kelompok' => 5,
                'kode_dokter'   => 6,
                'dokter'        => 7,
                'mou'           => 8,
                'marketing'     => 9,
                'bruto'         => 10,
                'disc'          => 11,
                'pay'           => 12,
                'jml_test'      => 13,
                'pemeriksaan'   => 14,
                'nip'           => 15,
                'jabatan'       => 16,
                'location'      => 17,
            ];

            $headerRow = $rows[0] ?? [];
            $hasHeader = false;
            foreach ($headerRow as $idx => $cellText) {
                $norm = strtolower(trim((string) $cellText));
                if (str_contains($norm, 'pid')) {
                    $hasHeader = true;
                    $colMap['pid'] = $idx;
                } elseif (str_contains($norm, 'tanggal') || str_contains($norm, 'tgl')) {
                    $colMap['tanggal'] = $idx;
                } elseif ($norm === 'jk' || str_contains($norm, 'kelamin')) {
                    $colMap['jk'] = $idx;
                } elseif (str_contains($norm, 'kecamatan')) {
                    $colMap['kecamatan'] = $idx;
                } elseif (str_contains($norm, 'tipe omset') || str_contains($norm, 'tipe_omset')) {
                    $colMap['tipe_omset'] = $idx;
                } elseif (str_contains($norm, 'kel.pelanggan') || str_contains($norm, 'kelompok')) {
                    $colMap['kode_kelompok'] = $idx;
                } elseif (str_contains($norm, 'kode dokter') || str_contains($norm, 'kd_dokter')) {
                    $colMap['kode_dokter'] = $idx;
                } elseif ($norm === 'dokter' || str_contains($norm, 'nama dokter')) {
                    $colMap['dokter'] = $idx;
                } elseif (str_contains($norm, 'mou') || str_contains($norm, 'agreement')) {
                    $colMap['mou'] = $idx;
                } elseif (str_contains($norm, 'marketing')) {
                    $colMap['marketing'] = $idx;
                } elseif (str_contains($norm, 'bruto')) {
                    $colMap['bruto'] = $idx;
                } elseif (str_contains($norm, 'disc') || str_contains($norm, 'diskon')) {
                    $colMap['disc'] = $idx;
                } elseif (str_contains($norm, 'pay') || str_contains($norm, 'bayar')) {
                    $colMap['pay'] = $idx;
                } elseif (str_contains($norm, 'jml') || str_contains($norm, 'test')) {
                    $colMap['jml_test'] = $idx;
                } elseif (str_contains($norm, 'pemeriksaan')) {
                    $colMap['pemeriksaan'] = $idx;
                } elseif (str_contains($norm, 'nip')) {
                    $colMap['nip'] = $idx;
                } elseif (str_contains($norm, 'jabatan')) {
                    $colMap['jabatan'] = $idx;
                } elseif (str_contains($norm, 'location') || str_contains($norm, 'lokasi')) {
                    $colMap['location'] = $idx;
                }
            }

            $startIndex = $hasHeader ? 1 : 0;
            $updatedCount = 0;
            $notFoundCount = 0;
            $skippedCount  = 0;
            $details       = [];

            DB::transaction(function () use (
                $rows,
                $startIndex,
                $colMap,
                &$dokterCache,
                &$marketingCache,
                &$kelompokCache,
                &$updatedCount,
                &$notFoundCount,
                &$skippedCount,
                &$details
            ) {
                for ($i = $startIndex; $i < count($rows); $i++) {
                    $row = $rows[$i] ?? [];
                    $rowNum = $i + 1;

                    $pid      = strtoupper(trim((string) ($row[$colMap['pid']] ?? '')));
                    $rawDate  = trim((string) ($row[$colMap['tanggal']] ?? ''));

                    if ($pid === '' && $rawDate === '') {
                        $skippedCount++;
                        continue;
                    }

                    if ($pid === '' || $rawDate === '') {
                        $notFoundCount++;
                        $details[] = "Baris {$rowNum}: PID atau Tanggal Kunjungan kosong.";
                        continue;
                    }

                    $parsedDate = $this->parseDate($rawDate);
                    if (!$parsedDate) {
                        $notFoundCount++;
                        $details[] = "Baris {$rowNum}: Format tanggal '{$rawDate}' untuk PID '{$pid}' tidak valid.";
                        continue;
                    }

                    // 1. Cari pelanggan berdasarkan PID
                    $pelanggan = Pelanggan::where('pid', $pid)->first();
                    if (!$pelanggan) {
                        $notFoundCount++;
                        $details[] = "Baris {$rowNum}: Pelanggan dengan PID '{$pid}' tidak ditemukan.";
                        continue;
                    }

                    // 2. Cari kunjungan pada tanggal tersebut
                    $kunjungans = Kunjungan::where('pelanggan_id', $pelanggan->id)
                        ->whereDate('tanggal_kunjungan', $parsedDate)
                        ->get();

                    if ($kunjungans->isEmpty()) {
                        $notFoundCount++;
                        $details[] = "Baris {$rowNum}: Kunjungan tanggal '{$parsedDate}' untuk PID '{$pid}' ({$pelanggan->nama}) tidak ditemukan.";
                        continue;
                    }

                    // 3. Update data Pelanggan jika kolom terisi
                    $jk        = trim((string) ($row[$colMap['jk']] ?? ''));
                    $kecamatan = trim((string) ($row[$colMap['kecamatan']] ?? ''));
                    $nip       = trim((string) ($row[$colMap['nip']] ?? ''));
                    $jabatan   = trim((string) ($row[$colMap['jabatan']] ?? ''));
                    $location  = trim((string) ($row[$colMap['location']] ?? ''));

                    $pelangganDirty = false;
                    if ($jk !== '') {
                        $pelanggan->jenis_kelamin = $jk;
                        $pelangganDirty = true;
                    }
                    if ($kecamatan !== '') {
                        $pelanggan->kecamatan = $kecamatan;
                        $pelangganDirty = true;
                    }
                    if ($nip !== '') {
                        $pelanggan->nip = $nip;
                        $pelangganDirty = true;
                    }
                    if ($jabatan !== '') {
                        $pelanggan->jabatan = $jabatan;
                        $pelangganDirty = true;
                    }
                    if ($location !== '') {
                        $pelanggan->location = $location;
                        $pelangganDirty = true;
                    }

                    if ($pelangganDirty) {
                        $pelanggan->save();
                    }

                    // 4. Persiapkan data kunjungan
                    $tipeOmset   = trim((string) ($row[$colMap['tipe_omset']] ?? ''));
                    $kodeKel     = trim((string) ($row[$colMap['kode_kelompok']] ?? ''));
                    $kodeDokter  = trim((string) ($row[$colMap['kode_dokter']] ?? ''));
                    $namaDokter  = trim((string) ($row[$colMap['dokter']] ?? ''));
                    $mou         = trim((string) ($row[$colMap['mou']] ?? ''));
                    $marketing   = trim((string) ($row[$colMap['marketing']] ?? ''));
                    $brutoRaw    = trim((string) ($row[$colMap['bruto']] ?? ''));
                    $discRaw     = trim((string) ($row[$colMap['disc']] ?? ''));
                    $payRaw      = trim((string) ($row[$colMap['pay']] ?? ''));
                    $jmlTestRaw  = trim((string) ($row[$colMap['jml_test']] ?? ''));
                    $pemeriksaan = trim((string) ($row[$colMap['pemeriksaan']] ?? ''));

                    // Parse numeric
                    $brutoValue = $this->parseBiaya($brutoRaw);
                    $discValue  = $this->parseBiaya($discRaw);
                    $payValue   = $this->parseBiaya($payRaw);
                    $jmlTest    = (is_numeric($jmlTestRaw)) ? (int) $jmlTestRaw : null;

                    // Kelompok Pelanggan Resolution
                    $kelompokId = null;
                    if ($kodeKel !== '') {
                        if (isset($kelompokCache[$kodeKel])) {
                            $kelompokId = $kelompokCache[$kodeKel];
                        } else {
                            $kp = KelompokPelanggan::where('kode', $kodeKel)->first();
                            if (!$kp) {
                                $kp = KelompokPelanggan::whereRaw('LOWER(kode) = ?', [strtolower($kodeKel)])->first();
                            }
                            if ($kp) {
                                $kelompokId = $kp->id;
                                $kelompokCache[$kodeKel] = $kelompokId;
                            }
                        }
                    }

                    // Dokter Resolution
                    $dokterId = null;
                    if ($kodeDokter !== '' || $namaDokter !== '') {
                        $docKey = ($kodeDokter ?? '') . '|' . ($namaDokter ?? '');
                        if (isset($dokterCache[$docKey])) {
                            $dokterId = $dokterCache[$docKey];
                        } else {
                            $doc = null;
                            if ($kodeDokter !== '') {
                                $doc = Dokter::where('kode_dokter', $kodeDokter)->first();
                            }
                            if (!$doc && $namaDokter !== '') {
                                $doc = Dokter::where('nama_dokter', $namaDokter)->first();
                            }
                            if ($doc) {
                                if ($kodeDokter !== '' && empty($doc->kode_dokter)) {
                                    $doc->kode_dokter = $kodeDokter;
                                    $doc->save();
                                }
                            } else {
                                $doc = Dokter::create([
                                    'kode_dokter' => $kodeDokter !== '' ? $kodeDokter : null,
                                    'nama_dokter' => $namaDokter !== '' ? $namaDokter : ($kodeDokter !== '' ? $kodeDokter : 'Dokter'),
                                ]);
                            }
                            $dokterId = $doc->id;
                            $dokterCache[$docKey] = $dokterId;
                        }
                    }

                    // Marketing Resolution
                    $marketingId = null;
                    if ($marketing !== '') {
                        if (isset($marketingCache[$marketing])) {
                            $marketingId = $marketingCache[$marketing];
                        } else {
                            $mkt = Marketing::firstOrCreate(['nama_marketing' => $marketing]);
                            $marketingId = $mkt->id;
                            $marketingCache[$marketing] = $marketingId;
                        }
                    }

                    // 5. Update setiap kunjungan yang cocok
                    foreach ($kunjungans as $kunjungan) {
                        $kunjunganDirty = false;

                        if ($tipeOmset !== '') {
                            $kunjungan->tipe_omset = $tipeOmset;
                            $kunjunganDirty = true;
                        }
                        if ($kelompokId !== null) {
                            $kunjungan->kelompok_pelanggan_id = $kelompokId;
                            $kunjunganDirty = true;
                        }
                        if ($dokterId !== null) {
                            $kunjungan->dokter_id = $dokterId;
                            $kunjunganDirty = true;
                        }
                        if ($mou !== '') {
                            $kunjungan->mou = $mou;
                            $kunjunganDirty = true;
                        }
                        if ($marketingId !== null) {
                            $kunjungan->marketing_id = $marketingId;
                            $kunjunganDirty = true;
                        }
                        if ($brutoValue !== null) {
                            $kunjungan->bruto = $brutoValue;
                            $kunjunganDirty = true;
                        }
                        if ($discValue !== null) {
                            $kunjungan->disc = $discValue;
                            $kunjunganDirty = true;
                        }
                        if ($payValue !== null) {
                            $kunjungan->pay = $payValue;
                            // Jika kolom biaya awal masih 0 / null, perbarui juga nilai biaya
                            if ($kunjungan->biaya === null || (float)$kunjungan->biaya == 0.0) {
                                $kunjungan->biaya = $payValue;
                            }
                            $kunjunganDirty = true;
                        }
                        if ($jmlTest !== null) {
                            $kunjungan->jml_test = $jmlTest;
                            $kunjunganDirty = true;
                        }
                        if ($pemeriksaan !== '') {
                            $kunjungan->pemeriksaan = $pemeriksaan;
                            $kunjunganDirty = true;
                        }

                        if ($kunjunganDirty) {
                            $kunjungan->save();
                            $updatedCount++;
                        }
                    }
                }
            });

            // Catat log aktivitas
            ActivityLog::record(
                'update',
                'Kunjungan',
                "IT melakukan Update Kolom Terbaru via Excel: {$updatedCount} record kunjungan berhasil diupdate, {$notFoundCount} data tidak ditemukan.",
                Auth::id(),
                Auth::user()->username ?? 'unknown',
                Auth::user()->role?->name ?? 'IT',
                $request->ip(),
                $request->userAgent()
            );

            $message = "Proses selesai: {$updatedCount} data kunjungan berhasil diperbarui.";
            if ($notFoundCount > 0) {
                $message .= " ({$notFoundCount} baris tidak ditemukan/gagal dicocokkan).";
            }

            return back()->with('success', $message)->with('import_details', array_slice($details, 0, 50));

        } catch (\Exception $e) {
            Log::error('Update Kolom Terbaru Import Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat memproses file: ' . $e->getMessage());
        }
    }

    /**
     * Helper untuk parse berbagai variasi format tanggal Excel
     */
    private function parseDate($dateValue): ?string
    {
        if (empty($dateValue)) {
            return null;
        }

        if (is_numeric($dateValue)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $dateValue))->format('Y-m-d');
            } catch (\Exception $e) {
                // Lanjut coba cara lain
            }
        }

        $clean = trim((string) $dateValue);

        $formats = [
            'Y-m-d',
            'd-m-Y',
            'd/m/Y',
            'Y/m/d',
            'm/d/Y',
            'd.m.Y',
            'Y.m.d',
        ];

        foreach ($formats as $format) {
            try {
                $d = Carbon::createFromFormat($format, $clean);
                if ($d && $d->format($format) === $clean) {
                    return $d->format('Y-m-d');
                }
            } catch (\Exception $e) {
                // Coba format berikutnya
            }
        }

        try {
            return Carbon::parse($clean)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse nilai nominal / biaya dari berbagai format input
     */
    private function parseBiaya($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        try {
            $cleanValue = (string) $value;
            $cleanValue = str_replace(['Rp', 'rp', 'RP', ' '], '', $cleanValue);

            if (strpos($cleanValue, ',') !== false && strpos($cleanValue, '.') !== false) {
                $cleanValue = str_replace('.', '', $cleanValue);
                $cleanValue = str_replace(',', '.', $cleanValue);
            } elseif (strpos($cleanValue, ',') !== false) {
                $parts = explode(',', $cleanValue);
                if (count($parts) === 2 && strlen($parts[1]) <= 2) {
                    $cleanValue = str_replace(',', '.', $cleanValue);
                } else {
                    $cleanValue = str_replace(',', '', $cleanValue);
                }
            } elseif (strpos($cleanValue, '.') !== false) {
                $parts = explode('.', $cleanValue);
                if (count($parts) === 2 && strlen($parts[1]) === 3) {
                    $cleanValue = str_replace('.', '', $cleanValue);
                }
            }

            return is_numeric($cleanValue) ? (float) $cleanValue : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
