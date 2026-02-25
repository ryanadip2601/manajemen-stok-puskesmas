@extends('layouts.app')

@section('title', 'Detail Ruangan')
@section('header', 'Detail Ruangan')
@section('breadcrumb', 'Aset / Ruangan / Detail')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold dark:text-white">{{ $ruangan->nama_ruangan }}</h3>
            <p class="text-sm dark:text-slate-400">{{ $ruangan->lokasi ?? 'Lokasi tidak ditentukan' }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('barang-aset.create', ['ruangan_id' => $ruangan->id]) }}" class="px-4 py-2 bg-emerald-500/20 text-emerald-400 rounded-xl font-medium hover:bg-emerald-500/30 transition">
                <i class="fas fa-plus mr-2"></i>Tambah Aset
            </a>
            <a href="{{ route('pemeliharaan.riwayat-ruangan', $ruangan->id) }}" class="px-4 py-2 bg-purple-500/20 text-purple-400 rounded-xl font-medium hover:bg-purple-500/30 transition">
                <i class="fas fa-tools mr-2"></i>Riwayat Pemeliharaan
            </a>
            <a href="{{ route('ruangan.edit', $ruangan->id) }}" class="px-4 py-2 bg-amber-500/20 text-amber-400 rounded-xl font-medium hover:bg-amber-500/30 transition">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
            <a href="{{ route('ruangan.index') }}" class="px-4 py-2 dark:bg-slate-700 bg-slate-200 dark:text-slate-300 rounded-xl font-medium hover:bg-slate-600 hover:text-white transition">
                <i class="fas fa-arrow-left mr-2"></i>Kembali
            </a>
        </div>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="dark:bg-slate-800/50 bg-white rounded-xl p-4 border dark:border-slate-700/50">
            <p class="text-xs dark:text-slate-400">Total Aset</p>
            <p class="text-2xl font-bold text-blue-400">{{ $ruangan->barang_aset_count }}</p>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-xl p-4 border dark:border-slate-700/50">
            <p class="text-xs dark:text-slate-400">Kondisi Baik</p>
            <p class="text-2xl font-bold text-emerald-400">{{ $asetByKondisi['baik'] ?? 0 }}</p>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-xl p-4 border dark:border-slate-700/50">
            <p class="text-xs dark:text-slate-400">Rusak Ringan</p>
            <p class="text-2xl font-bold text-amber-400">{{ $asetByKondisi['rusak_ringan'] ?? 0 }}</p>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-xl p-4 border dark:border-slate-700/50">
            <p class="text-xs dark:text-slate-400">Rusak Berat</p>
            <p class="text-2xl font-bold text-red-400">{{ $asetByKondisi['rusak_berat'] ?? 0 }}</p>
        </div>
    </div>

    <!-- Daftar Aset -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 overflow-hidden">
        <div class="p-6 border-b dark:border-slate-700/50">
            <h4 class="text-lg font-bold dark:text-white flex items-center">
                <i class="fas fa-boxes text-blue-400 mr-2"></i>Daftar Aset di Ruangan Ini
            </h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="dark:bg-slate-700/30 bg-slate-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold dark:text-slate-400 uppercase">Kode</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold dark:text-slate-400 uppercase">Nama Aset</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold dark:text-slate-400 uppercase">Kategori</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold dark:text-slate-400 uppercase">Kondisi</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold dark:text-slate-400 uppercase">Jumlah</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold dark:text-slate-400 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50">
                    @forelse($ruangan->barangAset as $aset)
                    <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50">
                        <td class="px-4 py-3 font-mono text-sm dark:text-slate-300">{{ $aset->kode_aset }}</td>
                        <td class="px-4 py-3 dark:text-white font-medium">{{ $aset->nama_aset }}</td>
                        <td class="px-4 py-3 dark:text-slate-400">{{ $aset->kategoriAset->nama_kategori ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 text-xs rounded {{ \App\Models\BarangAset::getKondisiBadgeClass($aset->kondisi) }}">{{ $aset->kondisi_label }}</span>
                        </td>
                        <td class="px-4 py-3 text-center dark:text-white font-semibold">{{ $aset->jumlah }}</td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('barang-aset.show', $aset->id) }}" class="p-2 bg-blue-500/20 text-blue-400 rounded-lg hover:bg-blue-500/30 transition"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center dark:text-slate-500">Belum ada aset di ruangan ini</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
