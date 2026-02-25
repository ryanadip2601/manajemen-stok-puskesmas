@extends('layouts.app')

@section('title', $reportType === 'quarterly' ? 'Laporan Triwulan' : 'Laporan Bulanan')
@section('header', $reportType === 'quarterly' ? 'Laporan Triwulan' : 'Laporan Bulanan')
@section('breadcrumb', 'Laporan / ' . ($reportType === 'quarterly' ? 'Triwulan' : 'Bulanan'))

@section('content')
<div class="space-y-6">
    <!-- Filter Section -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6 transition-colors duration-300">
        <form method="GET" action="{{ route('reports.monthly') }}" class="space-y-4">
            <!-- Report Type Toggle -->
            <div class="flex flex-wrap gap-2 mb-4">
                <button type="button" onclick="setReportType('monthly')" 
                    class="px-4 py-2 rounded-xl font-medium transition {{ $reportType === 'monthly' ? 'bg-emerald-500 text-white' : 'bg-slate-700/50 text-slate-300 hover:bg-slate-700' }}">
                    <i class="fas fa-calendar-alt mr-2"></i>Bulanan
                </button>
                <button type="button" onclick="setReportType('quarterly')" 
                    class="px-4 py-2 rounded-xl font-medium transition {{ $reportType === 'quarterly' ? 'bg-emerald-500 text-white' : 'bg-slate-700/50 text-slate-300 hover:bg-slate-700' }}">
                    <i class="fas fa-calendar-week mr-2"></i>Triwulan
                </button>
            </div>
            
            <input type="hidden" name="report_type" id="report_type" value="{{ $reportType }}">
            
            <div class="flex flex-wrap items-end gap-4">
                <!-- Month (shown only for monthly) -->
                <div id="month-filter" class="flex-1 min-w-[150px] {{ $reportType === 'quarterly' ? 'hidden' : '' }}">
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Bulan</label>
                    <select name="month" class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                        @foreach($months as $num => $name)
                            <option value="{{ $num }}" {{ $month == $num ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Quarter (shown only for quarterly) -->
                <div id="quarter-filter" class="flex-1 min-w-[150px] {{ $reportType === 'monthly' ? 'hidden' : '' }}">
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Triwulan</label>
                    <select name="quarter" class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                        @foreach($quarters as $num => $name)
                            <option value="{{ $num }}" {{ $quarter == $num ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Year -->
                <div class="flex-1 min-w-[120px]">
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Tahun</label>
                    <select name="year" class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Perolehan Filter -->
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Perolehan</label>
                    <select name="perolehan" class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                        <option value="">Semua Perolehan</option>
                        @foreach($perolehanOptions as $key => $value)
                            <option value="{{ $key }}" {{ $perolehan == $key ? 'selected' : '' }}>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:from-emerald-600 hover:to-teal-700 transition-all shadow-lg">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Period Info -->
    <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-4">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center text-blue-300">
                <i class="fas fa-calendar-check mr-3 text-xl"></i>
                <div>
                    <p class="font-medium">
                        @if($reportType === 'quarterly')
                            Laporan Triwulan {{ ['I', 'II', 'III', 'IV'][$quarter-1] }} Tahun {{ $year }}
                        @else
                            Laporan Bulan {{ $months[$month] }} {{ $year }}
                        @endif
                    </p>
                    <p class="text-sm text-blue-400">Periode: {{ $dateStart->format('d/m/Y') }} - {{ $dateEnd->format('d/m/Y') }}</p>
                </div>
            </div>
            @if($perolehan)
                <span class="px-3 py-1 bg-amber-500/20 text-amber-400 rounded-lg text-sm">
                    <i class="fas fa-filter mr-1"></i>{{ $perolehan }}
                </span>
            @endif
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6 transition-colors duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Total Barang</p>
                    <p class="text-3xl font-bold text-emerald-400 mt-1">{{ number_format($summary['total_items']) }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-emerald-500/20 flex items-center justify-center">
                    <i class="fas fa-boxes text-emerald-400 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6 transition-colors duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Total Masuk</p>
                    <p class="text-3xl font-bold text-green-400 mt-1">{{ number_format($summary['total_stock_in']) }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-green-500/20 flex items-center justify-center">
                    <i class="fas fa-arrow-down text-green-400 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6 transition-colors duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Total Keluar</p>
                    <p class="text-3xl font-bold text-red-400 mt-1">{{ number_format($summary['total_stock_out']) }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-red-500/20 flex items-center justify-center">
                    <i class="fas fa-arrow-up text-red-400 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6 transition-colors duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Total Nilai</p>
                    <p class="text-2xl font-bold text-purple-400 mt-1">Rp {{ number_format($summary['total_value'], 0, ',', '.') }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-purple-500/20 flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-purple-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Button -->
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('reports.monthly.export', ['report_type' => $reportType, 'month' => $month, 'quarter' => $quarter, 'year' => $year, 'perolehan' => $perolehan]) }}" 
           class="px-5 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 text-white rounded-xl font-semibold hover:from-cyan-600 hover:to-blue-700 transition-all shadow-lg inline-flex items-center">
            <i class="fas fa-file-csv mr-2"></i>Export CSV
        </a>
    </div>

    <!-- Items Table -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden transition-colors duration-300">
        <div class="p-6 border-b dark:border-slate-700/50 border-slate-200">
            <h3 class="text-lg font-bold dark:text-white text-slate-800 flex items-center">
                <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center mr-3">
                    <i class="fas fa-clipboard-list text-blue-400"></i>
                </div>
                Daftar Barang
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="dark:bg-slate-700/30 bg-slate-100">
                    <tr>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">No</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Nama Barang</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Perolehan</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Jenis Barang</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Satuan</th>
                        <th class="px-4 py-4 text-right text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Harga Satuan</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Stok</th>
                        <th class="px-4 py-4 text-right text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Jumlah (Rp)</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50 divide-slate-200">
                    @php $no = 1; $totalValue = 0; @endphp
                    @forelse($items as $item)
                    @php 
                        $jumlah = $item->stock * ($item->price ?? 0);
                        $totalValue += $jumlah;
                    @endphp
                    <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-4 dark:text-slate-400 text-slate-600">{{ $no++ }}</td>
                        <td class="px-4 py-4 dark:text-white text-slate-800 font-medium">{{ $item->name }}</td>
                        <td class="px-4 py-4">
                            @if($item->perolehan)
                                <span class="px-2 py-1 text-xs bg-blue-500/20 text-blue-400 rounded-lg">{{ $item->perolehan }}</span>
                            @else
                                <span class="text-slate-500">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 dark:text-slate-400 text-slate-600">{{ $item->category->name ?? '-' }}</td>
                        <td class="px-4 py-4 text-center dark:text-slate-400 text-slate-600">{{ $item->unit->name ?? '-' }}</td>
                        <td class="px-4 py-4 text-right dark:text-slate-300 text-slate-700">Rp {{ number_format($item->price ?? 0, 0, ',', '.') }}</td>
                        <td class="px-4 py-4 text-center">
                            <span class="px-3 py-1 bg-slate-600/50 text-white rounded-lg font-semibold">{{ $item->stock }}</span>
                        </td>
                        <td class="px-4 py-4 text-right dark:text-emerald-400 text-emerald-600 font-semibold">Rp {{ number_format($jumlah, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center dark:text-slate-500 text-slate-400">
                            <i class="fas fa-inbox text-4xl mb-3 block"></i>
                            Tidak ada data barang
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($items->count() > 0)
                <tfoot class="dark:bg-slate-700/50 bg-slate-200">
                    <tr>
                        <td colspan="6" class="px-4 py-4 text-right font-bold dark:text-white text-slate-800">TOTAL</td>
                        <td class="px-4 py-4 text-center font-bold dark:text-white text-slate-800">{{ $items->sum('stock') }}</td>
                        <td class="px-4 py-4 text-right font-bold text-emerald-400">Rp {{ number_format($totalValue, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<script>
function setReportType(type) {
    document.getElementById('report_type').value = type;
    
    const monthFilter = document.getElementById('month-filter');
    const quarterFilter = document.getElementById('quarter-filter');
    
    if (type === 'quarterly') {
        monthFilter.classList.add('hidden');
        quarterFilter.classList.remove('hidden');
    } else {
        monthFilter.classList.remove('hidden');
        quarterFilter.classList.add('hidden');
    }
    
    // Update button styles
    document.querySelectorAll('button[onclick^="setReportType"]').forEach(btn => {
        btn.classList.remove('bg-emerald-500', 'text-white');
        btn.classList.add('bg-slate-700/50', 'text-slate-300');
    });
    event.target.classList.remove('bg-slate-700/50', 'text-slate-300');
    event.target.classList.add('bg-emerald-500', 'text-white');
}
</script>
@endsection
