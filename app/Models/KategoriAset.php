<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriAset extends Model
{
    use HasFactory;

    protected $table = 'kategori_aset';

    protected $fillable = [
        'nama_kategori',
        'kode_golongan',
        'golongan',
        'umur_manfaat',
        'dapat_disusutkan',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'umur_manfaat' => 'integer',
        'dapat_disusutkan' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Golongan aset sesuai standar akuntansi pemerintah
    const GOLONGAN_OPTIONS = [
        'tanah' => 'Tanah',
        'peralatan_mesin' => 'Peralatan dan Mesin',
        'gedung_bangunan' => 'Gedung dan Bangunan',
        'jalan_irigasi' => 'Jalan, Irigasi dan Jaringan',
        'aset_tetap_lainnya' => 'Aset Tetap Lainnya',
        'kdp' => 'Konstruksi Dalam Pengerjaan',
    ];

    // Umur manfaat default per golongan (dalam tahun)
    const UMUR_MANFAAT_DEFAULT = [
        'tanah' => 0,              // Tidak disusutkan
        'peralatan_mesin' => 5,    // 5 tahun
        'gedung_bangunan' => 50,   // 50 tahun
        'jalan_irigasi' => 10,     // 10 tahun
        'aset_tetap_lainnya' => 4, // 4 tahun
        'kdp' => 0,                // Tidak disusutkan
    ];

    // Golongan yang tidak disusutkan
    const GOLONGAN_TIDAK_DISUSUTKAN = ['tanah', 'kdp'];

    // Relationships
    public function barangAset()
    {
        return $this->hasMany(BarangAset::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDapatDisusutkan($query)
    {
        return $query->where('dapat_disusutkan', true)
                     ->whereNotIn('golongan', self::GOLONGAN_TIDAK_DISUSUTKAN);
    }

    public function scopeGolongan($query, $golongan)
    {
        return $query->where('golongan', $golongan);
    }

    // Accessors
    public function getTotalAsetAttribute()
    {
        return $this->barangAset()->count();
    }

    public function getTotalNilaiAttribute()
    {
        return $this->barangAset()->sum('harga_perolehan');
    }

    public function getGolonganLabelAttribute()
    {
        return self::GOLONGAN_OPTIONS[$this->golongan] ?? $this->golongan;
    }

    public function getIsTidakDisusutkanAttribute()
    {
        return in_array($this->golongan, self::GOLONGAN_TIDAK_DISUSUTKAN) || !$this->dapat_disusutkan;
    }

    // Static methods
    public static function getGolonganOptions()
    {
        return self::GOLONGAN_OPTIONS;
    }

    public static function getUmurManfaatDefault($golongan)
    {
        return self::UMUR_MANFAAT_DEFAULT[$golongan] ?? 5;
    }

    // Boot method untuk auto-set umur manfaat berdasarkan golongan
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($kategori) {
            // Set umur manfaat default jika belum diisi
            if (empty($kategori->umur_manfaat)) {
                $kategori->umur_manfaat = self::getUmurManfaatDefault($kategori->golongan);
            }

            // Set dapat_disusutkan berdasarkan golongan
            if (in_array($kategori->golongan, self::GOLONGAN_TIDAK_DISUSUTKAN)) {
                $kategori->dapat_disusutkan = false;
            }
        });

        static::updating(function ($kategori) {
            // Update dapat_disusutkan jika golongan berubah
            if ($kategori->isDirty('golongan')) {
                if (in_array($kategori->golongan, self::GOLONGAN_TIDAK_DISUSUTKAN)) {
                    $kategori->dapat_disusutkan = false;
                }
            }
        });
    }
}
