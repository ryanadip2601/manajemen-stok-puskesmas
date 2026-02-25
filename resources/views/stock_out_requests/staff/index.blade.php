@extends('layouts.app')

@section('title', 'Permintaan Pengambilan Barang')
@section('header', 'Permintaan Pengambilan Barang')
@section('breadcrumb', 'Pengambilan Barang / Riwayat Permintaan')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <p class="text-slate-400">Daftar permintaan pengambilan barang Anda</p>
        </div>
        <a href="{{ route('stock-request.staff.create') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-teal-500 to-emerald-600 text-white rounded-xl font-semibold hover:from-teal-600 hover:to-emerald-700 transition-all shadow-lg shadow-teal-500/20">
            <i class="fas fa-plus mr-2"></i>
            Ajukan Pengambilan
        </a>
    </div>

    <!-- Info Box -->
    <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-4">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-blue-400 mt-0.5 mr-3"></i>
            <div class="text-sm text-blue-300">
                <p class="font-medium mb-1">Alur Pengambilan Barang:</p>
                <ol class="list-decimal list-inside text-blue-200/80 space-y-1">
                    <li><span class="text-yellow-400 font-medium">Menunggu</span> - Permintaan Anda sedang ditinjau oleh Admin Gudang</li>
                    <li><span class="text-blue-400 font-medium">Disetujui</span> - Silakan ambil barang di gudang</li>
                    <li><span class="text-green-400 font-medium">Selesai</span> - Barang sudah diambil, stok berkurang</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-slate-800/50 backdrop-blur-xl rounded-2xl border border-slate-700/50 overflow-hidden">
        @if($requests->isEmpty())
            <div class="p-12 text-center">
                <div class="w-20 h-20 bg-slate-700/50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-clipboard-list text-slate-500 text-3xl"></i>
                </div>
                <h3 class="text-slate-400 text-lg font-medium mb-2">Belum Ada Permintaan</h3>
                <p class="text-slate-500 mb-4">Anda belum pernah mengajukan permintaan pengambilan barang.</p>
                <a href="{{ route('stock-request.staff.create') }}" class="inline-flex items-center px-4 py-2 bg-teal-500/20 text-teal-400 rounded-lg hover:bg-teal-500/30 transition">
                    <i class="fas fa-plus mr-2"></i>
                    Ajukan Sekarang
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-700/50">
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">Tanggal</th>
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">Barang</th>
                            <th class="text-center text-slate-300 font-semibold px-6 py-4">Jumlah</th>
                            <th class="text-center text-slate-300 font-semibold px-6 py-4">Status</th>
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">Keterangan</th>
                            <th class="text-center text-slate-300 font-semibold px-6 py-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                        @foreach($requests as $request)
                            <tr class="hover:bg-slate-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="text-white">{{ $request->request_date->format('d M Y') }}</span>
                                    <p class="text-xs text-slate-500">{{ $request->created_at->format('H:i') }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-red-600 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-box text-white text-sm"></i>
                                        </div>
                                        <div>
                                            <span class="text-white font-medium">{{ $request->item->name }}</span>
                                            <p class="text-xs text-slate-500">{{ $request->item->category->name ?? '-' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 bg-slate-700 text-white rounded-lg font-semibold">
                                        {{ $request->quantity }} {{ $request->item->unit->symbol ?? '' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 rounded-lg text-sm font-medium {{ $request->getStatusBadgeClass() }}">
                                        {{ $request->getStatusLabel() }}
                                    </span>
                                    @if($request->isRejected() && $request->reject_reason)
                                        <p class="text-xs text-red-400 mt-1">{{ $request->reject_reason }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-400 text-sm">
                                    {{ $request->notes ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($request->isPending())
                                        <form action="{{ route('stock-request.staff.cancel', $request->id) }}" method="POST" class="inline" onsubmit="return confirm('Batalkan permintaan ini?')">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition text-sm">
                                                <i class="fas fa-times mr-1"></i>Batalkan
                                            </button>
                                        </form>
                                    @elseif($request->isApproved())
                                        <span class="text-blue-400 text-sm">
                                            <i class="fas fa-clock mr-1"></i>Silakan ambil
                                        </span>
                                    @else
                                        <span class="text-slate-500">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($requests->hasPages())
                <div class="px-6 py-4 border-t border-slate-700/50">
                    {{ $requests->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
