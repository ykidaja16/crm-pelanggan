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
            $pelanggan = Pelanggan::with('cabang')
                ->where('pid', 'like', '%' . $this->search . '%')
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
                            'class' => $this->getClass($cumulative, $kunjungans->count()),
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
                    'class' => $this->getClass($cumulative, $kunjungans->count()),
                    'kunjungan_terakhir' => $kunjungans->last()->tanggal_kunjungan->format('Y-m-d')
                ];
            }

            // Untuk export, kembalikan data pelanggan dengan history sebagai baris terpisah
            $data = [];
            foreach ($history as $h) {
                $data[] = [
                    'id' => $pelanggan->id,
                    'pid' => $pelanggan->pid,
                    'nama' => $pelanggan->nama,
                    'cabang' => $pelanggan->cabang?->nama ?? '-',
                    'no_telp' => $pelanggan->no_telp ?? '-',
                    'dob' => $pelanggan->dob ? $pelanggan->dob->format('d-m-Y') : '-',
                    'alamat' => $pelanggan->alamat ?? '-',
                    'kota' => $pelanggan->kota ?? '-',
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
        $endDate = null;
        if ($this->type == 'perbulan') {
            $endDate = \Carbon\Carbon::createFromDate($this->tahun, $this->bulan, 1)->endOfMonth();
        } elseif ($this->type == 'pertahun') {
            $endDate = \Carbon\Carbon::createFromDate($this->tahun, 12, 31);
        }

        $pelanggan = Pelanggan::with(['cabang', 'kunjungans' => function($q) use ($endDate) {
            if ($endDate) {
                $q->where('tanggal_kunjungan', '<=', $endDate);
            }
        }])->get();

        // Hitung total kumulatif dan filter berdasarkan kunjungan di periode
        $pelanggan = $pelanggan->filter(function ($p) use ($endDate) {
            $p->total_biaya = $p->kunjungans->sum('biaya');
            $p->total_kedatangan = $p->kunjungans->count();
            $p->class = $this->getClass($p->total_biaya, $p->total_kedatangan);

            // Ambil kunjungan terakhir di periode
            if ($this->type == 'perbulan') {
                $kunjunganFiltered = $p->kunjungans->filter(function($k) {
                    return $k->tanggal_kunjungan->month == $this->bulan && $k->tanggal_kunjungan->year == $this->tahun;
                });
                $p->tgl_kunjungan = $kunjunganFiltered->sortByDesc('tanggal_kunjungan')->first()?->tanggal_kunjungan->format('Y-m-d') ?? '-';
                return $kunjunganFiltered->isNotEmpty();
            } elseif ($this->type == 'pertahun') {
                $kunjunganFiltered = $p->kunjungans->filter(function($k) {
                    return $k->tanggal_kunjungan->year == $this->tahun;
                });
                $p->tgl_kunjungan = $kunjunganFiltered->sortByDesc('tanggal_kunjungan')->first()?->tanggal_kunjungan->format('Y-m-d') ?? '-';
                return $kunjunganFiltered->isNotEmpty();
            } else {
                $p->tgl_kunjungan = $p->kunjungans->sortByDesc('tanggal_kunjungan')->first()?->tanggal_kunjungan->format('Y-m-d') ?? '-';
                return true;
            }
        });

        // Kembalikan data pelanggan yang difilter
        return $pelanggan->map(function ($p) {
            return [
                'id' => $p->id,
                'pid' => $p->pid,
                'nama' => $p->nama,
                'cabang' => $p->cabang?->nama ?? '-',
                'no_telp' => $p->no_telp ?? '-',
                'dob' => $p->dob ? $p->dob->format('d-m-Y') : '-',
                'alamat' => $p->alamat ?? '-',
                'kota' => $p->kota ?? '-',
                'total_kedatangan' => $p->total_kedatangan,
                'class' => $p->class,
                'total_biaya' => $p->total_biaya,
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
                'PID',
                'Nama Pasien',
                'Cabang',
                'No Telp',
                'DOB',
                'Alamat',
                'Kota',
                'Bulan',
                'Total Bulanan',
                'Total Kumulatif',
                'Kelas',
                'Kunjungan Terakhir'
            ];
        } else {
            return [
                'ID',
                'PID',
                'Nama Pasien',
                'Cabang',
                'No Telp',
                'DOB',
                'Alamat',
                'Kota',
                'Total Kedatangan',
                'Kelas',
                'Total Biaya',
                'Tanggal Kunjungan Terakhir'
            ];
        }
    }

    /**
     * Get class based on total biaya and total kedatangan
     * Potensial: Kedatangan minimal 2x dengan biaya berapapun atau 1x datang dengan minimal biaya 1 Juta
     * Loyal: Kedatangan minimal 5x dengan total biaya berapapun
     * Prioritas: 1x Kedatangan minimal 4 Juta, atau total biaya sudah lebih dari 4 juta
     */
    private function getClass($totalBiaya, $totalKedatangan)
    {
        // Prioritas: 1x Kedatangan minimal 4 Juta, atau total biaya sudah lebih dari 4 juta
        if ($totalKedatangan >= 1 && $totalBiaya >= 4000000) {
            return 'Prioritas';
        }
        
        // Loyal: Kedatangan minimal 5x dengan total biaya berapapun
        if ($totalKedatangan >= 5) {
            return 'Loyal';
        }
        
        // Potensial: Kedatangan minimal 2x dengan biaya berapapun atau 1x datang dengan minimal biaya 1 Juta
        if ($totalKedatangan >= 2 || ($totalKedatangan >= 1 && $totalBiaya >= 1000000)) {
            return 'Potensial';
        }
        
        return 'Potensial'; // Default
    }
}
