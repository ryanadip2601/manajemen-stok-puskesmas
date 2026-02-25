@extends('layouts.app')

@section('title', 'Detail Permintaan - ' . $usulan->kode_permintaan)

@section('header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold dark:text-white text-slate-800">Detail Permintaan Barang</h1>
            <p class="text-sm dark:text-slate-400 text-slate-500 mt-1">Verifikasi dan tindak lanjut usulan permintaan</p>
        </div>
        <a href="{{ route('usulan-permintaan-barang.admin.index') }}"
            class="inline-flex items-center gap-2 dark:bg-slate-700/50 bg-slate-100 dark:hover:bg-slate-600 hover:bg-slate-200 dark:text-slate-300 text-slate-600 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200">
            <i class="fas fa-arrow-left text-xs"></i>
            Kembali
        </a>
    </div>
@endsection

@section('breadcrumb')
    <nav class="flex items-center space-x-2 text-sm dark:text-slate-400 text-slate-500">
        <a href="{{ route('dashboard') }}" class="hover:text-emerald-500 transition-colors">
            <i class="fas fa-home"></i>
        </a>
        <span>/</span>
        <a href="{{ route('usulan-permintaan-barang.admin.index') }}" class="hover:text-emerald-500 transition-colors">Verifikasi Permintaan</a>
        <span>/</span>
        <span class="dark:text-white text-slate-800 font-medium">{{ $usulan->kode_permintaan }}</span>
    </nav>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left Column - Info (2 cols wide) --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Header Card --}}
            <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-lg border dark:border-slate-700/50 border-slate-200 p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-file-alt text-white text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold dark:text-white text-slate-800">{{ $usulan->kode_permintaan }}</h2>
                            <p class="text-sm dark:text-slate-400 text-slate-500">Permintaan Barang Habis Pakai</p>
                        </div>
                    </div>

                    @php
                        $statusBadge = [
                            'pending' => 'bg-yellow-50 text-yellow-700 border-yellow-300 dark:bg-yellow-600/10 dark:text-yellow-400 dark:border-yellow-500/30',
                            'diproses' => 'bg-cyan-50 text-cyan-700 border-cyan-300 dark:bg-cyan-600/10 dark:text-cyan-400 dark:border-cyan-500/30',
                            'disetujui' => 'bg-emerald-50 text-emerald-700 border-emerald-300 dark:bg-emerald-600/10 dark:text-emerald-400 dark:border-emerald-500/30',
                            'ditolak' => 'bg-red-50 text-red-700 border-red-300 dark:bg-red-600/10 dark:text-red-400 dark:border-red-500/30',
                            'revisi' => 'bg-purple-50 text-purple-700 border-purple-300 dark:bg-purple-600/10 dark:text-purple-400 dark:border-purple-500/30',
                        ];
                        $statusIcons = [
                            'pending' => 'fa-clock',
                            'diproses' => 'fa-spinner',
                            'disetujui' => 'fa-check-circle',
                            'ditolak' => 'fa-times-circle',
                            'revisi' => 'fa-edit',
                        ];
                    @endphp
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold border {{ $statusBadge[$usulan->status] ?? $statusBadge['pending'] }}">
                        <i class="fas {{ $statusIcons[$usulan->status] ?? 'fa-clock' }} text-xs"></i>
                        {{ ucfirst($usulan->status) }}
                    </span>
                </div>
            </div>

            {{-- Detail Info Card --}}
            <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-lg border dark:border-slate-700/50 border-slate-200 p-6">
                <h3 class="font-semibold dark:text-white text-slate-800 mb-5 flex items-center gap-2">
                    <i class="fas fa-info-circle text-emerald-500"></i>
                    Informasi Permintaan
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Pengusul --}}
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fas fa-user text-white text-sm"></i>
                        </div>
                        <div>
                            <p class="text-xs dark:text-slate-500 text-slate-400 font-medium uppercase tracking-wider">Pengusul</p>
                            <p class="text-sm font-semibold dark:text-white text-slate-800 mt-0.5">{{ $usulan->user->name ?? '-' }}</p>
                            <p class="text-xs dark:text-slate-400 text-slate-500">{{ $usulan->user->email ?? '' }}</p>
                        </div>
                    </div>

                    {{-- Ruangan --}}
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fas fa-door-open text-white text-sm"></i>
                        </div>
                        <div>
                            <p class="text-xs dark:text-slate-500 text-slate-400 font-medium uppercase tracking-wider">Ruangan Tujuan</p>
                            <p class="text-sm font-semibold dark:text-white text-slate-800 mt-0.5">{{ $usulan->ruangan->nama_ruangan ?? '-' }}</p>
                        </div>
                    </div>

                    {{-- Nama Barang --}}
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fas fa-box text-white text-sm"></i>
                        </div>
                        <div>
                            <p class="text-xs dark:text-slate-500 text-slate-400 font-medium uppercase tracking-wider">Nama Barang</p>
                            <p class="text-sm font-semibold dark:text-white text-slate-800 mt-0.5">{{ $usulan->nama_barang }}</p>
                        </div>
                    </div>

                    {{-- Jumlah --}}
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500 to-teal-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fas fa-sort-numeric-up text-white text-sm"></i>
                        </div>
                        <div>
                            <p class="text-xs dark:text-slate-500 text-slate-400 font-medium uppercase tracking-wider">Jumlah</p>
                            <p class="text-sm font-semibold dark:text-white text-slate-800 mt-0.5">{{ number_format($usulan->jumlah) }} {{ $usulan->satuan ?? '' }}</p>
                        </div>
                    </div>

                    {{-- Prioritas --}}
                    <div class="flex items-start gap-3">
                        @php
                            $priGradients = [
                                'rendah' => 'from-slate-400 to-slate-500',
                                'sedang' => 'from-blue-500 to-blue-600',
                                'tinggi' => 'from-orange-500 to-orange-600',
                                'mendesak' => 'from-red-500 to-rose-600',
                            ];
                            $priGrad = $priGradients[$usulan->prioritas] ?? $priGradients['sedang'];
                        @endphp
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br {{ $priGrad }} flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fas fa-flag text-white text-sm"></i>
                        </div>
                        <div>
                            <p class="text-xs dark:text-slate-500 text-slate-400 font-medium uppercase tracking-wider">Prioritas</p>
                            <p class="text-sm font-semibold dark:text-white text-slate-800 mt-0.5">{{ ucfirst($usulan->prioritas) }}</p>
                        </div>
                    </div>

                    {{-- Tanggal --}}
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-pink-500 to-rose-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fas fa-calendar-alt text-white text-sm"></i>
                        </div>
                        <div>
                            <p class="text-xs dark:text-slate-500 text-slate-400 font-medium uppercase tracking-wider">Tanggal Permintaan</p>
                            <p class="text-sm font-semibold dark:text-white text-slate-800 mt-0.5">{{ $usulan->created_at->format('d F Y, H:i') }} WIB</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Foto Barang --}}
            @if($usulan->foto_barang)
                <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-lg border dark:border-slate-700/50 border-slate-200 p-6">
                    <h3 class="font-semibold dark:text-white text-slate-800 mb-3 flex items-center gap-2">
                        <i class="fas fa-image text-pink-500"></i>
                        Foto Barang
                    </h3>
                    <div class="relative inline-block group rounded-xl overflow-hidden border dark:border-slate-600/30 border-slate-200">
                        <img src="{{ $usulan->tipe_foto_barang === 'url' ? $usulan->foto_barang : asset($usulan->foto_barang) }}" 
                             alt="Foto {{ $usulan->nama_barang }}" 
                             class="h-48 w-auto object-cover max-w-full">
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <a href="{{ $usulan->tipe_foto_barang === 'url' ? $usulan->foto_barang : asset($usulan->foto_barang) }}" target="_blank" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg backdrop-blur-sm transition-colors text-sm font-semibold">
                                <i class="fas fa-external-link-alt mr-2"></i>Buka Gambar
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Keterangan User --}}
            @if($usulan->keterangan)
                <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-lg border dark:border-slate-700/50 border-slate-200 p-6">
                    <h3 class="font-semibold dark:text-white text-slate-800 mb-3 flex items-center gap-2">
                        <i class="fas fa-comment-alt text-blue-500"></i>
                        Keterangan Pengusul
                    </h3>
                    <div class="dark:bg-slate-700/30 bg-slate-50 rounded-xl p-4 border dark:border-slate-600/30 border-slate-200">
                        <p class="text-sm dark:text-slate-300 text-slate-700 leading-relaxed whitespace-pre-line">{{ $usulan->keterangan }}</p>
                    </div>
                </div>
            @endif

            {{-- Previous Admin Notes (for revisi/ditolak) --}}
            @if(in_array($usulan->status, ['revisi', 'ditolak']))
                <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-lg border {{ $usulan->status === 'ditolak' ? 'dark:border-red-500/30 border-red-200' : 'dark:border-purple-500/30 border-purple-200' }} p-6">
                    <h3 class="font-semibold mb-3 flex items-center gap-2 {{ $usulan->status === 'ditolak' ? 'text-red-600 dark:text-red-400' : 'text-purple-600 dark:text-purple-400' }}">
                        <i class="fas {{ $usulan->status === 'ditolak' ? 'fa-times-circle' : 'fa-exclamation-triangle' }}"></i>
                        {{ $usulan->status === 'ditolak' ? 'Alasan Penolakan' : 'Permintaan Revisi dari Admin' }}
                    </h3>
                    <div class="rounded-xl p-4 border {{ $usulan->status === 'ditolak' ? 'dark:bg-red-500/5 bg-red-50 dark:border-red-500/20 border-red-200' : 'dark:bg-purple-500/5 bg-purple-50 dark:border-purple-500/20 border-purple-200' }}">
                        <p class="text-sm dark:text-slate-300 text-slate-700 leading-relaxed whitespace-pre-line">{{ $usulan->status === 'ditolak' ? $usulan->alasan_penolakan : $usulan->permintaan_revisi }}</p>
                    </div>
                    @if($usulan->verifiedBy)
                        <div class="mt-3 flex items-center gap-2 text-xs dark:text-slate-500 text-slate-400">
                            <i class="fas fa-user-shield"></i>
                            <span>Oleh: {{ $usulan->verifiedBy->name ?? '-' }}</span>
                            <span>&middot;</span>
                            <span>{{ $usulan->verified_at ? \Carbon\Carbon::parse($usulan->verified_at)->format('d/m/Y H:i') : '-' }}</span>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Right Column - Action Panel --}}
        <div class="lg:col-span-1 space-y-6">

            {{-- Action Panel: Pending / Diproses --}}
            @if(in_array($usulan->status, ['pending', 'diproses']))
                <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-lg border dark:border-slate-700/50 border-slate-200 p-6"
                    x-data="{ action: '' }">

                    <h3 class="font-semibold dark:text-white text-slate-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-gavel text-amber-500"></i>
                        Tindakan Verifikasi
                    </h3>

                    {{-- Action Buttons --}}
                    <div class="grid grid-cols-1 gap-3 mb-5">
                        <button @click="action = action === 'approve' ? '' : 'approve'"
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200"
                            :class="action === 'approve'
                                ? 'bg-gradient-to-r from-emerald-600 to-green-600 text-white shadow-lg shadow-emerald-500/25'
                                : 'dark:bg-emerald-500/10 bg-emerald-50 dark:text-emerald-400 text-emerald-700 border dark:border-emerald-500/30 border-emerald-200 hover:shadow-md'">
                            <i class="fas fa-check-circle text-xs"></i>
                            Setujui
                        </button>

                        <button @click="action = action === 'reject' ? '' : 'reject'"
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200"
                            :class="action === 'reject'
                                ? 'bg-gradient-to-r from-red-600 to-rose-600 text-white shadow-lg shadow-red-500/25'
                                : 'dark:bg-red-500/10 bg-red-50 dark:text-red-400 text-red-700 border dark:border-red-500/30 border-red-200 hover:shadow-md'">
                            <i class="fas fa-times-circle text-xs"></i>
                            Tolak
                        </button>

                        <button @click="action = action === 'revision' ? '' : 'revision'"
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200"
                            :class="action === 'revision'
                                ? 'bg-gradient-to-r from-purple-600 to-violet-600 text-white shadow-lg shadow-purple-500/25'
                                : 'dark:bg-purple-500/10 bg-purple-50 dark:text-purple-400 text-purple-700 border dark:border-purple-500/30 border-purple-200 hover:shadow-md'">
                            <i class="fas fa-edit text-xs"></i>
                            Minta Revisi
                        </button>
                    </div>

                    {{-- Approve Form --}}
                    <div x-show="action === 'approve'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                        <form action="{{ route('usulan-permintaan-barang.admin.approve', $usulan->id) }}" method="POST">
                            @csrf

                            <div class="dark:bg-emerald-500/5 bg-emerald-50 rounded-xl p-4 border dark:border-emerald-500/20 border-emerald-200 mb-4">
                                <p class="text-xs dark:text-emerald-400 text-emerald-700 font-medium">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Lengkapi data barang untuk menambahkannya ke inventaris
                                </p>
                            </div>

                            <div class="space-y-4">
                                {{-- Category --}}
                                <div>
                                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                                        Kategori Barang <span class="text-red-500">*</span>
                                    </label>
                                    <select name="category_id" required
                                        class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 border rounded-xl px-4 py-3 dark:text-white text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                        <option value="">Pilih Kategori&hellip;</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <p class="text-xs text-red-500 mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Unit --}}
                                <div>
                                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                                        Satuan <span class="text-red-500">*</span>
                                    </label>
                                    <select name="unit_id" required
                                        class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 border rounded-xl px-4 py-3 dark:text-white text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                        <option value="">Pilih Satuan&hellip;</option>
                                        @foreach($units as $unit)
                                            <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('unit_id')
                                        <p class="text-xs text-red-500 mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Code --}}
                                <div>
                                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                                        Kode Barang <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="code" value="{{ old('code') }}" required
                                        placeholder="BRG00XXX"
                                        class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 border rounded-xl px-4 py-3 dark:text-white text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 placeholder:dark:text-slate-500 placeholder:text-slate-400 font-mono">
                                    @error('code')
                                        <p class="text-xs text-red-500 mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Price --}}
                                <div>
                                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                                        Harga Satuan <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm dark:text-slate-500 text-slate-400 font-medium">Rp</span>
                                        <input type="number" name="price" value="{{ old('price') }}" required min="0" step="1"
                                            placeholder="0"
                                            class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 border rounded-xl pl-10 pr-4 py-3 dark:text-white text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 placeholder:dark:text-slate-500 placeholder:text-slate-400">
                                    </div>
                                    @error('price')
                                        <p class="text-xs text-red-500 mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Perolehan --}}
                                <div>
                                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                                        Sumber Perolehan <span class="text-red-500">*</span>
                                    </label>
                                    <select name="perolehan" required
                                        class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 border rounded-xl px-4 py-3 dark:text-white text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                        <option value="">Pilih Sumber&hellip;</option>
                                        <option value="BLUD" {{ old('perolehan') == 'BLUD' ? 'selected' : '' }}>BLUD</option>
                                        <option value="APBD Balikpapan" {{ old('perolehan') == 'APBD Balikpapan' ? 'selected' : '' }}>APBD Balikpapan</option>
                                    </select>
                                    @error('perolehan')
                                        <p class="text-xs text-red-500 mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Minimum Stock --}}
                                <div>
                                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                                        Stok Minimum
                                    </label>
                                    <input type="number" name="minimum_stock" value="{{ old('minimum_stock', 5) }}" min="0"
                                        class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 border rounded-xl px-4 py-3 dark:text-white text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                    @error('minimum_stock')
                                        <p class="text-xs text-red-500 mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Catatan Admin --}}
                                <div>
                                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                                        Catatan Admin <span class="text-xs dark:text-slate-500 text-slate-400">(opsional)</span>
                                    </label>
                                    <textarea name="catatan_admin" rows="3"
                                        placeholder="Catatan tambahan&hellip;"
                                        class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 border rounded-xl px-4 py-3 dark:text-white text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 placeholder:dark:text-slate-500 placeholder:text-slate-400 resize-none">{{ old('catatan_admin') }}</textarea>
                                    @error('catatan_admin')
                                        <p class="text-xs text-red-500 mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <button type="submit"
                                class="w-full mt-5 bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white px-5 py-3 rounded-xl text-sm font-semibold transition-all duration-200 shadow-lg shadow-emerald-500/25 flex items-center justify-center gap-2"
                                onclick="return confirm('Yakin ingin menyetujui permintaan ini? Barang akan ditambahkan ke inventaris.')">
                                <i class="fas fa-check-circle"></i>
                                Setujui Permintaan
                            </button>
                        </form>
                    </div>

                    {{-- Reject Form --}}
                    <div x-show="action === 'reject'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                        <form action="{{ route('usulan-permintaan-barang.admin.reject', $usulan->id) }}" method="POST">
                            @csrf

                            <div class="dark:bg-red-500/5 bg-red-50 rounded-xl p-4 border dark:border-red-500/20 border-red-200 mb-4">
                                <p class="text-xs dark:text-red-400 text-red-700 font-medium">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    Permintaan akan ditolak dan pengusul akan diberitahu
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                                    Alasan Penolakan <span class="text-red-500">*</span>
                                </label>
                                <textarea name="alasan_penolakan" rows="4" required
                                    placeholder="Jelaskan alasan penolakan permintaan ini&hellip;"
                                    class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 border rounded-xl px-4 py-3 dark:text-white text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 placeholder:dark:text-slate-500 placeholder:text-slate-400 resize-none">{{ old('alasan_penolakan') }}</textarea>
                                @error('alasan_penolakan')
                                    <p class="text-xs text-red-500 mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit"
                                class="w-full mt-5 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white px-5 py-3 rounded-xl text-sm font-semibold transition-all duration-200 shadow-lg shadow-red-500/25 flex items-center justify-center gap-2"
                                onclick="return confirm('Yakin ingin menolak permintaan ini?')">
                                <i class="fas fa-times-circle"></i>
                                Tolak Permintaan
                            </button>
                        </form>
                    </div>

                    {{-- Revision Form --}}
                    <div x-show="action === 'revision'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                        <form action="{{ route('usulan-permintaan-barang.admin.revision', $usulan->id) }}" method="POST">
                            @csrf

                            <div class="dark:bg-purple-500/5 bg-purple-50 rounded-xl p-4 border dark:border-purple-500/20 border-purple-200 mb-4">
                                <p class="text-xs dark:text-purple-400 text-purple-700 font-medium">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Pengusul akan diminta untuk merevisi dan mengirimkan ulang permintaan
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                                    Permintaan Revisi <span class="text-red-500">*</span>
                                </label>
                                <textarea name="permintaan_revisi" rows="4" required
                                    placeholder="Jelaskan apa yang perlu direvisi oleh pengusul&hellip;"
                                    class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 border rounded-xl px-4 py-3 dark:text-white text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 placeholder:dark:text-slate-500 placeholder:text-slate-400 resize-none">{{ old('permintaan_revisi') }}</textarea>
                                @error('permintaan_revisi')
                                    <p class="text-xs text-red-500 mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit"
                                class="w-full mt-5 bg-gradient-to-r from-purple-600 to-violet-600 hover:from-purple-700 hover:to-violet-700 text-white px-5 py-3 rounded-xl text-sm font-semibold transition-all duration-200 shadow-lg shadow-purple-500/25 flex items-center justify-center gap-2"
                                onclick="return confirm('Yakin ingin meminta revisi untuk permintaan ini?')">
                                <i class="fas fa-edit"></i>
                                Minta Revisi
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Approved Result --}}
            @if($usulan->status === 'disetujui')
                <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-lg border dark:border-emerald-500/30 border-emerald-200 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-green-600 flex items-center justify-center">
                            <i class="fas fa-check-double text-white text-sm"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold dark:text-emerald-400 text-emerald-700">Permintaan Disetujui</h3>
                            <p class="text-xs dark:text-slate-400 text-slate-500">Barang telah ditambahkan ke inventaris</p>
                        </div>
                    </div>

                    @if($usulan->catatan_admin)
                        <div class="dark:bg-emerald-500/5 bg-emerald-50 rounded-xl p-4 border dark:border-emerald-500/20 border-emerald-200 mb-4">
                            <p class="text-xs dark:text-slate-500 text-slate-400 font-medium mb-1">Catatan Admin:</p>
                            <p class="text-sm dark:text-slate-300 text-slate-700 whitespace-pre-line">{{ $usulan->catatan_admin }}</p>
                        </div>
                    @endif

                    @if($usulan->item_id)
                        <a href="{{ route('items.show', $usulan->item_id) }}"
                            class="w-full inline-flex items-center justify-center gap-2 dark:bg-slate-700/50 bg-slate-100 dark:hover:bg-slate-600 hover:bg-slate-200 dark:text-emerald-400 text-emerald-600 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 mb-4">
                            <i class="fas fa-external-link-alt text-xs"></i>
                            Lihat di Data Barang
                        </a>
                    @endif

                    {{-- Verification Info --}}
                    <div class="border-t dark:border-slate-700/50 border-slate-200 pt-4">
                        <p class="text-xs dark:text-slate-500 text-slate-400 font-medium mb-2">Informasi Verifikasi</p>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-sm">
                                <i class="fas fa-user-shield text-xs dark:text-slate-500 text-slate-400 w-4"></i>
                                <span class="dark:text-slate-400 text-slate-500">Diverifikasi oleh:</span>
                                <span class="dark:text-white text-slate-800 font-medium">{{ $usulan->verifiedBy->name ?? '-' }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <i class="fas fa-clock text-xs dark:text-slate-500 text-slate-400 w-4"></i>
                                <span class="dark:text-slate-400 text-slate-500">Waktu:</span>
                                <span class="dark:text-white text-slate-800 font-medium">{{ $usulan->verified_at ? \Carbon\Carbon::parse($usulan->verified_at)->format('d F Y, H:i') : '-' }} WIB</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Rejected Result --}}
            @if($usulan->status === 'ditolak')
                <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-lg border dark:border-red-500/30 border-red-200 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center">
                            <i class="fas fa-ban text-white text-sm"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold dark:text-red-400 text-red-700">Permintaan Ditolak</h3>
                            <p class="text-xs dark:text-slate-400 text-slate-500">Permintaan tidak dapat diproses</p>
                        </div>
                    </div>

                    @if($usulan->alasan_penolakan)
                        <div class="dark:bg-red-500/5 bg-red-50 rounded-xl p-4 border dark:border-red-500/20 border-red-200 mb-4">
                            <p class="text-xs dark:text-slate-500 text-slate-400 font-medium mb-1">Alasan Penolakan:</p>
                            <p class="text-sm dark:text-slate-300 text-slate-700 whitespace-pre-line">{{ $usulan->alasan_penolakan }}</p>
                        </div>
                    @endif

                    {{-- Verification Info --}}
                    <div class="border-t dark:border-slate-700/50 border-slate-200 pt-4">
                        <p class="text-xs dark:text-slate-500 text-slate-400 font-medium mb-2">Informasi Verifikasi</p>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-sm">
                                <i class="fas fa-user-shield text-xs dark:text-slate-500 text-slate-400 w-4"></i>
                                <span class="dark:text-slate-400 text-slate-500">Ditolak oleh:</span>
                                <span class="dark:text-white text-slate-800 font-medium">{{ $usulan->verifiedBy->name ?? '-' }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <i class="fas fa-clock text-xs dark:text-slate-500 text-slate-400 w-4"></i>
                                <span class="dark:text-slate-400 text-slate-500">Waktu:</span>
                                <span class="dark:text-white text-slate-800 font-medium">{{ $usulan->verified_at ? \Carbon\Carbon::parse($usulan->verified_at)->format('d F Y, H:i') : '-' }} WIB</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Back Button (always visible on sidebar) --}}
            <a href="{{ route('usulan-permintaan-barang.admin.index') }}"
                class="w-full inline-flex items-center justify-center gap-2 dark:bg-slate-700/50 bg-slate-100 dark:hover:bg-slate-600 hover:bg-slate-200 dark:text-slate-300 text-slate-600 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200">
                <i class="fas fa-arrow-left text-xs"></i>
                Kembali ke Daftar
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2"
            class="fixed bottom-6 right-6 z-50 bg-gradient-to-r from-emerald-500 to-teal-500 text-white px-6 py-3 rounded-xl shadow-2xl shadow-emerald-500/25 flex items-center gap-3">
            <i class="fas fa-check-circle"></i>
            <span class="text-sm font-medium">{{ session('success') }}</span>
            <button @click="show = false" class="ml-2 hover:text-white/80">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2"
            class="fixed bottom-6 right-6 z-50 bg-gradient-to-r from-red-500 to-rose-500 text-white px-6 py-3 rounded-xl shadow-2xl shadow-red-500/25 flex items-center gap-3">
            <i class="fas fa-exclamation-circle"></i>
            <span class="text-sm font-medium">{{ session('error') }}</span>
            <button @click="show = false" class="ml-2 hover:text-white/80">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
    @endif
@endsection
