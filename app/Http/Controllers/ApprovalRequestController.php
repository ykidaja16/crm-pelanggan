<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\Pelanggan;
use App\Models\Kunjungan;
use App\Models\Cabang;
use App\Models\ActivityLog;
use App\Models\KelompokPelanggan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ApprovalRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ApprovalRequest::with(['requester', 'reviewer'])->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $requests = $query->paginate(20)->withQueryString();

        return view('approval-requests.index', compact('requests'));
    }

    /**
     * Helper: ambil superadmin pertama untuk cabang tertentu.
     */
    private function getFirstSuperadminForCabang(int $cabangId): ?int
    {
        $superadmin = User::whereHas('role', fn($q) => $q->where('name', 'Super Admin'))
            ->whereHas('cabangs', fn($q) => $q->where('cabangs.id', $cabangId))
            ->first();
        return $superadmin?->id;
    }

    /**
     * Submenu: Approval Pelanggan Khusus
     */
    public function indexPelangganKhusus(Request $request)
    {
        $query = ApprovalRequest::with(['requester', 'reviewer', 'assignedTo'])
            ->where('type', 'pelanggan_khusus')
            ->orderByDesc('id');

        // Super Admin hanya melihat yang di-assign ke dirinya
        if (Auth::user()->role?->name === 'Super Admin') {
            $query->where('assigned_to', Auth::id());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->paginate(20)->withQueryString();
        $cabangs  = Cabang::all()->keyBy('id');

        return view('approval-requests.pelanggan-khusus', compact('requests', 'cabangs'));
    }

    /**
     * Submenu: Approval Data Kunjungan
     */
    public function indexKunjungan(Request $request)
    {
        $query = ApprovalRequest::with(['requester', 'reviewer', 'assignedTo'])
            ->where('type', 'kunjungan')
            ->orderByDesc('id');

        // Super Admin hanya melihat yang di-assign ke dirinya
        if (Auth::user()->role?->name === 'Super Admin') {
            $query->where('assigned_to', Auth::id());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->paginate(20)->withQueryString();

        // Eager load kunjungan + pelanggan untuk popup informasi
        $kunjunganIds = $requests->pluck('target_id')->filter()->unique()->toArray();
        $kunjungans   = Kunjungan::with('pelanggan')->whereIn('id', $kunjunganIds)->get()->keyBy('id');

        return view('approval-requests.kunjungan', compact('requests', 'kunjungans'));
    }

    /**
     * Submenu: Approval Data Pelanggan (hapus / edit)
     */
    public function indexPelanggan(Request $request)
    {
        $query = ApprovalRequest::with(['requester', 'reviewer', 'assignedTo'])
            ->where('type', 'pelanggan')
            ->orderByDesc('id');

        // Super Admin hanya melihat yang di-assign ke dirinya
        if (Auth::user()->role?->name === 'Super Admin') {
            $query->where('assigned_to', Auth::id());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->paginate(20)->withQueryString();

        $cabangs = Cabang::all()->keyBy('id');

        return view('approval-requests.pelanggan', compact('requests', 'cabangs'));
    }

    /**
     * Point 6 & 8: Validasi PID duplikat dan prefix cabang untuk pelanggan khusus manual
     */
    public function storeSpecialCustomerRequest(Request $request)
    {
        $validated = $request->validate([
            'pid'                => 'required|string',
            'cabang_id'          => 'required|exists:cabangs,id',
            'nama'               => 'required|string',
            'no_telp'            => 'nullable|string',
            'dob'                => 'nullable|date',
            'alamat'             => 'nullable|string',
            'kota'               => 'nullable|string',
            'kelompok_pelanggan' => 'required|in:mandiri,klinisi',
            'kategori_khusus'    => 'required|string|max:200',
            'tanggal_kunjungan'  => 'required|date',
            'biaya'              => 'required',
            'request_note'       => 'required|string|max:500',
        ]);

        $pid = strtoupper(trim($validated['pid']));

        // Point 6: Cek PID duplikat di database
        $existingPelanggan = Pelanggan::where('pid', $pid)->first();
        if ($existingPelanggan) {
            return back()
                ->withInput()
                ->with('error', "PID {$pid} sudah terdaftar atas nama \"{$existingPelanggan->nama}\". Tidak bisa mengajukan pelanggan khusus dengan PID yang sama.");
        }

        // Cek juga di approval request yang masih pending
        $pendingRequest = ApprovalRequest::where('type', 'pelanggan_khusus')
            ->where('status', 'pending')
            ->whereJsonContains('payload->pid', $pid)
            ->first();
        if ($pendingRequest) {
            return back()
                ->withInput()
                ->with('error', "PID {$pid} sudah ada dalam pengajuan yang sedang menunggu approval.");
        }

        // Point 8: Validasi prefix PID sesuai cabang yang dipilih
        $cabang = Cabang::findOrFail($validated['cabang_id']);
        $pidPrefix = strtoupper(substr($pid, 0, 2));
        if ($pidPrefix !== strtoupper($cabang->kode)) {
            return back()
                ->withInput()
                ->with('error', "PID \"{$pid}\" tidak sesuai dengan cabang \"{$cabang->nama}\". Prefix PID harus \"{$cabang->kode}\" untuk cabang ini.");
        }

        $biayaValue = (float) preg_replace('/[^\d]/', '', (string) $validated['biaya']);

        // Auto-assign ke superadmin pertama di cabang yang dipilih
        $assignedTo = $this->getFirstSuperadminForCabang((int) $validated['cabang_id']);

        ApprovalRequest::create([
            'type'        => 'pelanggan_khusus',
            'action'      => 'create',
            'target_type' => Pelanggan::class,
            'target_id'   => null,
            'payload'     => [
                'pid'                => $pid,
                'cabang_id'          => $validated['cabang_id'],
                'nama'               => $validated['nama'],
                'no_telp'            => $validated['no_telp'] ?? null,
                'dob'                => $validated['dob'] ?? null,
                'alamat'             => $validated['alamat'] ?? null,
                'kota'               => $validated['kota'] ?? null,
                'kelompok_pelanggan' => $validated['kelompok_pelanggan'],
                'kategori_khusus'    => $validated['kategori_khusus'],
                'tanggal_kunjungan'  => $validated['tanggal_kunjungan'],
                'biaya_kunjungan'    => $biayaValue,
            ],
            'request_note' => $validated['request_note'],
            'status'       => 'pending',
            'requested_by' => Auth::id(),
            'assigned_to'  => $assignedTo,
        ]);

        ActivityLog::record(
            'create',
            'ApprovalRequest',
            'Mengajukan pelanggan khusus PID ' . $pid . ' untuk approval.',
            Auth::id(),
            Auth::user()->username ?? 'unknown',
            Auth::user()->role?->name ?? '-',
            $request->ip(),
            $request->userAgent()
        );

        return back()->with('success', 'Pengajuan pelanggan khusus berhasil dikirim untuk approval Superadmin.');
    }

    /**
     * Point 6 & 8: Validasi PID duplikat dan prefix cabang untuk import pelanggan khusus
     */
    public function storeSpecialCustomerImportRequest(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'request_note' => 'required|string|max:500',
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if (count($rows) < 2) {
            return back()->with('error', 'File import pelanggan khusus tidak berisi data.');
        }

        $cabangs = Cabang::all()->keyBy('kode');
        $created = 0;
        $errors = [];

        DB::transaction(function () use ($rows, $cabangs, $validated, &$created, &$errors) {
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i] ?? [];
                $rowNum = $i + 1;

                // Format: No | Nama | Total Kedatangan | Tanggal | Biaya | No Telp | DOB | PID | Alamat | Kota | Kelompok | Kategori Khusus
                $nama = trim((string) ($row[1] ?? ''));
                $tanggal = trim((string) ($row[3] ?? ''));
                $biaya = (float) preg_replace('/[^\d]/', '', (string) ($row[4] ?? '0'));
                $noTelp = trim((string) ($row[5] ?? ''));
                $dob = trim((string) ($row[6] ?? ''));
                $pid = strtoupper(trim((string) ($row[7] ?? '')));
                $alamat = trim((string) ($row[8] ?? ''));
                $kota = trim((string) ($row[9] ?? ''));
                $kelompokPelanggan = strtolower(trim((string) ($row[10] ?? 'mandiri')));
                $kelompokPelanggan = in_array($kelompokPelanggan, ['mandiri', 'klinisi']) ? $kelompokPelanggan : 'mandiri';
                $kategoriKhusus = trim((string) ($row[11] ?? ''));

                if ($pid === '' || $nama === '' || $kategoriKhusus === '') {
                    continue;
                }

                // Point 6: Cek PID duplikat di database
                $existingPelanggan = Pelanggan::where('pid', $pid)->first();
                if ($existingPelanggan) {
                    $errors[] = "Baris {$rowNum}: PID {$pid} sudah terdaftar atas nama \"{$existingPelanggan->nama}\".";
                    continue;
                }

                // Cek di pending approval
                $pendingRequest = ApprovalRequest::where('type', 'pelanggan_khusus')
                    ->where('status', 'pending')
                    ->whereJsonContains('payload->pid', $pid)
                    ->first();
                if ($pendingRequest) {
                    $errors[] = "Baris {$rowNum}: PID {$pid} sudah ada dalam pengajuan yang sedang menunggu approval.";
                    continue;
                }

                $cabangKode = strtoupper(substr($pid, 0, 2));
                $cabang = $cabangs->get($cabangKode);
                if (!$cabang) {
                    $errors[] = "Baris {$rowNum}: Kode cabang '{$cabangKode}' dalam PID '{$pid}' tidak valid.";
                    continue;
                }

                // Point 8: Validasi prefix PID sesuai cabang
                // (Untuk import, cabang ditentukan dari prefix PID, jadi sudah otomatis sesuai)

                // Auto-assign ke superadmin pertama di cabang
                $assignedToImport = $this->getFirstSuperadminForCabang($cabang->id);

                ApprovalRequest::create([
                    'type' => 'pelanggan_khusus',
                    'action' => 'create',
                    'target_type' => Pelanggan::class,
                    'target_id' => null,
                    'payload' => [
                        'pid' => $pid,
                        'cabang_id' => $cabang->id,
                        'nama' => $nama,
                        'no_telp' => $noTelp ?: null,
                        'dob' => $dob ?: null,
                        'alamat' => $alamat ?: null,
                        'kota' => $kota ?: null,
                        'kelompok_pelanggan' => $kelompokPelanggan,
                        'kategori_khusus' => $kategoriKhusus,
                        'tanggal_kunjungan' => $tanggal !== '' ? $tanggal : now()->format('Y-m-d'),
                        'biaya_kunjungan' => $biaya,
                    ],
                    'request_note' => $validated['request_note'],
                    'status' => 'pending',
                    'requested_by' => Auth::id(),
                    'assigned_to' => $assignedToImport,
                ]);

                $created++;
            }
        });

        ActivityLog::record(
            'create',
            'ApprovalRequest',
            'Mengajukan import pelanggan khusus sebanyak ' . $created . ' baris untuk approval.',
            Auth::id(),
            Auth::user()->username ?? 'unknown',
            Auth::user()->role?->name ?? '-',
            $request->ip(),
            $request->userAgent()
        );

        if (!empty($errors) && $created === 0) {
            return back()->with('error', 'Import gagal. ' . implode(' ', $errors));
        }

        $msg = "Pengajuan import pelanggan khusus berhasil dikirim ({$created} data pending approval).";
        if (!empty($errors)) {
            $msg .= ' Beberapa baris dilewati: ' . implode(' ', $errors);
        }

        return back()->with('success', $msg);
    }

    public function downloadTemplateKhusus()
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
            'Kota',
            'Kelompok Pelanggan (mandiri/klinisi)',
            'Kategori Khusus',
        ];

        $data = [
            [1, 'Budi Santoso', 1, '2024-01-15', 2500000, '081234567890', '1990-05-20', 'LX00001', 'Jl. Sudirman No. 123', 'Jakarta', 'mandiri', 'Kepala Dinas'],
            [2, 'Siti Aminah', 1, '2024-02-10', 4500000, '082345678901', '1985-08-12', 'LZ00002', 'Jl. Ahmad Yani No. 45', 'Bandung', 'klinisi', 'PIC Perusahaan'],
            [3, 'Ahmad Wijaya', 1, '2024-03-05', 1200000, '083456789012', '1992-11-03', 'LX00003', 'Jl. Gatot Subroto No. 78', 'Surabaya', 'mandiri', 'Lainnya'],
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($headers as $col => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . '1', $header);
        }

        foreach ($data as $row => $rowData) {
            foreach ($rowData as $col => $value) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . ($row + 2), $value);
            }
        }

        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
        }

        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2EFDA'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];
        $sheet->getStyle('A1:L1')->applyFromArray($headerStyle);

        $writer = new Xlsx($spreadsheet);
        $filename = 'template_import_pelanggan_khusus.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'template_khusus_');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Pengajuan edit kunjungan (Admin → Superadmin cabang)
     */
    public function storeKunjunganEditRequest(Request $request, $kunjunganId)
    {
        $validated = $request->validate([
            'tanggal_kunjungan'  => 'required|date',
            'biaya'              => 'required|numeric|min:0',
            'kelompok_pelanggan' => 'required|in:mandiri,klinisi',
            'request_note'       => 'required|string|max:500',
            'assigned_to'        => 'nullable|exists:users,id',
        ]);

        $kunjungan = Kunjungan::with('pelanggan')->findOrFail($kunjunganId);
        $pelanggan = $kunjungan->pelanggan;

        // Simpan data original (sebelum perubahan)
        $originalData = [
            'tanggal_kunjungan'  => $kunjungan->tanggal_kunjungan,
            'biaya'              => $kunjungan->biaya,
            'kelompok_pelanggan' => $kunjungan->kelompokPelanggan?->kode ?? null,
        ];

        // Tentukan assigned_to: dari form (jika ada dropdown) atau auto-assign
        $assignedTo = $validated['assigned_to'] ?? null;
        if (!$assignedTo && $pelanggan?->cabang_id) {
            $assignedTo = $this->getFirstSuperadminForCabang($pelanggan->cabang_id);
        }

        ApprovalRequest::create([
            'type'         => 'kunjungan',
            'action'       => 'edit',
            'target_type'  => Kunjungan::class,
            'target_id'    => $kunjungan->id,
            'payload'      => [
                'original_data'      => $originalData,
                'tanggal_kunjungan'  => $validated['tanggal_kunjungan'],
                'biaya'              => $validated['biaya'],
                'kelompok_pelanggan' => $validated['kelompok_pelanggan'],
            ],
            'request_note' => $validated['request_note'],
            'status'       => 'pending',
            'requested_by' => Auth::id(),
            'assigned_to'  => $assignedTo,
        ]);

        ActivityLog::record(
            'update',
            'ApprovalRequest',
            'Mengajukan edit kunjungan ID ' . $kunjungan->id . ' (PID ' . ($pelanggan->pid ?? '-') . ') untuk approval.',
            Auth::id(),
            Auth::user()->username ?? 'unknown',
            Auth::user()->role?->name ?? '-',
            $request->ip(),
            $request->userAgent()
        );

        return redirect()->route('pelanggan.show', $kunjungan->pelanggan_id)
            ->with('success', 'Pengajuan edit kunjungan berhasil dikirim untuk approval Superadmin.');
    }

    public function storeKunjunganDeleteRequest(Request $request, $kunjunganId)
    {
        $validated = $request->validate([
            'request_note' => 'required|string|max:500',
        ]);

        $kunjungan  = Kunjungan::with('pelanggan')->findOrFail($kunjunganId);
        $pelanggan  = $kunjungan->pelanggan;
        $assignedTo = $pelanggan?->cabang_id
            ? $this->getFirstSuperadminForCabang($pelanggan->cabang_id)
            : null;

        ApprovalRequest::create([
            'type'         => 'kunjungan',
            'action'       => 'delete',
            'target_type'  => Kunjungan::class,
            'target_id'    => $kunjungan->id,
            'payload'      => [],
            'request_note' => $validated['request_note'],
            'status'       => 'pending',
            'requested_by' => Auth::id(),
            'assigned_to'  => $assignedTo,
        ]);

        ActivityLog::record(
            'delete',
            'ApprovalRequest',
            'Mengajukan hapus kunjungan ID ' . $kunjungan->id . ' (PID ' . ($pelanggan->pid ?? '-') . ') untuk approval.',
            Auth::id(),
            Auth::user()->username ?? 'unknown',
            Auth::user()->role?->name ?? '-',
            $request->ip(),
            $request->userAgent()
        );

        return back()->with('success', 'Pengajuan hapus kunjungan berhasil dikirim untuk approval Superadmin.');
    }

    /**
     * Pengajuan edit data pelanggan (Admin → Superadmin cabang)
     */
    public function storePelangganEditRequest(Request $request, $pelangganId)
    {
        $validated = $request->validate([
            'pid'          => 'required|string',
            'cabang_id'    => 'required|exists:cabangs,id',
            'nama'         => 'required|string',
            'no_telp'      => 'nullable|string',
            'dob'          => 'nullable|date',
            'alamat'       => 'nullable|string',
            'kota'         => 'nullable|string',
            'request_note' => 'required|string|max:500',
            'assigned_to'  => 'nullable|exists:users,id',
        ]);

        $pelanggan = Pelanggan::findOrFail($pelangganId);

        // Simpan data original (sebelum perubahan)
        $originalData = [
            'pid'       => $pelanggan->pid,
            'cabang_id' => $pelanggan->cabang_id,
            'nama'      => $pelanggan->nama,
            'no_telp'   => $pelanggan->no_telp,
            'dob'       => $pelanggan->dob,
            'alamat'    => $pelanggan->alamat,
            'kota'      => $pelanggan->kota,
        ];

        // Tentukan assigned_to: dari form (dropdown) atau auto-assign
        $assignedTo = $validated['assigned_to'] ?? null;
        if (!$assignedTo) {
            $assignedTo = $this->getFirstSuperadminForCabang($pelanggan->cabang_id);
        }

        ApprovalRequest::create([
            'type'         => 'pelanggan',
            'action'       => 'edit',
            'target_type'  => Pelanggan::class,
            'target_id'    => $pelanggan->id,
            'payload'      => [
                'original_data' => $originalData,
                'pid'           => $validated['pid'],
                'cabang_id'     => $validated['cabang_id'],
                'nama'          => $validated['nama'],
                'no_telp'       => $validated['no_telp'] ?? null,
                'dob'           => $validated['dob'] ?? null,
                'alamat'        => $validated['alamat'] ?? null,
                'kota'          => $validated['kota'] ?? null,
            ],
            'request_note' => $validated['request_note'],
            'status'       => 'pending',
            'requested_by' => Auth::id(),
            'assigned_to'  => $assignedTo,
        ]);

        ActivityLog::record(
            'update',
            'ApprovalRequest',
            'Mengajukan edit pelanggan PID ' . $pelanggan->pid . ' untuk approval.',
            Auth::id(),
            Auth::user()->username ?? 'unknown',
            Auth::user()->role?->name ?? '-',
            $request->ip(),
            $request->userAgent()
        );

        return redirect()->route('pelanggan.show', $pelanggan->id)
            ->with('success', 'Pengajuan edit pelanggan berhasil dikirim untuk approval Superadmin.');
    }

    /**
     * Point 10: Approve request — termasuk pelanggan_delete dan pelanggan_bulk_delete
     */
    public function approve(Request $request, $id)
    {
        $validated = $request->validate([
            'decision_note' => 'required|string|max:500',
        ]);

        $approval = ApprovalRequest::findOrFail($id);

        if ($approval->status !== 'pending') {
            return back()->with('error', 'Request ini sudah diproses sebelumnya.');
        }

        DB::transaction(function () use ($approval, $validated, $request) {
            // Pelanggan Khusus: buat pelanggan baru
            if ($approval->type === 'pelanggan_khusus' && $approval->action === 'create') {
                $payload = $approval->payload ?? [];
                $cabang = Cabang::findOrFail($payload['cabang_id']);

                $pelanggan = Pelanggan::create([
                    'pid' => $payload['pid'],
                    'cabang_id' => $cabang->id,
                    'nama' => $payload['nama'],
                    'no_telp' => $payload['no_telp'] ?? null,
                    'dob' => $payload['dob'] ?? null,
                    'alamat' => $payload['alamat'] ?? null,
                    'kota' => $payload['kota'] ?? null,
                    'is_pelanggan_khusus' => true,
                    'kategori_khusus' => $payload['kategori_khusus'] ?? null,
                    'class' => 'Prioritas',
                    'total_kedatangan' => 0,
                    'total_biaya' => 0,
                ]);

                if (!empty($payload['tanggal_kunjungan']) || !empty($payload['biaya_kunjungan'])) {
                    $kelompokKode = $payload['kelompok_pelanggan'] ?? 'mandiri';
                    $kelompok = KelompokPelanggan::where('kode', $kelompokKode)->first();

                    Kunjungan::create([
                        'pelanggan_id' => $pelanggan->id,
                        'cabang_id' => $cabang->id,
                        'tanggal_kunjungan' => $payload['tanggal_kunjungan'] ?? now()->toDateString(),
                        'biaya' => (float) ($payload['biaya_kunjungan'] ?? 0),
                        'kelompok_pelanggan_id' => $kelompok?->id,
                        'total_kedatangan' => 1,
                    ]);

                    $pelanggan->updateStats(now(), 'Inisialisasi kunjungan dari approval pelanggan khusus');
                }

                $approval->target_id = $pelanggan->id;
            }

            // Kunjungan: edit atau delete
            if ($approval->type === 'kunjungan' && $approval->target_type === Kunjungan::class) {
                $kunjungan = Kunjungan::with('pelanggan')->findOrFail($approval->target_id);

                if ($approval->action === 'edit') {
                    $payload = $approval->payload ?? [];
                    $oldBiaya = (float) $kunjungan->biaya;
                    $newBiaya = (float) ($payload['biaya'] ?? $oldBiaya);
                    $biayaDiff = $newBiaya - $oldBiaya;

                    $kelompokKode = $payload['kelompok_pelanggan'] ?? null;
                    $kelompok = $kelompokKode
                        ? KelompokPelanggan::where('kode', $kelompokKode)->first()
                        : null;

                    $kunjungan->update([
                        'tanggal_kunjungan' => $payload['tanggal_kunjungan'] ?? $kunjungan->tanggal_kunjungan,
                        'biaya' => $newBiaya,
                        'kelompok_pelanggan_id' => $kelompok?->id ?? $kunjungan->kelompok_pelanggan_id,
                    ]);

                    $kunjungan->pelanggan->updateBiayaAndClass(
                        $biayaDiff,
                        \Carbon\Carbon::parse($kunjungan->tanggal_kunjungan),
                        'Perubahan dari approval edit kunjungan'
                    );
                }

                if ($approval->action === 'delete') {
                    $pelanggan = $kunjungan->pelanggan;
                    $deletedDate = \Carbon\Carbon::parse($kunjungan->tanggal_kunjungan);
                    $kunjungan->delete();
                    $pelanggan->updateStats($deletedDate, 'Perubahan dari approval hapus kunjungan');
                }
            }

            // Edit data pelanggan (dari Admin)
            if ($approval->type === 'pelanggan' && $approval->action === 'edit') {
                $pelanggan = Pelanggan::find($approval->target_id);
                if ($pelanggan) {
                    $payload = $approval->payload ?? [];
                    $pelanggan->update([
                        'pid'       => $payload['pid']       ?? $pelanggan->pid,
                        'cabang_id' => $payload['cabang_id'] ?? $pelanggan->cabang_id,
                        'nama'      => $payload['nama']      ?? $pelanggan->nama,
                        'no_telp'   => $payload['no_telp']   ?? $pelanggan->no_telp,
                        'dob'       => $payload['dob']       ?? $pelanggan->dob,
                        'alamat'    => $payload['alamat']    ?? $pelanggan->alamat,
                        'kota'      => $payload['kota']      ?? $pelanggan->kota,
                    ]);
                }
            }

            // Point 10: Hapus pelanggan individual (dari Admin)
            if ($approval->type === 'pelanggan' && $approval->action === 'delete') {
                $pelanggan = Pelanggan::find($approval->target_id);
                if ($pelanggan) {
                    $pelanggan->delete();
                }
            }

            // Point 10: Hapus pelanggan bulk (dari Admin)
            if ($approval->type === 'pelanggan' && $approval->action === 'bulk_delete') {
                $payload = $approval->payload ?? [];
                $ids = $payload['ids'] ?? [];
                if (!empty($ids)) {
                    Pelanggan::whereIn('id', $ids)->delete();
                }
            }

            $approval->status = 'approved';
            $approval->decision_note = $validated['decision_note'];
            $approval->reviewed_by = Auth::id();
            $approval->reviewed_at = now();
            $approval->save();

            ActivityLog::record(
                'update',
                'ApprovalRequest',
                'Menyetujui approval request #' . $approval->id . '. Catatan: ' . $validated['decision_note'],
                Auth::id(),
                Auth::user()->username ?? 'unknown',
                Auth::user()->role?->name ?? '-',
                $request->ip(),
                $request->userAgent()
            );
        });

        return back()->with('success', 'Request berhasil di-approve.');
    }

    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'decision_note' => 'required|string|max:500',
        ]);

        $approval = ApprovalRequest::findOrFail($id);

        if ($approval->status !== 'pending') {
            return back()->with('error', 'Request ini sudah diproses sebelumnya.');
        }

        $approval->status = 'rejected';
        $approval->decision_note = $validated['decision_note'];
        $approval->reviewed_by = Auth::id();
        $approval->reviewed_at = now();
        $approval->save();

        ActivityLog::record(
            'update',
            'ApprovalRequest',
            'Menolak approval request #' . $approval->id . '. Catatan: ' . $validated['decision_note'],
            Auth::id(),
            Auth::user()->username ?? 'unknown',
            Auth::user()->role?->name ?? '-',
            $request->ip(),
            $request->userAgent()
        );

        return back()->with('success', 'Request berhasil di-reject.');
    }

    /**
     * Point 3: Process approval via dropdown (Approve/Reject + 1 textbox catatan)
     */
    public function process(Request $request, $id)
    {
        $validated = $request->validate([
            'action'        => 'required|in:approve,reject',
            'decision_note' => 'required|string|max:500',
        ], [
            'action.required'        => 'Pilih aksi (Approve atau Reject).',
            'action.in'              => 'Aksi tidak valid.',
            'decision_note.required' => 'Catatan keputusan wajib diisi.',
        ]);

        if ($validated['action'] === 'approve') {
            return $this->approve(new Request([
                'decision_note' => $validated['decision_note'],
            ]), $id);
        } else {
            return $this->reject(new Request([
                'decision_note' => $validated['decision_note'],
            ]), $id);
        }
    }
}
