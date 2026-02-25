@extends('layouts.app')

@section('title', 'Detail Pemeliharaan')
@section('header', 'Detail Pemeliharaan')
@section('breadcrumb', 'Aset / Pemeliharaan / Detail')

@php
    $isStaff = auth()->user()->isStaff();
    $indexRoute = $isStaff ? route('staff.pemeliharaan.index') : route('pemeliharaan.index');
@endphp

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold dark:text-white">Laporan Pemeliharaan #{{ $pemeliharaan->id }}</h3>
            <p class="text-sm dark:text-slate-400">{{ $pemeliharaan->tanggal_pemeliharaan->format('d F Y') }}</p>
        </div>
        <div class="flex gap-2">
            @if(!$isStaff)
            <a href="{{ route('pemeliharaan.edit', $pemeliharaan->id) }}" class="px-4 py-2 bg-amber-500/20 text-amber-400 rounded-xl font-medium hover:bg-amber-500/30 transition">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
            @endif
            <a href="{{ $indexRoute }}" class="px-4 py-2 dark:bg-slate-700 bg-slate-200 dark:text-slate-300 rounded-xl font-medium hover:bg-slate-600 hover:text-white transition">
                <i class="fas fa-arrow-left mr-2"></i>Kembali
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Info Aset -->
            <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 p-6">
                <h4 class="text-lg font-bold dark:text-white mb-4 flex items-center">
                    <i class="fas fa-box text-blue-400 mr-2"></i>Informasi Aset
                </h4>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm dark:text-slate-400">Kode Aset</p>
                        <p class="dark:text-white font-mono">{{ $pemeliharaan->barangAset->kode_aset ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm dark:text-slate-400">Nama Aset</p>
                        <p class="dark:text-white font-medium">{{ $pemeliharaan->barangAset->nama_aset ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm dark:text-slate-400">Kategori</p>
                        <p class="dark:text-white">{{ $pemeliharaan->barangAset->kategoriAset->nama_kategori ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm dark:text-slate-400">Ruangan</p>
                        <span class="px-2 py-1 text-sm bg-blue-500/20 text-blue-400 rounded">{{ $pemeliharaan->ruangan->nama_ruangan ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Detail Pemeliharaan -->
            <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 p-6">
                <h4 class="text-lg font-bold dark:text-white mb-4 flex items-center">
                    <i class="fas fa-tools text-purple-400 mr-2"></i>Detail Pemeliharaan
                </h4>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm dark:text-slate-400">Jenis Pemeliharaan</p>
                            <span class="px-3 py-1 text-sm rounded {{ \App\Models\PemeliharaanAset::getJenisBadgeClass($pemeliharaan->jenis_pemeliharaan) }}">
                                {{ $pemeliharaan->jenis_label }}
                            </span>
                        </div>
                        <div>
                            <p class="text-sm dark:text-slate-400">Teknisi</p>
                            <p class="dark:text-white font-medium">{{ $pemeliharaan->teknisi }}</p>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm dark:text-slate-400">Deskripsi Masalah</p>
                        <p class="dark:text-white bg-slate-700/30 p-3 rounded-lg mt-1">{{ $pemeliharaan->deskripsi_masalah }}</p>
                    </div>
                    @if($pemeliharaan->tindakan_perbaikan)
                    <div>
                        <p class="text-sm dark:text-slate-400">Tindakan Perbaikan</p>
                        <p class="dark:text-white bg-emerald-500/10 p-3 rounded-lg mt-1 border border-emerald-500/30">{{ $pemeliharaan->tindakan_perbaikan }}</p>
                    </div>
                    @endif
                    @if($pemeliharaan->catatan)
                    <div>
                        <p class="text-sm dark:text-slate-400">Catatan</p>
                        <p class="dark:text-slate-300 italic">{{ $pemeliharaan->catatan }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status -->
            <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 p-6">
                <h4 class="text-lg font-bold dark:text-white mb-4">Status</h4>
                <div class="text-center py-4">
                    <span class="px-4 py-2 text-lg rounded-xl {{ \App\Models\PemeliharaanAset::getStatusBadgeClass($pemeliharaan->status) }}">
                        {{ $pemeliharaan->status_label }}
                    </span>
                </div>
                <div class="space-y-3 mt-4 text-sm">
                    <div class="flex justify-between">
                        <span class="dark:text-slate-400">Tanggal Laporan</span>
                        <span class="dark:text-white">{{ $pemeliharaan->tanggal_pemeliharaan->format('d/m/Y') }}</span>
                    </div>
                    @if($pemeliharaan->tanggal_selesai)
                    <div class="flex justify-between">
                        <span class="dark:text-slate-400">Tanggal Selesai</span>
                        <span class="dark:text-emerald-400">{{ $pemeliharaan->tanggal_selesai->format('d/m/Y') }}</span>
                    </div>
                    @endif
                    @if($pemeliharaan->biaya)
                    <div class="flex justify-between">
                        <span class="dark:text-slate-400">Biaya</span>
                        <span class="dark:text-emerald-400 font-medium">Rp {{ number_format($pemeliharaan->biaya, 0, ',', '.') }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Foto -->
            @if($pemeliharaan->lampiran_foto)
            <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 p-6">
                <h4 class="text-lg font-bold dark:text-white mb-4">Lampiran Foto</h4>
                <img src="{{ $pemeliharaan->lampiran_foto_url }}" alt="Lampiran" class="w-full rounded-xl">
            </div>
            @endif

            <!-- Info Tambahan -->
            <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 p-6">
                <h4 class="text-sm font-medium dark:text-slate-400 mb-3">Informasi</h4>
                <div class="text-sm space-y-2 dark:text-slate-300">
                    <p>Dibuat: {{ $pemeliharaan->createdBy->name ?? 'System' }}</p>
                    <p>Pada: {{ $pemeliharaan->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
