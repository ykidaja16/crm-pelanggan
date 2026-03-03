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
                        $visitDate = \Carbon\Carbon::parse($input['tanggal_kunjungan']);
                        
                        // Tambah kunjungan baru
                        $pelanggan->kunjungans()->create([
                            'cabang_id' => $input['existing_cabang_id'] ?? $pelanggan->cabang_id,
                            'tanggal_kunjungan' => $input['tanggal_kunjungan'],
                            'biaya' => $input['biaya'],
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

                    $pelanggan->kunjungans()->create([
                        'cabang_id' => $input['cabang_id'],
                        'tanggal_kunjungan' => $input['tanggal_kunjungan'],
                        'biaya' => $input['biaya'],
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
        $bulan          = $request->bulan;
        $tahun          = $request->tahun;
        $type           = $request->type;
        $search         = $request->search;
        $sort           = $request->sort ?? 'nama';
        $direction      = in_array($request->direction, ['asc', 'desc']) ? $request->direction : 'asc';
        $cabangId       = $request->cabang_id;
        $omsetRange     = $request->omset_range;
        $kedatanganRange = $request->kedatangan_range;
        $kelas          = $request->kelas;

        $cabangs = Cabang::all();

        // Set default periode ke "semua" jika tidak ada filter
        // Tapi tampilkan data kosong saat pertama kali masuk (belum klik filter)
        if (!$type && !$search) {
            $type  = 'semua';
            $bulan = null;
            $tahun = null;
            
            // Return view dengan data kosong (belum klik filter)
            return view('pelanggan.index', [
                'pelanggan'        => collect(),
                'bulan'            => null,
                'tahun'            => null,
                'type'             => 'semua',
                'search'           => null,
                'cabang_id'        => null,
                'omset_range'      => null,
                'kedatangan_range' => null,
                'kelas'            => null,
                'cabangs'          => $cabangs,
                'sort'             => $sort,
                'direction'        => $direction,
                'searchMode'       => false,
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
            $tglSubquery = "SELECT MAX(tanggal_kunjungan) FROM kunjungans
                WHERE kunjungans.pelanggan_id = pelanggans.id
                AND MONTH(tanggal_kunjungan) = {$bulan}
                AND YEAR(tanggal_kunjungan) = {$tahun}";
        } elseif ($type === 'pertahun' && $tahun) {
            $tglSubquery = "SELECT MAX(tanggal_kunjungan) FROM kunjungans
                WHERE kunjungans.pelanggan_id = pelanggans.id
                AND YEAR(tanggal_kunjungan) = {$tahun}";
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
            'cabangs'          => $cabangs,
            'sort'             => $sort,
            'direction'        => $direction,
            'searchMode'       => (bool) $search,
        ]);
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
            $rowNumber = 0;
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

    public function downloadTemplate()
    {
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
            'Kota'
        ];


        // Data dummy sebagai contoh
        $data = [
            [1, 'Budi Santoso', 3, '2024-01-15', 2500000, '081234567890', '1990-05-20', 'JK00001', 'Jl. Sudirman No. 123', 'Jakarta'],
            [2, 'Siti Aminah', 5, '2024-02-10', 4500000, '082345678901', '1985-08-12', 'BD00002', 'Jl. Ahmad Yani No. 45', 'Bandung'],
            [3, 'Ahmad Wijaya', 2, '2024-03-05', 1200000, '083456789012', '1992-11-03', 'SB00003', 'Jl. Gatot Subroto No. 78', 'Surabaya'],
            [4, 'Dewi Kusuma', 4, '2024-01-28', 3800000, '084567890123', '1988-04-25', 'YK00004', 'Jl. Malioboro No. 12', 'Yogyakarta'],
            [5, 'Eko Prasetyo', 1, '2024-02-20', 850000, '085678901234', '1995-09-18', 'ML00005', 'Jl. Ijen No. 56', 'Malang'],
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
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

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

    /**
     * Hapus multiple pelanggan sekaligus
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->back()->with('error', 'Tidak ada pelanggan yang dipilih.');
        }

        // Pastikan semua ID valid (integer)
        $ids = array_filter(array_map('intval', $ids));

        if (empty($ids)) {
            return redirect()->back()->with('error', 'ID pelanggan tidak valid.');
        }

        $count = Pelanggan::whereIn('id', $ids)->count();
        Pelanggan::whereIn('id', $ids)->delete();

        return redirect()->back()->with('success', "{$count} pelanggan berhasil dihapus.");
    }

    /**
     * Export multiple pelanggan terpilih ke Excel
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
            ->orderBy('tanggal_kunjungan', 'asc')
            ->paginate(10, ['*'], 'kunjungan_page');
        
        // Pagination untuk riwayat perubahan kelas (10 per halaman)
        $classHistories = $pelanggan->classHistories()
            ->orderBy('changed_at', 'asc')
            ->paginate(10, ['*'], 'class_page');
        
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
                $isNewPelanggan = !Pelanggan::where('pid', $pid)->exists();
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
                
                // Update stats dengan tanggal kunjungan
                $pelanggan->updateStats($tanggal);
                
                // Jika pelanggan baru, catat kelas awal dengan tanggal kunjungan
                if ($isNewPelanggan) {
                    $pelanggan->recordInitialClass($tanggal);
                }
                
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
