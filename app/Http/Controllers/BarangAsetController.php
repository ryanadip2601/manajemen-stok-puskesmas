<?php

namespace App\Http\Controllers;

use App\Models\BarangAset;
use App\Models\KategoriAset;
use App\Models\Ruangan;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarangAsetController extends Controller
{
    public function index(Request $request)
    {
        // Jika ada parameter filter baru dari request, simpan ke session
        if ($request->has('search') || $request->has('kategori') || $request->has('ruangan') || 
            $request->has('kondisi') || $request->has('status') || $request->has('tahun_perolehan')) {
            session([
                'barang_aset_filter' => [
                    'search' => $request->search,
                    'kategori' => $request->kategori,
                    'ruangan' => $request->ruangan,
                    'kondisi' => $request->kondisi,
                    'status' => $request->status,
                    'tahun_perolehan' => $request->tahun_perolehan,
                    'butuh_perbaikan' => $request->butuh_perbaikan,
                ]
            ]);
        }

        // Ambil filter dari session (jika ada)
        $filters = session('barang_aset_filter', []);
        
        // Gunakan filter dari request atau session
        $search = $request->search ?? ($filters['search'] ?? null);
        $kategori = $request->kategori ?? ($filters['kategori'] ?? null);
        $ruangan = $request->ruangan ?? ($filters['ruangan'] ?? null);
        $kondisi = $request->kondisi ?? ($filters['kondisi'] ?? null);
        $status = $request->status ?? ($filters['status'] ?? null);
        $tahun_perolehan = $request->tahun_perolehan ?? ($filters['tahun_perolehan'] ?? null);
        $butuh_perbaikan = $request->butuh_perbaikan ?? ($filters['butuh_perbaikan'] ?? null);

        $query = BarangAset::with(['kategoriAset', 'ruangan'])
            ->when($search, function ($q, $search) {
                $q->where(function($query) use ($search) {
                    $query->where('nama_aset', 'like', "%{$search}%")
                          ->orWhere('kode_aset', 'like', "%{$search}%")
                          ->orWhere('merk', 'like', "%{$search}%")
                          ->orWhere('nomor_register', 'like', "%{$search}%");
                });
            })
            ->when($kategori, function ($q, $kategori) {
                $q->where('kategori_aset_id', $kategori);
            })
            ->when($ruangan, function ($q, $ruangan) {
                $q->where('ruangan_id', $ruangan);
            })
            ->when($kondisi, function ($q, $kondisi) {
                $q->where('kondisi', $kondisi);
            })
            ->when($status, function ($q, $status) {
                $q->where('status_aset', $status);
            })
            ->when($tahun_perolehan, function ($q, $tahun) {
                $q->where('tahun_perolehan', $tahun);
            })
            ->when($butuh_perbaikan, function ($q) {
                $q->where('butuh_perbaikan', true);
            });

        $aset = $query->orderBy('nama_aset')->paginate(15);
        
        $kategoriList = KategoriAset::active()->orderBy('nama_kategori')->get();
        $ruanganList = Ruangan::active()->orderBy('nama_ruangan')->get();
        $kondisiOptions = BarangAset::getKondisiOptions();
        $statusOptions = BarangAset::getStatusAsetOptions();

        // Summary stats
        $summary = [
            'total' => BarangAset::count(),
            'baik' => BarangAset::where('kondisi', 'baik')->count(),
            'rusak_ringan' => BarangAset::where('kondisi', 'rusak_ringan')->count(),
            'rusak_berat' => BarangAset::where('kondisi', 'rusak_berat')->count(),
            'butuh_perbaikan' => BarangAset::where('butuh_perbaikan', true)->count(),
            'total_nilai' => BarangAset::sum('harga_perolehan'),
        ];

        // Kirim filter aktif ke view
        $activeFilters = compact('search', 'kategori', 'ruangan', 'kondisi', 'status', 'tahun_perolehan', 'butuh_perbaikan');

        return view('barang-aset.index', compact('aset', 'kategoriList', 'ruanganList', 'kondisiOptions', 'statusOptions', 'summary', 'activeFilters'));
    }

    // Clear filter session
    public function clearFilter()
    {
        session()->forget('barang_aset_filter');
        return redirect()->route('barang-aset.index');
    }

    public function create()
    {
        $kategoriList = KategoriAset::active()->orderBy('nama_kategori')->get();
        $ruanganList = Ruangan::active()->orderBy('nama_ruangan')->get();
        $kondisiOptions = BarangAset::getKondisiOptions();
        $kodeAset = BarangAset::generateKodeAset();

        return view('barang-aset.create', compact('kategoriList', 'ruanganList', 'kondisiOptions', 'kodeAset'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_aset' => 'required|string|max:50|unique:barang_aset,kode_aset',
            'nama_aset' => 'required|string|max:255',
            'kategori_aset_id' => 'required|exists:kategori_aset,id',
            'ruangan_id' => 'required|exists:ruangan,id',
            'kondisi' => 'required|in:baik,rusak_ringan,rusak_berat',
            'tanggal_masuk' => 'required|date',
            'tahun_perolehan' => 'nullable|integer|min:1980',
            'jumlah' => 'required|integer|min:1',
            'harga_perolehan' => 'required|numeric|min:0',
            'nilai_residu' => 'nullable|numeric|min:0',
            'umur_manfaat_override' => 'nullable|integer|min:0',
            'sumber_dana' => 'nullable|string|max:100',
            'nomor_register' => 'nullable|string|max:50',
            'no_sertifikat' => 'nullable|string|max:255',
            'asal_perolehan' => 'nullable|string|max:255',
            'ukuran_konstruksi' => 'nullable|string|max:255',
            'merk' => 'nullable|string|max:100',
            'spesifikasi' => 'nullable|string|max:255',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'keterangan' => 'nullable|string',
        ], [
            'kode_aset.required' => 'Kode aset harus diisi',
            'kode_aset.unique' => 'Kode aset sudah ada',
            'nama_aset.required' => 'Nama aset harus diisi',
            'kategori_aset_id.required' => 'Kategori aset harus dipilih',
            'ruangan_id.required' => 'Ruangan harus dipilih',
            'kondisi.required' => 'Kondisi harus dipilih',
            'tanggal_masuk.required' => 'Tanggal perolehan harus diisi',
            'jumlah.required' => 'Jumlah harus diisi',
            'jumlah.min' => 'Jumlah minimal 1',
            'harga_perolehan.required' => 'Harga perolehan harus diisi',
        ]);

        // Handle foto upload to public/uploads
        if ($request->hasFile('foto')) {
            $uploadPath = public_path('uploads/aset');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            $file = $request->file('foto');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadPath, $filename);
            $validated['foto'] = 'uploads/aset/' . $filename;
        }

        // Auto-set tahun_perolehan jika kosong
        if (empty($validated['tahun_perolehan'])) {
            $validated['tahun_perolehan'] = date('Y', strtotime($validated['tanggal_masuk']));
        }

        $validated['created_by'] = auth()->id();
        $validated['butuh_perbaikan'] = $validated['kondisi'] === 'rusak_berat';
        $validated['status_aset'] = 'aktif';

        $aset = BarangAset::create($validated);

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'barang_aset_created',
            'description' => 'Aset "' . $aset->nama_aset . '" ('. $aset->kode_aset .') ditambahkan ke ruangan ' . $aset->ruangan->nama_ruangan . ' dengan nilai Rp ' . number_format($aset->harga_perolehan, 0, ',', '.')
        ]);

        return redirect()->route('barang-aset.index')
            ->with('success', 'Barang aset berhasil ditambahkan');
    }

    public function show($id)
    {
        $aset = BarangAset::with(['kategoriAset', 'ruangan', 'pemeliharaan' => function($q) {
            $q->orderBy('tanggal_pemeliharaan', 'desc')->limit(10);
        }, 'createdBy', 'penyusutan'])->findOrFail($id);

        return view('barang-aset.show', compact('aset'));
    }

    public function edit($id)
    {
        $aset = BarangAset::with(['kategoriAset'])->findOrFail($id);
        $kategoriList = KategoriAset::active()->orderBy('nama_kategori')->get();
        $ruanganList = Ruangan::active()->orderBy('nama_ruangan')->get();
        $kondisiOptions = BarangAset::getKondisiOptions();

        return view('barang-aset.edit', compact('aset', 'kategoriList', 'ruanganList', 'kondisiOptions'));
    }

    public function update(Request $request, $id)
    {
        $aset = BarangAset::findOrFail($id);

        $validated = $request->validate([
            'kode_aset' => 'required|string|max:50|unique:barang_aset,kode_aset,' . $id,
            'nama_aset' => 'required|string|max:255',
            'kategori_aset_id' => 'required|exists:kategori_aset,id',
            'ruangan_id' => 'required|exists:ruangan,id',
            'kondisi' => 'required|in:baik,rusak_ringan,rusak_berat',
            'tanggal_masuk' => 'required|date',
            'tahun_perolehan' => 'nullable|integer|min:1980',
            'jumlah' => 'required|integer|min:1',
            'harga_perolehan' => 'nullable|numeric|min:0',
            'nilai_residu' => 'nullable|numeric|min:0',
            'umur_manfaat_override' => 'nullable|integer|min:0',
            'sumber_dana' => 'nullable|string|max:100',
            'nomor_register' => 'nullable|string|max:50',
            'no_sertifikat' => 'nullable|string|max:255',
            'asal_perolehan' => 'nullable|string|max:255',
            'ukuran_konstruksi' => 'nullable|string|max:255',
            'merk' => 'nullable|string|max:100',
            'spesifikasi' => 'nullable|string|max:255',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'keterangan' => 'nullable|string',
        ]);

        // Handle foto upload to public/uploads
        if ($request->hasFile('foto')) {
            // Delete old foto
            if ($aset->foto) {
                $oldPath = public_path($aset->foto);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            $uploadPath = public_path('uploads/aset');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            $file = $request->file('foto');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadPath, $filename);
            $validated['foto'] = 'uploads/aset/' . $filename;
        }

        // Update butuh_perbaikan based on kondisi
        $validated['butuh_perbaikan'] = $validated['kondisi'] === 'rusak_berat';

        $aset->update($validated);

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'barang_aset_updated',
            'description' => 'Aset "' . $aset->nama_aset . '" ('. $aset->kode_aset .') diupdate'
        ]);

        return redirect()->route('barang-aset.show', $aset->id)
            ->with('success', 'Barang aset berhasil diupdate');
    }

    public function destroy($id)
    {
        $aset = BarangAset::findOrFail($id);
        
        // Delete foto if exists
        if ($aset->foto) {
            $fullPath = public_path($aset->foto);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        $namaAset = $aset->nama_aset;
        $kodeAset = $aset->kode_aset;
        $aset->delete();

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'barang_aset_deleted',
            'description' => 'Aset "' . $namaAset . '" ('. $kodeAset .') dihapus'
        ]);

        return redirect()->route('barang-aset.index')
            ->with('success', 'Barang aset berhasil dihapus');
    }

    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'aset_ids' => 'required|array|min:1',
            'aset_ids.*' => 'integer|exists:barang_aset,id',
            'bulk_action' => 'required|in:delete,move',
            'target_ruangan_id' => 'nullable|integer|exists:ruangan,id',
        ], [
            'aset_ids.required' => 'Pilih minimal 1 aset terlebih dahulu.',
            'aset_ids.min' => 'Pilih minimal 1 aset terlebih dahulu.',
            'bulk_action.required' => 'Pilih aksi massal terlebih dahulu.',
            'target_ruangan_id.exists' => 'Ruangan tujuan tidak valid.',
        ]);

        $asetList = BarangAset::with('ruangan')
            ->whereIn('id', $validated['aset_ids'])
            ->get();

        if ($asetList->isEmpty()) {
            return back()->with('error', 'Data aset tidak ditemukan.');
        }

        if ($validated['bulk_action'] === 'move') {
            if (empty($validated['target_ruangan_id'])) {
                return back()->with('error', 'Pilih ruangan tujuan terlebih dahulu.');
            }

            $targetRuangan = Ruangan::active()->find($validated['target_ruangan_id']);
            if (!$targetRuangan) {
                return back()->with('error', 'Ruangan tujuan tidak aktif atau tidak ditemukan.');
            }

            $dipindahkan = 0;
            $dilewati = 0;

            foreach ($asetList as $aset) {
                if ((int) $aset->ruangan_id === (int) $targetRuangan->id) {
                    $dilewati++;
                    continue;
                }

                $aset->update(['ruangan_id' => $targetRuangan->id]);
                $dipindahkan++;
            }

            Log::create([
                'user_id' => auth()->id(),
                'action' => 'barang_aset_bulk_moved',
                'description' => "Pindah massal {$dipindahkan} aset ke ruangan {$targetRuangan->nama_ruangan}" . ($dilewati > 0 ? " ({$dilewati} aset dilewati)" : ''),
            ]);

            return back()->with('success', "Berhasil memindahkan {$dipindahkan} aset ke ruangan {$targetRuangan->nama_ruangan}" . ($dilewati > 0 ? ". {$dilewati} aset sudah berada di ruangan tersebut." : '.'));
        }

        $jumlahHapus = 0;

        DB::transaction(function () use ($asetList, &$jumlahHapus) {
            foreach ($asetList as $aset) {
                if ($aset->foto && !str_starts_with($aset->foto, 'http')) {
                    $fullPath = public_path($aset->foto);
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }

                $aset->delete();
                $jumlahHapus++;
            }
        });

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'barang_aset_bulk_deleted',
            'description' => "Hapus massal {$jumlahHapus} aset",
        ]);

        return back()->with('success', "Berhasil menghapus {$jumlahHapus} aset.");
    }

    // Generate kode aset via AJAX
    public function generateKode(Request $request)
    {
        $kode = BarangAset::generateKodeAset($request->kategori_id);
        return response()->json(['kode' => $kode]);
    }

    // Export to Excel/CSV
    public function exportExcel(Request $request)
    {
        $query = BarangAset::with(['kategoriAset', 'ruangan'])
            ->when($request->ruangan, fn($q, $r) => $q->where('ruangan_id', $r))
            ->when($request->kategori, fn($q, $k) => $q->where('kategori_aset_id', $k))
            ->when($request->kondisi, fn($q, $k) => $q->where('kondisi', $k))
            ->orderBy('kode_aset');

        $aset = $query->get();
        
        $filename = 'barang_aset_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($aset) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header
            fputcsv($file, [
                'No', 'Kode Aset', 'Nama Aset', 'Kategori', 'Golongan', 'Ruangan', 
                'Kondisi', 'Jumlah', 'Tahun Perolehan', 'Harga Perolehan', 
                'Umur Manfaat', 'Akum. Penyusutan', 'Nilai Buku', 'Sumber Dana', 'Merk'
            ]);
            
            $no = 1;
            foreach ($aset as $item) {
                $penyusutan = $item->hitungPenyusutan();
                fputcsv($file, [
                    $no++,
                    $item->kode_aset,
                    $item->nama_aset,
                    $item->kategoriAset->nama_kategori ?? '-',
                    $item->kategoriAset->golongan ?? '-',
                    $item->ruangan->nama_ruangan ?? '-',
                    $item->kondisi_label,
                    $item->jumlah,
                    $item->tahun_perolehan ?? '-',
                    $item->harga_perolehan ?? 0,
                    $penyusutan['umur_manfaat'] ?? '-',
                    $penyusutan['akumulasi_penyusutan'] ?? 0,
                    $penyusutan['nilai_buku'] ?? $item->harga_perolehan,
                    $item->sumber_dana ?? '-',
                    $item->merk ?? '-',
                ]);
            }
            
            fclose($file);
        };

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'export_barang_aset',
            'description' => 'Export data barang aset'
        ]);

        return response()->stream($callback, 200, $headers);
    }
}
