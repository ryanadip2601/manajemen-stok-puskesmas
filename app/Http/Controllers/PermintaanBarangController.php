<?php

namespace App\Http\Controllers;

use App\Models\PermintaanBarang;
use App\Models\Item;
use App\Models\Ruangan;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PermintaanBarangController extends Controller
{
    // ==================== STAFF METHODS ====================

    public function index(Request $request)
    {
        $query = PermintaanBarang::with(['item.category', 'item.unit', 'ruangan', 'verifiedBy'])
            ->byUser(auth()->id())
            ->latest('tanggal_permintaan');

        if ($request->filled('tipe')) {
            if ($request->tipe === 'barang_baru') {
                $query->tipeBarangBaru();
            } elseif ($request->tipe === 'restok') {
                $query->tipeRestok();
            }
        }

        if ($request->filled('status')) {
            $query->status($request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('kode_permintaan', 'like', "%{$search}%")
                  ->orWhere('nama_barang', 'like', "%{$search}%")
                  ->orWhereHas('item', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $permintaan = $query->paginate(15)->withQueryString();

        $userId = auth()->id();
        $stats = [
            'total' => PermintaanBarang::byUser($userId)->count(),
            'pending' => PermintaanBarang::byUser($userId)->pending()->count(),
            'disetujui' => PermintaanBarang::byUser($userId)->disetujui()->count(),
            'ditolak' => PermintaanBarang::byUser($userId)->ditolak()->count(),
            'revisi' => PermintaanBarang::byUser($userId)->revisi()->count(),
            'tersedia' => PermintaanBarang::byUser($userId)->tersedia()->count(),
        ];

        return view('permintaan-barang.index', compact('permintaan', 'stats'));
    }

    public function create()
    {
        $ruanganList = Ruangan::active()->orderBy('nama_ruangan')->get();

        // Semua barang untuk pencarian
        $allItems = Item::with(['category', 'unit'])
            ->orderBy('name', 'asc')
            ->get();

        // Barang yang stoknya 0 atau di bawah minimum (untuk restok)
        $lowStockItems = Item::with(['category', 'unit'])
            ->where(function($q) {
                $q->where('stock', 0)
                  ->orWhereColumn('stock', '<=', 'minimum_stock');
            })
            ->orderBy('name', 'asc')
            ->get();

        return view('permintaan-barang.create', compact('ruanganList', 'allItems', 'lowStockItems'));
    }

    public function store(Request $request)
    {
        $isRestok = $request->filled('item_id') && !$request->boolean('is_manual');

        if ($isRestok) {
            // Validasi untuk restok
            $validated = $request->validate([
                'item_id' => 'required|exists:items,id',
                'jumlah' => 'required|integer|min:1|max:99999',
                'prioritas' => 'required|in:rendah,sedang,tinggi',
                'keterangan' => 'nullable|string|max:2000',
            ], [
                'item_id.required' => 'Barang harus dipilih',
                'jumlah.required' => 'Jumlah wajib diisi',
                'jumlah.min' => 'Jumlah minimal 1',
                'prioritas.required' => 'Prioritas wajib dipilih',
            ]);

            $item = Item::findOrFail($validated['item_id']);

            // Cek apakah item memang low stock
            if ($item->stock > $item->minimum_stock) {
                return back()->withInput()->with('error', 'Barang "' . $item->name . '" masih memiliki stok yang cukup (' . $item->stock . '). Permintaan restok hanya untuk barang yang stoknya habis atau menipis.');
            }

            // Cek duplikasi pending
            $existing = PermintaanBarang::where('user_id', auth()->id())
                ->where('item_id', $request->item_id)
                ->where('tipe', PermintaanBarang::TIPE_RESTOK)
                ->whereIn('status', ['pending', 'disetujui'])
                ->first();

            if ($existing) {
                return back()->withInput()->with('error', 'Anda sudah memiliki permintaan restok yang masih aktif untuk barang ini. Kode: ' . $existing->kode_permintaan);
            }

            DB::beginTransaction();
            try {
                $permintaan = PermintaanBarang::create([
                    'user_id' => auth()->id(),
                    'tipe' => PermintaanBarang::TIPE_RESTOK,
                    'item_id' => $validated['item_id'],
                    'nama_barang' => $item->name,
                    'jumlah' => $validated['jumlah'],
                    'prioritas' => $validated['prioritas'],
                    'keterangan' => $validated['keterangan'] ?? null,
                    'tanggal_permintaan' => now(),
                    'status' => 'pending',
                ]);

                Log::create([
                    'user_id' => auth()->id(),
                    'action' => 'permintaan_barang_restok_created',
                    'description' => 'User ' . auth()->user()->name . ' mengajukan restok barang: ' . $item->name . ' (' . $permintaan->kode_permintaan . ')'
                ]);

                DB::commit();

                return redirect()->route('permintaan-barang.index')
                    ->with('success', 'Permintaan restok berhasil diajukan dengan kode: ' . $permintaan->kode_permintaan);

            } catch (\Exception $e) {
                DB::rollBack();
                return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
            }

        } else {
            // Validasi untuk barang baru (manual)
            $validated = $request->validate([
                'nama_barang' => 'required|string|max:255',
                'ruangan_id' => 'nullable|exists:ruangan,id',
                'jumlah' => 'required|integer|min:1|max:9999',
                'prioritas' => 'required|in:rendah,sedang,tinggi',
                'keterangan' => 'nullable|string|max:2000',
                'foto_tipe' => 'nullable|in:upload,url',
                'foto_upload' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
                'foto_url' => 'nullable|url|max:2000',
            ], [
                'nama_barang.required' => 'Nama barang wajib diisi',
                'jumlah.required' => 'Jumlah wajib diisi',
                'jumlah.min' => 'Jumlah minimal 1',
                'prioritas.required' => 'Prioritas wajib dipilih',
                'foto_upload.image' => 'File harus berupa gambar',
                'foto_upload.max' => 'Ukuran gambar maksimal 5MB',
                'foto_url.url' => 'URL gambar tidak valid',
            ]);

            // Cek duplikasi pending untuk barang baru
            $existingQuery = PermintaanBarang::where('user_id', auth()->id())
                ->where('nama_barang', $request->nama_barang)
                ->where('tipe', PermintaanBarang::TIPE_BARANG_BARU)
                ->whereIn('status', ['pending', 'diproses', 'revisi']);

            if ($request->filled('ruangan_id')) {
                $existingQuery->where('ruangan_id', $request->ruangan_id);
            }

            $existing = $existingQuery->first();

            if ($existing) {
                return back()->withInput()->with('error', 'Anda sudah memiliki permintaan pending untuk barang yang sama. Kode: ' . $existing->kode_permintaan);
            }

            DB::beginTransaction();
            try {
                $fotoBarang = null;
                $tipeFoto = null;

                if ($request->foto_tipe === 'upload' && $request->hasFile('foto_upload')) {
                    $file = $request->file('foto_upload');
                    $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $fotoBarang = $file->storeAs('permintaan_barang', $filename, 'public');
                    $tipeFoto = 'upload';
                } elseif ($request->foto_tipe === 'url' && $request->filled('foto_url')) {
                    $fotoBarang = $request->foto_url;
                    $tipeFoto = 'url';
                }

                $permintaan = PermintaanBarang::create([
                    'user_id' => auth()->id(),
                    'tipe' => PermintaanBarang::TIPE_BARANG_BARU,
                    'nama_barang' => $validated['nama_barang'],
                    'ruangan_id' => $validated['ruangan_id'] ?? null,
                    'jumlah' => $validated['jumlah'],
                    'prioritas' => $validated['prioritas'],
                    'keterangan' => $validated['keterangan'] ?? null,
                    'foto_barang' => $fotoBarang,
                    'tipe_foto_barang' => $tipeFoto,
                    'tanggal_permintaan' => now(),
                    'status' => 'pending',
                ]);

                Log::create([
                    'user_id' => auth()->id(),
                    'action' => 'permintaan_barang_baru_created',
                    'description' => 'User ' . auth()->user()->name . ' mengajukan permintaan barang baru: ' . $permintaan->nama_barang . ' (' . $permintaan->kode_permintaan . ')'
                ]);

                DB::commit();

                return redirect()->route('permintaan-barang.index')
                    ->with('success', 'Permintaan barang baru berhasil diajukan dengan kode: ' . $permintaan->kode_permintaan);

            } catch (\Exception $e) {
                DB::rollBack();
                return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
            }
        }
    }

    public function show($id)
    {
        $permintaan = PermintaanBarang::with(['user', 'item.category', 'item.unit', 'ruangan', 'verifiedBy', 'createdItem'])
            ->findOrFail($id);

        if (!auth()->user()->canManageWarehouse() && $permintaan->user_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses ke permintaan ini');
        }

        return view('permintaan-barang.show', compact('permintaan'));
    }

    public function edit($id)
    {
        $permintaan = PermintaanBarang::where('user_id', auth()->id())
            ->whereIn('status', ['pending', 'revisi'])
            ->findOrFail($id);

        $ruanganList = Ruangan::active()->orderBy('nama_ruangan')->get();

        $allItems = Item::with(['category', 'unit'])
            ->orderBy('name', 'asc')
            ->get();

        $lowStockItems = Item::with(['category', 'unit'])
            ->where(function($q) {
                $q->where('stock', 0)
                  ->orWhereColumn('stock', '<=', 'minimum_stock');
            })
            ->orderBy('name', 'asc')
            ->get();

        return view('permintaan-barang.edit', compact('permintaan', 'ruanganList', 'allItems', 'lowStockItems'));
    }

    public function update(Request $request, $id)
    {
        $permintaan = PermintaanBarang::where('user_id', auth()->id())
            ->whereIn('status', ['pending', 'revisi'])
            ->findOrFail($id);

        $isRestok = $request->filled('item_id') && !$request->boolean('is_manual');

        if ($isRestok) {
            $validated = $request->validate([
                'item_id' => 'required|exists:items,id',
                'jumlah' => 'required|integer|min:1|max:99999',
                'prioritas' => 'required|in:rendah,sedang,tinggi',
                'keterangan' => 'nullable|string|max:2000',
            ]);

            $item = Item::findOrFail($validated['item_id']);

            DB::beginTransaction();
            try {
                $permintaan->update([
                    'tipe' => PermintaanBarang::TIPE_RESTOK,
                    'item_id' => $validated['item_id'],
                    'nama_barang' => $item->name,
                    'ruangan_id' => null,
                    'jumlah' => $validated['jumlah'],
                    'prioritas' => $validated['prioritas'],
                    'keterangan' => $validated['keterangan'] ?? null,
                    'status' => 'pending',
                    'permintaan_revisi' => null,
                ]);

                Log::create([
                    'user_id' => auth()->id(),
                    'action' => 'permintaan_barang_updated',
                    'description' => 'User ' . auth()->user()->name . ' memperbarui permintaan: ' . $permintaan->kode_permintaan
                ]);

                DB::commit();

                return redirect()->route('permintaan-barang.index')
                    ->with('success', 'Permintaan berhasil diperbarui');

            } catch (\Exception $e) {
                DB::rollBack();
                return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
            }
        } else {
            $validated = $request->validate([
                'nama_barang' => 'required|string|max:255',
                'ruangan_id' => 'nullable|exists:ruangan,id',
                'jumlah' => 'required|integer|min:1|max:9999',
                'prioritas' => 'required|in:rendah,sedang,tinggi',
                'keterangan' => 'nullable|string|max:2000',
                'foto_tipe' => 'nullable|in:upload,url',
                'foto_upload' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
                'foto_url' => 'nullable|url|max:2000',
                'hapus_foto' => 'nullable|boolean',
            ]);

            DB::beginTransaction();
            try {
                $updateData = [
                    'tipe' => PermintaanBarang::TIPE_BARANG_BARU,
                    'item_id' => null,
                    'nama_barang' => $validated['nama_barang'],
                    'ruangan_id' => $validated['ruangan_id'] ?? null,
                    'jumlah' => $validated['jumlah'],
                    'prioritas' => $validated['prioritas'],
                    'keterangan' => $validated['keterangan'] ?? null,
                    'status' => 'pending',
                    'permintaan_revisi' => null,
                ];

                if ($request->boolean('hapus_foto')) {
                    if ($permintaan->tipe_foto_barang === 'upload' && $permintaan->foto_barang) {
                        Storage::disk('public')->delete($permintaan->foto_barang);
                    }
                    $updateData['foto_barang'] = null;
                    $updateData['tipe_foto_barang'] = null;
                } else {
                    if ($request->foto_tipe === 'upload' && $request->hasFile('foto_upload')) {
                        if ($permintaan->tipe_foto_barang === 'upload' && $permintaan->foto_barang) {
                            Storage::disk('public')->delete($permintaan->foto_barang);
                        }
                        $file = $request->file('foto_upload');
                        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                        $updateData['foto_barang'] = $file->storeAs('permintaan_barang', $filename, 'public');
                        $updateData['tipe_foto_barang'] = 'upload';
                    } elseif ($request->foto_tipe === 'url' && $request->filled('foto_url')) {
                        if ($permintaan->tipe_foto_barang === 'upload' && $permintaan->foto_barang) {
                            Storage::disk('public')->delete($permintaan->foto_barang);
                        }
                        $updateData['foto_barang'] = $request->foto_url;
                        $updateData['tipe_foto_barang'] = 'url';
                    }
                }

                $permintaan->update($updateData);

                Log::create([
                    'user_id' => auth()->id(),
                    'action' => 'permintaan_barang_updated',
                    'description' => 'User ' . auth()->user()->name . ' memperbarui permintaan: ' . $permintaan->kode_permintaan
                ]);

                DB::commit();

                return redirect()->route('permintaan-barang.index')
                    ->with('success', 'Permintaan berhasil diperbarui');

            } catch (\Exception $e) {
                DB::rollBack();
                return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
            }
        }
    }

    public function cancel($id)
    {
        $permintaan = PermintaanBarang::where('user_id', auth()->id())
            ->whereIn('status', ['pending', 'revisi'])
            ->findOrFail($id);

        DB::beginTransaction();
        try {
            $kode = $permintaan->kode_permintaan;
            $nama = $permintaan->nama_barang;
            $permintaan->delete();

            Log::create([
                'user_id' => auth()->id(),
                'action' => 'permintaan_barang_cancelled',
                'description' => 'User ' . auth()->user()->name . ' membatalkan permintaan: ' . $kode . ' (' . $nama . ')'
            ]);

            DB::commit();

            return redirect()->route('permintaan-barang.index')
                ->with('success', 'Permintaan berhasil dibatalkan');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // ==================== ADMIN METHODS ====================

    public function adminIndex(Request $request)
    {
        $query = PermintaanBarang::with(['user', 'item.category', 'item.unit', 'ruangan', 'verifiedBy'])
            ->latest('tanggal_permintaan');

        if ($request->filled('tipe')) {
            if ($request->tipe === 'barang_baru') {
                $query->tipeBarangBaru();
            } elseif ($request->tipe === 'restok') {
                $query->tipeRestok();
            }
        }

        if ($request->filled('status')) {
            $query->status($request->status);
        }

        if ($request->filled('prioritas')) {
            $query->prioritas($request->prioritas);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('kode_permintaan', 'like', "%{$search}%")
                  ->orWhere('nama_barang', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('item', function($q2) use ($search) {
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
            'total' => PermintaanBarang::count(),
            'pending' => PermintaanBarang::pending()->count(),
            'diproses' => PermintaanBarang::status('diproses')->count(),
            'disetujui' => PermintaanBarang::disetujui()->count(),
            'ditolak' => PermintaanBarang::ditolak()->count(),
            'revisi' => PermintaanBarang::revisi()->count(),
            'tersedia' => PermintaanBarang::tersedia()->count(),
        ];

        return view('permintaan-barang.admin.index', compact('permintaan', 'stats'));
    }

    public function adminShow($id)
    {
        $permintaan = PermintaanBarang::with(['user', 'item.category', 'item.unit', 'ruangan', 'verifiedBy', 'createdItem'])
            ->findOrFail($id);

        $categories = Category::all();
        $units = Unit::all();

        return view('permintaan-barang.admin.show', compact('permintaan', 'categories', 'units'));
    }

    public function proses($id)
    {
        $permintaan = PermintaanBarang::pending()->findOrFail($id);

        $permintaan->proses(auth()->id());

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'permintaan_barang_diproses',
            'description' => 'Admin ' . auth()->user()->name . ' memproses permintaan: ' . $permintaan->kode_permintaan
        ]);

        return back()->with('success', 'Permintaan sedang diproses');
    }

    public function approve(Request $request, $id)
    {
        $permintaan = PermintaanBarang::whereIn('status', ['pending', 'diproses'])
            ->findOrFail($id);

        if ($permintaan->tipe === PermintaanBarang::TIPE_BARANG_BARU) {
            // Untuk barang baru: perlu data item baru
            $validated = $request->validate([
                'category_id' => 'required|exists:categories,id',
                'unit_id' => 'required|exists:units,id',
                'code' => 'required|string|max:50|unique:items,code',
                'price' => 'required|numeric|min:0',
                'perolehan' => 'required|string|max:255',
                'minimum_stock' => 'required|integer|min:0',
                'catatan_admin' => 'nullable|string|max:1000',
            ], [
                'category_id.required' => 'Kategori barang wajib dipilih',
                'unit_id.required' => 'Satuan wajib dipilih',
                'code.required' => 'Kode barang wajib diisi',
                'code.unique' => 'Kode barang sudah digunakan',
                'price.required' => 'Harga satuan wajib diisi',
                'perolehan.required' => 'Perolehan wajib dipilih',
                'minimum_stock.required' => 'Stok minimum wajib diisi',
            ]);

            DB::beginTransaction();
            try {
                $permintaan->setujui(auth()->id(), $request->catatan_admin);

                $item = $permintaan->buatDataBarang([
                    'code' => $validated['code'],
                    'category_id' => $validated['category_id'],
                    'unit_id' => $validated['unit_id'],
                    'price' => $validated['price'],
                    'perolehan' => $validated['perolehan'],
                    'minimum_stock' => $validated['minimum_stock'],
                ]);

                Log::create([
                    'user_id' => auth()->id(),
                    'action' => 'permintaan_barang_baru_approved',
                    'description' => 'Admin ' . auth()->user()->name . ' menyetujui permintaan barang baru: ' . $permintaan->kode_permintaan . '. Data barang dibuat: ' . ($item ? $item->code : 'gagal')
                ]);

                DB::commit();

                return redirect()->route('permintaan-barang.admin.index')
                    ->with('success', 'Permintaan disetujui dan data barang berhasil dibuat dengan kode: ' . ($item ? $item->code : '-'));

            } catch (\Exception $e) {
                DB::rollBack();
                return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
            }

        } else {
            // Untuk restok: cukup approve saja
            $request->validate([
                'catatan_admin' => 'nullable|string|max:1000',
            ]);

            DB::beginTransaction();
            try {
                $permintaan->setujui(auth()->id(), $request->catatan_admin);

                Log::create([
                    'user_id' => auth()->id(),
                    'action' => 'permintaan_barang_restok_approved',
                    'description' => 'Admin ' . auth()->user()->name . ' menyetujui permintaan restok: ' . $permintaan->kode_permintaan . ' (' . $permintaan->nama_barang . ')'
                ]);

                DB::commit();

                return redirect()->route('permintaan-barang.admin.index')
                    ->with('success', 'Permintaan restok disetujui. Barang akan ditandai tersedia ketika stok masuk.');

            } catch (\Exception $e) {
                DB::rollBack();
                return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
            }
        }
    }

    public function reject(Request $request, $id)
    {
        $permintaan = PermintaanBarang::whereIn('status', ['pending', 'diproses'])
            ->findOrFail($id);

        $request->validate([
            'alasan_penolakan' => 'required|string|max:1000',
        ], [
            'alasan_penolakan.required' => 'Alasan penolakan wajib diisi',
        ]);

        $permintaan->tolak(auth()->id(), $request->alasan_penolakan);

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'permintaan_barang_rejected',
            'description' => 'Admin ' . auth()->user()->name . ' menolak permintaan: ' . $permintaan->kode_permintaan
        ]);

        return redirect()->route('permintaan-barang.admin.index')
            ->with('success', 'Permintaan ditolak');
    }

    public function requestRevision(Request $request, $id)
    {
        $permintaan = PermintaanBarang::whereIn('status', ['pending', 'diproses'])
            ->findOrFail($id);

        $request->validate([
            'permintaan_revisi' => 'required|string|max:1000',
        ], [
            'permintaan_revisi.required' => 'Permintaan revisi wajib diisi',
        ]);

        $permintaan->mintaRevisi(auth()->id(), $request->permintaan_revisi);

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'permintaan_barang_revision_requested',
            'description' => 'Admin ' . auth()->user()->name . ' meminta revisi permintaan: ' . $permintaan->kode_permintaan
        ]);

        return redirect()->route('permintaan-barang.admin.index')
            ->with('success', 'Permintaan revisi telah dikirim ke pengusul');
    }

    // ==================== API ====================

    public function getPendingCount()
    {
        $count = PermintaanBarang::menungguVerifikasi()->count();
        return response()->json(['count' => $count]);
    }

    public function getPopupNotifications()
    {
        $notifications = PermintaanBarang::getPopupNotifications(auth()->id());
        return response()->json([
            'count' => $notifications->count(),
            'notifications' => $notifications->map(function($n) {
                return [
                    'id' => $n->id,
                    'kode_permintaan' => $n->kode_permintaan,
                    'item_name' => $n->item->name ?? $n->nama_barang,
                    'jumlah' => $n->jumlah,
                    'item_stock' => $n->item->stock ?? 0,
                ];
            }),
        ]);
    }

    public function markAsRead(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            PermintaanBarang::where('user_id', auth()->id())
                ->where('status', PermintaanBarang::STATUS_TERSEDIA)
                ->whereNull('notified_at')
                ->update([
                    'status' => PermintaanBarang::STATUS_DIBACA,
                    'notified_at' => now(),
                ]);
        } else {
            PermintaanBarang::where('user_id', auth()->id())
                ->whereIn('id', $ids)
                ->where('status', PermintaanBarang::STATUS_TERSEDIA)
                ->update([
                    'status' => PermintaanBarang::STATUS_DIBACA,
                    'notified_at' => now(),
                ]);
        }

        return response()->json(['success' => true]);
    }

    // API untuk pencarian barang (AJAX dari form)
    public function searchItems(Request $request)
    {
        $search = $request->input('q', '');

        $items = Item::with(['category', 'unit'])
            ->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'code' => $item->code,
                    'stock' => $item->stock,
                    'minimum_stock' => $item->minimum_stock,
                    'category' => $item->category->name ?? '-',
                    'unit' => $item->unit->name ?? '-',
                    'unit_symbol' => $item->unit->symbol ?? '',
                    'is_low_stock' => $item->stock <= $item->minimum_stock,
                ];
            });

        return response()->json($items);
    }
}
