<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Pelanggan;
use App\Imports\KunjunganImport;
use Maatwebsite\Excel\Facades\Excel;


class PelangganController extends Controller
{
    public function store(Request $request)
    {
        $inputs = $request->inputs ?? [];
        $errors = [];
        $createdCount = 0;

        // First, collect all NIKs and check for duplicates within the input
        $niks = [];
        $duplicateInInput = [];
        foreach ($inputs as $index => $input) {
            $nik = trim($input['nik'] ?? '');
            if (!empty($nik)) {
                if (in_array($nik, $niks)) {
                    $duplicateInInput[$index] = $nik;
                }
                $niks[$index] = $nik;
            }
        }

        // Get all existing NIKs from database
        $existingNik = Pelanggan::whereIn('nik', array_values($niks))->pluck('nik')->toArray();

        foreach ($inputs as $index => $input) {
            // Preprocess biaya to remove dots
            $input['biaya'] = str_replace('.', '', $input['biaya'] ?? '');
            $nik = trim($input['nik'] ?? '');

            // Check for duplicate NIK within the input
            if (isset($duplicateInInput[$index])) {
                $errors[$index][] = 'NIK ' . $nik . ' sudah digunakan di dalam formulir ini.';
                continue;
            }

            // Check for existing NIK in database
            if (in_array($nik, $existingNik)) {
                $pelanggan = Pelanggan::where('nik', $nik)->first();
                $errors[$index][] = 'NIK ' . $nik . ' sudah terdaftar atas nama "' . $pelanggan->nama . '".';
                continue;
            }

            $validator = Validator::make($input, [
                'nik' => 'required',
                'nama' => 'required',
                'alamat' => 'nullable|string',
                'biaya' => 'required|numeric|min:0',
                'tanggal_kunjungan' => 'required|date',
            ], [
                'nik.required' => 'NIK wajib diisi.',
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
            } else {
                DB::transaction(function () use ($input, &$createdCount) {
                    $pelanggan = Pelanggan::create([
                        'nik' => $input['nik'],
                        'nama' => $input['nama'],
                        'alamat' => $input['alamat'] ?? null,
                        'class' => 'Basic', // temporary, will update after calculating
                    ]);

                    $pelanggan->kunjungans()->create([
                        'tanggal_kunjungan' => $input['tanggal_kunjungan'],
                        'biaya' => $input['biaya'],
                    ]);

                    // Calculate total and update class
                    $total = $pelanggan->kunjungans()->sum('biaya');
                    $pelanggan->update(['class' => $this->getClass($total)]);
                    $createdCount++;
                });
            }
        }

        // Collect only the failed inputs (rows with errors)
        $failedInputs = [];
        foreach ($errors as $index => $errorMessages) {
            if (isset($inputs[$index])) {
                $failedInputs[$index] = $inputs[$index];
            }
        }

        if ($createdCount > 0 && empty($errors)) {
            return redirect()->route('dashboard')->with('success', $createdCount . ' Pelanggan berhasil ditambahkan');
        } elseif ($createdCount > 0 && !empty($errors)) {
            return redirect()->route('pelanggan.create')
                ->with('success', $createdCount . ' data berhasil disimpan.')
                ->with('error', count($errors) . ' data gagal disimpan. Silakan periksa kesalahan di bawah.')
                ->with('errors', $errors)
                ->with('inputs', $failedInputs);
        } else {
            return redirect()->route('pelanggan.create')
                ->with('error', 'Semua data gagal disimpan. Silakan periksa kesalahan di bawah.')
                ->with('errors', $errors)
                ->with('inputs', $failedInputs);
        }
    }

    public function index(Request $request)
    {
        $bulan = $request->bulan ?? date('m');
        $tahun = $request->tahun ?? date('Y');
        $type = $request->type ?? 'perbulan'; // perbulan, pertahun, semua
        $search = $request->search;
        $sort = $request->sort ?? 'nama';
        $direction = $request->direction ?? 'asc';

        // Jika ada pencarian pelanggan, tampilkan riwayat klasifikasi
        if ($search) {
            $pelanggan = Pelanggan::where('nik', 'like', '%' . $search . '%')
                                  ->orWhere('nama', 'like', '%' . $search . '%')
                                  ->first();

            if (!$pelanggan) {
                return view('pelanggan.index', [
                    'pelanggan' => collect(),
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'type' => $type,
                    'search' => $search,
                    'history' => null,
                    'sort' => $sort,
                    'direction' => $direction
                ]);
            }

            // Ambil semua kunjungan pelanggan, urutkan berdasarkan tanggal
            $kunjungans = $pelanggan->kunjungans()->orderBy('tanggal_kunjungan')->get();

            // Hitung kumulatif per bulan
            $history = [];
            $cumulative = 0;
            $currentMonth = null;
            $monthlyTotal = 0;

            foreach ($kunjungans as $k) {
                $monthKey = $k->tanggal_kunjungan->format('Y-m');
                if ($currentMonth !== $monthKey) {
                    if ($currentMonth) {
                        $history[] = [
                            'bulan' => $currentMonth,
                            'total_bulanan' => $monthlyTotal,
                            'total_kumulatif' => $cumulative,
                            'class' => $this->getClass($cumulative),
                            'kunjungan_terakhir' => $k->tanggal_kunjungan->format('Y-m-d')
                        ];
                    }
                    $currentMonth = $monthKey;
                    $monthlyTotal = 0;
                }
                $monthlyTotal += $k->biaya;
                $cumulative += $k->biaya;
            }

            // Tambahkan bulan terakhir
            if ($currentMonth) {
                $history[] = [
                    'bulan' => $currentMonth,
                    'total_bulanan' => $monthlyTotal,
                    'total_kumulatif' => $cumulative,
                    'class' => $this->getClass($cumulative),
                    'kunjungan_terakhir' => $kunjungans->last()->tanggal_kunjungan->format('Y-m-d')
                ];
            }

            return view('pelanggan.index', [
                'pelanggan' => collect([$pelanggan]),
                'bulan' => $bulan,
                'tahun' => $tahun,
                'type' => $type,
                'search' => $search,
                'history' => $history,
                'sort' => $sort,
                'direction' => $direction
            ]);
        }

        // Tentukan tanggal akhir periode berdasarkan type
        $endDate = null;
        if ($type == 'perbulan') {
            $endDate = \Carbon\Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();
        } elseif ($type == 'pertahun') {
            $endDate = \Carbon\Carbon::createFromDate($tahun, 12, 31);
        } // untuk 'semua', endDate tetap null

        $pelanggan = Pelanggan::with(['kunjungans' => function($q) use ($endDate) {
            if ($endDate) {
                $q->where('tanggal_kunjungan', '<=', $endDate);
            }
        }])->get();

        // Hitung total kumulatif dan filter
        $pelanggan = $pelanggan->filter(function ($p) use ($endDate, $bulan, $tahun, $type) {
            $p->total = $p->kunjungans->sum('biaya');
            $p->class = $this->getClass($p->total);

            // Ambil kunjungan terakhir di periode
            if ($type == 'perbulan') {
                $kunjunganFiltered = $p->kunjungans->filter(function($k) use ($bulan, $tahun) {
                    return $k->tanggal_kunjungan->month == $bulan && $k->tanggal_kunjungan->year == $tahun;
                });
                $p->tgl_kunjungan = $kunjunganFiltered->sortByDesc('tanggal_kunjungan')->first()?->tanggal_kunjungan->format('Y-m-d') ?? '-';
                
                // Hanya tampilkan pelanggan yang memiliki kunjungan di bulan tersebut
                return $kunjunganFiltered->count() > 0;
            } elseif ($type == 'pertahun') {
                $kunjunganFiltered = $p->kunjungans->filter(function($k) use ($tahun) {
                    return $k->tanggal_kunjungan->year == $tahun;
                });
                $p->tgl_kunjungan = $kunjunganFiltered->sortByDesc('tanggal_kunjungan')->first()?->tanggal_kunjungan->format('Y-m-d') ?? '-';
                
                // Hanya tampilkan pelanggan yang memiliki kunjungan di tahun tersebut
                return $kunjunganFiltered->count() > 0;
            } else {
                $p->tgl_kunjungan = $p->kunjungans->sortByDesc('tanggal_kunjungan')->first()?->tanggal_kunjungan->format('Y-m-d') ?? '-';
                
                // Untuk semua data, tampilkan pelanggan yang pernah посещает
                return $p->total > 0;
            }
        });

        // Sorting
        if ($sort == 'nik') {
            $pelanggan = $direction == 'asc' ? $pelanggan->sortBy('nik') : $pelanggan->sortByDesc('nik');
        } elseif ($sort == 'nama') {
            $pelanggan = $direction == 'asc' ? $pelanggan->sortBy(function($p) { return strtolower($p->nama); }) : $pelanggan->sortByDesc(function($p) { return strtolower($p->nama); });
        } elseif ($sort == 'tgl_kunjungan') {
            $pelanggan = $direction == 'asc' ? $pelanggan->sortBy('tgl_kunjungan') : $pelanggan->sortByDesc('tgl_kunjungan');
        } elseif ($sort == 'class') {
            $pelanggan = $direction == 'asc' ? $pelanggan->sortBy('total') : $pelanggan->sortByDesc('total');
        }

        // Manual Pagination
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
            'sort' => $sort,
            'direction' => $direction
        ]);
    }


    public function import(Request $request)
    {
        Log::info('Import process started', ['user' => auth()->user()->id ?? 'guest']);
        
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ], [
            'file.required' => 'File Excel/CSV wajib diupload.',
            'file.file' => 'File harus berupa file.',
            'file.mimes' => 'File harus berupa format Excel (xlsx, xls) atau CSV.'
        ]);

        try {
            $file = $request->file('file');
            Log::info('File uploaded', ['filename' => $file->getClientOriginalName(), 'size' => $file->getSize()]);
            
            // Read the Excel file first to validate NIKs (without importing)
            $rows = Excel::toArray(null, $file);
            
            $errors = [];
            $rowNumber = 1;
            $totalRows = 0;
            $validRows = 0;
            
            if (empty($rows) || empty($rows[0])) {
                Log::warning('Empty or invalid file');
                return back()->with('error', 'File kosong atau tidak valid. Pastikan format file benar (Excel atau CSV).');
            }

            Log::info('Excel file read successfully', ['sheet_count' => count($rows), 'first_sheet_rows' => count($rows[0])]);
            
            foreach ($rows[0] as $row) {
                $rowNumber++;
                
                // Skip header row (row 1) - already skipped by startRow() in import class but we read raw
                if ($rowNumber == 2 && count($row) > 0 && strtolower(trim($row[0] ?? '')) == 'nik') {
                    continue;
                }
                
                if (count($row) < 5) {
                    Log::debug("Row $rowNumber skipped: insufficient columns", ['column_count' => count($row)]);
                    continue;
                }
                
                $nik = trim($row[0] ?? '');
                $nama = trim($row[1] ?? '');
                $alamat = trim($row[2] ?? '');
                
                if (empty($nik) || empty($nama)) {
                    Log::debug("Row $rowNumber skipped: empty NIK or nama");
                    continue;
                }
                
                $totalRows++;
                
                // Check if NIK exists in database
                $pelanggan = Pelanggan::where('nik', $nik)->first();
                
                if ($pelanggan) {
                    // NIK exists, check if nama and alamat match
                    $dbNama = trim($pelanggan->nama ?? '');
                    $dbAlamat = trim($pelanggan->alamat ?? '');
                    
                    // If either nama or alamat is different, validation error
                    if (strtolower($nama) !== strtolower($dbNama) || strtolower($alamat) !== strtolower($dbAlamat)) {
                        $errors[] = "Baris $rowNumber: NIK $nik sudah terdaftar dengan nama '$dbNama'. Data Excel nama '$nama' dan alamat '$alamat' tidak cocok.";
                        Log::warning("Validation failed for row $rowNumber", [
                            'nik' => $nik,
                            'excel_nama' => $nama,
                            'db_nama' => $dbNama,
                            'excel_alamat' => $alamat,
                            'db_alamat' => $dbAlamat
                        ]);
                    } else {
                        $validRows++;
                        Log::debug("Row $rowNumber validated: NIK exists but matches", ['nik' => $nik]);
                    }
                } else {
                    $validRows++;
                    Log::debug("Row $rowNumber validated: new NIK", ['nik' => $nik]);
                }
            }
            
            Log::info('Validation completed', ['total_rows' => $totalRows, 'valid_rows' => $validRows, 'errors' => count($errors)]);
            
            // If there are ANY errors, fail ALL import
            if (!empty($errors)) {
                Log::warning('Import failed due to validation errors', ['error_count' => count($errors)]);
                return back()->with('error', 'Import gagal! Beberapa data tidak cocok dengan database. Silakan perbaiki file Excel terlebih dahulu:')
                            ->with('import_errors', $errors);
            }
            
            if ($validRows === 0) {
                Log::warning('No valid rows to import');
                return back()->with('error', 'Tidak ada data valid untuk diimport. Pastikan file memiliki kolom: NIK, Nama, Alamat, Tanggal Kunjungan, Biaya');
            }

            // If no errors, proceed with import
            Log::info('Starting Excel import', ['valid_rows' => $validRows]);
            Excel::import(new KunjunganImport, $file);
            
            Log::info('Import completed successfully');
            return back()->with('success', "Import berhasil! $validRows data telah diproses.");
            
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errorMessages = [];
            
            foreach ($failures as $failure) {
                $errorMessages[] = "Baris {$failure->row()}: " . implode(', ', $failure->errors());
            }
            
            Log::error('Excel validation exception', ['errors' => $errorMessages]);
            return back()->with('error', 'Validasi Excel gagal:')
                        ->with('import_errors', $errorMessages);
                        
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
        return view('pelanggan.create');
    }

    public function edit($id)
    {
        $pelanggan = Pelanggan::findOrFail($id);
        return view('pelanggan.edit', compact('pelanggan'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nik' => 'required|unique:pelanggans,nik,' . $id,
            'nama' => 'required',
            'alamat' => 'nullable|string',
        ]);

        $pelanggan = Pelanggan::findOrFail($id);
        $pelanggan->update($request->only(['nik', 'nama', 'alamat']));

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
        $pelanggan = Pelanggan::with('kunjungans')->findOrFail($id);
        
        // Hitung total transaksi
        $totalTransaksi = $pelanggan->kunjungans->sum('biaya');
        
        // Urutkan kunjungan dari yang terbaru
        $kunjungans = $pelanggan->kunjungans->sortByDesc('tanggal_kunjungan');
        
        return view('pelanggan.show', compact('pelanggan', 'kunjungans', 'totalTransaksi'));
    }

    private function getClass($total)
    {
        if ($total >= 5000000) {
            return 'Platinum';
        }
        if ($total >= 1000000) {
            return 'Gold';
        }
        if ($total >= 100000) {
            return 'Silver';
        }
        return 'Basic';
    }

}
