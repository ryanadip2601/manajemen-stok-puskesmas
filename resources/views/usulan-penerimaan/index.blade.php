@extends('layouts.app')

@section('title', 'Usulan Penerimaan Barang')
@section('header', 'Usulan Penerimaan Barang')
@section('breadcrumb', 'Daftar usulan penerimaan barang Anda')

@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl p-4 border dark:border-slate-700/50 border-slate-200 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Total Usulan</p>
                    <p class="text-2xl font-bold dark:text-white text-slate-800">{{ $stats['total'] }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-clipboard-list text-blue-400 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl p-4 border dark:border-slate-700/50 border-slate-200 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Menunggu</p>
                    <p class="text-2xl font-bold text-amber-400">{{ $stats['pending'] }}</p>
                </div>
                <div class="w-12 h-12 bg-amber-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-clock text-amber-400 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl p-4 border dark:border-slate-700/50 border-slate-200 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Disetujui</p>
                    <p class="text-2xl font-bold text-emerald-400">{{ $stats['disetujui'] }}</p>
                </div>
                <div class="w-12 h-12 bg-emerald-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-emerald-400 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl p-4 border dark:border-slate-700/50 border-slate-200 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Ditolak</p>
                    <p class="text-2xl font-bold text-red-400">{{ $stats['ditolak'] }}</p>
                </div>
                <div class="w-12 h-12 bg-red-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-times-circle text-red-400 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl p-4 border dark:border-slate-700/50 border-slate-200 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Perlu Revisi</p>
                    <p class="text-2xl font-bold text-purple-400">{{ $stats['revisi'] }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-edit text-purple-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Actions -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl p-6 border dark:border-slate-700/50 border-slate-200 shadow-lg">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <form action="{{ route('usulan-penerimaan.index') }}" method="GET" class="flex flex-wrap items-center gap-3">
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode/nama barang..." 
                        class="dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl pl-10 pr-4 py-2 dark:text-white text-slate-800 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent w-64">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 dark:text-slate-400 text-slate-500"></i>
                </div>
                <select name="status" class="dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-2 dark:text-white text-slate-800 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Status</option>
                    @foreach(\App\Models\UsulanPenerimaanBarang::getStatusOptions() as $key => $label)
                        <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="ruangan" class="dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-2 dark:text-white text-slate-800 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Ruangan</option>
                    @foreach($ruanganList as $ruangan)
                        <option value="{{ $ruangan->id }}" {{ request('ruangan') == $ruangan->id ? 'selected' : '' }}>{{ $ruangan->nama_ruangan }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-xl text-sm font-semibold transition">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
                @if(request()->hasAny(['search', 'status', 'ruangan']))
                    <a href="{{ route('usulan-penerimaan.index') }}" class="px-4 py-2 dark:bg-slate-600 bg-slate-300 dark:hover:bg-slate-500 hover:bg-slate-400 dark:text-white text-slate-700 rounded-xl text-sm font-semibold transition">
                        <i class="fas fa-times mr-2"></i>Reset
                    </a>
                @endif
            </form>
            <div class="flex items-center gap-3">
                @if(auth()->user()->canManageWarehouse())
                    <a href="{{ route('usulan-penerimaan.export', request()->all()) }}" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl text-sm font-semibold transition flex items-center">
                        <i class="fas fa-download mr-2"></i>Export CSV
                    </a>
                @endif
                <a href="{{ route('usulan-penerimaan.create') }}" class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white rounded-xl font-semibold transition shadow-lg shadow-emerald-500/25 flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i>Buat Usulan Baru
                </a>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="dark:bg-slate-700/50 bg-slate-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider">Kode Usulan</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider">Barang</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider">Ruangan</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider">Jumlah</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider">Prioritas</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50 divide-slate-200">
                    @forelse($usulan as $item)
                    <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50 transition">
                        <td class="px-6 py-4">
                            <span class="font-mono text-sm font-semibold dark:text-blue-400 text-blue-600">{{ $item->kode_usulan }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                @if($item->foto_barang && is_array($item->foto_barang) && count($item->foto_barang) > 0)
                                    @php
                                        $foto = $item->foto_barang[0];
                                        $fotoUrl = str_starts_with($foto, 'uploads/') ? asset($foto) : asset('storage/' . $foto);
                                    @endphp
                                    <img src="{{ $fotoUrl }}" class="w-12 h-12 rounded-lg object-cover mr-3 border dark:border-slate-600 border-slate-300" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="w-12 h-12 rounded-lg dark:bg-slate-700 bg-slate-200 items-center justify-center mr-3" style="display:none;">
                                        <i class="fas fa-box dark:text-slate-500 text-slate-400"></i>
                                    </div>
                                @else
                                    <div class="w-12 h-12 rounded-lg dark:bg-slate-700 bg-slate-200 flex items-center justify-center mr-3">
                                        <i class="fas fa-box dark:text-slate-500 text-slate-400"></i>
                                    </div>
                                @endif
                                <div>
                                    <p class="font-semibold dark:text-white text-slate-800">{{ $item->nama_barang }}</p>
                                    <p class="text-xs dark:text-slate-400 text-slate-500">{{ $item->merk ?? '-' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="dark:text-slate-300 text-slate-700">{{ $item->ruangan->nama_ruangan }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="font-semibold dark:text-white text-slate-800">{{ $item->jumlah }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ \App\Models\UsulanPenerimaanBarang::getPrioritasBadgeClass($item->prioritas) }}">
                                {{ $item->prioritas_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold border {{ \App\Models\UsulanPenerimaanBarang::getStatusBadgeClass($item->status) }}">
                                {{ $item->status_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-sm dark:text-slate-400 text-slate-500">{{ $item->tanggal_usulan->format('d/m/Y') }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('usulan-penerimaan.show', $item->id) }}" class="w-8 h-8 rounded-lg bg-blue-500/20 hover:bg-blue-500/40 flex items-center justify-center transition" title="Detail">
                                    <i class="fas fa-eye text-blue-400 text-sm"></i>
                                </a>
                                @if($item->can_edit)
                                    <a href="{{ route('usulan-penerimaan.edit', $item->id) }}" class="w-8 h-8 rounded-lg bg-amber-500/20 hover:bg-amber-500/40 flex items-center justify-center transition" title="Edit">
                                        <i class="fas fa-edit text-amber-400 text-sm"></i>
                                    </a>
                                @endif
                                @if($item->can_cancel)
                                    <form action="{{ route('usulan-penerimaan.cancel', $item->id) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin membatalkan usulan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded-lg bg-red-500/20 hover:bg-red-500/40 flex items-center justify-center transition" title="Batalkan">
                                            <i class="fas fa-times text-red-400 text-sm"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-20 h-20 dark:bg-slate-700 bg-slate-200 rounded-full flex items-center justify-center mb-4">
                                    <i class="fas fa-inbox dark:text-slate-500 text-slate-400 text-3xl"></i>
                                </div>
                                <p class="dark:text-slate-400 text-slate-500 text-lg font-medium">Belum ada usulan penerimaan</p>
                                <p class="dark:text-slate-500 text-slate-400 text-sm mt-1">Klik tombol "Buat Usulan Baru" untuk membuat usulan pertama Anda</p>
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
