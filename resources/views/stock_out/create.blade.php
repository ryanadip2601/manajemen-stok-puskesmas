@extends('layouts.app')

@section('title', 'Tambah Barang Keluar')
@section('header', 'Tambah Barang Keluar')
@section('breadcrumb', 'Transaksi / Barang Keluar / Tambah')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('stock-out.index') }}" class="p-2 dark:bg-slate-700 bg-slate-200 rounded-lg dark:text-slate-300 text-slate-600 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h3 class="text-xl font-bold dark:text-white text-slate-800">Tambah Barang Keluar</h3>
                <p class="text-sm dark:text-slate-400 text-slate-600">Input banyak barang sekaligus dalam satu transaksi</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-sm dark:text-slate-400 text-slate-600">Total Item:</span>
            <span id="total-items" class="px-3 py-1 bg-red-500/20 text-red-400 rounded-full font-semibold">0</span>
        </div>
    </div>

    <!-- Form Card -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden">
        <div class="p-6 border-b dark:border-slate-700/50 border-slate-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-500/20 flex items-center justify-center">
                        <i class="fas fa-dolly text-red-400"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold dark:text-white text-slate-800">Form Batch Barang Keluar</h4>
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
                        class="dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-2 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors"
                    >
                </div>
            </div>
        </div>

        <div class="p-6 space-y-6">
            <!-- Quick Add Section -->
            <div class="dark:bg-slate-700/30 bg-slate-50 rounded-xl p-4">
                <div class="flex items-center gap-2 mb-4">
                    <i class="fas fa-plus-circle text-red-400"></i>
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
                                class="w-full dark:bg-slate-700/50 bg-white rounded-xl pl-10 pr-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors"
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
                                        @click="item.stock > 0 && selectItem(item)"
                                        @mouseenter="highlightedIndex = index"
                                        :class="{
                                            'dark:bg-red-600/30 bg-red-100': highlightedIndex === index && item.stock > 0,
                                            'opacity-50 cursor-not-allowed': item.stock <= 0,
                                            'cursor-pointer': item.stock > 0
                                        }"
                                        class="px-4 py-3 hover:dark:bg-slate-700/50 hover:bg-slate-100 transition-colors border-b dark:border-slate-700/50 border-slate-100 last:border-0"
                                    >
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1 min-w-0">
                                                <p class="font-medium dark:text-white text-slate-800 truncate" x-text="item.name"></p>
                                                <p class="text-xs dark:text-slate-400 text-slate-500">
                                                    <span class="font-mono px-1.5 py-0.5 dark:bg-slate-600 bg-slate-200 rounded mr-2" x-text="item.code"></span>
                                                    <span x-text="item.category"></span>
                                                </p>
                                            </div>
                                            <div class="text-right ml-3">
                                                <template x-if="item.stock > 0">
                                                    <span>
                                                        <span class="font-semibold text-emerald-400" x-text="item.stock"></span>
                                                        <span class="text-xs dark:text-slate-400 text-slate-500" x-text="item.unit"></span>
                                                    </span>
                                                </template>
                                                <template x-if="item.stock <= 0">
                                                    <span class="px-2 py-1 bg-red-500/20 text-red-400 rounded text-xs font-semibold">HABIS</span>
                                                </template>
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
                        <div class="relative">
                            <input 
                                type="number" 
                                id="quick-quantity" 
                                min="1"
                                value="1"
                                class="w-full dark:bg-slate-700/50 bg-white rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors"
                                placeholder="Qty"
                                oninput="checkQuickStock()"
                            >
                            <span id="quick-stock-warning" class="hidden absolute -bottom-5 left-0 text-xs text-red-400">Melebihi stok!</span>
                        </div>
                    </div>
                    <!-- Catatan -->
                    <div class="md:col-span-3">
                        <label class="block text-xs dark:text-slate-400 text-slate-500 mb-1">Catatan (Opsional)</label>
                        <input 
                            type="text" 
                            id="quick-notes" 
                            class="w-full dark:bg-slate-700/50 bg-white rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors"
                            placeholder="Catatan..."
                        >
                    </div>
                    <!-- Button Tambah -->
                    <div class="md:col-span-2 flex items-end">
                        <button 
                            type="button" 
                            id="add-btn"
                            onclick="addItem()"
                            class="w-full px-4 py-3 bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-xl font-semibold hover:from-red-600 hover:to-rose-700 transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <i class="fas fa-plus mr-2"></i>Tambah
                        </button>
                    </div>
                </div>
                <!-- Stock Info -->
                <div id="stock-info" class="mt-4 p-3 dark:bg-slate-600/30 bg-slate-100 rounded-lg hidden">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <span class="dark:text-slate-400 text-slate-500 text-sm">Stok tersedia:</span>
                            <span id="available-stock" class="font-semibold text-emerald-400">0</span>
                        </div>
                        <div id="low-stock-badge" class="hidden px-2 py-1 bg-amber-500/20 text-amber-400 rounded text-xs">
                            <i class="fas fa-exclamation-triangle mr-1"></i>Stok Terbatas
                        </div>
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
                            <th class="px-4 py-3 text-center text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider">Stok Tersedia</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold dark:text-slate-300 text-slate-600 uppercase tracking-wider">Qty Keluar</th>
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
                        <p class="text-2xl font-bold text-red-400" id="summary-items">0</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs dark:text-slate-400 text-slate-500">Total Qty Keluar</p>
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
                        href="{{ route('stock-out.index') }}" 
                        class="px-6 py-3 dark:bg-slate-700 bg-slate-200 dark:text-slate-300 text-slate-700 rounded-xl font-semibold hover:bg-slate-300 dark:hover:bg-slate-600 transition"
                    >
                        Batal
                    </a>
                    <button 
                        type="button" 
                        id="submit-btn"
                        onclick="submitBatch()"
                        disabled
                        class="px-8 py-3 bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-xl font-semibold hover:from-red-600 hover:to-rose-700 transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <i class="fas fa-save mr-2"></i>Simpan Semua (<span id="btn-count">0</span> item)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Form for Submission -->
<form id="batch-form" action="{{ route('stock-out.store-batch') }}" method="POST" class="hidden">
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
        'unit' => $item->unit->symbol,
        'category' => $item->category->name
    ];
})->values();
@endphp
<script>
// Data untuk search
const allItemsData = @json($itemsForJs);
let itemsList = [];
let itemsData = @json($items->keyBy('id'));
let currentQuickStock = 0;
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
                const item = this.filteredItems[this.highlightedIndex];
                if (item.stock > 0) {
                    this.selectItem(item);
                }
            }
        },
        
        selectItem(item) {
            if (item.stock <= 0) return;
            
            this.selectedId = item.id;
            this.searchQuery = item.name;
            this.showDropdown = false;
            selectedItemData = item;
            currentQuickStock = item.stock;
            
            // Update stock info display
            updateStockInfoDisplay(item);
        }
    }));
});

function updateStockInfoDisplay(item) {
    const stockInfo = document.getElementById('stock-info');
    const lowStockBadge = document.getElementById('low-stock-badge');
    
    if (item) {
        // Calculate already allocated stock for this item
        const allocated = itemsList
            .filter(i => i.id === item.id.toString())
            .reduce((sum, i) => sum + i.quantity, 0);
        
        const available = item.stock - allocated;
        
        document.getElementById('available-stock').textContent = available + ' ' + item.unit;
        stockInfo.classList.remove('hidden');
        
        if (available <= 10) {
            lowStockBadge.classList.remove('hidden');
        } else {
            lowStockBadge.classList.add('hidden');
        }
        
        // Set max quantity
        document.getElementById('quick-quantity').max = available;
        checkQuickStock();
    } else {
        stockInfo.classList.add('hidden');
        currentQuickStock = 0;
    }
}

function updateQuickItemInfo(select) {
    // Legacy function - kept for compatibility
}

function checkQuickStock() {
    const hiddenInput = document.getElementById('quick-item');
    const quantity = document.getElementById('quick-quantity');
    const warning = document.getElementById('quick-stock-warning');
    const addBtn = document.getElementById('add-btn');
    
    if (!hiddenInput.value || !selectedItemData) return;
    
    const stock = selectedItemData.stock;
    
    // Calculate already allocated stock for this item
    const allocated = itemsList
        .filter(item => item.id === hiddenInput.value.toString())
        .reduce((sum, item) => sum + item.quantity, 0);
    
    const available = stock - allocated;
    const qty = parseInt(quantity.value) || 0;
    
    if (qty > available) {
        warning.classList.remove('hidden');
        addBtn.disabled = true;
    } else {
        warning.classList.add('hidden');
        addBtn.disabled = false;
    }
}

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
    const stock = item.stock;
    
    // Calculate already allocated stock for this item
    const allocated = itemsList
        .filter(i => i.id === itemId)
        .reduce((sum, i) => sum + i.quantity, 0);
    
    if (qty > (stock - allocated)) {
        alert('Jumlah melebihi stok tersedia!');
        return;
    }
    
    // Check if item already exists in list
    const existingIndex = itemsList.findIndex(i => i.id === itemId);
    if (existingIndex !== -1) {
        // Update quantity instead of adding new
        const newQty = itemsList[existingIndex].quantity + qty;
        if (newQty > stock) {
            alert('Total jumlah melebihi stok tersedia!');
            return;
        }
        itemsList[existingIndex].quantity = newQty;
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
            stock: stock,
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
    document.getElementById('stock-info').classList.add('hidden');
    
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
    
    const item = itemsList[index];
    
    // Calculate other allocations for this item
    const otherAllocations = itemsList
        .filter((it, idx) => it.id === item.id && idx !== index)
        .reduce((sum, it) => sum + it.quantity, 0);
    
    if (qty > (item.stock - otherAllocations)) {
        alert('Jumlah melebihi stok tersedia!');
        return;
    }
    
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
    
    tbody.innerHTML = itemsList.map((item, index) => {
        const remaining = item.stock - item.quantity;
        const isLow = remaining <= 10;
        
        return `
        <tr class="border-b dark:border-slate-700/50 border-slate-200 hover:bg-slate-700/20 transition">
            <td class="px-4 py-3 dark:text-slate-300 text-slate-700">${index + 1}</td>
            <td class="px-4 py-3">
                <span class="px-2 py-1 dark:bg-slate-600 bg-slate-200 rounded text-xs font-mono dark:text-slate-300 text-slate-700">${item.code}</span>
            </td>
            <td class="px-4 py-3 dark:text-white text-slate-800 font-medium">${item.name}</td>
            <td class="px-4 py-3 dark:text-slate-400 text-slate-500">${item.category}</td>
            <td class="px-4 py-3 text-center">
                <span class="${isLow ? 'text-amber-400' : 'text-emerald-400'} font-medium">${item.stock}</span>
                <span class="dark:text-slate-500 text-slate-400 text-sm">${item.unit}</span>
                ${isLow ? '<i class="fas fa-exclamation-triangle text-amber-400 ml-1 text-xs"></i>' : ''}
            </td>
            <td class="px-4 py-3 text-center">
                <input 
                    type="number" 
                    value="${item.quantity}" 
                    min="1"
                    max="${item.stock}"
                    onchange="updateItemQuantity(${index}, this.value)"
                    class="w-20 text-center dark:bg-slate-700/50 bg-slate-100 rounded-lg px-2 py-1 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-500"
                >
                <span class="dark:text-slate-500 text-slate-400 text-sm ml-1">${item.unit}</span>
            </td>
            <td class="px-4 py-3">
                <input 
                    type="text" 
                    value="${item.notes || ''}" 
                    onchange="updateItemNotes(${index}, this.value)"
                    placeholder="Catatan..."
                    class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-lg px-3 py-1 dark:text-white text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
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
    `}).join('');
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
    
    // Validate all stocks one more time
    for (const item of itemsList) {
        if (item.quantity > item.stock) {
            alert(`Stok ${item.name} tidak mencukupi!`);
            return;
        }
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
