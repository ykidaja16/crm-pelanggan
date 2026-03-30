<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\Pelanggan;
use App\Models\Cabang;
use App\Models\ActivityLog;
use App\Models\ApprovalRequest;
use App\Models\KelompokPelanggan;
use App\Models\User;

/**
 * Controller untuk mengelola data pelanggan (Core CRUD)
 *
 * Import/Export  → PelangganImportExportController
 * Kunjungan CRUD → KunjunganController
 */
class PelangganController extends Controller
{
    /**
     * Menyimpan data pelanggan baru atau kunjungan ke pelanggan existing.
     */
    public function store(Request $request)
    {
        $inputs       = $request->inputs ?? [];
        $errors       = [];
        $createdCount = 0;
        $updatedCount = 0;
        $cabangs      = Cabang::all()->keyBy('id');

        // Ambil hak akses cabang user (empty = IT = semua cabang)
        /** @var User $user */
        $user = Auth::user();
        $accessibleCabangIds = $user->getAccessibleCabangIds();

        foreach ($inputs as $index => $input) {
            $mode           = $input['mode'] ?? 'new';
            $input['biaya'] = str_replace('.', '', $input['biaya'] ?? '');

            if ($mode === 'existing') {
                // Blokir jika pelanggan yang dipilih adalah pelanggan khusus
                $existingCheck = Pelanggan::find($input['existing_pelanggan_id'] ?? null);
                if ($existingCheck && $existingCheck->is_pelanggan_khusus) {
                    $errors[$index][] = "Pelanggan ini adalah pelanggan khusus. Gunakan menu Pelanggan Khusus untuk menambah kunjungan.";
                    continue;
                }

                $validator = Validator::make($input, [
                    'existing_pelanggan_id' => 'required|exists:pelanggans,id',
                    'biaya'                 => 'required|numeric|min:0',
                    'tanggal_kunjungan'     => 'required|date',
                    'kelompok_pelanggan'    => 'required|in:mandiri,klinisi',
                ], [
                    'existing_pelanggan_id.required' => 'Pelanggan harus dipilih terlebih dahulu.',
                    'existing_pelanggan_id.exists'   => 'Pelanggan tidak ditemukan.',
                    'biaya.required'                 => 'Biaya wajib diisi.',
                    'biaya.numeric'                  => 'Biaya harus berupa angka.',
                    'biaya.min'                      => 'Biaya tidak boleh negatif.',
                    'tanggal_kunjungan.required'     => 'Tanggal Kunjungan wajib diisi.',
                    'tanggal_kunjungan.date'         => 'Tanggal Kunjungan tidak valid.',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->all() as $error) {
                        $errors[$index][] = $error;
                    }
                } else {
                    DB::transaction(function () use ($input, &$updatedCount) {
                        $pelanggan    = Pelanggan::find($input['existing_pelanggan_id']);
                        $visitDate    = \Carbon\Carbon::parse($input['tanggal_kunjungan']);
                        $kelompokKode = $input['kelompok_pelanggan'] ?? 'mandiri';
                        $kelompok     = KelompokPelanggan::where('kode', $kelompokKode)->first();

                        $pelanggan->kunjungans()->create([
                            'cabang_id'             => $input['existing_cabang_id'] ?? $pelanggan->cabang_id,
                            'tanggal_kunjungan'     => $input['tanggal_kunjungan'],
                            'biaya'                 => $input['biaya'],
                            'kelompok_pelanggan_id' => $kelompok?->id,
                            'total_kedatangan'      => 1,
                        ]);

                        $pelanggan->updateStats($visitDate);
                        $updatedCount++;
                    });
                }
            } else {
                $pid = trim($input['pid'] ?? '');

                if (empty($pid)) {
                    $errors[$index][] = 'PID wajib diisi.';
                    continue;
                }

                $validator = Validator::make($input, [
                    'pid'                => 'required|string',
                    'cabang_id'          => 'required|exists:cabangs,id',
                    'nama'               => 'required',
                    'no_telp'            => 'nullable|string',
                    'dob'                => 'nullable|date',
                    'alamat'             => 'nullable|string',
                    'kota'               => 'nullable|string',
                    'biaya'              => 'required|numeric|min:0',
                    'tanggal_kunjungan'  => 'required|date',
                    'kelompok_pelanggan' => 'required|in:mandiri,klinisi',
                ], [
                    'pid.required'               => 'PID wajib diisi.',
                    'cabang_id.required'         => 'Cabang wajib dipilih.',
                    'cabang_id.exists'           => 'Cabang tidak valid.',
                    'nama.required'              => 'Nama wajib diisi.',
                    'biaya.required'             => 'Biaya wajib diisi.',
                    'biaya.numeric'              => 'Biaya harus berupa angka.',
                    'biaya.min'                  => 'Biaya tidak boleh negatif.',
                    'tanggal_kunjungan.required' => 'Tanggal Kunjungan wajib diisi.',
                    'tanggal_kunjungan.date'     => 'Tanggal Kunjungan tidak valid.',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->all() as $error) {
                        $errors[$index][] = $error;
                    }
                    continue;
                }

                // Validasi akses cabang user (non-IT hanya bisa ke cabang yang diizinkan)
                if (!empty($accessibleCabangIds) && !in_array((int)($input['cabang_id'] ?? 0), $accessibleCabangIds)) {
                    $errors[$index][] = "Anda tidak memiliki akses ke cabang yang dipilih.";
                    continue;
                }

                $existingPelanggan = Pelanggan::where('pid', $pid)->first();
                if ($existingPelanggan) {
                    if ($existingPelanggan->is_pelanggan_khusus) {
                        $errors[$index][] = "PID {$pid} adalah pelanggan khusus. Gunakan menu Pelanggan Khusus untuk menambah kunjungan.";
                    } else {
                        $errors[$index][] = "PID {$pid} sudah terdaftar atas nama \"{$existingPelanggan->nama}\".";
                    }
                    continue;
                }

                $selectedCabang = $cabangs->get($input['cabang_id']);
                if ($selectedCabang) {
                    $pidPrefix  = strtoupper(substr($pid, 0, 2));
                    $cabangKode = strtoupper($selectedCabang->kode);
                    if ($pidPrefix !== $cabangKode) {
                        $errors[$index][] = "PID \"{$pid}\" tidak sesuai dengan cabang \"{$selectedCabang->nama}\". Prefix PID harus \"{$cabangKode}\".";
                        continue;
                    }
                }

                DB::transaction(function () use ($input, &$createdCount) {
                    $visitDate    = \Carbon\Carbon::parse($input['tanggal_kunjungan']);
                    $pelanggan    = Pelanggan::create([
                        'pid'       => $input['pid'],
                        'cabang_id' => $input['cabang_id'],
                        'nama'      => $input['nama'],
                        'no_telp'   => $input['no_telp'] ?? null,
                        'dob'       => $input['dob'] ?? null,
                        'alamat'    => $input['alamat'] ?? null,
                        'kota'      => $input['kota'] ?? null,
                        'class'     => 'Potensial',
                    ]);
                    $kelompokKode = $input['kelompok_pelanggan'] ?? 'mandiri';
                    $kelompok     = KelompokPelanggan::where('kode', $kelompokKode)->first();

                    $pelanggan->kunjungans()->create([
                        'cabang_id'             => $input['cabang_id'],
                        'tanggal_kunjungan'     => $input['tanggal_kunjungan'],
                        'biaya'                 => $input['biaya'],
                        'kelompok_pelanggan_id' => $kelompok?->id,
                        'total_kedatangan'      => 1,
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
     * API endpoint untuk mencari pelanggan berdasarkan PID (autocomplete).
     *
     * Query params:
     *   pid    - PID atau nama pelanggan yang dicari
     *   khusus - jika "1", hanya cari pelanggan khusus (is_pelanggan_khusus=1)
     *            dan kembalikan format flat untuk tab Pelanggan Lama di khusus.blade.php
     */
    public function searchByPid(Request $request)
    {
        $query  = $request->query('pid');
        $khusus = $request->query('khusus') === '1';

        if (empty($query)) {
            return response()->json($khusus ? null : ['found' => false]);
        }

        if ($khusus) {
            // Cari pelanggan khusus berdasarkan PID atau nama
            $pelanggan = Pelanggan::with('cabang')
                ->where('is_pelanggan_khusus', true)
                ->where(function ($q) use ($query) {
                    $q->where('pid', $query)
                      ->orWhere('nama', 'like', '%' . $query . '%');
                })
                ->first();

            if (!$pelanggan) {
                return response()->json(null);
            }

            return response()->json([
                'id'                  => $pelanggan->id,
                'pid'                 => $pelanggan->pid,
                'nama'                => $pelanggan->nama,
                'cabang_nama'         => $pelanggan->cabang?->nama ?? '-',
                'kategori_khusus'     => $pelanggan->kategori_khusus ?? '-',
                'total_kedatangan'    => $pelanggan->total_kedatangan ?? 0,
                'is_pelanggan_khusus' => true,
            ]);
        }

        // Mode default: cari by PID exact match
        $pelanggan = Pelanggan::with('cabang')->where('pid', $query)->first();

        if (!$pelanggan) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found'     => true,
            'pelanggan' => [
                'id'                  => $pelanggan->id,
                'pid'                 => $pelanggan->pid,
                'nama'                => $pelanggan->nama,
                'class'               => $pelanggan->class,
                'cabang_id'           => $pelanggan->cabang_id,
                'is_pelanggan_khusus' => (bool) $pelanggan->is_pelanggan_khusus,
            ],
            'cabang' => $pelanggan->cabang?->nama ?? '-',
        ]);
    }

    /**
     * Helper: ambil superadmin yang punya akses ke cabang tertentu.
     */
    private function getSuperadminsByCabang(int $cabangId): \Illuminate\Database\Eloquent\Collection
    {
        return User::whereHas('role', fn($q) => $q->where('name', 'Super Admin'))
            ->whereHas('cabangs', fn($q) => $q->where('cabangs.id', $cabangId))
            ->get();
    }

    /**
     * Menampilkan daftar pelanggan dengan filter dan sorting.
     */
    public function index(Request $request)
    {
        $bulan           = $request->filled('bulan') ? (int) $request->bulan : null;
        $tahun           = $request->filled('tahun') ? (int) $request->tahun : null;
        $type            = $request->type;
        $search          = $request->search;
        $sort            = $request->sort ?? 'nama';
        $direction       = in_array(strtolower((string) $request->direction), ['asc', 'desc'], true)
            ? strtolower((string) $request->direction) : 'asc';
        $cabangId        = $request->cabang_id;
        $omsetRange      = $request->omset_range;
        $kedatanganRange = $request->kedatangan_range;
        $kelas           = $request->kelas;
        $tanggal_mulai   = $request->tanggal_mulai;
        $tanggal_selesai = $request->tanggal_selesai;
        $kelompokPelanggan = $request->kelompok_pelanggan;
        $tipePelanggan   = $request->tipe_pelanggan;


        // Filter cabangs dropdown by accessible cabangs
        /** @var User $user */
        $user = Auth::user();
        $accessibleCabangIds = $user->getAccessibleCabangIds();
        $cabangs = empty($accessibleCabangIds)
            ? Cabang::all()
            : Cabang::whereIn('id', $accessibleCabangIds)->get();

        // Tampilkan data kosong saat pertama kali masuk (belum klik filter)
        if (!$type && !$search && !$kelompokPelanggan && !$tipePelanggan
            && !$cabangId && !$kelas && !$omsetRange && !$kedatanganRange) {
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
        }

        // Filter by accessible cabangs (jika user punya batasan cabang)
        if (!empty($accessibleCabangIds)) {
            // Jika user memilih cabang tertentu, pastikan cabang itu ada di accessible list
            if ($cabangId && !in_array((int)$cabangId, $accessibleCabangIds)) {
                $cabangId = null;
            }
        }

        if ($type === 'perbulan' && !$bulan) {
            $bulan = date('m');
        }
        if ($type && $type !== 'semua' && !$tahun) {
            $tahun = date('Y');
        }

        // Subquery untuk tgl_kunjungan terakhir sesuai periode
        // Subquery untuk total_biaya & total_kedatangan kumulatif sesuai periode
        if ($type === 'perbulan' && $bulan && $tahun) {
            $safeBulan   = (int) $bulan;
            $safeTahun   = (int) $tahun;
            $tglSubquery = "SELECT MAX(tanggal_kunjungan) FROM kunjungans
                WHERE kunjungans.pelanggan_id = pelanggans.id
                AND MONTH(tanggal_kunjungan) = {$safeBulan}
                AND YEAR(tanggal_kunjungan) = {$safeTahun}";
            // Kumulatif: semua kunjungan s.d. akhir bulan yang dipilih
            $endOfPeriod      = Carbon::create($safeTahun, $safeBulan, 1)->endOfMonth()->format('Y-m-d');
            $biayaSubquery    = "SELECT COALESCE(SUM(biaya), 0) FROM kunjungans
                WHERE kunjungans.pelanggan_id = pelanggans.id
                AND DATE(tanggal_kunjungan) <= '{$endOfPeriod}'";
            $kedatanganSubquery = "SELECT COUNT(*) FROM kunjungans
                WHERE kunjungans.pelanggan_id = pelanggans.id
                AND DATE(tanggal_kunjungan) <= '{$endOfPeriod}'";
        } elseif ($type === 'pertahun' && $tahun) {
            $safeTahun   = (int) $tahun;
            $tglSubquery = "SELECT MAX(tanggal_kunjungan) FROM kunjungans
                WHERE kunjungans.pelanggan_id = pelanggans.id
                AND YEAR(tanggal_kunjungan) = {$safeTahun}";
            // Kumulatif: semua kunjungan s.d. akhir tahun yang dipilih
            $endOfPeriod      = Carbon::create($safeTahun, 12, 31)->format('Y-m-d');
            $biayaSubquery    = "SELECT COALESCE(SUM(biaya), 0) FROM kunjungans
                WHERE kunjungans.pelanggan_id = pelanggans.id
                AND DATE(tanggal_kunjungan) <= '{$endOfPeriod}'";
            $kedatanganSubquery = "SELECT COUNT(*) FROM kunjungans
                WHERE kunjungans.pelanggan_id = pelanggans.id
                AND DATE(tanggal_kunjungan) <= '{$endOfPeriod}'";
        } elseif ($type === 'range' && $tanggal_mulai && $tanggal_selesai) {
            $tglSubquery = "SELECT MAX(tanggal_kunjungan) FROM kunjungans
                WHERE kunjungans.pelanggan_id = pelanggans.id
                AND tanggal_kunjungan BETWEEN '{$tanggal_mulai}' AND '{$tanggal_selesai}'";
            $biayaSubquery    = "SELECT COALESCE(SUM(biaya), 0) FROM kunjungans
                WHERE kunjungans.pelanggan_id = pelanggans.id
                AND tanggal_kunjungan BETWEEN '{$tanggal_mulai}' AND '{$tanggal_selesai}'";
            $kedatanganSubquery = "SELECT COUNT(*) FROM kunjungans
                WHERE kunjungans.pelanggan_id = pelanggans.id
                AND tanggal_kunjungan BETWEEN '{$tanggal_mulai}' AND '{$tanggal_selesai}'";
            $endOfPeriod = $tanggal_selesai;
        } else {
            $tglSubquery        = "SELECT MAX(tanggal_kunjungan) FROM kunjungans
                WHERE kunjungans.pelanggan_id = pelanggans.id";
            $biayaSubquery      = null;
            $kedatanganSubquery = null;
        }


        $query = Pelanggan::with('cabang')
            ->select('pelanggans.*')
            ->selectRaw("({$tglSubquery}) as tgl_kunjungan");

        // Tambahkan subquery kumulatif jika filter periode aktif
        if ($biayaSubquery) {
            $query->selectRaw("({$biayaSubquery}) as biaya_periode")
                  ->selectRaw("({$kedatanganSubquery}) as kedatangan_periode");
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('pid', 'like', '%' . $search . '%')
                  ->orWhere('nama', 'like', '%' . $search . '%');
            });
        }

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
            } elseif ($type === 'range' && $tanggal_mulai && $tanggal_selesai) {
                $query->whereHas('kunjungans', function ($q) use ($tanggal_mulai, $tanggal_selesai) {
                    $q->whereBetween('tanggal_kunjungan', [$tanggal_mulai, $tanggal_selesai]);
                });
            }
        }


        // Filter by accessible cabangs
        if (!empty($accessibleCabangIds)) {
            if ($cabangId) {
                $query->where('cabang_id', $cabangId);
            } else {
                $query->whereIn('cabang_id', $accessibleCabangIds);
            }
        } elseif ($cabangId) {
            $query->where('cabang_id', $cabangId);
        }

        if ($kelas) {
            $query->where('class', $kelas);
        }

        if ($omsetRange !== null && $omsetRange !== '') {
            switch ($omsetRange) {
                case '0': $query->where('total_biaya', '<', 1000000); break;
                case '1': $query->whereBetween('total_biaya', [1000000, 4000000]); break;
                case '2': $query->where('total_biaya', '>=', 4000000); break;
            }
        }

        if ($kedatanganRange !== null && $kedatanganRange !== '') {
            switch ($kedatanganRange) {
                case '0': $query->where('total_kedatangan', '<=', 2); break;
                case '1': $query->whereBetween('total_kedatangan', [3, 4]); break;
                case '2': $query->where('total_kedatangan', '>', 4); break;
            }
        }

        if ($kelompokPelanggan) {
            $query->whereHas('kunjungans.kelompokPelanggan', function ($q) use ($kelompokPelanggan) {
                $q->where('kode', $kelompokPelanggan);
            });
        }

        if ($tipePelanggan === 'khusus') {
            $query->where('is_pelanggan_khusus', true);
        } elseif ($tipePelanggan === 'biasa') {
            $query->where(function ($q) {
                $q->where('is_pelanggan_khusus', false)->orWhereNull('is_pelanggan_khusus');
            });
        }

        if ($sort === 'tgl_kunjungan') {
            $query->orderByRaw("(tgl_kunjungan IS NULL) ASC, tgl_kunjungan {$direction}");
        } elseif ($sort === 'class') {
            $query->orderBy('total_biaya', $direction);
        } elseif ($sort === 'nama') {
            $query->orderByRaw("LOWER(nama) {$direction}");
        } elseif ($sort === 'alamat') {
            $query->orderByRaw("LOWER(alamat) {$direction}");
        } elseif ($sort === 'cabang_id') {
            $query->leftJoin('cabangs', 'pelanggans.cabang_id', '=', 'cabangs.id')
                  ->orderByRaw("LOWER(cabangs.nama) {$direction}")
                  ->select('pelanggans.*');
        } elseif ($sort === 'id') {
            $query->orderBy('pelanggans.id', $direction);
        } elseif (in_array($sort, ['pid', 'total_biaya', 'total_kedatangan', 'no_telp', 'dob'])) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderByRaw('LOWER(nama) ASC');
        }

        $pelanggan = $query->paginate(30)->withQueryString();

        return view('pelanggan.index', [
            'pelanggan'          => $pelanggan,
            'bulan'              => $bulan,
            'tahun'              => $tahun,
            'type'               => $type,
            'search'             => $search,
            'cabang_id'          => $cabangId,
            'omset_range'        => $omsetRange,
            'kedatangan_range'   => $kedatanganRange,
            'kelas'              => $kelas,
            'kelompok_pelanggan' => $kelompokPelanggan,
'tanggal_mulai'    => $tanggal_mulai,
            'tanggal_selesai'   => $tanggal_selesai,
            'tipe_pelanggan'     => $tipePelanggan,
            'cabangs'            => $cabangs,

            'sort'               => $sort,
            'direction'          => $direction,
            'searchMode'         => (bool) $search,
            'usePeriodeBiaya'    => ($biayaSubquery !== null),
        ]);
    }

    /**
     * Ajukan naik kelas ke Prioritas untuk pelanggan terpilih (bulk).
     * Hanya Admin yang bisa mengajukan; Superadmin yang approve.
     */
    public function requestNaikKelas(Request $request)
    {
        $role = Auth::user()->role?->name;

        if ($role !== 'Admin') {
            return redirect()->back()->with('error', 'Hanya Admin yang dapat mengajukan naik kelas.');
        }

        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return redirect()->back()->with('error', 'Tidak ada pelanggan yang dipilih.');
        }

        $ids = array_filter(array_map('intval', $ids));
        if (empty($ids)) {
            return redirect()->back()->with('error', 'ID pelanggan tidak valid.');
        }

        $pelanggans = Pelanggan::whereIn('id', $ids)->get(['id', 'pid', 'nama', 'class', 'cabang_id']);

        // Filter: hanya yang belum Prioritas
        $eligible = $pelanggans->filter(fn($p) => $p->class !== 'Prioritas');

        if ($eligible->isEmpty()) {
            return redirect()->back()->with('error', 'Semua pelanggan yang dipilih sudah berstatus Prioritas.');
        }

        $eligibleIds   = $eligible->pluck('id')->toArray();
        $eligibleCount = count($eligibleIds);

        // Auto-assign ke superadmin pertama dari cabang pelanggan pertama
        $firstPelanggan = $eligible->first();
        $assignedTo     = null;
        if ($firstPelanggan) {
            $superadmins = $this->getSuperadminsByCabang($firstPelanggan->cabang_id);
            $assignedTo  = $superadmins->first()?->id;
        }

        ApprovalRequest::create([
            'type'         => 'naik_kelas',
            'action'       => 'upgrade_class',
            'target_type'  => Pelanggan::class,
            'target_id'    => null,
            'payload'      => [
                'ids'        => array_values($eligibleIds),
                'count'      => $eligibleCount,
                'pelanggans' => $eligible->map(fn($p) => [
                    'id'    => $p->id,
                    'pid'   => $p->pid,
                    'nama'  => $p->nama,
                    'class' => $p->class,
                ])->values()->toArray(),
            ],
            'request_note' => "Pengajuan naik kelas ke Prioritas untuk {$eligibleCount} pelanggan.",
            'status'       => 'pending',
            'requested_by' => Auth::id(),
            'assigned_to'  => $assignedTo,
        ]);

        ActivityLog::record(
            'update', 'ApprovalRequest',
            "Mengajukan naik kelas {$eligibleCount} pelanggan ke Prioritas untuk approval Superadmin.",
            Auth::id(),
            Auth::user()->username ?? 'unknown',
            $role,
            $request->ip(),
            $request->userAgent()
        );

        return redirect()->back()->with('success', "Pengajuan naik kelas {$eligibleCount} pelanggan berhasil dikirim untuk approval Superadmin.");
    }

    /**
     * Export riwayat kunjungan pelanggan ke Excel.
     */
    public function exportKunjungan(Request $request, Pelanggan $pelanggan)
    {
        $kunjungans = $pelanggan->kunjungans()
            ->with('kelompokPelanggan')
            ->orderBy('tanggal_kunjungan', 'asc')
            ->get();

        $filename = 'riwayat-kunjungan-' . $pelanggan->pid . '-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new \App\Exports\KunjunganExport($pelanggan, $kunjungans), $filename);
    }

    /**
     * Tampilkan halaman Input Data Pelanggan (Tambah Manual + Import).
     * Cabangs difilter sesuai hak akses user.
     */
    public function inputPage()
    {
        /** @var User $user */
        $user = Auth::user();
        $accessibleCabangIds = $user->getAccessibleCabangIds();
        $cabangs = empty($accessibleCabangIds)
            ? Cabang::all()
            : Cabang::whereIn('id', $accessibleCabangIds)->get();
        return view('pelanggan.input', compact('cabangs'));
    }

    /**
     * Tampilkan form tambah pelanggan baru.
     * Cabangs difilter sesuai hak akses user (IT = semua cabang).
     */
    public function create()
    {
        /** @var User $user */
        $user = Auth::user();
        $accessibleCabangIds = $user->getAccessibleCabangIds();
        $cabangs = empty($accessibleCabangIds)
            ? Cabang::all()
            : Cabang::whereIn('id', $accessibleCabangIds)->get();
        return view('pelanggan.create', compact('cabangs'));
    }

    /**
     * Tampilkan halaman pelanggan khusus.
     * Cabangs difilter sesuai hak akses user (IT = semua cabang).
     */
    public function khusus()
    {
        /** @var User $user */
        $user = Auth::user();
        $accessibleCabangIds = $user->getAccessibleCabangIds();
        $cabangs = empty($accessibleCabangIds)
            ? Cabang::all()
            : Cabang::whereIn('id', $accessibleCabangIds)->get();

        // Buat map superadmin per cabang_id untuk dropdown dinamis di JS
        $superadminsByCabang = [];
        foreach ($cabangs as $cabang) {
            $admins = $this->getSuperadminsByCabang($cabang->id);
            if ($admins->isNotEmpty()) {
                $superadminsByCabang[$cabang->id] = $admins->map(fn($u) => [
                    'id'   => $u->id,
                    'name' => $u->name,
                ])->values()->toArray();
            }
        }

        return view('pelanggan.khusus', compact('cabangs', 'superadminsByCabang'));
    }

    /**
     * Tampilkan form edit data pelanggan.
     */
    public function edit($id)
    {
        $pelanggan   = Pelanggan::findOrFail($id);
        $cabangs     = Cabang::all();
        $role        = Auth::user()->role?->name;
        $superadmins = collect();

        // Untuk Admin: ambil superadmin yang bisa approve di cabang pelanggan ini
        if ($role === 'Admin') {
            $superadmins = $this->getSuperadminsByCabang($pelanggan->cabang_id);
        }

        return view('pelanggan.edit', compact('pelanggan', 'cabangs', 'superadmins', 'role'));
    }

    /**
     * Update data pelanggan (hanya Super Admin yang bisa langsung update).
     * Admin harus menggunakan approval route.
     */
    public function update(Request $request, $id)
    {
        $role = Auth::user()->role?->name;

        if ($role !== 'Super Admin') {
            return redirect()->back()->with('error', 'Hanya Super Admin yang dapat langsung mengubah data pelanggan.');
        }

        $request->validate([
            'pid'       => 'required|unique:pelanggans,pid,' . $id,
            'cabang_id' => 'required|exists:cabangs,id',
            'nama'      => 'required',
            'no_telp'   => 'nullable|string',
            'dob'       => 'nullable|date',
            'alamat'    => 'nullable|string',
            'kota'      => 'nullable|string',
        ]);

        $pelanggan = Pelanggan::findOrFail($id);

        // Validasi PID prefix sesuai cabang yang dipilih
        $cabang = \App\Models\Cabang::findOrFail($request->cabang_id);
        $pid = strtoupper(trim($request->pid));
        $pidPrefix = substr($pid, 0, 2);
        $cabangKode = strtoupper($cabang->kode);
        
        if ($pidPrefix !== $cabangKode) {
            return redirect()->back()
                ->withInput()
                ->with('error', "PID \"{$pid}\" tidak sesuai dengan cabang \"{$cabang->nama}\". Prefix PID harus \"{$cabangKode}\".");
        }

        $pelanggan->update($request->only(['pid', 'cabang_id', 'nama', 'no_telp', 'dob', 'alamat', 'kota']));

        ActivityLog::record(
            'update', 'Pelanggan',
            "Memperbarui data pelanggan {$pelanggan->pid} ({$pelanggan->nama})",
            Auth::id(),
            Auth::user()->username ?? 'unknown',
            $role,
            $request->ip(),
            $request->userAgent()
        );

        return redirect()->route('pelanggan.index')->with('success', 'Pelanggan berhasil diperbarui');
    }

    /**
     * Tampilkan detail pelanggan beserta riwayat kunjungan dan approval.
     */
    public function show(Request $request, $id)
    {
        $pelanggan      = Pelanggan::with(['kunjungans', 'cabang', 'classHistories.changedBy'])->findOrFail($id);
        $totalTransaksi = $pelanggan->kunjungans->sum('biaya');

        $kunjungans = $pelanggan->kunjungans()
            ->with('kelompokPelanggan')
            ->orderBy('tanggal_kunjungan', 'asc')
            ->paginate(10, ['*'], 'kunjungan_page');

        $kunjunganIds     = $kunjungans->pluck('id')->toArray();
        $pendingApprovals = \App\Models\ApprovalRequest::whereIn('target_id', $kunjunganIds)
            ->where('target_type', \App\Models\Kunjungan::class)
            ->where('status', 'pending')
            ->get()
            ->groupBy('target_id');

        $allKunjunganIds   = $pelanggan->kunjungans->pluck('id')->toArray();
        $approvalHistories = \App\Models\ApprovalRequest::whereIn('target_id', $allKunjunganIds)
            ->where('target_type', \App\Models\Kunjungan::class)
            ->with(['requester', 'reviewer'])
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'approval_page');

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
     * Hapus pelanggan.
     * Super Admin: langsung hapus. Admin: buat approval request.
     */
    public function destroy(Request $request, $id)
    {
        $pelanggan = Pelanggan::findOrFail($id);
        $role      = Auth::user()->role?->name;

        if ($role === 'Super Admin') {
            $pid  = $pelanggan->pid;
            $nama = $pelanggan->nama;
            $pelanggan->forceDelete();

            ActivityLog::record(
                'delete', 'Pelanggan',
                "Menghapus pelanggan {$pid} ({$nama})",
                Auth::id(),
                Auth::user()->username ?? 'unknown',
                $role,
                $request->ip(),
                $request->userAgent()
            );

            return redirect()->route('pelanggan.index')->with('success', "Pelanggan {$pid} berhasil dihapus.");
        }

        $request->validate([
            'catatan_hapus' => 'required|string|max:500',
        ], [
            'catatan_hapus.required' => 'Catatan/alasan hapus wajib diisi.',
        ]);

        // Auto-assign ke superadmin pertama di cabang pelanggan
        $superadmins = $this->getSuperadminsByCabang($pelanggan->cabang_id);
        $assignedTo  = $superadmins->first()?->id;

        \App\Models\ApprovalRequest::create([
            'type'         => 'pelanggan',
            'action'       => 'delete',
            'target_type'  => Pelanggan::class,
            'target_id'    => $pelanggan->id,
            'payload'      => ['pid' => $pelanggan->pid, 'nama' => $pelanggan->nama],
            'request_note' => $request->catatan_hapus,
            'status'       => 'pending',
            'requested_by' => Auth::id(),
            'assigned_to'  => $assignedTo,
        ]);

        ActivityLog::record(
            'delete', 'ApprovalRequest',
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
     * Hapus multiple pelanggan (bulk delete).
     * Super Admin: langsung hapus. Admin: buat approval request.
     */
    public function bulkDelete(Request $request)
    {
        $ids  = $request->input('ids', []);
        $role = Auth::user()->role?->name;

        if (empty($ids)) {
            return redirect()->back()->with('error', 'Tidak ada pelanggan yang dipilih.');
        }

        $ids = array_filter(array_map('intval', $ids));

        if (empty($ids)) {
            return redirect()->back()->with('error', 'ID pelanggan tidak valid.');
        }

        if ($role === 'Super Admin') {
            $count = Pelanggan::whereIn('id', $ids)->count();
            // Force delete: hapus permanen dari database
            Pelanggan::whereIn('id', $ids)->each(function ($p) { $p->forceDelete(); });

            ActivityLog::record(
                'delete', 'Pelanggan',
                "Bulk delete {$count} pelanggan oleh Super Admin.",
                Auth::id(),
                Auth::user()->username ?? 'unknown',
                $role,
                $request->ip(),
                $request->userAgent()
            );

            return redirect()->back()->with('success', "{$count} pelanggan berhasil dihapus.");
        }

        $request->validate([
            'catatan_hapus' => 'required|string|max:500',
        ], [
            'catatan_hapus.required' => 'Catatan/alasan hapus wajib diisi.',
        ]);

        $count = count($ids);

        // Auto-assign ke superadmin pertama dari cabang pelanggan pertama
        $firstPelanggan = Pelanggan::whereIn('id', $ids)->first();
        $assignedTo     = null;
        if ($firstPelanggan) {
            $superadmins = $this->getSuperadminsByCabang($firstPelanggan->cabang_id);
            $assignedTo  = $superadmins->first()?->id;
        }

        \App\Models\ApprovalRequest::create([
            'type'         => 'pelanggan',
            'action'       => 'bulk_delete',
            'target_type'  => Pelanggan::class,
            'target_id'    => null,
            'payload'      => ['ids' => array_values($ids), 'count' => $count],
            'request_note' => $request->catatan_hapus,
            'status'       => 'pending',
            'requested_by' => Auth::id(),
            'assigned_to'  => $assignedTo,
        ]);

        ActivityLog::record(
            'delete', 'ApprovalRequest',
            "Mengajukan bulk delete {$count} pelanggan untuk approval Superadmin.",
            Auth::id(),
            Auth::user()->username ?? 'unknown',
            $role,
            $request->ip(),
            $request->userAgent()
        );

        return redirect()->back()->with('success', "Pengajuan hapus {$count} pelanggan berhasil dikirim untuk approval Superadmin.");
    }
}
