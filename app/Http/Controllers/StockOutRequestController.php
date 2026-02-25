<?php

namespace App\Http\Controllers;

use App\Models\StockOutRequest;
use App\Models\Item;
use App\Models\Log;
use App\Services\StockService;
use Illuminate\Http\Request;
use Exception;

class StockOutRequestController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    // ==================== STAFF METHODS ====================

    public function staffIndex()
    {
        $requests = StockOutRequest::with(['item.category', 'item.unit', 'processedByUser'])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('stock_out_requests.staff.index', compact('requests'));
    }

    public function staffCreate()
    {
        $items = Item::with(['category', 'unit'])
            ->where('stock', '>', 0)
            ->get();
        return view('stock_out_requests.staff.create', compact('items'));
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

        $item = Item::findOrFail($validated['item_id']);

        if ($validated['quantity'] > $item->stock) {
            return back()->withInput()->with('error', 'Jumlah melebihi stok yang tersedia (' . $item->stock . ')');
        }

        $stockRequest = StockOutRequest::create([
            'user_id' => auth()->id(),
            'item_id' => $validated['item_id'],
            'quantity' => $validated['quantity'],
            'notes' => $validated['notes'],
            'status' => 'pending',
            'request_date' => now()->toDateString(),
        ]);

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'stock_request_created',
            'description' => 'User ' . auth()->user()->name . ' mengajukan permintaan pengambilan ' . $item->name . ' sebanyak ' . $validated['quantity']
        ]);

        return redirect()->route('stock-request.staff.index')
            ->with('success', 'Permintaan pengambilan barang berhasil diajukan. Mohon tunggu persetujuan dari Admin Gudang.');
    }

    public function staffCancel($id)
    {
        $request = StockOutRequest::where('user_id', auth()->id())
            ->where('id', $id)
            ->firstOrFail();

        if (!$request->isPending()) {
            return back()->with('error', 'Hanya permintaan dengan status "Menunggu" yang dapat dibatalkan.');
        }

        $request->update([
            'status' => 'cancelled',
        ]);

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'stock_request_cancelled',
            'description' => 'User ' . auth()->user()->name . ' membatalkan permintaan pengambilan ' . $request->item->name
        ]);

        return back()->with('success', 'Permintaan berhasil dibatalkan.');
    }

    // ==================== ADMIN/WAREHOUSE METHODS ====================

    public function checklistIndex(Request $request)
    {
        $status = $request->get('status', 'pending');
        
        $query = StockOutRequest::with(['user', 'item.category', 'item.unit', 'processedByUser']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $requests = $query->latest()->paginate(15);

        $counts = [
            'pending' => StockOutRequest::pending()->count(),
            'approved' => StockOutRequest::where('status', 'approved')->count(),
            'completed' => StockOutRequest::where('status', 'completed')->count(),
            'rejected' => StockOutRequest::where('status', 'rejected')->count(),
        ];

        return view('stock_out_requests.checklist.index', compact('requests', 'status', 'counts'));
    }

    public function approve($id)
    {
        $request = StockOutRequest::with('item')->findOrFail($id);

        if (!$request->isPending()) {
            return back()->with('error', 'Permintaan ini sudah diproses sebelumnya.');
        }

        // Check stock availability
        if ($request->quantity > $request->item->stock) {
            return back()->with('error', 'Stok tidak mencukupi. Stok tersedia: ' . $request->item->stock);
        }

        $request->update([
            'status' => 'approved',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'stock_request_approved',
            'description' => auth()->user()->name . ' menyetujui permintaan pengambilan ' . $request->item->name . ' oleh ' . $request->user->name
        ]);

        return back()->with('success', 'Permintaan berhasil disetujui. Pegawai dapat mengambil barang.');
    }

    public function reject(Request $request, $id)
    {
        $stockRequest = StockOutRequest::with('item')->findOrFail($id);

        if (!$stockRequest->isPending()) {
            return back()->with('error', 'Permintaan ini sudah diproses sebelumnya.');
        }

        $validated = $request->validate([
            'reject_reason' => 'nullable|string|max:500',
        ]);

        $stockRequest->update([
            'status' => 'rejected',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
            'reject_reason' => $validated['reject_reason'] ?? null,
        ]);

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'stock_request_rejected',
            'description' => auth()->user()->name . ' menolak permintaan pengambilan ' . $stockRequest->item->name . ' oleh ' . $stockRequest->user->name
        ]);

        return back()->with('success', 'Permintaan berhasil ditolak.');
    }

    public function complete($id)
    {
        $request = StockOutRequest::with('item')->findOrFail($id);

        if (!$request->isApproved()) {
            return back()->with('error', 'Hanya permintaan yang sudah disetujui yang dapat diselesaikan.');
        }

        // Check stock one more time
        if ($request->quantity > $request->item->stock) {
            return back()->with('error', 'Stok tidak mencukupi. Stok tersedia: ' . $request->item->stock);
        }

        try {
            // Deduct stock using StockService
            $this->stockService->addStockOut([
                'item_id' => $request->item_id,
                'quantity' => $request->quantity,
                'date' => now()->toDateString(),
                'notes' => 'Pengambilan oleh ' . $request->user->name . ($request->notes ? ' - ' . $request->notes : ''),
            ]);

            $request->update([
                'status' => 'completed',
                'pickup_date' => now()->toDateString(),
            ]);

            Log::create([
                'user_id' => auth()->id(),
                'action' => 'stock_request_completed',
                'description' => auth()->user()->name . ' menyelesaikan pengambilan ' . $request->item->name . ' oleh ' . $request->user->name . ' (Qty: ' . $request->quantity . ')'
            ]);

            return back()->with('success', 'Pengambilan barang berhasil diselesaikan. Stok telah dikurangi.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function undoApproval($id)
    {
        $request = StockOutRequest::findOrFail($id);

        if (!$request->isApproved()) {
            return back()->with('error', 'Hanya permintaan yang sudah disetujui yang dapat dibatalkan persetujuannya.');
        }

        $request->update([
            'status' => 'pending',
            'processed_by' => null,
            'processed_at' => null,
        ]);

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'stock_request_undo_approval',
            'description' => auth()->user()->name . ' membatalkan persetujuan pengambilan ' . $request->item->name
        ]);

        return back()->with('success', 'Persetujuan berhasil dibatalkan. Status kembali ke "Menunggu".');
    }

    // ==================== API FOR NOTIFICATION ====================

    public function getPendingCount()
    {
        $pendingCount = StockOutRequest::pending()->count();
        $latestPending = StockOutRequest::with(['user', 'item'])
            ->pending()
            ->latest()
            ->first();

        // Get recent pending requests (up to 10)
        $recentRequests = StockOutRequest::with(['user', 'item'])
            ->pending()
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'user_name' => $request->user->name,
                    'item_name' => $request->item->name,
                    'quantity' => $request->quantity,
                    'created_at' => $request->created_at->toIso8601String(),
                    'created_at_human' => $request->created_at->diffForHumans(),
                    'created_at_formatted' => $request->created_at->format('H:i, d M Y'),
                ];
            });

        return response()->json([
            'count' => $pendingCount,
            'latest' => $latestPending ? [
                'id' => $latestPending->id,
                'user_name' => $latestPending->user->name,
                'item_name' => $latestPending->item->name,
                'quantity' => $latestPending->quantity,
                'created_at' => $latestPending->created_at->toIso8601String(),
                'created_at_human' => $latestPending->created_at->diffForHumans(),
                'created_at_formatted' => $latestPending->created_at->format('H:i, d M Y'),
            ] : null,
            'requests' => $recentRequests
        ]);
    }
}
