@extends('layouts.app')

@section('title', 'Detail Permintaan Restok')
@section('header', 'Detail Permintaan Restok')
@section('breadcrumb', 'Detail permintaan restok barang')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Back Button -->
    <div>
        <a href="{{ route('permintaan-restok.index') }}" class="inline-flex items-center px-4 py-2 dark:bg-slate-700 bg-slate-200 dark:hover:bg-slate-600 hover:bg-slate-300 dark:text-white text-slate-700 rounded-xl text-sm font-semibold transition">
            <i class="fas fa-arrow-left mr-2"></i>Kembali
        </a>
    </div>

    <!-- Main Card -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-orange-500 to-amber-500 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <i class="fas fa-boxes-stacked mr-3"></i>
                        {{ $permintaan->kode_restok }}
                    </h2>
                    <p class="text-orange-100 text-sm mt-1">Permintaan Restok Barang</p>
                </div>
                <span class="px-4 py-2 rounded-full text-sm font-semibold border {{ \App\Models\PermintaanRestok::getStatusBadgeClass($permintaan->status) }}">
                    {{ $permintaan->status_label }}
                </span>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6 space-y-6">
            <!-- Info Barang -->
            <div>
                <h3 class="text-sm font-semibold dark:text-slate-400 text-slate-500 uppercase tracking-wider mb-3">Informasi Barang</h3>
                <div class="dark:bg-slate-700/30 bg-slate-50 rounded-xl p-4 border dark:border-slate-600/50 border-slate-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2 flex items-center gap-4">
                            @if($permintaan->item->photo)
                                <img src="{{ $permintaan->item->getPhotoUrl() }}" alt="{{ $permintaan->item->name }}" class="w-16 h-16 rounded-xl object-cover border dark:border-slate-600 border-slate-300">
                            @else
                                <div class="w-16 h-16 rounded-xl dark:bg-slate-700 bg-slate-200 flex items-center justify-center border dark:border-slate-600 border-slate-300">
                                    <i class="fas fa-box text-2xl dark:text-slate-500 text-slate-400"></i>
                                </div>
                            @endif
                            <div>
                                <p class="text-sm dark:text-slate-400 text-slate-500">Nama Barang</p>
                                <p class="font-semibold dark:text-white text-slate-800 text-lg">{{ $permintaan->item->name ?? '-' }}</p>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm dark:text-slate-400 text-slate-500">Kategori</p>
                            <p class="font-semibold dark:text-white text-slate-800">{{ $permintaan->item->category->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm dark:text-slate-400 text-slate-500">Stok Saat Ini</p>
                            <p class="font-bold text-lg {{ ($permintaan->item->stock ?? 0) == 0 ? 'text-red-400' : (($permintaan->item->stock ?? 0) <= ($permintaan->item->minimum_stock ?? 0) ? 'text-amber-400' : 'text-emerald-400') }}">
                                {{ $permintaan->item->stock ?? 0 }} {{ $permintaan->item->unit->symbol ?? '' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm dark:text-slate-400 text-slate-500">Stok Minimum</p>
                            <p class="font-semibold dark:text-white text-slate-800">{{ $permintaan->item->minimum_stock ?? 0 }} {{ $permintaan->item->unit->symbol ?? '' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Permintaan -->
            <div>
                <h3 class="text-sm font-semibold dark:text-slate-400 text-slate-500 uppercase tracking-wider mb-3">Detail Permintaan</h3>
                <div class="dark:bg-slate-700/30 bg-slate-50 rounded-xl p-4 border dark:border-slate-600/50 border-slate-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm dark:text-slate-400 text-slate-500">Jumlah Diminta</p>
                            <p class="font-bold text-lg dark:text-white text-slate-800">{{ $permintaan->jumlah_diminta }} {{ $permintaan->item->unit->symbol ?? '' }}</p>
                        </div>
                        <div>
                            <p class="text-sm dark:text-slate-400 text-slate-500">Prioritas</p>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ \App\Models\PermintaanRestok::getPrioritasBadgeClass($permintaan->prioritas) }}">
                                {{ $permintaan->prioritas_label }}
                            </span>
                        </div>
                        <div>
                            <p class="text-sm dark:text-slate-400 text-slate-500">Tanggal Permintaan</p>
                            <p class="font-semibold dark:text-white text-slate-800">{{ $permintaan->tanggal_permintaan->format('d F Y') }}</p>
                        </div>
                        <div>
                            <p class="text-sm dark:text-slate-400 text-slate-500">Diajukan Oleh</p>
                            <p class="font-semibold dark:text-white text-slate-800">{{ $permintaan->user->name ?? '-' }}</p>
                        </div>
                    </div>
                    @if($permintaan->keterangan)
                    <div class="mt-4 pt-4 border-t dark:border-slate-600/50 border-slate-200">
                        <p class="text-sm dark:text-slate-400 text-slate-500">Keterangan / Alasan</p>
                        <p class="dark:text-white text-slate-800 mt-1">{{ $permintaan->keterangan }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Info Verifikasi -->
            @if($permintaan->verified_by)
            <div>
                <h3 class="text-sm font-semibold dark:text-slate-400 text-slate-500 uppercase tracking-wider mb-3">Verifikasi Admin</h3>
                <div class="dark:bg-slate-700/30 bg-slate-50 rounded-xl p-4 border dark:border-slate-600/50 border-slate-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm dark:text-slate-400 text-slate-500">Diverifikasi Oleh</p>
                            <p class="font-semibold dark:text-white text-slate-800">{{ $permintaan->verifiedBy->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm dark:text-slate-400 text-slate-500">Tanggal Verifikasi</p>
                            <p class="font-semibold dark:text-white text-slate-800">{{ $permintaan->verified_at ? $permintaan->verified_at->format('d F Y H:i') : '-' }}</p>
                        </div>
                    </div>

                    @if($permintaan->catatan_admin)
                    <div class="mt-4 pt-4 border-t dark:border-slate-600/50 border-slate-200">
                        <p class="text-sm dark:text-slate-400 text-slate-500">Catatan Admin</p>
                        <p class="dark:text-white text-slate-800 mt-1">{{ $permintaan->catatan_admin }}</p>
                    </div>
                    @endif

                    @if($permintaan->alasan_penolakan)
                    <div class="mt-4 pt-4 border-t dark:border-slate-600/50 border-slate-200">
                        <p class="text-sm text-red-400">Alasan Penolakan</p>
                        <p class="text-red-300 mt-1">{{ $permintaan->alasan_penolakan }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Status Tersedia -->
            @if($permintaan->is_tersedia || $permintaan->is_dibaca)
            <div class="dark:bg-emerald-500/10 bg-emerald-50 border dark:border-emerald-500/30 border-emerald-200 rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <i class="fas fa-check-circle text-emerald-400 text-xl"></i>
                    <div>
                        <p class="font-semibold dark:text-emerald-300 text-emerald-700">Barang Sudah Tersedia!</p>
                        <p class="text-sm dark:text-emerald-400 text-emerald-600 mt-1">Barang yang Anda minta sudah memiliki stok. Stok saat ini: <strong>{{ $permintaan->item->stock ?? 0 }} {{ $permintaan->item->unit->symbol ?? '' }}</strong></p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Actions -->
            @if($permintaan->can_cancel)
            <div class="pt-4 border-t dark:border-slate-700/50 border-slate-200">
                <form action="{{ route('permintaan-restok.cancel', $permintaan->id) }}" method="POST" onsubmit="return confirm('Yakin ingin membatalkan permintaan restok ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-6 py-3 bg-red-500 hover:bg-red-600 text-white rounded-xl font-semibold transition">
                        <i class="fas fa-times mr-2"></i>Batalkan Permintaan
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
