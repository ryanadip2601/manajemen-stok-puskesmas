@extends('layouts.app')

@section('title', 'Edit Kategori')
@section('header', 'Edit Kategori')
@section('breadcrumb', 'Master Data / Kategori / Edit')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('categories.index') }}" class="p-2.5 dark:bg-slate-700 bg-slate-200 rounded-xl dark:text-slate-300 text-slate-600 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h3 class="text-xl font-bold dark:text-white text-slate-800">Edit Kategori</h3>
            <p class="text-sm dark:text-slate-400 text-slate-600">{{ $category->name }}</p>
        </div>
    </div>

    <!-- Form Card -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden">
        <!-- Header -->
        <div class="p-6 border-b dark:border-slate-700/50 border-slate-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500/20 to-orange-500/20 flex items-center justify-center">
                        <i class="fas fa-edit text-amber-400"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold dark:text-white text-slate-800">Form Edit Kategori</h4>
                        <p class="text-xs dark:text-slate-400 text-slate-500">Update informasi kategori</p>
                    </div>
                </div>
                <span class="px-3 py-1.5 bg-blue-500/20 text-blue-400 rounded-lg text-sm font-medium">
                    <i class="fas fa-box mr-1"></i>
                    {{ $category->items_count ?? 0 }} barang
                </span>
            </div>
        </div>

        <!-- Form -->
        <form action="{{ route('categories.update', $category->id) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Nama Kategori -->
            <div>
                <label for="name" class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">
                    Nama Kategori <span class="text-red-400">*</span>
                </label>
                <div class="relative">
                    <i class="fas fa-tag absolute left-4 top-1/2 -translate-y-1/2 dark:text-slate-500 text-slate-400"></i>
                    <input type="text" id="name" name="name" value="{{ old('name', $category->name) }}" required
                        class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl pl-11 pr-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('name') ring-2 ring-red-500 @enderror">
                </div>
                @error('name')
                <p class="text-red-400 text-sm mt-2 flex items-center gap-1">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ $message }}
                </p>
                @enderror
            </div>

            <!-- Deskripsi -->
            <div>
                <label for="description" class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">
                    Deskripsi <span class="dark:text-slate-500 text-slate-400">(Opsional)</span>
                </label>
                <textarea id="description" name="description" rows="4"
                    placeholder="Deskripsi singkat tentang kategori ini..."
                    class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 resize-none">{{ old('description', $category->description) }}</textarea>
            </div>

            <!-- Category Info -->
            <div class="p-4 dark:bg-slate-700/30 bg-slate-50 rounded-xl">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 rounded-lg bg-slate-500/20 flex items-center justify-center">
                        <i class="fas fa-info-circle dark:text-slate-400 text-slate-500 text-sm"></i>
                    </div>
                    <p class="dark:text-slate-300 text-slate-700 font-medium text-sm">Informasi Kategori</p>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="dark:text-slate-500 text-slate-400">ID Kategori</p>
                        <p class="dark:text-slate-300 text-slate-700 font-mono">{{ $category->id }}</p>
                    </div>
                    <div>
                        <p class="dark:text-slate-500 text-slate-400">Dibuat</p>
                        <p class="dark:text-slate-300 text-slate-700">{{ $category->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <p class="dark:text-slate-500 text-slate-400">Terakhir Update</p>
                        <p class="dark:text-slate-300 text-slate-700">{{ $category->updated_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <p class="dark:text-slate-500 text-slate-400">Jumlah Barang</p>
                        <p class="dark:text-slate-300 text-slate-700 font-semibold">{{ $category->items_count ?? 0 }} item</p>
                    </div>
                </div>
            </div>

            <!-- Warning if has items -->
            @if(($category->items_count ?? 0) > 0)
            <div class="p-4 dark:bg-amber-900/20 bg-amber-50 rounded-xl border dark:border-amber-500/20 border-amber-200">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="fas fa-exclamation-triangle text-amber-400 text-sm"></i>
                    </div>
                    <div>
                        <p class="dark:text-amber-300 text-amber-700 font-medium text-sm">Perhatian</p>
                        <p class="dark:text-amber-400/80 text-amber-600 text-sm mt-1">Kategori ini memiliki {{ $category->items_count }} barang. Perubahan nama kategori akan mempengaruhi semua barang terkait.</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Actions -->
            <div class="flex flex-wrap items-center gap-3 pt-4 border-t dark:border-slate-700/50 border-slate-200">
                <button type="submit" class="flex-1 sm:flex-none px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-indigo-700 transition-all shadow-lg flex items-center justify-center gap-2">
                    <i class="fas fa-save"></i>
                    <span>Update Kategori</span>
                </button>
                <a href="{{ route('categories.index') }}" class="px-6 py-3 dark:bg-slate-700 bg-slate-200 dark:text-slate-300 text-slate-700 rounded-xl font-semibold hover:bg-slate-300 dark:hover:bg-slate-600 transition flex items-center gap-2">
                    <i class="fas fa-times"></i>
                    <span>Batal</span>
                </a>
                @if(($category->items_count ?? 0) == 0)
                <button type="button" onclick="document.getElementById('delete-form').submit()" class="ml-auto px-6 py-3 bg-red-500/20 text-red-400 rounded-xl font-semibold hover:bg-red-500/30 transition flex items-center gap-2">
                    <i class="fas fa-trash"></i>
                    <span>Hapus</span>
                </button>
                @endif
            </div>
        </form>
        
        @if(($category->items_count ?? 0) == 0)
        <form id="delete-form" action="{{ route('categories.destroy', $category->id) }}" method="POST" class="hidden" onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">
            @csrf
            @method('DELETE')
        </form>
        @endif
    </div>
</div>
@endsection
