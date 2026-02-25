@extends('layouts.app')

@section('title', 'Data Barang')
@section('header', 'Data Barang')
@section('breadcrumb', 'Master Data / Barang')

@section('content')
<div class="space-y-6">
    <!-- Header & Add Button -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold dark:text-white text-slate-800">Daftar Barang</h3>
            <p class="text-sm dark:text-slate-400 text-slate-600">Kelola data barang inventaris</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('items.import') }}" class="px-5 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-indigo-700 transition-all shadow-lg inline-flex items-center">
                <i class="fas fa-file-excel mr-2"></i>Import Excel
            </a>
            <a href="{{ route('items.create') }}" class="px-5 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:from-emerald-600 hover:to-teal-700 transition-all shadow-lg inline-flex items-center">
                <i class="fas fa-plus mr-2"></i>Tambah Barang
            </a>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6 transition-colors duration-300">
        <form method="GET" action="{{ route('items.index') }}" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Cari Barang</label>
                <input 
                    type="text" 
                    name="search" 
                    value="{{ $activeFilters['search'] ?? '' }}"
                    placeholder="Nama atau kode barang..." 
                    class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
                >
            </div>
            <div class="flex-1 min-w-[160px]">
                <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Perolehan</label>
                <select 
                    name="perolehan" 
                    class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
                >
                    <option value="">Semua Perolehan</option>
                    @foreach(\App\Models\Item::getPerolehanOptions() as $key => $value)
                        <option value="{{ $key }}" @selected(($activeFilters['perolehan'] ?? '') == $key)>{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[160px]">
                <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Jenis Barang</label>
                <select 
                    name="category_id" 
                    class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
                >
                    <option value="">Semua Jenis</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(($activeFilters['category_id'] ?? '') == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-5 py-3 bg-gradient-to-r from-slate-600 to-slate-700 text-white rounded-xl font-semibold hover:from-slate-700 hover:to-slate-800 transition-all shadow-lg">
                    <i class="fas fa-search mr-2"></i>Cari
                </button>
                <a href="{{ route('items.index', ['low_stock' => 1]) }}" class="px-5 py-3 bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-xl font-semibold hover:from-red-600 hover:to-rose-700 transition-all shadow-lg inline-flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Stok Rendah
                </a>
                <a href="{{ route('items.clear-filter') }}" class="px-4 py-3 bg-slate-500/50 dark:text-slate-300 text-slate-600 rounded-xl hover:bg-slate-500/70 transition" title="Reset Filter">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Items Table -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden transition-colors duration-300">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="dark:bg-slate-700/30 bg-slate-100">
                    <tr>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase w-12 text-center">No</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Barang</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Perolehan</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Jenis</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Satuan</th>
                        <th class="px-4 py-4 text-right text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Harga</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Stok</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50 divide-slate-200">
                    @php $no = ($items->currentPage() - 1) * $items->perPage() + 1; @endphp
                    @forelse($items as $item)
                    <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-4 dark:text-slate-400 text-slate-600 text-center">{{ $no++ }}</td>
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-4">
                                @if($item->photo)
                                    <img src="{{ $item->getPhotoUrl() }}" alt="{{ $item->name }}" class="w-12 h-12 rounded-xl object-cover border dark:border-slate-600 border-slate-300">
                                @else
                                    <div class="w-12 h-12 rounded-xl dark:bg-slate-700 bg-slate-200 flex items-center justify-center border dark:border-slate-600 border-slate-300">
                                        <i class="fas fa-box text-lg dark:text-slate-500 text-slate-400"></i>
                                    </div>
                                @endif
                                <div>
                                    <p class="dark:text-white text-slate-800 font-medium">{{ $item->name }}</p>
                                    <p class="text-xs dark:text-slate-500 text-slate-400 font-mono">{{ $item->code }}</p>
                                </div>
                            </div>
                        </td>
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
                            @if($item->isLowStock())
                                <span class="px-3 py-1 bg-red-500/20 text-red-400 rounded-lg font-semibold">{{ $item->stock }}</span>
                            @else
                                <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 rounded-lg font-semibold">{{ $item->stock }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('items.edit', $item->id) }}" class="p-2 bg-amber-500/20 text-amber-400 rounded-lg hover:bg-amber-500/30 transition">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('items.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus barang ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                    @if(request('search'))
                                        <input type="hidden" name="search" value="{{ request('search') }}">
                                    @endif
                                    @if(request('category_id'))
                                        <input type="hidden" name="category_id" value="{{ request('category_id') }}">
                                    @endif
                                    @if(request('perolehan'))
                                        <input type="hidden" name="perolehan" value="{{ request('perolehan') }}">
                                    @endif
                                    @if(request('low_stock'))
                                        <input type="hidden" name="low_stock" value="{{ request('low_stock') }}">
                                    @endif
                                    <button type="submit" class="p-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center dark:text-slate-500 text-slate-400">
                            <i class="fas fa-box-open text-4xl mb-3 block"></i>
                            Belum ada data barang
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
        <div class="p-4 border-t dark:border-slate-700/50 border-slate-200">
            {{ $items->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
