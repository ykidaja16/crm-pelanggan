<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use App\Models\Pelanggan;
use App\Models\Cabang;
use App\Models\ActivityLog;
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
                    $errors[$index][] = "PID {$pid} sudah terdaftar atas nama \"{$existingPelanggan->nama}\".";
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
            'found'     => true,
            'pelanggan' => [
                'id'        => $pelanggan->id,
                'pid'       => $pelanggan->pid,
                'nama'      => $pelanggan->nama,
                'class'     => $pelanggan->class,
                'cabang_id' => $pelanggan->cabang_id,
            ],
            'cabang' => $pelanggan->cabang->nama ?? '-',
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
        if ($type === 'perbulan' && $bulan && $tahun) {
            $safeBulan   = (int) $bulan;
            $safeTahun   = (int) $tahun;
            $tglSubquery = "SELECT MAX(tanggal_kunjungan) FROM kunjungans
                WHERE kunjungans.pelanggan_id = pelanggans.id
                AND MONTH(tanggal_kunjungan) = {$safeBulan}
                AND YEAR(tanggal_kunjungan) = {$safeTahun}";
        } elseif ($type === 'pertahun' && $tahun) {
            $safeTahun   = (int) $tahun;
            $tglSubquery = "SELECT MAX(tanggal_kunjungan) FROM kunjungans
                WHERE kunjungans.pelanggan_id = pelanggans.id
                AND YEAR(tanggal_kunjungan) = {$safeTahun}";
        } else {
            $tglSubquery = "SELECT MAX(tanggal_kunjungan) FROM kunjungans
                WHERE kunjungans.pelanggan_id = pelanggans.id";
        }

        $query = Pelanggan::with('cabang')
            ->select('pelanggans.*')
            ->selectRaw("({$tglSubquery}) as tgl_kunjungan");

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
            'tipe_pelanggan'     => $tipePelanggan,
            'cabangs'            => $cabangs,
            'sort'               => $sort,
            'direction'          => $direction,
            'searchMode'         => (bool) $search,
        ]);
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
            $pelanggan->delete();

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
            Pelanggan::whereIn('id', $ids)->delete();

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
