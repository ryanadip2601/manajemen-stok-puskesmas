<?php

namespace App\Services;

use App\Models\Item;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\Log;
use Exception;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function addStockIn(array $data)
    {
        return DB::transaction(function () use ($data) {
            $item = Item::findOrFail($data['item_id']);
            
            $stockIn = StockIn::create([
                'item_id' => $data['item_id'],
                'user_id' => auth()->id(),
                'quantity' => $data['quantity'],
                'date' => $data['date'],
                'notes' => $data['notes'] ?? null,
            ]);

            return $stockIn;
        });
    }

    public function addStockOut(array $data)
    {
        return DB::transaction(function () use ($data) {
            $item = Item::findOrFail($data['item_id']);
            
            if ($item->stock < $data['quantity']) {
                throw new Exception("Stok tidak mencukupi. Stok tersedia: {$item->stock} {$item->unit->symbol}");
            }

            $stockOut = StockOut::create([
                'item_id' => $data['item_id'],
                'user_id' => auth()->id(),
                'quantity' => $data['quantity'],
                'date' => $data['date'],
                'notes' => $data['notes'] ?? null,
            ]);

            return $stockOut;
        });
    }

    public function updateStockIn(StockIn $stockIn, array $data)
    {
        return DB::transaction(function () use ($stockIn, $data) {
            $stockIn->update($data);
            return $stockIn;
        });
    }

    public function updateStockOut(StockOut $stockOut, array $data)
    {
        return DB::transaction(function () use ($stockOut, $data) {
            $item = $stockOut->item;
            $originalQuantity = $stockOut->quantity;
            $newQuantity = $data['quantity'];
            $difference = $newQuantity - $originalQuantity;

            if ($difference > 0 && $item->stock < $difference) {
                throw new Exception("Stok tidak mencukupi. Stok tersedia: {$item->stock} {$item->unit->symbol}");
            }

            $stockOut->update($data);
            return $stockOut;
        });
    }

    public function deleteStockIn(StockIn $stockIn)
    {
        return DB::transaction(function () use ($stockIn) {
            $stockIn->delete();
        });
    }

    public function deleteStockOut(StockOut $stockOut)
    {
        return DB::transaction(function () use ($stockOut) {
            $stockOut->delete();
        });
    }

    public function getCurrentStock(int $itemId)
    {
        $item = Item::findOrFail($itemId);
        return $item->stock;
    }

    public function getLowStockItems()
    {
        return Item::with(['category', 'unit'])
            ->lowStock()
            ->orderBy('stock', 'asc')
            ->get();
    }

    public function getStockHistory(int $itemId)
    {
        $item = Item::with(['stockIns.user', 'stockOuts.user'])->findOrFail($itemId);
        
        $history = collect();
        
        foreach ($item->stockIns as $stockIn) {
            $history->push([
                'type' => 'in',
                'date' => $stockIn->date,
                'quantity' => $stockIn->quantity,
                'user' => $stockIn->user->name,
                'notes' => $stockIn->notes,
                'created_at' => $stockIn->created_at,
            ]);
        }
        
        foreach ($item->stockOuts as $stockOut) {
            $history->push([
                'type' => 'out',
                'date' => $stockOut->date,
                'quantity' => $stockOut->quantity,
                'user' => $stockOut->user->name,
                'notes' => $stockOut->notes,
                'created_at' => $stockOut->created_at,
            ]);
        }
        
        return $history->sortByDesc('date')->values();
    }

    public function getDashboardStats()
    {
        return [
            'total_items' => Item::count(),
            'total_stock' => Item::sum('stock'),
            'low_stock_count' => Item::lowStock()->count(),
            'categories_count' => Item::distinct('category_id')->count(),
            'recent_stock_in' => StockIn::with(['item.unit', 'user'])
                ->latest()
                ->take(5)
                ->get(),
            'recent_stock_out' => StockOut::with(['item.unit', 'user'])
                ->latest()
                ->take(5)
                ->get(),
            'low_stock_items' => Item::with(['category', 'unit'])
                ->lowStock()
                ->take(10)
                ->get(),
        ];
    }
}
