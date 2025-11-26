<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;

class StockOut extends Model
{
    use HasFactory;

    protected $table = 'stock_out';

    protected $fillable = [
        'item_id',
        'user_id',
        'quantity',
        'date',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'date' => 'date',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::creating(function ($stockOut) {
            $item = $stockOut->item;
            if ($item->stock < $stockOut->quantity) {
                throw new Exception("Stok tidak mencukupi. Stok tersedia: {$item->stock}");
            }
        });

        static::created(function ($stockOut) {
            $stockOut->item()->decrement('stock', $stockOut->quantity);
            
            Log::create([
                'user_id' => $stockOut->user_id,
                'action' => 'stock_out_created',
                'description' => "Barang keluar: {$stockOut->item->name} sebanyak {$stockOut->quantity} {$stockOut->item->unit->symbol}"
            ]);
        });

        static::updating(function ($stockOut) {
            $originalQuantity = $stockOut->getOriginal('quantity');
            $difference = $stockOut->quantity - $originalQuantity;
            
            if ($difference > 0) {
                $item = $stockOut->item;
                if ($item->stock < $difference) {
                    throw new Exception("Stok tidak mencukupi untuk update. Stok tersedia: {$item->stock}");
                }
            }
        });

        static::updated(function ($stockOut) {
            $originalQuantity = $stockOut->getOriginal('quantity');
            $difference = $stockOut->quantity - $originalQuantity;
            
            if ($difference != 0) {
                $stockOut->item()->decrement('stock', $difference);
                
                Log::create([
                    'user_id' => $stockOut->user_id,
                    'action' => 'stock_out_updated',
                    'description' => "Update barang keluar: {$stockOut->item->name} dari {$originalQuantity} menjadi {$stockOut->quantity}"
                ]);
            }
        });

        static::deleted(function ($stockOut) {
            $stockOut->item()->increment('stock', $stockOut->quantity);
            
            Log::create([
                'user_id' => auth()->id(),
                'action' => 'stock_out_deleted',
                'description' => "Hapus barang keluar: {$stockOut->item->name} sebanyak {$stockOut->quantity}"
            ]);
        });
    }
}
