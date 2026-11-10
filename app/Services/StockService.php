<?php

namespace App\Services;

use App\Models\Item;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\StockOutRequest;
use App\Models\Log;
use Carbon\Carbon;
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

    public function getDashboardStats(int $rangeDays = 30)
    {
        $rangeDays = in_array($rangeDays, [30, 90, 180], true) ? $rangeDays : 30;

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
            'range_days' => $rangeDays,
            'chart_data' => $this->getChartData(),
            'insight_data' => $this->getInsightData($rangeDays),
        ];
    }

    public function getInsightData(int $rangeDays = 30)
    {
        $startDate = now()->subDays($rangeDays - 1)->startOfDay();
        $endDate = now()->endOfDay();
        $startDateString = $startDate->toDateString();
        $endDateString = $endDate->toDateString();

        $requestBase = StockOutRequest::whereBetween('request_date', [$startDateString, $endDateString]);

        $requestFunnel = [
            'pending' => (clone $requestBase)->where('status', 'pending')->count(),
            'approved' => (clone $requestBase)->where('status', 'approved')->count(),
            'completed' => (clone $requestBase)->where('status', 'completed')->count(),
            'rejected' => (clone $requestBase)->where('status', 'rejected')->count(),
            'cancelled' => (clone $requestBase)->where('status', 'cancelled')->count(),
        ];
        $requestFunnel['total'] = array_sum($requestFunnel);

        $processedRequests = StockOutRequest::whereBetween('request_date', [$startDateString, $endDateString])
            ->whereNotNull('processed_at')
            ->get(['status', 'created_at', 'processed_at', 'updated_at']);

        $approvalDurations = [];
        $completionDurations = [];
        $processedWithin24 = 0;

        foreach ($processedRequests as $request) {
            if ($request->created_at && $request->processed_at) {
                $approvalHours = $request->created_at->diffInMinutes($request->processed_at) / 60;
                $approvalDurations[] = $approvalHours;

                if ($approvalHours <= 24) {
                    $processedWithin24++;
                }
            }

            if ($request->status === 'completed' && $request->processed_at && $request->updated_at) {
                $completionDurations[] = $request->processed_at->diffInMinutes($request->updated_at) / 60;
            }
        }

        $processedTotal = count($approvalDurations);
        $avgApprovalHours = $processedTotal > 0 ? round(array_sum($approvalDurations) / $processedTotal, 1) : 0;
        $avgCompletionHours = count($completionDurations) > 0 ? round(array_sum($completionDurations) / count($completionDurations), 1) : 0;
        $processedWithin24Rate = $processedTotal > 0 ? round(($processedWithin24 / $processedTotal) * 100, 1) : 0;

        $movingItems = StockOut::with('item:id,name')
            ->whereBetween('date', [$startDateString, $endDateString])
            ->selectRaw('item_id, SUM(quantity) as total_out')
            ->groupBy('item_id')
            ->orderByDesc('total_out')
            ->limit(10)
            ->get();

        $movingTotalQty = (int) $movingItems->sum('total_out');
        $paretoLabels = [];
        $paretoValues = [];
        $paretoCumulative = [];
        $runningTotal = 0;

        foreach ($movingItems as $movement) {
            $qty = (int) $movement->total_out;
            $runningTotal += $qty;

            $paretoLabels[] = $movement->item?->name ?? 'Unknown Item';
            $paretoValues[] = $qty;
            $paretoCumulative[] = $movingTotalQty > 0 ? round(($runningTotal / $movingTotalQty) * 100, 1) : 0;
        }

        $latestStockIn = StockIn::selectRaw('item_id, MAX(date) as latest_date')
            ->groupBy('item_id')
            ->pluck('latest_date', 'item_id');

        $latestStockOut = StockOut::selectRaw('item_id, MAX(date) as latest_date')
            ->groupBy('item_id')
            ->pluck('latest_date', 'item_id');

        $agingBuckets = [
            '0-30 hari' => 0,
            '31-60 hari' => 0,
            '61-90 hari' => 0,
            '>90 hari' => 0,
            'Belum bergerak' => 0,
        ];

        $items = Item::select('id')->get();
        foreach ($items as $item) {
            $lastIn = $latestStockIn[$item->id] ?? null;
            $lastOut = $latestStockOut[$item->id] ?? null;

            if (!$lastIn && !$lastOut) {
                $agingBuckets['Belum bergerak']++;
                continue;
            }

            $lastMovement = null;
            if ($lastIn && $lastOut) {
                $lastMovement = Carbon::parse($lastIn)->greaterThan(Carbon::parse($lastOut)) ? $lastIn : $lastOut;
            } elseif ($lastIn) {
                $lastMovement = $lastIn;
            } else {
                $lastMovement = $lastOut;
            }

            $daysSinceMovement = Carbon::parse($lastMovement)->diffInDays(now());

            if ($daysSinceMovement <= 30) {
                $agingBuckets['0-30 hari']++;
            } elseif ($daysSinceMovement <= 60) {
                $agingBuckets['31-60 hari']++;
            } elseif ($daysSinceMovement <= 90) {
                $agingBuckets['61-90 hari']++;
            } else {
                $agingBuckets['>90 hari']++;
            }
        }

        $dates = [];
        for ($i = $rangeDays - 1; $i >= 0; $i--) {
            $dates[] = now()->subDays($i)->toDateString();
        }

        $requestedMap = StockOutRequest::whereBetween('request_date', [$startDateString, $endDateString])
            ->selectRaw('request_date, COUNT(*) as total')
            ->groupBy('request_date')
            ->pluck('total', 'request_date');

        $completedMap = StockOutRequest::whereBetween('pickup_date', [$startDateString, $endDateString])
            ->where('status', 'completed')
            ->selectRaw('pickup_date, COUNT(*) as total')
            ->groupBy('pickup_date')
            ->pluck('total', 'pickup_date');

        $trendLabels = [];
        $trendRequested = [];
        $trendCompleted = [];
        $trendFillRate = [];

        foreach ($dates as $date) {
            $requested = (int) ($requestedMap[$date] ?? 0);
            $completed = (int) ($completedMap[$date] ?? 0);

            $trendLabels[] = Carbon::parse($date)->translatedFormat('d M');
            $trendRequested[] = $requested;
            $trendCompleted[] = $completed;
            $trendFillRate[] = $requested > 0 ? round(min(($completed / $requested) * 100, 100), 1) : ($completed > 0 ? 100 : 0);
        }

        $totalRequested = array_sum($trendRequested);
        $totalCompleted = array_sum($trendCompleted);
        $overallFulfillmentRate = $totalRequested > 0 ? round(($totalCompleted / $totalRequested) * 100, 1) : 0;

        return [
            'request_funnel' => $requestFunnel,
            'sla' => [
                'avg_approval_hours' => $avgApprovalHours,
                'avg_completion_hours' => $avgCompletionHours,
                'processed_within_24h_rate' => $processedWithin24Rate,
                'processed_total' => $processedTotal,
            ],
            'pareto' => [
                'labels' => $paretoLabels,
                'values' => $paretoValues,
                'cumulative' => $paretoCumulative,
                'total_quantity' => $movingTotalQty,
            ],
            'aging' => [
                'labels' => array_keys($agingBuckets),
                'values' => array_values($agingBuckets),
            ],
            'fulfillment' => [
                'labels' => $trendLabels,
                'requested' => $trendRequested,
                'completed' => $trendCompleted,
                'fill_rate' => $trendFillRate,
                'overall_rate' => $overallFulfillmentRate,
                'total_requested' => $totalRequested,
                'total_completed' => $totalCompleted,
            ],
        ];
    }

    public function getChartData()
    {
        // Data untuk 6 bulan terakhir
        $months = [];
        $stockInData = [];
        $stockOutData = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthName = $date->translatedFormat('M Y');
            $months[] = $monthName;
            
            // Hitung total stock in per bulan
            $stockInData[] = StockIn::whereYear('date', $date->year)
                ->whereMonth('date', $date->month)
                ->sum('quantity');
            
            // Hitung total stock out per bulan
            $stockOutData[] = StockOut::whereYear('date', $date->year)
                ->whereMonth('date', $date->month)
                ->sum('quantity');
        }

        // Data stok per kategori
        $categories = \App\Models\Category::withCount('items')
            ->withSum('items', 'stock')
            ->get();
        
        $categoryNames = $categories->pluck('name')->toArray();
        $categoryStocks = $categories->pluck('items_sum_stock')->map(fn($val) => $val ?? 0)->toArray();

        // Top 5 items dengan stok terbanyak
        $topItems = Item::with('unit')
            ->orderBy('stock', 'desc')
            ->take(5)
            ->get();
        
        $topItemNames = $topItems->pluck('name')->toArray();
        $topItemStocks = $topItems->pluck('stock')->toArray();

        return [
            'months' => $months,
            'stock_in' => $stockInData,
            'stock_out' => $stockOutData,
            'category_names' => $categoryNames,
            'category_stocks' => $categoryStocks,
            'top_item_names' => $topItemNames,
            'top_item_stocks' => $topItemStocks,
        ];
    }
}
