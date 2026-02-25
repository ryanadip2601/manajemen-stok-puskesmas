@extends('layouts.app')

@section('title', 'Ajukan Pengambilan Barang')
@section('header', 'Ajukan Pengambilan Barang')
@section('breadcrumb', 'Pengambilan Barang / Ajukan Permintaan')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-slate-800/50 backdrop-blur-xl rounded-2xl border border-slate-700/50 overflow-hidden">
        <div class="p-6 border-b border-slate-700/50">
            <h3 class="text-xl font-bold text-white flex items-center">
                <div class="w-10 h-10 bg-gradient-to-br from-teal-500 to-emerald-600 rounded-xl flex items-center justify-center mr-3">
                    <i class="fas fa-clipboard-check text-white"></i>
                </div>
                Form Permintaan Pengambilan
            </h3>
            <p class="text-slate-400 text-sm mt-2">Ajukan permintaan untuk mengambil barang dari gudang</p>
        </div>

        <form action="{{ route('stock-request.staff.store') }}" method="POST" class="p-6 space-y-6" x-data="staffItemSearch()">
            @csrf

            <!-- Cari Barang -->
            <div>
                <label class="block text-slate-300 font-medium mb-2">
                    <i class="fas fa-search mr-2 text-emerald-400"></i>Cari Barang <span class="text-red-400">*</span>
                </label>
                <div class="relative" @click.away="showDropdown = false">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-box text-slate-400"></i>
                    </div>
                    <input 
                        type="text" 
                        x-model="searchQuery"
                        @focus="showDropdown = true"
                        @input="filterItems()"
                        @keydown.down.prevent="navigateDown()"
                        @keydown.up.prevent="navigateUp()"
                        @keydown.enter.prevent="selectHighlighted()"
                        @keydown.escape="showDropdown = false"
                        placeholder="Ketik nama barang untuk mencari..."
                        class="w-full bg-slate-700/50 border border-slate-600 rounded-xl pl-10 pr-4 py-3 text-white focus:outline-none focus:border-teal-500 transition"
                        required
                    >
                    <input type="hidden" name="item_id" x-model="selectedId" required>
                    
                    <!-- Dropdown Results -->
                    <div 
                        x-show="showDropdown && filteredItems.length > 0" 
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform -translate-y-2"
                        x-transition:enter-end="opacity-100 transform translate-y-0"
                        class="absolute z-50 w-full mt-2 bg-slate-800 rounded-xl shadow-2xl border border-slate-700 max-h-64 overflow-y-auto"
                    >
                        <template x-for="(item, index) in filteredItems" :key="item.id">
                            <div 
                                @click="selectItem(item)"
                                @mouseenter="highlightedIndex = index"
                                :class="{'bg-teal-600/30': highlightedIndex === index}"
                                class="px-4 py-3 cursor-pointer hover:bg-slate-700/50 transition-colors border-b border-slate-700/50 last:border-0"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-white truncate" x-text="item.name"></p>
                                        <p class="text-xs text-slate-400" x-text="item.category || 'Umum'"></p>
                                    </div>
                                    <div class="text-right ml-3">
                                        <span class="font-semibold" :class="item.stock > 0 ? 'text-emerald-400' : 'text-red-400'" x-text="item.stock > 0 ? item.stock : 'HABIS'"></span>
                                        <span class="text-xs text-slate-400" x-text="item.unit" x-show="item.stock > 0"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    
                    <!-- No Results -->
                    <div 
                        x-show="showDropdown && searchQuery.length > 0 && filteredItems.length === 0" 
                        class="absolute z-50 w-full mt-2 bg-slate-800 rounded-xl shadow-2xl border border-slate-700 p-4 text-center"
                    >
                        <i class="fas fa-search text-slate-400 mb-2"></i>
                        <p class="text-slate-400 text-sm">Barang tidak ditemukan</p>
                    </div>
                </div>
                @error('item_id')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Info Stok -->
            <div x-show="selectedItem" x-transition class="bg-slate-700/30 rounded-xl p-4">
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Stok Tersedia:</span>
                    <span class="text-emerald-400 font-bold text-lg" x-text="selectedItem ? selectedItem.stock + ' ' + selectedItem.unit : ''"></span>
                </div>
            </div>

            <!-- Jumlah -->
            <div>
                <label class="block text-slate-300 font-medium mb-2">
                    <i class="fas fa-sort-numeric-up mr-2 text-blue-400"></i>Jumlah yang Diminta <span class="text-red-400">*</span>
                </label>
                <input type="number" name="quantity" id="quantity" value="{{ old('quantity', 1) }}" min="1" :max="selectedItem ? selectedItem.stock : 9999" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-teal-500 transition" required>
                @error('quantity')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Keterangan -->
            <div>
                <label class="block text-slate-300 font-medium mb-2">
                    <i class="fas fa-sticky-note mr-2 text-yellow-400"></i>Keterangan / Keperluan
                </label>
                <textarea name="notes" rows="3" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-teal-500 transition resize-none" placeholder="Contoh: Untuk kegiatan posyandu tanggal 25">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-slate-700/50">
                <a href="{{ route('stock-request.staff.index') }}" class="px-6 py-3 bg-slate-700 text-white rounded-xl hover:bg-slate-600 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
                <button type="submit" :disabled="!selectedId" class="px-6 py-3 bg-gradient-to-r from-teal-500 to-emerald-600 text-white rounded-xl font-semibold hover:from-teal-600 hover:to-emerald-700 transition-all shadow-lg shadow-teal-500/20 disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-paper-plane mr-2"></i>Ajukan Permintaan
                </button>
            </div>
        </form>
    </div>

    <!-- Info Box -->
    <div class="mt-6 bg-amber-500/10 border border-amber-500/30 rounded-xl p-4">
        <div class="flex items-start">
            <i class="fas fa-exclamation-triangle text-amber-400 mt-0.5 mr-3"></i>
            <div class="text-sm text-amber-300">
                <p class="font-medium mb-1">Perhatian:</p>
                <ul class="list-disc list-inside text-amber-200/80 space-y-1">
                    <li>Permintaan akan ditinjau oleh Admin Gudang</li>
                    <li>Anda akan mendapat notifikasi setelah permintaan disetujui</li>
                    <li>Barang hanya bisa diambil setelah permintaan <span class="text-blue-400 font-medium">disetujui</span></li>
                    <li>Stok akan berkurang setelah pengambilan <span class="text-green-400 font-medium">selesai</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
@php
$staffItemsForJs = $items->map(function($item) {
    return [
        'id' => $item->id,
        'name' => $item->name,
        'stock' => $item->stock,
        'unit' => $item->unit->symbol ?? '',
        'category' => $item->category->name ?? 'Umum'
    ];
})->values();
@endphp
<script>
const allStaffItems = @json($staffItemsForJs);

function staffItemSearch() {
    return {
        searchQuery: '',
        showDropdown: false,
        filteredItems: allStaffItems.filter(i => i.stock > 0),
        highlightedIndex: 0,
        selectedId: '{{ old("item_id", "") }}',
        selectedItem: null,
        
        init() {
            // If there's an old value, find and select that item
            if (this.selectedId) {
                const item = allStaffItems.find(i => i.id == this.selectedId);
                if (item) {
                    this.searchQuery = item.name;
                    this.selectedItem = item;
                }
            }
        },
        
        filterItems() {
            const query = this.searchQuery.toLowerCase().trim();
            if (query === '') {
                this.filteredItems = allStaffItems.filter(i => i.stock > 0);
            } else {
                this.filteredItems = allStaffItems.filter(item => 
                    (item.name.toLowerCase().includes(query) || 
                    (item.category && item.category.toLowerCase().includes(query))) &&
                    item.stock > 0
                );
            }
            this.highlightedIndex = 0;
            this.showDropdown = true;
        },
        
        navigateDown() {
            if (this.highlightedIndex < this.filteredItems.length - 1) {
                this.highlightedIndex++;
            }
        },
        
        navigateUp() {
            if (this.highlightedIndex > 0) {
                this.highlightedIndex--;
            }
        },
        
        selectHighlighted() {
            if (this.filteredItems.length > 0 && this.highlightedIndex < this.filteredItems.length) {
                this.selectItem(this.filteredItems[this.highlightedIndex]);
            }
        },
        
        selectItem(item) {
            this.selectedId = item.id;
            this.searchQuery = item.name;
            this.showDropdown = false;
            this.selectedItem = item;
            
            // Update max quantity
            document.getElementById('quantity').max = item.stock;
        }
    }
}
</script>
@endpush
@endsection

