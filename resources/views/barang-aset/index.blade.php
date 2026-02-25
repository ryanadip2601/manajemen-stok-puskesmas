@extends('layouts.app')

@section('title', 'Barang Aset')
@section('header', 'Barang Aset')
@section('breadcrumb', 'Aset / Barang Aset')

@section('content')
<div class="space-y-6">
    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="dark:bg-slate-800/50 bg-white rounded-xl shadow-lg dark:border dark:border-slate-700/50 border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                    <i class="fas fa-boxes text-blue-400"></i>
                </div>
                <div>
                    <p class="text-xs dark:text-slate-400 text-slate-600">Total Aset</p>
                    <p class="text-xl font-bold dark:text-white text-slate-800">{{ $summary['total'] }}</p>
                </div>
            </div>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-xl shadow-lg dark:border dark:border-slate-700/50 border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                    <i class="fas fa-check-circle text-emerald-400"></i>
                </div>
                <div>
                    <p class="text-xs dark:text-slate-400 text-slate-600">Kondisi Baik</p>
                    <p class="text-xl font-bold text-emerald-500">{{ $summary['baik'] }}</p>
                </div>
            </div>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-xl shadow-lg dark:border dark:border-slate-700/50 border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-amber-400"></i>
                </div>
                <div>
                    <p class="text-xs dark:text-slate-400 text-slate-600">Rusak Ringan</p>
                    <p class="text-xl font-bold text-amber-500">{{ $summary['rusak_ringan'] }}</p>
                </div>
            </div>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-xl shadow-lg dark:border dark:border-slate-700/50 border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-red-500/20 flex items-center justify-center">
                    <i class="fas fa-times-circle text-red-400"></i>
                </div>
                <div>
                    <p class="text-xs dark:text-slate-400 text-slate-600">Rusak Berat</p>
                    <p class="text-xl font-bold text-red-500">{{ $summary['rusak_berat'] }}</p>
                </div>
            </div>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-xl shadow-lg dark:border dark:border-slate-700/50 border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                    <i class="fas fa-tools text-purple-400"></i>
                </div>
                <div>
                    <p class="text-xs dark:text-slate-400 text-slate-600">Perlu Perbaikan</p>
                    <p class="text-xl font-bold text-purple-500">{{ $summary['butuh_perbaikan'] }}</p>
                </div>
            </div>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-xl shadow-lg dark:border dark:border-slate-700/50 border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-cyan-500/20 flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-cyan-400"></i>
                </div>
                <div>
                    <p class="text-xs dark:text-slate-400 text-slate-600">Total Nilai</p>
                    <p class="text-sm font-bold text-cyan-400">Rp {{ number_format($summary['total_nilai'] / 1000000, 1) }}Jt</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Header & Actions -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold dark:text-white text-slate-800">Daftar Barang Aset</h3>
            <p class="text-sm dark:text-slate-400 text-slate-600">
                @if(auth()->user()->canManageWarehouse())
                    Kelola inventaris aset tetap dengan penyusutan
                @else
                    Lihat daftar aset dan ajukan pemeliharaan
                @endif
            </p>
        </div>
        @if(auth()->user()->canManageWarehouse())
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('barang-aset.export') }}" class="px-4 py-2 bg-amber-500/20 text-amber-400 rounded-xl font-medium hover:bg-amber-500/30 transition">
                <i class="fas fa-file-excel mr-2"></i>Export
            </a>
            <a href="{{ route('barang-aset.create') }}" class="px-5 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:from-emerald-600 hover:to-teal-700 transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Tambah Aset
            </a>
        </div>
        @endif
    </div>

    <!-- Filters -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6">
        <form method="GET" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <div>
                <input type="text" name="search" value="{{ $activeFilters['search'] ?? '' }}" placeholder="Cari nama/kode..."
                    class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-2 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-sm">
            </div>
            <div>
                <select name="kategori" class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-2 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-sm">
                    <option value="">Semua Kategori</option>
                    @foreach($kategoriList as $k)
                        <option value="{{ $k->id }}" @selected(($activeFilters['kategori'] ?? '') == $k->id)>{{ $k->nama_kategori }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="ruangan" class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-2 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-sm">
                    <option value="">Semua Ruangan</option>
                    @foreach($ruanganList as $r)
                        <option value="{{ $r->id }}" @selected(($activeFilters['ruangan'] ?? '') == $r->id)>{{ $r->nama_ruangan }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="kondisi" class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-2 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-sm">
                    <option value="">Semua Kondisi</option>
                    @foreach($kondisiOptions as $key => $label)
                        <option value="{{ $key }}" @selected(($activeFilters['kondisi'] ?? '') == $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <input type="number" name="tahun_perolehan" value="{{ $activeFilters['tahun_perolehan'] ?? '' }}" placeholder="Tahun"
                    class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-2 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-sm">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-500 text-white rounded-xl font-medium hover:bg-blue-600 transition text-sm">
                    <i class="fas fa-search"></i>
                </button>
                <a href="{{ route('barang-aset.clear-filter') }}" class="px-4 py-2 dark:bg-slate-700 bg-slate-200 dark:text-slate-300 text-slate-700 rounded-xl hover:bg-slate-300 dark:hover:bg-slate-600 transition" title="Reset Filter">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="dark:bg-slate-700/30 bg-slate-100">
                    <tr>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">No</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Kode / Nama</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Kategori</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Lokasi</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Kondisi</th>
                        <th class="px-4 py-4 text-right text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Nilai Perolehan</th>
                        <th class="px-4 py-4 text-right text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Nilai Buku</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50 divide-slate-200">
                    @php $no = ($aset->currentPage() - 1) * $aset->perPage() + 1; @endphp
                    @forelse($aset as $item)
                    @php $penyusutan = $item->hitungPenyusutan(); @endphp
                    <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-4 dark:text-slate-400 text-slate-600">{{ $no++ }}</td>
                        <td class="px-4 py-4">
                            <div>
                                <p class="dark:text-white text-slate-800 font-medium">{{ $item->nama_aset }}</p>
                                <p class="text-xs dark:text-slate-500 text-slate-400 font-mono">{{ $item->kode_aset }}</p>
                                @if($item->merk)
                                    <p class="text-xs dark:text-slate-500 text-slate-400">{{ $item->merk }}</p>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <span class="px-2 py-1 text-xs rounded-lg dark:bg-slate-600/50 bg-slate-200 dark:text-slate-300 text-slate-700">
                                {{ $item->kategoriAset->nama_kategori ?? '-' }}
                            </span>
                        </td>
                        <td class="px-4 py-4 dark:text-slate-300 text-slate-700">
                            {{ $item->ruangan->nama_ruangan ?? '-' }}
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="px-2 py-1 text-xs rounded-lg {{ \App\Models\BarangAset::getKondisiBadgeClass($item->kondisi) }}">
                                {{ $item->kondisi_label }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-right dark:text-slate-300 text-slate-700 font-mono">
                            Rp {{ number_format($item->harga_perolehan ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-4 text-right">
                            <p class="font-semibold {{ ($penyusutan['sudah_habis'] ?? false) ? 'text-red-400' : 'text-emerald-500' }} font-mono">
                                Rp {{ number_format($penyusutan['nilai_buku'] ?? 0, 0, ',', '.') }}
                            </p>
                            <p class="text-xs dark:text-slate-500 text-slate-400">{{ number_format($penyusutan['persentase_penyusutan'] ?? 0, 0) }}% tersusutkan</p>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <div class="flex items-center justify-center gap-1">
                                @if(auth()->user()->canManageWarehouse())
                                <a href="{{ route('barang-aset.show', $item->id) }}" class="p-2 bg-blue-500/20 text-blue-400 rounded-lg hover:bg-blue-500/30 transition" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('penyusutan.kartu', $item->id) }}" class="p-2 bg-amber-500/20 text-amber-400 rounded-lg hover:bg-amber-500/30 transition" title="Penyusutan">
                                    <i class="fas fa-calculator"></i>
                                </a>
                                <a href="{{ route('barang-aset.edit', $item->id) }}" class="p-2 bg-emerald-500/20 text-emerald-400 rounded-lg hover:bg-emerald-500/30 transition" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('barang-aset.destroy', $item->id) }}" method="POST" class="inline" onsubmit="return confirm('Yakin hapus aset ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @else
                                {{-- Staff: hanya bisa lihat dan ajukan pemeliharaan --}}
                                <a href="{{ route('staff.barang-aset.show', $item->id) }}" class="p-2 bg-blue-500/20 text-blue-400 rounded-lg hover:bg-blue-500/30 transition" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('staff.pemeliharaan.create', ['aset_id' => $item->id]) }}" class="p-2 bg-purple-500/20 text-purple-400 rounded-lg hover:bg-purple-500/30 transition" title="Ajukan Pemeliharaan">
                                    <i class="fas fa-tools"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center dark:text-slate-500 text-slate-400">
                            <i class="fas fa-boxes text-4xl mb-3 block"></i>
                            Belum ada data barang aset
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($aset->hasPages())
        <div class="p-4 border-t dark:border-slate-700/50 border-slate-200">
            {{ $aset->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
