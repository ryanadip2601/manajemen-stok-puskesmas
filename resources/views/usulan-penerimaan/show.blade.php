@extends('layouts.app')

@section('title', 'Detail Usulan Penerimaan')
@section('header', 'Detail Usulan Penerimaan')
@section('breadcrumb', 'Lihat detail usulan penerimaan barang')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Status Card -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r {{ $usulan->is_disetujui ? 'from-emerald-500/20 to-green-500/20' : ($usulan->is_ditolak ? 'from-red-500/20 to-rose-500/20' : ($usulan->is_revisi ? 'from-purple-500/20 to-fuchsia-500/20' : 'from-amber-500/20 to-orange-500/20')) }} border-b dark:border-slate-700/50 border-slate-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-14 h-14 bg-gradient-to-br {{ $usulan->is_disetujui ? 'from-emerald-500 to-green-500' : ($usulan->is_ditolak ? 'from-red-500 to-rose-500' : ($usulan->is_revisi ? 'from-purple-500 to-fuchsia-500' : 'from-amber-500 to-orange-500')) }} rounded-xl flex items-center justify-center mr-4 shadow-lg">
                        <i class="fas {{ $usulan->is_disetujui ? 'fa-check-circle' : ($usulan->is_ditolak ? 'fa-times-circle' : ($usulan->is_revisi ? 'fa-edit' : 'fa-clock')) }} text-white text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm dark:text-slate-400 text-slate-500">Kode Usulan</p>
                        <h3 class="text-xl font-bold dark:text-white text-slate-800 font-mono">{{ $usulan->kode_usulan }}</h3>
                    </div>
                </div>
                <div class="text-right">
                    <span class="px-4 py-2 rounded-full text-sm font-semibold border {{ \App\Models\UsulanPenerimaanBarang::getStatusBadgeClass($usulan->status) }}">
                        {{ $usulan->status_label }}
                    </span>
                    <p class="text-sm dark:text-slate-400 text-slate-500 mt-2">
                        <i class="fas fa-calendar mr-1"></i>{{ $usulan->tanggal_usulan->format('d F Y') }}
                    </p>
                </div>
            </div>
        </div>

        @if($usulan->is_revisi && $usulan->permintaan_revisi)
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

        @if($usulan->is_ditolak && $usulan->alasan_penolakan)
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

        @if($usulan->is_disetujui && $usulan->barangAset)
        <div class="px-6 py-4 bg-emerald-500/10 border-b dark:border-slate-700/50 border-slate-200">
            <div class="flex items-start">
                <i class="fas fa-check-circle text-emerald-400 mt-1 mr-3"></i>
                <div>
                    <p class="font-semibold text-emerald-400">Barang Aset Telah Dibuat:</p>
                    <p class="dark:text-emerald-300 text-emerald-700 mt-1">
                        Kode Aset: <span class="font-mono font-bold">{{ $usulan->barangAset->kode_aset }}</span>
                        @if(auth()->user()->canManageWarehouse())
                            - <a href="{{ route('barang-aset.show', $usulan->barangAset->id) }}" class="underline hover:no-underline">Lihat Detail</a>
                        @endif
                    </p>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Detail Barang -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b dark:border-slate-700/50 border-slate-200">
            <h4 class="font-bold dark:text-white text-slate-800 flex items-center">
                <i class="fas fa-box text-emerald-400 mr-2"></i>Data Barang
            </h4>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="text-sm dark:text-slate-400 text-slate-500">Nama Barang</label>
                    <p class="font-semibold dark:text-white text-slate-800 mt-1">{{ $usulan->nama_barang }}</p>
                </div>
                <div>
                    <label class="text-sm dark:text-slate-400 text-slate-500">Merk/Brand</label>
                    <p class="font-semibold dark:text-white text-slate-800 mt-1">{{ $usulan->merk ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm dark:text-slate-400 text-slate-500">Ruangan Tujuan</label>
                    <p class="font-semibold dark:text-white text-slate-800 mt-1">
                        <i class="fas fa-door-open text-blue-400 mr-1"></i>{{ $usulan->ruangan->nama_ruangan }}
                    </p>
                </div>
                <div>
                    <label class="text-sm dark:text-slate-400 text-slate-500">Kategori Aset</label>
                    <p class="font-semibold dark:text-white text-slate-800 mt-1">{{ $usulan->kategoriAset->nama_kategori ?? 'Belum ditentukan' }}</p>
                </div>
                <div>
                    <label class="text-sm dark:text-slate-400 text-slate-500">Jumlah</label>
                    <p class="font-semibold dark:text-white text-slate-800 mt-1">{{ $usulan->jumlah }} unit</p>
                </div>
                <div>
                    <label class="text-sm dark:text-slate-400 text-slate-500">Kondisi</label>
                    <p class="mt-1">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ \App\Models\UsulanPenerimaanBarang::getKondisiBadgeClass($usulan->kondisi_barang) }}">
                            {{ $usulan->kondisi_label }}
                        </span>
                    </p>
                </div>
                <div>
                    <label class="text-sm dark:text-slate-400 text-slate-500">Kelengkapan</label>
                    <p class="font-semibold dark:text-white text-slate-800 mt-1">{{ $usulan->kelengkapan_label }}</p>
                </div>
                <div>
                    <label class="text-sm dark:text-slate-400 text-slate-500">Prioritas</label>
                    <p class="mt-1">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ \App\Models\UsulanPenerimaanBarang::getPrioritasBadgeClass($usulan->prioritas) }}">
                            {{ $usulan->prioritas_label }}
                        </span>
                    </p>
                </div>
                <div>
                    <label class="text-sm dark:text-slate-400 text-slate-500">Estimasi Harga</label>
                    <p class="font-semibold dark:text-white text-slate-800 mt-1">
                        @if($usulan->estimasi_harga)
                            Rp {{ number_format($usulan->estimasi_harga, 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </p>
                </div>
                <div>
                    <label class="text-sm dark:text-slate-400 text-slate-500">Sumber Barang</label>
                    <p class="font-semibold dark:text-white text-slate-800 mt-1">{{ $usulan->sumber_barang ?? '-' }}</p>
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm dark:text-slate-400 text-slate-500">Spesifikasi</label>
                    <p class="dark:text-slate-300 text-slate-700 mt-1">{{ $usulan->spesifikasi ?? '-' }}</p>
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm dark:text-slate-400 text-slate-500">Keterangan</label>
                    <p class="dark:text-slate-300 text-slate-700 mt-1">{{ $usulan->keterangan_user ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Foto Barang -->
    @if($usulan->foto_barang && count($usulan->foto_barang) > 0)
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b dark:border-slate-700/50 border-slate-200">
            <h4 class="font-bold dark:text-white text-slate-800 flex items-center">
                <i class="fas fa-images text-pink-400 mr-2"></i>Foto Barang ({{ count($usulan->foto_barang) }} foto)
            </h4>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                @foreach($usulan->foto_urls as $index => $url)
                <a href="{{ $url }}" target="_blank" class="group relative">
                    <img src="{{ $url }}" class="w-full h-32 object-cover rounded-xl border dark:border-slate-600 border-slate-300 group-hover:opacity-80 transition">
                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 rounded-xl transition flex items-center justify-center">
                        <i class="fas fa-search-plus text-white text-xl"></i>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

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
                @if($usulan->catatan_admin)
                <div class="md:col-span-2">
                    <label class="text-sm dark:text-slate-400 text-slate-500">Catatan Admin</label>
                    <p class="dark:text-slate-300 text-slate-700 mt-1">{{ $usulan->catatan_admin }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Pengusul Info -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b dark:border-slate-700/50 border-slate-200">
            <h4 class="font-bold dark:text-white text-slate-800 flex items-center">
                <i class="fas fa-user text-cyan-400 mr-2"></i>Informasi Pengusul
            </h4>
        </div>
        <div class="p-6">
            <div class="flex items-center">
                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-user text-white text-xl"></i>
                </div>
                <div>
                    <p class="font-bold dark:text-white text-slate-800">{{ $usulan->user->name }}</p>
                    <p class="text-sm dark:text-slate-400 text-slate-500">{{ $usulan->user->email }}</p>
                    <p class="text-sm dark:text-slate-500 text-slate-400 mt-1">
                        <i class="fas fa-clock mr-1"></i>Diajukan pada {{ $usulan->created_at->format('d F Y H:i') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center justify-between">
        <a href="{{ route('usulan-penerimaan.index') }}" class="px-6 py-3 dark:bg-slate-700 bg-slate-200 dark:hover:bg-slate-600 hover:bg-slate-300 dark:text-white text-slate-700 rounded-xl font-semibold transition">
            <i class="fas fa-arrow-left mr-2"></i>Kembali
        </a>
        @if($usulan->can_edit && $usulan->user_id === auth()->id())
        <div class="flex gap-3">
            <a href="{{ route('usulan-penerimaan.edit', $usulan->id) }}" class="px-6 py-3 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-semibold transition">
                <i class="fas fa-edit mr-2"></i>Edit Usulan
            </a>
            <form action="{{ route('usulan-penerimaan.cancel', $usulan->id) }}" method="POST" onsubmit="return confirm('Yakin ingin membatalkan usulan ini?')">
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
