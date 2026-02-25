@extends('layouts.app')

@section('title', 'Kategori Aset')
@section('header', 'Kategori Aset')
@section('breadcrumb', 'Aset / Kategori')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold dark:text-white text-slate-800">Daftar Kategori Aset</h3>
            <p class="text-sm dark:text-slate-400 text-slate-600">Kelola kategori untuk pengelompokan aset dengan umur manfaat</p>
        </div>
        <a href="{{ route('kategori-aset.create') }}" class="px-5 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:from-emerald-600 hover:to-teal-700 transition-all shadow-lg">
            <i class="fas fa-plus mr-2"></i>Tambah Kategori
        </a>
    </div>

    <!-- Table -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="dark:bg-slate-700/30 bg-slate-100">
                    <tr>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">No</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Nama Kategori</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Golongan</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Umur Manfaat</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Jumlah Aset</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Status</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50 divide-slate-200">
                    @php $no = ($kategori->currentPage() - 1) * $kategori->perPage() + 1; @endphp
                    @forelse($kategori as $k)
                    <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-4 dark:text-slate-400 text-slate-600">{{ $no++ }}</td>
                        <td class="px-4 py-4">
                            <p class="dark:text-white text-slate-800 font-medium">{{ $k->nama_kategori }}</p>
                            @if($k->kode_golongan)
                                <p class="text-xs dark:text-slate-500 text-slate-400 font-mono">{{ $k->kode_golongan }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            @php
                                $golonganLabel = [
                                    'tanah' => 'Tanah',
                                    'peralatan_mesin' => 'Peralatan & Mesin',
                                    'gedung_bangunan' => 'Gedung & Bangunan',
                                    'jalan_irigasi' => 'Jalan & Irigasi',
                                    'aset_tetap_lainnya' => 'Aset Lainnya',
                                    'kdp' => 'KDP',
                                ];
                                $golonganColor = [
                                    'tanah' => 'bg-amber-500/20 text-amber-400',
                                    'peralatan_mesin' => 'bg-blue-500/20 text-blue-400',
                                    'gedung_bangunan' => 'bg-purple-500/20 text-purple-400',
                                    'jalan_irigasi' => 'bg-cyan-500/20 text-cyan-400',
                                    'aset_tetap_lainnya' => 'bg-pink-500/20 text-pink-400',
                                    'kdp' => 'bg-slate-500/20 text-slate-400',
                                ];
                            @endphp
                            <span class="px-2 py-1 text-xs rounded-lg {{ $golonganColor[$k->golongan] ?? 'bg-slate-500/20 text-slate-400' }}">
                                {{ $golonganLabel[$k->golongan] ?? $k->golongan }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            @if($k->umur_manfaat > 0)
                                <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 rounded-lg font-semibold">{{ $k->umur_manfaat }} th</span>
                            @else
                                <span class="px-3 py-1 bg-slate-500/20 text-slate-400 rounded-lg text-xs">Tidak disusutkan</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="px-3 py-1 bg-blue-500/20 text-blue-400 rounded-lg font-semibold">{{ $k->barang_aset_count }}</span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            @if($k->is_active)
                                <span class="px-2 py-1 text-xs bg-emerald-500/20 text-emerald-400 rounded-lg">Aktif</span>
                            @else
                                <span class="px-2 py-1 text-xs bg-red-500/20 text-red-400 rounded-lg">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('barang-aset.index', ['kategori' => $k->id]) }}" class="p-2 bg-cyan-500/20 text-cyan-400 rounded-lg hover:bg-cyan-500/30 transition" title="Lihat Aset">
                                    <i class="fas fa-boxes"></i>
                                </a>
                                <a href="{{ route('penyusutan.laporan-detail', ['kategori' => $k->id]) }}" class="p-2 bg-amber-500/20 text-amber-400 rounded-lg hover:bg-amber-500/30 transition" title="Lihat Penyusutan">
                                    <i class="fas fa-calculator"></i>
                                </a>
                                <a href="{{ route('kategori-aset.edit', $k->id) }}" class="p-2 bg-blue-500/20 text-blue-400 rounded-lg hover:bg-blue-500/30 transition" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('kategori-aset.destroy', $k->id) }}" method="POST" class="inline" onsubmit="return confirm('Yakin hapus kategori ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center dark:text-slate-500 text-slate-400">
                            <i class="fas fa-tags text-4xl mb-3 block"></i>
                            Belum ada kategori aset
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($kategori->hasPages())
        <div class="p-4 border-t dark:border-slate-700/50 border-slate-200">
            {{ $kategori->links() }}
        </div>
        @endif
    </div>

    <!-- Info Golongan -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6">
        <h4 class="font-semibold dark:text-white text-slate-800 mb-4"><i class="fas fa-info-circle text-blue-400 mr-2"></i>Standar Golongan Aset & Umur Manfaat</h4>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 text-sm">
            <div class="p-3 dark:bg-slate-700/30 bg-slate-50 rounded-xl text-center">
                <p class="dark:text-amber-400 text-amber-600 font-semibold">Tanah</p>
                <p class="dark:text-slate-400 text-slate-500 text-xs">Tidak disusutkan</p>
            </div>
            <div class="p-3 dark:bg-slate-700/30 bg-slate-50 rounded-xl text-center">
                <p class="dark:text-blue-400 text-blue-600 font-semibold">Peralatan & Mesin</p>
                <p class="dark:text-slate-400 text-slate-500 text-xs">5 tahun</p>
            </div>
            <div class="p-3 dark:bg-slate-700/30 bg-slate-50 rounded-xl text-center">
                <p class="dark:text-purple-400 text-purple-600 font-semibold">Gedung & Bangunan</p>
                <p class="dark:text-slate-400 text-slate-500 text-xs">50 tahun</p>
            </div>
            <div class="p-3 dark:bg-slate-700/30 bg-slate-50 rounded-xl text-center">
                <p class="dark:text-cyan-400 text-cyan-600 font-semibold">Jalan & Irigasi</p>
                <p class="dark:text-slate-400 text-slate-500 text-xs">10 tahun</p>
            </div>
            <div class="p-3 dark:bg-slate-700/30 bg-slate-50 rounded-xl text-center">
                <p class="dark:text-pink-400 text-pink-600 font-semibold">Aset Lainnya</p>
                <p class="dark:text-slate-400 text-slate-500 text-xs">4 tahun</p>
            </div>
            <div class="p-3 dark:bg-slate-700/30 bg-slate-50 rounded-xl text-center">
                <p class="dark:text-slate-400 text-slate-600 font-semibold">KDP</p>
                <p class="dark:text-slate-400 text-slate-500 text-xs">Tidak disusutkan</p>
            </div>
        </div>
    </div>
</div>
@endsection
