<?php

namespace App\Http\Controllers;

use App\Models\StockOut;
use App\Models\Item;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        // Simpan filter ke session jika ada parameter baru
        if ($request->has('item_id') || $request->has('start_date') || $request->has('end_date')) {
            session([
                'stock_out_filter' => [
                    'item_id' => $request->item_id,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                ]
            ]);
        }

        // Ambil filter dari session
        $filters = session('stock_out_filter', []);
        $item_id = $request->item_id ?? ($filters['item_id'] ?? null);
        $start_date = $request->start_date ?? ($filters['start_date'] ?? null);
        $end_date = $request->end_date ?? ($filters['end_date'] ?? null);

        $stockOuts = StockOut::with(['item.category', 'item.unit', 'user'])
            ->when($item_id, function ($query, $itemId) {
                $query->where('item_id', $itemId);
            })
            ->when($start_date, function ($query, $startDate) {
                $query->whereDate('date', '>=', $startDate);
            })
            ->when($end_date, function ($query, $endDate) {
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
        $activeFilters = compact('item_id', 'start_date', 'end_date');
        return view('stock_out.index', compact('stockOuts', 'items', 'activeFilters'));
    }

    public function clearFilter()
    {
        session()->forget('stock_out_filter');
        return redirect()->route('stock-out.index');
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
        $items = Item::with(['category', 'unit'])->orderBy('name', 'asc')->get();
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
        $items = Item::with(['category', 'unit'])->orderBy('name', 'asc')->get();
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

    // ==================== STAFF METHODS ====================
    // Khusus untuk pegawai melakukan pengambilan barang

    public function staffIndex()
    {
        // Staff hanya bisa melihat transaksi pengambilan barang miliknya sendiri
        $stockOuts = StockOut::with(['item.category', 'item.unit'])
            ->where('user_id', auth()->id())
            ->latest('date')
            ->paginate(10);

        return view('stock_out.staff.index', compact('stockOuts'));
    }

    public function staffCreate()
    {
        $items = Item::with(['category', 'unit'])
            ->where('stock', '>', 0)
            ->orderBy('name', 'asc')
            ->get();
        return view('stock_out.staff.create', compact('items'));
    }

    public function staffStore(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ], [
            'item_id.required' => 'Barang harus dipilih',
            'quantity.required' => 'Jumlah harus diisi',
            'quantity.min' => 'Jumlah minimal 1',
        ]);

        // Set tanggal hari ini dan user_id
        $validated['date'] = now()->toDateString();

        try {
            $stockOut = $this->stockService->addStockOut($validated);

            return redirect()->route('stock-out.staff.index')
                ->with('success', 'Pengambilan barang berhasil dicatat');
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function storeBatch(Request $request)
    {
        $request->validate([
            'items' => 'required|string',
            'date' => 'required|date',
        ]);

        $itemsData = json_decode($request->items, true);
        
        if (!is_array($itemsData) || count($itemsData) === 0) {
            return back()->with('error', 'Tidak ada barang yang ditambahkan');
        }

        // Validate stock availability for all items first
        $stockCheck = [];
        foreach ($itemsData as $itemData) {
            $itemId = $itemData['item_id'];
            if (!isset($stockCheck[$itemId])) {
                $stockCheck[$itemId] = 0;
            }
            $stockCheck[$itemId] += $itemData['quantity'];
        }

        foreach ($stockCheck as $itemId => $totalQty) {
            $item = Item::find($itemId);
            if (!$item || $item->stock < $totalQty) {
                $itemName = $item ? $item->name : 'Unknown';
                return back()->with('error', "Stok {$itemName} tidak mencukupi. Tersedia: " . ($item ? $item->stock : 0));
            }
        }

        try {
            DB::transaction(function () use ($itemsData, $request) {
                foreach ($itemsData as $itemData) {
                    $this->stockService->addStockOut([
                        'item_id' => $itemData['item_id'],
                        'quantity' => $itemData['quantity'],
                        'date' => $request->date,
                        'notes' => $itemData['notes'] ?? null,
                    ]);
                }
            });

            $count = count($itemsData);
            return redirect()->route('stock-out.index')
                ->with('success', "{$count} transaksi barang keluar berhasil ditambahkan");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
