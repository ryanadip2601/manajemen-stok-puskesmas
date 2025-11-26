<?php

namespace App\Http\Controllers;

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

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        }

        return view('dashboard.index', $stats);
    }
}
