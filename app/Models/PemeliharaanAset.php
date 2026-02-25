<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PemeliharaanAset extends Model
{
    use HasFactory;

    protected $table = 'pemeliharaan_aset';

    protected $fillable = [
        'barang_aset_id',
        'ruangan_id',
        'tanggal_pemeliharaan',
        'jenis_pemeliharaan',
        'deskripsi_masalah',
        'tindakan_perbaikan',
        'status',
        'teknisi',
        'biaya',
        'lampiran_foto',
        'tanggal_selesai',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_pemeliharaan' => 'date',
        'tanggal_selesai' => 'date',
        'biaya' => 'decimal:2',
    ];

    // Jenis pemeliharaan options
    public static function getJenisPemeliharaanOptions()
    {
        return [
            'rutinitas' => 'Rutinitas',
            'perbaikan' => 'Perbaikan',
            'penggantian' => 'Penggantian',
        ];
    }

    // Status options
    public static function getStatusOptions()
    {
        return [
            'menunggu' => 'Menunggu',
            'proses' => 'Proses',
            'selesai' => 'Selesai',
            'rusak' => 'Rusak (Tidak Bisa Diperbaiki)',
        ];
    }

    public static function getStatusBadgeClass($status)
    {
        return match($status) {
            'menunggu' => 'bg-amber-500/20 text-amber-400',
            'proses' => 'bg-blue-500/20 text-blue-400',
            'selesai' => 'bg-emerald-500/20 text-emerald-400',
            'rusak' => 'bg-red-500/20 text-red-400',
            default => 'bg-slate-500/20 text-slate-400',
        };
    }

    public static function getJenisBadgeClass($jenis)
    {
        return match($jenis) {
            'rutinitas' => 'bg-cyan-500/20 text-cyan-400',
            'perbaikan' => 'bg-orange-500/20 text-orange-400',
            'penggantian' => 'bg-purple-500/20 text-purple-400',
            default => 'bg-slate-500/20 text-slate-400',
        };
    }

    // Relationships
    public function barangAset()
    {
        return $this->belongsTo(BarangAset::class);
    }

    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeJenis($query, $jenis)
    {
        return $query->where('jenis_pemeliharaan', $jenis);
    }

    public function scopeTeknisi($query, $teknisi)
    {
        return $query->where('teknisi', 'like', "%{$teknisi}%");
    }

    public function scopeRuangan($query, $ruanganId)
    {
        return $query->where('ruangan_id', $ruanganId);
    }

    public function scopePeriode($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal_pemeliharaan', [$startDate, $endDate]);
    }

    public function scopeMenunggu($query)
    {
        return $query->where('status', 'menunggu');
    }

    public function scopeProses($query)
    {
        return $query->where('status', 'proses');
    }

    // Accessors
    public function getStatusLabelAttribute()
    {
        return self::getStatusOptions()[$this->status] ?? $this->status;
    }

    public function getJenisLabelAttribute()
    {
        return self::getJenisPemeliharaanOptions()[$this->jenis_pemeliharaan] ?? $this->jenis_pemeliharaan;
    }

    public function getLampiranFotoUrlAttribute()
    {
        if ($this->lampiran_foto) {
            // Handle both old storage/ paths and new uploads/ paths
            if (str_starts_with($this->lampiran_foto, 'uploads/')) {
                return asset($this->lampiran_foto);
            }
            return asset('storage/' . $this->lampiran_foto);
        }
        return null;
    }

    // Methods
    public function selesaikan($tindakan = null, $tanggalSelesai = null)
    {
        $this->update([
            'status' => 'selesai',
            'tindakan_perbaikan' => $tindakan ?? $this->tindakan_perbaikan,
            'tanggal_selesai' => $tanggalSelesai ?? now(),
        ]);
        
        // Update kondisi barang jika perbaikan selesai
        if ($this->barangAset) {
            $this->barangAset->update([
                'kondisi' => 'baik',
                'butuh_perbaikan' => false,
            ]);
        }
    }

    public function tandaiRusak()
    {
        $this->update(['status' => 'rusak']);
        
        // Update kondisi barang menjadi rusak berat
        if ($this->barangAset) {
            $this->barangAset->update([
                'kondisi' => 'rusak_berat',
                'butuh_perbaikan' => true,
            ]);
        }
    }

    public function mulaiProses()
    {
        $this->update(['status' => 'proses']);
    }
}
