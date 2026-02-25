<?php

namespace App\Http\Controllers;

use App\Models\UsulanPermintaanBarang;
use App\Models\Ruangan;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Item;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsulanPermintaanController extends Controller
{
    // ==================== STAFF METHODS ====================

    public function index(Request $request)
    {
        $query = UsulanPermintaanBarang::with(['ruangan', 'verifiedBy', 'item'])
            ->byUser(auth()->id())
            ->latest('tanggal_permintaan');

        if ($request->filled('status')) {
            $query->status($request->status);
        }

        if ($request->filled('ruangan')) {
            $query->byRuangan($request->ruangan);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('kode_permintaan', 'like', "%{$search}%")
                  ->orWhere('nama_barang', 'like', "%{$search}%");
            });
        }

        $usulan = $query->paginate(15)->withQueryString();
        $ruanganList = Ruangan::active()->orderBy('nama_ruangan')->get();

        $stats = [
            'total' => UsulanPermintaanBarang::byUser(auth()->id())->count(),
            'pending' => UsulanPermintaanBarang::byUser(auth()->id())->pending()->count(),
            'disetujui' => UsulanPermintaanBarang::byUser(auth()->id())->disetujui()->count(),
            'ditolak' => UsulanPermintaanBarang::byUser(auth()->id())->ditolak()->count(),
            'revisi' => UsulanPermintaanBarang::byUser(auth()->id())->revisi()->count(),
        ];

        return view('usulan-permintaan.index', compact('usulan', 'ruanganList', 'stats'));
    }

    public function create()
    {
        $ruanganList = Ruangan::active()->orderBy('nama_ruangan')->get();
        return view('usulan-permintaan.create', compact('ruanganList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ruangan_id' => 'required|exists:ruangan,id',
            'nama_barang' => 'required|string|max:255',
            'jumlah' => 'required|integer|min:1|max:9999',
            'prioritas' => 'required|in:rendah,sedang,tinggi',
            'keterangan_user' => 'nullable|string|max:2000',
            'foto_tipe' => 'nullable|in:upload,url',
            'foto_upload' => 'nullable|required_if:foto_tipe,upload|image|mimes:jpeg,png,jpg,webp|max:5120',
            'foto_url' => 'nullable|required_if:foto_tipe,url|url|max:2048',
        ], [
            'ruangan_id.required' => 'Ruangan tujuan wajib dipilih',
            'nama_barang.required' => 'Nama barang wajib diisi',
            'jumlah.required' => 'Jumlah wajib diisi',
            'jumlah.min' => 'Jumlah minimal 1',
            'prioritas.required' => 'Prioritas wajib dipilih',
            'foto_upload.image' => 'File harus berupa gambar',
            'foto_upload.mimes' => 'Format gambar yang diizinkan: JPG, PNG, WEBP',
            'foto_upload.max' => 'Ukuran maksimal foto adalah 5MB',
            'foto_url.url' => 'Format link URL tidak valid',
        ]);

        // Cek duplikasi pending
        $existing = UsulanPermintaanBarang::where('user_id', auth()->id())
            ->where('ruangan_id', $request->ruangan_id)
            ->where('nama_barang', $request->nama_barang)
            ->whereIn('status', ['pending', 'diproses', 'revisi'])
            ->first();

        if ($existing) {
            return back()->withInput()->with('error', 'Anda sudah memiliki permintaan pending untuk barang yang sama di ruangan ini. Kode: ' . $existing->kode_permintaan);
        }

        DB::beginTransaction();
        try {
            // Handle upload foto
            $fotoBarang = null;
            $tipeFoto = $request->foto_tipe;

            if ($tipeFoto === 'upload' && $request->hasFile('foto_upload')) {
                $file = $request->file('foto_upload');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = 'uploads/usulan-permintaan';
                
                if (!file_exists(public_path($path))) {
                    mkdir(public_path($path), 0755, true);
                }
                
                $file->move(public_path($path), $filename);
                $fotoBarang = $path . '/' . $filename;
            } elseif ($tipeFoto === 'url' && $request->filled('foto_url')) {
                $fotoBarang = $request->foto_url;
            }

            $usulan = UsulanPermintaanBarang::create([
                'user_id' => auth()->id(),
                'ruangan_id' => $validated['ruangan_id'],
                'nama_barang' => $validated['nama_barang'],
                'jumlah' => $validated['jumlah'],
                'prioritas' => $validated['prioritas'],
                'keterangan_user' => $validated['keterangan_user'] ?? null,
                'foto_barang' => $fotoBarang,
                'tipe_foto_barang' => $tipeFoto,
                'tanggal_permintaan' => now(),
                'status' => 'pending',
            ]);

            Log::create([
                'user_id' => auth()->id(),
                'action' => 'usulan_permintaan_created',
                'description' => 'User ' . auth()->user()->name . ' mengajukan permintaan barang habis pakai: ' . $usulan->nama_barang . ' (' . $usulan->kode_permintaan . ')'
            ]);

            DB::commit();

            return redirect()->route('usulan-permintaan-barang.index')
                ->with('success', 'Permintaan barang berhasil diajukan dengan kode: ' . $usulan->kode_permintaan);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $usulan = UsulanPermintaanBarang::with(['user', 'ruangan', 'verifiedBy', 'item'])
            ->findOrFail($id);

        if (!auth()->user()->canManageWarehouse() && $usulan->user_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses ke permintaan ini');
        }

        return view('usulan-permintaan.show-permintaan', compact('usulan'));
    }

    public function edit($id)
    {
        $usulan = UsulanPermintaanBarang::where('user_id', auth()->id())
            ->whereIn('status', ['pending', 'revisi'])
            ->findOrFail($id);

        $ruanganList = Ruangan::active()->orderBy('nama_ruangan')->get();

        return view('usulan-permintaan.edit-permintaan', compact('usulan', 'ruanganList'));
    }

    public function update(Request $request, $id)
    {
        $usulan = UsulanPermintaanBarang::where('user_id', auth()->id())
            ->whereIn('status', ['pending', 'revisi'])
            ->findOrFail($id);

        $validated = $request->validate([
            'ruangan_id' => 'required|exists:ruangan,id',
            'nama_barang' => 'required|string|max:255',
            'jumlah' => 'required|integer|min:1|max:9999',
            'prioritas' => 'required|in:rendah,sedang,tinggi',
            'keterangan_user' => 'nullable|string|max:2000',
            'foto_tipe' => 'nullable|in:upload,url',
            'foto_upload' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'foto_url' => 'nullable|url|max:2048',
        ], [
            'ruangan_id.required' => 'Ruangan tujuan wajib dipilih',
            'nama_barang.required' => 'Nama barang wajib diisi',
            'jumlah.required' => 'Jumlah wajib diisi',
            'jumlah.min' => 'Jumlah minimal 1',
            'prioritas.required' => 'Prioritas wajib dipilih',
            'foto_upload.image' => 'File harus berupa gambar',
            'foto_upload.mimes' => 'Format gambar yang diizinkan: JPG, PNG, WEBP',
            'foto_upload.max' => 'Ukuran maksimal foto adalah 5MB',
            'foto_url.url' => 'Format link URL tidak valid',
        ]);

        DB::beginTransaction();
        try {
            $oldStatus = $usulan->status;
            
            // Handle foto update
            $fotoBarang = $usulan->foto_barang;
            $tipeFoto = $request->foto_tipe ?: $usulan->tipe_foto_barang;

            if ($request->foto_tipe === 'upload' && $request->hasFile('foto_upload')) {
                // Hapus foto lama jika ada dan tipenya upload
                if ($usulan->tipe_foto_barang === 'upload' && $usulan->foto_barang && file_exists(public_path($usulan->foto_barang))) {
                    unlink(public_path($usulan->foto_barang));
                }

                $file = $request->file('foto_upload');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = 'uploads/usulan-permintaan';
                
                if (!file_exists(public_path($path))) {
                    mkdir(public_path($path), 0755, true);
                }
                
                $file->move(public_path($path), $filename);
                $fotoBarang = $path . '/' . $filename;
                $tipeFoto = 'upload';
            } elseif ($request->foto_tipe === 'url' && $request->filled('foto_url')) {
                // Hapus foto lama jika ada dan tipenya upload
                if ($usulan->tipe_foto_barang === 'upload' && $usulan->foto_barang && file_exists(public_path($usulan->foto_barang))) {
                    unlink(public_path($usulan->foto_barang));
                }
                
                $fotoBarang = $request->foto_url;
                $tipeFoto = 'url';
            }

            $usulan->update([
                'ruangan_id' => $validated['ruangan_id'],
                'nama_barang' => $validated['nama_barang'],
                'jumlah' => $validated['jumlah'],
                'prioritas' => $validated['prioritas'],
                'keterangan_user' => $validated['keterangan_user'] ?? null,
                'foto_barang' => $fotoBarang,
                'tipe_foto_barang' => $tipeFoto,
                'status' => 'pending',
                'permintaan_revisi' => null,
            ]);

            Log::create([
                'user_id' => auth()->id(),
                'action' => 'usulan_permintaan_updated',
                'description' => 'User ' . auth()->user()->name . ' memperbarui permintaan: ' . $usulan->kode_permintaan . ($oldStatus === 'revisi' ? ' (dari status revisi)' : '')
            ]);

            DB::commit();

            return redirect()->route('usulan-permintaan-barang.index')
                ->with('success', 'Permintaan berhasil diperbarui');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function cancel($id)
    {
        $usulan = UsulanPermintaanBarang::where('user_id', auth()->id())
            ->whereIn('status', ['pending', 'revisi'])
            ->findOrFail($id);

        DB::beginTransaction();
        try {
            // Hapus foto jika ada
            if ($usulan->tipe_foto_barang === 'upload' && $usulan->foto_barang && file_exists(public_path($usulan->foto_barang))) {
                unlink(public_path($usulan->foto_barang));
            }

            $kode = $usulan->kode_permintaan;
            $usulan->delete();

            Log::create([
                'user_id' => auth()->id(),
                'action' => 'usulan_permintaan_cancelled',
                'description' => 'User ' . auth()->user()->name . ' membatalkan permintaan: ' . $kode
            ]);

            DB::commit();

            return redirect()->route('usulan-permintaan-barang.index')
                ->with('success', 'Permintaan berhasil dibatalkan');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // ==================== ADMIN METHODS ====================

    public function adminIndex(Request $request)
    {
        $query = UsulanPermintaanBarang::with(['user', 'ruangan', 'verifiedBy', 'item'])
            ->latest('tanggal_permintaan');

        if ($request->filled('status')) {
            $query->status($request->status);
        }

        if ($request->filled('ruangan')) {
            $query->byRuangan($request->ruangan);
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
                  });
            });
        }

        if ($request->filled('dari_tanggal')) {
            $query->whereDate('tanggal_permintaan', '>=', $request->dari_tanggal);
        }

        if ($request->filled('sampai_tanggal')) {
            $query->whereDate('tanggal_permintaan', '<=', $request->sampai_tanggal);
        }

        $usulan = $query->paginate(20)->withQueryString();
        $ruanganList = Ruangan::active()->orderBy('nama_ruangan')->get();

        $stats = [
            'total' => UsulanPermintaanBarang::count(),
            'pending' => UsulanPermintaanBarang::pending()->count(),
            'diproses' => UsulanPermintaanBarang::status('diproses')->count(),
            'disetujui' => UsulanPermintaanBarang::disetujui()->count(),
            'ditolak' => UsulanPermintaanBarang::ditolak()->count(),
            'revisi' => UsulanPermintaanBarang::revisi()->count(),
        ];

        return view('usulan-permintaan.admin.index-permintaan', compact('usulan', 'ruanganList', 'stats'));
    }

    public function adminShow($id)
    {
        $usulan = UsulanPermintaanBarang::with(['user', 'ruangan', 'verifiedBy', 'item'])
            ->findOrFail($id);

        $categories = Category::all();
        $units = Unit::all();

        return view('usulan-permintaan.admin.show-permintaan', compact('usulan', 'categories', 'units'));
    }

    public function proses($id)
    {
        $usulan = UsulanPermintaanBarang::pending()->findOrFail($id);

        $usulan->proses(auth()->id());

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'usulan_permintaan_diproses',
            'description' => 'Admin ' . auth()->user()->name . ' memproses permintaan: ' . $usulan->kode_permintaan
        ]);

        return back()->with('success', 'Permintaan sedang diproses');
    }

    public function approve(Request $request, $id)
    {
        $usulan = UsulanPermintaanBarang::whereIn('status', ['pending', 'diproses'])
            ->findOrFail($id);

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
            $usulan->setujui(auth()->id(), $request->catatan_admin);

            $item = $usulan->buatDataBarang([
                'code' => $validated['code'],
                'category_id' => $validated['category_id'],
                'unit_id' => $validated['unit_id'],
                'price' => $validated['price'],
                'perolehan' => $validated['perolehan'],
                'minimum_stock' => $validated['minimum_stock'],
            ]);

            Log::create([
                'user_id' => auth()->id(),
                'action' => 'usulan_permintaan_approved',
                'description' => 'Admin ' . auth()->user()->name . ' menyetujui permintaan: ' . $usulan->kode_permintaan . '. Data barang dibuat: ' . ($item ? $item->code : 'gagal')
            ]);

            DB::commit();

            return redirect()->route('usulan-permintaan-barang.admin.index')
                ->with('success', 'Permintaan disetujui dan data barang berhasil dibuat dengan kode: ' . ($item ? $item->code : '-'));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $usulan = UsulanPermintaanBarang::whereIn('status', ['pending', 'diproses'])
            ->findOrFail($id);

        $request->validate([
            'alasan_penolakan' => 'required|string|max:1000',
        ], [
            'alasan_penolakan.required' => 'Alasan penolakan wajib diisi',
        ]);

        $usulan->tolak(auth()->id(), $request->alasan_penolakan);

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'usulan_permintaan_rejected',
            'description' => 'Admin ' . auth()->user()->name . ' menolak permintaan: ' . $usulan->kode_permintaan
        ]);

        return redirect()->route('usulan-permintaan-barang.admin.index')
            ->with('success', 'Permintaan ditolak');
    }

    public function requestRevision(Request $request, $id)
    {
        $usulan = UsulanPermintaanBarang::whereIn('status', ['pending', 'diproses'])
            ->findOrFail($id);

        $request->validate([
            'permintaan_revisi' => 'required|string|max:1000',
        ], [
            'permintaan_revisi.required' => 'Permintaan revisi wajib diisi',
        ]);

        $usulan->mintaRevisi(auth()->id(), $request->permintaan_revisi);

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'usulan_permintaan_revision_requested',
            'description' => 'Admin ' . auth()->user()->name . ' meminta revisi permintaan: ' . $usulan->kode_permintaan
        ]);

        return redirect()->route('usulan-permintaan-barang.admin.index')
            ->with('success', 'Permintaan revisi telah dikirim ke pengusul');
    }

    public function getPendingCount()
    {
        $count = UsulanPermintaanBarang::menungguVerifikasi()->count();
        return response()->json(['count' => $count]);
    }
}
