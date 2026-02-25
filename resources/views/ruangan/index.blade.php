@extends('layouts.app')

@section('title', 'Data Ruangan')
@section('header', 'Data Ruangan')
@section('breadcrumb', 'Aset / Ruangan')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold dark:text-white">Daftar Ruangan</h3>
            <p class="text-sm dark:text-slate-400">Kelola data ruangan untuk penempatan aset</p>
        </div>
        <a href="{{ route('ruangan.create') }}" class="px-5 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:from-emerald-600 hover:to-teal-700 transition-all shadow-lg">
            <i class="fas fa-plus mr-2"></i>Tambah Ruangan
        </a>
    </div>

    <!-- Filter -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 p-6">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium dark:text-slate-400 mb-2">Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama atau lokasi..." 
                    class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <button type="submit" class="px-5 py-3 bg-slate-600 text-white rounded-xl font-semibold hover:bg-slate-700 transition">
                <i class="fas fa-search mr-2"></i>Cari
            </button>
        </form>
    </div>

    <!-- Table -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="dark:bg-slate-700/30 bg-slate-100">
                    <tr>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 uppercase">No</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 uppercase">Nama Ruangan</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 uppercase">Lokasi</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 uppercase">Jumlah Aset</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 uppercase">Status</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50">
                    @php $no = ($ruangan->currentPage() - 1) * $ruangan->perPage() + 1; @endphp
                    @forelse($ruangan as $r)
                    <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-4 dark:text-slate-400">{{ $no++ }}</td>
                        <td class="px-4 py-4 dark:text-white font-medium">{{ $r->nama_ruangan }}</td>
                        <td class="px-4 py-4 dark:text-slate-300">{{ $r->lokasi ?? '-' }}</td>
                        <td class="px-4 py-4 text-center">
                            <span class="px-3 py-1 bg-blue-500/20 text-blue-400 rounded-lg font-semibold">{{ $r->barang_aset_count }}</span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            @if($r->is_active)
                                <span class="px-2 py-1 text-xs bg-emerald-500/20 text-emerald-400 rounded">Aktif</span>
                            @else
                                <span class="px-2 py-1 text-xs bg-red-500/20 text-red-400 rounded">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('ruangan.show', $r->id) }}" class="p-2 bg-blue-500/20 text-blue-400 rounded-lg hover:bg-blue-500/30 transition"><i class="fas fa-eye"></i></a>
                                <a href="{{ route('barang-aset.index', ['ruangan' => $r->id]) }}" class="p-2 bg-cyan-500/20 text-cyan-400 rounded-lg hover:bg-cyan-500/30 transition" title="Lihat Aset"><i class="fas fa-boxes"></i></a>
                                <a href="{{ route('ruangan.edit', $r->id) }}" class="p-2 bg-amber-500/20 text-amber-400 rounded-lg hover:bg-amber-500/30 transition"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('ruangan.destroy', $r->id) }}" method="POST" class="inline" onsubmit="return confirm('Yakin hapus ruangan ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-12 text-center dark:text-slate-500"><i class="fas fa-door-open text-4xl mb-3 block"></i>Belum ada data ruangan</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($ruangan->hasPages())<div class="p-4 border-t dark:border-slate-700/50">{{ $ruangan->links() }}</div>@endif
    </div>
</div>
@endsection
