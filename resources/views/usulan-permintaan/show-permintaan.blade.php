@extends('layouts.app')

@section('title', 'Detail Permintaan Barang Habis Pakai')
@section('header', 'Detail Permintaan Barang Habis Pakai')
@section('breadcrumb', 'Lihat detail permintaan barang habis pakai')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Status Card -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r {{ $usulan->status === 'disetujui' ? 'from-emerald-500/20 to-green-500/20' : ($usulan->status === 'ditolak' ? 'from-red-500/20 to-rose-500/20' : ($usulan->status === 'revisi' ? 'from-purple-500/20 to-fuchsia-500/20' : 'from-amber-500/20 to-orange-500/20')) }} border-b dark:border-slate-700/50 border-slate-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-14 h-14 bg-gradient-to-br {{ $usulan->status === 'disetujui' ? 'from-emerald-500 to-green-500' : ($usulan->status === 'ditolak' ? 'from-red-500 to-rose-500' : ($usulan->status === 'revisi' ? 'from-purple-500 to-fuchsia-500' : 'from-amber-500 to-orange-500')) }} rounded-xl flex items-center justify-center mr-4 shadow-lg">
                        <i class="fas {{ $usulan->status === 'disetujui' ? 'fa-check-circle' : ($usulan->status === 'ditolak' ? 'fa-times-circle' : ($usulan->status === 'revisi' ? 'fa-edit' : 'fa-clock')) }} text-white text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm dark:text-slate-400 text-slate-500">Kode Permintaan</p>
                        <h3 class="text-xl font-bold dark:text-white text-slate-800 font-mono">{{ $usulan->kode_permintaan }}</h3>
                    </div>
                </div>
                <div class="text-right">
                    <span class="px-4 py-2 rounded-full text-sm font-semibold border {{ \App\Models\UsulanPermintaanBarang::getStatusBadgeClass($usulan->status) }}">
                        {{ $usulan->status_label }}
                    </span>
                    <p class="text-sm dark:text-slate-400 text-slate-500 mt-2">
                        <i class="fas fa-calendar mr-1"></i>{{ $usulan->tanggal_permintaan->format('d F Y') }}
                    </p>
                </div>
            </div>
        </div>

        @if($usulan->status === 'ditolak' && $usulan->alasan_penolakan)
        <div class="px-6 py-4 bg-red-500/10 border-b dark:border-slate-700/50 border-slate-200">
            <div class="flex items-start">
                <i class="fas fa-times-circle text-red-400 mt-1 mr-3"></i>
                <div>
                    <p class="font-semibold text-red-400">Alasan Penolakan:</p>
                    <p class="dark:text-red-300 text-red-700 mt-1">{{ $usulan->alasan_penolakan }}</p>
                </div>
            </div>
        </div>
        @endif

        @if($usulan->status === 'revisi' && $usulan->permintaan_revisi)
        <div class="px-6 py-4 bg-purple-500/10 border-b dark:border-slate-700/50 border-slate-200">
            <div class="flex items-start">
                <i class="fas fa-exclamation-triangle text-purple-400 mt-1 mr-3"></i>
                <div>
                    <p class="font-semibold text-purple-400">Permintaan Revisi dari Admin:</p>
                    <p class="dark:text-purple-300 text-purple-700 mt-1">{{ $usulan->permintaan_revisi }}</p>
                </div>
            </div>
        </div>
        @endif

        @if($usulan->status === 'disetujui' && $usulan->catatan_admin)
        <div class="px-6 py-4 bg-emerald-500/10 border-b dark:border-slate-700/50 border-slate-200">
            <div class="flex items-start">
                <i class="fas fa-check-circle text-emerald-400 mt-1 mr-3"></i>
                <div>
                    <p class="font-semibold text-emerald-400">Catatan Admin:</p>
                    <p class="dark:text-emerald-300 text-emerald-700 mt-1">{{ $usulan->catatan_admin }}</p>
                    @if($usulan->item_id && $usulan->item)
                    <p class="dark:text-emerald-300 text-emerald-700 mt-2">
                        Barang terkait: <a href="{{ route('items.show', $usulan->item_id) }}" class="underline hover:no-underline font-semibold">Lihat Detail Barang</a>
                    </p>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Detail Permintaan -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b dark:border-slate-700/50 border-slate-200">
            <h4 class="font-bold dark:text-white text-slate-800 flex items-center">
                <i class="fas fa-box text-emerald-400 mr-2"></i>Detail Permintaan
            </h4>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="text-sm dark:text-slate-400 text-slate-500">Pengusul</label>
                    <p class="font-semibold dark:text-white text-slate-800 mt-1">
                        <i class="fas fa-user text-blue-400 mr-1"></i>{{ $usulan->user->name }}
                    </p>
                </div>
                <div>
                    <label class="text-sm dark:text-slate-400 text-slate-500">Ruangan Tujuan</label>
                    <p class="font-semibold dark:text-white text-slate-800 mt-1">
                        <i class="fas fa-door-open text-blue-400 mr-1"></i>{{ $usulan->ruangan->nama_ruangan }}
                    </p>
                </div>
                <div>
                    <label class="text-sm dark:text-slate-400 text-slate-500">Nama Barang</label>
                    <p class="font-semibold dark:text-white text-slate-800 mt-1">{{ $usulan->nama_barang }}</p>
                </div>
                <div>
                    <label class="text-sm dark:text-slate-400 text-slate-500">Jumlah</label>
                    <p class="font-semibold dark:text-white text-slate-800 mt-1">{{ $usulan->jumlah }}</p>
                </div>
                <div>
                    <label class="text-sm dark:text-slate-400 text-slate-500">Prioritas</label>
                    <p class="mt-1">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ \App\Models\UsulanPermintaanBarang::getPrioritasBadgeClass($usulan->prioritas) }}">
                            {{ $usulan->prioritas_label }}
                        </span>
                    </p>
                </div>
                <div>
                    <label class="text-sm dark:text-slate-400 text-slate-500">Tanggal Permintaan</label>
                    <p class="font-semibold dark:text-white text-slate-800 mt-1">
                        <i class="fas fa-calendar text-slate-400 mr-1"></i>{{ $usulan->tanggal_permintaan->format('d F Y') }}
                    </p>
                </div>
                
                @if($usulan->foto_barang)
                <div class="md:col-span-2">
                    <label class="text-sm dark:text-slate-400 text-slate-500 mb-2 block">Foto Barang</label>
                    <div class="relative inline-block group">
                        <img src="{{ $usulan->tipe_foto_barang === 'url' ? $usulan->foto_barang : asset($usulan->foto_barang) }}" 
                             alt="Foto {{ $usulan->nama_barang }}" 
                             class="h-48 w-auto object-cover rounded-xl border dark:border-slate-600 border-slate-300 shadow-md">
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity rounded-xl flex items-center justify-center">
                            <a href="{{ $usulan->tipe_foto_barang === 'url' ? $usulan->foto_barang : asset($usulan->foto_barang) }}" target="_blank" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg backdrop-blur-sm transition-colors text-sm font-semibold">
                                <i class="fas fa-external-link-alt mr-2"></i>Lihat Penuh
                            </a>
                        </div>
                    </div>
                </div>
                @endif

                <div class="md:col-span-2">
                    <label class="text-sm dark:text-slate-400 text-slate-500">Keterangan/Alasan</label>
                    <p class="dark:text-slate-300 text-slate-700 mt-1">{{ $usulan->keterangan ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Verifikasi -->
    @if($usulan->verified_by)
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b dark:border-slate-700/50 border-slate-200">
            <h4 class="font-bold dark:text-white text-slate-800 flex items-center">
                <i class="fas fa-user-shield text-blue-400 mr-2"></i>Informasi Verifikasi
            </h4>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="text-sm dark:text-slate-400 text-slate-500">Diverifikasi Oleh</label>
                    <p class="font-semibold dark:text-white text-slate-800 mt-1">{{ $usulan->verifiedBy->name }}</p>
                </div>
                <div>
                    <label class="text-sm dark:text-slate-400 text-slate-500">Waktu Verifikasi</label>
                    <p class="font-semibold dark:text-white text-slate-800 mt-1">{{ $usulan->verified_at->format('d F Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Action Buttons -->
    <div class="flex items-center justify-between">
        <a href="{{ route('usulan-permintaan-barang.index') }}" class="px-6 py-3 dark:bg-slate-700 bg-slate-200 dark:hover:bg-slate-600 hover:bg-slate-300 dark:text-white text-slate-700 rounded-xl font-semibold transition">
            <i class="fas fa-arrow-left mr-2"></i>Kembali
        </a>
        @if($usulan->can_edit && $usulan->user_id === auth()->id())
        <div class="flex gap-3">
            <a href="{{ route('usulan-permintaan-barang.edit', $usulan->id) }}" class="px-6 py-3 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-semibold transition">
                <i class="fas fa-edit mr-2"></i>Edit Permintaan
            </a>
            <form action="{{ route('usulan-permintaan-barang.cancel', $usulan->id) }}" method="POST" onsubmit="return confirm('Yakin ingin membatalkan permintaan ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-6 py-3 bg-red-500 hover:bg-red-600 text-white rounded-xl font-semibold transition">
                    <i class="fas fa-times mr-2"></i>Batalkan
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
