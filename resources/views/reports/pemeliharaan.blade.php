@extends('layouts.app')

@section('title', 'Laporan Pemeliharaan Aset')
@section('header', 'Laporan Pemeliharaan Aset')
@section('breadcrumb', 'Laporan / Pemeliharaan')

@section('content')
<div class="space-y-6">
    {{-- Filter --}}
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6 transition-colors duration-300">
        <form method="GET" action="{{ route('reports.pemeliharaan') }}">
            <div class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[180px]">
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Cari</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Deskripsi / teknisi / barang..." class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                </div>
                <div class="flex-1 min-w-[140px]">
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Status</label>
                    <select name="status" class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                        <option value="">Semua Status</option>
                        <option value="menunggu" {{ request('status') == 'menunggu' ? 'selected' : '' }}>Menunggu</option>
                        <option value="proses" {{ request('status') == 'proses' ? 'selected' : '' }}>Proses</option>
                        <option value="selesai" {{ request('status') == 'selesai' ? 'selected' : '' }}>Selesai</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[140px]">
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Jenis</label>
                    <select name="jenis" class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                        <option value="">Semua Jenis</option>
                        <option value="preventif" {{ request('jenis') == 'preventif' ? 'selected' : '' }}>Preventif</option>
                        <option value="korektif" {{ request('jenis') == 'korektif' ? 'selected' : '' }}>Korektif</option>
                        <option value="darurat" {{ request('jenis') == 'darurat' ? 'selected' : '' }}>Darurat</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Dari Tanggal</label>
                    <input type="date" name="dari_tanggal" value="{{ request('dari_tanggal') }}" class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Sampai Tanggal</label>
                    <input type="date" name="sampai_tanggal" value="{{ request('sampai_tanggal') }}" class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:from-emerald-600 hover:to-teal-700 transition-all shadow-lg">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                    <a href="{{ route('reports.pemeliharaan') }}" class="px-4 py-3 dark:bg-slate-700 bg-slate-200 dark:text-slate-300 text-slate-700 rounded-xl hover:bg-slate-600 transition-colors">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl p-4 dark:border dark:border-slate-700/50 border border-slate-200 transition-colors">
            <p class="text-sm dark:text-slate-400 text-slate-500">Total</p>
            <p class="text-2xl font-bold text-orange-400 mt-1">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl p-4 dark:border dark:border-slate-700/50 border border-slate-200 transition-colors">
            <p class="text-sm dark:text-slate-400 text-slate-500">Menunggu</p>
            <p class="text-2xl font-bold text-yellow-400 mt-1">{{ number_format($stats['menunggu']) }}</p>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl p-4 dark:border dark:border-slate-700/50 border border-slate-200 transition-colors">
            <p class="text-sm dark:text-slate-400 text-slate-500">Proses</p>
            <p class="text-2xl font-bold text-blue-400 mt-1">{{ number_format($stats['proses']) }}</p>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl p-4 dark:border dark:border-slate-700/50 border border-slate-200 transition-colors">
            <p class="text-sm dark:text-slate-400 text-slate-500">Selesai</p>
            <p class="text-2xl font-bold text-green-400 mt-1">{{ number_format($stats['selesai']) }}</p>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl p-4 dark:border dark:border-slate-700/50 border border-slate-200 transition-colors">
            <p class="text-sm dark:text-slate-400 text-slate-500">Total Biaya</p>
            <p class="text-xl font-bold text-emerald-400 mt-1">Rp {{ number_format($stats['total_biaya'], 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Export --}}
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('reports.pemeliharaan.export', request()->query()) }}" class="px-5 py-3 bg-gradient-to-r from-emerald-500 to-green-600 text-white rounded-xl font-semibold hover:from-emerald-600 hover:to-green-700 transition-all shadow-lg inline-flex items-center">
            <i class="fas fa-file-csv mr-2"></i>Export CSV
        </a>
        <a href="{{ route('reports.index') }}" class="px-5 py-3 dark:bg-slate-700 bg-slate-200 dark:text-slate-300 text-slate-700 rounded-xl font-semibold hover:bg-slate-600 transition-all inline-flex items-center">
            <i class="fas fa-arrow-left mr-2"></i>Kembali
        </a>
    </div>

    {{-- Table --}}
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden transition-colors duration-300">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="dark:bg-slate-700/30 bg-slate-100">
                    <tr>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">No</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Tanggal</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Barang Aset</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Jenis</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Deskripsi</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Teknisi</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Status</th>
                        <th class="px-4 py-4 text-right text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Biaya</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Pelapor</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50 divide-slate-200">
                    @forelse($data as $i => $row)
                    <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-4 dark:text-slate-400 text-slate-600">{{ $data->firstItem() + $i }}</td>
                        <td class="px-4 py-4 dark:text-white text-slate-800">{{ $row->tanggal_pemeliharaan ? $row->tanggal_pemeliharaan->format('d/m/Y') : '-' }}</td>
                        <td class="px-4 py-4 dark:text-white text-slate-800 font-medium">{{ $row->barangAset->nama_barang ?? '-' }}</td>
                        <td class="px-4 py-4 text-center">
                            @php
                                $jenisColors = [
                                    'preventif' => 'bg-blue-500/20 text-blue-400',
                                    'korektif' => 'bg-orange-500/20 text-orange-400',
                                    'darurat' => 'bg-red-500/20 text-red-400',
                                ];
                                $jColor = $jenisColors[$row->jenis_pemeliharaan] ?? 'bg-slate-500/20 text-slate-400';
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $jColor }}">{{ ucfirst($row->jenis_pemeliharaan ?? '-') }}</span>
                        </td>
                        <td class="px-4 py-4 dark:text-slate-400 text-slate-500 text-sm max-w-[200px] truncate">{{ $row->deskripsi_masalah ?? '-' }}</td>
                        <td class="px-4 py-4 dark:text-slate-300 text-slate-700">{{ $row->teknisi ?? '-' }}</td>
                        <td class="px-4 py-4 text-center">
                            @php
                                $statusColors = [
                                    'menunggu' => 'bg-yellow-500/20 text-yellow-400',
                                    'proses' => 'bg-blue-500/20 text-blue-400',
                                    'selesai' => 'bg-green-500/20 text-green-400',
                                ];
                                $sColor = $statusColors[$row->status] ?? 'bg-slate-500/20 text-slate-400';
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $sColor }}">{{ ucfirst($row->status) }}</span>
                        </td>
                        <td class="px-4 py-4 text-right dark:text-emerald-400 text-emerald-600 font-semibold">Rp {{ number_format($row->biaya ?? 0, 0, ',', '.') }}</td>
                        <td class="px-4 py-4 dark:text-slate-300 text-slate-700">{{ $row->createdBy->name ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center dark:text-slate-500 text-slate-400">
                            <i class="fas fa-inbox text-4xl mb-3 block"></i>
                            Tidak ada data pemeliharaan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($data->hasPages())
        <div class="px-6 py-4 border-t dark:border-slate-700/50 border-slate-200">
            {{ $data->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
