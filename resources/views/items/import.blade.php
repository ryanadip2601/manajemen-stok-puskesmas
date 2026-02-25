@extends('layouts.app')

@section('title', 'Import Barang dari Excel')
@section('header', 'Import Barang')
@section('breadcrumb', 'Master Data / Barang / Import')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold dark:text-white text-slate-800">Import Data Barang</h3>
            <p class="text-sm dark:text-slate-400 text-slate-600">Upload file Excel untuk menambahkan data barang</p>
        </div>
        <a href="{{ route('items.index') }}" class="px-5 py-3 bg-slate-600 text-white rounded-xl font-semibold hover:bg-slate-700 transition-all shadow-lg inline-flex items-center">
            <i class="fas fa-arrow-left mr-2"></i>Kembali
        </a>
    </div>

    <!-- Import Form -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6 transition-colors duration-300">
        <form action="{{ route('items.import.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- File Upload -->
            <div>
                <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                    File Excel <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input 
                        type="file" 
                        name="file" 
                        accept=".xlsx,.xls"
                        class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-500 file:text-white hover:file:bg-emerald-600"
                        required
                    >
                </div>
                @error('file')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-xs dark:text-slate-500 text-slate-400 mt-2">Format yang didukung: .xlsx, .xls (Max: 10MB)</p>
            </div>

            <!-- Info Box -->
            <div class="dark:bg-blue-900/30 bg-blue-50 border dark:border-blue-700 border-blue-200 rounded-xl p-4">
                <h4 class="font-semibold dark:text-blue-300 text-blue-700 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>Format Excel yang Didukung
                </h4>
                <p class="text-sm dark:text-blue-200 text-blue-600 mb-3">
                    Sistem akan membaca kolom-kolom berikut dari file Excel:
                </p>
                <ul class="text-sm dark:text-blue-200 text-blue-600 space-y-1 list-disc list-inside">
                    <li><strong>Spesifikasi Barang</strong> - Nama barang</li>
                    <li><strong>KATEGORI PERSEDIAAN</strong> - Kategori/jenis barang (Obat, Bahan Lainnya, dll)</li>
                    <li><strong>Jumlah</strong> - Stok awal</li>
                    <li><strong>Satuan barang</strong> - Satuan (Box, Botol, Pcs, dll)</li>
                    <li><strong>Harga Satuan</strong> - Harga per unit</li>
                    <li><strong>Keterangan Sumber Dana</strong> - Perolehan (BLUD, APBD, dll)</li>
                </ul>
            </div>

            <!-- Warning Box -->
            <div class="dark:bg-yellow-900/30 bg-yellow-50 border dark:border-yellow-700 border-yellow-200 rounded-xl p-4">
                <h4 class="font-semibold dark:text-yellow-300 text-yellow-700 mb-2">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Perhatian
                </h4>
                <ul class="text-sm dark:text-yellow-200 text-yellow-600 space-y-1 list-disc list-inside">
                    <li>Kategori dan satuan baru akan dibuat otomatis jika belum ada</li>
                    <li>Jika barang dengan nama yang sama sudah ada, stok akan ditambahkan</li>
                    <li>Kode barang akan di-generate otomatis</li>
                    <li>Pastikan format file sesuai dengan template</li>
                </ul>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end gap-3">
                <a href="{{ route('items.index') }}" class="px-6 py-3 bg-slate-500/50 dark:text-slate-300 text-slate-600 rounded-xl hover:bg-slate-500/70 transition font-semibold">
                    Batal
                </a>
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:from-emerald-600 hover:to-teal-700 transition-all shadow-lg">
                    <i class="fas fa-upload mr-2"></i>Import Data
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
