@extends('layouts.app')

@section('title', 'Tambah Kategori')
@section('header', 'Tambah Kategori')
@section('breadcrumb', 'Master Data / Kategori / Tambah')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('categories.index') }}" class="p-2.5 dark:bg-slate-700 bg-slate-200 rounded-xl dark:text-slate-300 text-slate-600 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h3 class="text-xl font-bold dark:text-white text-slate-800">Tambah Kategori Baru</h3>
            <p class="text-sm dark:text-slate-400 text-slate-600">Buat kategori untuk mengelompokkan barang</p>
        </div>
    </div>

    <!-- Form Card -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden">
        <!-- Header -->
        <div class="p-6 border-b dark:border-slate-700/50 border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500/20 to-teal-500/20 flex items-center justify-center">
                    <i class="fas fa-plus text-emerald-400"></i>
                </div>
                <div>
                    <h4 class="font-semibold dark:text-white text-slate-800">Form Kategori</h4>
                    <p class="text-xs dark:text-slate-400 text-slate-500">Isi data kategori dengan lengkap</p>
                </div>
            </div>
        </div>

        <!-- Form -->
        <form action="{{ route('categories.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            <!-- Nama Kategori -->
            <div>
                <label for="name" class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">
                    Nama Kategori <span class="text-red-400">*</span>
                </label>
                <div class="relative">
                    <i class="fas fa-tag absolute left-4 top-1/2 -translate-y-1/2 dark:text-slate-500 text-slate-400"></i>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                        placeholder="Contoh: Obat-obatan, Alat Kesehatan"
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
                    class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 resize-none">{{ old('description') }}</textarea>
                <p class="text-xs dark:text-slate-500 text-slate-400 mt-2">Berikan deskripsi untuk memudahkan identifikasi kategori</p>
            </div>

            <!-- Info Box -->
            <div class="p-4 dark:bg-blue-900/20 bg-blue-50 rounded-xl border dark:border-blue-500/20 border-blue-200">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-500/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="fas fa-lightbulb text-blue-400 text-sm"></i>
                    </div>
                    <div>
                        <p class="dark:text-blue-300 text-blue-700 font-medium text-sm">Tips</p>
                        <p class="dark:text-blue-400/80 text-blue-600 text-sm mt-1">Gunakan nama kategori yang jelas dan mudah dipahami. Kategori yang baik akan memudahkan pencarian dan pengelompokan barang.</p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3 pt-4 border-t dark:border-slate-700/50 border-slate-200">
                <button type="submit" class="flex-1 sm:flex-none px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:from-emerald-600 hover:to-teal-700 transition-all shadow-lg flex items-center justify-center gap-2">
                    <i class="fas fa-save"></i>
                    <span>Simpan Kategori</span>
                </button>
                <a href="{{ route('categories.index') }}" class="px-6 py-3 dark:bg-slate-700 bg-slate-200 dark:text-slate-300 text-slate-700 rounded-xl font-semibold hover:bg-slate-300 dark:hover:bg-slate-600 transition flex items-center gap-2">
                    <i class="fas fa-times"></i>
                    <span>Batal</span>
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
