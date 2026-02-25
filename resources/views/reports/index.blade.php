@extends('layouts.app')

@section('title', 'Semua Laporan')
@section('header', 'Pusat Laporan')
@section('breadcrumb', 'Laporan / Semua Laporan')

@section('content')
<div class="space-y-6">
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6 transition-colors duration-300">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-xl bg-cyan-500/20 flex items-center justify-center mr-4">
                <i class="fas fa-chart-pie text-cyan-400 text-2xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold dark:text-white text-slate-800">Pusat Laporan</h2>
                <p class="text-sm dark:text-slate-400 text-slate-500">Pilih jenis laporan yang ingin dilihat</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Barang Masuk --}}
            <a href="{{ route('reports.barang-masuk') }}" class="group dark:bg-slate-700/30 bg-slate-50 rounded-2xl p-5 dark:border dark:border-slate-700/50 border border-slate-200 hover:border-green-500/50 dark:hover:border-green-500/50 transition-all duration-300 hover:shadow-lg hover:shadow-green-500/10">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl bg-green-500/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas fa-arrow-down text-green-400 text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold text-green-400">{{ number_format($stats['barang_masuk']) }}</span>
                </div>
                <h3 class="font-semibold dark:text-white text-slate-800">Barang Masuk</h3>
                <p class="text-xs dark:text-slate-400 text-slate-500 mt-1">Riwayat penerimaan barang</p>
            </a>

            {{-- Barang Keluar --}}
            <a href="{{ route('reports.barang-keluar') }}" class="group dark:bg-slate-700/30 bg-slate-50 rounded-2xl p-5 dark:border dark:border-slate-700/50 border border-slate-200 hover:border-red-500/50 dark:hover:border-red-500/50 transition-all duration-300 hover:shadow-lg hover:shadow-red-500/10">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl bg-red-500/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas fa-arrow-up text-red-400 text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold text-red-400">{{ number_format($stats['barang_keluar']) }}</span>
                </div>
                <h3 class="font-semibold dark:text-white text-slate-800">Barang Keluar</h3>
                <p class="text-xs dark:text-slate-400 text-slate-500 mt-1">Riwayat pengeluaran barang</p>
            </a>

            {{-- Pengambilan Barang --}}
            <a href="{{ route('reports.pengambilan-barang') }}" class="group dark:bg-slate-700/30 bg-slate-50 rounded-2xl p-5 dark:border dark:border-slate-700/50 border border-slate-200 hover:border-amber-500/50 dark:hover:border-amber-500/50 transition-all duration-300 hover:shadow-lg hover:shadow-amber-500/10">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-500/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas fa-hand-holding text-amber-400 text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold text-amber-400">{{ number_format($stats['pengambilan']) }}</span>
                </div>
                <h3 class="font-semibold dark:text-white text-slate-800">Pengambilan Barang</h3>
                <p class="text-xs dark:text-slate-400 text-slate-500 mt-1">Permintaan pengambilan staf</p>
            </a>

            {{-- Permintaan BHP --}}
            <a href="{{ route('reports.permintaan-bhp') }}" class="group dark:bg-slate-700/30 bg-slate-50 rounded-2xl p-5 dark:border dark:border-slate-700/50 border border-slate-200 hover:border-purple-500/50 dark:hover:border-purple-500/50 transition-all duration-300 hover:shadow-lg hover:shadow-purple-500/10">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl bg-purple-500/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas fa-clipboard-list text-purple-400 text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold text-purple-400">{{ number_format($stats['permintaan_bhp']) }}</span>
                </div>
                <h3 class="font-semibold dark:text-white text-slate-800">Permintaan BHP</h3>
                <p class="text-xs dark:text-slate-400 text-slate-500 mt-1">Permintaan barang habis pakai</p>
            </a>

            {{-- Usulan Aset --}}
            <a href="{{ route('reports.usulan-aset') }}" class="group dark:bg-slate-700/30 bg-slate-50 rounded-2xl p-5 dark:border dark:border-slate-700/50 border border-slate-200 hover:border-blue-500/50 dark:hover:border-blue-500/50 transition-all duration-300 hover:shadow-lg hover:shadow-blue-500/10">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-500/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas fa-file-signature text-blue-400 text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold text-blue-400">{{ number_format($stats['usulan_aset']) }}</span>
                </div>
                <h3 class="font-semibold dark:text-white text-slate-800">Usulan Aset</h3>
                <p class="text-xs dark:text-slate-400 text-slate-500 mt-1">Usulan permintaan barang aset</p>
            </a>

            {{-- Pemeliharaan --}}
            <a href="{{ route('reports.pemeliharaan') }}" class="group dark:bg-slate-700/30 bg-slate-50 rounded-2xl p-5 dark:border dark:border-slate-700/50 border border-slate-200 hover:border-orange-500/50 dark:hover:border-orange-500/50 transition-all duration-300 hover:shadow-lg hover:shadow-orange-500/10">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl bg-orange-500/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas fa-tools text-orange-400 text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold text-orange-400">{{ number_format($stats['pemeliharaan']) }}</span>
                </div>
                <h3 class="font-semibold dark:text-white text-slate-800">Pemeliharaan</h3>
                <p class="text-xs dark:text-slate-400 text-slate-500 mt-1">Pemeliharaan & perbaikan aset</p>
            </a>

            {{-- Laporan Bulanan --}}
            <a href="{{ route('reports.monthly') }}" class="group dark:bg-slate-700/30 bg-slate-50 rounded-2xl p-5 dark:border dark:border-slate-700/50 border border-slate-200 hover:border-cyan-500/50 dark:hover:border-cyan-500/50 transition-all duration-300 hover:shadow-lg hover:shadow-cyan-500/10">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl bg-cyan-500/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas fa-calendar-alt text-cyan-400 text-xl"></i>
                    </div>
                    <i class="fas fa-arrow-right text-slate-500 group-hover:text-cyan-400 transition-colors"></i>
                </div>
                <h3 class="font-semibold dark:text-white text-slate-800">Laporan Bulanan</h3>
                <p class="text-xs dark:text-slate-400 text-slate-500 mt-1">Rekap stok bulanan/triwulan</p>
            </a>

            {{-- Laporan Tahunan --}}
            <a href="{{ route('reports.yearly') }}" class="group dark:bg-slate-700/30 bg-slate-50 rounded-2xl p-5 dark:border dark:border-slate-700/50 border border-slate-200 hover:border-indigo-500/50 dark:hover:border-indigo-500/50 transition-all duration-300 hover:shadow-lg hover:shadow-indigo-500/10">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-500/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas fa-chart-line text-indigo-400 text-xl"></i>
                    </div>
                    <i class="fas fa-arrow-right text-slate-500 group-hover:text-indigo-400 transition-colors"></i>
                </div>
                <h3 class="font-semibold dark:text-white text-slate-800">Laporan Tahunan</h3>
                <p class="text-xs dark:text-slate-400 text-slate-500 mt-1">Rekap stok tahunan</p>
            </a>
        </div>
    </div>
</div>
@endsection
