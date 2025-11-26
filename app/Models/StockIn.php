<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockIn extends Model
{
    use HasFactory;

    protected $table = 'stock_in';

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
        static::created(function ($stockIn) {
            $stockIn->item()->increment('stock', $stockIn->quantity);
            
            Log::create([
                'user_id' => $stockIn->user_id,
                'action' => 'stock_in_created',
                'description' => "Barang masuk: {$stockIn->item->name} sebanyak {$stockIn->quantity} {$stockIn->item->unit->symbol}"
            ]);
        });

        static::updated(function ($stockIn) {
            $originalQuantity = $stockIn->getOriginal('quantity');
            $difference = $stockIn->quantity - $originalQuantity;
            
            if ($difference != 0) {
                $stockIn->item()->increment('stock', $difference);
                
                Log::create([
                    'user_id' => $stockIn->user_id,
                    'action' => 'stock_in_updated',
                    'description' => "Update barang masuk: {$stockIn->item->name} dari {$originalQuantity} menjadi {$stockIn->quantity}"
                ]);
            }
        });

        static::deleted(function ($stockIn) {
            $stockIn->item()->decrement('stock', $stockIn->quantity);
            
            Log::create([
                'user_id' => auth()->id(),
                'action' => 'stock_in_deleted',
                'description' => "Hapus barang masuk: {$stockIn->item->name} sebanyak {$stockIn->quantity}"
            ]);
        });
    }
}
