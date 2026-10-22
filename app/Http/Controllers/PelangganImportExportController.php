<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

use App\Models\Pelanggan;
use App\Models\Kunjungan;
use App\Models\Cabang;
use App\Models\KelompokPelanggan;
use App\Models\Dokter;
use App\Models\Marketing;
use App\Models\ImportBatch;
use App\Models\ImportBatchPelangganSnapshot;
use App\Imports\KunjunganImport;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller untuk mengelola import dan export data pelanggan
 * Menangani: import Excel/CSV, export Excel, download template, bulk export, progress import
 */
class PelangganImportExportController extends Controller
{
    /**
     * Mengimport data pelanggan dari file Excel/CSV
     * Cara kerja:
     * 1. Validasi file (harus xlsx, xls, csv, atau txt)
     * 2. Baca file sesuai extension (CSV pakai readCsvFile, Excel pakai Excel::toArray)
     * 3. Validasi setiap baris: cek kolom cukup, PID valid, nama tidak mismatch dengan DB
     * 4. Jika ada error → return error tanpa import apa pun
     * 5. Jika valid → panggil processCsvImport atau KunjunganImport untuk proses data
     * 6. Return pesan sukses dengan jumlah data yang diproses
     */
    public function import(Request $request)
    {
        // Catat user yang melakukan import untuk keperluan log
        $userId = Auth::check() ? Auth::user()->id : 'guest';
        Log::info('Import process started', ['user' => $userId]);

        // Deteksi apakah request dari AJAX (fetch) atau form biasa
        $isAjax = $request->ajax() || $request->wantsJson();

        // ── Validasi akses cabang user ──────────────────────────────────────
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        $accessibleCabangIds = $authUser->getAccessibleCabangIds();

        $selectedCabangId = $request->input('import_cabang_id');
        if (!$selectedCabangId) {
            $msg = 'Pilih cabang terlebih dahulu sebelum melakukan import.';
            if ($isAjax) return response()->json(['success' => false, 'message' => $msg], 422);
            return back()->with('error', $msg);
        }

        if (!empty($accessibleCabangIds) && !in_array((int)$selectedCabangId, $accessibleCabangIds)) {
            $msg = 'Anda tidak memiliki akses ke cabang yang dipilih.';
            if ($isAjax) return response()->json(['success' => false, 'message' => $msg], 403);
            return back()->with('error', $msg);
        }

        $selectedCabang = Cabang::find($selectedCabangId);
        if (!$selectedCabang) {
            $msg = 'Cabang yang dipilih tidak ditemukan.';
            if ($isAjax) return response()->json(['success' => false, 'message' => $msg], 422);
            return back()->with('error', $msg);
        }
        // ───────────────────────────────────────────────────────────────────

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt'
        ], [
            'file.required' => 'File Excel/CSV wajib diupload.',
            'file.file' => 'File harus berupa file.',
            'file.mimes' => 'File harus berupa format Excel (xlsx, xls) atau CSV.'
        ]);

        try {
            Cache::put("import_progress_{$userId}", 0, now()->addMinutes(30));

            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension() ?? '');
            $originalFilename = $file->getClientOriginalName() ?? 'unknown';
            Log::info('File uploaded', ['filename' => $originalFilename, 'size' => $file->getSize(), 'extension' => $extension]);

            // ── Validasi Idempotensi: Cegah file dengan nama yang sama persis terimport dua kali ──
            $existingBatch = ImportBatch::with('user')
                ->where('cabang_id', $selectedCabang->id)
                ->whereRaw('LOWER(filename) = ?', [strtolower(trim($originalFilename))])
                ->where('status', 'completed')
                ->latest('imported_at')
                ->first();

            if ($existingBatch) {
                $importedAt = $existingBatch->imported_at ? $existingBatch->imported_at->format('d/m/Y H:i') : '-';
                $uploaderName = $existingBatch->user ? $existingBatch->user->name : 'Sistem';
                $msg = "File dengan nama '{$originalFilename}' sudah pernah diimport pada {$importedAt} (oleh {$uploaderName}) di cabang {$selectedCabang->nama}. "
                     . "Untuk mencegah duplikasi data, file yang sama tidak dapat diimport ulang. "
                     . "Silakan periksa kembali file Anda atau ganti nama file jika data memang berbeda.";
                Log::warning('Import rejected: duplicate filename detected', [
                    'filename'  => $originalFilename,
                    'cabang_id' => $selectedCabang->id,
                    'batch_id'  => $existingBatch->batch_id,
                ]);
                if ($isAjax) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->with('error', $msg);
            }

            if ($extension === 'csv' || $extension === 'txt') {
                $rows = $this->readCsvFile($file);
            } else {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file->getPathname());
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($file->getPathname());
                $sheet = $spreadsheet->getActiveSheet();
                $rows = [$sheet->toArray(null, true, false, false)];
            }

            $errors = [];
            $rowNumber = 0;
            $totalRows = 0;
            $validRows = 0;
            $mismatchRows = [];

            if (empty($rows) || empty($rows[0])) {
                Log::warning('Empty or invalid file');
                if ($isAjax) {
                    return response()->json(['success' => false, 'message' => 'File kosong atau tidak valid.'], 422);
                }
                return back()->with('error', 'File kosong atau tidak valid.');
            }

            Log::info('Excel file read successfully', ['sheet_count' => count($rows), 'first_sheet_rows' => count($rows[0])]);

            // ── Validasi format file: tolak jika format Pelanggan Khusus (kolom "Kategori Khusus") ──
            $isKhususFormat = false;
            $firstSheet     = $rows[0] ?? [];

            // Cek header row (baris pertama) untuk kolom yang mengandung kata 'kategori'
            $headerRow = $firstSheet[0] ?? [];
            foreach ($headerRow as $colHeader) {
                $h = strtolower(trim((string) $colHeader));
                if (!empty($h) && str_contains($h, 'kategori')) {
                    $isKhususFormat = true;
                    Log::info('Detected khusus format from header', ['col_header' => $h]);
                    break;
                }
            }

            // Jika header tidak eksplisit, cek nilai baris data apakah ada nilai kategori khusus
            if (!$isKhususFormat && count($firstSheet) > 1) {
                // Hindari cek jika ini adalah format omset (punya NOREG / KODE DOKTER / TIPE OMSET)
                $hasNewFormatHeader = false;
                foreach ($headerRow as $colHeader) {
                    $ch = strtoupper(trim((string) $colHeader));
                    if (in_array($ch, ['NOREG', 'KODE DOKTER', 'TIPE OMSET', 'BRUTO'], true)) {
                        $hasNewFormatHeader = true;
                        break;
                    }
                }

                if (!$hasNewFormatHeader) {
                    $knownKategori = ['kepala dinas', 'pic perusahaan', 'tokoh masyarakat', 'dokter', 'lainnya'];
                    foreach ($firstSheet as $rowIdx => $row) {
                        if ($rowIdx === 0) continue; // skip header
                        $valCol13 = strtolower(trim((string) ($row[12] ?? '')));
                        if (in_array($valCol13, $knownKategori, true)) {
                            $isKhususFormat = true;
                            Log::info('Detected khusus format from data row value', ['row' => $rowIdx + 1, 'val' => $valCol13]);
                            break;
                        }
                    }
                }
            }

            if ($isKhususFormat) {
                $msg = 'Format file ini adalah format Pelanggan Khusus (memiliki kolom "Kategori Khusus"). '
                     . 'Import Pelanggan Khusus tidak diperbolehkan di menu Data Pelanggan. '
                     . 'Gunakan menu Pelanggan Khusus untuk mengimport data ini.';
                Log::warning('Import rejected: khusus format file uploaded to regular import', ['user' => $userId]);
                if ($isAjax) return response()->json(['success' => false, 'message' => $msg], 422);
                return back()->with('error', $msg);
            }
            // ─────────────────────────────────────────────────────────────────────────

            $cabangs   = Cabang::all()->keyBy('kode');
            $headerMap = $this->buildHeaderMap($headerRow);
            $pidColIdx  = $headerMap['pid'] ?? 7;
            $namaColIdx = $headerMap['nama'] ?? 1;

            // Kumpulkan semua PID unik di file untuk batch query (optimasi: ribuan query menjadi 1 query)
            $allPidsInFile = [];
            foreach ($rows[0] as $rIdx => $r) {
                if ($rIdx === 0 && count($r) > 0) {
                    $firstVal = strtolower(trim((string) ($r[0] ?? '')));
                    $pidVal   = strtolower(trim((string) ($r[$pidColIdx] ?? '')));
                    if ($firstVal === 'no' || $pidVal === 'noreg' || $pidVal === 'pid') continue;
                }
                $p = strtoupper(trim((string) ($r[$pidColIdx] ?? '')));
                if (!empty($p)) {
                    $allPidsInFile[] = $p;
                }
            }
            $existingPelanggans = empty($allPidsInFile)
                ? collect()
                : Pelanggan::whereIn('pid', array_unique($allPidsInFile))->get()->keyBy('pid');

            // Array untuk tracking PID dan Nama dalam satu file (validasi nama berbeda)
            $pidNamaMap = [];
            // Array untuk tracking signature data lengkap per PID (validasi baris duplikat identik)
            $pidRowSignatures = [];

            foreach ($rows[0] as $row) {
                $rowNumber++;

                if ($rowNumber == 1 && count($row) > 0) {
                    $firstVal = strtolower(trim((string) ($row[0] ?? '')));
                    $pidVal   = strtolower(trim((string) ($row[$pidColIdx] ?? '')));
                    if ($firstVal === 'no' || $pidVal === 'noreg' || $pidVal === 'pid') {
                        continue;
                    }
                }

                if (count($row) < 3) {
                    Log::debug("Row $rowNumber skipped: insufficient columns", ['column_count' => count($row)]);
                    continue;
                }

                $no   = trim((string) ($row[$headerMap['no'] ?? 0] ?? ''));
                $nama = trim((string) ($row[$namaColIdx] ?? ''));
                $pid  = strtoupper(trim((string) ($row[$pidColIdx] ?? '')));

                $pelanggan = $existingPelanggans->get($pid);

                // Auto-fallback: jika nama di Excel kosong tapi PID terdaftar di DB, gunakan nama dari database
                if (empty($nama) && $pelanggan && !empty($pelanggan->nama)) {
                    $nama = $pelanggan->nama;
                }

                // Tracking PID dan Nama untuk validasi duplikat dalam satu file
                if (!empty($pid) && !empty($nama)) {
                    if (!isset($pidNamaMap[$pid])) {
                        // PID pertama kali ditemukan, simpan nama dan nomor baris
                        $pidNamaMap[$pid] = [
                            'nama' => $nama,
                            'baris' => $rowNumber
                        ];
                    } elseif (strtolower($pidNamaMap[$pid]['nama']) !== strtolower($nama)) {
                        // PID sudah ada dengan nama berbeda, catat konflik
                        if (!isset($pidNamaMap[$pid]['konflik'])) {
                            $pidNamaMap[$pid]['konflik'] = [];
                        }
                        $pidNamaMap[$pid]['konflik'][] = [
                            'nama' => $nama,
                            'baris' => $rowNumber
                        ];
                    }

                    // ── Validasi baris duplikat identik dalam satu file untuk PID yang sama ──
                    // Salin semua kolom baris kecuali kolom urutan nomor ('NO')
                    $dataCols = $row;
                    if (isset($headerMap['no']) && $headerMap['no'] !== null && array_key_exists($headerMap['no'], $dataCols)) {
                        unset($dataCols[$headerMap['no']]);
                    }
                    $normalizedCols = [];
                    foreach ($dataCols as $colKey => $cellVal) {
                        if ($cellVal === null) {
                            $normalizedCols[$colKey] = '';
                        } elseif (is_string($cellVal)) {
                            $normalizedCols[$colKey] = trim($cellVal);
                        } elseif (is_numeric($cellVal)) {
                            $normalizedCols[$colKey] = (string) (float) $cellVal;
                        } else {
                            $normalizedCols[$colKey] = (string) $cellVal;
                        }
                    }
                    $rowSignature = md5(json_encode($normalizedCols));
                    $pidRowSignatures[$pid][$rowSignature][] = [
                        'baris' => $rowNumber,
                        'nama'  => $nama,
                    ];
                }

                if (empty($pid) || empty($nama)) {
                    Log::debug("Row {$rowNumber} skipped: empty PID or nama");
                    continue;
                }

                $cabangKode = strtoupper(substr($pid, 0, 2));
                if (!isset($cabangs[$cabangKode])) {
                    $errors[] = "Baris {$rowNumber}: Kode cabang '{$cabangKode}' dalam PID '{$pid}' tidak valid.";
                    continue;
                }

                // Validasi PID prefix harus sesuai cabang yang dipilih
                if (strtoupper($cabangKode) !== strtoupper($selectedCabang->kode)) {
                    $errors[] = "Baris {$rowNumber}: PID '{$pid}' (prefix '{$cabangKode}') tidak sesuai dengan cabang yang dipilih '{$selectedCabang->nama}' (kode '{$selectedCabang->kode}').";
                    continue;
                }

                $totalRows++;

                if ($pelanggan) {
                    // Blokir import jika PID adalah pelanggan khusus
                    if ($pelanggan->is_pelanggan_khusus) {
                        $errors[] = "Baris {$rowNumber}: PID '{$pid}' adalah pelanggan khusus. Gunakan menu Pelanggan Khusus untuk import data ini.";
                        continue;
                    }

                    $dbNama = trim($pelanggan->nama ?? '');

                    if (strtolower($nama) !== strtolower($dbNama)) {
                        $errors[] = "Baris {$rowNumber}: PID {$pid} sudah terdaftar dengan nama '{$dbNama}'. Data Excel nama '{$nama}' tidak sesuai.";
                        $mismatchRows[] = [
                            'error_key'   => md5($rowNumber . '|' . $pid . '|' . $dbNama . '|' . $nama),
                            'row_number'  => $rowNumber,
                            'pid'         => $pid,
                            'db_nama'     => $dbNama,
                            'excel_nama'  => $nama,
                        ];
                    } else {
                        $validRows++;
                    }
                } else {
                    $validRows++;
                }
            }

            // Validasi duplikat PID dengan nama berbeda dalam satu file
            foreach ($pidNamaMap as $pid => $data) {
                if (isset($data['konflik']) && !empty($data['konflik'])) {
                    // Ada konflik nama untuk PID yang sama
                    $barisPertama = $data['baris'];
                    $namaPertama = $data['nama'];
                    
                    foreach ($data['konflik'] as $konflik) {
                        $errors[] = "Baris {$barisPertama} dan {$konflik['baris']}: PID {$pid} memiliki nama berbeda ('{$namaPertama}' dan '{$konflik['nama']}')";
                    }
                }
            }

            // Validasi baris duplikat identik untuk PID yang sama dalam satu file
            foreach ($pidRowSignatures as $pId => $signatures) {
                foreach ($signatures as $sig => $occurrences) {
                    if (count($occurrences) > 1) {
                        $barisList = array_column($occurrences, 'baris');
                        $pNama     = $occurrences[0]['nama'] ?? '';

                        if (count($barisList) === 2) {
                            $barisStr = "Baris {$barisList[0]} dan Baris {$barisList[1]}";
                        } else {
                            $lastBaris = array_pop($barisList);
                            $barisStr = "Baris " . implode(', ', $barisList) . ", dan Baris {$lastBaris}";
                        }

                        $errors[] = "{$barisStr}: Ditemukan baris duplikat untuk PID '{$pId}'"
                                  . ($pNama !== '' ? " (Nama: '{$pNama}')" : '')
                                  . " dengan seluruh isi kolom yang sama persis. Harap periksa kembali file Anda.";
                    }
                }
            }

            Log::info('Validation completed', ['total_rows' => $totalRows, 'valid_rows' => $validRows, 'errors' => count($errors)]);

            if (!empty($errors)) {
                Log::warning('Import failed due to validation errors', ['error_count' => count($errors)]);
                if ($isAjax) {
                    return response()->json([
                        'success'       => false,
                        'message'       => 'Import gagal! Beberapa data tidak sesuai atau ditemukan baris duplikat.',
                        'errors'        => $errors,
                        'mismatch_rows' => $mismatchRows,
                    ], 422);
                }
                return back()->with('error', 'Import gagal! Beberapa data tidak sesuai atau ditemukan baris duplikat.')
                             ->with('import_errors', $errors);
            }

            if ($validRows === 0) {
                Log::warning('No valid rows to import');
                if ($isAjax) {
                    return response()->json(['success' => false, 'message' => 'Tidak ada data valid untuk diimport.'], 422);
                }
                return back()->with('error', "Tidak ada data valid untuk diimport.");
            }

            Log::info('Starting import', ['valid_rows' => $validRows, 'file_type' => $extension]);

            // Generate UUID unik untuk sesi import ini (digunakan untuk rollback)
            $batchId = Str::uuid()->toString();

            // Gunakan processCsvImport untuk SEMUA format (CSV maupun Excel)
            // karena $rows[0] sudah tersedia dari readCsvFile() / Excel::toArray()
            // Ini memastikan snapshot dan import_batch_id selalu tersimpan
            $processedCount = $this->processCsvImport($rows[0], (int) $userId, $batchId);

            // Catat sesi import ke tabel import_batches untuk keperluan rollback
            ImportBatch::create([
                'batch_id'    => $batchId,
                'user_id'     => Auth::id(),
                'cabang_id'   => $selectedCabang->id,
                'filename'    => $file->getClientOriginalName() ?? 'unknown',
                'total_rows'  => $processedCount,
                'status'      => 'completed',
                'imported_at' => now(),
            ]);

            Cache::put("import_progress_{$userId}", 100, now()->addMinutes(30));

            $filename = $file->getClientOriginalName() ?? 'unknown';
            Log::info('Import completed successfully', ['filename' => $filename, 'batch_id' => $batchId]);

            $successMessage = "Import berhasil! File '$filename' dengan $validRows data telah diproses.";
            if ($isAjax) {
                return response()->json(['success' => true, 'message' => $successMessage]);
            }
            return back()->with('success', $successMessage);

        } catch (\Exception $e) {
            Cache::put("import_progress_{$userId}", 100, now()->addMinutes(5));

            Log::error('Import exception', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);

            $errorMessage = 'Terjadi kesalahan saat import: ' . $e->getMessage();
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => $errorMessage], 500);
            }
            return back()->with('error', $errorMessage);
        }
    }

    /**
     * Export data pelanggan ke Excel berdasarkan filter yang aktif
     * Filename dibuat dinamis sesuai filter (contoh: pelanggan_01_2024_Potensial.xlsx)
     */
    public function export(Request $request)
    {
        // Gunakan ?: agar empty string juga di-fallback (bukan hanya null)
        $bulan           = $request->bulan   ?: date('m');
        $tahun           = $request->tahun   ?: date('Y');
        $type            = $request->type    ?: 'semua';
        $search          = $request->search;
        $cabangId        = $request->cabang_id;
        $omsetRange      = $request->omset_range;
        $kedatanganRange = $request->kedatangan_range;
        $kelas           = $request->kelas;
        $tipePelanggan   = $request->tipe_pelanggan;
        $tanggalMulai    = $request->tanggal_mulai;
        $tanggalSelesai  = $request->tanggal_selesai;
        $pemeriksaan     = $request->pemeriksaan;
        $jenisKelamin    = $request->jenis_kelamin;

        // Build filename based on filters
        $filename = 'pelanggan';
        if ($search) {
            $filename .= '_search_' . preg_replace('/[^a-zA-Z0-9]/', '_', $search);
        } else {
            if ($type == 'perbulan') {
                $filename .= '_' . $bulan . '_' . $tahun;
            } elseif ($type == 'pertahun') {
                $filename .= '_tahun_' . $tahun;
            } elseif ($type == 'range' && $tanggalMulai && $tanggalSelesai) {
                $filename .= '_' . str_replace('-', '', $tanggalMulai) . '_sd_' . str_replace('-', '', $tanggalSelesai);
            } else {
                $filename .= '_semua';
            }
        }
        if ($kelas) {
            $filename .= '_' . $kelas;
        }
        if ($tipePelanggan) {
            $filename .= '_' . $tipePelanggan;
        }
        if ($jenisKelamin) {
            $filename .= '_jk_' . $jenisKelamin;
        }
        if ($cabangId) {
            $cabang    = Cabang::find($cabangId);
            $filename .= '_' . ($cabang ? preg_replace('/[^a-zA-Z0-9]/', '_', $cabang->nama) : 'cabang');
        }
        if ($pemeriksaan) {
            $filename .= '_pemeriksaan_' . preg_replace('/[^a-zA-Z0-9]/', '_', substr($pemeriksaan, 0, 20));
        }
        $filename .= '.xlsx';

        return Excel::download(
            new \App\Exports\PelangganExport(
                $bulan,
                $tahun,
                $type,
                $search,
                $cabangId,
                $omsetRange,
                $kedatanganRange,
                $kelas,
                $tipePelanggan,
                $tanggalMulai,
                $tanggalSelesai,
                $pemeriksaan,
                $jenisKelamin
            ),
            $filename
        );
    }

    /**
     * Download template Excel untuk import pelanggan
     * Template berisi header dan 5 baris contoh data
     * Memudahkan user mengisi data dengan format yang benar
     */
    public function downloadTemplate()
    {
        // Header kolom sesuai format import Excel baru (27 kolom, tanpa kolom Total Kedatangan)
        $headers = [
            'NO',
            'TANGGAL',
            'NOREG',
            'PASIEN',
            'JK',
            'HP',
            'ALAMAT',
            'KOTA',
            'KECAMATAN',
            'TIPE OMSET',
            'DOB',
            'KODE KEL.PELANGGAN',
            'KEL.PELANGGAN',
            'KODE DOKTER',
            'DOKTER',
            'MOU',
            'MARKETING',
            'BRUTO',
            'DISC',
            'TOTAL',
            'PAY',
            'JML TEST',
            'PEMERIKSAAN',
            'NIP',
            'NIK',
            'JABATAN',
            'LOCATION',
        ];

        // Data dummy sebagai contoh sesuai format Excel baru
        $data = [
            [
                1,
                '2026-10-06',
                'LXG0045028',
                'An. (Pr). AULIA AZ ZAHRA',
                'P',
                '082245326855',
                'JL PLAGSAN BARAT NO 58',
                'Malang',
                'Blimbing',
                'Rujukan',
                '2009-12-21',
                '3411-2100614',
                'MUTIARA BUNDA, RSB',
                '3411210577',
                'LUCIA FLORA WATI',
                'RUJUKAN 2023',
                'GILANG MAHARDIKA',
                192000,
                0,
                192000,
                192000,
                3,
                'Thorax PA,Cetak Film Sedang 26 x 36 (Thorax PA), Jasa Ekspertisi Thorax PA',
                '',
                '3573016112090002',
                '',
                '',
            ],
            [
                2,
                '2026-10-06',
                'LXG0045092',
                'An. (Pr). DIANDRA BAFANA ANSORI',
                'Perempuan',
                '08179611574',
                'PBI 01 NO 4',
                'Malang',
                'Blimbing',
                'Dokter',
                '2012-07-16',
                'PRAMITA00013',
                'PASIEN KLINISI',
                '3411260416',
                'NANDITYA IKA PARAMITA',
                'PASIEN UMUM 2026',
                'Septian Nugroho',
                839000,
                0,
                839000,
                839000,
                2,
                'Thorax PA/Lat,,Jasa Ekspertisi Thorax PA/Lat',
                '',
                '',
                '',
                '',
            ],
            [
                3,
                '2026-10-06',
                'LXG0045441',
                'An. (Lk). MUHAMMAD ARRASYA ATHARRAZKA',
                'L',
                '089628982211',
                'JL PANDOWO 204',
                'Malang',
                'Lawang',
                'Dokter',
                '2023-01-05',
                'PRAMITA00013',
                'PASIEN KLINISI',
                '3411260416',
                'NANDITYA IKA PARAMITA',
                'PASIEN UMUM 2026',
                'Septian Nugroho',
                350000,
                0,
                350000,
                350000,
                1,
                'Hematologi Lengkap',
                '',
                '',
                '',
                '',
            ],
        ];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        // Set header
        foreach ($headers as $col => $header) {
            $sheet->setCellValue(
                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . '1',
                $header
            );
        }

        // Kolom text yang wajib disimpan sebagai explicit string agar tidak terformat scientific (1.23E+15)
        // 2: NOREG, 4: JK, 5: HP, 11: KODE KEL.PELANGGAN, 13: KODE DOKTER, 23: NIP, 24: NIK
        $textCols = [2, 4, 5, 11, 13, 23, 24];

        // Set data
        foreach ($data as $row => $rowData) {
            foreach ($rowData as $col => $value) {
                $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . ($row + 2);
                if (in_array($col, $textCols, true) && $value !== '' && $value !== null) {
                    $sheet->setCellValueExplicit($cellCoord, (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($cellCoord, $value);
                }
            }
        }

        // Auto resize columns
        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimension(
                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col)
            )->setAutoSize(true);
        }

        // Style header
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2EFDA']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                ]
            ]
        ];
        $lastHeaderCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$lastHeaderCol}1")->applyFromArray($headerStyle);

        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'template_import_pelanggan.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'template_');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Export multiple pelanggan terpilih ke Excel
     * Menerima array ID dari checkbox, export hanya pelanggan yang dipilih
     */
    public function bulkExport(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->back()->with('error', 'Tidak ada pelanggan yang dipilih untuk diexport.');
        }

        // Pastikan semua ID valid (integer)
        $ids = array_filter(array_map('intval', $ids));

        if (empty($ids)) {
            return redirect()->back()->with('error', 'ID pelanggan tidak valid.');
        }

        $filename = 'pelanggan_terpilih_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(new \App\Exports\PelangganBulkExport($ids), $filename);
    }

    /**
     * Mengembalikan progress import saat ini (untuk polling AJAX)
     */
    public function importProgress(Request $request)
    {
        $userId   = Auth::id() ?? 0;
        $progress = Cache::get("import_progress_{$userId}", 0);
        $total    = Cache::get("import_total_{$userId}", 0);
        $current  = Cache::get("import_current_{$userId}", 0);

        return response()->json([
            'percent' => (int) $progress,
            'current' => (int) $current,
            'total'   => (int) $total,
            'status'  => $progress >= 100 ? 'done' : 'processing',
        ]);
    }

    /**
     * Sesuaikan nama pelanggan di database berdasarkan pilihan mismatch per baris.
     * Dipanggil dari UI setelah import gagal karena mismatch nama.
     */
    public function syncMismatchNames(Request $request)
    {
        $isAjax = $request->ajax() || $request->wantsJson();
    
        $validated = $request->validate([
            'selected_mismatches'              => 'required|array|min:1',
            'selected_mismatches.*.pid'        => 'required|string',
            'selected_mismatches.*.excel_nama' => 'required|string',
            'selected_mismatches.*.row_number' => 'nullable|integer',
            'selected_mismatches.*.error_key'  => 'nullable|string',
            'original_filename'                => 'nullable|string|max:255',
        ], [
            'selected_mismatches.required' => 'Tidak ada data mismatch yang dipilih.',
            'selected_mismatches.array'    => 'Format data mismatch tidak valid.',
            'selected_mismatches.min'      => 'Pilih minimal satu data mismatch untuk disesuaikan.',
        ]);

        $selected = collect($validated['selected_mismatches'] ?? []);
        $updatedCount = 0;

        DB::transaction(function () use ($selected, &$updatedCount) {
            // Update per PID unik (jika satu PID muncul di banyak baris, update sekali saja ke nama terakhir dipilih)
            $byPid = $selected->groupBy('pid');

            foreach ($byPid as $pid => $items) {
                $targetName = trim((string) ($items->last()['excel_nama'] ?? ''));
                if ($targetName === '') {
                    continue;
                }

                $pelanggan = Pelanggan::where('pid', $pid)->first();
                if (!$pelanggan || $pelanggan->is_pelanggan_khusus) {
                    continue;
                }

                if (trim((string)$pelanggan->nama) !== $targetName) {
                    $pelanggan->nama = $targetName;
                    $pelanggan->save();
                    $updatedCount++;
                }
            }
        });

        // Sisipkan nama file ke notifikasi jika tersedia
        $originalFilename = trim((string) ($validated['original_filename'] ?? ''));
        $fileInfo = $originalFilename !== '' ? " File {$originalFilename}" : '';
        $message  = "Data Pelanggans Berhasil Disesuaikan, Silahkan Import Ulang{$fileInfo}.";

        if ($isAjax) {
            return response()->json([
                'success'       => true,
                'message'       => $message,
                'updated_count' => $updatedCount,
            ]);
        }

        return back()->with('success', $message);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Memproses import data dari file CSV/Excel
     *
     * DUPLICATE HANDLING (Penanganan Data Duplikat):
     * Jika dalam 1 file ada multiple baris dengan PID yang sama:
     * - Total Kedatangan: dijumlahkan dari semua baris (contoh: 2 + 3 = 5)
     * - Total Biaya: dijumlahkan dari semua baris (contoh: 500000 + 700000 = 1200000)
     * - Tanggal Kedatangan: diambil yang paling terbaru
     * - No: diambil dari baris yang tanggalnya terbaru
     *
     * Cara kerja:
     * 1. STEP 1 - AGGREGATE: Loop semua baris, kelompokkan by PID, jumlahkan data
     * 2. STEP 2 - PROCESS: Loop data yang sudah di-aggregate, simpan ke database
     * 3. Setiap pelanggan baru dicatat riwayat kelas awalnya
     */
    /**
     * Membangun peta indeks kolom berdasarkan nama header baris pertama
     * Mendukung format Excel baru maupun format lama (backward compatible)
     */
    private function buildHeaderMap(array $headerRow): array
    {
        $normalized = [];
        foreach ($headerRow as $idx => $colName) {
            $cleaned = strtoupper(trim((string) $colName));
            $cleaned = preg_replace('/\s+/', ' ', $cleaned);
            if ($cleaned !== '') {
                $normalized[$cleaned] = $idx;
            }
        }

        // Helper untuk mencari index kolom pertama yang cocok dari daftar alias
        $findCol = function (array $aliases) use ($normalized): ?int {
            foreach ($aliases as $alias) {
                $aliasUpper = strtoupper($alias);
                if (isset($normalized[$aliasUpper])) {
                    return $normalized[$aliasUpper];
                }
            }
            // Partial match fallback
            foreach ($aliases as $alias) {
                $aliasUpper = strtoupper($alias);
                foreach ($normalized as $header => $idx) {
                    if (str_contains($header, $aliasUpper)) {
                        return $idx;
                    }
                }
            }
            return null;
        };

        $map = [
            'no'             => $findCol(['NO', 'NOMOR']),
            'tanggal'        => $findCol(['TANGGAL KEDATANGAN TERAKHIR', 'TANGGAL KEDATANGAN', 'TANGGAL']),
            'pid'            => $findCol(['NOREG', 'NO REG', 'NO. REG', 'NO.REG', 'PID']),
            'nama'           => $findCol(['PASIEN', 'NAMA PASIEN', 'NAMA']),
            'jk'             => $findCol(['JK', 'JENIS KELAMIN', 'JENIS_KELAMIN', 'GENDER', 'SEX']),
            'hp'             => $findCol(['HP', 'NO TELPON', 'NO TELEPON', 'NO TELP', 'NO. TELP', 'NO. HP', 'TELEPON']),
            'alamat'         => $findCol(['ALAMAT']),
            'kota'           => $findCol(['KOTA']),
            'kecamatan'      => $findCol(['KECAMATAN']),
            'tipe_omset'     => $findCol(['TIPE OMSET', 'TIPE_OMSET', 'OMSET TIPE']),
            'dob'            => $findCol(['DOB', 'TGL LAHIR', 'TANGGAL LAHIR']),
            'kode_kelompok'  => $findCol(['KODE KEL.PELANGGAN', 'KODE KEL PELANGGAN', 'KODE KELOMPOK PELANGGAN', 'KODE KELOMPOK']),
            'kelompok'       => $findCol(['KEL.PELANGGAN', 'KEL PELANGGAN', 'KELOMPOK PELANGGAN (MANDIRI/KLINISI)', 'KELOMPOK PELANGGAN', 'KELOMPOK']),
            'kode_dokter'    => $findCol(['KODE DOKTER', 'KODE_DOKTER']),
            'dokter'         => $findCol(['DOKTER', 'NAMA DOKTER']),
            'mou'            => $findCol(['MOU/AGREEMENT', 'AGREEMENT', 'MOU']),
            'marketing'      => $findCol(['MARKETING', 'NAMA MARKETING']),
            'bruto'          => $findCol(['BRUTO']),
            'disc'           => $findCol(['DISC', 'DISKON', 'DISCOUNT']),
            'total'          => $findCol(['TOTAL (BIAYA)', 'TOTAL', 'BIAYA']),
            'pay'            => $findCol(['PAY', 'BAYAR']),
            'jml_test'       => $findCol(['JML TEST', 'JUMLAH TEST', 'JML_TEST', 'TEST']),
            'pemeriksaan'    => $findCol(['PEMERIKSAAN']),
            'nip'            => $findCol(['NIP']),
            'nik'            => $findCol(['NIK']),
            'jabatan'        => $findCol(['JABATAN']),
            'location'       => $findCol(['LOCATION', 'LOKASI']),
            'kedatangan'     => $findCol(['KEDATANGAN', 'TOTAL KEDATANGAN']),
        ];

        // Jika file tidak memiliki header sama sekali, gunakan fallback indeks legacy (14 kolom)
        if ($map['pid'] === null && $map['nama'] === null) {
            $map = [
                'no'             => 0,
                'nama'           => 1,
                'kedatangan'     => 2,
                'tanggal'        => 3,
                'total'          => 4,
                'hp'             => 5,
                'dob'            => 6,
                'pid'            => 7,
                'alamat'         => 8,
                'kota'           => 9,
                'kelompok'       => 10,
                'nik'            => 11,
                'pemeriksaan'    => 12,
                'mou'            => 13,
                'jk'             => null,
                'kecamatan'      => null,
                'tipe_omset'     => null,
                'kode_kelompok'  => null,
                'kode_dokter'    => null,
                'dokter'         => null,
                'marketing'      => null,
                'bruto'          => null,
                'disc'           => null,
                'pay'            => null,
                'jml_test'       => null,
                'nip'            => null,
                'jabatan'        => null,
                'location'       => null,
            ];
        }

        return $map;
    }

    /**
     * Memproses import data dari file CSV/Excel
     *
     * DUPLICATE HANDLING (Penanganan Data Duplikat):
     * Jika dalam 1 file ada multiple baris dengan PID yang sama:
     * - Total Kedatangan: dijumlahkan dari semua baris (contoh: 2 + 3 = 5)
     * - Total Biaya: dijumlahkan dari semua baris (contoh: 500000 + 700000 = 1200000)
     * - Tanggal Kedatangan: diambil yang paling terbaru
     * - No: diambil dari baris yang tanggalnya terbaru
     *
     * Cara kerja:
     * 1. STEP 1 - AGGREGATE: Loop semua baris, kelompokkan by PID, jumlahkan data
     * 2. STEP 2 - PROCESS: Loop data yang sudah di-aggregate, simpan ke database
     * 3. Setiap pelanggan baru dicatat riwayat kelas awalnya
     */
    private function processCsvImport(array $rows, int $importUserId = 0, string $batchId = ''): int
    {
        $processedCount   = 0;
        $duplicateSkipped = 0;
        $seenRows         = [];
        $totalDataRows    = count($rows);
        $currentRow       = 0;

        // Load semua cabang ke memory untuk lookup cepat (hindari query berulang)
        $cabangs = Cabang::all()->keyBy('kode');

        // Preload default kelompok pelanggan mandiri
        $defaultMandiriId = KelompokPelanggan::where('kode', 'mandiri')->value('id');

        // Memory cache untuk master data selama batch import
        $kelompokCache  = [];
        $dokterCache    = [];
        $marketingCache = [];

        // Build header map
        $headerRow = $rows[0] ?? [];
        $headerMap = $this->buildHeaderMap($headerRow);
        $pidColIdx = $headerMap['pid'] ?? 7;

        // Preload pelanggan di file untuk optimasi query
        $allPidsInFile = [];
        foreach ($rows as $rIdx => $r) {
            if ($rIdx === 0 && count($r) > 0) {
                $firstVal = strtolower(trim((string) ($r[0] ?? '')));
                $pidVal   = strtolower(trim((string) ($r[$pidColIdx] ?? '')));
                if ($firstVal === 'no' || $pidVal === 'noreg' || $pidVal === 'pid') continue;
            }
            $p = strtoupper(trim((string) ($r[$pidColIdx] ?? '')));
            if (!empty($p)) {
                $allPidsInFile[] = $p;
            }
        }
        $existingPelanggans = empty($allPidsInFile)
            ? collect()
            : Pelanggan::whereIn('pid', array_unique($allPidsInFile))->get()->keyBy('pid');

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $currentRow++;

            if ($index === 0 && count($row) > 0) {
                $firstVal = strtolower(trim((string) ($row[0] ?? '')));
                $pidVal   = strtolower(trim((string) ($row[$pidColIdx] ?? '')));
                if ($firstVal === 'no' || $pidVal === 'noreg' || $pidVal === 'pid') {
                    continue;
                }
            }

            if (count($row) < 3) {
                continue;
            }

            // Ambil data per kolom berdasarkan headerMap
            $no                = ($headerMap['no'] !== null && isset($row[$headerMap['no']])) ? (int) $row[$headerMap['no']] : null;
            $pid               = ($headerMap['pid'] !== null && isset($row[$headerMap['pid']])) ? strtoupper(trim((string) $row[$headerMap['pid']])) : '';
            $nama              = ($headerMap['nama'] !== null && isset($row[$headerMap['nama']])) ? trim((string) $row[$headerMap['nama']]) : '';
            $jkRaw             = ($headerMap['jk'] !== null && isset($row[$headerMap['jk']])) ? trim((string) $row[$headerMap['jk']]) : null;
            $jenisKelamin      = $this->normalizeJenisKelamin($jkRaw);
            $noTelp            = ($headerMap['hp'] !== null && isset($row[$headerMap['hp']])) ? trim((string) $row[$headerMap['hp']]) : '';
            $tanggalKedatangan = ($headerMap['tanggal'] !== null && isset($row[$headerMap['tanggal']])) ? $row[$headerMap['tanggal']] : null;
            $dob               = ($headerMap['dob'] !== null && isset($row[$headerMap['dob']])) ? $row[$headerMap['dob']] : null;
            $alamat            = ($headerMap['alamat'] !== null && isset($row[$headerMap['alamat']])) ? trim((string) $row[$headerMap['alamat']]) : '';
            $kota              = ($headerMap['kota'] !== null && isset($row[$headerMap['kota']])) ? trim((string) $row[$headerMap['kota']]) : '';
            $kecamatan         = ($headerMap['kecamatan'] !== null && isset($row[$headerMap['kecamatan']])) ? trim((string) $row[$headerMap['kecamatan']]) : null;
            $tipeOmset         = ($headerMap['tipe_omset'] !== null && isset($row[$headerMap['tipe_omset']])) ? trim((string) $row[$headerMap['tipe_omset']]) : null;
            $kodeKelompokRaw   = ($headerMap['kode_kelompok'] !== null && isset($row[$headerMap['kode_kelompok']])) ? trim((string) $row[$headerMap['kode_kelompok']]) : null;
            $kelompokRaw       = ($headerMap['kelompok'] !== null && isset($row[$headerMap['kelompok']])) ? trim((string) $row[$headerMap['kelompok']]) : '';
            $kodeDokterRaw     = ($headerMap['kode_dokter'] !== null && isset($row[$headerMap['kode_dokter']])) ? trim((string) $row[$headerMap['kode_dokter']]) : null;
            $dokterRaw         = ($headerMap['dokter'] !== null && isset($row[$headerMap['dokter']]) && trim((string) $row[$headerMap['dokter']]) !== '') ? trim((string) $row[$headerMap['dokter']]) : null;
            $mou               = ($headerMap['mou'] !== null && isset($row[$headerMap['mou']]) && trim((string) $row[$headerMap['mou']]) !== '') ? trim((string) $row[$headerMap['mou']]) : null;
            $marketingRaw      = ($headerMap['marketing'] !== null && isset($row[$headerMap['marketing']]) && trim((string) $row[$headerMap['marketing']]) !== '') ? trim((string) $row[$headerMap['marketing']]) : null;
            $brutoRaw          = ($headerMap['bruto'] !== null && isset($row[$headerMap['bruto']])) ? $row[$headerMap['bruto']] : null;
            $discRaw           = ($headerMap['disc'] !== null && isset($row[$headerMap['disc']])) ? $row[$headerMap['disc']] : null;
            $biayaRaw          = ($headerMap['total'] !== null && isset($row[$headerMap['total']])) ? $row[$headerMap['total']] : null;
            $payRaw            = ($headerMap['pay'] !== null && isset($row[$headerMap['pay']])) ? $row[$headerMap['pay']] : null;
            $jmlTestRaw        = ($headerMap['jml_test'] !== null && isset($row[$headerMap['jml_test']])) ? $row[$headerMap['jml_test']] : null;
            $pemeriksaan       = ($headerMap['pemeriksaan'] !== null && isset($row[$headerMap['pemeriksaan']]) && trim((string) $row[$headerMap['pemeriksaan']]) !== '') ? trim((string) $row[$headerMap['pemeriksaan']]) : null;
            $nipRaw            = ($headerMap['nip'] !== null && isset($row[$headerMap['nip']])) ? trim((string) $row[$headerMap['nip']]) : null;
            $nikRaw            = ($headerMap['nik'] !== null && isset($row[$headerMap['nik']])) ? trim((string) $row[$headerMap['nik']]) : null;
            $jabatan           = ($headerMap['jabatan'] !== null && isset($row[$headerMap['jabatan']]) && trim((string) $row[$headerMap['jabatan']]) !== '') ? trim((string) $row[$headerMap['jabatan']]) : null;
            $location          = ($headerMap['location'] !== null && isset($row[$headerMap['location']]) && trim((string) $row[$headerMap['location']]) !== '') ? trim((string) $row[$headerMap['location']]) : null;

            // Total Kedatangan: jika ada kolom KEDATANGAN dan > 0, gunakan nilainya; jika tidak, default 1
            $totalKedatangan = 1;
            if ($headerMap['kedatangan'] !== null && isset($row[$headerMap['kedatangan']]) && is_numeric($row[$headerMap['kedatangan']])) {
                $valK = (int) $row[$headerMap['kedatangan']];
                if ($valK > 0) {
                    $totalKedatangan = $valK;
                }
            }

            // Sanitasi NIK dan NIP
            $nik = $nikRaw;
            if (empty($nik) || strtolower($nik) === 'tidak ada identitas' || $nik === '-') {
                $nik = null;
            }
            $nip = $nipRaw;
            if (empty($nip) || strtolower($nip) === 'tidak ada identitas' || $nip === '-') {
                $nip = null;
            }

            // Auto-fallback: jika nama di Excel kosong tapi PID terdaftar di DB, gunakan nama dari database
            $pelangganExisting = $existingPelanggans->get($pid);
            if (empty($nama) && $pelangganExisting && !empty($pelangganExisting->nama)) {
                $nama = $pelangganExisting->nama;
            }

            if (empty($pid) || empty($nama)) {
                continue;
            }

            // Dedup strict by full row content
            $dedupKey = md5(json_encode($row));
            if (isset($seenRows[$dedupKey])) {
                $duplicateSkipped++;
                continue;
            }
            $seenRows[$dedupKey] = true;

            $cabangKode = strtoupper(substr($pid, 0, 2));
            if (!isset($cabangs[$cabangKode])) {
                continue;
            }

            $tanggal = $this->parseCsvDate($tanggalKedatangan);
            if (!$tanggal) {
                continue;
            }

            $dobDate    = $this->parseCsvDate($dob);
            $biayaValue = $this->parseCsvBiaya($biayaRaw);
            if ($biayaValue === null) {
                $biayaValue = $this->parseCsvBiaya($payRaw) ?? 0.0;
            }
            $brutoValue = $this->parseCsvBiaya($brutoRaw);
            $discValue  = $this->parseCsvBiaya($discRaw);
            $payValue   = $this->parseCsvBiaya($payRaw);
            $jmlTest    = (is_numeric($jmlTestRaw)) ? (int) $jmlTestRaw : null;

            $cabang = $cabangs[$cabangKode];

            // Kelompok Pelanggan Master Handling
            $kodeKel = !empty($kodeKelompokRaw) ? $kodeKelompokRaw : null;
            $namaKel = !empty($kelompokRaw) ? $kelompokRaw : null;
            $kelompokId = null;

            if ($kodeKel || $namaKel) {
                $cacheKey = ($kodeKel ?? '') . '|' . ($namaKel ?? '');
                if (isset($kelompokCache[$cacheKey])) {
                    $kelompokId = $kelompokCache[$cacheKey];
                } else {
                    $kp = null;
                    if ($kodeKel) {
                        $kp = KelompokPelanggan::where('kode', $kodeKel)->first();
                    }
                    if (!$kp && $namaKel) {
                        $kp = KelompokPelanggan::where('nama', $namaKel)->first();
                        if (!$kp) {
                            $kp = KelompokPelanggan::whereRaw('LOWER(nama) = ?', [strtolower($namaKel)])->first();
                        }
                    }

                    if ($kp) {
                        if ($kodeKel && $kp->kode !== $kodeKel) {
                            $existsOther = KelompokPelanggan::where('kode', $kodeKel)->where('id', '!=', $kp->id)->exists();
                            if (!$existsOther) {
                                $kp->kode = $kodeKel;
                            }
                        }
                        if ($namaKel && (empty($kp->nama) || strtolower($kp->nama) === 'mandiri' || strtolower($kp->nama) === 'klinisi')) {
                            $kp->nama = $namaKel;
                        }
                        $kp->save();
                    } else {
                        $baseKode = $kodeKel ?: Str::slug($namaKel ?? 'kelompok');
                        $kodeToUse = $baseKode;
                        $counter = 1;
                        while (KelompokPelanggan::where('kode', $kodeToUse)->exists()) {
                            $kodeToUse = $baseKode . '-' . $counter++;
                        }
                        $baseNama = $namaKel ?: $kodeToUse;
                        $namaToUse = $baseNama;
                        $counterNama = 1;
                        while (KelompokPelanggan::where('nama', $namaToUse)->exists()) {
                            $namaToUse = $baseNama . ' (' . $counterNama++ . ')';
                        }
                        $kp = KelompokPelanggan::create([
                            'kode'      => $kodeToUse,
                            'nama'      => $namaToUse,
                            'is_active' => true,
                        ]);
                    }
                    $kelompokId = $kp->id;
                    $kelompokCache[$cacheKey] = $kelompokId;
                }
            } else {
                $kelompokId = $defaultMandiriId;
            }

            // Dokter Master Handling
            $kodeDokter = !empty($kodeDokterRaw) ? $kodeDokterRaw : null;
            $namaDokter = !empty($dokterRaw) ? $dokterRaw : null;
            $dokterId   = null;

            if ($kodeDokter || $namaDokter) {
                $docKey = ($kodeDokter ?? '') . '|' . ($namaDokter ?? '');
                if (isset($dokterCache[$docKey])) {
                    $dokterId = $dokterCache[$docKey];
                } else {
                    $doc = null;
                    if ($kodeDokter) {
                        $doc = Dokter::where('kode_dokter', $kodeDokter)->first();
                    }
                    if (!$doc && $namaDokter) {
                        $doc = Dokter::where('nama_dokter', $namaDokter)->first();
                    }
                    if ($doc) {
                        if ($kodeDokter && empty($doc->kode_dokter)) {
                            $doc->kode_dokter = $kodeDokter;
                            $doc->save();
                        }
                    } else {
                        $doc = Dokter::create([
                            'kode_dokter' => $kodeDokter,
                            'nama_dokter' => $namaDokter ?: ($kodeDokter ?? 'Dokter'),
                        ]);
                    }
                    $dokterId = $doc->id;
                    $dokterCache[$docKey] = $dokterId;
                }
            }

            // Marketing Master Handling
            $namaMarketing = !empty($marketingRaw) ? $marketingRaw : null;
            $marketingId   = null;

            if ($namaMarketing) {
                if (isset($marketingCache[$namaMarketing])) {
                    $marketingId = $marketingCache[$namaMarketing];
                } else {
                    $mkt = Marketing::firstOrCreate(['nama_marketing' => $namaMarketing]);
                    $marketingId = $mkt->id;
                    $marketingCache[$namaMarketing] = $marketingId;
                }
            }

            DB::transaction(function () use (
                $pid,
                $nama,
                $jenisKelamin,
                $noTelp,
                $dobDate,
                $alamat,
                $kota,
                $kecamatan,
                $nip,
                $nik,
                $jabatan,
                $location,
                $totalKedatangan,
                $biayaValue,
                $brutoValue,
                $discValue,
                $payValue,
                $jmlTest,
                $tipeOmset,
                $dokterId,
                $marketingId,
                $kelompokId,
                $pemeriksaan,
                $mou,
                $cabang,
                $tanggal,
                $no,
                $batchId,
                &$processedCount
            ) {
                $pelanggan      = Pelanggan::firstOrNew(['pid' => $pid]);
                $isNewPelanggan = !$pelanggan->exists;

                // ── Simpan snapshot data pelanggan SEBELUM diubah (untuk rollback) ──
                if (!empty($batchId)) {
                    $snapshotExists = ImportBatchPelangganSnapshot::where('import_batch_id', $batchId)
                        ->where('pelanggan_id', $pelanggan->id ?? 0)
                        ->exists();

                    if (!$snapshotExists && !$isNewPelanggan) {
                        ImportBatchPelangganSnapshot::create([
                            'import_batch_id'         => $batchId,
                            'pelanggan_id'            => $pelanggan->id,
                            'is_new_pelanggan'        => false,
                            'total_kedatangan_before' => $pelanggan->total_kedatangan ?? 0,
                            'total_biaya_before'      => $pelanggan->total_biaya ?? 0,
                            'class_before'            => $pelanggan->class,
                        ]);
                    }
                }

                $pelanggan->cabang_id = $cabang->id;
                $pelanggan->nama      = $nama;
                $pelanggan->nik       = $nik;
                $pelanggan->nip       = $nip;
                $pelanggan->no_telp   = $noTelp;
                $pelanggan->dob       = $dobDate;
                $pelanggan->alamat    = $alamat;
                $pelanggan->kota      = $kota;
                if (!empty($kecamatan))    $pelanggan->kecamatan    = $kecamatan;
                if (!empty($jabatan))      $pelanggan->jabatan      = $jabatan;
                if (!empty($location))     $pelanggan->location     = $location;
                if (!empty($jenisKelamin)) $pelanggan->jenis_kelamin = $jenisKelamin;

                if ($isNewPelanggan) {
                    $pelanggan->total_kedatangan = $totalKedatangan;
                    $pelanggan->total_biaya      = $biayaValue;

                    $hasHighValueVisit = ($biayaValue >= 4000000);

                    $pelanggan->class = Pelanggan::calculateClass(
                        $pelanggan->total_kedatangan,
                        $pelanggan->total_biaya,
                        $hasHighValueVisit,
                        (bool) $pelanggan->is_pelanggan_khusus
                    );
                }

                $pelanggan->save();

                if (!empty($batchId) && $isNewPelanggan) {
                    ImportBatchPelangganSnapshot::create([
                        'import_batch_id'         => $batchId,
                        'pelanggan_id'            => $pelanggan->id,
                        'is_new_pelanggan'        => true,
                        'total_kedatangan_before' => 0,
                        'total_biaya_before'      => 0,
                        'class_before'            => null,
                    ]);
                }

                Kunjungan::create([
                    'no'                    => $no,
                    'pelanggan_id'          => $pelanggan->id,
                    'cabang_id'             => $cabang->id,
                    'tanggal_kunjungan'     => $tanggal,
                    'biaya'                 => $biayaValue,
                    'pemeriksaan'           => $pemeriksaan,
                    'mou'                   => $mou,
                    'tipe_omset'            => $tipeOmset,
                    'dokter_id'             => $dokterId,
                    'marketing_id'          => $marketingId,
                    'bruto'                 => $brutoValue,
                    'disc'                  => $discValue,
                    'pay'                   => $payValue,
                    'jml_test'              => $jmlTest,
                    'total_kedatangan'      => $totalKedatangan,
                    'kelompok_pelanggan_id' => $kelompokId,
                    'import_batch_id'       => $batchId ?: null,
                ]);

                if ($isNewPelanggan) {
                    $pelanggan->recordInitialClass($tanggal);
                } else {
                    $pelanggan->updateStats($tanggal, 'Perubahan dari import data CSV');
                }

                $processedCount++;
            });

            if ($importUserId > 0 && $totalDataRows > 0) {
                $percent = (int) floor(($currentRow / $totalDataRows) * 100);
                Cache::put("import_progress_{$importUserId}", min($percent, 99), now()->addMinutes(30));
            }
        }

        Log::info('CSV import completed', [
            'processed'          => $processedCount,
            'duplicates_skipped' => $duplicateSkipped,
            'batch_id'           => $batchId,
        ]);

        return $processedCount;
    }

    /**
     * Normalisasi nilai jenis kelamin dari import Excel
     * Menerima L / P maupun teks kepanjangannya: Laki-Laki / Perempuan (atau LK, PR, Pria, Wanita)
     */
    private function normalizeJenisKelamin(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $trimmed = trim((string) $raw);
        if ($trimmed === '' || $trimmed === '-') {
            return null;
        }

        // Bersihkan tanda baca / pemisah untuk fleksibilitas (misal: "Laki-Laki", "Laki Laki")
        $clean = strtoupper(preg_replace('/[^A-Za-z]/', '', $trimmed));

        if (in_array($clean, ['L', 'LK', 'LAKILAKI', 'LAKI', 'PRIA', 'M', 'MALE'], true)) {
            return 'Laki-Laki';
        }

        if (in_array($clean, ['P', 'PR', 'PEREMPUAN', 'WANITA', 'F', 'FEMALE'], true)) {
            return 'Perempuan';
        }

        return substr($trimmed, 0, 20);
    }

    /**
     * Parse tanggal dari berbagai format CSV/Excel
     * Support:
     * - Excel serial date (angka numerik, misal: 45678) dari Excel::toArray()
     * - String format: Y-m-d, d/m/Y, d-m-Y, d/m/y, d-m-y, Y/m/d
     * - Carbon/DateTime instance
     * Return null jika format tidak valid
     */
    private function parseCsvDate($value): ?\Carbon\Carbon
    {
        if (empty($value) && $value !== 0) {
            return null;
        }

        try {
            // Handle Excel serial date (angka numerik dari Excel::toArray())
            // Excel menyimpan tanggal sebagai angka hari sejak 1 Jan 1900
            if (is_numeric($value) && !is_string($value)) {
                $dateTime = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value);
                $date = \Carbon\Carbon::instance($dateTime);
                if ($date->year > 1900 && $date->year < 2100) {
                    return $date;
                }
                return null;
            }

            // Handle Carbon instance
            if ($value instanceof \Carbon\Carbon) {
                return $value;
            }

            // Handle DateTime instance
            if ($value instanceof \DateTime) {
                return \Carbon\Carbon::instance($value);
            }

            $dateString = trim((string) $value);

            $formats = [
                'Y-m-d',
                'd/m/Y',
                'd-m-Y',
                'd/m/y',
                'd-m-y',
                'Y/m/d',
                'd F Y',
                'd M Y',
            ];

            foreach ($formats as $format) {
                try {
                    $date = \Carbon\Carbon::createFromFormat($format, $dateString);
                    if ($date && $date->year > 1900 && $date->year < 2100) {
                        return $date;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            $date = \Carbon\Carbon::parse($dateString);
            if ($date->year > 1900 && $date->year < 2100) {
                return $date;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse biaya dari berbagai format CSV/Excel
     * Membersihkan format: Rp, spasi, titik ribuan, koma desimal
     * Contoh: "Rp 1.500.000,00" → 1500000
     * Return null jika nilai negatif atau tidak valid
     */
    private function parseCsvBiaya($value): ?float
    {
        if (empty($value) && $value !== 0 && $value !== '0') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return (float) $value;
            }

            $cleanValue = (string) $value;
            $cleanValue = str_replace(['Rp', ' ', '.', ',00'], '', $cleanValue);

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
            }

            $result = (float) $cleanValue;
            return $result >= 0 ? $result : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Membaca file CSV dengan auto-detect delimiter
     * Cara kerja:
     * 1. Baca file dan hapus BOM (Byte Order Mark) jika ada
     * 2. Convert encoding ke UTF-8 jika perlu
     * 3. Deteksi delimiter (koma, titik koma, atau tab) berdasarkan jumlah kolom
     * 4. Parse setiap baris menjadi array
     * Return: Array 2D dengan format [ [baris1], [baris2], ... ]
     */
    private function readCsvFile($file)
    {
        $path    = $file->getPathname();
        $content = file_get_contents($path);

        $bom = pack('CCC', 0xEF, 0xBB, 0xBF);
        if (substr($content, 0, 3) === $bom) {
            $content = substr($content, 3);
        }

        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
        }

        $delimiters   = [',', ';', "\t"];
        $bestDelimiter = ',';
        $maxCols      = 0;

        $lines     = explode("\n", $content);
        $firstLine = $lines[0] ?? '';

        foreach ($delimiters as $delimiter) {
            $cols = count(str_getcsv($firstLine, $delimiter));
            if ($cols > $maxCols) {
                $maxCols       = $cols;
                $bestDelimiter = $delimiter;
            }
        }

        Log::info('CSV delimiter detected', ['delimiter' => $bestDelimiter, 'columns' => $maxCols]);

        $rows = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $row    = str_getcsv($line, $bestDelimiter);
            $rows[] = $row;
        }

        return [$rows];
    }
}
