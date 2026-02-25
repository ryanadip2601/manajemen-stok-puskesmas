@extends('layouts.app')

@section('title', 'Edit Barang Keluar')
@section('header', 'Edit Barang Keluar')
@section('breadcrumb', 'Transaksi / Barang Keluar / Edit')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('stock-out.index') }}" class="p-2 dark:bg-slate-700 bg-slate-200 rounded-lg dark:text-slate-300 text-slate-600 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h3 class="text-xl font-bold dark:text-white text-slate-800">Edit Barang Keluar</h3>
            <p class="text-sm dark:text-slate-400 text-slate-600">Ubah data transaksi barang keluar</p>
        </div>
    </div>

    <!-- Form Card -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden">
        <div class="p-6 border-b dark:border-slate-700/50 border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center">
                    <i class="fas fa-edit text-amber-400"></i>
                </div>
                <div>
                    <h4 class="font-semibold dark:text-white text-slate-800">Form Edit</h4>
                    <p class="text-xs dark:text-slate-400 text-slate-500">ID Transaksi: #{{ $stockOut->id }}</p>
                </div>
            </div>
        </div>

        <form action="{{ route('stock-out.update', $stockOut->id) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Pilih Barang -->
            <div>
                <label for="item_id" class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">
                    Pilih Barang <span class="text-red-400">*</span>
                </label>
                <select 
                    id="item_id" 
                    name="item_id" 
                    class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors @error('item_id') ring-2 ring-red-500 @enderror"
                    required
                    onchange="updateItemInfo(this)"
                >
                    @foreach($items as $item)
                        <option 
                            value="{{ $item->id }}" 
                            data-stock="{{ $item->stock }}"
                            data-unit="{{ $item->unit->symbol }}"
                            data-category="{{ $item->category->name }}"
                            {{ old('item_id', $stockOut->item_id) == $item->id ? 'selected' : '' }}
                        >
                            {{ $item->name }} [{{ $item->stock }} {{ $item->unit->symbol }}] - {{ $item->code }}
                        </option>
                    @endforeach
                </select>
                @error('item_id')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
                
                <!-- Item Info Box -->
                <div id="item-info" class="mt-3 p-4 dark:bg-slate-700/30 bg-slate-50 rounded-xl">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="dark:text-slate-400 text-slate-500">Kategori</p>
                            <p class="dark:text-white text-slate-800 font-medium" id="info-category">-</p>
                        </div>
                        <div>
                            <p class="dark:text-slate-400 text-slate-500">Stok Tersedia</p>
                            <p class="font-semibold">
                                <span id="info-stock" class="text-emerald-400">-</span>
                                <span id="info-unit" class="dark:text-slate-400 text-slate-500"></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Jumlah & Tanggal -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="quantity" class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">
                        Jumlah Keluar <span class="text-red-400">*</span>
                    </label>
                    <input 
                        type="number" 
                        id="quantity" 
                        name="quantity" 
                        value="{{ old('quantity', $stockOut->quantity) }}"
                        min="1"
                        class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors @error('quantity') ring-2 ring-red-500 @enderror"
                        placeholder="Masukkan jumlah"
                        required
                    >
                    @error('quantity')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="date" class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">
                        Tanggal <span class="text-red-400">*</span>
                    </label>
                    <input 
                        type="date" 
                        id="date" 
                        name="date" 
                        value="{{ old('date', $stockOut->date->format('Y-m-d')) }}"
                        class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors @error('date') ring-2 ring-red-500 @enderror"
                        required
                    >
                    @error('date')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Catatan -->
            <div>
                <label for="notes" class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">
                    Catatan <span class="dark:text-slate-500 text-slate-400">(Opsional)</span>
                </label>
                <textarea 
                    id="notes" 
                    name="notes" 
                    rows="3"
                    class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors resize-none"
                    placeholder="Catatan tambahan..."
                >{{ old('notes', $stockOut->notes) }}</textarea>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3 pt-4 border-t dark:border-slate-700/50 border-slate-200">
                <button 
                    type="submit" 
                    class="flex-1 md:flex-none px-6 py-3 bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-xl font-semibold hover:from-red-600 hover:to-rose-700 transition-all shadow-lg"
                >
                    <i class="fas fa-save mr-2"></i>Update
                </button>
                <a 
                    href="{{ route('stock-out.index') }}" 
                    class="px-6 py-3 dark:bg-slate-700 bg-slate-200 dark:text-slate-300 text-slate-700 rounded-xl font-semibold hover:bg-slate-300 dark:hover:bg-slate-600 transition"
                >
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function updateItemInfo(select) {
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        document.getElementById('info-category').textContent = option.dataset.category;
        document.getElementById('info-stock').textContent = option.dataset.stock;
        document.getElementById('info-unit').textContent = option.dataset.unit;
    }
}

// Auto trigger on page load
document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('item_id');
    if (select.value) {
        updateItemInfo(select);
    }
});
</script>
@endpush
