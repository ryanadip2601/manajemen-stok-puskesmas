<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermintaanRestok extends Model
{
    use HasFactory;

    protected $table = 'permintaan_restok';

    protected $fillable = [
        'kode_restok',
        'user_id',
        'item_id',
        'jumlah_diminta',
        'prioritas',
        'keterangan',
        'tanggal_permintaan',
        'status',
        'catatan_admin',
        'alasan_penolakan',
        'verified_by',
        'verified_at',
        'notified_at',
    ];

    protected $casts = [
        'tanggal_permintaan' => 'date',
        'verified_at' => 'datetime',
        'notified_at' => 'datetime',
        'jumlah_diminta' => 'integer',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_DISETUJUI = 'disetujui';
    const STATUS_DITOLAK = 'ditolak';
    const STATUS_TERSEDIA = 'tersedia';
    const STATUS_DIBACA = 'dibaca';

    public static function getStatusOptions()
    {
        return [
            self::STATUS_PENDING => 'Menunggu Persetujuan',
            self::STATUS_DISETUJUI => 'Disetujui (Menunggu Stok)',
            self::STATUS_DITOLAK => 'Ditolak',
            self::STATUS_TERSEDIA => 'Barang Tersedia',
            self::STATUS_DIBACA => 'Sudah Dibaca',
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
            self::STATUS_DISETUJUI => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
            self::STATUS_DITOLAK => 'bg-red-500/20 text-red-400 border-red-500/30',
            self::STATUS_TERSEDIA => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
            self::STATUS_DIBACA => 'bg-slate-500/20 text-slate-400 border-slate-500/30',
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

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
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

    public function scopeDisetujui($query)
    {
        return $query->where('status', self::STATUS_DISETUJUI);
    }

    public function scopeDitolak($query)
    {
        return $query->where('status', self::STATUS_DITOLAK);
    }

    public function scopeTersedia($query)
    {
        return $query->where('status', self::STATUS_TERSEDIA);
    }

    public function scopeDibaca($query)
    {
        return $query->where('status', self::STATUS_DIBACA);
    }

    public function scopeMenungguVerifikasi($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeBelumDibaca($query)
    {
        return $query->where('status', self::STATUS_TERSEDIA)
            ->whereNull('notified_at');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByItem($query, $itemId)
    {
        return $query->where('item_id', $itemId);
    }

    public function scopePrioritas($query, $prioritas)
    {
        return $query->where('prioritas', $prioritas);
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

    public function getIsDisetujuiAttribute()
    {
        return $this->status === self::STATUS_DISETUJUI;
    }

    public function getIsDitolakAttribute()
    {
        return $this->status === self::STATUS_DITOLAK;
    }

    public function getIsTersediaAttribute()
    {
        return $this->status === self::STATUS_TERSEDIA;
    }

    public function getIsDibacaAttribute()
    {
        return $this->status === self::STATUS_DIBACA;
    }

    public function getCanCancelAttribute()
    {
        return $this->status === self::STATUS_PENDING;
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

    public function tandaiTersedia()
    {
        $this->update([
            'status' => self::STATUS_TERSEDIA,
            'notified_at' => null,
        ]);
    }

    public function tandaiDibaca()
    {
        $this->update([
            'status' => self::STATUS_DIBACA,
            'notified_at' => now(),
        ]);
    }

    // Check apakah item terkait sudah memiliki stok
    public function isItemAvailable()
    {
        return $this->item && $this->item->stock > 0;
    }

    // Generate unique kode_restok
    public static function generateKodeRestok()
    {
        $prefix = 'RST';
        $year = date('Y');
        $month = date('m');

        $last = self::where('kode_restok', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('kode_restok', 'desc')
            ->first();

        if ($last) {
            $lastNumber = (int) substr($last->kode_restok, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf("%s-%s%s-%04d", $prefix, $year, $month, $newNumber);
    }

    // Auto-check: dipanggil setelah StockIn untuk menandai permintaan yang terpenuhi
    public static function checkAndUpdateAvailability($itemId)
    {
        $item = Item::find($itemId);
        if (!$item || $item->stock <= 0) {
            return 0;
        }

        $updated = self::where('item_id', $itemId)
            ->where('status', self::STATUS_DISETUJUI)
            ->get();

        $count = 0;
        foreach ($updated as $permintaan) {
            $permintaan->tandaiTersedia();
            $count++;
        }

        return $count;
    }

    // Get semua notifikasi popup untuk user tertentu
    public static function getPopupNotifications($userId)
    {
        return self::with('item')
            ->where('user_id', $userId)
            ->where('status', self::STATUS_TERSEDIA)
            ->whereNull('notified_at')
            ->latest()
            ->get();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->kode_restok)) {
                $model->kode_restok = self::generateKodeRestok();
            }
            if (empty($model->tanggal_permintaan)) {
                $model->tanggal_permintaan = now();
            }
        });
    }
}
