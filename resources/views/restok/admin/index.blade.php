@extends('layouts.app')

@section('title', 'Kelola Permintaan Restok')
@section('header', 'Kelola Permintaan Restok Barang')
@section('breadcrumb', 'Daftar permintaan restok barang dari semua user')

@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl p-4 border dark:border-slate-700/50 border-slate-200 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Total</p>
                    <p class="text-2xl font-bold dark:text-white text-slate-800">{{ $stats['total'] }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-boxes-stacked text-blue-400 text-xl"></i>
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
                    <p class="text-2xl font-bold text-blue-400">{{ $stats['disetujui'] }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-thumbs-up text-blue-400 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl p-4 border dark:border-slate-700/50 border-slate-200 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Tersedia</p>
                    <p class="text-2xl font-bold text-emerald-400">{{ $stats['tersedia'] }}</p>
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
    </div>

    <!-- Filter -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl p-6 border dark:border-slate-700/50 border-slate-200 shadow-lg">
        <form action="{{ route('permintaan-restok.admin.index') }}" method="GET" class="flex flex-wrap items-center gap-3">
            <div class="relative">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode/barang/user..."
                    class="dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl pl-10 pr-4 py-2 dark:text-white text-slate-800 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent w-64">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 dark:text-slate-400 text-slate-500"></i>
            </div>
            <select name="status" class="dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-2 dark:text-white text-slate-800 text-sm focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu</option>
                <option value="disetujui" {{ request('status') == 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                <option value="tersedia" {{ request('status') == 'tersedia' ? 'selected' : '' }}>Tersedia</option>
                <option value="ditolak" {{ request('status') == 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                <option value="dibaca" {{ request('status') == 'dibaca' ? 'selected' : '' }}>Sudah Dibaca</option>
            </select>
            <select name="prioritas" class="dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-2 dark:text-white text-slate-800 text-sm focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Prioritas</option>
                <option value="tinggi" {{ request('prioritas') == 'tinggi' ? 'selected' : '' }}>Tinggi</option>
                <option value="sedang" {{ request('prioritas') == 'sedang' ? 'selected' : '' }}>Sedang</option>
                <option value="rendah" {{ request('prioritas') == 'rendah' ? 'selected' : '' }}>Rendah</option>
            </select>
            <input type="date" name="dari_tanggal" value="{{ request('dari_tanggal') }}" class="dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-2 dark:text-white text-slate-800 text-sm focus:ring-2 focus:ring-blue-500">
            <input type="date" name="sampai_tanggal" value="{{ request('sampai_tanggal') }}" class="dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-2 dark:text-white text-slate-800 text-sm focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-xl text-sm font-semibold transition">
                <i class="fas fa-filter mr-2"></i>Filter
            </button>
            @if(request()->hasAny(['search', 'status', 'prioritas', 'dari_tanggal', 'sampai_tanggal']))
                <a href="{{ route('permintaan-restok.admin.index') }}" class="px-4 py-2 dark:bg-slate-600 bg-slate-300 dark:hover:bg-slate-500 hover:bg-slate-400 dark:text-white text-slate-700 rounded-xl text-sm font-semibold transition">
                    <i class="fas fa-times mr-2"></i>Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="dark:bg-slate-700/50 bg-slate-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider">Kode</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider">Pengajuan</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider">Barang</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider">Stok</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider">Diminta</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider">Prioritas</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50 divide-slate-200">
                    @forelse($permintaan as $item)
                    <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50 transition">
                        <td class="px-6 py-4">
                            <span class="font-mono text-sm font-semibold dark:text-orange-400 text-orange-600">{{ $item->kode_restok }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-semibold dark:text-white text-slate-800">{{ $item->user->name ?? '-' }}</p>
                            <p class="text-xs dark:text-slate-400 text-slate-500">{{ $item->user->getRoleName() ?? '' }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-semibold dark:text-white text-slate-800">{{ $item->item->name ?? '-' }}</p>
                            <p class="text-xs dark:text-slate-400 text-slate-500">{{ $item->item->code ?? '' }}</p>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="font-semibold {{ ($item->item->stock ?? 0) == 0 ? 'text-red-400' : 'text-amber-400' }}">
                                {{ $item->item->stock ?? 0 }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="font-semibold dark:text-white text-slate-800">{{ $item->jumlah_diminta }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ \App\Models\PermintaanRestok::getPrioritasBadgeClass($item->prioritas) }}">
                                {{ $item->prioritas_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold border {{ \App\Models\PermintaanRestok::getStatusBadgeClass($item->status) }}">
                                {{ $item->status_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-sm dark:text-slate-400 text-slate-500">{{ $item->tanggal_permintaan->format('d/m/Y') }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('permintaan-restok.admin.show', $item->id) }}" class="w-8 h-8 rounded-lg bg-blue-500/20 hover:bg-blue-500/40 flex items-center justify-center transition" title="Detail">
                                    <i class="fas fa-eye text-blue-400 text-sm"></i>
                                </a>
                                @if($item->is_pending)
                                    <form action="{{ route('permintaan-restok.admin.approve', $item->id) }}" method="POST" class="inline" onsubmit="return confirm('Setujui permintaan restok ini?')">
                                        @csrf
                                        <button type="submit" class="w-8 h-8 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/40 flex items-center justify-center transition" title="Setujui">
                                            <i class="fas fa-check text-emerald-400 text-sm"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-20 h-20 dark:bg-slate-700 bg-slate-200 rounded-full flex items-center justify-center mb-4">
                                    <i class="fas fa-boxes-stacked dark:text-slate-500 text-slate-400 text-3xl"></i>
                                </div>
                                <p class="dark:text-slate-400 text-slate-500 text-lg font-medium">Belum ada permintaan restok</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($permintaan->hasPages())
        <div class="px-6 py-4 border-t dark:border-slate-700/50 border-slate-200">
            {{ $permintaan->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
