@extends('layouts.app')

@section('title', 'Kelola Usulan Penerimaan Barang')
@section('header', 'Kelola Usulan Penerimaan Barang')
@section('breadcrumb', 'Verifikasi usulan penerimaan barang dari pengguna')

@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl p-4 border dark:border-slate-700/50 border-slate-200 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Total</p>
                    <p class="text-2xl font-bold dark:text-white text-slate-800">{{ $stats['total'] }}</p>
                </div>
                <div class="w-10 h-10 bg-blue-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-clipboard-list text-blue-400"></i>
                </div>
            </div>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl p-4 border dark:border-slate-700/50 border-slate-200 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Pending</p>
                    <p class="text-2xl font-bold text-amber-400">{{ $stats['pending'] }}</p>
                </div>
                <div class="w-10 h-10 bg-amber-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-clock text-amber-400"></i>
                </div>
            </div>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl p-4 border dark:border-slate-700/50 border-slate-200 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Diproses</p>
                    <p class="text-2xl font-bold text-blue-400">{{ $stats['diproses'] }}</p>
                </div>
                <div class="w-10 h-10 bg-blue-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-spinner text-blue-400"></i>
                </div>
            </div>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl p-4 border dark:border-slate-700/50 border-slate-200 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Disetujui</p>
                    <p class="text-2xl font-bold text-emerald-400">{{ $stats['disetujui'] }}</p>
                </div>
                <div class="w-10 h-10 bg-emerald-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-emerald-400"></i>
                </div>
            </div>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl p-4 border dark:border-slate-700/50 border-slate-200 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Ditolak</p>
                    <p class="text-2xl font-bold text-red-400">{{ $stats['ditolak'] }}</p>
                </div>
                <div class="w-10 h-10 bg-red-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-times-circle text-red-400"></i>
                </div>
            </div>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl p-4 border dark:border-slate-700/50 border-slate-200 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Revisi</p>
                    <p class="text-2xl font-bold text-purple-400">{{ $stats['revisi'] }}</p>
                </div>
                <div class="w-10 h-10 bg-purple-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-edit text-purple-400"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl p-6 border dark:border-slate-700/50 border-slate-200 shadow-lg">
        <form action="{{ route('usulan-penerimaan.admin.index') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm dark:text-slate-400 text-slate-500 mb-1">Pencarian</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Kode, nama barang, pengusul..." 
                        class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl pl-10 pr-4 py-2 dark:text-white text-slate-800 text-sm focus:ring-2 focus:ring-blue-500">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 dark:text-slate-400 text-slate-500"></i>
                </div>
            </div>
            <div>
                <label class="block text-sm dark:text-slate-400 text-slate-500 mb-1">Status</label>
                <select name="status" class="dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-2 dark:text-white text-slate-800 text-sm">
                    <option value="">Semua Status</option>
                    @foreach(\App\Models\UsulanPenerimaanBarang::getStatusOptions() as $key => $label)
                        <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm dark:text-slate-400 text-slate-500 mb-1">Ruangan</label>
                <select name="ruangan" class="dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-2 dark:text-white text-slate-800 text-sm">
                    <option value="">Semua Ruangan</option>
                    @foreach($ruanganList as $ruangan)
                        <option value="{{ $ruangan->id }}" {{ request('ruangan') == $ruangan->id ? 'selected' : '' }}>{{ $ruangan->nama_ruangan }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm dark:text-slate-400 text-slate-500 mb-1">Prioritas</label>
                <select name="prioritas" class="dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-2 dark:text-white text-slate-800 text-sm">
                    <option value="">Semua</option>
                    @foreach(\App\Models\UsulanPenerimaanBarang::getPrioritasOptions() as $key => $label)
                        <option value="{{ $key }}" {{ request('prioritas') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm dark:text-slate-400 text-slate-500 mb-1">Dari Tanggal</label>
                <input type="date" name="dari_tanggal" value="{{ request('dari_tanggal') }}" class="dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-2 dark:text-white text-slate-800 text-sm">
            </div>
            <div>
                <label class="block text-sm dark:text-slate-400 text-slate-500 mb-1">Sampai Tanggal</label>
                <input type="date" name="sampai_tanggal" value="{{ request('sampai_tanggal') }}" class="dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-2 dark:text-white text-slate-800 text-sm">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-xl text-sm font-semibold transition">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
                @if(request()->hasAny(['search', 'status', 'ruangan', 'prioritas', 'dari_tanggal', 'sampai_tanggal']))
                    <a href="{{ route('usulan-penerimaan.admin.index') }}" class="px-4 py-2 dark:bg-slate-600 bg-slate-300 dark:hover:bg-slate-500 hover:bg-slate-400 dark:text-white text-slate-700 rounded-xl text-sm font-semibold transition">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </div>
            <a href="{{ route('usulan-penerimaan.admin.export', request()->all()) }}" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl text-sm font-semibold transition">
                <i class="fas fa-download mr-2"></i>Export CSV
            </a>
        </form>
    </div>

    <!-- Table -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="dark:bg-slate-700/50 bg-slate-100">
                    <tr>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase">Kode</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase">Pengusul</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase">Barang</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase">Ruangan</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase">Jml</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase">Prioritas</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase">Status</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase">Tanggal</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50 divide-slate-200">
                    @forelse($usulan as $item)
                    <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50 transition {{ $item->prioritas == 'tinggi' && $item->is_pending ? 'dark:bg-red-500/5 bg-red-50' : '' }}">
                        <td class="px-4 py-4">
                            <span class="font-mono text-sm font-semibold dark:text-blue-400 text-blue-600">{{ $item->kode_usulan }}</span>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-full flex items-center justify-center mr-2">
                                    <i class="fas fa-user text-white text-xs"></i>
                                </div>
                                <div>
                                    <p class="font-medium dark:text-white text-slate-800 text-sm">{{ $item->user->name }}</p>
                                    <p class="text-xs dark:text-slate-500 text-slate-400">{{ $item->user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center">
                                @if($item->foto_barang && is_array($item->foto_barang) && count($item->foto_barang) > 0)
                                    @php
                                        $foto = $item->foto_barang[0];
                                        $fotoUrl = str_starts_with($foto, 'uploads/') ? asset($foto) : asset('storage/' . $foto);
                                    @endphp
                                    <img src="{{ $fotoUrl }}" class="w-10 h-10 rounded-lg object-cover mr-2 border dark:border-slate-600 border-slate-300" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="w-10 h-10 rounded-lg dark:bg-slate-700 bg-slate-200 items-center justify-center mr-2" style="display:none;">
                                        <i class="fas fa-box dark:text-slate-500 text-slate-400 text-xs"></i>
                                    </div>
                                @else
                                    <div class="w-10 h-10 rounded-lg dark:bg-slate-700 bg-slate-200 flex items-center justify-center mr-2">
                                        <i class="fas fa-box dark:text-slate-500 text-slate-400 text-xs"></i>
                                    </div>
                                @endif
                                <div>
                                    <p class="font-medium dark:text-white text-slate-800 text-sm">{{ Str::limit($item->nama_barang, 25) }}</p>
                                    <p class="text-xs dark:text-slate-500 text-slate-400">{{ $item->merk ?? '-' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <span class="text-sm dark:text-slate-300 text-slate-700">{{ $item->ruangan->nama_ruangan }}</span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="font-semibold dark:text-white text-slate-800">{{ $item->jumlah }}</span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ \App\Models\UsulanPenerimaanBarang::getPrioritasBadgeClass($item->prioritas) }}">
                                {{ $item->prioritas_label }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold border {{ \App\Models\UsulanPenerimaanBarang::getStatusBadgeClass($item->status) }}">
                                {{ $item->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="text-sm dark:text-slate-400 text-slate-500">{{ $item->tanggal_usulan->format('d/m/Y') }}</span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('usulan-penerimaan.admin.show', $item->id) }}" class="inline-flex items-center px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-xs font-semibold transition">
                                    <i class="fas fa-eye mr-1"></i>Detail
                                </a>
                                <form action="{{ route('usulan-penerimaan.admin.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus usulan ini? Data yang dihapus tidak dapat dikembalikan.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-semibold transition">
                                        <i class="fas fa-trash mr-1"></i>Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-16 h-16 dark:bg-slate-700 bg-slate-200 rounded-full flex items-center justify-center mb-4">
                                    <i class="fas fa-inbox dark:text-slate-500 text-slate-400 text-2xl"></i>
                                </div>
                                <p class="dark:text-slate-400 text-slate-500 font-medium">Tidak ada usulan penerimaan</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($usulan->hasPages())
        <div class="px-6 py-4 border-t dark:border-slate-700/50 border-slate-200">
            {{ $usulan->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
