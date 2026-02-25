@extends('layouts.app')

@section('title', 'Detail Barang')
@section('header', 'Detail Barang')
@section('breadcrumb', 'Master Data / Barang / Detail')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold dark:text-white text-slate-800">Detail Barang</h3>
            <p class="text-sm dark:text-slate-400 text-slate-600">Informasi lengkap barang {{ $item->name }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('items.index') }}" class="px-5 py-3 bg-slate-500/50 dark:text-slate-300 text-slate-600 rounded-xl font-semibold hover:bg-slate-500/70 transition-all inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>Kembali
            </a>
            <a href="{{ route('items.edit', $item->id) }}" class="px-5 py-3 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-xl font-semibold hover:from-amber-600 hover:to-orange-700 transition-all shadow-lg inline-flex items-center">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
        </div>
    </div>

    <!-- Info Barang -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Detail Utama -->
        <div class="lg:col-span-2 dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6 transition-colors duration-300">
            <h4 class="text-lg font-semibold dark:text-white text-slate-800 mb-4 flex items-center">
                <i class="fas fa-box text-emerald-400 mr-2"></i>Informasi Barang
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs dark:text-slate-500 text-slate-400 uppercase font-semibold mb-1">Kode Barang</p>
                    <p class="dark:text-white text-slate-800 font-mono font-medium">{{ $item->code }}</p>
                </div>
                <div>
                    <p class="text-xs dark:text-slate-500 text-slate-400 uppercase font-semibold mb-1">Nama Barang</p>
                    <p class="dark:text-white text-slate-800 font-medium">{{ $item->name }}</p>
                </div>
                <div>
                    <p class="text-xs dark:text-slate-500 text-slate-400 uppercase font-semibold mb-1">Kategori</p>
                    <p class="dark:text-slate-300 text-slate-700">{{ $item->category->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs dark:text-slate-500 text-slate-400 uppercase font-semibold mb-1">Satuan</p>
                    <p class="dark:text-slate-300 text-slate-700">{{ $item->unit->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs dark:text-slate-500 text-slate-400 uppercase font-semibold mb-1">Perolehan</p>
                    @if($item->perolehan)
                        <span class="px-2 py-1 text-xs bg-blue-500/20 text-blue-400 rounded-lg">{{ $item->perolehan }}</span>
                    @else
                        <span class="dark:text-slate-500 text-slate-400">-</span>
                    @endif
                </div>
                <div>
                    <p class="text-xs dark:text-slate-500 text-slate-400 uppercase font-semibold mb-1">Harga Satuan</p>
                    <p class="dark:text-white text-slate-800 font-semibold">Rp {{ number_format($item->price ?? 0, 0, ',', '.') }}</p>
                </div>
                @if($item->barcode)
                <div>
                    <p class="text-xs dark:text-slate-500 text-slate-400 uppercase font-semibold mb-1">Barcode</p>
                    <p class="dark:text-slate-300 text-slate-700 font-mono">{{ $item->barcode }}</p>
                </div>
                @endif
                @if($item->description)
                <div class="md:col-span-2">
                    <p class="text-xs dark:text-slate-500 text-slate-400 uppercase font-semibold mb-1">Deskripsi</p>
                    <p class="dark:text-slate-300 text-slate-700">{{ $item->description }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Stok Info -->
        <div class="space-y-6">
            <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6 transition-colors duration-300">
                <h4 class="text-lg font-semibold dark:text-white text-slate-800 mb-4 flex items-center">
                    <i class="fas fa-cubes text-cyan-400 mr-2"></i>Informasi Stok
                </h4>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm dark:text-slate-400 text-slate-600">Stok Saat Ini</span>
                        @if($item->isLowStock())
                            <span class="px-3 py-1 bg-red-500/20 text-red-400 rounded-lg font-bold text-lg font-variant-numeric: tabular-nums">{{ $item->stock }}</span>
                        @else
                            <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 rounded-lg font-bold text-lg" style="font-variant-numeric: tabular-nums">{{ $item->stock }}</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm dark:text-slate-400 text-slate-600">Stok Minimum</span>
                        <span class="dark:text-white text-slate-800 font-semibold" style="font-variant-numeric: tabular-nums">{{ $item->minimum_stock }}</span>
                    </div>
                    @if($item->isLowStock())
                    <div class="p-3 bg-red-500/10 border border-red-500/30 rounded-xl">
                        <p class="text-red-400 text-sm flex items-center">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Stok di bawah minimum!
                        </p>
                    </div>
                    @endif
                </div>
            </div>

            @if($item->photo)
            <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6 transition-colors duration-300">
                <h4 class="text-lg font-semibold dark:text-white text-slate-800 mb-4 flex items-center">
                    <i class="fas fa-image text-purple-400 mr-2"></i>Foto Barang
                </h4>
                <img src="{{ $item->getPhotoUrl() }}" alt="{{ $item->name }}" class="w-full rounded-xl object-cover">
            </div>
            @endif
        </div>
    </div>

    <!-- Riwayat Barang Masuk -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden transition-colors duration-300">
        <div class="p-6 border-b dark:border-slate-700/50 border-slate-200">
            <h4 class="text-lg font-semibold dark:text-white text-slate-800 flex items-center">
                <i class="fas fa-arrow-down text-emerald-400 mr-2"></i>Riwayat Barang Masuk
                <span class="ml-2 px-2 py-0.5 text-xs bg-emerald-500/20 text-emerald-400 rounded-lg">{{ $item->stockIns->count() }}</span>
            </h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="dark:bg-slate-700/30 bg-slate-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Tanggal</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Jumlah</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Oleh</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50 divide-slate-200">
                    @forelse($item->stockIns->sortByDesc('date') as $index => $stockIn)
                    <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3 dark:text-slate-400 text-slate-600">{{ $index + 1 }}</td>
                        <td class="px-4 py-3 dark:text-slate-300 text-slate-700">{{ $stockIn->date ? $stockIn->date->format('d/m/Y') : '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 bg-emerald-500/20 text-emerald-400 rounded-lg font-semibold">+{{ $stockIn->quantity }}</span>
                        </td>
                        <td class="px-4 py-3 dark:text-slate-300 text-slate-700">{{ $stockIn->user->name ?? '-' }}</td>
                        <td class="px-4 py-3 dark:text-slate-400 text-slate-500">{{ $stockIn->notes ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center dark:text-slate-500 text-slate-400">
                            <i class="fas fa-inbox text-3xl mb-2 block"></i>
                            Belum ada riwayat barang masuk
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Riwayat Barang Keluar -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden transition-colors duration-300">
        <div class="p-6 border-b dark:border-slate-700/50 border-slate-200">
            <h4 class="text-lg font-semibold dark:text-white text-slate-800 flex items-center">
                <i class="fas fa-arrow-up text-red-400 mr-2"></i>Riwayat Barang Keluar
                <span class="ml-2 px-2 py-0.5 text-xs bg-red-500/20 text-red-400 rounded-lg">{{ $item->stockOuts->count() }}</span>
            </h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="dark:bg-slate-700/30 bg-slate-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Tanggal</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Jumlah</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Oleh</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50 divide-slate-200">
                    @forelse($item->stockOuts->sortByDesc('date') as $index => $stockOut)
                    <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3 dark:text-slate-400 text-slate-600">{{ $index + 1 }}</td>
                        <td class="px-4 py-3 dark:text-slate-300 text-slate-700">{{ $stockOut->date ? $stockOut->date->format('d/m/Y') : '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 bg-red-500/20 text-red-400 rounded-lg font-semibold">-{{ $stockOut->quantity }}</span>
                        </td>
                        <td class="px-4 py-3 dark:text-slate-300 text-slate-700">{{ $stockOut->user->name ?? '-' }}</td>
                        <td class="px-4 py-3 dark:text-slate-400 text-slate-500">{{ $stockOut->notes ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center dark:text-slate-500 text-slate-400">
                            <i class="fas fa-inbox text-3xl mb-2 block"></i>
                            Belum ada riwayat barang keluar
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
