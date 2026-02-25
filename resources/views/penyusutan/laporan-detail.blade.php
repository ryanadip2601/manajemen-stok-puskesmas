@extends('layouts.app')

@section('title', 'Laporan Detail Penyusutan')
@section('header', 'Laporan Detail Penyusutan')
@section('breadcrumb', 'Aset / Penyusutan / Laporan Detail')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('penyusutan.index') }}" class="p-2 dark:bg-slate-700 bg-slate-200 rounded-lg dark:text-slate-300 text-slate-600 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h3 class="text-xl font-bold dark:text-white text-slate-800">Laporan Detail Penyusutan</h3>
                <p class="text-sm dark:text-slate-400 text-slate-600">Perhitungan penyusutan per aset</p>
            </div>
        </div>
        <a href="{{ route('penyusutan.export', request()->query()) }}" class="px-4 py-2 bg-cyan-500/20 text-cyan-400 rounded-xl font-medium hover:bg-cyan-500/30 transition">
            <i class="fas fa-download mr-2"></i>Export CSV
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="dark:bg-slate-800/50 bg-white rounded-xl p-4 border dark:border-slate-700/50 border-slate-200">
            <p class="text-xs dark:text-slate-400 text-slate-600">Total Aset</p>
            <p class="text-2xl font-bold text-blue-400">{{ number_format($summary['total_aset']) }}</p>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-xl p-4 border dark:border-slate-700/50 border-slate-200">
            <p class="text-xs dark:text-slate-400 text-slate-600">Nilai Perolehan</p>
            <p class="text-lg font-bold text-emerald-400">Rp {{ number_format($summary['total_nilai_perolehan'], 0, ',', '.') }}</p>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-xl p-4 border dark:border-slate-700/50 border-slate-200">
            <p class="text-xs dark:text-slate-400 text-slate-600">Akumulasi Penyusutan</p>
            <p class="text-lg font-bold text-amber-400">Rp {{ number_format($summary['total_akumulasi'], 0, ',', '.') }}</p>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-xl p-4 border dark:border-slate-700/50 border-slate-200">
            <p class="text-xs dark:text-slate-400 text-slate-600">Nilai Buku</p>
            <p class="text-lg font-bold text-purple-400">Rp {{ number_format($summary['total_nilai_buku'], 0, ',', '.') }}</p>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6">
        <form method="GET" action="{{ route('penyusutan.laporan-detail') }}" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Golongan</label>
                <select name="golongan" class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="">Semua Golongan</option>
                    @foreach($golonganList as $key => $label)
                        <option value="{{ $key }}" {{ $golongan == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Ruangan</label>
                <select name="ruangan" class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="">Semua Ruangan</option>
                    @foreach($ruanganList as $r)
                        <option value="{{ $r->id }}" {{ $ruangan == $r->id ? 'selected' : '' }}>{{ $r->nama_ruangan }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Kategori</label>
                <select name="kategori" class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="">Semua Kategori</option>
                    @foreach($kategoriList as $k)
                        <option value="{{ $k->id }}" {{ $kategori == $k->id ? 'selected' : '' }}>{{ $k->nama_kategori }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-5 py-3 bg-slate-600 text-white rounded-xl font-semibold hover:bg-slate-700 transition">
                    <i class="fas fa-search mr-2"></i>Filter
                </button>
                <a href="{{ route('penyusutan.laporan-detail') }}" class="px-4 py-3 bg-slate-500/50 text-slate-300 rounded-xl hover:bg-slate-500 transition">
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
                        <th class="px-3 py-3 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">No</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Kode Aset</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Nama Aset</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Kategori</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Thn Peroleh</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Umur</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Nilai Perolehan</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Penyusutan/Thn</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Akum. Penyusutan</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Nilai Buku</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">%</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50 divide-slate-200">
                    @forelse($data as $index => $item)
                    <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50 transition-colors">
                        <td class="px-3 py-3 dark:text-slate-400 text-slate-600">{{ $index + 1 }}</td>
                        <td class="px-3 py-3">
                            <span class="font-mono text-sm dark:text-slate-300 text-slate-700">{{ $item['kode_aset'] }}</span>
                        </td>
                        <td class="px-3 py-3">
                            <p class="dark:text-white text-slate-800 font-medium text-sm">{{ Str::limit($item['nama_aset'], 30) }}</p>
                            <p class="text-xs dark:text-slate-500 text-slate-400">{{ $item['ruangan'] }}</p>
                        </td>
                        <td class="px-3 py-3">
                            <span class="px-2 py-1 text-xs bg-blue-500/20 text-blue-400 rounded-lg">{{ $item['kategori'] }}</span>
                        </td>
                        <td class="px-3 py-3 text-center dark:text-slate-400 text-slate-600">{{ $item['tahun_perolehan'] }}</td>
                        <td class="px-3 py-3 text-center">
                            @if($item['dapat_disusutkan'])
                                <span class="text-sm dark:text-slate-300 text-slate-700">{{ $item['umur_manfaat'] }} th</span>
                            @else
                                <span class="text-xs text-slate-500">-</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-right dark:text-slate-300 text-slate-700 text-sm">Rp {{ number_format($item['nilai_perolehan'], 0, ',', '.') }}</td>
                        <td class="px-3 py-3 text-right text-sm">
                            @if($item['dapat_disusutkan'])
                                <span class="dark:text-slate-300 text-slate-700">Rp {{ number_format($item['penyusutan_per_tahun'], 0, ',', '.') }}</span>
                            @else
                                <span class="text-slate-500">-</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-right text-amber-400 text-sm">Rp {{ number_format($item['akumulasi_penyusutan'], 0, ',', '.') }}</td>
                        <td class="px-3 py-3 text-right text-emerald-400 font-semibold text-sm">Rp {{ number_format($item['nilai_buku'], 0, ',', '.') }}</td>
                        <td class="px-3 py-3 text-center">
                            @if($item['dapat_disusutkan'])
                                @if($item['sudah_habis'] ?? false)
                                    <span class="px-2 py-1 text-xs bg-red-500/20 text-red-400 rounded-lg">100%</span>
                                @else
                                    <span class="px-2 py-1 text-xs bg-emerald-500/20 text-emerald-400 rounded-lg">{{ number_format($item['persentase_penyusutan'], 1) }}%</span>
                                @endif
                            @else
                                <span class="text-xs text-slate-500">-</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-center">
                            <a href="{{ route('penyusutan.kartu', $item['aset_id']) }}" class="p-2 bg-blue-500/20 text-blue-400 rounded-lg hover:bg-blue-500/30 transition inline-block" title="Kartu Penyusutan">
                                <i class="fas fa-file-alt"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="px-6 py-12 text-center dark:text-slate-500 text-slate-400">
                            <i class="fas fa-calculator text-4xl mb-3 block"></i>
                            Belum ada data aset
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
