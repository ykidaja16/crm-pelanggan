<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

use App\Models\Pelanggan;
use App\Models\Kunjungan;
use App\Models\Cabang;
use App\Imports\KunjunganImport;
use Maatwebsite\Excel\Facades\Excel;

class PelangganController extends Controller
{
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
                        
                        // Tambah kunjungan baru
                        $pelanggan->kunjungans()->create([
                            'cabang_id' => $input['existing_cabang_id'] ?? $pelanggan->cabang_id,
                            'tanggal_kunjungan' => $input['tanggal_kunjungan'],
                            'biaya' => $input['biaya'],
                        ]);

                        // Update stats pelanggan
                        $pelanggan->updateStats();
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

                DB::transaction(function () use ($input, &$createdCount) {
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

                    $pelanggan->kunjungans()->create([
                        'cabang_id' => $input['cabang_id'],
                        'tanggal_kunjungan' => $input['tanggal_kunjungan'],
                        'biaya' => $input['biaya'],
                    ]);

                    $pelanggan->updateStats();
                    $pelanggan->recordInitialClass();
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


    public function index(Request $request)
    {
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $type = $request->type;
        $search = $request->search;
        $sort = $request->sort ?? 'nama';
        $direction = $request->direction ?? 'asc';
        
        $cabangId = $request->cabang_id;
        $omsetRange = $request->omset_range;
        $kedatanganRange = $request->kedatangan_range;

        $cabangs = Cabang::all();

        if (!$type && !$bulan && !$tahun && !$search && !$cabangId && !$omsetRange && !$kedatanganRange) {
            return view('pelanggan.index', [
                'pelanggan' => collect(),
                'bulan' => null,
                'tahun' => null,
                'type' => null,
                'search' => null,
                'cabang_id' => null,
                'omset_range' => null,
                'kedatangan_range' => null,
                'cabangs' => $cabangs,
                'sort' => $sort,
                'direction' => $direction,
                'searchMode' => false
            ]);
        }

        if ($type && !$bulan) {
            $bulan = date('m');
        }
        if ($type && !$tahun) {
            $tahun = date('Y');
        }
        if (!$type) {
            $type = 'perbulan';
            $bulan = date('m');
            $tahun = date('Y');
        }

        $searchMode = false;
        if ($search) {
            $searchMode = true;
            $pelangganQuery = Pelanggan::with('cabang')
                ->where('pid', 'like', '%' . $search . '%')
                ->orWhere('nama', 'like', '%' . $search . '%');

            
            $pelanggan = $pelangganQuery->get();
            $pelanggan = $this->applyFilters($pelanggan, $cabangId, $omsetRange, $kedatanganRange);
            
            $pelanggan = $pelanggan->map(function ($p) {
                $p->tgl_kunjungan = $p->kunjungans->sortByDesc('tanggal_kunjungan')->first()?->tanggal_kunjungan->format('Y-m-d') ?? '-';
                return $p;
            });

            $pelanggan = $this->applySorting($pelanggan, $sort, $direction);

            $perPage = 30;
            $page = $request->input('page', 1);
            $sliced = $pelanggan->slice(($page - 1) * $perPage, $perPage)->values();
            $pelangganPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $sliced,
                $pelanggan->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return view('pelanggan.index', [
                'pelanggan' => $pelangganPaginator,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'type' => $type,
                'search' => $search,
                'cabang_id' => $cabangId,
                'omset_range' => $omsetRange,
                'kedatangan_range' => $kedatanganRange,
                'cabangs' => $cabangs,
                'history' => null,
                'sort' => $sort,
                'direction' => $direction,
                'searchMode' => true
            ]);
        }

        $endDate = null;
        if ($type == 'perbulan') {
            $endDate = \Carbon\Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();
        } elseif ($type == 'pertahun') {
            $endDate = \Carbon\Carbon::createFromDate($tahun, 12, 31);
        }

        $pelanggan = Pelanggan::with(['cabang', 'kunjungans' => function($q) use ($endDate) {
            if ($endDate) {
                $q->where('tanggal_kunjungan', '<=', $endDate);
            }
        }])->get();

        $pelanggan = $this->applyFilters($pelanggan, $cabangId, $omsetRange, $kedatanganRange);

        $pelanggan = $pelanggan->filter(function ($p) use ($endDate, $bulan, $tahun, $type) {
            if ($type == 'perbulan') {
                $kunjunganFiltered = $p->kunjungans->filter(function($k) use ($bulan, $tahun) {
                    return $k->tanggal_kunjungan->month == $bulan && $k->tanggal_kunjungan->year == $tahun;
                });
                $p->tgl_kunjungan = $kunjunganFiltered->sortByDesc('tanggal_kunjungan')->first()?->tanggal_kunjungan->format('Y-m-d') ?? '-';
                return $kunjunganFiltered->count() > 0;
            } elseif ($type == 'pertahun') {
                $kunjunganFiltered = $p->kunjungans->filter(function($k) use ($tahun) {
                    return $k->tanggal_kunjungan->year == $tahun;
                });
                $p->tgl_kunjungan = $kunjunganFiltered->sortByDesc('tanggal_kunjungan')->first()?->tanggal_kunjungan->format('Y-m-d') ?? '-';
                return $kunjunganFiltered->count() > 0;
            } else {
                $p->tgl_kunjungan = $p->kunjungans->sortByDesc('tanggal_kunjungan')->first()?->tanggal_kunjungan->format('Y-m-d') ?? '-';
                return true;
            }
        });

        $pelanggan = $this->applySorting($pelanggan, $sort, $direction);

        $perPage = 30;
        $page = $request->input('page', 1);
        $sliced = $pelanggan->slice(($page - 1) * $perPage, $perPage)->values();
        $pelangganPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $sliced,
            $pelanggan->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('pelanggan.index', [
            'pelanggan' => $pelangganPaginator,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'type' => $type,
            'search' => $search,
            'cabang_id' => $cabangId,
            'omset_range' => $omsetRange,
            'kedatangan_range' => $kedatanganRange,
            'cabangs' => $cabangs,
            'sort' => $sort,
            'direction' => $direction,
            'searchMode' => false
        ]);
    }

    private function applyFilters($pelanggan, $cabangId, $omsetRange, $kedatanganRange)
    {
        if ($cabangId) {
            $pelanggan = $pelanggan->where('cabang_id', $cabangId);
        }

        if ($omsetRange !== null && $omsetRange !== '') {
            $pelanggan = $pelanggan->filter(function ($p) use ($omsetRange) {
                $totalBiaya = $p->total_biaya ?? $p->kunjungans->sum('biaya');
                switch ($omsetRange) {
                    case '0':
                        return $totalBiaya < 1000000;
                    case '1':
                        return $totalBiaya >= 1000000 && $totalBiaya < 4000000;
                    case '2':
                        return $totalBiaya >= 4000000;
                    default:
                        return true;
                }
            });
        }

        if ($kedatanganRange !== null && $kedatanganRange !== '') {
            $pelanggan = $pelanggan->filter(function ($p) use ($kedatanganRange) {
                $totalKedatangan = $p->total_kedatangan ?? $p->kunjungans->count();
                switch ($kedatanganRange) {
                    case '0':
                        return $totalKedatangan <= 2;
                    case '1':
                        return $totalKedatangan >= 3 && $totalKedatangan <= 4;
                    case '2':
                        return $totalKedatangan > 4;
                    default:
                        return true;
                }
            });
        }

        return $pelanggan;
    }

    private function applySorting($pelanggan, $sort, $direction)
    {
        if ($sort == 'pid') {
            $pelanggan = $direction == 'asc' ? $pelanggan->sortBy('pid') : $pelanggan->sortByDesc('pid');
        } elseif ($sort == 'nama') {
            $pelanggan = $direction == 'asc' 
                ? $pelanggan->sortBy(function($p) { return strtolower($p->nama); }) 
                : $pelanggan->sortByDesc(function($p) { return strtolower($p->nama); });
        } elseif ($sort == 'tgl_kunjungan') {
            $pelanggan = $direction == 'asc' ? $pelanggan->sortBy('tgl_kunjungan') : $pelanggan->sortByDesc('tgl_kunjungan');
        } elseif ($sort == 'class') {
            $pelanggan = $direction == 'asc' 
                ? $pelanggan->sortBy('total_biaya') 
                : $pelanggan->sortByDesc('total_biaya');
        }

        return $pelanggan;
    }

    public function import(Request $request)
    {
        $userId = Auth::check() ? Auth::user()->id : 'guest';
        Log::info('Import process started', ['user' => $userId]);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt'
        ], [
            'file.required' => 'File Excel/CSV wajib diupload.',
            'file.file' => 'File harus berupa file.',
            'file.mimes' => 'File harus berupa format Excel (xlsx, xls) atau CSV.'
        ]);

        try {
            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());
            Log::info('File uploaded', ['filename' => $file->getClientOriginalName(), 'size' => $file->getSize(), 'extension' => $extension]);
            
            if ($extension === 'csv' || $extension === 'txt') {
                $rows = $this->readCsvFile($file);
            } else {
                $rows = Excel::toArray(null, $file);
            }

            $errors = [];
            $rowNumber = 1;
            $totalRows = 0;
            $validRows = 0;
            
            if (empty($rows) || empty($rows[0])) {
                Log::warning('Empty or invalid file');
                return back()->with('error', 'File kosong atau tidak valid.');
            }

            Log::info('Excel file read successfully', ['sheet_count' => count($rows), 'first_sheet_rows' => count($rows[0])]);
            
            $cabangs = Cabang::all()->keyBy('kode');
            
            foreach ($rows[0] as $row) {
                $rowNumber++;
                
                if ($rowNumber == 2 && count($row) > 0 && strtolower(trim($row[0] ?? '')) == 'no') {
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
                        $errors[] = "Baris {$rowNumber}: PID {$pid} sudah terdaftar dengan nama '{$dbNama}'. Data Excel nama '{$nama}' tidak cocok.";
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
                return back()->with('error', 'Import gagal! Beberapa data tidak cocok dengan database.')
                            ->with('import_errors', $errors);
            }
            
            if ($validRows === 0) {
                Log::warning('No valid rows to import');
                return back()->with('error', "Tidak ada data valid untuk diimport.");
            }

            Log::info('Starting import', ['valid_rows' => $validRows, 'file_type' => $extension]);
            
            if ($extension === 'csv' || $extension === 'txt') {
                $this->processCsvImport($rows[0]);
            } else {
                Excel::import(new KunjunganImport, $file);
            }
            
            Log::info('Import completed successfully');
            return back()->with('success', "Import berhasil! $validRows data telah diproses.");

        } catch (\Exception $e) {
            Log::error('Import exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Terjadi kesalahan saat import: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $bulan = $request->bulan ?? date('m');
        $tahun = $request->tahun ?? date('Y');
        $type = $request->type ?? 'perbulan';
        $search = $request->search;
        return Excel::download(new \App\Exports\PelangganExport($bulan, $tahun, $type, $search), 'pelanggan_'.$bulan.'_'.$tahun.'.xlsx');
    }

    public function create()
    {
        $cabangs = Cabang::all();
        return view('pelanggan.create', compact('cabangs'));
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

    public function destroy($id)
    {
        $pelanggan = Pelanggan::findOrFail($id);
        $pelanggan->delete();

        return redirect()->route('dashboard')->with('success', 'Pelanggan berhasil dihapus');
    }

    public function show($id)
    {
        $pelanggan = Pelanggan::with(['kunjungans', 'cabang', 'classHistories.changedBy'])->findOrFail($id);
        $totalTransaksi = $pelanggan->kunjungans->sum('biaya');
        $kunjungans = $pelanggan->kunjungans->sortByDesc('tanggal_kunjungan');
        $classHistories = $pelanggan->classHistories;
        
        return view('pelanggan.show', compact('pelanggan', 'kunjungans', 'totalTransaksi', 'classHistories'));
    }


    private function processCsvImport(array $rows): void
    {
        $processedCount = 0;
        $cabangs = Cabang::all()->keyBy('kode');
        
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            
            if ($index === 0 && count($row) > 0 && strtolower(trim($row[0] ?? '')) === 'no') {
                continue;
            }
            
            if (count($row) < 10) {
                continue;
            }
            
            $no = isset($row[0]) ? (int) $row[0] : null;
            $nama = trim($row[1] ?? '');
            $tanggalKedatangan = $row[3] ?? null;
            $biaya = $row[4] ?? null;
            $noTelp = trim($row[5] ?? '');
            $dob = $row[6] ?? null;
            $pid = trim($row[7] ?? '');
            $alamat = trim($row[8] ?? '');
            $kota = trim($row[9] ?? '');
            
            if (empty($pid) || empty($nama)) {
                continue;
            }
            
            $cabangKode = strtoupper(substr($pid, 0, 2));
            if (!isset($cabangs[$cabangKode])) {
                continue;
            }
            $cabang = $cabangs[$cabangKode];
            
            $tanggal = $this->parseCsvDate($tanggalKedatangan);
            if (!$tanggal) {
                continue;
            }
            
            $dobDate = $this->parseCsvDate($dob);
            $biayaValue = $this->parseCsvBiaya($biaya);
            if ($biayaValue === null) {
                continue;
            }
            
            DB::transaction(function () use ($no, $pid, $nama, $noTelp, $dobDate, $alamat, $kota, $cabang, $tanggal, $biayaValue, &$processedCount) {
                $pelanggan = Pelanggan::firstOrNew(['pid' => $pid]);
                
                $pelanggan->cabang_id = $cabang->id;
                $pelanggan->nama = $nama;
                $pelanggan->no_telp = $noTelp;
                $pelanggan->dob = $dobDate;
                $pelanggan->alamat = $alamat;
                $pelanggan->kota = $kota;
                $pelanggan->save();
                
                Kunjungan::create([
                    'no' => $no,
                    'pelanggan_id' => $pelanggan->id,
                    'cabang_id' => $cabang->id,
                    'tanggal_kunjungan' => $tanggal,
                    'biaya' => $biayaValue
                ]);
                
                $pelanggan->updateStats();
                $processedCount++;
            });
        }
        
        Log::info('CSV import completed', ['processed' => $processedCount]);
    }
    
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
}
