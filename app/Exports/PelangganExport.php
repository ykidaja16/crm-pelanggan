<?php

namespace App\Exports;

use App\Models\Pelanggan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PelangganExport implements FromCollection, WithHeadings
{
    protected $bulan;
    protected $tahun;
    protected $type;
    protected $search;

    public function __construct($bulan, $tahun, $type, $search)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->type = $type;
        $this->search = $search;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // Jika ada pencarian pelanggan spesifik
        if ($this->search) {
            $pelanggan = Pelanggan::where('nik', 'like', '%' . $this->search . '%')
                                  ->orWhere('nama', 'like', '%' . $this->search . '%')
                                  ->first();

            if (!$pelanggan) {
                return collect();
            }

            // Ambil semua kunjungan pelanggan
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

            // Untuk export, kembalikan data pelanggan dengan history sebagai baris terpisah
            $data = [];
            foreach ($history as $h) {
                $data[] = [
                    'id' => $pelanggan->id,
                    'nik' => $pelanggan->nik,
                    'nama' => $pelanggan->nama,
                    'alamat' => $pelanggan->alamat,
                    'bulan' => $h['bulan'],
                    'total_bulanan' => $h['total_bulanan'],
                    'total_kumulatif' => $h['total_kumulatif'],
                    'class' => $h['class'],
                    'kunjungan_terakhir' => $h['kunjungan_terakhir']
                ];
            }
            return collect($data);
        }

        // Tentukan tanggal akhir periode berdasarkan type
        // Untuk 'semua', tidak ada filter tanggal - tampilkan semua data
        $endDate = null;
        if ($this->type == 'perbulan') {
            $endDate = \Carbon\Carbon::createFromDate($this->tahun, $this->bulan, 1)->endOfMonth();
        } elseif ($this->type == 'pertahun') {
            $endDate = \Carbon\Carbon::createFromDate($this->tahun, 12, 31);
        }

        $pelanggan = Pelanggan::with(['kunjungans' => function($q) use ($endDate) {
            if ($endDate) {
                $q->where('tanggal_kunjungan', '<=', $endDate);
            }
            // Jika endDate null (tipe 'semua'), tidak ada filter - ambil semua kunjungan
        }])->get();

        // Hitung total kumulatif dan filter berdasarkan kunjungan di periode
        $pelanggan = $pelanggan->filter(function ($p) use ($endDate) {
            $p->total = $p->kunjungans->sum('biaya');
            $p->class = $this->getClass($p->total);

            // Ambil kunjungan terakhir di periode
            if ($this->type == 'perbulan') {
                $kunjunganFiltered = $p->kunjungans->filter(function($k) {
                    return $k->tanggal_kunjungan->month == $this->bulan && $k->tanggal_kunjungan->year == $this->tahun;
                });
                $p->tgl_kunjungan = $kunjunganFiltered->sortByDesc('tanggal_kunjungan')->first()?->tanggal_kunjungan->format('Y-m-d') ?? '-';
                // Filter: hanya yang ada kunjungan di bulan tersebut
                return $kunjunganFiltered->isNotEmpty();
            } elseif ($this->type == 'pertahun') {
                $kunjunganFiltered = $p->kunjungans->filter(function($k) {
                    return $k->tanggal_kunjungan->year == $this->tahun;
                });
                $p->tgl_kunjungan = $kunjunganFiltered->sortByDesc('tanggal_kunjungan')->first()?->tanggal_kunjungan->format('Y-m-d') ?? '-';
                // Filter: hanya yang ada kunjungan di tahun tersebut
                return $kunjunganFiltered->isNotEmpty();
            } else {
                // Untuk semua data, export SEMUA pelanggan tanpa filter
                $p->tgl_kunjungan = $p->kunjungans->sortByDesc('tanggal_kunjungan')->first()?->tanggal_kunjungan->format('Y-m-d') ?? '-';
                return true;
            }

        });

        // Kembalikan data pelanggan yang difilter
        return $pelanggan->map(function ($p) {
            return [
                'id' => $p->id,
                'nik' => $p->nik,
                'nama' => $p->nama,
                'alamat' => $p->alamat,
                'class' => $p->class,
                'total' => $p->total,
                'tgl_kunjungan' => $p->tgl_kunjungan
            ];
        });
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        if ($this->search) {
            return [
                'ID',
                'NIK',
                'Nama',
                'Alamat',
                'Bulan',
                'Total Bulanan',
                'Total Kumulatif',
                'Kelas',
                'Kunjungan Terakhir'
            ];
        } else {
            return [
                'ID',
                'NIK',
                'Nama',
                'Alamat',
                'Kelas',
                'Total',
                'Tanggal Kunjungan Terakhir'
            ];
        }
    }

    private function getClass($total)
    {
        if ($total >= 5000000) return 'Platinum';
        if ($total >= 1000000) return 'Gold';
        if ($total >= 100000) return 'Silver';
        return 'Basic';
    }
}
