<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ruangan extends Model
{
    use HasFactory;

    protected $table = 'ruangan';

    protected $fillable = [
        'nama_ruangan',
        'lokasi',
        'keterangan',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function barangAset()
    {
        return $this->hasMany(BarangAset::class);
    }

    public function pemeliharaan()
    {
        return $this->hasMany(PemeliharaanAset::class);
    }

    public function usulanPenerimaan()
    {
        return $this->hasMany(UsulanPenerimaanBarang::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accessors
    public function getTotalAsetAttribute()
    {
        return $this->barangAset()->sum('jumlah');
    }

    public function getAsetRusakAttribute()
    {
        return $this->barangAset()->whereIn('kondisi', ['rusak_ringan', 'rusak_berat'])->count();
    }
}
