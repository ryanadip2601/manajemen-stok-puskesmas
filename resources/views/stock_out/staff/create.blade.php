@extends('layouts.app')

@section('title', 'Ambil Barang')
@section('header', 'Ambil Barang')
@section('breadcrumb', 'Pengambilan Barang / Ambil Barang')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-slate-800/50 backdrop-blur-xl rounded-2xl border border-slate-700/50 overflow-hidden">
        <div class="p-6 border-b border-slate-700/50">
            <h3 class="text-xl font-bold text-white flex items-center">
                <div class="w-10 h-10 bg-gradient-to-br from-teal-500 to-emerald-600 rounded-xl flex items-center justify-center mr-3">
                    <i class="fas fa-hand-holding-box text-white"></i>
                </div>
                Form Pengambilan Barang
            </h3>
            <p class="text-slate-400 text-sm mt-2">Catat pengambilan barang dari gudang</p>
        </div>

        <form action="{{ route('stock-out.staff.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            <!-- Pilih Barang -->
            <div>
                <label class="block text-slate-300 font-medium mb-2">
                    <i class="fas fa-box mr-2 text-emerald-400"></i>Pilih Barang <span class="text-red-400">*</span>
                </label>
                <select name="item_id" id="item_id" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-teal-500 transition" required>
                    <option value="">-- Pilih Barang --</option>
                    @foreach($items as $item)
                        <option value="{{ $item->id }}" data-stock="{{ $item->stock }}" data-unit="{{ $item->unit->symbol ?? '' }}" {{ old('item_id') == $item->id ? 'selected' : '' }}>
                            {{ $item->name }} (Stok: {{ $item->stock }} {{ $item->unit->symbol ?? '' }})
                        </option>
                    @endforeach
                </select>
                @error('item_id')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Info Stok -->
            <div id="stock-info" class="hidden bg-slate-700/30 rounded-xl p-4">
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Stok Tersedia:</span>
                    <span id="available-stock" class="text-emerald-400 font-bold text-lg">0</span>
                </div>
            </div>

            <!-- Jumlah -->
            <div>
                <label class="block text-slate-300 font-medium mb-2">
                    <i class="fas fa-sort-numeric-up mr-2 text-blue-400"></i>Jumlah <span class="text-red-400">*</span>
                </label>
                <input type="number" name="quantity" id="quantity" value="{{ old('quantity', 1) }}" min="1" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-teal-500 transition" required>
                @error('quantity')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Keterangan -->
            <div>
                <label class="block text-slate-300 font-medium mb-2">
                    <i class="fas fa-sticky-note mr-2 text-yellow-400"></i>Keterangan / Keperluan
                </label>
                <textarea name="notes" rows="3" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-teal-500 transition resize-none" placeholder="Contoh: Untuk kegiatan posyandu">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-slate-700/50">
                <a href="{{ route('stock-out.staff.index') }}" class="px-6 py-3 bg-slate-700 text-white rounded-xl hover:bg-slate-600 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-teal-500 to-emerald-600 text-white rounded-xl font-semibold hover:from-teal-600 hover:to-emerald-700 transition-all shadow-lg shadow-teal-500/20">
                    <i class="fas fa-check mr-2"></i>Simpan Pengambilan
                </button>
            </div>
        </form>
    </div>

    <!-- Info Box -->
    <div class="mt-6 bg-blue-500/10 border border-blue-500/30 rounded-xl p-4">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-blue-400 mt-0.5 mr-3"></i>
            <div class="text-sm text-blue-300">
                <p class="font-medium mb-1">Catatan Penting:</p>
                <ul class="list-disc list-inside text-blue-200/80 space-y-1">
                    <li>Pastikan jumlah yang diambil sesuai dengan kebutuhan</li>
                    <li>Stok barang akan berkurang secara otomatis setelah pengambilan dicatat</li>
                    <li>Isi keterangan untuk memudahkan pelacakan penggunaan barang</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('item_id').addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        const stock = selected.getAttribute('data-stock');
        const unit = selected.getAttribute('data-unit');
        const stockInfo = document.getElementById('stock-info');
        const availableStock = document.getElementById('available-stock');
        const quantityInput = document.getElementById('quantity');

        if (stock) {
            stockInfo.classList.remove('hidden');
            availableStock.textContent = stock + ' ' + (unit || '');
            quantityInput.setAttribute('max', stock);
        } else {
            stockInfo.classList.add('hidden');
        }
    });

    // Validate quantity on change
    document.getElementById('quantity').addEventListener('change', function() {
        const max = parseInt(this.getAttribute('max')) || 0;
        const value = parseInt(this.value) || 0;
        
        if (value > max && max > 0) {
            alert('Jumlah melebihi stok yang tersedia!');
            this.value = max;
        }
    });
</script>
@endpush
@endsection
