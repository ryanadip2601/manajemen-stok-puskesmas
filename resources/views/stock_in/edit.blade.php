@extends('layouts.app')

@section('title', 'Edit Barang Masuk')
@section('header', 'Edit Barang Masuk')
@section('breadcrumb', 'Transaksi / Barang Masuk / Edit')

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-xl shadow-md">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-800">Form Edit Barang Masuk</h3>
            </div>

            <form action="{{ route('stock-in.update', $stockIn->id) }}" method="POST" class="p-6">
                @csrf
                @method('PUT')

                <div class="mb-6">
                    <label for="item_id" class="block text-gray-700 font-semibold mb-2">
                        Pilih Barang <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="item_id" 
                        name="item_id" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                        @foreach($items as $item)
                            <option 
                                value="{{ $item->id }}" 
                                {{ old('item_id', $stockIn->item_id) == $item->id ? 'selected' : '' }}
                            >
                                {{ $item->code }} - {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-6">
                    <label for="quantity" class="block text-gray-700 font-semibold mb-2">
                        Jumlah Masuk <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        id="quantity" 
                        name="quantity" 
                        value="{{ old('quantity', $stockIn->quantity) }}"
                        min="1"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                </div>

                <div class="mb-6">
                    <label for="date" class="block text-gray-700 font-semibold mb-2">
                        Tanggal <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        id="date" 
                        name="date" 
                        value="{{ old('date', $stockIn->date->format('Y-m-d')) }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                </div>

                <div class="mb-6">
                    <label for="notes" class="block text-gray-700 font-semibold mb-2">
                        Catatan
                    </label>
                    <textarea 
                        id="notes" 
                        name="notes" 
                        rows="3"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >{{ old('notes', $stockIn->notes) }}</textarea>
                </div>

                <div class="flex items-center gap-4">
                    <button 
                        type="submit" 
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg transition font-semibold"
                    >
                        <i class="fas fa-save mr-2"></i>
                        Update
                    </button>
                    <a 
                        href="{{ route('stock-in.index') }}" 
                        class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg transition font-semibold"
                    >
                        <i class="fas fa-arrow-left mr-2"></i>
                        Kembali
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
