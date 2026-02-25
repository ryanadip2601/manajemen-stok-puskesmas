<?php

namespace App\Http\Controllers;

use App\Models\PemeliharaanAset;
use App\Models\BarangAset;
use App\Models\Ruangan;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PemeliharaanAsetController extends Controller
{
    public function index(Request $request)
    {
        // Simpan filter ke session jika ada parameter baru
        if ($request->has('search') || $request->has('ruangan') || $request->has('status') || 
            $request->has('jenis') || $request->has('teknisi') || $request->has('tanggal_dari') || $request->has('tanggal_sampai')) {
            session([
                'pemeliharaan_filter' => [
                    'search' => $request->search,
                    'ruangan' => $request->ruangan,
                    'status' => $request->status,
                    'jenis' => $request->jenis,
                    'teknisi' => $request->teknisi,
                    'tanggal_dari' => $request->tanggal_dari,
                    'tanggal_sampai' => $request->tanggal_sampai,
                ]
            ]);
        }

        // Ambil filter dari session
        $filters = session('pemeliharaan_filter', []);
        $search = $request->search ?? ($filters['search'] ?? null);
        $ruangan = $request->ruangan ?? ($filters['ruangan'] ?? null);
        $status = $request->status ?? ($filters['status'] ?? null);
        $jenis = $request->jenis ?? ($filters['jenis'] ?? null);
        $teknisi = $request->teknisi ?? ($filters['teknisi'] ?? null);
        $tanggal_dari = $request->tanggal_dari ?? ($filters['tanggal_dari'] ?? null);
        $tanggal_sampai = $request->tanggal_sampai ?? ($filters['tanggal_sampai'] ?? null);

        $query = PemeliharaanAset::with(['barangAset', 'ruangan', 'createdBy']);

        // Staff hanya bisa lihat pemeliharaan yang dia buat
        if (auth()->user()->isStaff()) {
            $query->where('created_by', auth()->id());
        }

        $query->when($search, function ($q, $search) {
                $q->where(function($query) use ($search) {
                    $query->where('teknisi', 'like', "%{$search}%")
                          ->orWhere('deskripsi_masalah', 'like', "%{$search}%")
                          ->orWhereHas('barangAset', function($q) use ($search) {
                              $q->where('nama_aset', 'like', "%{$search}%")
                                ->orWhere('kode_aset', 'like', "%{$search}%");
                          });
                });
            })
            ->when($ruangan, fn($q, $r) => $q->where('ruangan_id', $r))
            ->when($status, fn($q, $s) => $q->where('status', $s))
            ->when($jenis, fn($q, $j) => $q->where('jenis_pemeliharaan', $j))
            ->when($teknisi, fn($q, $t) => $q->where('teknisi', 'like', "%{$t}%"))
            ->when($tanggal_dari, fn($q, $d) => $q->where('tanggal_pemeliharaan', '>=', $d))
            ->when($tanggal_sampai, fn($q, $d) => $q->where('tanggal_pemeliharaan', '<=', $d));

        $pemeliharaan = $query->orderBy('tanggal_pemeliharaan', 'desc')->paginate(15);
        
        $ruanganList = Ruangan::active()->orderBy('nama_ruangan')->get();
        $statusOptions = PemeliharaanAset::getStatusOptions();
        $jenisOptions = PemeliharaanAset::getJenisPemeliharaanOptions();

        // Summary stats - filter by user for staff
        if (auth()->user()->isStaff()) {
            $baseQuery = PemeliharaanAset::where('created_by', auth()->id());
            $summary = [
                'total' => (clone $baseQuery)->count(),
                'menunggu' => (clone $baseQuery)->where('status', 'menunggu')->count(),
                'proses' => (clone $baseQuery)->where('status', 'proses')->count(),
                'selesai' => (clone $baseQuery)->where('status', 'selesai')->count(),
                'rusak' => (clone $baseQuery)->where('status', 'rusak')->count(),
            ];
        } else {
            $summary = [
                'total' => PemeliharaanAset::count(),
                'menunggu' => PemeliharaanAset::where('status', 'menunggu')->count(),
                'proses' => PemeliharaanAset::where('status', 'proses')->count(),
                'selesai' => PemeliharaanAset::where('status', 'selesai')->count(),
                'rusak' => PemeliharaanAset::where('status', 'rusak')->count(),
            ];
        }

        // Pass isStaff flag for view
        $isStaff = auth()->user()->isStaff();
        $activeFilters = compact('search', 'ruangan', 'status', 'jenis', 'teknisi', 'tanggal_dari', 'tanggal_sampai');

        return view('pemeliharaan.index', compact('pemeliharaan', 'ruanganList', 'statusOptions', 'jenisOptions', 'summary', 'isStaff', 'activeFilters'));
    }

    public function clearFilter()
    {
        session()->forget('pemeliharaan_filter');
        $isStaff = auth()->user()->isStaff();
        return redirect()->route($isStaff ? 'staff.pemeliharaan.index' : 'pemeliharaan.index');
    }

    public function create(Request $request)
    {
        $ruanganList = Ruangan::active()->orderBy('nama_ruangan')->get();
        $jenisOptions = PemeliharaanAset::getJenisPemeliharaanOptions();
        
        // Get aset based on ruangan if provided
        $asetList = collect();
        if ($request->ruangan_id) {
            $asetList = BarangAset::where('ruangan_id', $request->ruangan_id)
                ->orderBy('nama_aset')
                ->get();
        }

        // Pre-select aset if provided
        $selectedAset = null;
        if ($request->aset_id) {
            $selectedAset = BarangAset::with('ruangan')->find($request->aset_id);
            if ($selectedAset) {
                $asetList = BarangAset::where('ruangan_id', $selectedAset->ruangan_id)
                    ->orderBy('nama_aset')
                    ->get();
            }
        }

        return view('pemeliharaan.create', compact('ruanganList', 'jenisOptions', 'asetList', 'selectedAset'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'barang_aset_id' => 'required|exists:barang_aset,id',
            'ruangan_id' => 'required|exists:ruangan,id',
            'tanggal_pemeliharaan' => 'required|date',
            'jenis_pemeliharaan' => 'required|in:rutinitas,perbaikan,penggantian',
            'deskripsi_masalah' => 'required|string',
            'tindakan_perbaikan' => 'nullable|string',
            'teknisi' => 'required|string|max:100',
            'biaya' => 'nullable|numeric|min:0',
            'lampiran_foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'catatan' => 'nullable|string',
        ], [
            'barang_aset_id.required' => 'Barang aset harus dipilih',
            'barang_aset_id.exists' => 'Barang aset tidak valid',
            'ruangan_id.required' => 'Ruangan harus dipilih',
            'tanggal_pemeliharaan.required' => 'Tanggal pemeliharaan harus diisi',
            'jenis_pemeliharaan.required' => 'Jenis pemeliharaan harus dipilih',
            'deskripsi_masalah.required' => 'Deskripsi masalah harus diisi',
            'teknisi.required' => 'Nama teknisi harus diisi',
        ]);

        // Handle foto upload to public/uploads
        if ($request->hasFile('lampiran_foto')) {
            $uploadPath = public_path('uploads/pemeliharaan');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            $file = $request->file('lampiran_foto');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadPath, $filename);
            $validated['lampiran_foto'] = 'uploads/pemeliharaan/' . $filename;
        }

        $validated['status'] = 'menunggu';
        $validated['created_by'] = auth()->id();

        $pemeliharaan = PemeliharaanAset::create($validated);

        // Update aset status if needed
        $aset = BarangAset::find($validated['barang_aset_id']);
        if ($aset && $validated['jenis_pemeliharaan'] === 'perbaikan') {
            $aset->update(['butuh_perbaikan' => true]);
        }

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'pemeliharaan_created',
            'description' => 'Pemeliharaan untuk aset "' . $aset->nama_aset . '" dibuat oleh teknisi ' . $validated['teknisi']
        ]);

        // Redirect based on user role
        $redirectRoute = auth()->user()->isStaff() ? 'staff.pemeliharaan.index' : 'pemeliharaan.index';
        
        return redirect()->route($redirectRoute)
            ->with('success', 'Laporan pemeliharaan berhasil dibuat');
    }

    public function show($id)
    {
        $pemeliharaan = PemeliharaanAset::with(['barangAset.kategoriAset', 'ruangan', 'createdBy'])
            ->findOrFail($id);

        return view('pemeliharaan.show', compact('pemeliharaan'));
    }

    public function edit($id)
    {
        $pemeliharaan = PemeliharaanAset::with(['barangAset', 'ruangan'])->findOrFail($id);
        $ruanganList = Ruangan::active()->orderBy('nama_ruangan')->get();
        $asetList = BarangAset::where('ruangan_id', $pemeliharaan->ruangan_id)
            ->orderBy('nama_aset')
            ->get();
        $jenisOptions = PemeliharaanAset::getJenisPemeliharaanOptions();
        $statusOptions = PemeliharaanAset::getStatusOptions();

        return view('pemeliharaan.edit', compact('pemeliharaan', 'ruanganList', 'asetList', 'jenisOptions', 'statusOptions'));
    }

    public function update(Request $request, $id)
    {
        $pemeliharaan = PemeliharaanAset::findOrFail($id);

        $validated = $request->validate([
            'barang_aset_id' => 'required|exists:barang_aset,id',
            'ruangan_id' => 'required|exists:ruangan,id',
            'tanggal_pemeliharaan' => 'required|date',
            'jenis_pemeliharaan' => 'required|in:rutinitas,perbaikan,penggantian',
            'deskripsi_masalah' => 'required|string',
            'tindakan_perbaikan' => 'nullable|string',
            'status' => 'required|in:menunggu,proses,selesai,rusak',
            'teknisi' => 'required|string|max:100',
            'biaya' => 'nullable|numeric|min:0',
            'lampiran_foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'tanggal_selesai' => 'nullable|date',
            'catatan' => 'nullable|string',
        ]);

        // Handle foto upload to public/uploads
        if ($request->hasFile('lampiran_foto')) {
            // Delete old photo
            if ($pemeliharaan->lampiran_foto) {
                $oldPath = public_path($pemeliharaan->lampiran_foto);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            $uploadPath = public_path('uploads/pemeliharaan');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            $file = $request->file('lampiran_foto');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadPath, $filename);
            $validated['lampiran_foto'] = 'uploads/pemeliharaan/' . $filename;
        }

        // Auto set tanggal_selesai if status is selesai
        if ($validated['status'] === 'selesai' && !$validated['tanggal_selesai']) {
            $validated['tanggal_selesai'] = now();
        }

        $pemeliharaan->update($validated);

        // Update aset kondisi based on status
        $aset = BarangAset::find($validated['barang_aset_id']);
        if ($aset) {
            if ($validated['status'] === 'selesai') {
                $aset->update(['kondisi' => 'baik', 'butuh_perbaikan' => false]);
            } elseif ($validated['status'] === 'rusak') {
                $aset->update(['kondisi' => 'rusak_berat', 'butuh_perbaikan' => true]);
            }
        }

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'pemeliharaan_updated',
            'description' => 'Pemeliharaan #' . $pemeliharaan->id . ' diupdate, status: ' . $validated['status']
        ]);

        return redirect()->route('pemeliharaan.index')
            ->with('success', 'Laporan pemeliharaan berhasil diupdate');
    }

    public function destroy($id)
    {
        $pemeliharaan = PemeliharaanAset::findOrFail($id);
        
        
        if ($pemeliharaan->lampiran_foto) {
            $fullPath = public_path($pemeliharaan->lampiran_foto);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        $pemeliharaan->delete();

        Log::create([
            'user_id' => auth()->id(),
            'action' => 'pemeliharaan_deleted',
            'description' => 'Pemeliharaan #' . $id . ' dihapus'
        ]);

        return redirect()->route('pemeliharaan.index')
            ->with('success', 'Laporan pemeliharaan berhasil dihapus');
    }

    // Update status via AJAX
    public function updateStatus(Request $request, $id)
    {
        $pemeliharaan = PemeliharaanAset::findOrFail($id);
        $status = $request->status;

        if ($status === 'selesai') {
            $pemeliharaan->selesaikan($request->tindakan_perbaikan);
        } elseif ($status === 'rusak') {
            $pemeliharaan->tandaiRusak();
        } elseif ($status === 'proses') {
            $pemeliharaan->mulaiProses();
        } else {
            $pemeliharaan->update(['status' => $status]);
        }

        return response()->json(['success' => true, 'message' => 'Status berhasil diupdate']);
    }

    // Get aset by ruangan via AJAX
    public function getAsetByRuangan($ruanganId)
    {
        try {
            $aset = BarangAset::where('ruangan_id', $ruanganId)
                ->select('id', 'kode_aset', 'nama_aset', 'kondisi')
                ->orderBy('nama_aset')
                ->get();

            return response()->json($aset);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal memuat data aset: ' . $e->getMessage()], 500);
        }
    }

    // Riwayat per ruangan
    public function riwayatRuangan($ruanganId)
    {
        $ruangan = Ruangan::findOrFail($ruanganId);
        
        $pemeliharaan = PemeliharaanAset::with(['barangAset'])
            ->where('ruangan_id', $ruanganId)
            ->orderBy('tanggal_pemeliharaan', 'desc')
            ->paginate(20);

        return view('pemeliharaan.riwayat-ruangan', compact('ruangan', 'pemeliharaan'));
    }

    // Riwayat per aset
    public function riwayatAset($asetId)
    {
        $aset = BarangAset::with(['kategoriAset', 'ruangan'])->findOrFail($asetId);
        
        $pemeliharaan = PemeliharaanAset::where('barang_aset_id', $asetId)
            ->orderBy('tanggal_pemeliharaan', 'desc')
            ->paginate(20);

        return view('pemeliharaan.riwayat-aset', compact('aset', 'pemeliharaan'));
    }

    // Export to Excel
    public function exportExcel(Request $request)
    {
        $query = PemeliharaanAset::with(['barangAset', 'ruangan'])
            ->when($request->ruangan, fn($q, $r) => $q->where('ruangan_id', $r))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->tanggal_dari, fn($q, $d) => $q->where('tanggal_pemeliharaan', '>=', $d))
            ->when($request->tanggal_sampai, fn($q, $d) => $q->where('tanggal_pemeliharaan', '<=', $d))
            ->orderBy('tanggal_pemeliharaan', 'desc');

        $data = $query->get();
        
        $filename = 'pemeliharaan_aset_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['No', 'Tanggal', 'Kode Aset', 'Nama Aset', 'Ruangan', 'Jenis', 'Deskripsi Masalah', 'Tindakan', 'Status', 'Teknisi', 'Biaya', 'Tanggal Selesai']);
            
            $no = 1;
            foreach ($data as $item) {
                fputcsv($file, [
                    $no++,
                    $item->tanggal_pemeliharaan->format('d/m/Y'),
                    $item->barangAset->kode_aset ?? '-',
                    $item->barangAset->nama_aset ?? '-',
                    $item->ruangan->nama_ruangan ?? '-',
                    $item->jenis_label,
                    $item->deskripsi_masalah,
                    $item->tindakan_perbaikan ?? '-',
                    $item->status_label,
                    $item->teknisi,
                    $item->biaya ?? 0,
                    $item->tanggal_selesai ? $item->tanggal_selesai->format('d/m/Y') : '-',
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
