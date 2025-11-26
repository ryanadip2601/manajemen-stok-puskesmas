<?php

namespace App\Http\Controllers;

use App\Models\StockIn;
use App\Models\Item;
use App\Services\StockService;
use Illuminate\Http\Request;
use Exception;

class StockInController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request)
    {
        $stockIns = StockIn::with(['item.category', 'item.unit', 'user'])
            ->when($request->item_id, function ($query, $itemId) {
                $query->where('item_id', $itemId);
            })
            ->when($request->start_date, function ($query, $startDate) {
                $query->whereDate('date', '>=', $startDate);
            })
            ->when($request->end_date, function ($query, $endDate) {
                $query->whereDate('date', '<=', $endDate);
            })
            ->latest('date')
            ->paginate(10);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $stockIns
            ]);
        }

        $items = Item::all();
        return view('stock_in.index', compact('stockIns', 'items'));
    }

    public function show($id)
    {
        $stockIn = StockIn::with(['item.category', 'item.unit', 'user'])->findOrFail($id);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $stockIn
            ]);
        }

        return view('stock_in.show', compact('stockIn'));
    }

    public function create()
    {
        $items = Item::with(['category', 'unit'])->get();
        return view('stock_in.create', compact('items'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ], [
            'item_id.required' => 'Barang harus dipilih',
            'quantity.required' => 'Jumlah harus diisi',
            'quantity.min' => 'Jumlah minimal 1',
            'date.required' => 'Tanggal harus diisi',
        ]);

        try {
            $stockIn = $this->stockService->addStockIn($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Barang masuk berhasil ditambahkan',
                    'data' => $stockIn->load(['item.category', 'item.unit', 'user'])
                ], 201);
            }

            return redirect()->route('stock-in.index')->with('success', 'Barang masuk berhasil ditambahkan');
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 400);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit($id)
    {
        $stockIn = StockIn::with(['item'])->findOrFail($id);
        $items = Item::with(['category', 'unit'])->get();
        return view('stock_in.edit', compact('stockIn', 'items'));
    }

    public function update(Request $request, $id)
    {
        $stockIn = StockIn::findOrFail($id);

        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ], [
            'item_id.required' => 'Barang harus dipilih',
            'quantity.required' => 'Jumlah harus diisi',
            'quantity.min' => 'Jumlah minimal 1',
            'date.required' => 'Tanggal harus diisi',
        ]);

        try {
            $this->stockService->updateStockIn($stockIn, $validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Barang masuk berhasil diupdate',
                    'data' => $stockIn->load(['item.category', 'item.unit', 'user'])
                ]);
            }

            return redirect()->route('stock-in.index')->with('success', 'Barang masuk berhasil diupdate');
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 400);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy(Request $request, $id)
    {
        $stockIn = StockIn::findOrFail($id);

        try {
            $this->stockService->deleteStockIn($stockIn);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Barang masuk berhasil dihapus'
                ]);
            }

            return redirect()->route('stock-in.index')->with('success', 'Barang masuk berhasil dihapus');
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 400);
            }

            return back()->with('error', $e->getMessage());
        }
    }
}
