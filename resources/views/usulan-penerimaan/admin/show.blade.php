@extends('layouts.app')

@section('title', 'Verifikasi Usulan Penerimaan')
@section('header', 'Verifikasi Usulan Penerimaan')
@section('breadcrumb', 'Detail dan verifikasi usulan penerimaan barang')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="{ showApprove: false, showReject: false, showRevision: false }">
    <!-- Status & Action Card -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r {{ $usulan->is_disetujui ? 'from-emerald-500/20 to-green-500/20' : ($usulan->is_ditolak ? 'from-red-500/20 to-rose-500/20' : ($usulan->is_revisi ? 'from-purple-500/20 to-fuchsia-500/20' : 'from-amber-500/20 to-orange-500/20')) }} border-b dark:border-slate-700/50 border-slate-200">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center">
                    <div class="w-14 h-14 bg-gradient-to-br {{ $usulan->is_disetujui ? 'from-emerald-500 to-green-500' : ($usulan->is_ditolak ? 'from-red-500 to-rose-500' : ($usulan->is_revisi ? 'from-purple-500 to-fuchsia-500' : 'from-amber-500 to-orange-500')) }} rounded-xl flex items-center justify-center mr-4 shadow-lg">
                        <i class="fas {{ $usulan->is_disetujui ? 'fa-check-circle' : ($usulan->is_ditolak ? 'fa-times-circle' : ($usulan->is_revisi ? 'fa-edit' : 'fa-clock')) }} text-white text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm dark:text-slate-400 text-slate-500">Kode Usulan</p>
                        <h3 class="text-xl font-bold dark:text-white text-slate-800 font-mono">{{ $usulan->kode_usulan }}</h3>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold border {{ \App\Models\UsulanPenerimaanBarang::getStatusBadgeClass($usulan->status) }}">
                            {{ $usulan->status_label }}
                        </span>
                    </div>
                </div>
                
                @if(in_array($usulan->status, ['pending', 'diproses']))
                <div class="flex flex-wrap gap-2">
                    @if($usulan->is_pending)
                    <form action="{{ route('usulan-penerimaan.admin.proses', $usulan->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-xl font-semibold transition shadow-lg">
                            <i class="fas fa-spinner mr-2"></i>Mulai Proses
                        </button>
                    </form>
                    @endif
                    <button @click="showApprove = true" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-semibold transition shadow-lg shadow-emerald-500/25">
                        <i class="fas fa-check mr-2"></i>Setujui
                    </button>
                    <button @click="showRevision = true" class="px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-xl font-semibold transition shadow-lg shadow-purple-500/25">
                        <i class="fas fa-edit mr-2"></i>Minta Revisi
                    </button>
                    <button @click="showReject = true" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-xl font-semibold transition shadow-lg shadow-red-500/25">
                        <i class="fas fa-times mr-2"></i>Tolak
                    </button>
                </div>
                @endif
            </div>
        </div>

        @if($usulan->is_disetujui && $usulan->barangAset)
        <div class="px-6 py-4 bg-emerald-500/10 border-b dark:border-slate-700/50 border-slate-200">
            <div class="flex items-center justify-between">
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-emerald-400 mt-1 mr-3"></i>
                    <div>
                        <p class="font-semibold text-emerald-400">Barang Aset Berhasil Dibuat</p>
                        <p class="dark:text-emerald-300 text-emerald-700 mt-1">Kode Aset: <span class="font-mono font-bold">{{ $usulan->barangAset->kode_aset }}</span></p>
                    </div>
                </div>
                <a href="{{ route('barang-aset.show', $usulan->barangAset->id) }}" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-semibold transition">
                    <i class="fas fa-external-link-alt mr-2"></i>Lihat Aset
                </a>
            </div>
        </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Detail Barang -->
            <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b dark:border-slate-700/50 border-slate-200">
                    <h4 class="font-bold dark:text-white text-slate-800 flex items-center">
                        <i class="fas fa-box text-emerald-400 mr-2"></i>Data Barang yang Diusulkan
                    </h4>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs dark:text-slate-500 text-slate-400 uppercase">Nama Barang</label>
                            <p class="font-semibold dark:text-white text-slate-800">{{ $usulan->nama_barang }}</p>
                        </div>
                        <div>
                            <label class="text-xs dark:text-slate-500 text-slate-400 uppercase">Merk</label>
                            <p class="font-semibold dark:text-white text-slate-800">{{ $usulan->merk ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="text-xs dark:text-slate-500 text-slate-400 uppercase">Ruangan Tujuan</label>
                            <p class="font-semibold dark:text-blue-400 text-blue-600">
                                <i class="fas fa-door-open mr-1"></i>{{ $usulan->ruangan->nama_ruangan }}
                            </p>
                        </div>
                        <div>
                            <label class="text-xs dark:text-slate-500 text-slate-400 uppercase">Kategori (Usulan)</label>
                            <p class="font-semibold dark:text-white text-slate-800">{{ $usulan->kategoriAset->nama_kategori ?? 'Belum ditentukan' }}</p>
                        </div>
                        <div>
                            <label class="text-xs dark:text-slate-500 text-slate-400 uppercase">Jumlah</label>
                            <p class="font-semibold dark:text-white text-slate-800 text-lg">{{ $usulan->jumlah }} unit</p>
                        </div>
                        <div>
                            <label class="text-xs dark:text-slate-500 text-slate-400 uppercase">Kondisi</label>
                            <p><span class="px-3 py-1 rounded-full text-xs font-semibold {{ \App\Models\UsulanPenerimaanBarang::getKondisiBadgeClass($usulan->kondisi_barang) }}">{{ $usulan->kondisi_label }}</span></p>
                        </div>
                        <div>
                            <label class="text-xs dark:text-slate-500 text-slate-400 uppercase">Kelengkapan</label>
                            <p class="font-semibold dark:text-white text-slate-800">{{ $usulan->kelengkapan_label }}</p>
                        </div>
                        <div>
                            <label class="text-xs dark:text-slate-500 text-slate-400 uppercase">Estimasi Harga</label>
                            <p class="font-semibold dark:text-emerald-400 text-emerald-600">
                                @if($usulan->estimasi_harga)
                                    Rp {{ number_format($usulan->estimasi_harga, 0, ',', '.') }}
                                @else
                                    -
                                @endif
                            </p>
                        </div>
                        <div>
                            <label class="text-xs dark:text-slate-500 text-slate-400 uppercase">Sumber Barang</label>
                            <p class="font-semibold dark:text-white text-slate-800">{{ $usulan->sumber_barang ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="text-xs dark:text-slate-500 text-slate-400 uppercase">Tgl Penerimaan Fisik</label>
                            <p class="font-semibold dark:text-white text-slate-800">{{ $usulan->tanggal_penerimaan_fisik?->format('d/m/Y') ?? '-' }}</p>
                        </div>
                        <div class="col-span-2">
                            <label class="text-xs dark:text-slate-500 text-slate-400 uppercase">Spesifikasi</label>
                            <p class="dark:text-slate-300 text-slate-700">{{ $usulan->spesifikasi ?? '-' }}</p>
                        </div>
                        <div class="col-span-2">
                            <label class="text-xs dark:text-slate-500 text-slate-400 uppercase">Keterangan dari Pengusul</label>
                            <p class="dark:text-slate-300 text-slate-700">{{ $usulan->keterangan_user ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Foto Barang -->
            @if($usulan->foto_barang && count($usulan->foto_barang) > 0)
            <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b dark:border-slate-700/50 border-slate-200">
                    <h4 class="font-bold dark:text-white text-slate-800 flex items-center">
                        <i class="fas fa-images text-pink-400 mr-2"></i>Foto Bukti Barang ({{ count($usulan->foto_barang) }} foto)
                    </h4>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @foreach($usulan->foto_urls as $url)
                        <a href="{{ $url }}" target="_blank" class="group relative block">
                            <img src="{{ $url }}" class="w-full h-40 object-cover rounded-xl border dark:border-slate-600 border-slate-300 group-hover:opacity-80 transition">
                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 rounded-xl transition flex items-center justify-center">
                                <i class="fas fa-search-plus text-white text-2xl"></i>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Pengusul -->
            <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b dark:border-slate-700/50 border-slate-200">
                    <h4 class="font-bold dark:text-white text-slate-800 flex items-center">
                        <i class="fas fa-user text-blue-400 mr-2"></i>Pengusul
                    </h4>
                </div>
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-user text-white text-xl"></i>
                        </div>
                        <div>
                            <p class="font-bold dark:text-white text-slate-800">{{ $usulan->user->name }}</p>
                            <p class="text-sm dark:text-slate-400 text-slate-500">{{ $usulan->user->getRoleName() }}</p>
                        </div>
                    </div>
                    <div class="space-y-2 text-sm">
                        <p class="dark:text-slate-400 text-slate-500">
                            <i class="fas fa-envelope mr-2 w-4"></i>{{ $usulan->user->email }}
                        </p>
                        @if($usulan->user->phone)
                        <p class="dark:text-slate-400 text-slate-500">
                            <i class="fas fa-phone mr-2 w-4"></i>{{ $usulan->user->phone }}
                        </p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b dark:border-slate-700/50 border-slate-200">
                    <h4 class="font-bold dark:text-white text-slate-800 flex items-center">
                        <i class="fas fa-history text-cyan-400 mr-2"></i>Timeline
                    </h4>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-blue-500/20 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                                <i class="fas fa-paper-plane text-blue-400 text-xs"></i>
                            </div>
                            <div>
                                <p class="font-medium dark:text-white text-slate-800 text-sm">Usulan Diajukan</p>
                                <p class="text-xs dark:text-slate-500 text-slate-400">{{ $usulan->created_at->format('d M Y H:i') }}</p>
                            </div>
                        </div>
                        @if($usulan->verified_at)
                        <div class="flex items-start">
                            <div class="w-8 h-8 {{ $usulan->is_disetujui ? 'bg-emerald-500/20' : ($usulan->is_ditolak ? 'bg-red-500/20' : 'bg-purple-500/20') }} rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                                <i class="fas {{ $usulan->is_disetujui ? 'fa-check' : ($usulan->is_ditolak ? 'fa-times' : 'fa-edit') }} {{ $usulan->is_disetujui ? 'text-emerald-400' : ($usulan->is_ditolak ? 'text-red-400' : 'text-purple-400') }} text-xs"></i>
                            </div>
                            <div>
                                <p class="font-medium dark:text-white text-slate-800 text-sm">{{ $usulan->status_label }}</p>
                                <p class="text-xs dark:text-slate-500 text-slate-400">{{ $usulan->verified_at->format('d M Y H:i') }}</p>
                                <p class="text-xs dark:text-slate-400 text-slate-500">oleh {{ $usulan->verifiedBy->name }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Info Tambahan -->
            <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg p-6">
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm dark:text-slate-400 text-slate-500">Prioritas</span>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ \App\Models\UsulanPenerimaanBarang::getPrioritasBadgeClass($usulan->prioritas) }}">{{ $usulan->prioritas_label }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm dark:text-slate-400 text-slate-500">Tanggal Usulan</span>
                        <span class="text-sm font-medium dark:text-white text-slate-800">{{ $usulan->tanggal_usulan->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <div class="flex justify-start">
        <a href="{{ route('usulan-penerimaan.admin.index') }}" class="px-6 py-3 dark:bg-slate-700 bg-slate-200 dark:hover:bg-slate-600 hover:bg-slate-300 dark:text-white text-slate-700 rounded-xl font-semibold transition">
            <i class="fas fa-arrow-left mr-2"></i>Kembali ke Daftar
        </a>
    </div>

    <!-- Modal Approve -->
    <div x-show="showApprove" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" @click.self="showApprove = false">
        <div class="dark:bg-slate-800 bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden" @click.stop>
            <div class="px-6 py-4 bg-gradient-to-r from-emerald-500 to-green-500">
                <h3 class="text-lg font-bold text-white"><i class="fas fa-check-circle mr-2"></i>Setujui Usulan</h3>
            </div>
            <form action="{{ route('usulan-penerimaan.admin.approve', $usulan->id) }}" method="POST" class="p-6">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                            Kategori Aset <span class="text-red-500">*</span>
                        </label>
                        <select name="kategori_aset_id" required class="w-full dark:bg-slate-700 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($kategoriList as $kategori)
                                <option value="{{ $kategori->id }}" {{ $usulan->kategori_aset_id == $kategori->id ? 'selected' : '' }}>{{ $kategori->nama_kategori }} ({{ $kategori->golongan_label }})</option>
                            @endforeach
                        </select>
                        <p class="text-xs dark:text-slate-500 text-slate-400 mt-1">Kategori ini akan digunakan untuk membuat data aset baru</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">Catatan (Opsional)</label>
                        <textarea name="catatan_admin" rows="3" placeholder="Tambahkan catatan..." class="w-full dark:bg-slate-700 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800"></textarea>
                    </div>
                    <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-xl">
                        <p class="text-sm dark:text-emerald-300 text-emerald-700">
                            <i class="fas fa-info-circle mr-2"></i>Setelah disetujui, sistem akan otomatis membuat data Barang Aset baru berdasarkan usulan ini.
                        </p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="showApprove = false" class="px-6 py-3 dark:bg-slate-700 bg-slate-200 dark:text-white text-slate-700 rounded-xl font-semibold">Batal</button>
                    <button type="submit" class="px-6 py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-semibold">
                        <i class="fas fa-check mr-2"></i>Setujui & Buat Aset
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Reject -->
    <div x-show="showReject" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" @click.self="showReject = false">
        <div class="dark:bg-slate-800 bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden" @click.stop>
            <div class="px-6 py-4 bg-gradient-to-r from-red-500 to-rose-500">
                <h3 class="text-lg font-bold text-white"><i class="fas fa-times-circle mr-2"></i>Tolak Usulan</h3>
            </div>
            <form action="{{ route('usulan-penerimaan.admin.reject', $usulan->id) }}" method="POST" class="p-6">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                            Alasan Penolakan <span class="text-red-500">*</span>
                        </label>
                        <textarea name="alasan_penolakan" rows="4" required placeholder="Jelaskan alasan penolakan usulan ini..." class="w-full dark:bg-slate-700 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800"></textarea>
                    </div>
                    <div class="p-4 bg-red-500/10 border border-red-500/30 rounded-xl">
                        <p class="text-sm dark:text-red-300 text-red-700">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Usulan yang ditolak tidak dapat diubah lagi. Pengusul akan menerima notifikasi penolakan.
                        </p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="showReject = false" class="px-6 py-3 dark:bg-slate-700 bg-slate-200 dark:text-white text-slate-700 rounded-xl font-semibold">Batal</button>
                    <button type="submit" class="px-6 py-3 bg-red-500 hover:bg-red-600 text-white rounded-xl font-semibold">
                        <i class="fas fa-times mr-2"></i>Tolak Usulan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Request Revision -->
    <div x-show="showRevision" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" @click.self="showRevision = false">
        <div class="dark:bg-slate-800 bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden" @click.stop>
            <div class="px-6 py-4 bg-gradient-to-r from-purple-500 to-fuchsia-500">
                <h3 class="text-lg font-bold text-white"><i class="fas fa-edit mr-2"></i>Minta Revisi</h3>
            </div>
            <form action="{{ route('usulan-penerimaan.admin.revision', $usulan->id) }}" method="POST" class="p-6">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                            Permintaan Revisi <span class="text-red-500">*</span>
                        </label>
                        <textarea name="permintaan_revisi" rows="4" required placeholder="Jelaskan apa yang perlu direvisi oleh pengusul..." class="w-full dark:bg-slate-700 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800"></textarea>
                    </div>
                    <div class="p-4 bg-purple-500/10 border border-purple-500/30 rounded-xl">
                        <p class="text-sm dark:text-purple-300 text-purple-700">
                            <i class="fas fa-info-circle mr-2"></i>Pengusul akan menerima notifikasi dan dapat mengedit usulannya sesuai permintaan revisi.
                        </p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="showRevision = false" class="px-6 py-3 dark:bg-slate-700 bg-slate-200 dark:text-white text-slate-700 rounded-xl font-semibold">Batal</button>
                    <button type="submit" class="px-6 py-3 bg-purple-500 hover:bg-purple-600 text-white rounded-xl font-semibold">
                        <i class="fas fa-paper-plane mr-2"></i>Kirim Permintaan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
