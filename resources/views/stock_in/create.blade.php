@extends('layouts.app')

@section('title', 'Tambah Barang Masuk')
@section('header', 'Tambah Barang Masuk')
@section('breadcrumb', 'Transaksi / Barang Masuk / Tambah')

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-xl shadow-md">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-800">Form Tambah Barang Masuk</h3>
            </div>

            <form action="{{ route('stock-in.store') }}" method="POST" class="p-6">
                @csrf

                <div class="mb-6">
                    <label for="item_id" class="block text-gray-700 font-semibold mb-2">
                        Pilih Barang <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="item_id" 
                        name="item_id" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('item_id') border-red-500 @enderror"
                        required
                        onchange="updateItemInfo(this)"
                    >
                        <option value="">Pilih Barang</option>
                        @foreach($items as $item)
                            <option 
                                value="{{ $item->id }}" 
                                data-stock="{{ $item->stock }}"
                                data-unit="{{ $item->unit->symbol }}"
                                data-category="{{ $item->category->name }}"
                                {{ old('item_id') == $item->id ? 'selected' : '' }}
                            >
                                {{ $item->code }} - {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('item_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <div id="item-info" class="mt-2 text-sm text-gray-600 hidden">
                        <p><strong>Kategori:</strong> <span id="info-category"></span></p>
                        <p><strong>Stok Saat Ini:</strong> <span id="info-stock"></span> <span id="info-unit"></span></p>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="quantity" class="block text-gray-700 font-semibold mb-2">
                        Jumlah Masuk <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        id="quantity" 
                        name="quantity" 
                        value="{{ old('quantity') }}"
                        min="1"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('quantity') border-red-500 @enderror"
                        placeholder="Masukkan jumlah"
                        required
                    >
                    @error('quantity')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="date" class="block text-gray-700 font-semibold mb-2">
                        Tanggal <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        id="date" 
                        name="date" 
                        value="{{ old('date', date('Y-m-d')) }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('date') border-red-500 @enderror"
                        required
                    >
                    @error('date')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
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
                        placeholder="Catatan tambahan (opsional)"
                    >{{ old('notes') }}</textarea>
                </div>

                <div class="flex items-center gap-4">
                    <button 
                        type="submit" 
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg transition font-semibold"
                    >
                        <i class="fas fa-save mr-2"></i>
                        Simpan
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

@push('scripts')
<script>
function updateItemInfo(select) {
    const option = select.options[select.selectedIndex];
    const infoDiv = document.getElementById('item-info');
    
    if (option.value) {
        document.getElementById('info-category').textContent = option.dataset.category;
        document.getElementById('info-stock').textContent = option.dataset.stock;
        document.getElementById('info-unit').textContent = option.dataset.unit;
        infoDiv.classList.remove('hidden');
    } else {
        infoDiv.classList.add('hidden');
    }
}
</script>
@endpush
