@extends('layouts.app')

@section('title', 'Daftar Kategori')
@section('header', 'Daftar Kategori')
@section('breadcrumb', 'Master Data / Kategori')

@section('content')
<div class="space-y-6">
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="dark:bg-slate-800/50 bg-white rounded-xl shadow-lg dark:border dark:border-slate-700/50 border border-slate-200 p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-tags text-white text-xl"></i>
                </div>
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-600">Total Kategori</p>
                    <p class="text-2xl font-bold dark:text-white text-slate-800">{{ $categories->total() }}</p>
                </div>
            </div>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-xl shadow-lg dark:border dark:border-slate-700/50 border border-slate-200 p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-boxes text-white text-xl"></i>
                </div>
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-600">Total Barang</p>
                    <p class="text-2xl font-bold text-emerald-500">{{ $categories->sum('items_count') }}</p>
                </div>
            </div>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-xl shadow-lg dark:border dark:border-slate-700/50 border border-slate-200 p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-folder-open text-white text-xl"></i>
                </div>
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-600">Kategori Terisi</p>
                    <p class="text-2xl font-bold text-amber-500">{{ $categories->where('items_count', '>', 0)->count() }}</p>
                </div>
            </div>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-xl shadow-lg dark:border dark:border-slate-700/50 border border-slate-200 p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-folder text-white text-xl"></i>
                </div>
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-600">Kategori Kosong</p>
                    <p class="text-2xl font-bold text-purple-500">{{ $categories->where('items_count', 0)->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden">
        <!-- Header -->
        <div class="p-6 border-b dark:border-slate-700/50 border-slate-200">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/20 flex items-center justify-center">
                        <i class="fas fa-tags text-blue-400"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold dark:text-white text-slate-800">Data Kategori</h3>
                        <p class="text-xs dark:text-slate-400 text-slate-500">Kelola kategori barang inventaris</p>
                    </div>
                </div>
                <a href="{{ route('categories.create') }}" class="px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:from-emerald-600 hover:to-teal-700 transition-all shadow-lg flex items-center gap-2">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Kategori</span>
                </a>
            </div>
        </div>

        <!-- Search -->
        <div class="p-6 border-b dark:border-slate-700/50 border-slate-200 dark:bg-slate-800/30 bg-slate-50">
            <form method="GET" action="{{ route('categories.index') }}">
                <div class="flex gap-3">
                    <div class="flex-1 relative">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 dark:text-slate-500 text-slate-400"></i>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama kategori..."
                            class="w-full dark:bg-slate-700/50 bg-white rounded-xl pl-11 pr-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-slate-600 border border-slate-300">
                    </div>
                    <button type="submit" class="px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-xl font-medium transition-all flex items-center gap-2">
                        <i class="fas fa-search"></i>
                        <span class="hidden sm:inline">Cari</span>
                    </button>
                    @if(request('search'))
                    <a href="{{ route('categories.index') }}" class="px-4 py-3 dark:bg-slate-700 bg-slate-200 dark:text-slate-300 text-slate-700 rounded-xl hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                        <i class="fas fa-times"></i>
                    </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="dark:bg-slate-700/30 bg-slate-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase tracking-wider">No</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase tracking-wider">Nama Kategori</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase tracking-wider">Deskripsi</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase tracking-wider">Jumlah Barang</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50 divide-slate-200">
                    @forelse($categories as $category)
                    <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="dark:text-slate-400 text-slate-600">{{ $loop->iteration + ($categories->currentPage() - 1) * $categories->perPage() }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500/20 to-indigo-500/20 flex items-center justify-center">
                                    <i class="fas fa-folder text-blue-400"></i>
                                </div>
                                <div>
                                    <p class="dark:text-white text-slate-800 font-semibold">{{ $category->name }}</p>
                                    <p class="text-xs dark:text-slate-500 text-slate-400">ID: {{ $category->id }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="dark:text-slate-300 text-slate-600 text-sm max-w-xs truncate">{{ $category->description ?? '-' }}</p>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($category->items_count > 0)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-500/20 text-emerald-400 rounded-lg text-sm font-semibold">
                                <i class="fas fa-box text-xs"></i>
                                {{ $category->items_count }} barang
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-500/20 dark:text-slate-400 text-slate-500 rounded-lg text-sm">
                                <i class="fas fa-inbox text-xs"></i>
                                Kosong
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('items.index', ['category' => $category->id]) }}" class="p-2 bg-cyan-500/20 text-cyan-400 rounded-lg hover:bg-cyan-500/30 transition" title="Lihat Barang">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('categories.edit', $category->id) }}" class="p-2 bg-amber-500/20 text-amber-400 rounded-lg hover:bg-amber-500/30 transition" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('categories.destroy', $category->id) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">
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
                        <td colspan="5" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-20 h-20 rounded-full bg-slate-500/10 flex items-center justify-center mb-4">
                                    <i class="fas fa-folder-open text-4xl dark:text-slate-600 text-slate-300"></i>
                                </div>
                                <p class="dark:text-slate-400 text-slate-500 font-medium mb-1">Belum ada data kategori</p>
                                <p class="dark:text-slate-500 text-slate-400 text-sm mb-4">Mulai dengan menambahkan kategori baru</p>
                                <a href="{{ route('categories.create') }}" class="px-4 py-2 bg-emerald-500/20 text-emerald-400 rounded-lg hover:bg-emerald-500/30 transition font-medium">
                                    <i class="fas fa-plus mr-2"></i>Tambah Kategori
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($categories->hasPages())
        <div class="p-6 border-t dark:border-slate-700/50 border-slate-200">
            {{ $categories->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
