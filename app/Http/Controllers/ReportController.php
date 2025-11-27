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
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $stockIn = StockIn::with(['item.unit', 'item.category'])
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->orderBy('date', 'desc')
            ->get();

        $stockOut = StockOut::with(['item.unit', 'item.category'])
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->orderBy('date', 'desc')
            ->get();

        $summary = [
            'total_stock_in' => $stockIn->sum('quantity'),
            'total_stock_out' => $stockOut->sum('quantity'),
            'total_value_in' => $stockIn->sum(fn($s) => $s->quantity * ($s->item->price ?? 0)),
            'total_value_out' => $stockOut->sum(fn($s) => $s->quantity * ($s->item->price ?? 0)),
            'transaction_count_in' => $stockIn->count(),
            'transaction_count_out' => $stockOut->count(),
        ];

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = \Carbon\Carbon::create()->month($i)->translatedFormat('F');
        }

        $years = range(now()->year - 5, now()->year);

        return view('reports.monthly', compact('stockIn', 'stockOut', 'summary', 'month', 'year', 'months', 'years'));
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
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $type = $request->get('type', 'all');

        $filename = "laporan_bulanan_{$year}_{$month}_{$type}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($month, $year, $type) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            if ($type === 'in' || $type === 'all') {
                fputcsv($file, ['=== LAPORAN BARANG MASUK ===']);
                fputcsv($file, ['Tanggal', 'Kode Barang', 'Nama Barang', 'Kategori', 'Jumlah', 'Satuan', 'Keterangan']);
                
                $stockIn = StockIn::with(['item.unit', 'item.category'])
                    ->whereMonth('date', $month)
                    ->whereYear('date', $year)
                    ->orderBy('date', 'desc')
                    ->get();

                foreach ($stockIn as $record) {
                    fputcsv($file, [
                        $record->date->format('d/m/Y'),
                        $record->item->code ?? '-',
                        $record->item->name ?? '-',
                        $record->item->category->name ?? '-',
                        $record->quantity,
                        $record->item->unit->symbol ?? '-',
                        $record->notes ?? '-',
                    ]);
                }

                fputcsv($file, []);
                fputcsv($file, ['Total Barang Masuk:', $stockIn->sum('quantity')]);
                fputcsv($file, []);
            }

            if ($type === 'out' || $type === 'all') {
                fputcsv($file, ['=== LAPORAN BARANG KELUAR ===']);
                fputcsv($file, ['Tanggal', 'Kode Barang', 'Nama Barang', 'Kategori', 'Jumlah', 'Satuan', 'Keterangan']);
                
                $stockOut = StockOut::with(['item.unit', 'item.category'])
                    ->whereMonth('date', $month)
                    ->whereYear('date', $year)
                    ->orderBy('date', 'desc')
                    ->get();

                foreach ($stockOut as $record) {
                    fputcsv($file, [
                        $record->date->format('d/m/Y'),
                        $record->item->code ?? '-',
                        $record->item->name ?? '-',
                        $record->item->category->name ?? '-',
                        $record->quantity,
                        $record->item->unit->symbol ?? '-',
                        $record->notes ?? '-',
                    ]);
                }

                fputcsv($file, []);
                fputcsv($file, ['Total Barang Keluar:', $stockOut->sum('quantity')]);
            }

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
