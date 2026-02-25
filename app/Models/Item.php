<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'unit_id',
        'code',
        'barcode',
        'name',
        'description',
        'perolehan',
        'price',
        'photo',
        'stock',
        'minimum_stock',
    ];

    protected $casts = [
        'stock' => 'integer',
        'minimum_stock' => 'integer',
        'price' => 'decimal:2',
    ];

    // Daftar pilihan perolehan
    public static function getPerolehanOptions()
    {
        return [
            'APBD Balikpapan' => 'APBD Balikpapan',
            'BLUD' => 'BLUD',
            'Dropping IFK/DKK - APBD Balikpapan' => 'Dropping IFK/DKK - APBD Balikpapan',
            'Dropping IFK/DKK - APBN' => 'Dropping IFK/DKK - APBN',
            'HIBAH OPD LAIN BALIKPAPAN' => 'HIBAH OPD LAIN BALIKPAPAN',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function stockIns()
    {
        return $this->hasMany(StockIn::class);
    }

    public function stockOuts()
    {
        return $this->hasMany(StockOut::class);
    }

    public function isLowStock()
    {
        return $this->stock <= $this->minimum_stock;
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock', '<=', 'minimum_stock');
    }

    public function getPhotoUrl()
    {
        if ($this->photo) {
            // Jika photo adalah URL lengkap (dari pencarian Google)
            if (str_starts_with($this->photo, 'http://') || str_starts_with($this->photo, 'https://')) {
                return $this->photo;
            }
            return asset('storage/' . $this->photo);
        }
        return null;
    }
}
