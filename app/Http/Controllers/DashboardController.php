<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Category;
use App\Services\StockService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request)
    {
        $stats = $this->stockService->getDashboardStats();
        
        // Tambahan data untuk modal
        $stats['all_items'] = Item::with(['category', 'unit'])->orderBy('name')->get();
        $stats['categories'] = Category::withCount('items')->with(['items' => function($q) {
            $q->with('unit')->orderBy('name');
        }])->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        }

        return view('dashboard.index', $stats);
    }
}
