@extends('layouts.app')

@section('title', 'Laporan Pengambilan Barang')
@section('header', 'Laporan Pengambilan Barang')
@section('breadcrumb', 'Laporan / Pengambilan Barang')

@section('content')
<div class="space-y-6">
    {{-- Filter --}}
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6 transition-colors duration-300">
        <form method="GET" action="{{ route('reports.pengambilan-barang') }}">
            <div class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[180px]">
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Cari</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama pemohon / barang..." class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Status</label>
                    <select name="status" class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Dari Tanggal</label>
                    <input type="date" name="dari_tanggal" value="{{ request('dari_tanggal') }}" class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Sampai Tanggal</label>
                    <input type="date" name="sampai_tanggal" value="{{ request('sampai_tanggal') }}" class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:from-emerald-600 hover:to-teal-700 transition-all shadow-lg">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                    <a href="{{ route('reports.pengambilan-barang') }}" class="px-4 py-3 dark:bg-slate-700 bg-slate-200 dark:text-slate-300 text-slate-700 rounded-xl hover:bg-slate-600 transition-colors">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl p-4 dark:border dark:border-slate-700/50 border border-slate-200 transition-colors">
            <p class="text-sm dark:text-slate-400 text-slate-500">Total</p>
            <p class="text-2xl font-bold text-amber-400 mt-1">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl p-4 dark:border dark:border-slate-700/50 border border-slate-200 transition-colors">
            <p class="text-sm dark:text-slate-400 text-slate-500">Pending</p>
            <p class="text-2xl font-bold text-yellow-400 mt-1">{{ number_format($stats['pending']) }}</p>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl p-4 dark:border dark:border-slate-700/50 border border-slate-200 transition-colors">
            <p class="text-sm dark:text-slate-400 text-slate-500">Disetujui</p>
            <p class="text-2xl font-bold text-blue-400 mt-1">{{ number_format($stats['approved']) }}</p>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl p-4 dark:border dark:border-slate-700/50 border border-slate-200 transition-colors">
            <p class="text-sm dark:text-slate-400 text-slate-500">Selesai</p>
            <p class="text-2xl font-bold text-green-400 mt-1">{{ number_format($stats['completed']) }}</p>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl p-4 dark:border dark:border-slate-700/50 border border-slate-200 transition-colors">
            <p class="text-sm dark:text-slate-400 text-slate-500">Ditolak</p>
            <p class="text-2xl font-bold text-red-400 mt-1">{{ number_format($stats['rejected']) }}</p>
        </div>
    </div>

    {{-- Export --}}
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('reports.pengambilan-barang.export', request()->query()) }}" class="px-5 py-3 bg-gradient-to-r from-emerald-500 to-green-600 text-white rounded-xl font-semibold hover:from-emerald-600 hover:to-green-700 transition-all shadow-lg inline-flex items-center">
            <i class="fas fa-file-csv mr-2"></i>Export CSV
        </a>
        <a href="{{ route('reports.index') }}" class="px-5 py-3 dark:bg-slate-700 bg-slate-200 dark:text-slate-300 text-slate-700 rounded-xl font-semibold hover:bg-slate-600 transition-all inline-flex items-center">
            <i class="fas fa-arrow-left mr-2"></i>Kembali
        </a>
    </div>

    {{-- Table --}}
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden transition-colors duration-300">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="dark:bg-slate-700/30 bg-slate-100">
                    <tr>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">No</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Tanggal</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Pemohon</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Nama Barang</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Jumlah</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Status</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Diproses Oleh</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50 divide-slate-200">
                    @forelse($data as $i => $row)
                    <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-4 dark:text-slate-400 text-slate-600">{{ $data->firstItem() + $i }}</td>
                        <td class="px-4 py-4 dark:text-white text-slate-800">{{ $row->request_date ? $row->request_date->format('d/m/Y') : '-' }}</td>
                        <td class="px-4 py-4 dark:text-white text-slate-800 font-medium">{{ $row->user->name ?? '-' }}</td>
                        <td class="px-4 py-4 dark:text-slate-300 text-slate-700">{{ $row->item->name ?? '-' }}</td>
                        <td class="px-4 py-4 text-center"><span class="px-3 py-1 bg-amber-500/20 text-amber-400 rounded-lg font-semibold">{{ $row->quantity }}</span></td>
                        <td class="px-4 py-4 text-center">
                            @php
                                $statusColors = [
                                    'pending' => 'bg-yellow-500/20 text-yellow-400',
                                    'approved' => 'bg-blue-500/20 text-blue-400',
                                    'completed' => 'bg-green-500/20 text-green-400',
                                    'rejected' => 'bg-red-500/20 text-red-400',
                                    'cancelled' => 'bg-slate-500/20 text-slate-400',
                                ];
                                $color = $statusColors[$row->status] ?? 'bg-slate-500/20 text-slate-400';
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $color }}">{{ ucfirst($row->status) }}</span>
                        </td>
                        <td class="px-4 py-4 dark:text-slate-300 text-slate-700">{{ $row->processedByUser->name ?? '-' }}</td>
                        <td class="px-4 py-4 dark:text-slate-400 text-slate-500 text-sm max-w-[200px] truncate">{{ $row->notes ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center dark:text-slate-500 text-slate-400">
                            <i class="fas fa-inbox text-4xl mb-3 block"></i>
                            Tidak ada data pengambilan barang
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($data->hasPages())
        <div class="px-6 py-4 border-t dark:border-slate-700/50 border-slate-200">
            {{ $data->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
