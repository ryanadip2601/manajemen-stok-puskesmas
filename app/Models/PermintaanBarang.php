<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermintaanBarang extends Model
{
    use HasFactory;

    protected $table = 'permintaan_barang';

    protected $fillable = [
        'kode_permintaan',
        'user_id',
        'tipe',
        'item_id',
        'nama_barang',
        'ruangan_id',
        'jumlah',
        'prioritas',
        'keterangan',
        'tanggal_permintaan',
        'status',
        'catatan_admin',
        'alasan_penolakan',
        'permintaan_revisi',
        'verified_by',
        'verified_at',
        'notified_at',
        'created_item_id',
        'foto_barang',
        'tipe_foto_barang',
    ];

    protected $casts = [
        'tanggal_permintaan' => 'date',
        'verified_at' => 'datetime',
        'notified_at' => 'datetime',
        'jumlah' => 'integer',
    ];

    // Tipe constants
    const TIPE_BARANG_BARU = 'barang_baru';
    const TIPE_RESTOK = 'restok';

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_DIPROSES = 'diproses';
    const STATUS_DISETUJUI = 'disetujui';
    const STATUS_DITOLAK = 'ditolak';
    const STATUS_REVISI = 'revisi';
    const STATUS_TERSEDIA = 'tersedia';
    const STATUS_DIBACA = 'dibaca';

    public static function getTipeOptions()
    {
        return [
            self::TIPE_BARANG_BARU => 'Barang Baru',
            self::TIPE_RESTOK => 'Restok',
        ];
    }

    public static function getStatusOptions()
    {
        return [
            self::STATUS_PENDING => 'Menunggu Verifikasi',
            self::STATUS_DIPROSES => 'Sedang Diproses',
            self::STATUS_DISETUJUI => 'Disetujui',
            self::STATUS_DITOLAK => 'Ditolak',
            self::STATUS_REVISI => 'Perlu Revisi',
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
            self::STATUS_DIPROSES => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
            self::STATUS_DISETUJUI => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
            self::STATUS_DITOLAK => 'bg-red-500/20 text-red-400 border-red-500/30',
            self::STATUS_REVISI => 'bg-purple-500/20 text-purple-400 border-purple-500/30',
            self::STATUS_TERSEDIA => 'bg-cyan-500/20 text-cyan-400 border-cyan-500/30',
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

    public static function getTipeBadgeClass($tipe)
    {
        return match($tipe) {
            self::TIPE_BARANG_BARU => 'bg-indigo-500/20 text-indigo-400 border-indigo-500/30',
            self::TIPE_RESTOK => 'bg-orange-500/20 text-orange-400 border-orange-500/30',
            default => 'bg-slate-500/20 text-slate-400 border-slate-500/30',
        };
    }

    // ==================== RELATIONSHIPS ====================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function createdItem()
    {
        return $this->belongsTo(Item::class, 'created_item_id');
    }

    // ==================== SCOPES ====================

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

    public function scopeTersedia($query)
    {
        return $query->where('status', self::STATUS_TERSEDIA);
    }

    public function scopeDibaca($query)
    {
        return $query->where('status', self::STATUS_DIBACA);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByRuangan($query, $ruanganId)
    {
        return $query->where('ruangan_id', $ruanganId);
    }

    public function scopeByItem($query, $itemId)
    {
        return $query->where('item_id', $itemId);
    }

    public function scopeTipeBarangBaru($query)
    {
        return $query->where('tipe', self::TIPE_BARANG_BARU);
    }

    public function scopeTipeRestok($query)
    {
        return $query->where('tipe', self::TIPE_RESTOK);
    }

    public function scopePrioritas($query, $prioritas)
    {
        return $query->where('prioritas', $prioritas);
    }

    public function scopeBelumDibaca($query)
    {
        return $query->where('status', self::STATUS_TERSEDIA)
            ->whereNull('notified_at');
    }

    // ==================== ACCESSORS ====================

    public function getTipeLabelAttribute()
    {
        return self::getTipeOptions()[$this->tipe] ?? $this->tipe;
    }

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

    public function getIsTersediaAttribute()
    {
        return $this->status === self::STATUS_TERSEDIA;
    }

    public function getIsDibacaAttribute()
    {
        return $this->status === self::STATUS_DIBACA;
    }

    public function getIsBarangBaruAttribute()
    {
        return $this->tipe === self::TIPE_BARANG_BARU;
    }

    public function getIsRestokAttribute()
    {
        return $this->tipe === self::TIPE_RESTOK;
    }

    public function getCanEditAttribute()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_REVISI]);
    }

    public function getCanCancelAttribute()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_REVISI]);
    }

    public function getFotoUrl()
    {
        if (!$this->foto_barang) {
            return null;
        }

        if ($this->tipe_foto_barang === 'url') {
            return $this->foto_barang;
        }

        return asset('storage/' . $this->foto_barang);
    }

    // ==================== METHODS ====================

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

    public function isItemAvailable()
    {
        return $this->item && $this->item->stock > 0;
    }

    public function buatDataBarang($data)
    {
        if ($this->status !== self::STATUS_DISETUJUI || $this->tipe !== self::TIPE_BARANG_BARU) {
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
            'description' => 'Dari permintaan barang #' . $this->kode_permintaan . '. ' . ($this->keterangan ?? ''),
        ]);

        $this->update(['created_item_id' => $item->id]);

        return $item;
    }

    // Auto-check: dipanggil setelah StockIn untuk menandai permintaan restok yang terpenuhi
    public static function checkAndUpdateAvailability($itemId)
    {
        $item = Item::find($itemId);
        if (!$item || $item->stock <= 0) {
            return 0;
        }

        $updated = self::where('item_id', $itemId)
            ->where('tipe', self::TIPE_RESTOK)
            ->where('status', self::STATUS_DISETUJUI)
            ->get();

        $count = 0;
        foreach ($updated as $permintaan) {
            $permintaan->tandaiTersedia();
            $count++;
        }

        return $count;
    }

    // Get semua notifikasi popup untuk user tertentu (restok yang tersedia)
    public static function getPopupNotifications($userId)
    {
        return self::with('item')
            ->where('user_id', $userId)
            ->where('tipe', self::TIPE_RESTOK)
            ->where('status', self::STATUS_TERSEDIA)
            ->whereNull('notified_at')
            ->latest()
            ->get();
    }

    // Generate unique kode_permintaan
    public static function generateKodePermintaan()
    {
        $prefix = 'PBR';
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
