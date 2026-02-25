@extends('layouts.app')

@section('title', 'Barang Masuk')
@section('header', 'Barang Masuk')
@section('breadcrumb', 'Transaksi / Barang Masuk')

@section('content')
<div class="space-y-6">
    <!-- Header & Add Button -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold dark:text-white text-slate-800">Data Barang Masuk</h3>
            <p class="text-sm dark:text-slate-400 text-slate-600">Riwayat transaksi barang masuk</p>
        </div>
        <a href="{{ route('stock-in.create') }}" class="px-5 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:from-emerald-600 hover:to-teal-700 transition-all shadow-lg inline-flex items-center">
            <i class="fas fa-plus mr-2"></i>Tambah Barang Masuk
        </a>
    </div>

    <!-- Filter Section -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6 transition-colors duration-300">
        <form method="GET" action="{{ route('stock-in.index') }}" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Pilih Barang</label>
                <select 
                    name="item_id" 
                    class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
                >
                    <option value="">Semua Barang</option>
                    @foreach($items as $item)
                        <option value="{{ $item->id }}" @selected(($activeFilters['item_id'] ?? '') == $item->id)>
                            {{ $item->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Tanggal Mulai</label>
                <input 
                    type="date" 
                    name="start_date" 
                    value="{{ $activeFilters['start_date'] ?? '' }}"
                    class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
                >
            </div>
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Tanggal Akhir</label>
                <input 
                    type="date" 
                    name="end_date" 
                    value="{{ $activeFilters['end_date'] ?? '' }}"
                    class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
                >
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-5 py-3 bg-gradient-to-r from-slate-600 to-slate-700 text-white rounded-xl font-semibold hover:from-slate-700 hover:to-slate-800 transition-all shadow-lg">
                    <i class="fas fa-search mr-2"></i>Filter
                </button>
                <a href="{{ route('stock-in.clear-filter') }}" class="px-4 py-3 bg-slate-500/50 dark:text-slate-300 text-slate-600 rounded-xl hover:bg-slate-500/70 transition" title="Reset Filter">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Stock In Table -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden transition-colors duration-300">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="dark:bg-slate-700/30 bg-slate-100">
                    <tr>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">No</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Tanggal</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Nama Barang</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Kategori</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Jumlah</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">User</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Catatan</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50 divide-slate-200">
                    @php $no = ($stockIns->currentPage() - 1) * $stockIns->perPage() + 1; @endphp
                    @forelse($stockIns as $stockIn)
                        <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-4 dark:text-slate-400 text-slate-600">{{ $no++ }}</td>
                            <td class="px-4 py-4">
                                <span class="dark:text-slate-300 text-slate-700">{{ $stockIn->date->format('d/m/Y') }}</span>
                            </td>
                            <td class="px-4 py-4">
                                <p class="dark:text-white text-slate-800 font-medium">{{ $stockIn->item->name }}</p>
                                <p class="text-xs dark:text-slate-500 text-slate-400 font-mono">{{ $stockIn->item->code }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <span class="px-2 py-1 text-xs bg-blue-500/20 text-blue-400 rounded-lg">{{ $stockIn->item->category->name ?? '-' }}</span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 rounded-lg font-semibold">
                                    +{{ $stockIn->quantity }} {{ $stockIn->item->unit->symbol ?? '' }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 flex items-center justify-center text-white text-xs font-bold">
                                        {{ strtoupper(substr($stockIn->user->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <span class="dark:text-slate-400 text-slate-600 text-sm">{{ $stockIn->user->name ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 dark:text-slate-400 text-slate-500 text-sm max-w-[200px] truncate">
                                {{ $stockIn->notes ?? '-' }}
                            </td>
                            <td class="px-4 py-4 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('stock-in.edit', $stockIn->id) }}" class="p-2 bg-amber-500/20 text-amber-400 rounded-lg hover:bg-amber-500/30 transition" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('stock-in.destroy', $stockIn->id) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus data ini? Stok barang akan dikurangi.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center dark:text-slate-500 text-slate-400">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 rounded-full bg-emerald-500/10 flex items-center justify-center mb-4">
                                        <i class="fas fa-box-open text-3xl text-emerald-500/50"></i>
                                    </div>
                                    <p class="font-medium dark:text-slate-400 text-slate-500">Belum ada data barang masuk</p>
                                    <p class="text-sm dark:text-slate-500 text-slate-400 mt-1">Klik tombol "Tambah Barang Masuk" untuk menambah data</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($stockIns->hasPages())
        <div class="p-4 border-t dark:border-slate-700/50 border-slate-200">
            {{ $stockIns->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
