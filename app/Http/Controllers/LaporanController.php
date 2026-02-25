<?php

namespace App\Http\Controllers;

use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\StockOutRequest;
use App\Models\PermintaanBarang;
use App\Models\UsulanPenerimaanBarang;
use App\Models\PemeliharaanAset;
use App\Models\Item;
use App\Models\Ruangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

class LaporanController extends Controller
{
    public function index()
    {
        $stats = [
            'barang_masuk' => StockIn::count(),
            'barang_keluar' => StockOut::count(),
            'pengambilan' => StockOutRequest::count(),
            'permintaan_bhp' => PermintaanBarang::count(),
            'usulan_aset' => UsulanPenerimaanBarang::count(),
            'pemeliharaan' => PemeliharaanAset::count(),
        ];

        return view('reports.index', compact('stats'));
    }

    // 1. Laporan Barang Masuk
    public function barangMasuk(Request $request)
    {
        $query = StockIn::with(['item.category', 'item.unit', 'user']);

        if ($request->filled('dari_tanggal')) {
            $query->whereDate('date', '>=', $request->dari_tanggal);
        }
        if ($request->filled('sampai_tanggal')) {
            $query->whereDate('date', '<=', $request->sampai_tanggal);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('item', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }
        if ($request->filled('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        $stats = [
            'total_transaksi' => (clone $query)->count(),
            'total_qty' => (clone $query)->sum('quantity'),
        ];

        $data = $query->latest('date')->paginate(15)->withQueryString();
        $items = Item::orderBy('name')->get();

        return view('reports.barang-masuk', compact('data', 'stats', 'items'));
    }

    public function exportBarangMasuk(Request $request)
    {
        $query = StockIn::with(['item.category', 'item.unit', 'user']);

        if ($request->filled('dari_tanggal')) {
            $query->whereDate('date', '>=', $request->dari_tanggal);
        }
        if ($request->filled('sampai_tanggal')) {
            $query->whereDate('date', '<=', $request->sampai_tanggal);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('item', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }
        if ($request->filled('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        $data = $query->latest('date')->get();

        $csv = "No,Tanggal,Kode Barang,Nama Barang,Kategori,Satuan,Jumlah,Catatan,Petugas\n";
        foreach ($data as $i => $row) {
            $csv .= ($i + 1) . ','
                . $row->date->format('d/m/Y') . ','
                . ($row->item->code ?? '-') . ','
                . '"' . ($row->item->name ?? '-') . '",'
                . '"' . ($row->item->category->name ?? '-') . '",'
                . ($row->item->unit->name ?? '-') . ','
                . $row->quantity . ','
                . '"' . str_replace('"', '""', $row->notes ?? '-') . '",'
                . '"' . ($row->user->name ?? '-') . '"'
                . "\n";
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="laporan-barang-masuk-' . date('Y-m-d') . '.csv"',
        ]);
    }

    // 2. Laporan Barang Keluar
    public function barangKeluar(Request $request)
    {
        $query = StockOut::with(['item.category', 'item.unit', 'user']);

        if ($request->filled('dari_tanggal')) {
            $query->whereDate('date', '>=', $request->dari_tanggal);
        }
        if ($request->filled('sampai_tanggal')) {
            $query->whereDate('date', '<=', $request->sampai_tanggal);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('item', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }
        if ($request->filled('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        $stats = [
            'total_transaksi' => (clone $query)->count(),
            'total_qty' => (clone $query)->sum('quantity'),
        ];

        $data = $query->latest('date')->paginate(15)->withQueryString();
        $items = Item::orderBy('name')->get();

        return view('reports.barang-keluar', compact('data', 'stats', 'items'));
    }

    public function exportBarangKeluar(Request $request)
    {
        $query = StockOut::with(['item.category', 'item.unit', 'user']);

        if ($request->filled('dari_tanggal')) {
            $query->whereDate('date', '>=', $request->dari_tanggal);
        }
        if ($request->filled('sampai_tanggal')) {
            $query->whereDate('date', '<=', $request->sampai_tanggal);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('item', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }
        if ($request->filled('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        $data = $query->latest('date')->get();

        $csv = "No,Tanggal,Kode Barang,Nama Barang,Kategori,Satuan,Jumlah,Catatan,Petugas\n";
        foreach ($data as $i => $row) {
            $csv .= ($i + 1) . ','
                . $row->date->format('d/m/Y') . ','
                . ($row->item->code ?? '-') . ','
                . '"' . ($row->item->name ?? '-') . '",'
                . '"' . ($row->item->category->name ?? '-') . '",'
                . ($row->item->unit->name ?? '-') . ','
                . $row->quantity . ','
                . '"' . str_replace('"', '""', $row->notes ?? '-') . '",'
                . '"' . ($row->user->name ?? '-') . '"'
                . "\n";
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="laporan-barang-keluar-' . date('Y-m-d') . '.csv"',
        ]);
    }

    // 3. Laporan Pengambilan Barang
    public function pengambilanBarang(Request $request)
    {
        $query = StockOutRequest::with(['user', 'item.category', 'item.unit', 'processedByUser']);

        if ($request->filled('dari_tanggal')) {
            $query->whereDate('request_date', '>=', $request->dari_tanggal);
        }
        if ($request->filled('sampai_tanggal')) {
            $query->whereDate('request_date', '<=', $request->sampai_tanggal);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('item', fn($i) => $i->where('name', 'like', "%{$search}%"));
            });
        }

        $stats = [
            'total' => (clone $query)->count(),
            'pending' => StockOutRequest::where('status', 'pending')->count(),
            'approved' => StockOutRequest::where('status', 'approved')->count(),
            'completed' => StockOutRequest::where('status', 'completed')->count(),
            'rejected' => StockOutRequest::where('status', 'rejected')->count(),
        ];

        $data = $query->latest('request_date')->paginate(15)->withQueryString();

        return view('reports.pengambilan-barang', compact('data', 'stats'));
    }

    public function exportPengambilanBarang(Request $request)
    {
        $query = StockOutRequest::with(['user', 'item.category', 'item.unit', 'processedByUser']);

        if ($request->filled('dari_tanggal')) {
            $query->whereDate('request_date', '>=', $request->dari_tanggal);
        }
        if ($request->filled('sampai_tanggal')) {
            $query->whereDate('request_date', '<=', $request->sampai_tanggal);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $data = $query->latest('request_date')->get();

        $csv = "No,Tanggal,Pemohon,Nama Barang,Jumlah,Status,Diproses Oleh,Tanggal Proses,Catatan\n";
        foreach ($data as $i => $row) {
            $csv .= ($i + 1) . ','
                . ($row->request_date ? $row->request_date->format('d/m/Y') : '-') . ','
                . '"' . ($row->user->name ?? '-') . '",'
                . '"' . ($row->item->name ?? '-') . '",'
                . $row->quantity . ','
                . ucfirst($row->status) . ','
                . '"' . ($row->processedByUser->name ?? '-') . '",'
                . ($row->processed_at ? $row->processed_at->format('d/m/Y') : '-') . ','
                . '"' . str_replace('"', '""', $row->notes ?? '-') . '"'
                . "\n";
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="laporan-pengambilan-barang-' . date('Y-m-d') . '.csv"',
        ]);
    }

    // 4. Laporan Permintaan Barang BHP
    public function permintaanBhp(Request $request)
    {
        $query = PermintaanBarang::with(['user', 'item', 'ruangan', 'verifiedBy']);

        if ($request->filled('dari_tanggal')) {
            $query->whereDate('tanggal_permintaan', '>=', $request->dari_tanggal);
        }
        if ($request->filled('sampai_tanggal')) {
            $query->whereDate('tanggal_permintaan', '<=', $request->sampai_tanggal);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('tipe')) {
            $query->where('tipe', $request->tipe);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                  ->orWhere('kode_permintaan', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('item', fn($i) => $i->where('name', 'like', "%{$search}%"));
            });
        }

        $stats = [
            'total' => (clone $query)->count(),
            'pending' => PermintaanBarang::where('status', 'pending')->count(),
            'disetujui' => PermintaanBarang::where('status', 'disetujui')->count(),
            'ditolak' => PermintaanBarang::where('status', 'ditolak')->count(),
            'barang_baru' => PermintaanBarang::where('tipe', 'barang_baru')->count(),
            'restok' => PermintaanBarang::where('tipe', 'restok')->count(),
        ];

        $data = $query->latest('tanggal_permintaan')->paginate(15)->withQueryString();

        return view('reports.permintaan-bhp', compact('data', 'stats'));
    }

    public function exportPermintaanBhp(Request $request)
    {
        $query = PermintaanBarang::with(['user', 'item', 'ruangan', 'verifiedBy']);

        if ($request->filled('dari_tanggal')) {
            $query->whereDate('tanggal_permintaan', '>=', $request->dari_tanggal);
        }
        if ($request->filled('sampai_tanggal')) {
            $query->whereDate('tanggal_permintaan', '<=', $request->sampai_tanggal);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('tipe')) {
            $query->where('tipe', $request->tipe);
        }

        $data = $query->latest('tanggal_permintaan')->get();

        $csv = "No,Kode,Tanggal,Pemohon,Tipe,Nama Barang,Jumlah,Prioritas,Status,Diverifikasi Oleh\n";
        foreach ($data as $i => $row) {
            $namaBarang = $row->tipe === 'restok' ? ($row->item->name ?? '-') : ($row->nama_barang ?? '-');
            $csv .= ($i + 1) . ','
                . ($row->kode_permintaan ?? '-') . ','
                . ($row->tanggal_permintaan ? $row->tanggal_permintaan->format('d/m/Y') : '-') . ','
                . '"' . ($row->user->name ?? '-') . '",'
                . ($row->tipe === 'barang_baru' ? 'Barang Baru' : 'Restok') . ','
                . '"' . $namaBarang . '",'
                . ($row->jumlah ?? '-') . ','
                . ucfirst($row->prioritas ?? '-') . ','
                . ucfirst($row->status) . ','
                . '"' . ($row->verifiedBy->name ?? '-') . '"'
                . "\n";
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="laporan-permintaan-bhp-' . date('Y-m-d') . '.csv"',
        ]);
    }

    // 5. Laporan Usulan Permintaan Barang Aset
    public function usulanAset(Request $request)
    {
        $query = UsulanPenerimaanBarang::with(['user', 'ruangan', 'verifiedBy']);

        if ($request->filled('dari_tanggal')) {
            $query->whereDate('tanggal_usulan', '>=', $request->dari_tanggal);
        }
        if ($request->filled('sampai_tanggal')) {
            $query->whereDate('tanggal_usulan', '<=', $request->sampai_tanggal);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('prioritas')) {
            $query->where('prioritas', $request->prioritas);
        }
        if ($request->filled('ruangan')) {
            $query->where('ruangan_id', $request->ruangan);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                  ->orWhere('kode_usulan', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        $stats = [
            'total' => (clone $query)->count(),
            'pending' => UsulanPenerimaanBarang::where('status', 'pending')->count(),
            'diproses' => UsulanPenerimaanBarang::where('status', 'diproses')->count(),
            'disetujui' => UsulanPenerimaanBarang::where('status', 'disetujui')->count(),
            'ditolak' => UsulanPenerimaanBarang::where('status', 'ditolak')->count(),
            'revisi' => UsulanPenerimaanBarang::where('status', 'revisi')->count(),
        ];

        $data = $query->latest('tanggal_usulan')->paginate(15)->withQueryString();
        $ruanganList = Ruangan::orderBy('nama_ruangan')->get();

        return view('reports.usulan-aset', compact('data', 'stats', 'ruanganList'));
    }

    public function exportUsulanAset(Request $request)
    {
        $query = UsulanPenerimaanBarang::with(['user', 'ruangan', 'verifiedBy']);

        if ($request->filled('dari_tanggal')) {
            $query->whereDate('tanggal_usulan', '>=', $request->dari_tanggal);
        }
        if ($request->filled('sampai_tanggal')) {
            $query->whereDate('tanggal_usulan', '<=', $request->sampai_tanggal);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('ruangan')) {
            $query->where('ruangan_id', $request->ruangan);
        }

        $data = $query->latest('tanggal_usulan')->get();

        $csv = "No,Kode,Tanggal,Pengusul,Nama Barang,Merk,Ruangan,Jumlah,Prioritas,Status,Estimasi Harga,Diverifikasi Oleh\n";
        foreach ($data as $i => $row) {
            $csv .= ($i + 1) . ','
                . ($row->kode_usulan ?? '-') . ','
                . ($row->tanggal_usulan ? $row->tanggal_usulan->format('d/m/Y') : '-') . ','
                . '"' . ($row->user->name ?? '-') . '",'
                . '"' . ($row->nama_barang ?? '-') . '",'
                . '"' . ($row->merk ?? '-') . '",'
                . '"' . ($row->ruangan->nama_ruangan ?? '-') . '",'
                . ($row->jumlah ?? '-') . ','
                . ucfirst($row->prioritas ?? '-') . ','
                . ucfirst($row->status) . ','
                . number_format($row->estimasi_harga ?? 0, 0, ',', '.') . ','
                . '"' . ($row->verifiedBy->name ?? '-') . '"'
                . "\n";
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="laporan-usulan-aset-' . date('Y-m-d') . '.csv"',
        ]);
    }

    // 6. Laporan Pemeliharaan Aset
    public function pemeliharaan(Request $request)
    {
        $query = PemeliharaanAset::with(['barangAset', 'ruangan', 'createdBy']);

        if ($request->filled('dari_tanggal')) {
            $query->whereDate('tanggal_pemeliharaan', '>=', $request->dari_tanggal);
        }
        if ($request->filled('sampai_tanggal')) {
            $query->whereDate('tanggal_pemeliharaan', '<=', $request->sampai_tanggal);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('jenis')) {
            $query->where('jenis_pemeliharaan', $request->jenis);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('deskripsi_masalah', 'like', "%{$search}%")
                  ->orWhere('teknisi', 'like', "%{$search}%")
                  ->orWhereHas('barangAset', fn($b) => $b->where('nama_barang', 'like', "%{$search}%"))
                  ->orWhereHas('createdBy', fn($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        $stats = [
            'total' => (clone $query)->count(),
            'menunggu' => PemeliharaanAset::where('status', 'menunggu')->count(),
            'proses' => PemeliharaanAset::where('status', 'proses')->count(),
            'selesai' => PemeliharaanAset::where('status', 'selesai')->count(),
            'total_biaya' => PemeliharaanAset::where('status', 'selesai')->sum('biaya'),
        ];

        $data = $query->latest('tanggal_pemeliharaan')->paginate(15)->withQueryString();

        return view('reports.pemeliharaan', compact('data', 'stats'));
    }

    public function exportPemeliharaan(Request $request)
    {
        $query = PemeliharaanAset::with(['barangAset', 'ruangan', 'createdBy']);

        if ($request->filled('dari_tanggal')) {
            $query->whereDate('tanggal_pemeliharaan', '>=', $request->dari_tanggal);
        }
        if ($request->filled('sampai_tanggal')) {
            $query->whereDate('tanggal_pemeliharaan', '<=', $request->sampai_tanggal);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('jenis')) {
            $query->where('jenis_pemeliharaan', $request->jenis);
        }

        $data = $query->latest('tanggal_pemeliharaan')->get();

        $csv = "No,Tanggal,Barang Aset,Jenis,Deskripsi Masalah,Teknisi,Status,Biaya,Pelapor,Tgl Selesai\n";
        foreach ($data as $i => $row) {
            $csv .= ($i + 1) . ','
                . ($row->tanggal_pemeliharaan ? $row->tanggal_pemeliharaan->format('d/m/Y') : '-') . ','
                . '"' . ($row->barangAset->nama_barang ?? '-') . '",'
                . ucfirst($row->jenis_pemeliharaan ?? '-') . ','
                . '"' . str_replace('"', '""', $row->deskripsi_masalah ?? '-') . '",'
                . '"' . ($row->teknisi ?? '-') . '",'
                . ucfirst($row->status) . ','
                . number_format($row->biaya ?? 0, 0, ',', '.') . ','
                . '"' . ($row->createdBy->name ?? '-') . '",'
                . ($row->tanggal_selesai ? $row->tanggal_selesai->format('d/m/Y') : '-')
                . "\n";
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="laporan-pemeliharaan-' . date('Y-m-d') . '.csv"',
        ]);
    }
}
