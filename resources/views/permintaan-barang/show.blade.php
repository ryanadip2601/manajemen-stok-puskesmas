@extends('layouts.app')

@section('title', 'Detail Permintaan Barang')
@section('header', 'Detail Permintaan Barang')
@section('breadcrumb', 'Detail permintaan barang Anda')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Back Button -->
    <div>
        <a href="{{ route('permintaan-barang.index') }}" class="inline-flex items-center px-4 py-2 dark:bg-slate-700 bg-slate-200 dark:hover:bg-slate-600 hover:bg-slate-300 dark:text-white text-slate-700 rounded-xl font-semibold transition text-sm">
            <i class="fas fa-arrow-left mr-2"></i>Kembali ke Daftar
        </a>
    </div>

    <!-- Header Card -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r {{ $permintaan->is_barang_baru ? 'from-emerald-500/20 to-teal-500/20' : 'from-orange-500/20 to-amber-500/20' }} border-b dark:border-slate-700/50 border-slate-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-14 h-14 bg-gradient-to-br {{ $permintaan->is_barang_baru ? 'from-emerald-500 to-teal-500' : 'from-orange-500 to-amber-500' }} rounded-xl flex items-center justify-center mr-4 shadow-lg">
                        <i class="fas {{ $permintaan->is_barang_baru ? 'fa-plus-circle' : 'fa-boxes-stacked' }} text-white text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm dark:text-slate-400 text-slate-500">Kode Permintaan</p>
                        <h3 class="text-xl font-bold dark:text-white text-slate-800 font-mono">{{ $permintaan->kode_permintaan }}</h3>
                    </div>
                </div>
                <div class="text-right space-y-2">
                    <div class="flex items-center gap-2 justify-end">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold border {{ \App\Models\PermintaanBarang::getTipeBadgeClass($permintaan->tipe) }}">
                            {{ $permintaan->tipe_label }}
                        </span>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold border {{ \App\Models\PermintaanBarang::getStatusBadgeClass($permintaan->status) }}">
                            {{ $permintaan->status_label }}
                        </span>
                    </div>
                    <p class="text-sm dark:text-slate-400 text-slate-500">
                        <i class="fas fa-calendar mr-1"></i>{{ $permintaan->tanggal_permintaan->format('d F Y') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Informasi Barang -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b dark:border-slate-700/50 border-slate-200">
            <h4 class="font-bold dark:text-white text-slate-800 flex items-center">
                <i class="fas fa-box text-emerald-400 mr-2"></i>Informasi Barang
            </h4>
        </div>
        <div class="p-6">
            <div class="flex flex-col md:flex-row gap-6">
                <!-- Foto (Jika barang manual punya foto) -->
                @if($permintaan->is_barang_baru && $permintaan->foto_barang)
                <div class="w-full md:w-48 flex-shrink-0">
                    <p class="text-sm dark:text-slate-400 text-slate-500 mb-2">Foto / Referensi Barang</p>
                    <img src="{{ $permintaan->getFotoUrl() }}" alt="Foto {{ $permintaan->nama_barang }}" 
                         class="w-full h-48 object-cover rounded-xl border dark:border-slate-600 border-slate-300 shadow-sm"
                         onerror="this.src='https://via.placeholder.com/300?text=Image+Not+Found'; this.onerror='';">
                </div>
                @endif
                
                <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-6 content-start">
                    <div class="{{ ($permintaan->is_barang_baru && $permintaan->foto_barang) ? 'col-span-1 md:col-span-2' : '' }}">
                        <label class="text-sm dark:text-slate-400 text-slate-500">Nama Barang</label>
                        <div class="flex items-center gap-3 mt-1">
                            @if($permintaan->is_restok && $permintaan->item && $permintaan->item->photo)
                                <img src="{{ $permintaan->item->getPhotoUrl() }}" alt="{{ $permintaan->nama_barang }}" class="w-12 h-12 rounded-xl object-cover border dark:border-slate-600 border-slate-300">
                            @endif
                            <p class="font-semibold dark:text-white text-slate-800">{{ $permintaan->nama_barang }}</p>
                        </div>
                    </div>

                    @if($permintaan->is_restok && $permintaan->item)
                    <div>
                        <label class="text-sm dark:text-slate-400 text-slate-500">Kode Barang</label>
                        <p class="font-semibold dark:text-white text-slate-800 mt-1 font-mono">{{ $permintaan->item->code }}</p>
                    </div>
                    <div>
                        <label class="text-sm dark:text-slate-400 text-slate-500">Kategori</label>
                        <p class="font-semibold dark:text-white text-slate-800 mt-1">{{ $permintaan->item->category->name ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-sm dark:text-slate-400 text-slate-500">Satuan</label>
                        <p class="font-semibold dark:text-white text-slate-800 mt-1">{{ $permintaan->item->unit->name ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-sm dark:text-slate-400 text-slate-500">Stok Saat Ini</label>
                        <p class="font-bold text-lg mt-1 {{ $permintaan->item->stock === 0 ? 'text-red-400' : ($permintaan->item->stock <= $permintaan->item->minimum_stock ? 'text-amber-400' : 'text-emerald-400') }}">
                            {{ $permintaan->item->stock }} {{ $permintaan->item->unit->symbol ?? '' }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm dark:text-slate-400 text-slate-500">Stok Minimum</label>
                        <p class="font-semibold dark:text-white text-slate-800 mt-1">{{ $permintaan->item->minimum_stock }} {{ $permintaan->item->unit->symbol ?? '' }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Ruangan (barang baru with ruangan) -->
    @if($permintaan->is_barang_baru && $permintaan->ruangan)
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b dark:border-slate-700/50 border-slate-200">
            <h4 class="font-bold dark:text-white text-slate-800 flex items-center">
                <i class="fas fa-door-open text-blue-400 mr-2"></i>Ruangan Tujuan
            </h4>
        </div>
        <div class="p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center mr-4">
                    <i class="fas fa-door-open text-blue-400 text-xl"></i>
                </div>
                <div>
                    <p class="font-semibold dark:text-white text-slate-800">{{ $permintaan->ruangan->nama_ruangan }}</p>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Ruangan tujuan untuk barang baru</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Detail Permintaan -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b dark:border-slate-700/50 border-slate-200">
            <h4 class="font-bold dark:text-white text-slate-800 flex items-center">
                <i class="fas fa-clipboard-list text-amber-400 mr-2"></i>Detail Permintaan
            </h4>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="text-sm dark:text-slate-400 text-slate-500">Jumlah</label>
                    <p class="font-bold dark:text-white text-slate-800 text-lg mt-1">{{ $permintaan->jumlah }}</p>
                </div>
                <div>
                    <label class="text-sm dark:text-slate-400 text-slate-500">Prioritas</label>
                    <p class="mt-1">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ \App\Models\PermintaanBarang::getPrioritasBadgeClass($permintaan->prioritas) }}">
                            {{ $permintaan->prioritas_label }}
                        </span>
                    </p>
                </div>
                <div>
                    <label class="text-sm dark:text-slate-400 text-slate-500">Tanggal Permintaan</label>
                    <p class="font-semibold dark:text-white text-slate-800 mt-1">
                        <i class="fas fa-calendar text-slate-400 mr-1"></i>{{ $permintaan->tanggal_permintaan->format('d F Y') }}
                    </p>
                </div>
                @if($permintaan->keterangan)
                <div class="md:col-span-3">
                    <label class="text-sm dark:text-slate-400 text-slate-500">Keterangan / Alasan</label>
                    <p class="dark:text-slate-300 text-slate-700 mt-1">{{ $permintaan->keterangan }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Permintaan Revisi -->
    @if($permintaan->is_revisi && $permintaan->permintaan_revisi)
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
        <div class="px-6 py-4 bg-purple-500/10">
            <div class="flex items-start">
                <i class="fas fa-exclamation-triangle text-purple-400 mt-1 mr-3"></i>
                <div>
                    <p class="font-semibold text-purple-400">Permintaan Revisi dari Admin:</p>
                    <p class="dark:text-purple-300 text-purple-700 mt-1">{{ $permintaan->permintaan_revisi }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Riwayat Verifikasi -->
    @if($permintaan->verified_by)
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b dark:border-slate-700/50 border-slate-200">
            <h4 class="font-bold dark:text-white text-slate-800 flex items-center">
                <i class="fas fa-user-shield text-blue-400 mr-2"></i>Riwayat Verifikasi
            </h4>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="text-sm dark:text-slate-400 text-slate-500">Diverifikasi Oleh</label>
                    <p class="font-semibold dark:text-white text-slate-800 mt-1">{{ $permintaan->verifiedBy->name }}</p>
                </div>
                <div>
                    <label class="text-sm dark:text-slate-400 text-slate-500">Waktu Verifikasi</label>
                    <p class="font-semibold dark:text-white text-slate-800 mt-1">{{ $permintaan->verified_at->format('d F Y H:i') }}</p>
                </div>
                @if($permintaan->catatan_admin)
                <div class="md:col-span-2">
                    <label class="text-sm dark:text-slate-400 text-slate-500">Catatan Admin</label>
                    <p class="dark:text-slate-300 text-slate-700 mt-1">{{ $permintaan->catatan_admin }}</p>
                </div>
                @endif
                @if($permintaan->alasan_penolakan)
                <div class="md:col-span-2">
                    <label class="text-sm dark:text-slate-400 text-slate-500">Alasan Penolakan</label>
                    <div class="mt-1 p-3 bg-red-500/10 border border-red-500/30 rounded-xl">
                        <p class="text-red-400">{{ $permintaan->alasan_penolakan }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Created Item Link -->
    @if($permintaan->created_item_id && $permintaan->createdItem)
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
        <div class="px-6 py-4 bg-emerald-500/10">
            <div class="flex items-start">
                <i class="fas fa-check-circle text-emerald-400 mt-1 mr-3"></i>
                <div>
                    <p class="font-semibold text-emerald-400">Barang Telah Dibuat di Sistem:</p>
                    <p class="dark:text-emerald-300 text-emerald-700 mt-1">
                        Kode Barang: <span class="font-mono font-bold">{{ $permintaan->createdItem->code }}</span>
                        - <span class="font-semibold">{{ $permintaan->createdItem->name }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Action Buttons -->
    <div class="flex items-center justify-between">
        <a href="{{ route('permintaan-barang.index') }}" class="px-6 py-3 dark:bg-slate-700 bg-slate-200 dark:hover:bg-slate-600 hover:bg-slate-300 dark:text-white text-slate-700 rounded-xl font-semibold transition">
            <i class="fas fa-arrow-left mr-2"></i>Kembali
        </a>
        @if($permintaan->can_edit)
        <div class="flex gap-3">
            <a href="{{ route('permintaan-barang.edit', $permintaan->id) }}" class="px-6 py-3 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-semibold transition">
                <i class="fas fa-edit mr-2"></i>Edit Permintaan
            </a>
            @if($permintaan->can_cancel)
            <form action="{{ route('permintaan-barang.cancel', $permintaan->id) }}" method="POST" onsubmit="return confirm('Yakin ingin membatalkan permintaan ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-6 py-3 bg-red-500 hover:bg-red-600 text-white rounded-xl font-semibold transition">
                    <i class="fas fa-times mr-2"></i>Batalkan
                </button>
            </form>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection
