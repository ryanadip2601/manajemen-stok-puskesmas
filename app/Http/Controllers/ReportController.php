<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StockIn;
use App\Models\StockOut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ReportController extends Controller
{
    public function monthly(Request $request)
    {
        $reportType = $request->get('report_type', 'monthly'); // monthly or quarterly
        $month = $request->get('month', now()->month);
        $quarter = $request->get('quarter', ceil(now()->month / 3));
        $year = $request->get('year', now()->year);
        $perolehan = $request->get('perolehan', '');

        // Determine date range based on report type
        if ($reportType === 'quarterly') {
            $startMonth = ($quarter - 1) * 3 + 1;
            $endMonth = $quarter * 3;
            $dateStart = \Carbon\Carbon::create($year, $startMonth, 1)->startOfMonth();
            $dateEnd = \Carbon\Carbon::create($year, $endMonth, 1)->endOfMonth();
        } else {
            $dateStart = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
            $dateEnd = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();
        }

        // Query items with stock movements in the period
        $query = Item::with(['unit', 'category'])
            ->select('items.*')
            ->selectRaw('COALESCE((SELECT SUM(quantity) FROM stock_in WHERE stock_in.item_id = items.id AND stock_in.date BETWEEN ? AND ?), 0) as total_in', [$dateStart, $dateEnd])
            ->selectRaw('COALESCE((SELECT SUM(quantity) FROM stock_out WHERE stock_out.item_id = items.id AND stock_out.date BETWEEN ? AND ?), 0) as total_out', [$dateStart, $dateEnd]);

        // Filter by perolehan if selected
        if ($perolehan) {
            $query->where('perolehan', $perolehan);
        }

        $items = $query->orderBy('name')->get();

        // Calculate summary
        $summary = [
            'total_items' => $items->count(),
            'total_stock_in' => $items->sum('total_in'),
            'total_stock_out' => $items->sum('total_out'),
            'total_value' => $items->sum(fn($i) => $i->stock * ($i->price ?? 0)),
        ];

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = \Carbon\Carbon::create()->month($i)->translatedFormat('F');
        }

        $quarters = [
            1 => 'Triwulan I (Jan - Mar)',
            2 => 'Triwulan II (Apr - Jun)',
            3 => 'Triwulan III (Jul - Sep)',
            4 => 'Triwulan IV (Okt - Des)',
        ];

        $years = range(now()->year - 5, now()->year);
        $perolehanOptions = Item::getPerolehanOptions();

        return view('reports.monthly', compact(
            'items', 'summary', 'month', 'quarter', 'year', 'months', 'quarters', 'years',
            'reportType', 'perolehan', 'perolehanOptions', 'dateStart', 'dateEnd'
        ));
    }

    public function yearly(Request $request)
    {
        $year = $request->get('year', now()->year);

        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $stockIn = StockIn::whereMonth('date', $m)->whereYear('date', $year)->sum('quantity');
            $stockOut = StockOut::whereMonth('date', $m)->whereYear('date', $year)->sum('quantity');
            
            $monthlyData[$m] = [
                'month' => \Carbon\Carbon::create()->month($m)->translatedFormat('F'),
                'stock_in' => $stockIn,
                'stock_out' => $stockOut,
                'net' => $stockIn - $stockOut,
            ];
        }

        $summary = [
            'total_stock_in' => array_sum(array_column($monthlyData, 'stock_in')),
            'total_stock_out' => array_sum(array_column($monthlyData, 'stock_out')),
            'net_change' => array_sum(array_column($monthlyData, 'net')),
        ];

        $topItems = Item::with(['unit', 'category'])
            ->withSum(['stockIns as total_in' => fn($q) => $q->whereYear('date', $year)], 'quantity')
            ->withSum(['stockOuts as total_out' => fn($q) => $q->whereYear('date', $year)], 'quantity')
            ->orderByDesc('total_in')
            ->limit(10)
            ->get();

        $years = range(now()->year - 5, now()->year);

        return view('reports.yearly', compact('monthlyData', 'summary', 'topItems', 'year', 'years'));
    }

    public function exportMonthlyCSV(Request $request)
    {
        $reportType = $request->get('report_type', 'monthly');
        $month = $request->get('month', now()->month);
        $quarter = $request->get('quarter', ceil(now()->month / 3));
        $year = $request->get('year', now()->year);
        $perolehan = $request->get('perolehan', '');

        // Determine date range
        if ($reportType === 'quarterly') {
            $startMonth = ($quarter - 1) * 3 + 1;
            $endMonth = $quarter * 3;
            $dateStart = \Carbon\Carbon::create($year, $startMonth, 1)->startOfMonth();
            $dateEnd = \Carbon\Carbon::create($year, $endMonth, 1)->endOfMonth();
            $periodName = "Triwulan_{$quarter}";
        } else {
            $dateStart = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
            $dateEnd = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();
            $periodName = "Bulan_{$month}";
        }

        $filename = "laporan_{$periodName}_{$year}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($dateStart, $dateEnd, $perolehan, $reportType, $year, $month, $quarter) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Title
            if ($reportType === 'quarterly') {
                $quarterNames = ['I', 'II', 'III', 'IV'];
                fputcsv($file, ["=== LAPORAN TRIWULAN {$quarterNames[$quarter-1]} TAHUN {$year} ==="]);
            } else {
                $monthName = \Carbon\Carbon::create()->month($month)->translatedFormat('F');
                fputcsv($file, ["=== LAPORAN BULANAN {$monthName} {$year} ==="]);
            }
            fputcsv($file, ["Periode: {$dateStart->format('d/m/Y')} - {$dateEnd->format('d/m/Y')}"]);
            fputcsv($file, []);

            // Header
            fputcsv($file, ['No', 'Nama Barang', 'Perolehan', 'Jenis Barang', 'Satuan', 'Harga Satuan', 'Stok', 'Jumlah (Rp)']);

            // Query items
            $query = Item::with(['unit', 'category'])
                ->select('items.*')
                ->selectRaw('COALESCE((SELECT SUM(quantity) FROM stock_in WHERE stock_in.item_id = items.id AND stock_in.date BETWEEN ? AND ?), 0) as total_in', [$dateStart, $dateEnd])
                ->selectRaw('COALESCE((SELECT SUM(quantity) FROM stock_out WHERE stock_out.item_id = items.id AND stock_out.date BETWEEN ? AND ?), 0) as total_out', [$dateStart, $dateEnd]);

            if ($perolehan) {
                $query->where('perolehan', $perolehan);
            }

            $items = $query->orderBy('name')->get();

            $no = 1;
            $totalValue = 0;
            foreach ($items as $item) {
                $jumlah = $item->stock * ($item->price ?? 0);
                $totalValue += $jumlah;
                
                fputcsv($file, [
                    $no++,
                    $item->name,
                    $item->perolehan ?? '-',
                    $item->category->name ?? '-',
                    $item->unit->name ?? '-',
                    number_format($item->price ?? 0, 0, ',', '.'),
                    $item->stock,
                    number_format($jumlah, 0, ',', '.'),
                ]);
            }

            fputcsv($file, []);
            fputcsv($file, ['', '', '', '', '', 'TOTAL:', $items->sum('stock'), number_format($totalValue, 0, ',', '.')]);

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function exportYearlyCSV(Request $request)
    {
        $year = $request->get('year', now()->year);

        $filename = "laporan_tahunan_{$year}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($year) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, ["=== LAPORAN TAHUNAN {$year} ==="]);
            fputcsv($file, []);
            fputcsv($file, ['RINGKASAN PER BULAN']);
            fputcsv($file, ['Bulan', 'Barang Masuk', 'Barang Keluar', 'Selisih']);

            $totalIn = 0;
            $totalOut = 0;

            for ($m = 1; $m <= 12; $m++) {
                $stockIn = StockIn::whereMonth('date', $m)->whereYear('date', $year)->sum('quantity');
                $stockOut = StockOut::whereMonth('date', $m)->whereYear('date', $year)->sum('quantity');
                
                $monthName = \Carbon\Carbon::create()->month($m)->translatedFormat('F');
                
                fputcsv($file, [
                    $monthName,
                    $stockIn,
                    $stockOut,
                    $stockIn - $stockOut,
                ]);

                $totalIn += $stockIn;
                $totalOut += $stockOut;
            }

            fputcsv($file, []);
            fputcsv($file, ['TOTAL', $totalIn, $totalOut, $totalIn - $totalOut]);

            fputcsv($file, []);
            fputcsv($file, []);
            fputcsv($file, ['TOP 10 BARANG MASUK']);
            fputcsv($file, ['No', 'Kode', 'Nama Barang', 'Kategori', 'Total Masuk', 'Total Keluar', 'Stok Saat Ini']);

            $topItems = Item::with(['unit', 'category'])
                ->withSum(['stockIns as total_in' => fn($q) => $q->whereYear('date', $year)], 'quantity')
                ->withSum(['stockOuts as total_out' => fn($q) => $q->whereYear('date', $year)], 'quantity')
                ->orderByDesc('total_in')
                ->limit(10)
                ->get();

            $no = 1;
            foreach ($topItems as $item) {
                fputcsv($file, [
                    $no++,
                    $item->code ?? '-',
                    $item->name,
                    $item->category->name ?? '-',
                    $item->total_in ?? 0,
                    $item->total_out ?? 0,
                    $item->stock,
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
