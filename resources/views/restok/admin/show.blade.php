@extends('layouts.app')

@section('title', 'Detail Permintaan Restok - Admin')
@section('header', 'Detail Permintaan Restok')
@section('breadcrumb', 'Verifikasi permintaan restok barang')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Back Button -->
    <div>
        <a href="{{ route('permintaan-restok.admin.index') }}" class="inline-flex items-center px-4 py-2 dark:bg-slate-700 bg-slate-200 dark:hover:bg-slate-600 hover:bg-slate-300 dark:text-white text-slate-700 rounded-xl text-sm font-semibold transition">
            <i class="fas fa-arrow-left mr-2"></i>Kembali
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Permintaan Detail -->
            <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
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

                <div class="p-6 space-y-6">
                    <!-- Pengaju -->
                    <div>
                        <h3 class="text-sm font-semibold dark:text-slate-400 text-slate-500 uppercase tracking-wider mb-3">Pengaju</h3>
                        <div class="dark:bg-slate-700/30 bg-slate-50 rounded-xl p-4 border dark:border-slate-600/50 border-slate-200">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm dark:text-slate-400 text-slate-500">Nama</p>
                                    <p class="font-semibold dark:text-white text-slate-800">{{ $permintaan->user->name ?? '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm dark:text-slate-400 text-slate-500">Role</p>
                                    <p class="font-semibold dark:text-white text-slate-800">{{ $permintaan->user->getRoleName() ?? '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Info Barang -->
                    <div>
                        <h3 class="text-sm font-semibold dark:text-slate-400 text-slate-500 uppercase tracking-wider mb-3">Informasi Barang</h3>
                        <div class="dark:bg-slate-700/30 bg-slate-50 rounded-xl p-4 border dark:border-slate-600/50 border-slate-200">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm dark:text-slate-400 text-slate-500">Nama Barang</p>
                                    <p class="font-semibold dark:text-white text-slate-800 text-lg">{{ $permintaan->item->name ?? '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm dark:text-slate-400 text-slate-500">Kode Barang</p>
                                    <p class="font-mono font-semibold dark:text-white text-slate-800">{{ $permintaan->item->code ?? '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm dark:text-slate-400 text-slate-500">Kategori</p>
                                    <p class="font-semibold dark:text-white text-slate-800">{{ $permintaan->item->category->name ?? '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm dark:text-slate-400 text-slate-500">Satuan</p>
                                    <p class="font-semibold dark:text-white text-slate-800">{{ $permintaan->item->unit->name ?? '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm dark:text-slate-400 text-slate-500">Stok Saat Ini</p>
                                    <p class="font-bold text-2xl {{ ($permintaan->item->stock ?? 0) == 0 ? 'text-red-400' : (($permintaan->item->stock ?? 0) <= ($permintaan->item->minimum_stock ?? 0) ? 'text-amber-400' : 'text-emerald-400') }}">
                                        {{ $permintaan->item->stock ?? 0 }} <span class="text-sm font-normal">{{ $permintaan->item->unit->symbol ?? '' }}</span>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm dark:text-slate-400 text-slate-500">Stok Minimum</p>
                                    <p class="font-semibold dark:text-white text-slate-800">{{ $permintaan->item->minimum_stock ?? 0 }} {{ $permintaan->item->unit->symbol ?? '' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detail Permintaan -->
                    <div>
                        <h3 class="text-sm font-semibold dark:text-slate-400 text-slate-500 uppercase tracking-wider mb-3">Detail Permintaan</h3>
                        <div class="dark:bg-slate-700/30 bg-slate-50 rounded-xl p-4 border dark:border-slate-600/50 border-slate-200">
                            <div class="grid grid-cols-2 gap-4">
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
                            </div>

                            @if($permintaan->keterangan)
                            <div class="mt-4 pt-4 border-t dark:border-slate-600/50 border-slate-200">
                                <p class="text-sm dark:text-slate-400 text-slate-500">Keterangan / Alasan</p>
                                <p class="dark:text-white text-slate-800 mt-1">{{ $permintaan->keterangan }}</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Verifikasi Info (jika sudah diverifikasi) -->
                    @if($permintaan->verified_by)
                    <div>
                        <h3 class="text-sm font-semibold dark:text-slate-400 text-slate-500 uppercase tracking-wider mb-3">Riwayat Verifikasi</h3>
                        <div class="dark:bg-slate-700/30 bg-slate-50 rounded-xl p-4 border dark:border-slate-600/50 border-slate-200">
                            <div class="grid grid-cols-2 gap-4">
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
                </div>
            </div>
        </div>

        <!-- Action Panel -->
        <div class="space-y-6">
            @if($permintaan->is_pending)
            <!-- Approve Form -->
            <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
                <div class="dark:bg-emerald-500/10 bg-emerald-50 px-6 py-3 border-b dark:border-slate-700/50 border-slate-200">
                    <h3 class="font-semibold dark:text-emerald-400 text-emerald-700 flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>Setujui Permintaan
                    </h3>
                </div>
                <form action="{{ route('permintaan-restok.admin.approve', $permintaan->id) }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">Catatan Admin</label>
                        <textarea name="catatan_admin" rows="3"
                            class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent resize-none"
                            placeholder="Catatan opsional..."></textarea>
                    </div>
                    <p class="text-xs dark:text-slate-400 text-slate-500">
                        <i class="fas fa-info-circle mr-1"></i>
                        Setelah disetujui, status akan berubah menjadi "Disetujui (Menunggu Stok)". 
                        Ketika barang masuk diinput, staff otomatis akan menerima notifikasi.
                    </p>
                    <button type="submit" onclick="return confirm('Yakin setujui permintaan restok ini?')"
                        class="w-full px-6 py-3 bg-gradient-to-r from-emerald-500 to-green-500 hover:from-emerald-600 hover:to-green-600 text-white rounded-xl font-semibold transition shadow-lg shadow-emerald-500/25">
                        <i class="fas fa-check mr-2"></i>Setujui Permintaan
                    </button>
                </form>
            </div>

            <!-- Reject Form -->
            <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
                <div class="dark:bg-red-500/10 bg-red-50 px-6 py-3 border-b dark:border-slate-700/50 border-slate-200">
                    <h3 class="font-semibold dark:text-red-400 text-red-700 flex items-center">
                        <i class="fas fa-times-circle mr-2"></i>Tolak Permintaan
                    </h3>
                </div>
                <form action="{{ route('permintaan-restok.admin.reject', $permintaan->id) }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                            Alasan Penolakan <span class="text-red-400">*</span>
                        </label>
                        <textarea name="alasan_penolakan" rows="3" required
                            class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none"
                            placeholder="Jelaskan alasan penolakan..."></textarea>
                        @error('alasan_penolakan')
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" onclick="return confirm('Yakin tolak permintaan restok ini?')"
                        class="w-full px-6 py-3 bg-gradient-to-r from-red-500 to-rose-500 hover:from-red-600 hover:to-rose-600 text-white rounded-xl font-semibold transition shadow-lg shadow-red-500/25">
                        <i class="fas fa-times mr-2"></i>Tolak Permintaan
                    </button>
                </form>
            </div>
            @else
            <!-- Status Info Card -->
            <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg p-6">
                <div class="text-center">
                    @if($permintaan->is_disetujui)
                        <div class="w-16 h-16 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-thumbs-up text-blue-400 text-2xl"></i>
                        </div>
                        <p class="font-semibold dark:text-blue-400 text-blue-600">Permintaan Disetujui</p>
                        <p class="text-sm dark:text-slate-400 text-slate-500 mt-2">Menunggu stok masuk. Staff akan otomatis dinotifikasi ketika barang tersedia.</p>
                    @elseif($permintaan->is_ditolak)
                        <div class="w-16 h-16 bg-red-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-times-circle text-red-400 text-2xl"></i>
                        </div>
                        <p class="font-semibold dark:text-red-400 text-red-600">Permintaan Ditolak</p>
                    @elseif($permintaan->is_tersedia)
                        <div class="w-16 h-16 bg-emerald-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-check-circle text-emerald-400 text-2xl"></i>
                        </div>
                        <p class="font-semibold dark:text-emerald-400 text-emerald-600">Barang Tersedia</p>
                        <p class="text-sm dark:text-slate-400 text-slate-500 mt-2">Staff belum membaca notifikasi.</p>
                    @elseif($permintaan->is_dibaca)
                        <div class="w-16 h-16 bg-slate-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-check-double text-slate-400 text-2xl"></i>
                        </div>
                        <p class="font-semibold dark:text-slate-400 text-slate-600">Selesai - Sudah Dibaca</p>
                        <p class="text-sm dark:text-slate-400 text-slate-500 mt-2">Staff sudah menerima notifikasi barang tersedia.</p>
                    @endif
                </div>
            </div>

            <!-- Quick Link -->
            @if($permintaan->is_disetujui)
            <a href="{{ route('items.show', $permintaan->item_id) }}" class="block dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg p-4 dark:hover:bg-slate-700/50 hover:bg-slate-50 transition">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-box text-blue-400"></i>
                    </div>
                    <div>
                        <p class="font-semibold dark:text-white text-slate-800 text-sm">Lihat Data Barang</p>
                        <p class="text-xs dark:text-slate-400 text-slate-500">Input barang masuk untuk memenuhi permintaan ini</p>
                    </div>
                    <i class="fas fa-chevron-right dark:text-slate-500 text-slate-400 ml-auto"></i>
                </div>
            </a>
            @endif
            @endif
        </div>
    </div>
</div>
@endsection
