@extends('layouts.app')

@section('title', 'Riwayat Pengambilan Barang')
@section('header', 'Riwayat Pengambilan Barang')
@section('breadcrumb', 'Pengambilan Barang / Riwayat')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <p class="text-slate-400">Daftar barang yang telah Anda ambil</p>
        </div>
        <a href="{{ route('stock-out.staff.create') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-teal-500 to-emerald-600 text-white rounded-xl font-semibold hover:from-teal-600 hover:to-emerald-700 transition-all shadow-lg shadow-teal-500/20">
            <i class="fas fa-plus mr-2"></i>
            Ambil Barang
        </a>
    </div>

    <!-- Table -->
    <div class="bg-slate-800/50 backdrop-blur-xl rounded-2xl border border-slate-700/50 overflow-hidden">
        @if($stockOuts->isEmpty())
            <div class="p-12 text-center">
                <div class="w-20 h-20 bg-slate-700/50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-box-open text-slate-500 text-3xl"></i>
                </div>
                <h3 class="text-slate-400 text-lg font-medium mb-2">Belum Ada Pengambilan</h3>
                <p class="text-slate-500 mb-4">Anda belum pernah mengambil barang dari gudang.</p>
                <a href="{{ route('stock-out.staff.create') }}" class="inline-flex items-center px-4 py-2 bg-teal-500/20 text-teal-400 rounded-lg hover:bg-teal-500/30 transition">
                    <i class="fas fa-plus mr-2"></i>
                    Ambil Barang Sekarang
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-700/50">
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">Tanggal</th>
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">Barang</th>
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">Kategori</th>
                            <th class="text-center text-slate-300 font-semibold px-6 py-4">Jumlah</th>
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                        @foreach($stockOuts as $stockOut)
                            <tr class="hover:bg-slate-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="text-white">{{ \Carbon\Carbon::parse($stockOut->date)->format('d M Y') }}</span>
                                    <p class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($stockOut->created_at)->format('H:i') }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-orange-600 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-box text-white text-sm"></i>
                                        </div>
                                        <div>
                                            <span class="text-white font-medium">{{ $stockOut->item->name }}</span>
                                            @if($stockOut->item->barcode)
                                                <p class="text-xs text-slate-500">{{ $stockOut->item->barcode }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 bg-purple-500/20 text-purple-400 rounded-lg text-sm">
                                        {{ $stockOut->item->category->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 bg-red-500/20 text-red-400 rounded-lg font-semibold">
                                        {{ $stockOut->quantity }} {{ $stockOut->item->unit->symbol ?? '' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-400">
                                    {{ $stockOut->notes ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($stockOuts->hasPages())
                <div class="px-6 py-4 border-t border-slate-700/50">
                    {{ $stockOuts->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
