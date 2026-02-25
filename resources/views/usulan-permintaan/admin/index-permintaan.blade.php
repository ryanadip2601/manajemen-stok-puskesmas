@extends('layouts.app')

@section('title', 'Verifikasi Permintaan Barang Habis Pakai')

@section('header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold dark:text-white text-slate-800">Verifikasi Permintaan Barang Habis Pakai</h1>
            <p class="text-sm dark:text-slate-400 text-slate-500 mt-1">Kelola dan verifikasi semua usulan permintaan barang dari seluruh unit</p>
        </div>
    </div>
@endsection

@section('breadcrumb')
    <nav class="flex items-center space-x-2 text-sm dark:text-slate-400 text-slate-500">
        <a href="{{ route('dashboard') }}" class="hover:text-emerald-500 transition-colors">
            <i class="fas fa-home"></i>
        </a>
        <span>/</span>
        <span class="dark:text-white text-slate-800 font-medium">Verifikasi Permintaan Barang</span>
    </nav>
@endsection

@section('content')
    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
        {{-- Total --}}
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-lg border dark:border-slate-700/50 border-slate-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                    <i class="fas fa-clipboard-list text-white text-sm"></i>
                </div>
                <span class="text-2xl font-bold dark:text-white text-slate-800">{{ number_format($stats['total'] ?? 0) }}</span>
            </div>
            <p class="text-xs dark:text-slate-400 text-slate-500 font-medium">Total Permintaan</p>
        </div>

        {{-- Menunggu --}}
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-lg border dark:border-slate-700/50 border-slate-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-yellow-500 to-amber-600 flex items-center justify-center">
                    <i class="fas fa-clock text-white text-sm"></i>
                </div>
                <span class="text-2xl font-bold dark:text-white text-slate-800">{{ number_format($stats['pending'] ?? 0) }}</span>
            </div>
            <p class="text-xs dark:text-slate-400 text-slate-500 font-medium">Menunggu</p>
        </div>

        {{-- Diproses --}}
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-lg border dark:border-slate-700/50 border-slate-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500 to-cyan-600 flex items-center justify-center">
                    <i class="fas fa-spinner text-white text-sm"></i>
                </div>
                <span class="text-2xl font-bold dark:text-white text-slate-800">{{ number_format($stats['diproses'] ?? 0) }}</span>
            </div>
            <p class="text-xs dark:text-slate-400 text-slate-500 font-medium">Diproses</p>
        </div>

        {{-- Disetujui --}}
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-lg border dark:border-slate-700/50 border-slate-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-green-600 flex items-center justify-center">
                    <i class="fas fa-check-circle text-white text-sm"></i>
                </div>
                <span class="text-2xl font-bold dark:text-white text-slate-800">{{ number_format($stats['disetujui'] ?? 0) }}</span>
            </div>
            <p class="text-xs dark:text-slate-400 text-slate-500 font-medium">Disetujui</p>
        </div>

        {{-- Ditolak --}}
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-lg border dark:border-slate-700/50 border-slate-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center">
                    <i class="fas fa-times-circle text-white text-sm"></i>
                </div>
                <span class="text-2xl font-bold dark:text-white text-slate-800">{{ number_format($stats['ditolak'] ?? 0) }}</span>
            </div>
            <p class="text-xs dark:text-slate-400 text-slate-500 font-medium">Ditolak</p>
        </div>

        {{-- Revisi --}}
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-lg border dark:border-slate-700/50 border-slate-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-violet-600 flex items-center justify-center">
                    <i class="fas fa-edit text-white text-sm"></i>
                </div>
                <span class="text-2xl font-bold dark:text-white text-slate-800">{{ number_format($stats['revisi'] ?? 0) }}</span>
            </div>
            <p class="text-xs dark:text-slate-400 text-slate-500 font-medium">Revisi</p>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-lg border dark:border-slate-700/50 border-slate-200 p-6 mb-6">
        <div class="flex items-center gap-2 mb-4">
            <i class="fas fa-filter dark:text-slate-400 text-slate-500"></i>
            <h3 class="font-semibold dark:text-white text-slate-800">Filter Pencarian</h3>
        </div>

        <form action="{{ route('usulan-permintaan-barang.admin.index') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                {{-- Search --}}
                <div>
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Pencarian</label>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 dark:text-slate-500 text-slate-400 text-sm"></i>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Kode, nama barang&hellip;"
                            class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 border rounded-xl pl-10 pr-4 py-3 dark:text-white text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 placeholder:dark:text-slate-500 placeholder:text-slate-400">
                    </div>
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Status</label>
                    <select name="status"
                        class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 border rounded-xl px-4 py-3 dark:text-white text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="">Semua Status</option>
                        @foreach(\App\Models\UsulanPermintaanBarang::getStatusOptions() as $key => $label)
                            <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Ruangan --}}
                <div>
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Ruangan</label>
                    <select name="ruangan_id"
                        class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 border rounded-xl px-4 py-3 dark:text-white text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="">Semua Ruangan</option>
                        @foreach($ruanganList as $ruangan)
                            <option value="{{ $ruangan->id }}" {{ request('ruangan_id') == $ruangan->id ? 'selected' : '' }}>{{ $ruangan->nama_ruangan }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Prioritas --}}
                <div>
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Prioritas</label>
                    <select name="prioritas"
                        class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 border rounded-xl px-4 py-3 dark:text-white text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="">Semua Prioritas</option>
                        @foreach(\App\Models\UsulanPermintaanBarang::getPrioritasOptions() as $key => $label)
                            <option value="{{ $key }}" {{ request('prioritas') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Dari Tanggal --}}
                <div>
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Dari Tanggal</label>
                    <input type="date" name="dari_tanggal" value="{{ request('dari_tanggal') }}"
                        class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 border rounded-xl px-4 py-3 dark:text-white text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                {{-- Sampai Tanggal --}}
                <div>
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Sampai Tanggal</label>
                    <input type="date" name="sampai_tanggal" value="{{ request('sampai_tanggal') }}"
                        class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 border rounded-xl px-4 py-3 dark:text-white text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div class="flex items-center gap-3 mt-4">
                <button type="submit"
                    class="inline-flex items-center gap-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 shadow-lg shadow-emerald-500/25">
                    <i class="fas fa-search text-xs"></i>
                    Filter
                </button>
                <a href="{{ route('usulan-permintaan-barang.admin.index') }}"
                    class="inline-flex items-center gap-2 dark:text-slate-400 text-slate-500 hover:text-emerald-500 dark:hover:text-emerald-400 text-sm font-medium transition-colors">
                    <i class="fas fa-undo text-xs"></i>
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-lg border dark:border-slate-700/50 border-slate-200 overflow-hidden">
        <div class="p-6 border-b dark:border-slate-700/50 border-slate-200">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold dark:text-white text-slate-800">
                    <i class="fas fa-list-alt mr-2 text-emerald-500"></i>
                    Daftar Permintaan
                </h3>
                <span class="text-sm dark:text-slate-400 text-slate-500">
                    {{ $usulan->total() }} permintaan ditemukan
                </span>
            </div>
        </div>

        @if($usulan->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="dark:bg-slate-700/30 bg-slate-50">
                            <th class="text-left px-6 py-4 text-xs font-semibold dark:text-slate-400 text-slate-500 uppercase tracking-wider">Kode</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold dark:text-slate-400 text-slate-500 uppercase tracking-wider">Pengusul</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold dark:text-slate-400 text-slate-500 uppercase tracking-wider">Barang</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold dark:text-slate-400 text-slate-500 uppercase tracking-wider">Ruangan</th>
                            <th class="text-center px-6 py-4 text-xs font-semibold dark:text-slate-400 text-slate-500 uppercase tracking-wider">Jumlah</th>
                            <th class="text-center px-6 py-4 text-xs font-semibold dark:text-slate-400 text-slate-500 uppercase tracking-wider">Prioritas</th>
                            <th class="text-center px-6 py-4 text-xs font-semibold dark:text-slate-400 text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="text-center px-6 py-4 text-xs font-semibold dark:text-slate-400 text-slate-500 uppercase tracking-wider">Tanggal</th>
                            <th class="text-center px-6 py-4 text-xs font-semibold dark:text-slate-400 text-slate-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-slate-700/50 divide-slate-100">
                        @foreach($usulan as $item)
                            <tr class="dark:hover:bg-slate-700/20 hover:bg-slate-50 transition-colors">
                                {{-- Kode --}}
                                <td class="px-6 py-4">
                                    <span class="text-sm font-mono font-semibold dark:text-emerald-400 text-emerald-600">{{ $item->kode_permintaan }}</span>
                                </td>

                                {{-- Pengusul --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center flex-shrink-0">
                                            <span class="text-white text-xs font-bold">{{ strtoupper(substr($item->user->name ?? '-', 0, 1)) }}</span>
                                        </div>
                                        <span class="text-sm dark:text-white text-slate-800 font-medium">{{ $item->user->name ?? '-' }}</span>
                                    </div>
                                </td>

                                {{-- Barang --}}
                                <td class="px-6 py-4">
                                    <span class="text-sm dark:text-slate-300 text-slate-700 max-w-[200px] truncate block">{{ $item->nama_barang }}</span>
                                </td>

                                {{-- Ruangan --}}
                                <td class="px-6 py-4">
                                    <span class="text-sm dark:text-slate-300 text-slate-700">{{ $item->ruangan->nama_ruangan ?? '-' }}</span>
                                </td>

                                {{-- Jumlah --}}
                                <td class="px-6 py-4 text-center">
                                    <span class="text-sm font-semibold dark:text-white text-slate-800">{{ number_format($item->jumlah) }}</span>
                                    <span class="text-xs dark:text-slate-400 text-slate-500">{{ $item->satuan ?? '' }}</span>
                                </td>

                                {{-- Prioritas Badge --}}
                                <td class="px-6 py-4 text-center">
                                    @php
                                        $prioritasColors = [
                                            'rendah' => 'bg-slate-100 text-slate-600 dark:bg-slate-600/30 dark:text-slate-400',
                                            'sedang' => 'bg-blue-100 text-blue-700 dark:bg-blue-600/20 dark:text-blue-400',
                                            'tinggi' => 'bg-orange-100 text-orange-700 dark:bg-orange-600/20 dark:text-orange-400',
                                            'mendesak' => 'bg-red-100 text-red-700 dark:bg-red-600/20 dark:text-red-400',
                                        ];
                                        $pColor = $prioritasColors[$item->prioritas] ?? $prioritasColors['sedang'];
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold {{ $pColor }}">
                                        {{ ucfirst($item->prioritas) }}
                                    </span>
                                </td>

                                {{-- Status Badge --}}
                                <td class="px-6 py-4 text-center">
                                    @php
                                        $statusStyles = [
                                            'pending' => 'bg-yellow-50 text-yellow-700 border-yellow-300 dark:bg-yellow-600/10 dark:text-yellow-400 dark:border-yellow-500/30',
                                            'diproses' => 'bg-cyan-50 text-cyan-700 border-cyan-300 dark:bg-cyan-600/10 dark:text-cyan-400 dark:border-cyan-500/30',
                                            'disetujui' => 'bg-emerald-50 text-emerald-700 border-emerald-300 dark:bg-emerald-600/10 dark:text-emerald-400 dark:border-emerald-500/30',
                                            'ditolak' => 'bg-red-50 text-red-700 border-red-300 dark:bg-red-600/10 dark:text-red-400 dark:border-red-500/30',
                                            'revisi' => 'bg-purple-50 text-purple-700 border-purple-300 dark:bg-purple-600/10 dark:text-purple-400 dark:border-purple-500/30',
                                        ];
                                        $sStyle = $statusStyles[$item->status] ?? $statusStyles['pending'];
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold border {{ $sStyle }}">
                                        {{ ucfirst($item->status) }}
                                    </span>
                                </td>

                                {{-- Tanggal --}}
                                <td class="px-6 py-4 text-center">
                                    <span class="text-sm dark:text-slate-400 text-slate-500">{{ $item->created_at->format('d/m/Y') }}</span>
                                </td>

                                {{-- Aksi --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('usulan-permintaan-barang.admin.show', $item->id) }}"
                                            class="w-8 h-8 rounded-lg dark:bg-slate-700/50 bg-slate-100 dark:hover:bg-slate-600 hover:bg-slate-200 flex items-center justify-center transition-colors group"
                                            title="Lihat Detail">
                                            <i class="fas fa-eye text-xs dark:text-slate-400 text-slate-500 group-hover:text-emerald-500"></i>
                                        </a>

                                        @if($item->status === 'pending')
                                            <form action="{{ route('usulan-permintaan-barang.admin.proses', $item->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit"
                                                    class="inline-flex items-center gap-1.5 bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200 shadow-sm"
                                                    onclick="return confirm('Proses permintaan ini?')">
                                                    <i class="fas fa-cog text-[10px]"></i>
                                                    Proses
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($usulan->hasPages())
                <div class="p-6 border-t dark:border-slate-700/50 border-slate-200">
                    {{ $usulan->withQueryString()->links() }}
                </div>
            @endif
        @else
            {{-- Empty State --}}
            <div class="p-12 text-center">
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-slate-200 to-slate-300 dark:from-slate-700 dark:to-slate-600 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-inbox text-2xl dark:text-slate-500 text-slate-400"></i>
                </div>
                <h3 class="text-lg font-semibold dark:text-white text-slate-800 mb-2">Belum Ada Permintaan</h3>
                <p class="text-sm dark:text-slate-400 text-slate-500 max-w-md mx-auto">
                    Belum ada usulan permintaan barang habis pakai yang masuk. Permintaan akan muncul di sini setelah pengguna mengajukan usulan.
                </p>
            </div>
        @endif
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2"
            class="fixed bottom-6 right-6 z-50 bg-gradient-to-r from-emerald-500 to-teal-500 text-white px-6 py-3 rounded-xl shadow-2xl shadow-emerald-500/25 flex items-center gap-3">
            <i class="fas fa-check-circle"></i>
            <span class="text-sm font-medium">{{ session('success') }}</span>
            <button @click="show = false" class="ml-2 hover:text-white/80">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2"
            class="fixed bottom-6 right-6 z-50 bg-gradient-to-r from-red-500 to-rose-500 text-white px-6 py-3 rounded-xl shadow-2xl shadow-red-500/25 flex items-center gap-3">
            <i class="fas fa-exclamation-circle"></i>
            <span class="text-sm font-medium">{{ session('error') }}</span>
            <button @click="show = false" class="ml-2 hover:text-white/80">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
    @endif
@endsection
