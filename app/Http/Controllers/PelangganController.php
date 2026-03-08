<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

use App\Models\Pelanggan;
use App\Models\Kunjungan;
use App\Models\Cabang;
use App\Models\ActivityLog;
use App\Models\KelompokPelanggan;
use App\Imports\KunjunganImport;
use Maatwebsite\Excel\Facades\Excel;


/**
 * Controller untuk mengelola data pelanggan
 * Menangani CRUD pelanggan, import/export, dan pencarian
 */
class PelangganController extends Controller
{
    /**
     * Menyimpan data pelanggan baru atau kunjungan ke pelanggan existing
     * Cara kerja:
     * 1. Loop melalui semua input dari form (bisa multiple pelanggan)
     * 2. Jika mode 'existing' → tambah kunjungan ke pelanggan yang sudah ada
     * 3. Jika mode 'new' → buat pelanggan baru + kunjungan pertama
     * 4. Validasi setiap input sebelum disimpan
     * 5. Return pesan sukses/error sesuai hasil
     */
    public function store(Request $request)
    {

        $inputs = $request->inputs ?? [];
        $errors = [];
        $createdCount = 0;
        $updatedCount = 0;

        $cabangs = Cabang::all()->keyBy('id');

        foreach ($inputs as $index => $input) {
            $mode = $input['mode'] ?? 'new';
            $input['biaya'] = str_replace('.', '', $input['biaya'] ?? '');

            if ($mode === 'existing') {
                // Mode: Tambah kunjungan ke pelanggan yang sudah ada
                $validator = Validator::make($input, [
                    'existing_pelanggan_id' => 'required|exists:pelanggans,id',
                    'biaya' => 'required|numeric|min:0',
                    'tanggal_kunjungan' => 'required|date',
                    'kelompok_pelanggan' => 'required|in:mandiri,klinisi',
                ], [
                    'existing_pelanggan_id.required' => 'Pelanggan harus dipilih terlebih dahulu.',
                    'existing_pelanggan_id.exists' => 'Pelanggan tidak ditemukan.',
                    'biaya.required' => 'Biaya wajib diisi.',
                    'biaya.numeric' => 'Biaya harus berupa angka.',
                    'biaya.min' => 'Biaya tidak boleh negatif.',
                    'tanggal_kunjungan.required' => 'Tanggal Kunjungan wajib diisi.',
                    'tanggal_kunjungan.date' => 'Tanggal Kunjungan tidak valid.',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->all() as $error) {
                        $errors[$index][] = $error;
                    }
                } else {
                    DB::transaction(function () use ($input, &$updatedCount) {
                        $pelanggan = Pelanggan::find($input['existing_pelanggan_id']);
                        $visitDate = \Carbon\Carbon::parse($input['tanggal_kunjungan']);
                        
                        // Tambah kunjungan baru
                        $kelompokKode = $input['kelompok_pelanggan'] ?? 'mandiri';
                        $kelompok = KelompokPelanggan::where('kode', $kelompokKode)->first();

                        $pelanggan->kunjungans()->create([
                            'cabang_id' => $input['existing_cabang_id'] ?? $pelanggan->cabang_id,
                            'tanggal_kunjungan' => $input['tanggal_kunjungan'],
                            'biaya' => $input['biaya'],
                            'kelompok_pelanggan_id' => $kelompok?->id,
                            'total_kedatangan' => 1,
                        ]);

                        // Update stats pelanggan dengan tanggal kunjungan
                        $pelanggan->updateStats($visitDate);
                        $updatedCount++;
                    });
                }
            } else {
                // Mode: Pelanggan baru
                $pid = trim($input['pid'] ?? '');
                
                // Validasi PID wajib diisi
                if (empty($pid)) {
                    $errors[$index][] = "PID wajib diisi.";
                    continue;
                }

                $validator = Validator::make($input, [
                    'pid' => 'required|string',
                    'cabang_id' => 'required|exists:cabangs,id',
                    'nama' => 'required',
                    'no_telp' => 'nullable|string',
                    'dob' => 'nullable|date',
                    'alamat' => 'nullable|string',
                    'kota' => 'nullable|string',
                    'biaya' => 'required|numeric|min:0',
                    'tanggal_kunjungan' => 'required|date',
                    'kelompok_pelanggan' => 'required|in:mandiri,klinisi',
                ], [
                    'pid.required' => 'PID wajib diisi.',
                    'cabang_id.required' => 'Cabang wajib dipilih.',
                    'cabang_id.exists' => 'Cabang tidak valid.',
                    'nama.required' => 'Nama wajib diisi.',
                    'biaya.required' => 'Biaya wajib diisi.',
                    'biaya.numeric' => 'Biaya harus berupa angka.',
                    'biaya.min' => 'Biaya tidak boleh negatif.',
                    'tanggal_kunjungan.required' => 'Tanggal Kunjungan wajib diisi.',
                    'tanggal_kunjungan.date' => 'Tanggal Kunjungan tidak valid.',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->all() as $error) {
                        $errors[$index][] = $error;
                    }
                    continue;
                }

                // Cek duplikat PID
                $existingPelanggan = Pelanggan::where('pid', $pid)->first();
                if ($existingPelanggan) {
                    $errors[$index][] = "PID {$pid} sudah terdaftar atas nama \"{$existingPelanggan->nama}\".";
                    continue;
                }

                // Point 8: Validasi prefix PID harus sesuai dengan cabang yang dipilih
                $selectedCabang = $cabangs->get($input['cabang_id']);
                if ($selectedCabang) {
                    $pidPrefix = strtoupper(substr($pid, 0, 2));
                    $cabangKode = strtoupper($selectedCabang->kode);
                    if ($pidPrefix !== $cabangKode) {
                        $errors[$index][] = "PID \"{$pid}\" tidak sesuai dengan cabang \"{$selectedCabang->nama}\". Prefix PID harus \"{$cabangKode}\" untuk cabang ini.";
                        continue;
                    }
                }

                DB::transaction(function () use ($input, &$createdCount) {
                    $visitDate = \Carbon\Carbon::parse($input['tanggal_kunjungan']);
                    
                    $pelanggan = Pelanggan::create([
                        'pid' => $input['pid'],
                        'cabang_id' => $input['cabang_id'],
                        'nama' => $input['nama'],
                        'no_telp' => $input['no_telp'] ?? null,
                        'dob' => $input['dob'] ?? null,
                        'alamat' => $input['alamat'] ?? null,
                        'kota' => $input['kota'] ?? null,
                        'class' => 'Potensial',
                    ]);

                    $kelompokKode = $input['kelompok_pelanggan'] ?? 'mandiri';
                    $kelompok = KelompokPelanggan::where('kode', $kelompokKode)->first();

                    $pelanggan->kunjungans()->create([
                        'cabang_id' => $input['cabang_id'],
                        'tanggal_kunjungan' => $input['tanggal_kunjungan'],
                        'biaya' => $input['biaya'],
                        'kelompok_pelanggan_id' => $kelompok?->id,
                        'total_kedatangan' => 1,
                    ]);

                    $pelanggan->updateStats($visitDate);
                    $pelanggan->recordInitialClass($visitDate);
                    $createdCount++;
                });

            }

        }


        $failedInputs = [];
        foreach ($errors as $index => $errorMessages) {
            if (isset($inputs[$index])) {
                $failedInputs[$index] = $inputs[$index];
            }
        }

        $successMessage = '';
        if ($createdCount > 0) {
            $successMessage .= $createdCount . ' pelanggan baru berhasil ditambahkan. ';
        }
        if ($updatedCount > 0) {
            $successMessage .= $updatedCount . ' kunjungan berhasil ditambahkan ke pelanggan lama.';
        }

        if (($createdCount > 0 || $updatedCount > 0) && empty($errors)) {
            return redirect()->route('pelanggan.create')->with('success', trim($successMessage));
        } elseif (($createdCount > 0 || $updatedCount > 0) && !empty($errors)) {
            return redirect()->route('pelanggan.create')
                ->with('success', trim($successMessage))
                ->with('error', count($errors) . ' data gagal disimpan.')
                ->with('errors', $errors)
                ->with('inputs', $failedInputs);
        } else {
            return redirect()->route('pelanggan.create')
                ->with('error', 'Semua data gagal disimpan.')
                ->with('errors', $errors)
                ->with('inputs', $failedInputs);
        }
    }

    /**
     * API endpoint untuk mencari pelanggan berdasarkan PID
     * Digunakan saat autocomplete di form tambah kunjungan
     * Return: JSON dengan data pelanggan jika ditemukan
     */
    public function searchByPid(Request $request)
    {

        $pid = $request->query('pid');
        
        if (empty($pid)) {
            return response()->json(['found' => false]);
        }
        
        $pelanggan = Pelanggan::with('cabang')->where('pid', $pid)->first();
        
        if (!$pelanggan) {
            return response()->json(['found' => false]);
        }
        
        return response()->json([
            'found' => true,
            'pelanggan' => [
                'id' => $pelanggan->id,
                'pid' => $pelanggan->pid,
                'nama' => $pelanggan->nama,
                'class' => $pelanggan->class,
                'cabang_id' => $pelanggan->cabang_id,
            ],
            'cabang' => $pelanggan->cabang->nama ?? '-',
        ]);
    }


    /**
     * Menampilkan daftar pelanggan dengan filter dan sorting
     * Cara kerja:
     * 1. Ambil parameter filter dari request (bulan, tahun, search, dll)
     * 2. Jika tidak ada filter → tampilkan halaman kosong (belum klik filter)
     * 3. Build query dengan subquery untuk tanggal kunjungan terakhir
     * 4. Terapkan filter: periode, cabang, kelas, range omset/kedatangan
     * 5. Sorting di level database (bukan collection)
     * 6. Paginate hasil (30 per halaman)
     */
    public function index(Request $request)
    {
        // Ambil semua parameter filter dari URL/form
        $bulan          = $request->filled('bulan') ? (int) $request->bulan : null;
        $tahun          = $request->filled('tahun') ? (int) $request->tahun : null;
        $type           = $request->type;
        $search         = $request->search;
        $sort           = $request->sort ?? 'nama';
        $direction      = in_array(strtolower((string) $request->direction), ['asc', 'desc'], true)
            ? strtolower((string) $request->direction)
            : 'asc';
        $cabangId       = $request->cabang_id;
        $omsetRange     = $request->omset_range;
        $kedatanganRange = $request->kedatangan_range;
        $kelas          = $request->kelas;
        $kelompokPelanggan = $request->kelompok_pelanggan; // Point 4: filter mandiri/klinisi
        $tipePelanggan  = $request->tipe_pelanggan;        // Point 4: filter biasa/khusus

        $cabangs = Cabang::all();

        // Set default periode ke "semua" jika tidak ada filter
        // Tapi tampilkan data kosong saat pertama kali masuk (belum klik filter)
        if (!$type && !$search && !$kelompokPelanggan && !$tipePelanggan && !$cabangId && !$kelas && !$omsetRange && !$kedatanganRange) {
            $type  = 'semua';
            $bulan = null;
            $tahun = null;

            // Return view dengan data kosong (belum klik filter)
            return view('pelanggan.index', [
                'pelanggan'          => collect(),
                'bulan'              => null,
                'tahun'              => null,
                'type'               => 'semua',
                'search'             => null,
                'cabang_id'          => null,
                'omset_range'        => null,
                'kedatangan_range'   => null,
                'kelas'              => null,
                'kelompok_pelanggan' => null,
                'tipe_pelanggan'     => null,
                'cabangs'            => $cabangs,
                'sort'               => $sort,
                'direction'          => $direction,
                'searchMode'         => false,
            ]);
        } elseif ($type === 'perbulan' && !$bulan) {
            $bulan = date('m');
        }
        
        if ($type && $type !== 'semua' && !$tahun) {
            $tahun = date('Y');
        }



        // Subquery untuk tgl_kunjungan terakhir sesuai periode yang dipilih
        // Dihitung di DB level — tidak perlu load semua kunjungan ke memory
        if ($type === 'perbulan' && $bulan && $tahun) {
            $safeBulan = (int) $bulan;
            $safeTahun = (int) $tahun;
            $tglSubquery = "SELECT MAX(tanggal_kunjungan) FROM kunjungans
                WHERE kunjungans.pelanggan_id = pelanggans.id
                AND MONTH(tanggal_kunjungan) = {$safeBulan}
                AND YEAR(tanggal_kunjungan) = {$safeTahun}";
        } elseif ($type === 'pertahun' && $tahun) {
            $safeTahun = (int) $tahun;
            $tglSubquery = "SELECT MAX(tanggal_kunjungan) FROM kunjungans
                WHERE kunjungans.pelanggan_id = pelanggans.id
                AND YEAR(tanggal_kunjungan) = {$safeTahun}";
        } else {
            $tglSubquery = "SELECT MAX(tanggal_kunjungan) FROM kunjungans
                WHERE kunjungans.pelanggan_id = pelanggans.id";
        }

        // Base query — semua filter dilakukan di DB, bukan di PHP/collection
        $query = Pelanggan::with('cabang')
            ->select('pelanggans.*')
            ->selectRaw("({$tglSubquery}) as tgl_kunjungan");

        // Filter pencarian (PID atau Nama)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('pid', 'like', '%' . $search . '%')
                  ->orWhere('nama', 'like', '%' . $search . '%');
            });
        }

        // Filter periode — hanya diterapkan jika bukan mode pencarian
        if (!$search) {
            if ($type === 'perbulan') {
                $query->whereHas('kunjungans', function ($q) use ($bulan, $tahun) {
                    $q->whereMonth('tanggal_kunjungan', $bulan)
                      ->whereYear('tanggal_kunjungan', $tahun);
                });
            } elseif ($type === 'pertahun') {
                $query->whereHas('kunjungans', function ($q) use ($tahun) {
                    $q->whereYear('tanggal_kunjungan', $tahun);
                });
            }
            // type === 'semua' — tidak ada filter periode
        }

        // Filter cabang
        if ($cabangId) {
            $query->where('cabang_id', $cabangId);
        }

        // Filter kelas
        if ($kelas) {
            $query->where('class', $kelas);
        }

        // Filter range omset — menggunakan kolom total_biaya yang sudah tersimpan
        if ($omsetRange !== null && $omsetRange !== '') {
            switch ($omsetRange) {
                case '0': $query->where('total_biaya', '<', 1000000); break;
                case '1': $query->whereBetween('total_biaya', [1000000, 4000000]); break;
                case '2': $query->where('total_biaya', '>=', 4000000); break;
            }
        }

        // Filter range kedatangan — menggunakan kolom total_kedatangan yang sudah tersimpan
        if ($kedatanganRange !== null && $kedatanganRange !== '') {
            switch ($kedatanganRange) {
                case '0': $query->where('total_kedatangan', '<=', 2); break;
                case '1': $query->whereBetween('total_kedatangan', [3, 4]); break;
                case '2': $query->where('total_kedatangan', '>', 4); break;
            }
        }

        // Point 4: Filter kelompok pelanggan (mandiri/klinisi)
        if ($kelompokPelanggan) {
            $query->whereHas('kunjungans.kelompokPelanggan', function ($q) use ($kelompokPelanggan) {
                $q->where('kode', $kelompokPelanggan);
            });
        }

        // Point 4: Filter tipe pelanggan (biasa/khusus)
        if ($tipePelanggan === 'khusus') {
            $query->where('is_pelanggan_khusus', true);
        } elseif ($tipePelanggan === 'biasa') {
            $query->where(function ($q) {
                $q->where('is_pelanggan_khusus', false)
                  ->orWhereNull('is_pelanggan_khusus');
            });
        }

        // Sorting di DB level — tidak perlu sortBy() di collection
        if ($sort === 'tgl_kunjungan') {
            // MySQL: NULL values diletakkan di akhir (IS NULL = 0 untuk non-null, 1 untuk null)
            $query->orderByRaw("(tgl_kunjungan IS NULL) ASC, tgl_kunjungan {$direction}");
        } elseif ($sort === 'class') {
            $query->orderBy('total_biaya', $direction);
        } elseif ($sort === 'nama') {
            $query->orderByRaw("LOWER(nama) {$direction}");
        } elseif ($sort === 'alamat') {
            $query->orderByRaw("LOWER(alamat) {$direction}");
        } elseif ($sort === 'cabang_id') {
            // Join dengan tabel cabangs untuk sorting berdasarkan nama cabang
            $query->leftJoin('cabangs', 'pelanggans.cabang_id', '=', 'cabangs.id')
                  ->orderByRaw("LOWER(cabangs.nama) {$direction}")
                  ->select('pelanggans.*'); // Pastikan hanya select dari pelanggans
        } elseif ($sort === 'id') {
            $query->orderBy('pelanggans.id', $direction);
        } elseif (in_array($sort, ['pid', 'total_biaya', 'total_kedatangan', 'no_telp', 'dob'])) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderByRaw('LOWER(nama) ASC');
        }




        // Paginate di DB level — tidak perlu manual slice dari collection
        $pelanggan = $query->paginate(30)->withQueryString();

        return view('pelanggan.index', [
            'pelanggan'        => $pelanggan,
            'bulan'            => $bulan,
            'tahun'            => $tahun,
            'type'             => $type,
            'search'           => $search,
            'cabang_id'        => $cabangId,
            'omset_range'      => $omsetRange,
            'kedatangan_range' => $kedatanganRange,
            'kelas'            => $kelas,
            'kelompok_pelanggan' => $kelompokPelanggan,
            'tipe_pelanggan'   => $tipePelanggan,
            'cabangs'          => $cabangs,
            'sort'             => $sort,
            'direction'        => $direction,
            'searchMode'       => (bool) $search,
        ]);
    }

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
            $extension = strtolower($file->getClientOriginalExtension());
            Log::info('File uploaded', ['filename' => $file->getClientOriginalName(), 'size' => $file->getSize(), 'extension' => $extension]);
            
            if ($extension === 'csv' || $extension === 'txt') {
                $rows = $this->readCsvFile($file);
            } else {
                $rows = Excel::toArray(null, $file);
            }

            $errors = [];
            $rowNumber = 0;
            $totalRows = 0;
            $validRows = 0;

            if (empty($rows) || empty($rows[0])) {
                Log::warning('Empty or invalid file');
                if ($isAjax) {
                    return response()->json(['success' => false, 'message' => 'File kosong atau tidak valid.'], 422);
                }
                return back()->with('error', 'File kosong atau tidak valid.');
            }

            Log::info('Excel file read successfully', ['sheet_count' => count($rows), 'first_sheet_rows' => count($rows[0])]);
            
            $cabangs = Cabang::all()->keyBy('kode');
            
            foreach ($rows[0] as $row) {
                $rowNumber++;
                
                if ($rowNumber == 1 && count($row) > 0 && strtolower(trim($row[0] ?? '')) == 'no') {
                    continue;
                }

                if (count($row) < 10) {
                    Log::debug("Row $rowNumber skipped: insufficient columns", ['column_count' => count($row)]);
                    continue;
                }
                
                $no = trim($row[0] ?? '');
                $nama = trim($row[1] ?? '');
                $pid = trim($row[7] ?? '');
                
                if (empty($pid) || empty($nama)) {
                    Log::debug("Row {$rowNumber} skipped: empty PID or nama");
                    continue;
                }

                $cabangKode = strtoupper(substr($pid, 0, 2));
                if (!isset($cabangs[$cabangKode])) {
                    $errors[] = "Baris {$rowNumber}: Kode cabang '{$cabangKode}' dalam PID '{$pid}' tidak valid.";
                    continue;
                }
                
                $totalRows++;
                
                $pelanggan = Pelanggan::where('pid', $pid)->first();
                
                if ($pelanggan) {
                    $dbNama = trim($pelanggan->nama ?? '');
                    
                    if (strtolower($nama) !== strtolower($dbNama)) {
                        $errors[] = "Baris {$rowNumber}: PID {$pid} sudah terdaftar dengan nama '{$dbNama}'. Data Excel nama '{$nama}' tidak sesuai.";
                    } else {
                        $validRows++;
                    }
                } else {
                    $validRows++;
                }
            }
            
            Log::info('Validation completed', ['total_rows' => $totalRows, 'valid_rows' => $validRows, 'errors' => count($errors)]);

            if (!empty($errors)) {
                Log::warning('Import failed due to validation errors', ['error_count' => count($errors)]);
                if ($isAjax) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Import gagal! Beberapa data tidak sesuai dengan database.',
                        'errors' => $errors
                    ], 422);
                }
                return back()->with('error', 'Import gagal! Beberapa data tidak sesuai dengan database.')
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
            
            if ($extension === 'csv' || $extension === 'txt') {
                $this->processCsvImport($rows[0], (int) $userId);
            } else {
                Excel::import(new KunjunganImport, $file);
            }

            Cache::put("import_progress_{$userId}", 100, now()->addMinutes(30));
            
            $filename = $file->getClientOriginalName();
            Log::info('Import completed successfully', ['filename' => $filename]);

            $successMessage = "Import berhasil! File '$filename' dengan $validRows data telah diproses.";
            if ($isAjax) {
                return response()->json(['success' => true, 'message' => $successMessage]);
            }
            return back()->with('success', $successMessage);

        } catch (\Exception $e) {
            Cache::put("import_progress_{$userId}", 100, now()->addMinutes(5));

            Log::error('Import exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
        $bulan = $request->bulan ?? date('m');

        $tahun = $request->tahun ?? date('Y');
        $type = $request->type ?? 'perbulan';
        $search = $request->search;
        $cabangId = $request->cabang_id;
        $omsetRange = $request->omset_range;
        $kedatanganRange = $request->kedatangan_range;
        $kelas = $request->kelas;
        
        // Build filename based on filters
        $filename = 'pelanggan';
        if ($search) {
            $filename .= '_search_' . preg_replace('/[^a-zA-Z0-9]/', '_', $search);
        } else {
            if ($type == 'perbulan') {
                $filename .= '_' . $bulan . '_' . $tahun;
            } elseif ($type == 'pertahun') {
                $filename .= '_tahun_' . $tahun;
            } else {
                $filename .= '_semua';
            }
        }
        if ($kelas) {
            $filename .= '_' . $kelas;
        }
        if ($cabangId) {
            $cabang = Cabang::find($cabangId);
            $filename .= '_' . ($cabang ? preg_replace('/[^a-zA-Z0-9]/', '_', $cabang->nama) : 'cabang');
        }
        $filename .= '.xlsx';
        
        return Excel::download(new \App\Exports\PelangganExport($bulan, $tahun, $type, $search, $cabangId, $omsetRange, $kedatanganRange, $kelas), $filename);
    }

    /**
     * Download template Excel untuk import pelanggan
     * Template berisi header dan 5 baris contoh data
     * Memudahkan user mengisi data dengan format yang benar
     */
    public function downloadTemplate()
    {
        // Header kolom sesuai format import
        $headers = [
            'No',
            'Nama Pasien',
            'Total Kedatangan',
            'Tanggal Kedatangan Terakhir',
            'Total (Biaya)',
            'No Telpon',
            'DOB',
            'PID',
            'Alamat',
            'Kota',
            'Kelompok Pelanggan (mandiri/klinisi)'
        ];



        // Data dummy sebagai contoh
        $data = [
            [1, 'Budi Santoso', 3, '2024-01-15', 2500000, '081234567890', '1990-05-20', 'JK00001', 'Jl. Sudirman No. 123', 'Jakarta', 'mandiri'],
            [2, 'Siti Aminah', 5, '2024-02-10', 4500000, '082345678901', '1985-08-12', 'BD00002', 'Jl. Ahmad Yani No. 45', 'Bandung', 'klinisi'],
            [3, 'Ahmad Wijaya', 2, '2024-03-05', 1200000, '083456789012', '1992-11-03', 'SB00003', 'Jl. Gatot Subroto No. 78', 'Surabaya', 'mandiri'],
            [4, 'Dewi Kusuma', 4, '2024-01-28', 3800000, '084567890123', '1988-04-25', 'YK00004', 'Jl. Malioboro No. 12', 'Yogyakarta', 'klinisi'],
            [5, 'Eko Prasetyo', 1, '2024-02-20', 850000, '085678901234', '1995-09-18', 'ML00005', 'Jl. Ijen No. 56', 'Malang', 'mandiri'],
        ];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set header
        foreach ($headers as $col => $header) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . '1', $header);
        }

        // Set data
        foreach ($data as $row => $rowData) {
            foreach ($rowData as $col => $value) {
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . ($row + 2), $value);
            }
        }

        // Auto resize columns
        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
        }

        // Style header
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2EFDA']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                ]
            ]
        ];
        $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        $filename = 'template_import_pelanggan.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'template_');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }


    public function create()
    {
        $cabangs = Cabang::all();
        return view('pelanggan.create', compact('cabangs'));
    }

    public function khusus()
    {
        $cabangs = Cabang::all();
        return view('pelanggan.khusus', compact('cabangs'));
    }

    public function edit($id)
    {
        $pelanggan = Pelanggan::findOrFail($id);
        $cabangs = Cabang::all();
        return view('pelanggan.edit', compact('pelanggan', 'cabangs'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'pid' => 'required|unique:pelanggans,pid,' . $id,
            'cabang_id' => 'required|exists:cabangs,id',
            'nama' => 'required',
            'no_telp' => 'nullable|string',
            'dob' => 'nullable|date',
            'alamat' => 'nullable|string',
            'kota' => 'nullable|string',
        ]);

        $pelanggan = Pelanggan::findOrFail($id);
        $pelanggan->update($request->only(['pid', 'cabang_id', 'nama', 'no_telp', 'dob', 'alamat', 'kota']));


        return redirect()->route('dashboard')->with('success', 'Pelanggan berhasil diperbarui');
    }

    /**
     * Point 10: Hapus pelanggan
     * - Super Admin: langsung hapus
     * - Admin: buat approval request
     */
    public function destroy(Request $request, $id)
    {
        $pelanggan = Pelanggan::findOrFail($id);
        $role = Auth::user()->role?->name;

        if ($role === 'Super Admin') {
            // Super Admin: langsung hapus
            $pid = $pelanggan->pid;
            $nama = $pelanggan->nama;
            $pelanggan->delete();

            ActivityLog::record(
                'delete',
                'Pelanggan',
                "Menghapus pelanggan {$pid} ({$nama})",
                Auth::id(),
                Auth::user()->username ?? 'unknown',
                $role,
                $request->ip(),
                $request->userAgent()
            );

            return redirect()->route('pelanggan.index')->with('success', "Pelanggan {$pid} berhasil dihapus.");
        }

        // Admin: buat approval request
        $request->validate([
            'catatan_hapus' => 'required|string|max:500',
        ], [
            'catatan_hapus.required' => 'Catatan/alasan hapus wajib diisi.',
        ]);

        \App\Models\ApprovalRequest::create([
            'type'        => 'pelanggan',
            'action'      => 'delete',
            'target_type' => Pelanggan::class,
            'target_id'   => $pelanggan->id,
            'payload'     => ['pid' => $pelanggan->pid, 'nama' => $pelanggan->nama],
            'request_note' => $request->catatan_hapus,
            'status'      => 'pending',
            'requested_by' => Auth::id(),
        ]);

        ActivityLog::record(
            'delete',
            'ApprovalRequest',
            "Mengajukan hapus pelanggan {$pelanggan->pid} ({$pelanggan->nama}) untuk approval Superadmin.",
            Auth::id(),
            Auth::user()->username ?? 'unknown',
            $role,
            $request->ip(),
            $request->userAgent()
        );

        return redirect()->back()->with('success', "Pengajuan hapus pelanggan {$pelanggan->pid} berhasil dikirim untuk approval Superadmin.");
    }

    /**
     * Point 10: Hapus multiple pelanggan sekaligus (bulk delete)
     * - Super Admin: langsung hapus
     * - Admin: buat approval request
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        $role = Auth::user()->role?->name;

        if (empty($ids)) {
            return redirect()->back()->with('error', 'Tidak ada pelanggan yang dipilih.');
        }

        $ids = array_filter(array_map('intval', $ids));

        if (empty($ids)) {
            return redirect()->back()->with('error', 'ID pelanggan tidak valid.');
        }

        if ($role === 'Super Admin') {
            // Super Admin: langsung hapus
            $count = Pelanggan::whereIn('id', $ids)->count();
            Pelanggan::whereIn('id', $ids)->delete();

            ActivityLog::record(
                'delete',
                'Pelanggan',
                "Bulk delete {$count} pelanggan oleh Super Admin.",
                Auth::id(),
                Auth::user()->username ?? 'unknown',
                $role,
                $request->ip(),
                $request->userAgent()
            );

            return redirect()->back()->with('success', "{$count} pelanggan berhasil dihapus.");
        }

        // Admin: buat approval request
        $request->validate([
            'catatan_hapus' => 'required|string|max:500',
        ], [
            'catatan_hapus.required' => 'Catatan/alasan hapus wajib diisi.',
        ]);

        $count = count($ids);

        \App\Models\ApprovalRequest::create([
            'type'        => 'pelanggan',
            'action'      => 'bulk_delete',
            'target_type' => Pelanggan::class,
            'target_id'   => null,
            'payload'     => ['ids' => array_values($ids), 'count' => $count],
            'request_note' => $request->catatan_hapus,
            'status'      => 'pending',
            'requested_by' => Auth::id(),
        ]);

        ActivityLog::record(
            'delete',
            'ApprovalRequest',
            "Mengajukan bulk delete {$count} pelanggan untuk approval Superadmin.",
            Auth::id(),
            Auth::user()->username ?? 'unknown',
            $role,
            $request->ip(),
            $request->userAgent()
        );

        return redirect()->back()->with('success', "Pengajuan hapus {$count} pelanggan berhasil dikirim untuk approval Superadmin.");
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



    public function show(Request $request, $id)
    {
        $pelanggan = Pelanggan::with(['kunjungans', 'cabang', 'classHistories.changedBy'])->findOrFail($id);
        $totalTransaksi = $pelanggan->kunjungans->sum('biaya');

        // Pagination untuk riwayat kunjungan (10 per halaman)
        $kunjungans = $pelanggan->kunjungans()
            ->with('kelompokPelanggan')
            ->orderBy('tanggal_kunjungan', 'asc')
            ->paginate(10, ['*'], 'kunjungan_page');

        // Load pending approvals untuk kunjungan di halaman ini
        $kunjunganIds = $kunjungans->pluck('id')->toArray();
        $pendingApprovals = \App\Models\ApprovalRequest::whereIn('target_id', $kunjunganIds)
            ->where('target_type', \App\Models\Kunjungan::class)
            ->where('status', 'pending')
            ->get()
            ->groupBy('target_id');

        // Point 2: Pagination untuk riwayat pengajuan perubahan (10 per halaman)
        $allKunjunganIds = $pelanggan->kunjungans->pluck('id')->toArray();
        $approvalHistories = \App\Models\ApprovalRequest::whereIn('target_id', $allKunjunganIds)
            ->where('target_type', \App\Models\Kunjungan::class)
            ->with(['requester', 'reviewer'])
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'approval_page');

        // Pagination untuk riwayat perubahan kelas (10 per halaman)
        $classHistories = $pelanggan->classHistories()
            ->orderBy('created_at', 'asc')
            ->paginate(10, ['*'], 'class_page');

        return view('pelanggan.show', compact(
            'pelanggan',
            'kunjungans',
            'totalTransaksi',
            'classHistories',
            'pendingApprovals',
            'approvalHistories'
        ));
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
    private function processCsvImport(array $rows, int $importUserId = 0): void
    {
        $processedCount = 0;
        $duplicateSkipped = 0;
        $seenRows = [];
        $totalDataRows = count($rows);
        $currentRow = 0;

        // Load semua cabang ke memory untuk lookup cepat (hindari query berulang)
        $cabangs = Cabang::all()->keyBy('kode');

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $currentRow++;

            if ($index === 0 && count($row) > 0 && strtolower(trim($row[0] ?? '')) === 'no') {
                continue;
            }

            if (count($row) < 10) {
                continue;
            }

            $no = isset($row[0]) ? (int) $row[0] : null;
            $nama = trim($row[1] ?? '');
            $totalKedatangan = isset($row[2]) ? (int) $row[2] : 0;
            $tanggalKedatangan = $row[3] ?? null;
            $biaya = $row[4] ?? null;
            $noTelp = trim($row[5] ?? '');
            $dob = $row[6] ?? null;
            $pid = trim($row[7] ?? '');
            $alamat = trim($row[8] ?? '');
            $kota = trim($row[9] ?? '');
            $kelompokPelanggan = isset($row[10]) ? strtolower(trim((string) $row[10])) : 'mandiri';
            $kelompokPelanggan = in_array($kelompokPelanggan, ['mandiri', 'klinisi']) ? $kelompokPelanggan : 'mandiri';

            if (empty($pid) || empty($nama)) {
                continue;
            }

            // Dedup strict by full row content (semua kolom sama)
            $dedupKey = md5(json_encode([
                $no,
                $nama,
                $totalKedatangan,
                (string) $tanggalKedatangan,
                (string) $biaya,
                $noTelp,
                (string) $dob,
                $pid,
                $alamat,
                $kota,
                $kelompokPelanggan
            ]));

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

            $dobDate = $this->parseCsvDate($dob);
            $biayaValue = $this->parseCsvBiaya($biaya);
            if ($biayaValue === null) {
                continue;
            }

            $cabang = $cabangs[$cabangKode];

            DB::transaction(function () use (
                $pid,
                $nama,
                $noTelp,
                $dobDate,
                $alamat,
                $kota,
                $totalKedatangan,
                $biayaValue,
                $cabang,
                $tanggal,
                $no,
                $kelompokPelanggan,
                &$processedCount
            ) {
                $pelanggan = Pelanggan::firstOrNew(['pid' => $pid]);
                $isNewPelanggan = !$pelanggan->exists;

                $pelanggan->cabang_id = $cabang->id;
                $pelanggan->nama = $nama;
                $pelanggan->no_telp = $noTelp;
                $pelanggan->dob = $dobDate;
                $pelanggan->alamat = $alamat;
                $pelanggan->kota = $kota;

                if ($pelanggan->exists) {
                    $pelanggan->total_kedatangan += $totalKedatangan;
                    $pelanggan->total_biaya += $biayaValue;
                } else {
                    $pelanggan->total_kedatangan = $totalKedatangan;
                    $pelanggan->total_biaya = $biayaValue;
                }

                $hasHighValueVisit = $pelanggan->kunjungans()
                    ->where('biaya', '>=', 4000000)
                    ->exists() || ($biayaValue >= 4000000);

                $pelanggan->class = Pelanggan::calculateClass(
                    $pelanggan->total_kedatangan,
                    $pelanggan->total_biaya,
                    $hasHighValueVisit,
                    (bool) $pelanggan->is_pelanggan_khusus
                );

                $pelanggan->save();

                $kelompok = KelompokPelanggan::where('kode', $kelompokPelanggan)->first();

                Kunjungan::create([
                    'no' => $no,
                    'pelanggan_id' => $pelanggan->id,
                    'cabang_id' => $cabang->id,
                    'tanggal_kunjungan' => $tanggal,
                    'biaya' => $biayaValue,
                    'total_kedatangan' => $totalKedatangan,
                    'kelompok_pelanggan_id' => $kelompok?->id,
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
            'processed' => $processedCount,
            'duplicates_skipped' => $duplicateSkipped
        ]);
    }


    
    /**
     * Parse tanggal dari berbagai format CSV/Excel
     * Support format: Y-m-d, d/m/Y, d-m-Y, d/m/y, d-m-y, Y/m/d
     * Return null jika format tidak valid
     */
    private function parseCsvDate($value): ?\Carbon\Carbon
    {
        if (empty($value)) {
            return null;
        }

        
        try {
            $dateString = trim((string) $value);
            
            $formats = [
                'Y-m-d',
                'd/m/Y',
                'd-m-Y',
                'd/m/y',
                'd-m-y',
                'Y/m/d',
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
        $path = $file->getPathname();
        $content = file_get_contents($path);

        
        $bom = pack('CCC', 0xEF, 0xBB, 0xBF);
        if (substr($content, 0, 3) === $bom) {
            $content = substr($content, 3);
        }
        
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
        }
        
        $delimiters = [',', ';', "\t"];
        $bestDelimiter = ',';
        $maxCols = 0;
        
        $lines = explode("\n", $content);
        $firstLine = $lines[0] ?? '';
        
        foreach ($delimiters as $delimiter) {
            $cols = count(str_getcsv($firstLine, $delimiter));
            if ($cols > $maxCols) {
                $maxCols = $cols;
                $bestDelimiter = $delimiter;
            }
        }
        
        Log::info('CSV delimiter detected', ['delimiter' => $bestDelimiter, 'columns' => $maxCols]);
        
        $rows = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $row = str_getcsv($line, $bestDelimiter);
            $rows[] = $row;
        }
        
        return [$rows];
    }

    /**
     * Menampilkan form edit kunjungan
     * Method: GET /kunjungan/{kunjungan}/edit
     */
    public function editKunjungan($id)
    {
        $kunjungan = Kunjungan::with('pelanggan')->findOrFail($id);
        
        return view('pelanggan.edit-kunjungan', compact('kunjungan'));
    }


    /**
     * Update data kunjungan
     * Method: PUT /kunjungan/{kunjungan}
     * Setelah update, update biaya dan class pelanggan (total_kedatangan tetap)
     */
    public function updateKunjungan(Request $request, $id)
    {
        $request->validate([
            'tanggal_kunjungan' => 'required|date',
            'biaya' => 'required|numeric|min:0',
            'alasan_perubahan' => 'required|string|max:500',
            'kelompok_pelanggan' => 'required|in:mandiri,klinisi',
        ], [
            'tanggal_kunjungan.required' => 'Tanggal kunjungan wajib diisi.',
            'tanggal_kunjungan.date' => 'Format tanggal tidak valid.',
            'biaya.required' => 'Biaya wajib diisi.',
            'biaya.numeric' => 'Biaya harus berupa angka.',
            'biaya.min' => 'Biaya tidak boleh negatif.',
            'alasan_perubahan.required' => 'Alasan perubahan wajib diisi.',
            'alasan_perubahan.string' => 'Alasan perubahan harus berupa teks.',
            'alasan_perubahan.max' => 'Alasan perubahan maksimal 500 karakter.',
        ]);


        $kunjungan = Kunjungan::with('pelanggan')->findOrFail($id);
        $pelanggan = $kunjungan->pelanggan;
        
        // Simpan data lama untuk log
        $oldData = [
            'tanggal' => $kunjungan->tanggal_kunjungan,
            'biaya' => $kunjungan->biaya,
        ];

        // Hitung selisih biaya untuk update total_biaya pelanggan
        $biayaDifference = $request->biaya - $kunjungan->biaya;

        DB::transaction(function () use ($kunjungan, $pelanggan, $request, $biayaDifference) {
            // Update kunjungan
            $kelompok = KelompokPelanggan::where('kode', $request->kelompok_pelanggan)->first();

            $kunjungan->update([
                'tanggal_kunjungan' => $request->tanggal_kunjungan,
                'biaya' => $request->biaya,
                'kelompok_pelanggan_id' => $kelompok?->id ?? $kunjungan->kelompok_pelanggan_id,
            ]);

            // Update biaya dan class pelanggan (total_kedatangan tetap, tidak dihitung ulang)
            $pelanggan->updateBiayaAndClass(
                $biayaDifference,
                \Carbon\Carbon::parse($request->tanggal_kunjungan),
                'Perubahan dari edit kunjungan. Alasan user: ' . $request->alasan_perubahan
            );
        });


        // Catat di activity log
        ActivityLog::record(
            'update',
            'Kunjungan',
            "Mengubah kunjungan {$pelanggan->pid}: tanggal {$oldData['tanggal']} → {$request->tanggal_kunjungan}, biaya " . number_format($oldData['biaya'], 0, ',', '.') . " → " . number_format($request->biaya, 0, ',', '.') . ". Alasan: {$request->alasan_perubahan}",
            Auth::id(),
            Auth::user()->username ?? 'unknown',
            Auth::user()->role->name ?? '-',
            $request->ip(),
            $request->userAgent()
        );

        return redirect()->route('pelanggan.show', $pelanggan->id)
            ->with('success', 'Data kunjungan berhasil diperbarui.');
    }


    /**
     * Hapus kunjungan
     * Method: DELETE /kunjungan/{kunjungan}
     * Setelah hapus, recalculate stats pelanggan
     */
    public function destroyKunjungan(Request $request, $id)
    {
        $request->validate([
            'alasan_hapus' => 'required|string|max:500',
        ], [
            'alasan_hapus.required' => 'Alasan hapus wajib diisi.',
            'alasan_hapus.string' => 'Alasan hapus harus berupa teks.',
            'alasan_hapus.max' => 'Alasan hapus maksimal 500 karakter.',
        ]);

        $kunjungan = Kunjungan::with('pelanggan')->findOrFail($id);
        $pelanggan = $kunjungan->pelanggan;
        
        // Simpan data untuk log
        $logData = [
            'pid' => $pelanggan->pid,
            'tanggal' => $kunjungan->tanggal_kunjungan,
            'biaya' => $kunjungan->biaya,
        ];

        $deletedVisitDate = \Carbon\Carbon::parse($kunjungan->tanggal_kunjungan);

        DB::transaction(function () use ($kunjungan, $pelanggan, $request, $deletedVisitDate) {
            $kunjungan->delete();
            
            // Recalculate stats pelanggan setelah hapus
            $pelanggan->updateStats(
                $deletedVisitDate,
                'Perubahan dari hapus kunjungan. Alasan user: ' . $request->alasan_hapus
            );
        });

        // Catat di activity log
        ActivityLog::record(
            'delete',
            'Kunjungan',
            "Menghapus kunjungan {$logData['pid']} tanggal {$logData['tanggal']} (Rp " . number_format($logData['biaya'], 0, ',', '.') . "). Alasan: {$request->alasan_hapus}",
            Auth::id(),
            Auth::user()->username ?? 'unknown',
            Auth::user()->role->name ?? '-',
            request()->ip(),
            request()->userAgent()
        );

        return redirect()->route('pelanggan.show', $pelanggan->id)
            ->with('success', 'Data kunjungan berhasil dihapus.');
    }

    public function importProgress(Request $request)
    {
        $userId = Auth::id() ?? 0;
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
}
