@extends('layouts.app')

@section('title', 'Edit Permintaan Barang')
@section('header', 'Edit Permintaan Barang')
@section('breadcrumb', 'Edit permintaan barang Anda')

@section('content')
<div class="max-w-4xl mx-auto" x-data="permintaanForm()">
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 bg-gradient-to-r from-amber-500/20 to-orange-500/20 border-b dark:border-slate-700/50 border-slate-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-500 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                        <i class="fas fa-edit text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold dark:text-white text-slate-800">Edit Permintaan: {{ $permintaan->kode_permintaan }}</h3>
                        <p class="text-sm dark:text-slate-400 text-slate-500">Perbarui data permintaan barang Anda</p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-semibold border {{ \App\Models\PermintaanBarang::getStatusBadgeClass($permintaan->status) }}">
                    {{ $permintaan->status_label }}
                </span>
            </div>
        </div>

        <!-- Revisi Alert -->
        @if($permintaan->is_revisi && $permintaan->permintaan_revisi)
        <div class="px-6 py-4 bg-purple-500/10 border-b dark:border-slate-700/50 border-slate-200">
            <div class="flex items-start">
                <i class="fas fa-exclamation-triangle text-purple-400 mt-1 mr-3"></i>
                <div>
                    <p class="font-semibold text-purple-400">Permintaan Revisi dari Admin:</p>
                    <p class="dark:text-purple-300 text-purple-700 mt-1">{{ $permintaan->permintaan_revisi }}</p>
                </div>
            </div>
        </div>
        @endif

        <form action="{{ route('permintaan-barang.update', $permintaan->id) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Error Messages -->
            @if($errors->any())
            <div class="p-4 bg-red-500/10 border border-red-500/30 rounded-xl">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-red-400 mt-0.5 mr-3"></i>
                    <div class="text-sm">
                        <p class="font-semibold text-red-400 mb-2">Terdapat kesalahan pada form:</p>
                        <ul class="list-disc list-inside space-y-1 text-red-300">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            @endif

            @if(session('error'))
            <div class="p-4 bg-red-500/10 border border-red-500/30 rounded-xl">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-red-400 mt-0.5 mr-3"></i>
                    <p class="text-sm text-red-300">{{ session('error') }}</p>
                </div>
            </div>
            @endif

            <!-- Info Box -->
            <div class="p-4 bg-blue-500/10 border border-blue-500/30 rounded-xl">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-400 mt-0.5 mr-3"></i>
                    <div class="text-sm dark:text-blue-300 text-blue-700">
                        <p class="font-semibold mb-1">Cara Penggunaan:</p>
                        <ul class="list-disc list-inside space-y-1 dark:text-blue-400 text-blue-600">
                            <li>Cari nama barang di kolom pencarian di bawah</li>
                            <li>Jika barang ditemukan, klik untuk memilih (otomatis jadi <strong>permintaan restok</strong>)</li>
                            <li>Jika barang tidak ditemukan, centang "Barang belum terdaftar" dan tulis manual (jadi <strong>permintaan barang baru</strong>)</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Pencarian Barang -->
            <div>
                <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                    <i class="fas fa-search mr-2 text-blue-400"></i>Cari Barang
                </label>
                <div class="relative">
                    <input type="text" x-model="searchQuery" @input.debounce.300ms="searchItems()" @focus="showDropdown = searchResults.length > 0"
                        placeholder="Ketik nama barang untuk mencari..."
                        class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl pl-10 pr-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :disabled="isManual">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 dark:text-slate-400 text-slate-500"></i>

                    <!-- Search Results Dropdown -->
                    <div x-show="showDropdown && searchResults.length > 0" @click.away="showDropdown = false"
                        x-transition class="absolute z-50 w-full mt-2 dark:bg-slate-800 bg-white rounded-xl border dark:border-slate-600 border-slate-200 shadow-2xl max-h-64 overflow-y-auto">
                        <template x-for="item in searchResults" :key="item.id">
                            <button type="button" @click="selectItem(item)"
                                class="w-full px-4 py-3 text-left dark:hover:bg-slate-700 hover:bg-slate-50 transition border-b dark:border-slate-700/50 border-slate-100 last:border-0">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-semibold dark:text-white text-slate-800" x-text="item.name"></p>
                                        <p class="text-xs dark:text-slate-400 text-slate-500">
                                            <span x-text="item.category"></span> | <span x-text="item.unit"></span>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold" :class="item.stock === 0 ? 'text-red-400' : (item.is_low_stock ? 'text-amber-400' : 'text-emerald-400')">
                                            Stok: <span x-text="item.stock"></span>
                                        </p>
                                        <p class="text-xs dark:text-slate-500 text-slate-400">Min: <span x-text="item.minimum_stock"></span></p>
                                    </div>
                                </div>
                                <template x-if="item.is_low_stock">
                                    <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-orange-500/20 text-orange-400">Stok Menipis</span>
                                </template>
                            </button>
                        </template>
                    </div>

                    <!-- No Results -->
                    <div x-show="showDropdown && searchResults.length === 0 && searchQuery.length >= 2 && !isSearching"
                        class="absolute z-50 w-full mt-2 dark:bg-slate-800 bg-white rounded-xl border dark:border-slate-600 border-slate-200 shadow-2xl p-4">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-exclamation-circle text-amber-400"></i>
                            <div>
                                <p class="dark:text-slate-300 text-slate-700 text-sm font-semibold">Barang tidak ditemukan</p>
                                <p class="dark:text-slate-400 text-slate-500 text-xs">Centang "Barang belum terdaftar" di bawah untuk menulis manual</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selected Item Card -->
                <div x-show="selectedItem" x-transition class="mt-3 dark:bg-slate-700/30 bg-slate-50 border dark:border-slate-600/50 border-slate-200 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold border bg-orange-500/20 text-orange-400 border-orange-500/30">Restok Barang</span>
                        <button type="button" @click="clearSelection()" class="text-red-400 hover:text-red-300 text-sm">
                            <i class="fas fa-times mr-1"></i>Hapus Pilihan
                        </button>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="dark:text-slate-400 text-slate-500">Nama Barang</p>
                            <p class="font-semibold dark:text-white text-slate-800" x-text="selectedItem?.name"></p>
                        </div>
                        <div>
                            <p class="dark:text-slate-400 text-slate-500">Kategori</p>
                            <p class="font-semibold dark:text-white text-slate-800" x-text="selectedItem?.category"></p>
                        </div>
                        <div>
                            <p class="dark:text-slate-400 text-slate-500">Stok Saat Ini</p>
                            <p class="font-bold text-lg" :class="selectedItem?.stock === 0 ? 'text-red-400' : 'text-amber-400'" x-text="(selectedItem?.stock || 0) + ' ' + (selectedItem?.unit_symbol || '')"></p>
                        </div>
                        <div>
                            <p class="dark:text-slate-400 text-slate-500">Stok Minimum</p>
                            <p class="font-semibold dark:text-white text-slate-800" x-text="(selectedItem?.minimum_stock || 0) + ' ' + (selectedItem?.unit_symbol || '')"></p>
                        </div>
                    </div>
                </div>

                <!-- Hidden input for item_id -->
                <input type="hidden" name="item_id" :value="selectedItem?.id || ''">
            </div>

            <!-- Manual Toggle -->
            <div class="flex items-center gap-3 p-4 dark:bg-slate-700/20 bg-slate-50 rounded-xl border dark:border-slate-700/50 border-slate-200">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="isManual" name="is_manual" value="1" @change="handleManualToggle()" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:after:border-gray-600 peer-checked:bg-indigo-600"></div>
                </label>
                <div>
                    <p class="font-semibold dark:text-slate-300 text-slate-700 text-sm">Barang belum terdaftar (tulis manual)</p>
                    <p class="text-xs dark:text-slate-500 text-slate-400">Aktifkan jika barang yang Anda butuhkan belum ada di sistem</p>
                </div>
            </div>

            <!-- Nama Barang Manual (hanya jika isManual) -->
            <div x-show="isManual" x-transition>
                <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                    <i class="fas fa-box mr-2 text-indigo-400"></i>Nama Barang <span class="text-red-500">*</span>
                </label>
                <input type="text" name="nama_barang" :value="isManual ? '{{ old('nama_barang', $permintaan->is_barang_baru ? $permintaan->nama_barang : '') }}' : ''" :required="isManual" placeholder="Contoh: Kertas HVS A4, Tinta Printer, Spidol, dll..."
                    class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('nama_barang') border-red-500 @enderror">
                @error('nama_barang')
                    <p class="text-red-400 text-sm mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                @enderror
            </div>

            <!-- Ruangan Tujuan (hanya jika isManual / barang baru) -->
            <div x-show="isManual" x-transition>
                <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                    <i class="fas fa-door-open mr-2 text-blue-400"></i>Ruangan Tujuan <span class="font-normal dark:text-slate-500 text-slate-400">(Opsional)</span>
                </label>
                <select name="ruangan_id" class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">-- Pilih Ruangan --</option>
                    @foreach($ruanganList as $ruangan)
                        <option value="{{ $ruangan->id }}" {{ old('ruangan_id', $permintaan->ruangan_id) == $ruangan->id ? 'selected' : '' }}>{{ $ruangan->nama_ruangan }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Foto Barang Baru (hanya jika isManual) -->
            <div x-show="isManual" x-transition class="space-y-4">
                <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700">
                    <i class="fas fa-image mr-2 text-purple-400"></i>Foto Barang <span class="font-normal dark:text-slate-500 text-slate-400">(Opsional)</span>
                </label>

                <div class="dark:bg-slate-700/30 bg-slate-50 p-4 rounded-xl border dark:border-slate-600 border-slate-200">
                    <!-- Foto Saat Ini -->
                    @if($permintaan->foto_barang)
                    <div class="mb-5 pb-5 border-b dark:border-slate-600 border-slate-200">
                        <p class="text-sm dark:text-slate-400 text-slate-500 mb-2">Foto Saat Ini:</p>
                        <div class="flex items-start gap-4">
                            <img src="{{ $permintaan->getFotoUrl() }}" alt="Foto Saat Ini" class="h-32 w-auto object-cover rounded-xl border dark:border-slate-600 border-slate-300 shadow-sm" onerror="this.src='https://via.placeholder.com/150?text=Image+Not+Found'; this.onerror='';">
                            <label class="flex items-center gap-2 cursor-pointer mt-2 text-sm text-red-500 hover:text-red-600 transition-colors">
                                <input type="checkbox" name="hapus_foto" value="1" class="w-4 h-4 rounded border-slate-300 text-red-500 focus:ring-red-500">
                                Hapus foto ini
                            </label>
                        </div>
                    </div>
                    @endif

                    <div class="flex gap-4 mb-4">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="radio" name="foto_tipe" value="upload" x-model="photoMode" class="sr-only peer">
                            <div class="px-4 py-2 text-sm font-medium rounded-lg dark:text-slate-300 text-slate-700 dark:bg-slate-800 bg-white border dark:border-slate-600 border-slate-300 peer-checked:border-emerald-500 peer-checked:text-emerald-500 peer-checked:bg-emerald-50/10 transition-all cursor-pointer">
                                <i class="fas fa-upload mr-2"></i>Upload Baru
                            </div>
                        </label>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="radio" name="foto_tipe" value="url" x-model="photoMode" class="sr-only peer">
                            <div class="px-4 py-2 text-sm font-medium rounded-lg dark:text-slate-300 text-slate-700 dark:bg-slate-800 bg-white border dark:border-slate-600 border-slate-300 peer-checked:border-blue-500 peer-checked:text-blue-500 peer-checked:bg-blue-50/10 transition-all cursor-pointer">
                                <i class="fas fa-link mr-2"></i>Link / Google
                            </div>
                        </label>
                    </div>

                    <!-- Mode Upload File -->
                    <div x-show="photoMode === 'upload'" x-transition>
                        <input type="file" name="foto_upload" accept="image/*"
                            class="w-full text-sm dark:text-slate-300 text-slate-700 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-emerald-500/20 file:text-emerald-400 hover:file:bg-emerald-500/30 transition-all dark:bg-slate-800 bg-white border dark:border-slate-600 border-slate-300 rounded-xl"
                            onchange="previewImage(this, 'photo-preview')">
                        <p class="text-xs dark:text-slate-500 text-slate-400 mt-2">Format: JPG, PNG, WEBP. Maks 5MB. @if($permintaan->foto_barang) Memilih foto baru akan mengganti foto lama. @endif</p>
                        @error('foto_upload')
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Mode Link URL -->
                    <div x-show="photoMode === 'url'" x-transition class="space-y-3">
                        <div class="flex gap-2">
                            <input type="url" name="foto_url" x-model="fotoUrl" placeholder="https://contoh.com/gambar.jpg"
                                class="flex-1 dark:bg-slate-800 bg-white border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-2.5 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            <button type="button" @click="searchGoogleImages()" 
                                class="px-4 py-2.5 bg-blue-500 hover:bg-blue-600 text-white rounded-xl text-sm font-semibold transition-colors whitespace-nowrap">
                                <i class="fab fa-google mr-2"></i>Cari di Google
                            </button>
                        </div>
                        <p class="text-xs dark:text-slate-500 text-slate-400">Paste link gambar langsung dari hasil pencarian Google Images.</p>
                        @error('foto_url')
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Area Preview -->
                    <div id="photo-preview-container" class="mt-4 hidden" :class="{'hidden': photoMode === 'url' && (!fotoUrl || fotoUrl === '{{ $permintaan->tipe_foto_barang == 'url' ? $permintaan->foto_barang : '' }}')}">
                        <p class="text-xs dark:text-slate-400 text-slate-500 mb-2">Preview Foto Baru:</p>
                        <img id="photo-preview" :src="photoMode === 'url' ? fotoUrl : '#'" alt="Preview" class="h-40 w-auto object-cover rounded-xl border dark:border-slate-600 border-slate-300 bg-slate-100 dark:bg-slate-800" onerror="this.classList.add('hidden')">
                    </div>
                </div>
            </div>

            <!-- Jumlah & Prioritas -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                        <i class="fas fa-sort-numeric-up mr-2 text-amber-400"></i>Jumlah <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="jumlah" value="{{ old('jumlah', $permintaan->jumlah) }}" min="1" required class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('jumlah') border-red-500 @enderror">
                    @error('jumlah')
                        <p class="text-red-400 text-sm mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                        <i class="fas fa-flag mr-2 text-red-400"></i>Prioritas <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="relative cursor-pointer">
                            <input type="radio" name="prioritas" value="rendah" class="peer sr-only" {{ old('prioritas', $permintaan->prioritas) == 'rendah' ? 'checked' : '' }}>
                            <div class="p-3 rounded-xl border-2 dark:border-slate-600 border-slate-300 peer-checked:border-green-500 peer-checked:bg-green-500/10 transition text-center">
                                <i class="fas fa-arrow-down text-green-400 text-lg mb-1"></i>
                                <p class="text-sm font-semibold dark:text-slate-300 text-slate-700">Rendah</p>
                            </div>
                        </label>
                        <label class="relative cursor-pointer">
                            <input type="radio" name="prioritas" value="sedang" class="peer sr-only" {{ old('prioritas', $permintaan->prioritas) == 'sedang' ? 'checked' : '' }}>
                            <div class="p-3 rounded-xl border-2 dark:border-slate-600 border-slate-300 peer-checked:border-amber-500 peer-checked:bg-amber-500/10 transition text-center">
                                <i class="fas fa-minus text-amber-400 text-lg mb-1"></i>
                                <p class="text-sm font-semibold dark:text-slate-300 text-slate-700">Sedang</p>
                            </div>
                        </label>
                        <label class="relative cursor-pointer">
                            <input type="radio" name="prioritas" value="tinggi" class="peer sr-only" {{ old('prioritas', $permintaan->prioritas) == 'tinggi' ? 'checked' : '' }}>
                            <div class="p-3 rounded-xl border-2 dark:border-slate-600 border-slate-300 peer-checked:border-red-500 peer-checked:bg-red-500/10 transition text-center">
                                <i class="fas fa-arrow-up text-red-400 text-lg mb-1"></i>
                                <p class="text-sm font-semibold dark:text-slate-300 text-slate-700">Tinggi</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Keterangan -->
            <div>
                <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                    <i class="fas fa-comment-alt mr-2 text-slate-400"></i>Keterangan / Alasan <span class="font-normal dark:text-slate-500 text-slate-400">(Opsional)</span>
                </label>
                <textarea name="keterangan" rows="3" placeholder="Jelaskan alasan atau kebutuhan permintaan barang ini..." class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none">{{ old('keterangan', $permintaan->keterangan) }}</textarea>
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-between pt-4 border-t dark:border-slate-700/50 border-slate-200">
                <a href="{{ route('permintaan-barang.index') }}" class="px-6 py-3 dark:bg-slate-700 bg-slate-200 dark:hover:bg-slate-600 hover:bg-slate-300 dark:text-white text-slate-700 rounded-xl font-semibold transition">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
                <button type="submit" class="px-8 py-3 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white rounded-xl font-semibold transition shadow-lg shadow-amber-500/25">
                    <i class="fas fa-save mr-2"></i>Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function permintaanForm() {
    @if($permintaan->is_restok && $permintaan->item)
    const existingItem = {
        id: {{ $permintaan->item->id }},
        name: @json($permintaan->item->name),
        category: @json($permintaan->item->category->name ?? ''),
        unit: @json($permintaan->item->unit->name ?? ''),
        unit_symbol: @json($permintaan->item->unit->symbol ?? ''),
        stock: {{ $permintaan->item->stock ?? 0 }},
        minimum_stock: {{ $permintaan->item->minimum_stock ?? 0 }},
        is_low_stock: {{ ($permintaan->item->stock <= $permintaan->item->minimum_stock) ? 'true' : 'false' }}
    };
    @else
    const existingItem = null;
    @endif

    return {
        searchQuery: existingItem ? existingItem.name : '',
        searchResults: [],
        showDropdown: false,
        isSearching: false,
        selectedItem: existingItem,
        isManual: {{ old('is_manual', $permintaan->is_barang_baru ? '1' : '0') ? 'true' : 'false' }},
        photoMode: '{{ old('foto_tipe', 'upload') }}', // 'upload' atau 'url'
        fotoUrl: '{{ old('foto_url', $permintaan->tipe_foto_barang == 'url' ? $permintaan->foto_barang : '') }}',

        async searchItems() {
            if (this.searchQuery.length < 2) {
                this.searchResults = [];
                this.showDropdown = false;
                return;
            }

            this.isSearching = true;
            try {
                const response = await fetch(`{{ route('api.permintaan-barang.search-items') }}?q=${encodeURIComponent(this.searchQuery)}`);
                this.searchResults = await response.json();
                this.showDropdown = true;
            } catch (e) {
                console.error('Search error:', e);
            }
            this.isSearching = false;
        },

        selectItem(item) {
            this.selectedItem = item;
            this.searchQuery = item.name;
            this.showDropdown = false;
            this.isManual = false;
        },

        clearSelection() {
            this.selectedItem = null;
            this.searchQuery = '';
            this.searchResults = [];
        },

        handleManualToggle() {
            if (this.isManual) {
                this.clearSelection();
            } else {
                if(this.selectedItem) {
                    this.searchQuery = this.selectedItem.name;
                }
            }
        },

        searchGoogleImages() {
            // Ambil nama barang dari input form
            const namaBarang = document.querySelector('input[name="nama_barang"]').value;
            if (!namaBarang) {
                alert('Tolong isi nama barang terlebih dahulu untuk melakukan pencarian di Google Images.');
                return;
            }
            
            // Format URL pencarian gambar Google
            const query = encodeURIComponent(namaBarang);
            const googleImagesUrl = `https://www.google.com/search?tbm=isch&q=${query}`;
            
            // Buka tab baru
            window.open(googleImagesUrl, '_blank');
        }
    }
}

// Fungsi preview untuk upload file lokal
function previewImage(input, previewId) {
    const previewContainer = document.getElementById(previewId + '-container');
    const previewImage = document.getElementById(previewId);
    
    // Jika ada checkbox hapus foto, uncheck saat upload baru
    const deleteCheckbox = document.querySelector('input[name="hapus_foto"]');
    if(deleteCheckbox && input.files && input.files[0]) {
        deleteCheckbox.checked = false;
    }
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            previewImage.classList.remove('hidden');
            previewContainer.classList.remove('hidden');
        }
        
        reader.readAsDataURL(input.files[0]);
    } else {
        previewImage.src = '#';
        if(document.querySelector('[x-model="photoMode"]').value === 'upload') {
            previewContainer.classList.add('hidden');
        }
    }
}
</script>
@endsection
