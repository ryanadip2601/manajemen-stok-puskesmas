<?php

namespace App\Http\Controllers;

use App\Models\StockOut;
use App\Models\Item;
use App\Services\StockService;
use Illuminate\Http\Request;
use Exception;

class StockOutController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request)
    {
        $stockOuts = StockOut::with(['item.category', 'item.unit', 'user'])
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
                'data' => $stockOuts
            ]);
        }

        $items = Item::all();
        return view('stock_out.index', compact('stockOuts', 'items'));
    }

    public function show($id)
    {
        $stockOut = StockOut::with(['item.category', 'item.unit', 'user'])->findOrFail($id);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $stockOut
            ]);
        }

        return view('stock_out.show', compact('stockOut'));
    }

    public function create()
    {
        $items = Item::with(['category', 'unit'])->get();
        return view('stock_out.create', compact('items'));
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
            $stockOut = $this->stockService->addStockOut($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Barang keluar berhasil ditambahkan',
                    'data' => $stockOut->load(['item.category', 'item.unit', 'user'])
                ], 201);
            }

            return redirect()->route('stock-out.index')->with('success', 'Barang keluar berhasil ditambahkan');
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
        $stockOut = StockOut::with(['item'])->findOrFail($id);
        $items = Item::with(['category', 'unit'])->get();
        return view('stock_out.edit', compact('stockOut', 'items'));
    }

    public function update(Request $request, $id)
    {
        $stockOut = StockOut::findOrFail($id);

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
            $this->stockService->updateStockOut($stockOut, $validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Barang keluar berhasil diupdate',
                    'data' => $stockOut->load(['item.category', 'item.unit', 'user'])
                ]);
            }

            return redirect()->route('stock-out.index')->with('success', 'Barang keluar berhasil diupdate');
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
        $stockOut = StockOut::findOrFail($id);

        try {
            $this->stockService->deleteStockOut($stockOut);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Barang keluar berhasil dihapus'
                ]);
            }

            return redirect()->route('stock-out.index')->with('success', 'Barang keluar berhasil dihapus');
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
