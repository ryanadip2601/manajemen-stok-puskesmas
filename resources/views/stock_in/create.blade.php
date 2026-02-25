@extends('layouts.app')

@section('title', 'Tambah Barang Masuk')
@section('header', 'Tambah Barang Masuk')
@section('breadcrumb', 'Transaksi / Barang Masuk / Tambah')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('stock-in.index') }}" class="p-2 dark:bg-slate-700 bg-slate-200 rounded-lg dark:text-slate-300 text-slate-600 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h3 class="text-xl font-bold dark:text-white text-slate-800">Tambah Barang Masuk</h3>
                <p class="text-sm dark:text-slate-400 text-slate-600">Input banyak barang sekaligus dalam satu transaksi</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-sm dark:text-slate-400 text-slate-600">Total Item:</span>
            <span id="total-items" class="px-3 py-1 bg-emerald-500/20 text-emerald-400 rounded-full font-semibold">0</span>
        </div>
    </div>

    <!-- Form Card -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden">
        <div class="p-6 border-b dark:border-slate-700/50 border-slate-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center">
                        <i class="fas fa-box-open text-emerald-400"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold dark:text-white text-slate-800">Form Batch Barang Masuk</h4>
                        <p class="text-xs dark:text-slate-400 text-slate-500">Tambahkan barang ke daftar, lalu simpan sekaligus</p>
                    </div>
                </div>
                <!-- Global Date -->
                <div class="flex items-center gap-3">
                    <label class="text-sm dark:text-slate-300 text-slate-700">Tanggal Transaksi:</label>
                    <input 
                        type="date" 
                        id="global-date" 
                        value="{{ date('Y-m-d') }}"
                        class="dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-2 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
                    >
                </div>
            </div>
        </div>

        <div class="p-6 space-y-6">
            <!-- Quick Add Section -->
            <div class="dark:bg-slate-700/30 bg-slate-50 rounded-xl p-4">
                <div class="flex items-center gap-2 mb-4">
                    <i class="fas fa-plus-circle text-emerald-400"></i>
                    <span class="font-medium dark:text-white text-slate-800">Tambah Barang Cepat</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                    <!-- Search Barang -->
                    <div class="md:col-span-5" x-data="itemSearch()" @click.away="showDropdown = false">
                        <label class="block text-xs dark:text-slate-400 text-slate-500 mb-1">Cari Barang</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-search text-slate-400"></i>
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
                                placeholder="Ketik nama atau kode barang..."
                                class="w-full dark:bg-slate-700/50 bg-white rounded-xl pl-10 pr-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
                            >
                            <input type="hidden" id="quick-item" x-model="selectedId">
                            
                            <!-- Dropdown Results -->
                            <div 
                                x-show="showDropdown && filteredItems.length > 0" 
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform -translate-y-2"
                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                class="absolute z-50 w-full mt-2 dark:bg-slate-800 bg-white rounded-xl shadow-2xl border dark:border-slate-700 border-slate-200 max-h-64 overflow-y-auto"
                            >
                                <template x-for="(item, index) in filteredItems" :key="item.id">
                                    <div 
                                        @click="selectItem(item)"
                                        @mouseenter="highlightedIndex = index"
                                        :class="{'dark:bg-emerald-600/30 bg-emerald-100': highlightedIndex === index}"
                                        class="px-4 py-3 cursor-pointer hover:dark:bg-slate-700/50 hover:bg-slate-100 transition-colors border-b dark:border-slate-700/50 border-slate-100 last:border-0"
                                    >
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <template x-if="item.photo">
                                                    <img :src="item.photo" class="w-10 h-10 rounded object-cover border dark:border-slate-600 border-slate-300">
                                                </template>
                                                <template x-if="!item.photo">
                                                    <div class="w-10 h-10 rounded dark:bg-slate-700 bg-slate-200 flex items-center justify-center border dark:border-slate-600 border-slate-300">
                                                        <i class="fas fa-box dark:text-slate-500 text-slate-400"></i>
                                                    </div>
                                                </template>
                                                <div class="flex-1 min-w-0">
                                                    <p class="font-medium dark:text-white text-slate-800 truncate" x-text="item.name"></p>
                                                    <p class="text-xs dark:text-slate-400 text-slate-500">
                                                        <span class="font-mono px-1.5 py-0.5 dark:bg-slate-600 bg-slate-200 rounded mr-2" x-text="item.code"></span>
                                                        <span x-text="item.category"></span>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="text-right ml-3">
                                                <span class="font-semibold text-emerald-400" x-text="item.stock"></span>
                                                <span class="text-xs dark:text-slate-400 text-slate-500" x-text="item.unit"></span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            
                            <!-- No Results -->
                            <div 
                                x-show="showDropdown && searchQuery.length > 0 && filteredItems.length === 0" 
                                class="absolute z-50 w-full mt-2 dark:bg-slate-800 bg-white rounded-xl shadow-2xl border dark:border-slate-700 border-slate-200 p-4 text-center"
                            >
                                <i class="fas fa-search text-slate-400 mb-2"></i>
                                <p class="dark:text-slate-400 text-slate-500 text-sm">Barang tidak ditemukan</p>
                            </div>
                        </div>
                    </div>
                    <!-- Jumlah -->
                    <div class="md:col-span-2">
                        <label class="block text-xs dark:text-slate-400 text-slate-500 mb-1">Jumlah</label>
                        <input 
                            type="number" 
                            id="quick-quantity" 
                            min="1"
                            value="1"
                            class="w-full dark:bg-slate-700/50 bg-white rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
                            placeholder="Qty"
                        >
                    </div>
                    <!-- Catatan -->
                    <div class="md:col-span-3">
                        <label class="block text-xs dark:text-slate-400 text-slate-500 mb-1">Catatan (Opsional)</label>
                        <input 
                            type="text" 
                            id="quick-notes" 
                            class="w-full dark:bg-slate-700/50 bg-white rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
                            placeholder="Catatan..."
                        >
                    </div>
                    <!-- Button Tambah -->
                    <div class="md:col-span-2 flex items-end">
                        <button 
                            type="button" 
                            onclick="addItem()"
                            class="w-full px-4 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:from-emerald-600 hover:to-teal-700 transition-all shadow-lg"
                        >
                            <i class="fas fa-plus mr-2"></i>Tambah
                        </button>
                    </div>
                </div>
            </div>

            <!-- Items List Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="dark:bg-slate-700/30 bg-slate-100">
                            <th class="px-4 py-3 text-left text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider rounded-l-xl">No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider">Kode</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider">Nama Barang</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider">Kategori</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider">Stok Saat Ini</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider">Qty Masuk</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider">Catatan</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider rounded-r-xl">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="items-table-body">
                        <!-- Dynamic rows will be added here -->
                    </tbody>
                </table>
                
                <!-- Empty State -->
                <div id="empty-state" class="py-12 text-center">
                    <div class="w-16 h-16 mx-auto rounded-full bg-slate-700/30 flex items-center justify-center mb-4">
                        <i class="fas fa-inbox text-2xl text-slate-500"></i>
                    </div>
                    <p class="dark:text-slate-400 text-slate-500">Belum ada barang ditambahkan</p>
                    <p class="text-sm dark:text-slate-500 text-slate-400 mt-1">Pilih barang di atas untuk menambahkan ke daftar</p>
                </div>
            </div>

            <!-- Summary & Actions -->
            <div class="flex flex-col md:flex-row items-center justify-between gap-4 pt-4 border-t dark:border-slate-700/50 border-slate-200">
                <div class="flex items-center gap-6">
                    <div class="text-center">
                        <p class="text-xs dark:text-slate-400 text-slate-500">Total Item</p>
                        <p class="text-2xl font-bold text-emerald-400" id="summary-items">0</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs dark:text-slate-400 text-slate-500">Total Qty Masuk</p>
                        <p class="text-2xl font-bold text-blue-400" id="summary-quantity">0</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button 
                        type="button" 
                        onclick="clearAllItems()"
                        class="px-6 py-3 dark:bg-slate-700 bg-slate-200 dark:text-slate-300 text-slate-700 rounded-xl font-semibold hover:bg-slate-300 dark:hover:bg-slate-600 transition"
                    >
                        <i class="fas fa-trash-alt mr-2"></i>Hapus Semua
                    </button>
                    <a 
                        href="{{ route('stock-in.index') }}" 
                        class="px-6 py-3 dark:bg-slate-700 bg-slate-200 dark:text-slate-300 text-slate-700 rounded-xl font-semibold hover:bg-slate-300 dark:hover:bg-slate-600 transition"
                    >
                        Batal
                    </a>
                    <button 
                        type="button" 
                        id="submit-btn"
                        onclick="submitBatch()"
                        disabled
                        class="px-8 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:from-emerald-600 hover:to-teal-700 transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <i class="fas fa-save mr-2"></i>Simpan Semua (<span id="btn-count">0</span> item)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Form for Submission -->
<form id="batch-form" action="{{ route('stock-in.store-batch') }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="items" id="items-data">
    <input type="hidden" name="date" id="form-date">
</form>
@endsection

@push('scripts')
@php
$itemsForJs = $items->map(function($item) {
    return [
        'id' => $item->id,
        'code' => $item->code,
        'name' => $item->name,
        'stock' => $item->stock,
        'unit' => $item->unit->symbol ?? '',
        'category' => $item->category->name ?? '',
        'photo' => $item->getPhotoUrl()
    ];
})->values();
@endphp
<script>
// Data untuk search
const allItemsData = @json($itemsForJs);
let itemsList = [];
let itemsData = @json($items->keyBy('id'));
let selectedItemData = null;

// Register Alpine component
document.addEventListener('alpine:init', () => {
    Alpine.data('itemSearch', () => ({
        searchQuery: '',
        showDropdown: false,
        filteredItems: allItemsData,
        highlightedIndex: 0,
        selectedId: '',
        
        filterItems() {
            const query = this.searchQuery.toLowerCase().trim();
            if (query === '') {
                this.filteredItems = allItemsData;
            } else {
                this.filteredItems = allItemsData.filter(item => 
                    item.name.toLowerCase().includes(query) || 
                    item.code.toLowerCase().includes(query)
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
            selectedItemData = item;
        }
    }));
});

function addItem() {
    const hiddenInput = document.getElementById('quick-item');
    const quantity = document.getElementById('quick-quantity');
    const notes = document.getElementById('quick-notes');
    
    if (!hiddenInput.value || !selectedItemData) {
        alert('Pilih barang terlebih dahulu');
        return;
    }
    
    const qty = parseInt(quantity.value) || 0;
    if (qty < 1) {
        alert('Jumlah minimal 1');
        return;
    }
    
    const item = selectedItemData;
    const itemId = item.id.toString();
    
    // Check if item already exists in list
    const existingIndex = itemsList.findIndex(i => i.id === itemId);
    if (existingIndex !== -1) {
        // Update quantity instead of adding new
        itemsList[existingIndex].quantity += qty;
        if (notes.value) {
            itemsList[existingIndex].notes = notes.value;
        }
    } else {
        // Add new item
        itemsList.push({
            id: itemId,
            code: item.code,
            name: item.name,
            category: item.category,
            stock: parseInt(item.stock),
            unit: item.unit,
            quantity: qty,
            notes: notes.value
        });
    }
    
    // Reset quick add form
    hiddenInput.value = '';
    quantity.value = '1';
    notes.value = '';
    selectedItemData = null;
    
    // Reset search input
    const searchInput = document.querySelector('[x-model="searchQuery"]');
    if (searchInput) {
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('input'));
    }
    
    renderTable();
    updateSummary();
}

function removeItem(index) {
    itemsList.splice(index, 1);
    renderTable();
    updateSummary();
}

function updateItemQuantity(index, value) {
    const qty = parseInt(value) || 1;
    if (qty < 1) return;
    itemsList[index].quantity = qty;
    updateSummary();
}

function updateItemNotes(index, value) {
    itemsList[index].notes = value;
}

function renderTable() {
    const tbody = document.getElementById('items-table-body');
    const emptyState = document.getElementById('empty-state');
    
    if (itemsList.length === 0) {
        tbody.innerHTML = '';
        emptyState.classList.remove('hidden');
        return;
    }
    
    emptyState.classList.add('hidden');
    
    tbody.innerHTML = itemsList.map((item, index) => `
        <tr class="border-b dark:border-slate-700/50 border-slate-200 hover:bg-slate-700/20 transition">
            <td class="px-4 py-3 dark:text-slate-300 text-slate-700">${index + 1}</td>
            <td class="px-4 py-3">
                <span class="px-2 py-1 dark:bg-slate-600 bg-slate-200 rounded text-xs font-mono dark:text-slate-300 text-slate-700">${item.code}</span>
            </td>
            <td class="px-4 py-3 dark:text-white text-slate-800 font-medium">${item.name}</td>
            <td class="px-4 py-3 dark:text-slate-400 text-slate-500">${item.category}</td>
            <td class="px-4 py-3 text-center">
                <span class="text-emerald-400 font-medium">${item.stock}</span>
                <span class="dark:text-slate-500 text-slate-400 text-sm">${item.unit}</span>
            </td>
            <td class="px-4 py-3 text-center">
                <input 
                    type="number" 
                    value="${item.quantity}" 
                    min="1"
                    onchange="updateItemQuantity(${index}, this.value)"
                    class="w-20 text-center dark:bg-slate-700/50 bg-slate-100 rounded-lg px-2 py-1 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                >
                <span class="dark:text-slate-500 text-slate-400 text-sm ml-1">${item.unit}</span>
            </td>
            <td class="px-4 py-3">
                <input 
                    type="text" 
                    value="${item.notes || ''}" 
                    onchange="updateItemNotes(${index}, this.value)"
                    placeholder="Catatan..."
                    class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-lg px-3 py-1 dark:text-white text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
                >
            </td>
            <td class="px-4 py-3 text-center">
                <button 
                    type="button" 
                    onclick="removeItem(${index})"
                    class="p-2 text-red-400 hover:bg-red-500/20 rounded-lg transition"
                    title="Hapus"
                >
                    <i class="fas fa-times"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function updateSummary() {
    const totalItems = itemsList.length;
    const totalQty = itemsList.reduce((sum, item) => sum + item.quantity, 0);
    
    document.getElementById('total-items').textContent = totalItems;
    document.getElementById('summary-items').textContent = totalItems;
    document.getElementById('summary-quantity').textContent = totalQty;
    document.getElementById('btn-count').textContent = totalItems;
    
    const submitBtn = document.getElementById('submit-btn');
    submitBtn.disabled = totalItems === 0;
}

function clearAllItems() {
    if (itemsList.length === 0) return;
    
    if (confirm('Hapus semua barang dari daftar?')) {
        itemsList = [];
        renderTable();
        updateSummary();
    }
}

function submitBatch() {
    if (itemsList.length === 0) {
        alert('Tambahkan minimal 1 barang');
        return;
    }
    
    const date = document.getElementById('global-date').value;
    if (!date) {
        alert('Pilih tanggal transaksi');
        return;
    }
    
    // Prepare data
    const formData = itemsList.map(item => ({
        item_id: item.id,
        quantity: item.quantity,
        notes: item.notes || null
    }));
    
    document.getElementById('items-data').value = JSON.stringify(formData);
    document.getElementById('form-date').value = date;
    document.getElementById('batch-form').submit();
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    renderTable();
    updateSummary();
});
</script>
@endpush
