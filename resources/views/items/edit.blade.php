@extends('layouts.app')

@section('title', 'Edit Barang')
@section('header', 'Edit Barang')
@section('breadcrumb', 'Master Data / Barang / Edit')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-xl shadow-md">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-800">Form Edit Barang</h3>
            </div>

            <form action="{{ route('items.update', $item->id) }}" method="POST" class="p-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="code" class="block text-gray-700 font-semibold mb-2">
                            Kode Barang <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="code" 
                            name="code" 
                            value="{{ old('code', $item->code) }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('code') border-red-500 @enderror"
                            required
                        >
                        @error('code')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="name" class="block text-gray-700 font-semibold mb-2">
                            Nama Barang <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            value="{{ old('name', $item->name) }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                            required
                        >
                        @error('name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="category_id" class="block text-gray-700 font-semibold mb-2">
                            Kategori <span class="text-red-500">*</span>
                        </label>
                        <select 
                            id="category_id" 
                            name="category_id" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $item->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="unit_id" class="block text-gray-700 font-semibold mb-2">
                            Satuan <span class="text-red-500">*</span>
                        </label>
                        <select 
                            id="unit_id" 
                            name="unit_id" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" {{ old('unit_id', $item->unit_id) == $unit->id ? 'selected' : '' }}>
                                    {{ $unit->name }} ({{ $unit->symbol }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            Stok Saat Ini
                        </label>
                        <div class="w-full px-4 py-3 bg-gray-100 border border-gray-300 rounded-lg text-gray-700 font-semibold">
                            {{ $item->stock }} {{ $item->unit->symbol }}
                        </div>
                        <p class="text-sm text-gray-500 mt-1">Stok dikelola melalui transaksi masuk/keluar</p>
                    </div>

                    <div>
                        <label for="minimum_stock" class="block text-gray-700 font-semibold mb-2">
                            Stok Minimum <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="number" 
                            id="minimum_stock" 
                            name="minimum_stock" 
                            value="{{ old('minimum_stock', $item->minimum_stock) }}"
                            min="0"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                    </div>
                </div>

                <div class="mb-6">
                    <label for="description" class="block text-gray-700 font-semibold mb-2">
                        Deskripsi
                    </label>
                    <textarea 
                        id="description" 
                        name="description" 
                        rows="4"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >{{ old('description', $item->description) }}</textarea>
                </div>

                <div class="flex items-center gap-4">
                    <button 
                        type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition font-semibold"
                    >
                        <i class="fas fa-save mr-2"></i>
                        Update
                    </button>
                    <a 
                        href="{{ route('items.index') }}" 
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
