<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsulanPermintaanBarang extends Model
{
    use HasFactory;

    protected $table = 'usulan_permintaan_barang';

    protected $fillable = [
        'kode_permintaan',
        'user_id',
        'ruangan_id',
        'nama_barang',
        'jumlah',
        'prioritas',
        'keterangan_user',
        'tanggal_permintaan',
        'status',
        'catatan_admin',
        'alasan_penolakan',
        'permintaan_revisi',
        'verified_by',
        'verified_at',
        'item_id',
        'foto_barang',
        'tipe_foto_barang',
    ];

    protected $casts = [
        'tanggal_permintaan' => 'date',
        'verified_at' => 'datetime',
        'jumlah' => 'integer',
    ];

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

    public static function getPrioritasOptions()
    {
        return [
            'rendah' => 'Rendah',
            'sedang' => 'Sedang',
            'tinggi' => 'Tinggi',
        ];
    }

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

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
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
            return $query->whereBetween('tanggal_permintaan', [$from, $to]);
        }
        return $query->whereDate('tanggal_permintaan', $from);
    }

    // Accessors
    public function getStatusLabelAttribute()
    {
        return self::getStatusOptions()[$this->status] ?? $this->status;
    }

    public function getPrioritasLabelAttribute()
    {
        return self::getPrioritasOptions()[$this->prioritas] ?? $this->prioritas;
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

    public function buatDataBarang($data)
    {
        if ($this->status !== self::STATUS_DISETUJUI) {
            return null;
        }

        $item = Item::create([
            'code' => $data['code'],
            'name' => $this->nama_barang,
            'category_id' => $data['category_id'],
            'unit_id' => $data['unit_id'],
            'stock' => $this->jumlah,
            'minimum_stock' => $data['minimum_stock'] ?? 5,
            'price' => $data['price'] ?? 0,
            'perolehan' => $data['perolehan'] ?? 'BLUD',
            'description' => 'Dari usulan permintaan #' . $this->kode_permintaan . '. ' . ($this->keterangan_user ?? ''),
        ]);

        $this->update(['item_id' => $item->id]);

        return $item;
    }

    // Generate unique kode_permintaan
    public static function generateKodePermintaan()
    {
        $prefix = 'PMT';
        $year = date('Y');
        $month = date('m');

        $last = self::where('kode_permintaan', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('kode_permintaan', 'desc')
            ->first();

        if ($last) {
            $lastNumber = (int) substr($last->kode_permintaan, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf("%s-%s%s-%04d", $prefix, $year, $month, $newNumber);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->kode_permintaan)) {
                $model->kode_permintaan = self::generateKodePermintaan();
            }
            if (empty($model->tanggal_permintaan)) {
                $model->tanggal_permintaan = now();
            }
        });
    }
}
