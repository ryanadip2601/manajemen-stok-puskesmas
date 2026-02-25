@extends('layouts.app')

@section('title', 'Detail Barang Aset')
@section('header', 'Detail Barang Aset')
@section('breadcrumb', 'Aset / Barang Aset / Detail')

@php
    $penyusutan = $aset->hitungPenyusutan();
@endphp

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            @if(auth()->user()->canManageWarehouse())
            <a href="{{ route('barang-aset.index') }}" class="p-2 dark:bg-slate-700 bg-slate-200 rounded-lg dark:text-slate-300 text-slate-600 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            @else
            <a href="{{ route('staff.barang-aset.index') }}" class="p-2 dark:bg-slate-700 bg-slate-200 rounded-lg dark:text-slate-300 text-slate-600 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            @endif
            <div>
                <h3 class="text-xl font-bold dark:text-white text-slate-800">{{ $aset->nama_aset }}</h3>
                <p class="text-sm dark:text-slate-400 text-slate-600 font-mono">{{ $aset->kode_aset }}</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(auth()->user()->canManageWarehouse())
            <a href="{{ route('penyusutan.kartu', $aset->id) }}" class="px-4 py-2 bg-amber-500/20 text-amber-400 rounded-xl font-medium hover:bg-amber-500/30 transition">
                <i class="fas fa-calculator mr-2"></i>Kartu Penyusutan
            </a>
            <a href="{{ route('pemeliharaan.create', ['aset_id' => $aset->id]) }}" class="px-4 py-2 bg-purple-500/20 text-purple-400 rounded-xl font-medium hover:bg-purple-500/30 transition">
                <i class="fas fa-tools mr-2"></i>Buat Pemeliharaan
            </a>
            <a href="{{ route('barang-aset.edit', $aset->id) }}" class="px-4 py-2 bg-blue-500/20 text-blue-400 rounded-xl font-medium hover:bg-blue-500/30 transition">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
            @else
            {{-- Staff: hanya bisa ajukan pemeliharaan --}}
            <a href="{{ route('staff.pemeliharaan.create', ['aset_id' => $aset->id]) }}" class="px-4 py-2 bg-purple-500/20 text-purple-400 rounded-xl font-medium hover:bg-purple-500/30 transition">
                <i class="fas fa-tools mr-2"></i>Ajukan Pemeliharaan
            </a>
            @endif
        </div>
    </div>

    <!-- Penyusutan Summary Card -->
    <div class="dark:bg-gradient-to-r dark:from-slate-800 dark:to-slate-800/50 bg-gradient-to-r from-white to-slate-50 rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
            <h4 class="text-lg font-bold dark:text-white text-slate-800 flex items-center">
                <i class="fas fa-chart-line text-emerald-400 mr-3"></i>Ringkasan Penyusutan
            </h4>
            @if($penyusutan['dapat_disusutkan'])
                @if($penyusutan['sudah_habis'] ?? false)
                    <span class="px-3 py-1 bg-red-500/20 text-red-400 rounded-lg text-sm font-medium">
                        <i class="fas fa-exclamation-circle mr-1"></i>Habis Masa Manfaat
                    </span>
                @else
                    <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 rounded-lg text-sm font-medium">
                        <i class="fas fa-check-circle mr-1"></i>Aktif Disusutkan
                    </span>
                @endif
            @else
                <span class="px-3 py-1 bg-slate-500/20 text-slate-400 rounded-lg text-sm font-medium">
                    <i class="fas fa-ban mr-1"></i>Tidak Disusutkan
                </span>
            @endif
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-6">
            <div class="text-center p-4 dark:bg-slate-700/30 bg-slate-100 rounded-xl">
                <p class="text-xs dark:text-slate-400 text-slate-600 mb-1">Nilai Perolehan</p>
                <p class="text-lg font-bold dark:text-white text-slate-800">Rp {{ number_format($penyusutan['nilai_perolehan'], 0, ',', '.') }}</p>
            </div>
            <div class="text-center p-4 dark:bg-slate-700/30 bg-slate-100 rounded-xl">
                <p class="text-xs dark:text-slate-400 text-slate-600 mb-1">Akum. Penyusutan</p>
                <p class="text-lg font-bold text-amber-500">Rp {{ number_format($penyusutan['akumulasi_penyusutan'], 0, ',', '.') }}</p>
            </div>
            <div class="text-center p-4 dark:bg-emerald-900/30 bg-emerald-50 rounded-xl border dark:border-emerald-500/30 border-emerald-200">
                <p class="text-xs dark:text-emerald-400 text-emerald-700 mb-1">Nilai Buku</p>
                <p class="text-xl font-bold text-emerald-500">Rp {{ number_format($penyusutan['nilai_buku'], 0, ',', '.') }}</p>
            </div>
            <div class="text-center p-4 dark:bg-slate-700/30 bg-slate-100 rounded-xl">
                <p class="text-xs dark:text-slate-400 text-slate-600 mb-1">Usia Aset</p>
                <p class="text-lg font-bold text-blue-400">{{ floor($penyusutan['jumlah_bulan'] / 12) }} th {{ $penyusutan['jumlah_bulan'] % 12 }} bln</p>
            </div>
            <div class="text-center p-4 dark:bg-slate-700/30 bg-slate-100 rounded-xl">
                <p class="text-xs dark:text-slate-400 text-slate-600 mb-1">Tersusutkan</p>
                <p class="text-lg font-bold {{ ($penyusutan['sudah_habis'] ?? false) ? 'text-red-400' : 'text-cyan-400' }}">{{ number_format($penyusutan['persentase_penyusutan'], 1) }}%</p>
            </div>
        </div>

        <!-- Progress Bar -->
        @if($penyusutan['dapat_disusutkan'])
        <div class="mt-4">
            <div class="flex justify-between text-xs dark:text-slate-400 text-slate-600 mb-1">
                <span>Progress Penyusutan</span>
                <span>Sisa {{ $penyusutan['sisa_umur_tahun'] ?? 0 }} tahun {{ ($penyusutan['sisa_umur_bulan'] ?? 0) % 12 }} bulan</span>
            </div>
            <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-3">
                <div class="h-3 rounded-full transition-all {{ ($penyusutan['sudah_habis'] ?? false) ? 'bg-red-500' : 'bg-gradient-to-r from-emerald-500 to-teal-500' }}" 
                     style="width: {{ min(100, $penyusutan['persentase_penyusutan']) }}%"></div>
            </div>
        </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Informasi Aset -->
            <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6">
                <h4 class="text-lg font-bold dark:text-white text-slate-800 mb-4 flex items-center">
                    <i class="fas fa-info-circle text-blue-400 mr-2"></i>Informasi Aset
                </h4>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm dark:text-slate-400 text-slate-600">Kategori</p>
                        <p class="dark:text-white text-slate-800 font-medium">{{ $aset->kategoriAset->nama_kategori ?? '-' }}</p>
                        <p class="text-xs dark:text-slate-500 text-slate-400">{{ $aset->kategoriAset->golongan_label ?? '' }}</p>
                    </div>
                    <div>
                        <p class="text-sm dark:text-slate-400 text-slate-600">Ruangan</p>
                        <p class="dark:text-white text-slate-800 font-medium">{{ $aset->ruangan->nama_ruangan ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm dark:text-slate-400 text-slate-600">Kondisi</p>
                        <span class="px-3 py-1 text-sm rounded-lg {{ \App\Models\BarangAset::getKondisiBadgeClass($aset->kondisi) }}">
                            {{ $aset->kondisi_label }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm dark:text-slate-400 text-slate-600">Jumlah</p>
                        <p class="dark:text-white text-slate-800 font-bold text-2xl">{{ $aset->jumlah }}</p>
                    </div>
                    <div>
                        <p class="text-sm dark:text-slate-400 text-slate-600">Merk / Type</p>
                        <p class="dark:text-white text-slate-800 font-medium">{{ $aset->merk ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm dark:text-slate-400 text-slate-600">Tanggal Perolehan</p>
                        <p class="dark:text-white text-slate-800 font-medium">{{ $aset->tanggal_masuk->format('d/m/Y') }}</p>
                    </div>
                    <div>
                        <p class="text-sm dark:text-slate-400 text-slate-600">Tahun Perolehan</p>
                        <p class="dark:text-white text-slate-800 font-medium">{{ $aset->tahun_perolehan ?? $aset->tanggal_masuk->year }}</p>
                    </div>
                    <div>
                        <p class="text-sm dark:text-slate-400 text-slate-600">Sumber Dana</p>
                        <p class="dark:text-white text-slate-800 font-medium">{{ $aset->sumber_dana ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm dark:text-slate-400 text-slate-600">Nomor Register</p>
                        <p class="dark:text-white text-slate-800 font-mono">{{ $aset->nomor_register ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm dark:text-slate-400 text-slate-600">Umur Manfaat</p>
                        <p class="dark:text-white text-slate-800 font-medium">{{ $penyusutan['umur_manfaat'] }} tahun</p>
                    </div>
                    <div>
                        <p class="text-sm dark:text-slate-400 text-slate-600">No. Sertifikat/No. Pabrik/No. Chasis/No. Mesin</p>
                        <p class="dark:text-white text-slate-800 font-mono">{{ $aset->no_sertifikat ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm dark:text-slate-400 text-slate-600">Asal/Cara Perolehan</p>
                        <p class="dark:text-white text-slate-800 font-medium">{{ $aset->asal_perolehan ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm dark:text-slate-400 text-slate-600">Ukuran Barang/Konstruksi</p>
                        <p class="dark:text-white text-slate-800 font-medium">{{ $aset->ukuran_konstruksi ?? '-' }}</p>
                    </div>
                </div>

                @if($aset->spesifikasi)
                <div class="mt-4 pt-4 border-t dark:border-slate-700 border-slate-200">
                    <p class="text-sm dark:text-slate-400 text-slate-600">Spesifikasi</p>
                    <p class="dark:text-white text-slate-800">{{ $aset->spesifikasi }}</p>
                </div>
                @endif

                @if($aset->keterangan)
                <div class="mt-4 pt-4 border-t dark:border-slate-700 border-slate-200">
                    <p class="text-sm dark:text-slate-400 text-slate-600">Keterangan</p>
                    <p class="dark:text-white text-slate-800">{{ $aset->keterangan }}</p>
                </div>
                @endif
            </div>

            <!-- Data Penyusutan Detail -->
            <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6">
                <h4 class="text-lg font-bold dark:text-white text-slate-800 mb-4 flex items-center">
                    <i class="fas fa-calculator text-amber-400 mr-2"></i>Detail Penyusutan
                </h4>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div class="p-3 dark:bg-slate-700/30 bg-slate-50 rounded-xl">
                        <p class="text-xs dark:text-slate-400 text-slate-600">Penyusutan/Tahun</p>
                        <p class="dark:text-amber-400 text-amber-600 font-semibold">Rp {{ number_format($penyusutan['penyusutan_per_tahun'], 0, ',', '.') }}</p>
                    </div>
                    <div class="p-3 dark:bg-slate-700/30 bg-slate-50 rounded-xl">
                        <p class="text-xs dark:text-slate-400 text-slate-600">Penyusutan/Bulan</p>
                        <p class="dark:text-amber-400 text-amber-600 font-semibold">Rp {{ number_format($penyusutan['penyusutan_per_bulan'], 0, ',', '.') }}</p>
                    </div>
                    <div class="p-3 dark:bg-slate-700/30 bg-slate-50 rounded-xl">
                        <p class="text-xs dark:text-slate-400 text-slate-600">Nilai Residu</p>
                        <p class="dark:text-white text-slate-800 font-semibold">Rp {{ number_format($penyusutan['nilai_residu'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            <!-- Riwayat Pemeliharaan -->
            <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden">
                <div class="p-6 border-b dark:border-slate-700/50 border-slate-200 flex items-center justify-between">
                    <h4 class="text-lg font-bold dark:text-white text-slate-800 flex items-center">
                        <i class="fas fa-tools text-purple-400 mr-2"></i>Riwayat Pemeliharaan
                    </h4>
                    <a href="{{ route('pemeliharaan.riwayat-aset', $aset->id) }}" class="text-sm text-blue-400 hover:underline">Lihat Semua</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="dark:bg-slate-700/30 bg-slate-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Jenis</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Teknisi</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-slate-700/50 divide-slate-200">
                            @forelse($aset->pemeliharaan->take(5) as $p)
                            <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50">
                                <td class="px-4 py-3 dark:text-slate-300 text-slate-700">{{ $p->tanggal_pemeliharaan->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-lg {{ \App\Models\PemeliharaanAset::getJenisBadgeClass($p->jenis_pemeliharaan) }}">
                                        {{ $p->jenis_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 dark:text-slate-300 text-slate-700">{{ $p->teknisi }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 text-xs rounded-lg {{ \App\Models\PemeliharaanAset::getStatusBadgeClass($p->status) }}">
                                        {{ $p->status_label }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center dark:text-slate-500 text-slate-400">
                                    <i class="fas fa-tools text-2xl mb-2 block opacity-50"></i>
                                    Belum ada riwayat pemeliharaan
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Foto -->
            @if($aset->foto)
            <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6">
                <h4 class="text-lg font-bold dark:text-white text-slate-800 mb-4 flex items-center">
                    <i class="fas fa-image text-emerald-400 mr-2"></i>Foto
                </h4>
                <img src="{{ $aset->foto_url }}" alt="{{ $aset->nama_aset }}" class="w-full rounded-xl">
            </div>
            @endif

            <!-- Status -->
            <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6">
                <h4 class="text-lg font-bold dark:text-white text-slate-800 mb-4 flex items-center">
                    <i class="fas fa-clipboard-check text-cyan-400 mr-2"></i>Status
                </h4>
                <div class="space-y-4">
                    @if($aset->butuh_perbaikan)
                    <div class="p-3 bg-orange-500/10 border border-orange-500/30 rounded-xl">
                        <p class="text-orange-400 font-medium text-sm"><i class="fas fa-exclamation-triangle mr-2"></i>Butuh Perbaikan</p>
                    </div>
                    @else
                    <div class="p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-xl">
                        <p class="text-emerald-400 font-medium text-sm"><i class="fas fa-check-circle mr-2"></i>Kondisi Normal</p>
                    </div>
                    @endif

                    <div class="p-3 dark:bg-slate-700/30 bg-slate-50 rounded-xl">
                        <p class="text-xs dark:text-slate-400 text-slate-600">Status Aset</p>
                        <span class="px-2 py-1 text-xs rounded-lg {{ \App\Models\BarangAset::getStatusBadgeClass($aset->status_aset ?? 'aktif') }}">
                            {{ $aset->status_label ?? 'Aktif' }}
                        </span>
                    </div>
                    
                    <div class="text-sm dark:text-slate-400 text-slate-600 pt-3 border-t dark:border-slate-700 border-slate-200">
                        <p>Dibuat oleh: {{ $aset->createdBy->name ?? 'System' }}</p>
                        <p>Pada: {{ $aset->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6">
                <h4 class="text-lg font-bold dark:text-white text-slate-800 mb-4 flex items-center">
                    <i class="fas fa-bolt text-yellow-400 mr-2"></i>Aksi Cepat
                </h4>
                <div class="space-y-2">
                    @if(auth()->user()->canManageWarehouse())
                    <a href="{{ route('penyusutan.kartu', $aset->id) }}" class="w-full flex items-center px-4 py-3 dark:bg-slate-700/50 bg-slate-100 rounded-xl dark:text-white text-slate-800 hover:bg-amber-500/20 hover:text-amber-400 transition">
                        <i class="fas fa-file-invoice-dollar mr-3 text-amber-400"></i>Lihat Kartu Penyusutan
                    </a>
                    <a href="{{ route('pemeliharaan.create', ['aset_id' => $aset->id]) }}" class="w-full flex items-center px-4 py-3 dark:bg-slate-700/50 bg-slate-100 rounded-xl dark:text-white text-slate-800 hover:bg-purple-500/20 hover:text-purple-400 transition">
                        <i class="fas fa-wrench mr-3 text-purple-400"></i>Buat Jadwal Pemeliharaan
                    </a>
                    <a href="{{ route('barang-aset.edit', $aset->id) }}" class="w-full flex items-center px-4 py-3 dark:bg-slate-700/50 bg-slate-100 rounded-xl dark:text-white text-slate-800 hover:bg-blue-500/20 hover:text-blue-400 transition">
                        <i class="fas fa-edit mr-3 text-blue-400"></i>Edit Data Aset
                    </a>
                    @else
                    {{-- Staff: hanya bisa ajukan pemeliharaan --}}
                    <a href="{{ route('staff.pemeliharaan.create', ['aset_id' => $aset->id]) }}" class="w-full flex items-center px-4 py-3 dark:bg-slate-700/50 bg-slate-100 rounded-xl dark:text-white text-slate-800 hover:bg-purple-500/20 hover:text-purple-400 transition">
                        <i class="fas fa-wrench mr-3 text-purple-400"></i>Ajukan Pemeliharaan
                    </a>
                    <a href="{{ route('staff.pemeliharaan.index') }}" class="w-full flex items-center px-4 py-3 dark:bg-slate-700/50 bg-slate-100 rounded-xl dark:text-white text-slate-800 hover:bg-cyan-500/20 hover:text-cyan-400 transition">
                        <i class="fas fa-list mr-3 text-cyan-400"></i>Lihat Riwayat Pemeliharaan
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
