<?php

namespace App\Http\Controllers;

use App\Exports\SearchByPhoneFoundExport;
use App\Exports\SearchByPhoneNotFoundExport;
use App\Models\Pelanggan;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class SearchByPhoneController extends Controller
{
    private const PER_PAGE       = 50;
    private const SESSION_FOUND  = 'sbp_found';
    private const SESSION_NFOUND = 'sbp_not_found';

    // ─── Pages ───────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $isPaginating  = $request->has('found_page') || $request->has('nf_page');
        $isFreshSearch = Session::has('sbp_has_results');

        if ($isFreshSearch || $isPaginating) {
            $hasResults = Session::has(self::SESSION_FOUND);
        } else {
            Session::forget([self::SESSION_FOUND, self::SESSION_NFOUND]);
            $hasResults = false;
        }

        $foundAll    = $hasResults ? (Session::get(self::SESSION_FOUND)  ?? []) : [];
        $notFoundAll = $hasResults ? (Session::get(self::SESSION_NFOUND) ?? []) : [];

        $foundPage    = $this->paginate($foundAll,    self::PER_PAGE, $request->get('found_page', 1), 'found_page');
        $notFoundPage = $this->paginate($notFoundAll, self::PER_PAGE, $request->get('nf_page',    1), 'nf_page');

        // Hitung offset record individual sebelum halaman ini (karena 1 grup bisa >1 record)
        $foundRecordOffset = 0;
        $startGroup = ($foundPage->currentPage() - 1) * self::PER_PAGE;
        for ($i = 0; $i < $startGroup && isset($foundAll[$i]); $i++) {
            $foundRecordOffset += count($foundAll[$i]['records']);
        }

        $notFoundOffset = $notFoundPage->firstItem() - 1;

        return view('pelanggan.search-by-phone', compact(
            'foundPage', 'notFoundPage', 'hasResults',
            'foundRecordOffset', 'notFoundOffset'
        ));
    }

    public function search(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        $file      = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension() ?? '');

        $sheetRows = ($extension === 'csv' || $extension === 'txt')
            ? $this->readCsvFile($file)
            : (Excel::toArray(null, $file)[0] ?? []);

        if (empty($sheetRows)) {
            return back()->with('error', 'File kosong.');
        }

        // ── Parse Excel → map normalized_phone => {nama, no_telp_raw} ────────
        $excelData = [];
        foreach ($sheetRows as $index => $row) {
            $firstCell = strtolower(trim((string) ($row[0] ?? '')));
            if ($index === 0 && in_array($firstCell, ['nama', 'name'])) {
                continue;
            }

            $nama   = trim((string) ($row[0] ?? ''));
            $noTelp = trim((string) ($row[1] ?? ''));

            if ($nama === '' && $noTelp === '') {
                continue;
            }

            $normalized = $this->normalizePhone($noTelp);
            if ($normalized === '') {
                continue;
            }

            $excelData[$normalized] ??= ['nama' => $nama, 'no_telp_raw' => $noTelp];
        }

        if (empty($excelData)) {
            return back()->with('error', 'Tidak ada data nomer telepon valid di file.');
        }

        // ── Single query — ALL branches ───────────────────────────────────────
        $allVariations = array_unique(
            array_merge(...array_map([$this, 'phoneVariations'], array_keys($excelData)))
        );

        $dbResults = Pelanggan::with(['cabang', 'latestKunjungan'])
            ->whereIn('no_telp', $allVariations)
            ->get();

        // ── Group DB results by normalized phone ──────────────────────────────
        $phoneGroups = []; // normalized_phone => [Pelanggan, ...]
        foreach ($dbResults as $pelanggan) {
            $dbNorm = $this->normalizePhone($pelanggan->no_telp ?? '');
            if (isset($excelData[$dbNorm])) {
                $phoneGroups[$dbNorm][] = $pelanggan;
            }
        }

        // ── Build serializable found / not-found arrays ───────────────────────
        $foundAll    = [];
        $notFoundAll = [];

        foreach ($excelData as $normalized => $excelEntry) {
            if (!isset($phoneGroups[$normalized])) {
                $notFoundAll[] = $excelEntry;
                continue;
            }

            // Urutkan konsisten: semua kolom dibangun dari urutan yang sama
            $ordered = collect($phoneGroups[$normalized])->values();

            $pidLinks     = $ordered->map(fn ($p) => ['pid' => $p->pid, 'id' => $p->id, 'cabang_id' => $p->cabang_id])->all();
            $pids         = $ordered->pluck('pid')->join(', ');
            $cabangNames  = $ordered->map(fn ($p) => $p->cabang?->nama ?? '-')->join(', ');
            $namaDb       = $ordered->pluck('nama')->join(', ');
            $alamatStr    = $ordered->map(fn ($p) => $p->alamat ?? '-')->join(', ');
            $visitStr     = $ordered->map(fn ($p) => $p->latestKunjungan?->tanggal_kunjungan
                ? \Carbon\Carbon::parse($p->latestKunjungan->tanggal_kunjungan)->format('d/m/Y')
                : '-'
            )->join(', ');
            $classesArr   = $ordered->map(fn ($p) => $p->class ?? 'Umum')->all();
            $classStr     = implode(', ', $classesArr);

            // no_telp: ambil dari pelanggan pertama (cukup 1)
            $noTelp = $ordered->first()->no_telp;

            // Records individual per pelanggan — dipakai oleh export (1 baris per pelanggan)
            $records = $ordered->map(fn ($p) => [
                'id'                => $p->id,
                'cabang_id'         => $p->cabang_id,
                'pid'               => $p->pid,
                'cabang'            => $p->cabang?->nama ?? '-',
                'nama_db'           => $p->nama,
                'no_telp'           => $noTelp,
                'alamat'            => $p->alamat ?? '-',
                'latest_visit'      => $p->latestKunjungan?->tanggal_kunjungan
                    ? \Carbon\Carbon::parse($p->latestKunjungan->tanggal_kunjungan)->format('d/m/Y')
                    : '-',
                'total_kedatangan'  => $p->total_kedatangan ?? 0,
                'class'             => $p->class ?? 'Umum',
                'nama_excel'        => $excelEntry['nama'],
            ])->all();

            $foundAll[] = [
                'pids'         => $pids,
                'pid_links'    => $pidLinks,
                'cabang_names' => $cabangNames,
                'nama_db'      => $namaDb,
                'no_telp'      => $noTelp,
                'alamat'       => $alamatStr,
                'latest_visit' => $visitStr,
                'classes'      => $classesArr,
                'class_str'    => $classStr,
                'nama_excel'   => $excelEntry['nama'],
                'records'      => $records,    // untuk export per-baris
            ];
        }

        Session::put(self::SESSION_FOUND,  $foundAll);
        Session::put(self::SESSION_NFOUND, $notFoundAll);
        Session::flash('sbp_has_results', true);

        return redirect()->route('pelanggan.search-by-phone.index');
    }

    // ─── Exports ─────────────────────────────────────────────────────────────

    public function exportFound()
    {
        $data = Session::get(self::SESSION_FOUND, []);
        if (empty($data)) {
            return back()->with('error', 'Tidak ada data untuk diekspor. Lakukan pencarian terlebih dahulu.');
        }

        $filename = 'search_by_phone_ditemukan_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new SearchByPhoneFoundExport($data), $filename);
    }

    public function exportNotFound()
    {
        $data = Session::get(self::SESSION_NFOUND, []);
        if (empty($data)) {
            return back()->with('error', 'Tidak ada data untuk diekspor. Lakukan pencarian terlebih dahulu.');
        }

        $filename = 'search_by_phone_tidak_ditemukan_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new SearchByPhoneNotFoundExport($data), $filename);
    }

    // ─── Template ────────────────────────────────────────────────────────────

    public function downloadTemplate()
    {
        $headers = ['Nama', 'Nomer Telepon'];
        $data    = [
            ['Budi Santoso',  '081234567890'],
            ['Siti Rahayu',   '082345678901'],
            ['Ahmad Fauzi',   '083456789012'],
        ];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

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

        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'sbp_tpl_');
        $writer->save($tempFile);

        return response()->download($tempFile, 'template_search_by_phone.xlsx')->deleteFileAfterSend(true);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '628')) {
            $digits = '0' . substr($digits, 2);
        } elseif (str_starts_with($digits, '8') && strlen($digits) >= 9 && strlen($digits) <= 12) {
            $digits = '0' . $digits;
        }

        return $digits;
    }

    private function phoneVariations(string $normalized): array
    {
        $variations = [$normalized];

        if (str_starts_with($normalized, '0')) {
            $local       = substr($normalized, 1);
            $variations[] = '62' . $local;
            $variations[] = '+62' . $local;
            $variations[] = $local;
        }

        return $variations;
    }

    private function readCsvFile(\Illuminate\Http\UploadedFile $file): array
    {
        $lines = explode("\n", file_get_contents($file->getPathname()));
        $rows  = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $rows[] = str_getcsv($line);
            }
        }

        return $rows;
    }

    private function paginate(array $items, int $perPage, int $page, string $pageName): LengthAwarePaginator
    {
        $page   = max(1, (int) $page);
        $offset = ($page - 1) * $perPage;
        $slice  = array_slice($items, $offset, $perPage);

        return new LengthAwarePaginator($slice, count($items), $perPage, $page, [
            'path'      => request()->url(),
            'pageName'  => $pageName,
            'query'     => request()->query(),
        ]);
    }
}
