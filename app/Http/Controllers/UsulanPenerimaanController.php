<?php

namespace App\Http\Controllers;

use App\Models\UsulanPenerimaanBarang;
use App\Models\Ruangan;
use App\Models\KategoriAset;
use App\Models\BarangAset;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class UsulanPenerimaanController extends Controller
{
    // ==================== USER/STAFF METHODS ====================

    public function index(Request $request)
    {
        $query = UsulanPenerimaanBarang::with(['ruangan', 'kategoriAset', 'verifiedBy'])
            ->byUser(auth()->id())
            ->latest('tanggal_usulan');

        if ($request->filled('status')) {
            $query->status($request->status);
        }

        if ($request->filled('ruangan')) {
            $query->byRuangan($request->ruangan);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('kode_usulan', 'like', "%{$search}%")
                  ->orWhere('nama_barang', 'like', "%{$search}%");
            });
        }

        $usulan = $query->paginate(15)->withQueryString();
        $ruanganList = Ruangan::active()->orderBy('nama_ruangan')->get();
        
        $stats = [
            'total' => UsulanPenerimaanBarang::byUser(auth()->id())->count(),
            'pending' => UsulanPenerimaanBarang::byUser(auth()->id())->pending()->count(),
            'disetujui' => UsulanPenerimaanBarang::byUser(auth()->id())->disetujui()->count(),
            'ditolak' => UsulanPenerimaanBarang::byUser(auth()->id())->ditolak()->count(),
            'revisi' => UsulanPenerimaanBarang::byUser(auth()->id())->revisi()->count(),
        ];

        return view('usulan-penerimaan.index', compact('usulan', 'ruanganList', 'stats'));
    }

    public function create()
    {
        $ruanganList = Ruangan::active()->orderBy('nama_ruangan')->get();
        $kategoriList = KategoriAset::active()->orderBy('nama_kategori')->get();
        
        return view('usulan-penerimaan.create', compact('ruanganList', 'kategoriList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ruangan_id' => 'required|exists:ruangan,id',
            'kategori_aset_id' => 'nullable|exists:kategori_aset,id',
            'nama_barang' => 'required|string|max:255',
            'merk' => 'nullable|string|max:100',
            'spesifikasi' => 'nullable|string|max:1000',
            'jumlah' => 'required|integer|min:1|max:9999',
            'kondisi_barang' => 'required|in:baru,bekas_baik,bekas_rusak_ringan',
            'kelengkapan' => 'required|in:lengkap,tidak_lengkap',
            'estimasi_harga' => 'nullable|numeric|min:0',
            'sumber_barang' => 'nullable|string|max:100',
            'tanggal_penerimaan_fisik' => 'nullable|date',
            'prioritas' => 'required|in:rendah,sedang,tinggi',
            'keterangan_user' => 'nullable|string|max:2000',
            'foto_tipe' => 'nullable|in:upload,url',
            'foto_barang' => 'nullable|array|max:5',
            'foto_barang.*' => 'file|image|mimes:jpeg,png,jpg,webp|max:5120',
            'foto_url' => 'nullable|required_if:foto_tipe,url|url|max:2048',
        ], [
            'ruangan_id.required' => 'Ruangan wajib dipilih',
            'nama_barang.required' => 'Nama barang wajib diisi',
            'jumlah.required' => 'Jumlah wajib diisi',
            'jumlah.min' => 'Jumlah minimal 1',
            'kondisi_barang.required' => 'Kondisi barang wajib dipilih',
            'kelengkapan.required' => 'Kelengkapan wajib dipilih',
            'prioritas.required' => 'Prioritas wajib dipilih',
            'foto_barang.max' => 'Maksimal 5 foto yang dapat diunggah',
            'foto_barang.*.file' => 'File harus berupa file yang valid',
            'foto_barang.*.image' => 'File harus berupa gambar',
            'foto_barang.*.mimes' => 'Format gambar harus jpeg, png, jpg, atau webp',
            'foto_barang.*.max' => 'Ukuran gambar maksimal 5MB',
            'foto_url.url' => 'Format link URL tidak valid',
        ]);

        // Cek duplikasi usulan pending di ruangan yang sama
        $existingUsulan = UsulanPenerimaanBarang::where('user_id', auth()->id())
            ->where('ruangan_id', $request->ruangan_id)
            ->where('nama_barang', $request->nama_barang)
            ->whereIn('status', ['pending', 'diproses', 'revisi'])
            ->first();

        if ($existingUsulan) {
            return back()->withInput()->with('error', 'Anda sudah memiliki usulan pending untuk barang yang sama di ruangan ini. Kode usulan: ' . $existingUsulan->kode_usulan);
        }

        DB::beginTransaction();
        try {
            // Handle upload foto
            $fotoList = [];
            $tipeFoto = $request->foto_tipe ?? 'upload';

            if ($tipeFoto === 'upload' && $request->hasFile('foto_barang')) {
                $uploadPath = public_path('uploads/usulan-penerimaan');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                foreach ($request->file('foto_barang') as $foto) {
                    $filename = time() . '_' . uniqid() . '.' . $foto->getClientOriginalExtension();
                    $foto->move($uploadPath, $filename);
                    $fotoList[] = 'uploads/usulan-penerimaan/' . $filename;
                }
            } elseif ($tipeFoto === 'url' && $request->filled('foto_url')) {
                $fotoList[] = $request->foto_url;
            }

            $usulan = UsulanPenerimaanBarang::create([
                'user_id' => auth()->id(),
                'ruangan_id' => $validated['ruangan_id'],
                'kategori_aset_id' => $validated['kategori_aset_id'] ?? null,
                'nama_barang' => $validated['nama_barang'],
                'merk' => $validated['merk'] ?? null,
                'spesifikasi' => $validated['spesifikasi'] ?? null,
                'jumlah' => $validated['jumlah'],
                'kondisi_barang' => $validated['kondisi_barang'],
                'kelengkapan' => $validated['kelengkapan'],
                'estimasi_harga' => $validated['estimasi_harga'] ?? null,
                'sumber_barang' => $validated['sumber_barang'] ?? null,
                'tanggal_penerimaan_fisik' => $validated['tanggal_penerimaan_fisik'] ?? null,
                'prioritas' => $validated['prioritas'],
                'keterangan_user' => $validated['keterangan_user'] ?? null,
                'foto_barang' => $fotoList,
                'tanggal_usulan' => now(),
                'status' => 'pending',
            ]);

            Log::create([
                'user_id' => auth()->id(),
                'action' => 'usulan_penerimaan_created',
                'description' => 'User ' . auth()->user()->name . ' mengajukan usulan penerimaan barang: ' . $usulan->nama_barang . ' (' . $usulan->kode_usulan . ')'
            ]);

            DB::commit();

            return redirect()->route('usulan-penerimaan.index')
                ->with('success', 'Usulan penerimaan barang berhasil diajukan dengan kode: ' . $usulan->kode_usulan);

        } catch (\Exception $e) {
            DB::rollBack();
            // Hapus foto yang sudah diupload jika ada error
            foreach ($fotoList as $foto) {
                $fullPath = public_path($foto);
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $usulan = UsulanPenerimaanBarang::with(['user', 'ruangan', 'kategoriAset', 'verifiedBy', 'barangAset'])
            ->findOrFail($id);

        // Cek akses: user hanya bisa lihat miliknya sendiri, admin bisa lihat semua
        if (!auth()->user()->canManageWarehouse() && $usulan->user_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses ke usulan ini');
        }

        return view('usulan-penerimaan.show', compact('usulan'));
    }

    public function edit($id)
    {
        $usulan = UsulanPenerimaanBarang::where('user_id', auth()->id())
            ->whereIn('status', ['pending', 'revisi'])
            ->findOrFail($id);

        $ruanganList = Ruangan::active()->orderBy('nama_ruangan')->get();
        $kategoriList = KategoriAset::active()->orderBy('nama_kategori')->get();

        return view('usulan-penerimaan.edit', compact('usulan', 'ruanganList', 'kategoriList'));
    }

    public function update(Request $request, $id)
    {
        $usulan = UsulanPenerimaanBarang::where('user_id', auth()->id())
            ->whereIn('status', ['pending', 'revisi'])
            ->findOrFail($id);

        $validated = $request->validate([
            'ruangan_id' => 'required|exists:ruangan,id',
            'kategori_aset_id' => 'nullable|exists:kategori_aset,id',
            'nama_barang' => 'required|string|max:255',
            'merk' => 'nullable|string|max:100',
            'spesifikasi' => 'nullable|string|max:1000',
            'jumlah' => 'required|integer|min:1|max:9999',
            'kondisi_barang' => 'required|in:baru,bekas_baik,bekas_rusak_ringan',
            'kelengkapan' => 'required|in:lengkap,tidak_lengkap',
            'estimasi_harga' => 'nullable|numeric|min:0',
            'sumber_barang' => 'nullable|string|max:100',
            'tanggal_penerimaan_fisik' => 'nullable|date',
            'prioritas' => 'required|in:rendah,sedang,tinggi',
            'keterangan_user' => 'nullable|string|max:2000',
            'foto_tipe' => 'nullable|in:upload,url',
            'foto_barang_baru' => 'nullable|array|max:5',
            'foto_barang_baru.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'foto_url' => 'nullable|url|max:2048',
            'hapus_foto' => 'nullable|array',
        ], [
            'ruangan_id.required' => 'Ruangan wajib dipilih',
            'nama_barang.required' => 'Nama barang wajib diisi',
            'jumlah.required' => 'Jumlah wajib diisi',
            'jumlah.min' => 'Jumlah minimal 1',
            'foto_url.url' => 'Format link URL tidak valid',
        ]);

        DB::beginTransaction();
        try {
            $fotoList = $usulan->foto_barang ?? [];

            // Hapus foto yang diminta
            if ($request->has('hapus_foto')) {
                foreach ($request->hapus_foto as $fotoPath) {
                    // Hanya hapus file fisik jika bukan URL (dimulai dengan uploads/)
                    if (str_starts_with($fotoPath, 'uploads/')) {
                        $fullPath = public_path($fotoPath);
                        if (file_exists($fullPath)) {
                            unlink($fullPath);
                        }
                    }
                    $fotoList = array_filter($fotoList, fn($f) => $f !== $fotoPath);
                }
                $fotoList = array_values($fotoList);
            }

            // Upload foto baru ke public/uploads atau pakai URL
            $tipeFoto = $request->foto_tipe ?? 'upload';

            if ($tipeFoto === 'upload' && $request->hasFile('foto_barang_baru')) {
                $uploadPath = public_path('uploads/usulan-penerimaan');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                foreach ($request->file('foto_barang_baru') as $foto) {
                    if (count($fotoList) >= 5) break;
                    $filename = time() . '_' . uniqid() . '.' . $foto->getClientOriginalExtension();
                    $foto->move($uploadPath, $filename);
                    $fotoList[] = 'uploads/usulan-penerimaan/' . $filename;
                }
            } elseif ($tipeFoto === 'url' && $request->filled('foto_url')) {
                if (count($fotoList) < 5) {
                    $fotoList[] = $request->foto_url;
                }
            }

            // Pastikan minimal ada 1 foto
            if (empty($fotoList)) {
                return back()->withInput()->with('error', 'Minimal 1 foto barang harus ada');
            }

            $oldStatus = $usulan->status;
            
            $usulan->update([
                'ruangan_id' => $validated['ruangan_id'],
                'kategori_aset_id' => $validated['kategori_aset_id'] ?? null,
                'nama_barang' => $validated['nama_barang'],
                'merk' => $validated['merk'] ?? null,
                'spesifikasi' => $validated['spesifikasi'] ?? null,
                'jumlah' => $validated['jumlah'],
                'kondisi_barang' => $validated['kondisi_barang'],
                'kelengkapan' => $validated['kelengkapan'],
                'estimasi_harga' => $validated['estimasi_harga'] ?? null,
                'sumber_barang' => $validated['sumber_barang'] ?? null,
                'tanggal_penerimaan_fisik' => $validated['tanggal_penerimaan_fisik'] ?? null,
                'prioritas' => $validated['prioritas'],
                'keterangan_user' => $validated['keterangan_user'] ?? null,
                'foto_barang' => $fotoList,
                'status' => 'pending', // Reset ke pending setelah revisi
                'permintaan_revisi' => null, // Clear permintaan revisi
            ]);

            Log::create([
                'user_id' => auth()->id(),
                'action' => 'usulan_penerimaan_updated',
                'description' => 'User ' . auth()->user()->name . ' memperbarui usulan: ' . $usulan->kode_usulan . ($oldStatus === 'revisi' ? ' (dari status revisi)' : '')
            ]);

            DB::commit();

            return redirect()->route('usulan-penerimaan.index')
                ->with('success', 'Usulan berhasil diperbarui');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function cancel($id)
    {
        $usulan = UsulanPenerimaanBarang::where('user_id', auth()->id())
            ->whereIn('status', ['pending', 'revisi'])
            ->findOrFail($id);

        DB::beginTransaction();
        try {
            // Hapus foto
            if ($usulan->foto_barang) {
                foreach ($usulan->foto_barang as $foto) {
                    if (str_starts_with($foto, 'uploads/')) {
                        $fullPath = public_path($foto);
                        if (file_exists($fullPath)) {
                            unlink($fullPath);
                        }
                    }
                }
            }

            $kodeUsulan = $usulan->kode_usulan;
            $usulan->delete();

            Log::create([
                'user_id' => auth()->id(),
                'action' => 'usulan_penerimaan_cancelled',
                'description' => 'User ' . auth()->user()->name . ' membatalkan usulan: ' . $kodeUsulan
            ]);

            DB::commit();

            return redirect()->route('usulan-penerimaan.index')
                ->with('success', 'Usulan berhasil dibatalkan');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // ==================== ADMIN METHODS ====================

    public function adminIndex(Request $request)
    {
        $query = UsulanPenerimaanBarang::with(['user', 'ruangan', 'kategoriAset', 'verifiedBy'])
            ->latest('tanggal_usulan');

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
                $q->where('kode_usulan', 'like', "%{$search}%")
                  ->orWhere('nama_barang', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('dari_tanggal')) {
            $query->whereDate('tanggal_usulan', '>=', $request->dari_tanggal);
        }

        if ($request->filled('sampai_tanggal')) {
            $query->whereDate('tanggal_usulan', '<=', $request->sampai_tanggal);
        }

        $usulan = $query->paginate(20)->withQueryString();
        $ruanganList = Ruangan::active()->orderBy('nama_ruangan')->get();

        $stats = [
            'total' => UsulanPenerimaanBarang::count(),
            'pending' => UsulanPenerimaanBarang::pending()->count(),
            'diproses' => UsulanPenerimaanBarang::status('diproses')->count(),
            'disetujui' => UsulanPenerimaanBarang::disetujui()->count(),
            'ditolak' => UsulanPenerimaanBarang::ditolak()->count(),
            'revisi' => UsulanPenerimaanBarang::revisi()->count(),
        ];

        return view('usulan-penerimaan.admin.index', compact('usulan', 'ruanganList', 'stats'));
    }

    public function adminShow($id)
    {
        $usulan = UsulanPenerimaanBarang::with(['user', 'ruangan', 'kategoriAset', 'verifiedBy', 'barangAset'])
            ->findOrFail($id);

        $kategoriList = KategoriAset::active()->orderBy('nama_kategori')->get();

        return view('usulan-penerimaan.admin.show', compact('usulan', 'kategoriList'));
    }

    public function proses($id)
    {
        $usulan = UsulanPenerimaanBarang::pending()->findOrFail($id);

        $usulan->proses(auth()->id());

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'usulan_penerimaan_diproses',
            'description' => 'Admin ' . auth()->user()->name . ' memproses usulan: ' . $usulan->kode_usulan
        ]);

        return back()->with('success', 'Usulan sedang diproses');
    }

    public function approve(Request $request, $id)
    {
        $usulan = UsulanPenerimaanBarang::whereIn('status', ['pending', 'diproses'])
            ->findOrFail($id);

        $request->validate([
            'kategori_aset_id' => 'required|exists:kategori_aset,id',
            'catatan_admin' => 'nullable|string|max:1000',
        ], [
            'kategori_aset_id.required' => 'Kategori aset wajib dipilih untuk membuat inventaris',
        ]);

        DB::beginTransaction();
        try {
            // Update kategori jika diubah admin
            $usulan->update(['kategori_aset_id' => $request->kategori_aset_id]);

            // Setujui usulan
            $usulan->setujui(auth()->id(), $request->catatan_admin);

            // Buat barang aset otomatis
            $barangAset = $usulan->buatBarangAset($request->kategori_aset_id);

            Log::create([
                'user_id' => auth()->id(),
                'action' => 'usulan_penerimaan_approved',
                'description' => 'Admin ' . auth()->user()->name . ' menyetujui usulan: ' . $usulan->kode_usulan . '. Barang aset dibuat: ' . ($barangAset ? $barangAset->kode_aset : 'gagal')
            ]);

            DB::commit();

            return redirect()->route('usulan-penerimaan.admin.index')
                ->with('success', 'Usulan disetujui dan barang aset berhasil dibuat dengan kode: ' . ($barangAset ? $barangAset->kode_aset : '-'));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $usulan = UsulanPenerimaanBarang::whereIn('status', ['pending', 'diproses'])
            ->findOrFail($id);

        $request->validate([
            'alasan_penolakan' => 'required|string|max:1000',
        ], [
            'alasan_penolakan.required' => 'Alasan penolakan wajib diisi',
        ]);

        $usulan->tolak(auth()->id(), $request->alasan_penolakan);

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'usulan_penerimaan_rejected',
            'description' => 'Admin ' . auth()->user()->name . ' menolak usulan: ' . $usulan->kode_usulan . '. Alasan: ' . $request->alasan_penolakan
        ]);

        return redirect()->route('usulan-penerimaan.admin.index')
            ->with('success', 'Usulan ditolak');
    }

    public function requestRevision(Request $request, $id)
    {
        $usulan = UsulanPenerimaanBarang::whereIn('status', ['pending', 'diproses'])
            ->findOrFail($id);

        $request->validate([
            'permintaan_revisi' => 'required|string|max:1000',
        ], [
            'permintaan_revisi.required' => 'Permintaan revisi wajib diisi',
        ]);

        $usulan->mintaRevisi(auth()->id(), $request->permintaan_revisi);

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'usulan_penerimaan_revision_requested',
            'description' => 'Admin ' . auth()->user()->name . ' meminta revisi usulan: ' . $usulan->kode_usulan
        ]);

        return redirect()->route('usulan-penerimaan.admin.index')
            ->with('success', 'Permintaan revisi telah dikirim ke pengusul');
    }

    public function adminDestroy($id)
    {
        $usulan = UsulanPenerimaanBarang::findOrFail($id);

        if ($usulan->foto_barang && is_array($usulan->foto_barang)) {
            foreach ($usulan->foto_barang as $foto) {
                // Hanya hapus jika berupa file fisik
                if (str_starts_with($foto, 'uploads/')) {
                    $path = public_path($foto);
                    if (file_exists($path)) {
                        unlink($path);
                    }
                }
            }
        }

        $usulan->delete();

        return redirect()->route('usulan-penerimaan.admin.index')
            ->with('success', 'Usulan permintaan barang berhasil dihapus');
    }

    // API untuk notifikasi
    public function getPendingCount()
    {
        $count = UsulanPenerimaanBarang::menungguVerifikasi()->count();
        return response()->json(['count' => $count]);
    }

    // Export CSV untuk user (Administrator dan Admin Gudang - data sendiri)
    public function exportUserCSV(Request $request)
    {
        // Hanya admin dan admin_gudang yang bisa export
        if (!auth()->user()->canManageWarehouse()) {
            abort(403, 'Anda tidak memiliki akses untuk export');
        }

        $query = UsulanPenerimaanBarang::with(['ruangan', 'kategoriAset', 'verifiedBy'])
            ->byUser(auth()->id());

        if ($request->filled('status')) {
            $query->status($request->status);
        }

        if ($request->filled('ruangan')) {
            $query->byRuangan($request->ruangan);
        }

        $usulan = $query->orderBy('tanggal_usulan', 'desc')->get();

        $filename = 'usulan_penerimaan_saya_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($usulan) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($file, [
                'Kode Usulan',
                'Tanggal Usulan',
                'Ruangan',
                'Nama Barang',
                'Merk',
                'Jumlah',
                'Kondisi',
                'Kelengkapan',
                'Estimasi Harga',
                'Prioritas',
                'Status',
                'Diverifikasi Oleh',
                'Tanggal Verifikasi',
            ]);

            foreach ($usulan as $item) {
                fputcsv($file, [
                    $item->kode_usulan,
                    $item->tanggal_usulan->format('d/m/Y'),
                    $item->ruangan->nama_ruangan ?? '-',
                    $item->nama_barang,
                    $item->merk ?? '-',
                    $item->jumlah,
                    $item->kondisi_label,
                    $item->kelengkapan_label,
                    $item->estimasi_harga ? 'Rp ' . number_format($item->estimasi_harga, 0, ',', '.') : '-',
                    $item->prioritas_label,
                    $item->status_label,
                    $item->verifiedBy->name ?? '-',
                    $item->verified_at ? $item->verified_at->format('d/m/Y H:i') : '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Export CSV untuk admin (semua data)
    public function exportCSV(Request $request)
    {
        $query = UsulanPenerimaanBarang::with(['user', 'ruangan', 'kategoriAset']);

        if ($request->filled('status')) {
            $query->status($request->status);
        }

        if ($request->filled('dari_tanggal') && $request->filled('sampai_tanggal')) {
            $query->tanggal($request->dari_tanggal, $request->sampai_tanggal);
        }

        $usulan = $query->orderBy('tanggal_usulan', 'desc')->get();

        $filename = 'laporan_usulan_penerimaan_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($usulan) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($file, [
                'Kode Usulan',
                'Tanggal Usulan',
                'Pengusul',
                'Ruangan',
                'Nama Barang',
                'Merk',
                'Jumlah',
                'Kondisi',
                'Kelengkapan',
                'Estimasi Harga',
                'Prioritas',
                'Status',
                'Diverifikasi Oleh',
                'Tanggal Verifikasi',
            ]);

            foreach ($usulan as $item) {
                fputcsv($file, [
                    $item->kode_usulan,
                    $item->tanggal_usulan->format('d/m/Y'),
                    $item->user->name ?? '-',
                    $item->ruangan->nama_ruangan ?? '-',
                    $item->nama_barang,
                    $item->merk ?? '-',
                    $item->jumlah,
                    $item->kondisi_label,
                    $item->kelengkapan_label,
                    $item->estimasi_harga ? 'Rp ' . number_format($item->estimasi_harga, 0, ',', '.') : '-',
                    $item->prioritas_label,
                    $item->status_label,
                    $item->verifiedBy->name ?? '-',
                    $item->verified_at ? $item->verified_at->format('d/m/Y H:i') : '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
