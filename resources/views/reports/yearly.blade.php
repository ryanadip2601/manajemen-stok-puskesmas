@extends('layouts.app')

@section('title', 'Laporan Tahunan')
@section('header', 'Laporan Tahunan')
@section('breadcrumb', 'Laporan / Tahunan')

@section('content')
<div class="space-y-6">
    <!-- Filter Section -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6 transition-colors duration-300">
        <form method="GET" action="{{ route('reports.yearly') }}" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Tahun</label>
                <select name="year" class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:from-emerald-600 hover:to-teal-700 transition-all shadow-lg">
                <i class="fas fa-filter mr-2"></i>Filter
            </button>
            <a href="{{ route('reports.yearly.export', ['year' => $year]) }}" 
               class="px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 text-white rounded-xl font-semibold hover:from-cyan-600 hover:to-blue-700 transition-all shadow-lg inline-flex items-center">
                <i class="fas fa-file-csv mr-2"></i>Export CSV
            </a>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6 transition-colors duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Total Barang Masuk</p>
                    <p class="text-3xl font-bold text-green-400 mt-1">{{ number_format($summary['total_stock_in']) }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-green-500/20 flex items-center justify-center">
                    <i class="fas fa-arrow-down text-green-400 text-xl"></i>
                </div>
            </div>
            <p class="text-xs dark:text-slate-500 text-slate-400 mt-2">Sepanjang tahun {{ $year }}</p>
        </div>

        <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6 transition-colors duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Total Barang Keluar</p>
                    <p class="text-3xl font-bold text-red-400 mt-1">{{ number_format($summary['total_stock_out']) }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-red-500/20 flex items-center justify-center">
                    <i class="fas fa-arrow-up text-red-400 text-xl"></i>
                </div>
            </div>
            <p class="text-xs dark:text-slate-500 text-slate-400 mt-2">Sepanjang tahun {{ $year }}</p>
        </div>

        <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6 transition-colors duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Perubahan Bersih</p>
                    <p class="text-3xl font-bold {{ $summary['net_change'] >= 0 ? 'text-blue-400' : 'text-orange-400' }} mt-1">
                        {{ $summary['net_change'] >= 0 ? '+' : '' }}{{ number_format($summary['net_change']) }}
                    </p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-blue-500/20 flex items-center justify-center">
                    <i class="fas fa-chart-line text-blue-400 text-xl"></i>
                </div>
            </div>
            <p class="text-xs dark:text-slate-500 text-slate-400 mt-2">Selisih masuk - keluar</p>
        </div>
    </div>

    <!-- Monthly Chart -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6 transition-colors duration-300">
        <h3 class="text-lg font-bold dark:text-white text-slate-800 mb-6 flex items-center">
            <div class="w-10 h-10 rounded-lg bg-indigo-500/20 flex items-center justify-center mr-3">
                <i class="fas fa-chart-bar text-indigo-400"></i>
            </div>
            Grafik Bulanan - {{ $year }}
        </h3>
        <div class="overflow-x-auto">
            <div class="min-w-[800px]">
                <div class="flex items-end justify-between gap-2 h-64 px-4">
                    @foreach($monthlyData as $m => $data)
                    <div class="flex-1 flex flex-col items-center">
                        <div class="w-full flex gap-1 items-end justify-center h-48">
                            <!-- Bar Masuk -->
                            <div class="w-5 bg-gradient-to-t from-green-600 to-green-400 rounded-t transition-all hover:opacity-80" 
                                 style="height: {{ $summary['total_stock_in'] > 0 ? ($data['stock_in'] / $summary['total_stock_in'] * 180) : 0 }}px"
                                 title="Masuk: {{ $data['stock_in'] }}"></div>
                            <!-- Bar Keluar -->
                            <div class="w-5 bg-gradient-to-t from-red-600 to-red-400 rounded-t transition-all hover:opacity-80" 
                                 style="height: {{ $summary['total_stock_out'] > 0 ? ($data['stock_out'] / $summary['total_stock_out'] * 180) : 0 }}px"
                                 title="Keluar: {{ $data['stock_out'] }}"></div>
                        </div>
                        <p class="text-xs dark:text-slate-400 text-slate-600 mt-2 font-medium">{{ substr($data['month'], 0, 3) }}</p>
                    </div>
                    @endforeach
                </div>
                <div class="flex justify-center gap-6 mt-4 pt-4 border-t dark:border-slate-700/50 border-slate-200">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded bg-gradient-to-t from-green-600 to-green-400"></div>
                        <span class="text-sm dark:text-slate-400 text-slate-600">Barang Masuk</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded bg-gradient-to-t from-red-600 to-red-400"></div>
                        <span class="text-sm dark:text-slate-400 text-slate-600">Barang Keluar</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Data Table -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden transition-colors duration-300">
        <div class="p-6 border-b dark:border-slate-700/50 border-slate-200">
            <h3 class="text-lg font-bold dark:text-white text-slate-800 flex items-center">
                <div class="w-10 h-10 rounded-lg bg-cyan-500/20 flex items-center justify-center mr-3">
                    <i class="fas fa-table text-cyan-400"></i>
                </div>
                Data Per Bulan - {{ $year }}
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="dark:bg-slate-700/30 bg-slate-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Bulan</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Barang Masuk</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Barang Keluar</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Selisih</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50 divide-slate-200">
                    @foreach($monthlyData as $m => $data)
                    <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 dark:text-white text-slate-800 font-medium">{{ $data['month'] }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 bg-green-500/20 text-green-400 rounded-lg font-semibold">{{ number_format($data['stock_in']) }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 bg-red-500/20 text-red-400 rounded-lg font-semibold">{{ number_format($data['stock_out']) }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 {{ $data['net'] >= 0 ? 'bg-blue-500/20 text-blue-400' : 'bg-orange-500/20 text-orange-400' }} rounded-lg font-semibold">
                                {{ $data['net'] >= 0 ? '+' : '' }}{{ number_format($data['net']) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="dark:bg-slate-700/50 bg-slate-100">
                    <tr>
                        <td class="px-6 py-4 dark:text-white text-slate-800 font-bold">TOTAL</td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 bg-green-500/30 text-green-400 rounded-lg font-bold">{{ number_format($summary['total_stock_in']) }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 bg-red-500/30 text-red-400 rounded-lg font-bold">{{ number_format($summary['total_stock_out']) }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 {{ $summary['net_change'] >= 0 ? 'bg-blue-500/30 text-blue-400' : 'bg-orange-500/30 text-orange-400' }} rounded-lg font-bold">
                                {{ $summary['net_change'] >= 0 ? '+' : '' }}{{ number_format($summary['net_change']) }}
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Top Items -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden transition-colors duration-300">
        <div class="p-6 border-b dark:border-slate-700/50 border-slate-200">
            <h3 class="text-lg font-bold dark:text-white text-slate-800 flex items-center">
                <div class="w-10 h-10 rounded-lg bg-yellow-500/20 flex items-center justify-center mr-3">
                    <i class="fas fa-trophy text-yellow-400"></i>
                </div>
                Top 10 Barang Terbanyak - {{ $year }}
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="dark:bg-slate-700/30 bg-slate-100">
                    <tr>
                        <th class="px-6 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase w-16">No</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Kode</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Nama Barang</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Kategori</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Total Masuk</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Total Keluar</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Stok Saat Ini</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50 divide-slate-200">
                    @forelse($topItems as $index => $item)
                    <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 text-center">
                            @if($index < 3)
                                <span class="w-8 h-8 rounded-full {{ $index == 0 ? 'bg-yellow-500' : ($index == 1 ? 'bg-slate-400' : 'bg-amber-600') }} text-white inline-flex items-center justify-center font-bold text-sm">
                                    {{ $index + 1 }}
                                </span>
                            @else
                                <span class="dark:text-slate-400 text-slate-600">{{ $index + 1 }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-mono dark:bg-slate-600/50 bg-slate-200 rounded dark:text-slate-300 text-slate-600">{{ $item->code ?? '-' }}</span>
                        </td>
                        <td class="px-6 py-4 dark:text-white text-slate-800 font-medium">{{ $item->name }}</td>
                        <td class="px-6 py-4 dark:text-slate-400 text-slate-600">{{ $item->category->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 bg-green-500/20 text-green-400 rounded-lg font-semibold">{{ number_format($item->total_in ?? 0) }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 bg-red-500/20 text-red-400 rounded-lg font-semibold">{{ number_format($item->total_out ?? 0) }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 bg-blue-500/20 text-blue-400 rounded-lg font-semibold">{{ number_format($item->stock) }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center dark:text-slate-500 text-slate-400">
                            <i class="fas fa-inbox text-4xl mb-3 block"></i>
                            Tidak ada data
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
