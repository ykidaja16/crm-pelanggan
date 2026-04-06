<?php

namespace App\Exports;

use App\Models\Pelanggan;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PelangganExport implements FromCollection, WithHeadings
{
    protected $bulan;
    protected $tahun;
    protected $type;
    protected $search;
    protected $cabangId;
    protected $omsetRange;
    protected $kedatanganRange;
    protected $kelas;
    protected $tipePelanggan;
    protected $tanggalMulai;
    protected $tanggalSelesai;

    public function __construct(
        $bulan,
        $tahun,
        $type,
        $search,
        $cabangId        = null,
        $omsetRange      = null,
        $kedatanganRange = null,
        $kelas           = null,
        $tipePelanggan   = null,
        $tanggalMulai    = null,
        $tanggalSelesai  = null
    ) {
        $this->bulan           = $bulan;
        $this->tahun           = $tahun;
        $this->type            = $type;
        $this->search          = $search;
        $this->cabangId        = $cabangId;
        $this->omsetRange      = $omsetRange;
        $this->kedatanganRange = $kedatanganRange;
        $this->kelas           = $kelas;
        $this->tipePelanggan   = $tipePelanggan;
        $this->tanggalMulai    = $tanggalMulai;
        $this->tanggalSelesai  = $tanggalSelesai;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // Jika ada pencarian pelanggan spesifik (by PID atau nama)
        if ($this->search) {
            $pelanggan = Pelanggan::with(['cabang', 'kunjungans.kelompokPelanggan'])
                ->where(function ($q) {
                    $q->where('pid', 'like', '%' . $this->search . '%')
                      ->orWhere('nama', 'like', '%' . $this->search . '%');
                })
                ->first();

            if (!$pelanggan) {
                return collect();
            }

            // Load riwayat perubahan kelas urut ASC untuk lookup historis per kunjungan
            $classHistories = $pelanggan->classHistories()
                ->reorder()
                ->orderBy('changed_at', 'asc')
                ->get();

            // Ambil semua kunjungan pelanggan urut ASC by tanggal
            $kunjungans = $pelanggan->kunjungans()
                ->with('kelompokPelanggan')
                ->orderBy('tanggal_kunjungan', 'asc')
                ->get();

            // Buat satu baris per kunjungan (bukan per bulan)
            // Kunjungans sudah urut ASC by tanggal untuk kalkulasi kumulatif
            $data                 = [];
            $cumulative           = 0;
            $cumulativeBiaya      = 0;
            $cumulativeKedatangan = 0;
            $hasHighValue         = false;

            foreach ($kunjungans as $k) {
                $cumulative           += $k->biaya ?? 0;
                $cumulativeBiaya      += $k->biaya ?? 0;
                $cumulativeKedatangan += $k->total_kedatangan ?? 1;
                if (($k->biaya ?? 0) >= 4000000) {
                    $hasHighValue = true;
                }

                // Kelas historis: kelas yang berlaku pada saat tanggal kunjungan ini
                if ($classHistories->isEmpty()) {
                    // Tidak ada history → hitung dinamis berdasarkan kunjungan kumulatif s.d. tanggal ini
                    $classAtTime = Pelanggan::calculateClass(
                        $cumulativeKedatangan,
                        $cumulativeBiaya,
                        $hasHighValue,
                        (bool) $pelanggan->is_pelanggan_khusus
                    );
                } else {
                    $classAtTime = $this->getClassAtDate(
                        $k->tanggal_kunjungan,
                        $classHistories,
                        $pelanggan->class
                    );
                }

                $data[] = [
                    'id'                => $pelanggan->id,
                    'pid'               => $pelanggan->pid,
                    'nama'              => $pelanggan->nama,
                    'cabang'            => $pelanggan->cabang?->nama ?? '-',
                    'no_telp'           => $pelanggan->no_telp ?? '-',
                    'dob'               => $pelanggan->dob ? $pelanggan->dob->format('d-m-Y') : '-',
                    'alamat'            => $pelanggan->alamat ?? '-',
                    'kota'              => $pelanggan->kota ?? '-',
                    // Tanggal lengkap (dd-mm-yyyy), bukan hanya bulan-tahun
                    'tanggal_kunjungan' => $k->tanggal_kunjungan
                                            ? $k->tanggal_kunjungan->format('d-m-Y')
                                            : '-',
                    'biaya'             => $k->biaya ?? 0,
                    'total_kumulatif'   => $cumulative,
                    'class'             => $classAtTime,
                ];
            }

            return collect($data);
        }

        // Tentukan tanggal akhir periode berdasarkan type
        $endDate = null;
        if ($this->type == 'perbulan' && $this->bulan && $this->tahun) {
            $endDate = \Carbon\Carbon::createFromDate($this->tahun, $this->bulan, 1)->endOfMonth();
        } elseif ($this->type == 'pertahun' && $this->tahun) {
            $endDate = \Carbon\Carbon::createFromDate($this->tahun, 12, 31)->endOfDay();
        } elseif ($this->type == 'range' && $this->tanggalMulai && $this->tanggalSelesai) {
            $endDate = \Carbon\Carbon::parse($this->tanggalSelesai)->endOfDay();
        }

        $query = Pelanggan::with([
            'cabang',
            'kunjungans' => function($q) use ($endDate) {
                if ($endDate) {
                    $q->where('tanggal_kunjungan', '<=', $endDate);
                }
                $q->orderBy('tanggal_kunjungan', 'asc');
            },
            'classHistories' => function($q) {
                // Urut ASC agar getClassAtDate() bisa break lebih awal
                $q->reorder()->orderBy('changed_at', 'asc');
            },
        ]);

        // Apply cabang filter
        if ($this->cabangId) {
            $query->where('cabang_id', $this->cabangId);
        }

        // Apply kelas filter
        if ($this->kelas) {
            $query->where('class', $this->kelas);
        }

        // Apply tipe pelanggan filter
        if ($this->tipePelanggan === 'khusus') {
            $query->where('is_pelanggan_khusus', true);
        } elseif ($this->tipePelanggan === 'biasa') {
            $query->where(function ($q) {
                $q->where('is_pelanggan_khusus', false)->orWhereNull('is_pelanggan_khusus');
            });
        }

        $pelanggan = $query->get();

        // Filter berdasarkan kunjungan di periode, tapi gunakan data asli dari database
        $pelanggan = $pelanggan->filter(function ($p) use ($endDate) {
            // Gunakan nilai yang sudah tersimpan di database (tidak dihitung ulang dari kunjungan filtered)
            // Hanya gunakan kunjungan filtered untuk menentukan apakah pelanggan masuk filter periode

            if ($this->type == 'perbulan') {
                $kunjunganFiltered = $p->kunjungans->filter(function($k) {
                    return $k->tanggal_kunjungan->month == $this->bulan
                        && $k->tanggal_kunjungan->year  == $this->tahun;
                });
                $p->tgl_kunjungan = $kunjunganFiltered->sortByDesc('tanggal_kunjungan')->first()?->tanggal_kunjungan->format('d-m-Y') ?? '-';
                return $kunjunganFiltered->isNotEmpty();

            } elseif ($this->type == 'pertahun') {
                $kunjunganFiltered = $p->kunjungans->filter(function($k) {
                    return $k->tanggal_kunjungan->year == $this->tahun;
                });
                $p->tgl_kunjungan = $kunjunganFiltered->sortByDesc('tanggal_kunjungan')->first()?->tanggal_kunjungan->format('d-m-Y') ?? '-';
                return $kunjunganFiltered->isNotEmpty();

            } elseif ($this->type == 'range' && $this->tanggalMulai && $this->tanggalSelesai) {
                $mulai   = $this->tanggalMulai;
                $selesai = $this->tanggalSelesai;
                $kunjunganFiltered = $p->kunjungans->filter(function($k) use ($mulai, $selesai) {
                    $tgl = $k->tanggal_kunjungan->toDateString();
                    return $tgl >= $mulai && $tgl <= $selesai;
                });
                $p->tgl_kunjungan = $kunjunganFiltered->sortByDesc('tanggal_kunjungan')->first()?->tanggal_kunjungan->format('d-m-Y') ?? '-';
                return $kunjunganFiltered->isNotEmpty();

            } else {
                // type = 'semua' atau kosong → tampilkan semua
                $p->tgl_kunjungan = $p->kunjungans->sortByDesc('tanggal_kunjungan')->first()?->tanggal_kunjungan->format('d-m-Y') ?? '-';
                return true;
            }
        });

        // Hitung nilai akumulatif s/d endDate untuk setiap pelanggan.
        // $p->kunjungans sudah di-load dengan constraint (<= endDate),
        // sehingga sum() di bawah menghasilkan nilai kumulatif s/d endDate,
        // bukan nilai ALL-TIME dari kolom total_biaya / total_kedatangan di DB.
        $pelanggan->each(function ($p) use ($endDate) {
            if ($endDate) {
                // Akumulasi biaya s/d endDate
                $p->total_biaya_range      = (float) $p->kunjungans->sum('biaya');
                // Akumulasi kedatangan s/d endDate
                $p->total_kedatangan_range = (int)   $p->kunjungans->sum('total_kedatangan');

                // Hitung kelas berdasarkan data kumulatif s/d endDate
                if ($p->classHistories->isEmpty()) {
                    $hasHighValue = $p->kunjungans->contains(fn($k) => ($k->biaya ?? 0) >= 4000000);
                    $p->class_at_range = Pelanggan::calculateClass(
                        $p->total_kedatangan_range,
                        $p->total_biaya_range,
                        $hasHighValue,
                        (bool) $p->is_pelanggan_khusus
                    );
                } else {
                    $p->class_at_range = $this->getClassAtDate($endDate, $p->classHistories, $p->class);
                }
            } else {
                // type = 'semua' - tidak ada endDate, gunakan nilai ALL-TIME dari DB
                $p->total_biaya_range      = (float) $p->total_biaya;
                $p->total_kedatangan_range = (int)   $p->total_kedatangan;
                // Hitung kelas dari total keseluruhan
                if ($p->classHistories->isEmpty()) {
                    $hasHighValue = $p->kunjungans->contains(fn($k) => ($k->biaya ?? 0) >= 4000000);
                    $p->class_at_range = Pelanggan::calculateClass(
                        $p->total_kedatangan_range,
                        $p->total_biaya_range,
                        $hasHighValue,
                        (bool) $p->is_pelanggan_khusus
                    );
                } else {
                    $p->class_at_range = $p->class ?? 'Umum';
                }
            }
        });

        // Apply omset range filter (gunakan nilai range, bukan ALL-TIME)
        if ($this->omsetRange !== null && $this->omsetRange !== '') {
            $pelanggan = $pelanggan->filter(function ($p) {
                $biaya = $p->total_biaya_range;
                switch ($this->omsetRange) {
                    case '0':
                        return $biaya < 1000000;
                    case '1':
                        return $biaya >= 1000000 && $biaya < 4000000;
                    case '2':
                        return $biaya >= 4000000;
                    default:
                        return true;
                }
            });
        }

        // Apply kedatangan range filter (gunakan nilai range, bukan ALL-TIME)
        if ($this->kedatanganRange !== null && $this->kedatanganRange !== '') {
            $pelanggan = $pelanggan->filter(function ($p) {
                $kedatangan = $p->total_kedatangan_range;
                switch ($this->kedatanganRange) {
                    case '0':
                        return $kedatangan <= 2;
                    case '1':
                        return $kedatangan >= 3 && $kedatangan <= 4;
                    case '2':
                        return $kedatangan > 4;
                    default:
                        return true;
                }
            });
        }

        // Kembalikan data pelanggan yang difilter.
        // Kolom total_biaya, total_kedatangan, class menggunakan nilai range (s/d endDate),
        // bukan nilai ALL-TIME dari database.
        return $pelanggan->map(function ($p) {
            return [
                'id'               => $p->id,
                'pid'              => $p->pid,
                'nama'             => $p->nama,
                'cabang'           => $p->cabang?->nama ?? '-',
                'no_telp'          => $p->no_telp ?? '-',
                'dob'              => $p->dob ? $p->dob->format('d-m-Y') : '-',
                'alamat'           => $p->alamat ?? '-',
                'kota'             => $p->kota ?? '-',
                'total_kedatangan' => $p->total_kedatangan_range,
                'class'            => $p->class_at_range,
                'total_biaya'      => $p->total_biaya_range,
                'tgl_kunjungan'    => $p->tgl_kunjungan,
            ];
        });
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        if ($this->search) {
            // Search mode: satu baris per kunjungan dengan tanggal lengkap
            return [
                'ID',
                'PID',
                'Nama Pasien',
                'Cabang',
                'No Telp',
                'DOB',
                'Alamat',
                'Kota',
                'Tanggal Kunjungan',
                'Biaya',
                'Total Kumulatif',
                'Kelas',
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
     * Tentukan kelas pelanggan pada saat tanggal kunjungan tertentu
     * berdasarkan riwayat perubahan kelas (pelanggan_class_histories).
     *
     * Logika:
     * - Cari entry history terakhir yang changed_at <= tanggal kunjungan, ambil new_class-nya.
     * - Jika tidak ada entry yang cocok (kunjungan terjadi SEBELUM perubahan kelas pertama),
     *   gunakan previous_class dari entry pertama (kelas awal sebelum ada perubahan).
     * - Jika tidak ada history sama sekali, default 'Potensial'.
     *
     * PENTING: Jangan fallback ke $currentClass karena itu kelas SAAT INI,
     * bukan kelas historis saat kunjungan terjadi.
     *
     * @param  mixed       $visitDate       Tanggal kunjungan (Carbon|string)
     * @param  \Illuminate\Support\Collection $classHistories  History kelas urut ASC
     * @param  string|null $currentClass    Tidak digunakan untuk fallback (deprecated)
     */
    private function getClassAtDate($visitDate, $classHistories, ?string $currentClass = null): string
    {
        $visitDateStr = \Carbon\Carbon::parse($visitDate)->toDateString();
        $classAtTime  = null;

        foreach ($classHistories as $history) {
            $historyDateStr = $history->changed_at->toDateString();
            if ($historyDateStr <= $visitDateStr) {
                $classAtTime = $history->new_class;
            } else {
                // Karena sudah urut ASC, begitu melewati tanggal kunjungan bisa break
                break;
            }
        }

        // Ada history yang cocok → kembalikan kelas pada saat itu
        if ($classAtTime !== null) {
            return $classAtTime;
        }

        // Tidak ada history entry sebelum tanggal kunjungan ini.
        // Berarti kunjungan terjadi SEBELUM perubahan kelas pertama.
        // Gunakan previous_class dari entry pertama = kelas awal sebelum ada perubahan.
        $firstHistory = $classHistories->first();
        if ($firstHistory && $firstHistory->previous_class) {
            return $firstHistory->previous_class;
        }

        // Tidak ada history sama sekali → gunakan kelas saat ini sebagai fallback
        return $currentClass ?? 'Umum';
    }
}
