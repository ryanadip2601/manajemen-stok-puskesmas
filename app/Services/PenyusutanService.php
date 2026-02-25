<?php

namespace App\Services;

use App\Models\BarangAset;
use App\Models\KategoriAset;
use App\Models\PenyusutanAset;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PenyusutanService
{
    /**
     * Konstanta umur manfaat default berdasarkan golongan aset
     * Sesuai dengan standar akuntansi pemerintah
     */
    const UMUR_MANFAAT = [
        'tanah' => 0,              // Tidak disusutkan
        'peralatan_mesin' => 5,    // 5 tahun
        'gedung_bangunan' => 50,   // 50 tahun
        'jalan_irigasi' => 10,     // 10 tahun
        'aset_tetap_lainnya' => 4, // 4 tahun
        'kdp' => 0,                // Konstruksi Dalam Pengerjaan - tidak disusutkan
    ];

    /**
     * Golongan yang tidak disusutkan
     */
    const TIDAK_DISUSUTKAN = ['tanah', 'kdp'];

    /**
     * Hitung penyusutan per tahun (metode garis lurus)
     * Rumus: (Nilai Perolehan - Nilai Residu) / Umur Manfaat
     */
    public function hitungPenyusutanPerTahun(float $nilaiPerolehan, float $nilaiResidu, int $umurManfaat): float
    {
        if ($umurManfaat <= 0) {
            return 0;
        }
        return ($nilaiPerolehan - $nilaiResidu) / $umurManfaat;
    }

    /**
     * Hitung penyusutan per bulan
     */
    public function hitungPenyusutanPerBulan(float $nilaiPerolehan, float $nilaiResidu, int $umurManfaat): float
    {
        return $this->hitungPenyusutanPerTahun($nilaiPerolehan, $nilaiResidu, $umurManfaat) / 12;
    }

    /**
     * Hitung jumlah bulan dari tanggal perolehan sampai tanggal hitung
     */
    public function hitungJumlahBulan(Carbon $tanggalPerolehan, Carbon $tanggalHitung): int
    {
        // Penyusutan dimulai bulan berikutnya setelah perolehan
        $startDate = $tanggalPerolehan->copy()->startOfMonth()->addMonth();
        $endDate = $tanggalHitung->copy()->endOfMonth();
        
        if ($endDate < $startDate) {
            return 0;
        }
        
        return $startDate->diffInMonths($endDate) + 1;
    }

    /**
     * Hitung akumulasi penyusutan sampai tanggal tertentu
     */
    public function hitungAkumulasiPenyusutan(
        float $nilaiPerolehan,
        float $nilaiResidu,
        int $umurManfaat,
        Carbon $tanggalPerolehan,
        Carbon $tanggalHitung
    ): float {
        $penyusutanPerBulan = $this->hitungPenyusutanPerBulan($nilaiPerolehan, $nilaiResidu, $umurManfaat);
        $jumlahBulan = $this->hitungJumlahBulan($tanggalPerolehan, $tanggalHitung);
        
        // Maksimal akumulasi = nilai perolehan - nilai residu
        $maksAkumulasi = $nilaiPerolehan - $nilaiResidu;
        $akumulasi = $penyusutanPerBulan * $jumlahBulan;
        
        return min($akumulasi, $maksAkumulasi);
    }

    /**
     * Hitung nilai buku aset
     * Rumus: Nilai Perolehan - Akumulasi Penyusutan
     */
    public function hitungNilaiBuku(float $nilaiPerolehan, float $akumulasiPenyusutan): float
    {
        return max(0, $nilaiPerolehan - $akumulasiPenyusutan);
    }

    /**
     * Hitung nilai setelah mutasi
     * Rumus: Nilai Awal + Penambahan - Pengurangan - Koreksi
     */
    public function hitungNilaiMutasi(
        float $nilaiAwal,
        float $penambahan = 0,
        float $pengurangan = 0,
        float $koreksi = 0
    ): float {
        return $nilaiAwal + $penambahan - $pengurangan - $koreksi;
    }

    /**
     * Dapatkan umur manfaat aset
     * Prioritas: override di aset > kategori > default golongan
     */
    public function getUmurManfaat(BarangAset $aset): int
    {
        // Cek override di level aset
        if ($aset->umur_manfaat_override) {
            return $aset->umur_manfaat_override;
        }
        
        // Cek di kategori
        if ($aset->kategoriAset && $aset->kategoriAset->umur_manfaat) {
            return $aset->kategoriAset->umur_manfaat;
        }
        
        // Default berdasarkan golongan
        $golongan = $aset->kategoriAset->golongan ?? 'peralatan_mesin';
        return self::UMUR_MANFAAT[$golongan] ?? 5;
    }

    /**
     * Cek apakah aset dapat disusutkan
     */
    public function dapatDisusutkan(BarangAset $aset): bool
    {
        if ($aset->kategoriAset) {
            // Cek dari kategori
            if (!$aset->kategoriAset->dapat_disusutkan) {
                return false;
            }
            
            // Cek golongan
            $golongan = $aset->kategoriAset->golongan;
            if (in_array($golongan, self::TIDAK_DISUSUTKAN)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Hitung penyusutan lengkap untuk satu aset pada tanggal tertentu
     */
    public function hitungPenyusutanAset(BarangAset $aset, ?Carbon $tanggalHitung = null): array
    {
        $tanggalHitung = $tanggalHitung ?? Carbon::now();
        $tanggalPerolehan = Carbon::parse($aset->tanggal_masuk);
        
        // Nilai perolehan setelah mutasi
        $nilaiPerolehan = $this->hitungNilaiMutasi(
            $aset->harga_perolehan ?? 0,
            $aset->nilai_penambahan ?? 0,
            $aset->nilai_pengurangan ?? 0,
            $aset->nilai_koreksi ?? 0
        );
        
        $nilaiResidu = $aset->nilai_residu ?? 0;
        $umurManfaat = $this->getUmurManfaat($aset);
        $dapatDisusutkan = $this->dapatDisusutkan($aset);
        
        // Jika tidak dapat disusutkan
        if (!$dapatDisusutkan || $umurManfaat <= 0) {
            return [
                'aset_id' => $aset->id,
                'kode_aset' => $aset->kode_aset,
                'nama_aset' => $aset->nama_aset,
                'tanggal_perolehan' => $tanggalPerolehan->format('Y-m-d'),
                'tanggal_hitung' => $tanggalHitung->format('Y-m-d'),
                'nilai_perolehan' => $nilaiPerolehan,
                'nilai_residu' => $nilaiResidu,
                'umur_manfaat' => $umurManfaat,
                'dapat_disusutkan' => false,
                'penyusutan_per_tahun' => 0,
                'penyusutan_per_bulan' => 0,
                'jumlah_bulan' => 0,
                'akumulasi_penyusutan' => 0,
                'nilai_buku' => $nilaiPerolehan,
                'persentase_penyusutan' => 0,
                'sisa_umur_bulan' => null,
            ];
        }
        
        // Hitung penyusutan
        $penyusutanPerTahun = $this->hitungPenyusutanPerTahun($nilaiPerolehan, $nilaiResidu, $umurManfaat);
        $penyusutanPerBulan = $this->hitungPenyusutanPerBulan($nilaiPerolehan, $nilaiResidu, $umurManfaat);
        $jumlahBulan = $this->hitungJumlahBulan($tanggalPerolehan, $tanggalHitung);
        $akumulasiPenyusutan = $this->hitungAkumulasiPenyusutan(
            $nilaiPerolehan, $nilaiResidu, $umurManfaat, $tanggalPerolehan, $tanggalHitung
        );
        $nilaiBuku = $this->hitungNilaiBuku($nilaiPerolehan, $akumulasiPenyusutan);
        
        // Persentase penyusutan
        $persentasePenyusutan = $nilaiPerolehan > 0 
            ? ($akumulasiPenyusutan / $nilaiPerolehan) * 100 
            : 0;
        
        // Sisa umur
        $totalBulanUmur = $umurManfaat * 12;
        $sisaUmurBulan = max(0, $totalBulanUmur - $jumlahBulan);
        
        return [
            'aset_id' => $aset->id,
            'kode_aset' => $aset->kode_aset,
            'nama_aset' => $aset->nama_aset,
            'kategori' => $aset->kategoriAset->nama_kategori ?? '-',
            'golongan' => $aset->kategoriAset->golongan ?? '-',
            'ruangan' => $aset->ruangan->nama_ruangan ?? '-',
            'tanggal_perolehan' => $tanggalPerolehan->format('Y-m-d'),
            'tahun_perolehan' => $tanggalPerolehan->year,
            'tanggal_hitung' => $tanggalHitung->format('Y-m-d'),
            'nilai_perolehan' => $nilaiPerolehan,
            'nilai_residu' => $nilaiResidu,
            'umur_manfaat' => $umurManfaat,
            'dapat_disusutkan' => true,
            'penyusutan_per_tahun' => round($penyusutanPerTahun, 2),
            'penyusutan_per_bulan' => round($penyusutanPerBulan, 2),
            'jumlah_bulan' => $jumlahBulan,
            'akumulasi_penyusutan' => round($akumulasiPenyusutan, 2),
            'nilai_buku' => round($nilaiBuku, 2),
            'persentase_penyusutan' => round($persentasePenyusutan, 2),
            'sisa_umur_bulan' => $sisaUmurBulan,
            'sisa_umur_tahun' => floor($sisaUmurBulan / 12),
            'sudah_habis' => $sisaUmurBulan <= 0,
        ];
    }

    /**
     * Simpan record penyusutan untuk periode tertentu
     */
    public function simpanPenyusutan(BarangAset $aset, int $tahun, ?int $bulan = null): ?PenyusutanAset
    {
        $tanggalHitung = $bulan 
            ? Carbon::create($tahun, $bulan)->endOfMonth()
            : Carbon::create($tahun, 12, 31);
        
        $hasil = $this->hitungPenyusutanAset($aset, $tanggalHitung);
        
        if (!$hasil['dapat_disusutkan']) {
            return null;
        }
        
        // Ambil akumulasi awal dari periode sebelumnya
        $akumulasiAwal = $this->getAkumulasiSebelumnya($aset->id, $tahun, $bulan);
        
        // Penyusutan untuk periode ini
        $penyusutanPeriode = $bulan 
            ? $hasil['penyusutan_per_bulan']
            : $hasil['penyusutan_per_tahun'];
        
        return PenyusutanAset::updateOrCreate(
            [
                'barang_aset_id' => $aset->id,
                'tahun' => $tahun,
                'bulan' => $bulan,
            ],
            [
                'tanggal_hitung' => $tanggalHitung,
                'nilai_perolehan' => $hasil['nilai_perolehan'],
                'nilai_residu' => $hasil['nilai_residu'],
                'umur_manfaat' => $hasil['umur_manfaat'],
                'penyusutan_per_tahun' => $hasil['penyusutan_per_tahun'],
                'penyusutan_per_bulan' => $hasil['penyusutan_per_bulan'],
                'penyusutan_periode' => $penyusutanPeriode,
                'akumulasi_penyusutan_awal' => $akumulasiAwal,
                'akumulasi_penyusutan_akhir' => $hasil['akumulasi_penyusutan'],
                'nilai_buku_awal' => $hasil['nilai_perolehan'] - $akumulasiAwal,
                'nilai_buku_akhir' => $hasil['nilai_buku'],
                'mutasi_penambahan' => $aset->nilai_penambahan ?? 0,
                'mutasi_pengurangan' => $aset->nilai_pengurangan ?? 0,
                'mutasi_koreksi' => $aset->nilai_koreksi ?? 0,
                'metode_penyusutan' => 'garis_lurus',
                'created_by' => auth()->id(),
            ]
        );
    }

    /**
     * Dapatkan akumulasi penyusutan periode sebelumnya
     */
    protected function getAkumulasiSebelumnya(int $asetId, int $tahun, ?int $bulan): float
    {
        $query = PenyusutanAset::where('barang_aset_id', $asetId);
        
        if ($bulan) {
            // Cari bulan sebelumnya
            if ($bulan > 1) {
                $query->where('tahun', $tahun)->where('bulan', $bulan - 1);
            } else {
                // Januari - cari Desember tahun sebelumnya
                $query->where('tahun', $tahun - 1)->where('bulan', 12);
            }
        } else {
            // Tahunan - cari tahun sebelumnya
            $query->where('tahun', $tahun - 1)->whereNull('bulan');
        }
        
        $sebelumnya = $query->first();
        return $sebelumnya ? $sebelumnya->akumulasi_penyusutan_akhir : 0;
    }

    /**
     * Proses penyusutan untuk semua aset pada tahun tertentu
     */
    public function prosesPenyusutanTahunan(int $tahun): array
    {
        $hasil = [
            'total_aset' => 0,
            'aset_diproses' => 0,
            'aset_skip' => 0,
            'total_penyusutan' => 0,
            'errors' => [],
        ];
        
        $asetList = BarangAset::with(['kategoriAset', 'ruangan'])
            ->where('status_aset', 'aktif')
            ->whereNotNull('harga_perolehan')
            ->where('harga_perolehan', '>', 0)
            ->get();
        
        $hasil['total_aset'] = $asetList->count();
        
        foreach ($asetList as $aset) {
            try {
                $penyusutan = $this->simpanPenyusutan($aset, $tahun);
                
                if ($penyusutan) {
                    $hasil['aset_diproses']++;
                    $hasil['total_penyusutan'] += $penyusutan->penyusutan_periode;
                } else {
                    $hasil['aset_skip']++;
                }
            } catch (\Exception $e) {
                $hasil['errors'][] = [
                    'aset_id' => $aset->id,
                    'kode_aset' => $aset->kode_aset,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $hasil;
    }

    /**
     * Generate laporan penyusutan per golongan
     */
    public function laporanPerGolongan(int $tahun): array
    {
        $result = [];
        
        foreach (self::UMUR_MANFAAT as $golongan => $umur) {
            $data = PenyusutanAset::join('barang_aset', 'penyusutan_aset.barang_aset_id', '=', 'barang_aset.id')
                ->join('kategori_aset', 'barang_aset.kategori_aset_id', '=', 'kategori_aset.id')
                ->where('kategori_aset.golongan', $golongan)
                ->where('penyusutan_aset.tahun', $tahun)
                ->whereNull('penyusutan_aset.bulan')
                ->selectRaw('
                    COUNT(*) as jumlah_aset,
                    SUM(penyusutan_aset.nilai_perolehan) as total_nilai_perolehan,
                    SUM(penyusutan_aset.akumulasi_penyusutan_akhir) as total_akumulasi,
                    SUM(penyusutan_aset.nilai_buku_akhir) as total_nilai_buku,
                    SUM(penyusutan_aset.penyusutan_periode) as total_penyusutan_tahun
                ')
                ->first();
            
            $result[$golongan] = [
                'golongan' => $golongan,
                'nama_golongan' => $this->getNamaGolongan($golongan),
                'umur_manfaat' => $umur,
                'jumlah_aset' => $data->jumlah_aset ?? 0,
                'total_nilai_perolehan' => $data->total_nilai_perolehan ?? 0,
                'total_akumulasi' => $data->total_akumulasi ?? 0,
                'total_nilai_buku' => $data->total_nilai_buku ?? 0,
                'total_penyusutan_tahun' => $data->total_penyusutan_tahun ?? 0,
            ];
        }
        
        return $result;
    }

    /**
     * Generate laporan penyusutan per tahun perolehan
     */
    public function laporanPerTahunPerolehan(int $tahunHitung): array
    {
        return PenyusutanAset::join('barang_aset', 'penyusutan_aset.barang_aset_id', '=', 'barang_aset.id')
            ->where('penyusutan_aset.tahun', $tahunHitung)
            ->whereNull('penyusutan_aset.bulan')
            ->selectRaw('
                barang_aset.tahun_perolehan,
                COUNT(*) as jumlah_aset,
                SUM(penyusutan_aset.nilai_perolehan) as total_nilai_perolehan,
                SUM(penyusutan_aset.akumulasi_penyusutan_akhir) as total_akumulasi,
                SUM(penyusutan_aset.nilai_buku_akhir) as total_nilai_buku,
                SUM(penyusutan_aset.penyusutan_periode) as total_penyusutan
            ')
            ->groupBy('barang_aset.tahun_perolehan')
            ->orderBy('barang_aset.tahun_perolehan', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get nama golongan yang readable
     */
    public function getNamaGolongan(string $golongan): string
    {
        $nama = [
            'tanah' => 'Tanah',
            'peralatan_mesin' => 'Peralatan dan Mesin',
            'gedung_bangunan' => 'Gedung dan Bangunan',
            'jalan_irigasi' => 'Jalan, Irigasi dan Jaringan',
            'aset_tetap_lainnya' => 'Aset Tetap Lainnya',
            'kdp' => 'Konstruksi Dalam Pengerjaan',
        ];
        
        return $nama[$golongan] ?? $golongan;
    }

    /**
     * Export data ke format siap import
     */
    public function exportData(int $tahun, string $format = 'array'): array
    {
        $data = PenyusutanAset::with(['barangAset.kategoriAset', 'barangAset.ruangan'])
            ->where('tahun', $tahun)
            ->whereNull('bulan')
            ->get()
            ->map(function ($item) {
                return [
                    'kode_aset' => $item->barangAset->kode_aset,
                    'nama_aset' => $item->barangAset->nama_aset,
                    'kategori' => $item->barangAset->kategoriAset->nama_kategori ?? '-',
                    'golongan' => $item->barangAset->kategoriAset->golongan ?? '-',
                    'ruangan' => $item->barangAset->ruangan->nama_ruangan ?? '-',
                    'tahun_perolehan' => $item->barangAset->tahun_perolehan,
                    'nilai_perolehan' => $item->nilai_perolehan,
                    'umur_manfaat' => $item->umur_manfaat,
                    'penyusutan_per_tahun' => $item->penyusutan_per_tahun,
                    'akumulasi_penyusutan' => $item->akumulasi_penyusutan_akhir,
                    'nilai_buku' => $item->nilai_buku_akhir,
                ];
            });
        
        return $data->toArray();
    }
}
