<?php

namespace App\Http\Controllers;

use App\Models\PermintaanRestok;
use App\Models\Item;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermintaanRestokController extends Controller
{
    // ==================== STAFF METHODS ====================

    public function index(Request $request)
    {
        $query = PermintaanRestok::with(['item.category', 'item.unit', 'verifiedBy'])
            ->byUser(auth()->id())
            ->latest('tanggal_permintaan');

        if ($request->filled('status')) {
            $query->status($request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('kode_restok', 'like', "%{$search}%")
                  ->orWhereHas('item', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $permintaan = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => PermintaanRestok::byUser(auth()->id())->count(),
            'pending' => PermintaanRestok::byUser(auth()->id())->pending()->count(),
            'disetujui' => PermintaanRestok::byUser(auth()->id())->disetujui()->count(),
            'tersedia' => PermintaanRestok::byUser(auth()->id())->tersedia()->count(),
            'ditolak' => PermintaanRestok::byUser(auth()->id())->ditolak()->count(),
        ];

        return view('restok.index', compact('permintaan', 'stats'));
    }

    public function create()
    {
        // Tampilkan barang yang stoknya 0 atau di bawah minimum_stock
        $items = Item::with(['category', 'unit'])
            ->where(function($q) {
                $q->where('stock', 0)
                  ->orWhereColumn('stock', '<=', 'minimum_stock');
            })
            ->orderBy('name', 'asc')
            ->get();

        return view('restok.create', compact('items'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'jumlah_diminta' => 'required|integer|min:1|max:99999',
            'prioritas' => 'required|in:rendah,sedang,tinggi',
            'keterangan' => 'nullable|string|max:2000',
        ], [
            'item_id.required' => 'Barang harus dipilih',
            'jumlah_diminta.required' => 'Jumlah yang diminta wajib diisi',
            'jumlah_diminta.min' => 'Jumlah minimal 1',
            'prioritas.required' => 'Prioritas wajib dipilih',
        ]);

        // Cek apakah item memang stok 0 atau low stock
        $item = Item::findOrFail($validated['item_id']);
        if ($item->stock > $item->minimum_stock) {
            return back()->withInput()->with('error', 'Barang "' . $item->name . '" masih memiliki stok yang cukup (' . $item->stock . '). Permintaan restok hanya untuk barang yang stoknya habis atau menipis.');
        }

        // Cek duplikasi pending
        $existing = PermintaanRestok::where('user_id', auth()->id())
            ->where('item_id', $request->item_id)
            ->whereIn('status', ['pending', 'disetujui'])
            ->first();

        if ($existing) {
            return back()->withInput()->with('error', 'Anda sudah memiliki permintaan restok yang masih aktif untuk barang ini. Kode: ' . $existing->kode_restok);
        }

        DB::beginTransaction();
        try {
            $permintaan = PermintaanRestok::create([
                'user_id' => auth()->id(),
                'item_id' => $validated['item_id'],
                'jumlah_diminta' => $validated['jumlah_diminta'],
                'prioritas' => $validated['prioritas'],
                'keterangan' => $validated['keterangan'] ?? null,
                'tanggal_permintaan' => now(),
                'status' => 'pending',
            ]);

            Log::create([
                'user_id' => auth()->id(),
                'action' => 'permintaan_restok_created',
                'description' => 'User ' . auth()->user()->name . ' mengajukan permintaan restok barang: ' . $item->name . ' (' . $permintaan->kode_restok . ')'
            ]);

            DB::commit();

            return redirect()->route('permintaan-restok.index')
                ->with('success', 'Permintaan restok berhasil diajukan dengan kode: ' . $permintaan->kode_restok);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $permintaan = PermintaanRestok::with(['user', 'item.category', 'item.unit', 'verifiedBy'])
            ->findOrFail($id);

        if (!auth()->user()->canManageWarehouse() && $permintaan->user_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses ke permintaan ini');
        }

        return view('restok.show', compact('permintaan'));
    }

    public function cancel($id)
    {
        $permintaan = PermintaanRestok::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->findOrFail($id);

        DB::beginTransaction();
        try {
            $kode = $permintaan->kode_restok;
            $itemName = $permintaan->item->name;
            $permintaan->delete();

            Log::create([
                'user_id' => auth()->id(),
                'action' => 'permintaan_restok_cancelled',
                'description' => 'User ' . auth()->user()->name . ' membatalkan permintaan restok: ' . $kode . ' (' . $itemName . ')'
            ]);

            DB::commit();

            return redirect()->route('permintaan-restok.index')
                ->with('success', 'Permintaan restok berhasil dibatalkan');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // ==================== ADMIN METHODS ====================

    public function adminIndex(Request $request)
    {
        $query = PermintaanRestok::with(['user', 'item.category', 'item.unit', 'verifiedBy'])
            ->latest('tanggal_permintaan');

        if ($request->filled('status')) {
            $query->status($request->status);
        }

        if ($request->filled('prioritas')) {
            $query->prioritas($request->prioritas);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('kode_restok', 'like', "%{$search}%")
                  ->orWhereHas('item', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('dari_tanggal')) {
            $query->whereDate('tanggal_permintaan', '>=', $request->dari_tanggal);
        }

        if ($request->filled('sampai_tanggal')) {
            $query->whereDate('tanggal_permintaan', '<=', $request->sampai_tanggal);
        }

        $permintaan = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => PermintaanRestok::count(),
            'pending' => PermintaanRestok::pending()->count(),
            'disetujui' => PermintaanRestok::disetujui()->count(),
            'tersedia' => PermintaanRestok::tersedia()->count(),
            'ditolak' => PermintaanRestok::ditolak()->count(),
        ];

        return view('restok.admin.index', compact('permintaan', 'stats'));
    }

    public function adminShow($id)
    {
        $permintaan = PermintaanRestok::with(['user', 'item.category', 'item.unit', 'verifiedBy'])
            ->findOrFail($id);

        return view('restok.admin.show', compact('permintaan'));
    }

    public function approve(Request $request, $id)
    {
        $permintaan = PermintaanRestok::where('status', 'pending')->findOrFail($id);

        $request->validate([
            'catatan_admin' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $permintaan->setujui(auth()->id(), $request->catatan_admin);

            Log::create([
                'user_id' => auth()->id(),
                'action' => 'permintaan_restok_approved',
                'description' => 'Admin ' . auth()->user()->name . ' menyetujui permintaan restok: ' . $permintaan->kode_restok . ' (' . $permintaan->item->name . ')'
            ]);

            DB::commit();

            return redirect()->route('permintaan-restok.admin.index')
                ->with('success', 'Permintaan restok disetujui. Barang akan ditandai tersedia ketika stok masuk.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $permintaan = PermintaanRestok::where('status', 'pending')->findOrFail($id);

        $request->validate([
            'alasan_penolakan' => 'required|string|max:1000',
        ], [
            'alasan_penolakan.required' => 'Alasan penolakan wajib diisi',
        ]);

        DB::beginTransaction();
        try {
            $permintaan->tolak(auth()->id(), $request->alasan_penolakan);

            Log::create([
                'user_id' => auth()->id(),
                'action' => 'permintaan_restok_rejected',
                'description' => 'Admin ' . auth()->user()->name . ' menolak permintaan restok: ' . $permintaan->kode_restok
            ]);

            DB::commit();

            return redirect()->route('permintaan-restok.admin.index')
                ->with('success', 'Permintaan restok ditolak');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // ==================== API ====================

    public function getPendingCount()
    {
        $count = PermintaanRestok::pending()->count();
        return response()->json(['count' => $count]);
    }

    public function getPopupNotifications()
    {
        $notifications = PermintaanRestok::getPopupNotifications(auth()->id());
        return response()->json([
            'count' => $notifications->count(),
            'notifications' => $notifications->map(function($n) {
                return [
                    'id' => $n->id,
                    'kode_restok' => $n->kode_restok,
                    'item_name' => $n->item->name ?? 'Unknown',
                    'jumlah_diminta' => $n->jumlah_diminta,
                    'item_stock' => $n->item->stock ?? 0,
                ];
            }),
        ]);
    }

    public function markAsRead(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            // Mark all for current user
            PermintaanRestok::where('user_id', auth()->id())
                ->where('status', PermintaanRestok::STATUS_TERSEDIA)
                ->whereNull('notified_at')
                ->update([
                    'status' => PermintaanRestok::STATUS_DIBACA,
                    'notified_at' => now(),
                ]);
        } else {
            PermintaanRestok::where('user_id', auth()->id())
                ->whereIn('id', $ids)
                ->where('status', PermintaanRestok::STATUS_TERSEDIA)
                ->update([
                    'status' => PermintaanRestok::STATUS_DIBACA,
                    'notified_at' => now(),
                ]);
        }

        return response()->json(['success' => true]);
    }
}
