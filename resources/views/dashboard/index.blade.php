@extends('layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')
@section('breadcrumb', 'Halaman Utama / Dashboard')

@section('content')
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-semibold mb-1">Total Barang</p>
                    <h3 class="text-3xl font-bold text-gray-800">{{ $total_items }}</h3>
                </div>
                <div class="bg-blue-100 rounded-full p-4">
                    <i class="fas fa-box text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-semibold mb-1">Total Stok</p>
                    <h3 class="text-3xl font-bold text-gray-800">{{ $total_stock }}</h3>
                </div>
                <div class="bg-green-100 rounded-full p-4">
                    <i class="fas fa-cubes text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-red-500 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-semibold mb-1">Barang Hampir Habis</p>
                    <h3 class="text-3xl font-bold text-gray-800">{{ $low_stock_count }}</h3>
                </div>
                <div class="bg-red-100 rounded-full p-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-purple-500 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-semibold mb-1">Kategori</p>
                    <h3 class="text-3xl font-bold text-gray-800">{{ $categories_count }}</h3>
                </div>
                <div class="bg-purple-100 rounded-full p-4">
                    <i class="fas fa-folder text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Recent Stock In -->
        <div class="bg-white rounded-xl shadow-md">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 flex items-center">
                    <i class="fas fa-arrow-down text-green-600 mr-2"></i>
                    Barang Masuk Terbaru
                </h3>
            </div>
            <div class="p-6">
                @forelse($recent_stock_in as $stock)
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                        <div class="flex-1">
                            <p class="font-semibold text-gray-800">{{ $stock->item->name }}</p>
                            <p class="text-sm text-gray-500">{{ $stock->user->name }} • {{ $stock->date->format('d/m/Y') }}</p>
                        </div>
                        <div class="text-right">
                            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">
                                +{{ $stock->quantity }} {{ $stock->item->unit->symbol }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">Belum ada transaksi barang masuk</p>
                @endforelse
            </div>
        </div>

        <!-- Recent Stock Out -->
        <div class="bg-white rounded-xl shadow-md">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 flex items-center">
                    <i class="fas fa-arrow-up text-red-600 mr-2"></i>
                    Barang Keluar Terbaru
                </h3>
            </div>
            <div class="p-6">
                @forelse($recent_stock_out as $stock)
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                        <div class="flex-1">
                            <p class="font-semibold text-gray-800">{{ $stock->item->name }}</p>
                            <p class="text-sm text-gray-500">{{ $stock->user->name }} • {{ $stock->date->format('d/m/Y') }}</p>
                        </div>
                        <div class="text-right">
                            <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-semibold">
                                -{{ $stock->quantity }} {{ $stock->item->unit->symbol }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">Belum ada transaksi barang keluar</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Low Stock Items -->
    @if($low_stock_items->count() > 0)
        <div class="bg-white rounded-xl shadow-md">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                    Barang Hampir Habis
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Kode</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Nama Barang</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Kategori</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Stok Saat Ini</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Stok Minimum</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($low_stock_items as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-800">{{ $item->code }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $item->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $item->category->name }}</td>
                                <td class="px-6 py-4">
                                    <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-semibold">
                                        {{ $item->stock }} {{ $item->unit->symbol }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $item->minimum_stock }} {{ $item->unit->symbol }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
