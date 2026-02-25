<?php

namespace App\Models;

use App\Services\PenyusutanService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BarangAset extends Model
{
    use HasFactory;

    protected $table = 'barang_aset';

    protected $fillable = [
        'kode_aset',
        'nama_aset',
        'kategori_aset_id',
        'ruangan_id',
        'kondisi',
        'tanggal_masuk',
        'tahun_perolehan',
        'sumber_dana',
        'nomor_register',
        'no_sertifikat',
        'asal_perolehan',
        'ukuran_konstruksi',
        'jumlah',
        'harga_perolehan',
        'umur_manfaat_override',
        'nilai_residu',
        'nilai_penambahan',
        'nilai_pengurangan',
        'nilai_koreksi',
        'status_aset',
        'tanggal_penghapusan',
        'alasan_penghapusan',
        'merk',
        'spesifikasi',
        'foto',
        'keterangan',
        'butuh_perbaikan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_masuk' => 'date',
        'tanggal_penghapusan' => 'date',
        'tahun_perolehan' => 'integer',
        'jumlah' => 'integer',
        'umur_manfaat_override' => 'integer',
        'harga_perolehan' => 'decimal:2',
        'nilai_residu' => 'decimal:2',
        'nilai_penambahan' => 'decimal:2',
        'nilai_pengurangan' => 'decimal:2',
        'nilai_koreksi' => 'decimal:2',
        'butuh_perbaikan' => 'boolean',
    ];

    // Kondisi options
    public static function getKondisiOptions()
    {
        return [
            'baik' => 'Baik',
            'rusak_ringan' => 'Rusak Ringan',
            'rusak_berat' => 'Rusak Berat',
        ];
    }

    public static function getStatusAsetOptions()
    {
        return [
            'aktif' => 'Aktif',
            'tidak_aktif' => 'Tidak Aktif',
            'dihapuskan' => 'Dihapuskan',
            'dipindahkan' => 'Dipindahkan',
        ];
    }

    public static function getSumberDanaOptions()
    {
        return [
            'APBD' => 'APBD',
            'APBN' => 'APBN',
            'BLUD' => 'BLUD',
            'DAK' => 'DAK',
            'BOK' => 'BOK',
            'JKN' => 'JKN',
            'Hibah' => 'Hibah',
            'Lainnya' => 'Lainnya',
        ];
    }

    public static function getKondisiBadgeClass($kondisi)
    {
        return match($kondisi) {
            'baik' => 'bg-emerald-500/20 text-emerald-400',
            'rusak_ringan' => 'bg-amber-500/20 text-amber-400',
            'rusak_berat' => 'bg-red-500/20 text-red-400',
            default => 'bg-slate-500/20 text-slate-400',
        };
    }

    public static function getStatusBadgeClass($status)
    {
        return match($status) {
            'aktif' => 'bg-emerald-500/20 text-emerald-400',
            'tidak_aktif' => 'bg-slate-500/20 text-slate-400',
            'dihapuskan' => 'bg-red-500/20 text-red-400',
            'dipindahkan' => 'bg-blue-500/20 text-blue-400',
            default => 'bg-slate-500/20 text-slate-400',
        };
    }

    // Relationships
    public function kategoriAset()
    {
        return $this->belongsTo(KategoriAset::class);
    }

    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class);
    }

    public function pemeliharaan()
    {
        return $this->hasMany(PemeliharaanAset::class);
    }

    public function penyusutan()
    {
        return $this->hasMany(PenyusutanAset::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeKondisi($query, $kondisi)
    {
        return $query->where('kondisi', $kondisi);
    }

    public function scopeRuangan($query, $ruanganId)
    {
        return $query->where('ruangan_id', $ruanganId);
    }

    public function scopeKategori($query, $kategoriId)
    {
        return $query->where('kategori_aset_id', $kategoriId);
    }

    public function scopeButuhPerbaikan($query)
    {
        return $query->where('butuh_perbaikan', true);
    }

    public function scopeRusak($query)
    {
        return $query->whereIn('kondisi', ['rusak_ringan', 'rusak_berat']);
    }

    public function scopeAktif($query)
    {
        return $query->where('status_aset', 'aktif');
    }

    public function scopeDapatDisusutkan($query)
    {
        return $query->whereHas('kategoriAset', function($q) {
            $q->where('dapat_disusutkan', true)
              ->whereNotIn('golongan', KategoriAset::GOLONGAN_TIDAK_DISUSUTKAN);
        });
    }

    public function scopeTahunPerolehan($query, $tahun)
    {
        return $query->where('tahun_perolehan', $tahun);
    }

    // Accessors
    public function getKondisiLabelAttribute()
    {
        return self::getKondisiOptions()[$this->kondisi] ?? $this->kondisi;
    }

    public function getStatusLabelAttribute()
    {
        return self::getStatusAsetOptions()[$this->status_aset] ?? $this->status_aset;
    }

    public function getTotalNilaiAttribute()
    {
        return $this->jumlah * ($this->harga_perolehan ?? 0);
    }

    public function getNilaiSetelahMutasiAttribute()
    {
        return ($this->harga_perolehan ?? 0) 
             + ($this->nilai_penambahan ?? 0) 
             - ($this->nilai_pengurangan ?? 0) 
             - ($this->nilai_koreksi ?? 0);
    }

    public function getFotoUrlAttribute()
    {
        if ($this->foto) {
            // Handle both old storage/ paths and new uploads/ paths
            if (str_starts_with($this->foto, 'uploads/')) {
                return asset($this->foto);
            }
            return asset('storage/' . $this->foto);
        }
        return null;
    }

    public function getLastMaintenanceAttribute()
    {
        return $this->pemeliharaan()->latest('tanggal_pemeliharaan')->first();
    }

    public function getUmurManfaatAttribute()
    {
        // Override di level aset
        if ($this->umur_manfaat_override) {
            return $this->umur_manfaat_override;
        }
        // Dari kategori
        if ($this->kategoriAset) {
            return $this->kategoriAset->umur_manfaat;
        }
        return 5; // Default
    }

    public function getDapatDisusutkanAttribute()
    {
        if (!$this->kategoriAset) {
            return true;
        }
        return $this->kategoriAset->dapat_disusutkan && 
               !in_array($this->kategoriAset->golongan, KategoriAset::GOLONGAN_TIDAK_DISUSUTKAN);
    }

    // Penyusutan Methods
    public function hitungPenyusutan(?Carbon $tanggalHitung = null): array
    {
        $service = new PenyusutanService();
        return $service->hitungPenyusutanAset($this, $tanggalHitung);
    }

    public function getAkumulasiPenyusutanAttribute()
    {
        $hasil = $this->hitungPenyusutan();
        return $hasil['akumulasi_penyusutan'] ?? 0;
    }

    public function getNilaiBukuAttribute()
    {
        $hasil = $this->hitungPenyusutan();
        return $hasil['nilai_buku'] ?? $this->nilai_setelah_mutasi;
    }

    public function getPenyusutanTerakhirAttribute()
    {
        return $this->penyusutan()->latest('tahun')->latest('bulan')->first();
    }

    public function getUsiaAsetBulanAttribute()
    {
        $tanggalPerolehan = Carbon::parse($this->tanggal_masuk);
        return $tanggalPerolehan->diffInMonths(Carbon::now());
    }

    public function getUsiaAsetTahunAttribute()
    {
        return floor($this->usia_aset_bulan / 12);
    }

    public function getSisaUmurBulanAttribute()
    {
        $totalBulan = $this->umur_manfaat * 12;
        return max(0, $totalBulan - $this->usia_aset_bulan);
    }

    public function getSudahHabisUmurAttribute()
    {
        return $this->sisa_umur_bulan <= 0;
    }

    // Methods
    public function tandaiButuhPerbaikan()
    {
        $this->update(['butuh_perbaikan' => true]);
    }

    public function tandaiDiperbaiki()
    {
        $this->update(['butuh_perbaikan' => false]);
    }

    public function hapusAset(string $alasan)
    {
        $this->update([
            'status_aset' => 'dihapuskan',
            'tanggal_penghapusan' => Carbon::now(),
            'alasan_penghapusan' => $alasan,
        ]);
    }

    public function tambahMutasi(float $penambahan = 0, float $pengurangan = 0, float $koreksi = 0)
    {
        $this->update([
            'nilai_penambahan' => ($this->nilai_penambahan ?? 0) + $penambahan,
            'nilai_pengurangan' => ($this->nilai_pengurangan ?? 0) + $pengurangan,
            'nilai_koreksi' => ($this->nilai_koreksi ?? 0) + $koreksi,
        ]);
    }

    // Generate unique kode_aset
    public static function generateKodeAset($kategoriId = null)
    {
        $prefix = 'AST';
        if ($kategoriId) {
            $kategori = KategoriAset::find($kategoriId);
            if ($kategori) {
                $prefix = strtoupper(substr($kategori->nama_kategori, 0, 3));
            }
        }
        
        $year = date('Y');
        $lastAset = self::where('kode_aset', 'like', "{$prefix}-{$year}-%")
            ->orderBy('kode_aset', 'desc')
            ->first();
        
        if ($lastAset) {
            $lastNumber = (int) substr($lastAset->kode_aset, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return sprintf("%s-%s-%04d", $prefix, $year, $newNumber);
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($aset) {
            // Auto-set tahun_perolehan dari tanggal_masuk jika belum diisi
            if (empty($aset->tahun_perolehan) && $aset->tanggal_masuk) {
                $aset->tahun_perolehan = Carbon::parse($aset->tanggal_masuk)->year;
            }

            // Auto-generate kode_aset jika belum diisi
            if (empty($aset->kode_aset)) {
                $aset->kode_aset = self::generateKodeAset($aset->kategori_aset_id);
            }
        });
    }
}
