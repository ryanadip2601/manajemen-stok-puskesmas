<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UsulanPenerimaanBarang extends Model
{
    use HasFactory;

    protected $table = 'usulan_penerimaan_barang';

    protected $fillable = [
        'kode_usulan',
        'user_id',
        'ruangan_id',
        'kategori_aset_id',
        'nama_barang',
        'merk',
        'spesifikasi',
        'jumlah',
        'kondisi_barang',
        'kelengkapan',
        'estimasi_harga',
        'sumber_barang',
        'foto_barang',
        'tanggal_usulan',
        'tanggal_penerimaan_fisik',
        'status',
        'prioritas',
        'keterangan_user',
        'catatan_admin',
        'alasan_penolakan',
        'permintaan_revisi',
        'verified_by',
        'verified_at',
        'barang_aset_id',
    ];

    protected $casts = [
        'foto_barang' => 'array',
        'tanggal_usulan' => 'date',
        'tanggal_penerimaan_fisik' => 'date',
        'verified_at' => 'datetime',
        'jumlah' => 'integer',
        'estimasi_harga' => 'decimal:2',
    ];

    // Status options
    const STATUS_PENDING = 'pending';
    const STATUS_DIPROSES = 'diproses';
    const STATUS_DISETUJUI = 'disetujui';
    const STATUS_DITOLAK = 'ditolak';
    const STATUS_REVISI = 'revisi';

    public static function getStatusOptions()
    {
        return [
            self::STATUS_PENDING => 'Menunggu Verifikasi',
            self::STATUS_DIPROSES => 'Sedang Diproses',
            self::STATUS_DISETUJUI => 'Disetujui',
            self::STATUS_DITOLAK => 'Ditolak',
            self::STATUS_REVISI => 'Perlu Revisi',
        ];
    }

    public static function getKondisiOptions()
    {
        return [
            'baru' => 'Baru',
            'bekas_baik' => 'Bekas - Kondisi Baik',
            'bekas_rusak_ringan' => 'Bekas - Rusak Ringan',
        ];
    }

    public static function getKelengkapanOptions()
    {
        return [
            'lengkap' => 'Lengkap',
            'tidak_lengkap' => 'Tidak Lengkap',
        ];
    }

    public static function getPrioritasOptions()
    {
        return [
            'rendah' => 'Rendah',
            'sedang' => 'Sedang',
            'tinggi' => 'Tinggi',
        ];
    }

    public static function getSumberBarangOptions()
    {
        return [
            'Pembelian' => 'Pembelian',
            'Hibah' => 'Hibah',
            'Transfer' => 'Transfer dari Unit Lain',
            'Donasi' => 'Donasi',
            'Lainnya' => 'Lainnya',
        ];
    }

    // Badge classes
    public static function getStatusBadgeClass($status)
    {
        return match($status) {
            self::STATUS_PENDING => 'bg-amber-500/20 text-amber-400 border-amber-500/30',
            self::STATUS_DIPROSES => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
            self::STATUS_DISETUJUI => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
            self::STATUS_DITOLAK => 'bg-red-500/20 text-red-400 border-red-500/30',
            self::STATUS_REVISI => 'bg-purple-500/20 text-purple-400 border-purple-500/30',
            default => 'bg-slate-500/20 text-slate-400 border-slate-500/30',
        };
    }

    public static function getPrioritasBadgeClass($prioritas)
    {
        return match($prioritas) {
            'tinggi' => 'bg-red-500/20 text-red-400',
            'sedang' => 'bg-amber-500/20 text-amber-400',
            'rendah' => 'bg-green-500/20 text-green-400',
            default => 'bg-slate-500/20 text-slate-400',
        };
    }

    public static function getKondisiBadgeClass($kondisi)
    {
        return match($kondisi) {
            'baru' => 'bg-emerald-500/20 text-emerald-400',
            'bekas_baik' => 'bg-blue-500/20 text-blue-400',
            'bekas_rusak_ringan' => 'bg-amber-500/20 text-amber-400',
            default => 'bg-slate-500/20 text-slate-400',
        };
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class);
    }

    public function kategoriAset()
    {
        return $this->belongsTo(KategoriAset::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function barangAset()
    {
        return $this->belongsTo(BarangAset::class);
    }

    // Scopes
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeMenungguVerifikasi($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_DIPROSES]);
    }

    public function scopeDisetujui($query)
    {
        return $query->where('status', self::STATUS_DISETUJUI);
    }

    public function scopeDitolak($query)
    {
        return $query->where('status', self::STATUS_DITOLAK);
    }

    public function scopeRevisi($query)
    {
        return $query->where('status', self::STATUS_REVISI);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByRuangan($query, $ruanganId)
    {
        return $query->where('ruangan_id', $ruanganId);
    }

    public function scopePrioritas($query, $prioritas)
    {
        return $query->where('prioritas', $prioritas);
    }

    public function scopeTanggal($query, $from, $to = null)
    {
        if ($to) {
            return $query->whereBetween('tanggal_usulan', [$from, $to]);
        }
        return $query->whereDate('tanggal_usulan', $from);
    }

    // Accessors
    public function getStatusLabelAttribute()
    {
        return self::getStatusOptions()[$this->status] ?? $this->status;
    }

    public function getKondisiLabelAttribute()
    {
        return self::getKondisiOptions()[$this->kondisi_barang] ?? $this->kondisi_barang;
    }

    public function getKelengkapanLabelAttribute()
    {
        return self::getKelengkapanOptions()[$this->kelengkapan] ?? $this->kelengkapan;
    }

    public function getPrioritasLabelAttribute()
    {
        return self::getPrioritasOptions()[$this->prioritas] ?? $this->prioritas;
    }

    public function getFotoUrlsAttribute()
    {
        if (!$this->foto_barang || !is_array($this->foto_barang)) {
            return [];
        }
        return array_map(function($foto) {
            // Handle URL langsung
            if (str_starts_with($foto, 'http://') || str_starts_with($foto, 'https://')) {
                return $foto;
            }
            // Handle both old storage/ paths and new uploads/ paths
            if (str_starts_with($foto, 'uploads/')) {
                return asset($foto);
            }
            return asset('storage/' . $foto);
        }, $this->foto_barang);
    }

    public function getIsPendingAttribute()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getIsDiprosesAttribute()
    {
        return $this->status === self::STATUS_DIPROSES;
    }

    public function getIsDisetujuiAttribute()
    {
        return $this->status === self::STATUS_DISETUJUI;
    }

    public function getIsDitolakAttribute()
    {
        return $this->status === self::STATUS_DITOLAK;
    }

    public function getIsRevisiAttribute()
    {
        return $this->status === self::STATUS_REVISI;
    }

    public function getCanEditAttribute()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_REVISI]);
    }

    public function getCanCancelAttribute()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_REVISI]);
    }

    // Methods
    public function setujui($adminId, $catatan = null)
    {
        $this->update([
            'status' => self::STATUS_DISETUJUI,
            'catatan_admin' => $catatan,
            'verified_by' => $adminId,
            'verified_at' => now(),
        ]);
    }

    public function tolak($adminId, $alasan)
    {
        $this->update([
            'status' => self::STATUS_DITOLAK,
            'alasan_penolakan' => $alasan,
            'verified_by' => $adminId,
            'verified_at' => now(),
        ]);
    }

    public function mintaRevisi($adminId, $permintaan)
    {
        $this->update([
            'status' => self::STATUS_REVISI,
            'permintaan_revisi' => $permintaan,
            'verified_by' => $adminId,
            'verified_at' => now(),
        ]);
    }

    public function proses($adminId)
    {
        $this->update([
            'status' => self::STATUS_DIPROSES,
            'verified_by' => $adminId,
        ]);
    }

    public function konversiKeKondisiAset()
    {
        return match($this->kondisi_barang) {
            'baru' => 'baik',
            'bekas_baik' => 'baik',
            'bekas_rusak_ringan' => 'rusak_ringan',
            default => 'baik',
        };
    }

    public function buatBarangAset($kategoriId = null)
    {
        if ($this->status !== self::STATUS_DISETUJUI) {
            return null;
        }

        $barangAset = BarangAset::create([
            'kode_aset' => BarangAset::generateKodeAset($kategoriId ?? $this->kategori_aset_id),
            'nama_aset' => $this->nama_barang,
            'kategori_aset_id' => $kategoriId ?? $this->kategori_aset_id,
            'ruangan_id' => $this->ruangan_id,
            'kondisi' => $this->konversiKeKondisiAset(),
            'tanggal_masuk' => $this->tanggal_penerimaan_fisik ?? $this->tanggal_usulan,
            'tahun_perolehan' => ($this->tanggal_penerimaan_fisik ?? $this->tanggal_usulan)->year,
            'sumber_dana' => $this->sumber_barang === 'Hibah' ? 'Hibah' : 'Lainnya',
            'jumlah' => $this->jumlah,
            'harga_perolehan' => $this->estimasi_harga,
            'merk' => $this->merk,
            'spesifikasi' => $this->spesifikasi,
            'foto' => $this->foto_barang[0] ?? null,
            'keterangan' => "Dari usulan penerimaan #{$this->kode_usulan}. " . ($this->keterangan_user ?? ''),
            'status_aset' => 'aktif',
            'created_by' => $this->verified_by,
        ]);

        $this->update(['barang_aset_id' => $barangAset->id]);

        return $barangAset;
    }

    // Generate unique kode_usulan
    public static function generateKodeUsulan()
    {
        $prefix = 'USL';
        $year = date('Y');
        $month = date('m');
        
        $lastUsulan = self::where('kode_usulan', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('kode_usulan', 'desc')
            ->first();
        
        if ($lastUsulan) {
            $lastNumber = (int) substr($lastUsulan->kode_usulan, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return sprintf("%s-%s%s-%04d", $prefix, $year, $month, $newNumber);
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($usulan) {
            if (empty($usulan->kode_usulan)) {
                $usulan->kode_usulan = self::generateKodeUsulan();
            }
            if (empty($usulan->tanggal_usulan)) {
                $usulan->tanggal_usulan = now();
            }
        });
    }
}
