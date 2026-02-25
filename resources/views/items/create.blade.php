@extends('layouts.app')

@section('title', 'Tambah Barang')
@section('header', 'Tambah Barang')
@section('breadcrumb', 'Master Data / Barang / Tambah')

@section('content')
<div class="max-w-4xl mx-auto">
    <form action="{{ route('items.store') }}" method="POST" enctype="multipart/form-data" class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden transition-colors duration-300">
        <div class="p-6 border-b dark:border-slate-700/50 border-slate-200">
            <h3 class="text-lg font-bold dark:text-white text-slate-800 flex items-center">
                <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center mr-3">
                    <i class="fas fa-plus text-emerald-400"></i>
                </div>
                Form Tambah Barang
            </h3>
        </div>

        <form action="{{ route('items.store') }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf

            <!-- Nama Barang & Kode -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="name" class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                        Nama Barang <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="{{ old('name') }}"
                        class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors @error('name') border-red-500 @enderror"
                        placeholder="Contoh: Paracetamol 500mg"
                        required
                    >
                    @error('name')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="code" class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                        Kode Barang <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="code" 
                        name="code" 
                        value="{{ old('code') }}"
                        class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors @error('code') border-red-500 @enderror"
                        placeholder="Contoh: BRG001"
                        required
                    >
                    @error('code')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Perolehan & Jenis Barang (Kategori) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="perolehan" class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                        Perolehan <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="perolehan" 
                        name="perolehan" 
                        class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
                        required
                    >
                        <option value="">Pilih Perolehan</option>
                        @foreach(\App\Models\Item::getPerolehanOptions() as $key => $value)
                            <option value="{{ $key }}" {{ old('perolehan') == $key ? 'selected' : '' }}>{{ $value }}</option>
                        @endforeach
                    </select>
                    @error('perolehan')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="category_id" class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                        Jenis Barang <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="category_id" 
                        name="category_id" 
                        class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors @error('category_id') border-red-500 @enderror"
                        required
                    >
                        <option value="">Pilih Jenis Barang</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Satuan & Harga Satuan -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="unit_id" class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                        Satuan <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="unit_id" 
                        name="unit_id" 
                        class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors @error('unit_id') border-red-500 @enderror"
                        required
                    >
                        <option value="">Pilih Satuan</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                                {{ $unit->name }} ({{ $unit->symbol }})
                            </option>
                        @endforeach
                    </select>
                    @error('unit_id')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="price" class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                        Harga Satuan (Rp) <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        id="price" 
                        name="price" 
                        value="{{ old('price', 0) }}"
                        min="0"
                        step="1"
                        class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
                        placeholder="0"
                        required
                    >
                    @error('price')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Stok & Stok Minimum -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="stock" class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                        Jumlah (Stok Awal)
                    </label>
                    <input 
                        type="number" 
                        id="stock" 
                        name="stock" 
                        value="{{ old('stock', 0) }}"
                        min="0"
                        class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
                        placeholder="0"
                    >
                </div>

                <div>
                    <label for="minimum_stock" class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                        Stok Minimum <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        id="minimum_stock" 
                        name="minimum_stock" 
                        value="{{ old('minimum_stock', 10) }}"
                        min="0"
                        class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors @error('minimum_stock') border-red-500 @enderror"
                        placeholder="10"
                        required
                    >
                    @error('minimum_stock')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Foto Barang -->
            <div class="mb-6" x-data="{ photoMode: 'upload', fotoUrl: '', searchGoogleImages() { const name = document.getElementById('name').value; if(!name) { alert('Isi nama barang dulu'); return; } window.open(`https://www.google.com/search?tbm=isch&q=${encodeURIComponent(name)}`, '_blank'); } }">
                <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                    Foto Barang (Opsional)
                </label>

                <div class="dark:bg-slate-700/30 bg-slate-50 p-4 rounded-xl border dark:border-slate-600 border-slate-200">
                    <div class="flex gap-4 mb-4">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="radio" name="foto_tipe" value="upload" x-model="photoMode" class="sr-only peer">
                            <div class="px-4 py-2 text-sm font-medium rounded-lg dark:text-slate-300 text-slate-700 dark:bg-slate-800 bg-white border dark:border-slate-600 border-slate-300 peer-checked:border-emerald-500 peer-checked:text-emerald-500 peer-checked:bg-emerald-50/10 transition-all cursor-pointer">
                                <i class="fas fa-upload mr-2"></i>Upload File
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
                        <input 
                            type="file" 
                            id="photo" 
                            name="photo" 
                            accept="image/*"
                            class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-500/20 file:text-emerald-500 hover:file:bg-emerald-500/30 @error('photo') border-red-500 @enderror"
                            onchange="previewImage(this, 'photo-preview')"
                        >
                        <p class="text-xs dark:text-slate-500 text-slate-400 mt-2">Format: JPG, PNG, WEBP. Maks 5MB.</p>
                        @error('photo')
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
                                <i class="fab fa-google mr-2"></i>Cari
                            </button>
                        </div>
                        <p class="text-xs dark:text-slate-500 text-slate-400">Paste link gambar dari pencarian Google Images.</p>
                        @error('foto_url')
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Area Preview -->
                    <div id="photo-preview-container" class="mt-4 hidden" :class="{'hidden': photoMode === 'url' && !fotoUrl}">
                        <p class="text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Preview:</p>
                        <img id="photo-preview" :src="photoMode === 'url' ? fotoUrl : '#'" alt="Preview" class="h-40 w-auto rounded-xl border dark:border-slate-600 border-slate-300 object-cover" onerror="this.classList.add('hidden');" onload="this.classList.remove('hidden');">
                    </div>
                </div>
            </div>

            <!-- Deskripsi -->
            <div class="mb-6">
                <label for="description" class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                    Deskripsi
                </label>
                <textarea 
                    id="description" 
                    name="description" 
                    rows="3"
                    class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
                    placeholder="Deskripsi barang (opsional)"
                >{{ old('description') }}</textarea>
            </div>

            <!-- Preview Info -->
            <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-4 mb-6">
                <p class="text-sm text-blue-300">
                    <i class="fas fa-info-circle mr-2"></i>
                    Data yang ditambahkan akan muncul di Laporan dengan format: Nama Barang, Perolehan, Jenis Barang, Satuan, Harga Satuan, Jumlah
                </p>
            </div>

            <!-- Buttons -->
            <div class="flex items-center gap-4">
                <button 
                    type="submit" 
                    class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:from-emerald-600 hover:to-teal-700 transition-all shadow-lg"
                >
                    <i class="fas fa-save mr-2"></i>
                    Simpan
                </button>
                <a 
                    href="{{ route('items.index') }}" 
                    class="px-6 py-3 dark:bg-slate-700 bg-slate-200 dark:text-slate-300 text-slate-700 rounded-xl font-semibold hover:bg-slate-600 hover:text-white transition-all"
                >
                    <i class="fas fa-arrow-left mr-2"></i>
                    Kembali
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function previewImage(input, previewId) {
        const previewContainer = document.getElementById(previewId + '-container');
        const previewImage = document.getElementById(previewId);
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewContainer.classList.remove('hidden');
            }
            
            reader.readAsDataURL(input.files[0]);
        } else {
            previewImage.src = '#';
            previewContainer.classList.add('hidden');
        }
    }
</script>
@endsection
