<?php

namespace App\Http\Controllers;

use App\Models\BarangAset;
use App\Models\KategoriAset;
use App\Models\PenyusutanAset;
use App\Models\Ruangan;
use App\Models\Log;
use App\Services\PenyusutanService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PenyusutanController extends Controller
{
    protected $penyusutanService;

    public function __construct(PenyusutanService $penyusutanService)
    {
        $this->penyusutanService = $penyusutanService;
    }

    /**
     * Dashboard penyusutan - overview
     */
    public function index(Request $request)
    {
        $tahun = $request->get('tahun', date('Y'));
        
        // Summary keseluruhan
        $summary = PenyusutanAset::getSummaryTahun($tahun);
        
        // Hitung real-time untuk semua aset aktif
        $asetAktif = BarangAset::with(['kategoriAset', 'ruangan'])
            ->aktif()
            ->whereNotNull('harga_perolehan')
            ->where('harga_perolehan', '>', 0)
            ->get();
        
        $realtimeSummary = [
            'total_aset' => $asetAktif->count(),
            'total_nilai_perolehan' => 0,
            'total_akumulasi' => 0,
            'total_nilai_buku' => 0,
            'aset_habis_umur' => 0,
        ];
        
        foreach ($asetAktif as $aset) {
            $hasil = $aset->hitungPenyusutan();
            $realtimeSummary['total_nilai_perolehan'] += $hasil['nilai_perolehan'];
            $realtimeSummary['total_akumulasi'] += $hasil['akumulasi_penyusutan'];
            $realtimeSummary['total_nilai_buku'] += $hasil['nilai_buku'];
            if ($hasil['sudah_habis'] ?? false) {
                $realtimeSummary['aset_habis_umur']++;
            }
        }
        
        // Laporan per golongan
        $perGolongan = $this->penyusutanService->laporanPerGolongan($tahun);
        
        // Tahun yang tersedia
        $tahunList = PenyusutanAset::distinct()->pluck('tahun')->sort()->reverse()->values();
        if ($tahunList->isEmpty()) {
            $tahunList = collect([date('Y')]);
        }
        
        return view('penyusutan.index', compact(
            'tahun', 'summary', 'realtimeSummary', 'perGolongan', 'tahunList'
        ));
    }

    /**
     * Hitung penyusutan untuk satu aset
     */
    public function hitungAset(Request $request, $id)
    {
        $aset = BarangAset::with(['kategoriAset', 'ruangan'])->findOrFail($id);
        
        $tanggalHitung = $request->has('tanggal') 
            ? Carbon::parse($request->tanggal)
            : Carbon::now();
        
        $hasil = $this->penyusutanService->hitungPenyusutanAset($aset, $tanggalHitung);
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $hasil
            ]);
        }
        
        return view('penyusutan.detail-aset', compact('aset', 'hasil'));
    }

    /**
     * Proses penyusutan tahunan untuk semua aset
     */
    public function prosesTahunan(Request $request)
    {
        $request->validate([
            'tahun' => 'required|integer|min:2000|max:' . (date('Y') + 1),
        ]);
        
        $tahun = $request->tahun;
        
        $hasil = $this->penyusutanService->prosesPenyusutanTahunan($tahun);
        
        Log::create([
            'user_id' => auth()->id(),
            'action' => 'proses_penyusutan',
            'description' => "Proses penyusutan tahun {$tahun}: {$hasil['aset_diproses']} aset diproses, total penyusutan Rp " . number_format($hasil['total_penyusutan'], 0, ',', '.')
        ]);
        
        return redirect()->route('penyusutan.index', ['tahun' => $tahun])
            ->with('success', "Penyusutan tahun {$tahun} berhasil diproses. {$hasil['aset_diproses']} aset diproses, {$hasil['aset_skip']} aset dilewati.");
    }

    /**
     * Laporan penyusutan per golongan aset
     */
    public function laporanGolongan(Request $request)
    {
        $tahun = $request->get('tahun', date('Y'));
        
        $data = $this->penyusutanService->laporanPerGolongan($tahun);
        
        // Hitung total
        $total = [
            'jumlah_aset' => 0,
            'total_nilai_perolehan' => 0,
            'total_akumulasi' => 0,
            'total_nilai_buku' => 0,
            'total_penyusutan_tahun' => 0,
        ];
        
        foreach ($data as $item) {
            $total['jumlah_aset'] += $item['jumlah_aset'];
            $total['total_nilai_perolehan'] += $item['total_nilai_perolehan'];
            $total['total_akumulasi'] += $item['total_akumulasi'];
            $total['total_nilai_buku'] += $item['total_nilai_buku'];
            $total['total_penyusutan_tahun'] += $item['total_penyusutan_tahun'];
        }
        
        $tahunList = range(date('Y'), 2020);
        
        return view('penyusutan.laporan-golongan', compact('data', 'total', 'tahun', 'tahunList'));
    }

    /**
     * Laporan penyusutan per tahun perolehan
     */
    public function laporanTahunPerolehan(Request $request)
    {
        $tahun = $request->get('tahun', date('Y'));
        
        $data = $this->penyusutanService->laporanPerTahunPerolehan($tahun);
        
        $tahunList = range(date('Y'), 2020);
        
        return view('penyusutan.laporan-tahun-perolehan', compact('data', 'tahun', 'tahunList'));
    }

    /**
     * Laporan detail per aset
     */
    public function laporanDetail(Request $request)
    {
        $tahun = $request->get('tahun', date('Y'));
        $golongan = $request->get('golongan');
        $ruangan = $request->get('ruangan');
        $kategori = $request->get('kategori');
        
        $query = BarangAset::with(['kategoriAset', 'ruangan', 'penyusutan'])
            ->aktif()
            ->whereNotNull('harga_perolehan')
            ->where('harga_perolehan', '>', 0);
        
        if ($golongan) {
            $query->whereHas('kategoriAset', fn($q) => $q->where('golongan', $golongan));
        }
        if ($ruangan) {
            $query->where('ruangan_id', $ruangan);
        }
        if ($kategori) {
            $query->where('kategori_aset_id', $kategori);
        }
        
        $asetList = $query->orderBy('kode_aset')->get();
        
        // Hitung penyusutan untuk setiap aset
        $data = $asetList->map(function ($aset) {
            return $this->penyusutanService->hitungPenyusutanAset($aset);
        });
        
        // Summary
        $summary = [
            'total_aset' => $data->count(),
            'total_nilai_perolehan' => $data->sum('nilai_perolehan'),
            'total_akumulasi' => $data->sum('akumulasi_penyusutan'),
            'total_nilai_buku' => $data->sum('nilai_buku'),
        ];
        
        // Filter options
        $golonganList = KategoriAset::getGolonganOptions();
        $ruanganList = Ruangan::orderBy('nama_ruangan')->get();
        $kategoriList = KategoriAset::active()->orderBy('nama_kategori')->get();
        
        return view('penyusutan.laporan-detail', compact(
            'data', 'summary', 'tahun', 
            'golongan', 'ruangan', 'kategori',
            'golonganList', 'ruanganList', 'kategoriList'
        ));
    }

    /**
     * Kartu penyusutan individu aset
     */
    public function kartuAset($id)
    {
        $aset = BarangAset::with(['kategoriAset', 'ruangan', 'penyusutan' => function($q) {
            $q->orderBy('tahun', 'desc')->orderBy('bulan', 'desc');
        }])->findOrFail($id);
        
        // Hitung penyusutan real-time
        $hitungSekarang = $this->penyusutanService->hitungPenyusutanAset($aset);
        
        // Riwayat penyusutan
        $riwayat = $aset->penyusutan;
        
        return view('penyusutan.kartu-aset', compact('aset', 'hitungSekarang', 'riwayat'));
    }

    /**
     * Export laporan ke CSV
     */
    public function exportCSV(Request $request)
    {
        $tahun = $request->get('tahun', date('Y'));
        
        $data = $this->penyusutanService->exportData($tahun);
        
        $filename = "laporan_penyusutan_{$tahun}.csv";
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        
        $columns = [
            'Kode Aset', 'Nama Aset', 'Kategori', 'Golongan', 'Ruangan',
            'Tahun Perolehan', 'Nilai Perolehan', 'Umur Manfaat',
            'Penyusutan/Tahun', 'Akumulasi Penyusutan', 'Nilai Buku'
        ];
        
        $callback = function() use ($data, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            foreach ($data as $row) {
                fputcsv($file, [
                    $row['kode_aset'],
                    $row['nama_aset'],
                    $row['kategori'],
                    $row['golongan'],
                    $row['ruangan'],
                    $row['tahun_perolehan'],
                    $row['nilai_perolehan'],
                    $row['umur_manfaat'],
                    $row['penyusutan_per_tahun'],
                    $row['akumulasi_penyusutan'],
                    $row['nilai_buku'],
                ]);
            }
            
            fclose($file);
        };
        
        Log::create([
            'user_id' => auth()->id(),
            'action' => 'export_penyusutan',
            'description' => "Export laporan penyusutan tahun {$tahun}"
        ]);
        
        return response()->stream($callback, 200, $headers);
    }

    /**
     * API: Hitung penyusutan aset
     */
    public function apiHitungAset(Request $request, $id)
    {
        $aset = BarangAset::with(['kategoriAset', 'ruangan'])->findOrFail($id);
        
        $tanggalHitung = $request->has('tanggal') 
            ? Carbon::parse($request->tanggal)
            : Carbon::now();
        
        $hasil = $this->penyusutanService->hitungPenyusutanAset($aset, $tanggalHitung);
        
        return response()->json([
            'success' => true,
            'data' => $hasil
        ]);
    }

    /**
     * API: Summary penyusutan
     */
    public function apiSummary(Request $request)
    {
        $tahun = $request->get('tahun', date('Y'));
        
        $summary = PenyusutanAset::getSummaryTahun($tahun);
        $perGolongan = $this->penyusutanService->laporanPerGolongan($tahun);
        
        return response()->json([
            'success' => true,
            'data' => [
                'tahun' => $tahun,
                'summary' => $summary,
                'per_golongan' => $perGolongan,
            ]
        ]);
    }
}
