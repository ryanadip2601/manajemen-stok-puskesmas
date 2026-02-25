@extends('layouts.app')

@section('title', 'Pemeliharaan Aset')
@section('header', 'Pemeliharaan Aset Elektronik')
@section('breadcrumb', 'Aset / Pemeliharaan')

@section('content')
<div class="space-y-6">
    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="dark:bg-slate-800/50 bg-white rounded-xl p-4 border dark:border-slate-700/50">
            <p class="text-xs dark:text-slate-400 text-slate-600">Total Pemeliharaan</p>
            <p class="text-2xl font-bold text-blue-400">{{ number_format($summary['total']) }}</p>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-xl p-4 border dark:border-slate-700/50">
            <p class="text-xs dark:text-slate-400 text-slate-600">Menunggu</p>
            <p class="text-2xl font-bold text-amber-400">{{ number_format($summary['menunggu']) }}</p>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-xl p-4 border dark:border-slate-700/50">
            <p class="text-xs dark:text-slate-400 text-slate-600">Proses</p>
            <p class="text-2xl font-bold text-blue-400">{{ number_format($summary['proses']) }}</p>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-xl p-4 border dark:border-slate-700/50">
            <p class="text-xs dark:text-slate-400 text-slate-600">Selesai</p>
            <p class="text-2xl font-bold text-emerald-400">{{ number_format($summary['selesai']) }}</p>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-xl p-4 border dark:border-slate-700/50">
            <p class="text-xs dark:text-slate-400 text-slate-600">Rusak</p>
            <p class="text-2xl font-bold text-red-400">{{ number_format($summary['rusak']) }}</p>
        </div>
    </div>

    <!-- Header & Actions -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold dark:text-white text-slate-800">
                @if($isStaff ?? false)
                    Pemeliharaan Saya
                @else
                    Daftar Pemeliharaan
                @endif
            </h3>
            <p class="text-sm dark:text-slate-400 text-slate-600">
                @if($isStaff ?? false)
                    Riwayat pengajuan pemeliharaan aset Anda
                @else
                    Kelola pemeliharaan aset elektronik
                @endif
            </p>
        </div>
        <div class="flex gap-2">
            @if(!($isStaff ?? false))
            <a href="{{ route('pemeliharaan.export', request()->query()) }}" class="px-4 py-2 bg-cyan-500/20 text-cyan-400 rounded-xl font-medium hover:bg-cyan-500/30 transition">
                <i class="fas fa-download mr-2"></i>Export
            </a>
            <a href="{{ route('pemeliharaan.create') }}" class="px-5 py-2 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-xl font-semibold hover:from-purple-600 hover:to-indigo-700 transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Buat Laporan
            </a>
            @else
            <a href="{{ route('staff.pemeliharaan.create') }}" class="px-5 py-2 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-xl font-semibold hover:from-purple-600 hover:to-indigo-700 transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Ajukan Pemeliharaan
            </a>
            @endif
        </div>
    </div>

    <!-- Filter Section -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6">
        <form method="GET" action="{{ ($isStaff ?? false) ? route('staff.pemeliharaan.index') : route('pemeliharaan.index') }}" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Cari</label>
                <input type="text" name="search" value="{{ $activeFilters['search'] ?? '' }}" placeholder="Teknisi, masalah, aset..."
                    class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Ruangan</label>
                <select name="ruangan" class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">Semua Ruangan</option>
                    @foreach($ruanganList as $r)
                        <option value="{{ $r->id }}" @selected(($activeFilters['ruangan'] ?? '') == $r->id)>{{ $r->nama_ruangan }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[130px]">
                <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Status</label>
                <select name="status" class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">Semua</option>
                    @foreach($statusOptions as $key => $label)
                        <option value="{{ $key }}" @selected(($activeFilters['status'] ?? '') == $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[130px]">
                <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Jenis</label>
                <select name="jenis" class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">Semua</option>
                    @foreach($jenisOptions as $key => $label)
                        <option value="{{ $key }}" @selected(($activeFilters['jenis'] ?? '') == $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-5 py-3 bg-slate-600 text-white rounded-xl font-semibold hover:bg-slate-700 transition">
                    <i class="fas fa-search mr-2"></i>Filter
                </button>
                <a href="{{ route('pemeliharaan.clear-filter') }}" class="px-5 py-3 bg-slate-500/50 text-slate-300 rounded-xl hover:bg-slate-500 transition" title="Reset Filter">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="dark:bg-slate-700/30 bg-slate-100">
                    <tr>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Tanggal</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Aset</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Ruangan</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Jenis</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Teknisi</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Status</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50 divide-slate-200">
                    @forelse($pemeliharaan as $item)
                    <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-4 dark:text-slate-300">{{ $item->tanggal_pemeliharaan->format('d/m/Y') }}</td>
                        <td class="px-4 py-4">
                            <p class="dark:text-white text-slate-800 font-medium">{{ $item->barangAset->nama_aset ?? '-' }}</p>
                            <p class="text-xs dark:text-slate-500 font-mono">{{ $item->barangAset->kode_aset ?? '-' }}</p>
                        </td>
                        <td class="px-4 py-4">
                            <span class="px-2 py-1 text-xs bg-blue-500/20 text-blue-400 rounded-lg">{{ $item->ruangan->nama_ruangan ?? '-' }}</span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="px-2 py-1 text-xs rounded {{ \App\Models\PemeliharaanAset::getJenisBadgeClass($item->jenis_pemeliharaan) }}">
                                {{ $item->jenis_label }}
                            </span>
                        </td>
                        <td class="px-4 py-4 dark:text-slate-300">{{ $item->teknisi }}</td>
                        <td class="px-4 py-4 text-center">
                            <span class="px-3 py-1 text-xs rounded-lg {{ \App\Models\PemeliharaanAset::getStatusBadgeClass($item->status) }}">
                                {{ $item->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <div class="flex items-center justify-center gap-1">
                                @if($isStaff ?? false)
                                {{-- Staff: hanya bisa lihat detail --}}
                                <a href="{{ route('staff.pemeliharaan.show', $item->id) }}" class="p-2 bg-blue-500/20 text-blue-400 rounded-lg hover:bg-blue-500/30 transition" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @else
                                {{-- Admin: full access --}}
                                <a href="{{ route('pemeliharaan.show', $item->id) }}" class="p-2 bg-blue-500/20 text-blue-400 rounded-lg hover:bg-blue-500/30 transition" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('pemeliharaan.edit', $item->id) }}" class="p-2 bg-amber-500/20 text-amber-400 rounded-lg hover:bg-amber-500/30 transition" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('pemeliharaan.destroy', $item->id) }}" method="POST" class="inline" onsubmit="return confirm('Yakin hapus laporan ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center dark:text-slate-500 text-slate-400">
                            <i class="fas fa-tools text-4xl mb-3 block"></i>
                            Belum ada data pemeliharaan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($pemeliharaan->hasPages())
        <div class="p-4 border-t dark:border-slate-700/50 border-slate-200">
            {{ $pemeliharaan->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
