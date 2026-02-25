@extends('layouts.app')

@section('title', 'Edit Barang')
@section('header', 'Edit Barang')
@section('breadcrumb', 'Master Data / Barang / Edit')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden transition-colors duration-300">
        <div class="p-6 border-b dark:border-slate-700/50 border-slate-200">
            <h3 class="text-lg font-bold dark:text-white text-slate-800 flex items-center">
                <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center mr-3">
                    <i class="fas fa-edit text-blue-400"></i>
                </div>
                Form Edit Barang
            </h3>
        </div>

        <form action="{{ route('items.update', $item->id) }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf
            @method('PUT')

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
                        value="{{ old('name', $item->name) }}"
                        class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors @error('name') border-red-500 @enderror"
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
                        value="{{ old('code', $item->code) }}"
                        class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors @error('code') border-red-500 @enderror"
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
                            <option value="{{ $key }}" {{ old('perolehan', $item->perolehan) == $key ? 'selected' : '' }}>{{ $value }}</option>
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
                        class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
                        required
                    >
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $item->category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
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
                        class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
                        required
                    >
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" {{ old('unit_id', $item->unit_id) == $unit->id ? 'selected' : '' }}>
                                {{ $unit->name }} ({{ $unit->symbol }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="price" class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                        Harga Satuan (Rp) <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        id="price" 
                        name="price" 
                        value="{{ old('price', $item->price ?? 0) }}"
                        min="0"
                        step="1"
                        class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
                        placeholder="0"
                        required
                    >
                </div>
            </div>

            <!-- Stok Saat Ini & Stok Minimum -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                        Jumlah (Stok Saat Ini)
                    </label>
                    <div class="w-full dark:bg-slate-600/50 bg-slate-200 rounded-xl px-4 py-3 dark:text-white text-slate-800 font-semibold">
                        <span class="text-emerald-400">{{ $item->stock }}</span> {{ $item->unit->name ?? '' }}
                    </div>
                    <p class="text-xs dark:text-slate-500 text-slate-400 mt-1">
                        <i class="fas fa-info-circle mr-1"></i>Stok dikelola melalui transaksi masuk/keluar
                    </p>
                </div>

                <div>
                    <label for="minimum_stock" class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                        Stok Minimum <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        id="minimum_stock" 
                        name="minimum_stock" 
                        value="{{ old('minimum_stock', $item->minimum_stock) }}"
                        min="0"
                        class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors @error('minimum_stock') border-red-500 @enderror"
                        required
                    >
                    @error('minimum_stock')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Foto Barang -->
            <div class="mb-6" x-data="{ photoMode: 'upload', fotoUrl: '{{ str_starts_with($item->photo, 'http') ? $item->photo : '' }}', searchGoogleImages() { const name = document.getElementById('name').value; if(!name) { alert('Isi nama barang dulu'); return; } window.open(`https://www.google.com/search?tbm=isch&q=${encodeURIComponent(name)}`, '_blank'); } }">
                <label for="photo" class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                    Foto Barang (Opsional)
                </label>
                
                @if($item->photo)
                <div class="mb-4 flex flex-col items-start" id="current-photo-container">
                    <p class="text-sm dark:text-slate-400 text-slate-500 mb-2">Foto Saat Ini:</p>
                    <img src="{{ $item->getPhotoUrl() }}" alt="Foto {{ $item->name }}" class="h-40 w-auto rounded-xl border dark:border-slate-600 border-slate-300 object-cover mb-3">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="delete_photo" value="1" class="w-4 h-4 text-red-500 rounded border-slate-300 focus:ring-red-500 dark:border-slate-600 dark:bg-slate-700 dark:checked:bg-red-500" onchange="togglePhotoInput(this)">
                        <span class="text-sm dark:text-slate-400 text-slate-600 group-hover:text-red-500 transition-colors">Hapus foto saat ini</span>
                    </label>
                </div>
                @endif

                <div id="photo-upload-container" class="dark:bg-slate-700/30 bg-slate-50 p-4 rounded-xl border dark:border-slate-600 border-slate-200 mt-2">
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
                        <input 
                            type="file" 
                            id="photo" 
                            name="photo" 
                            accept="image/*"
                            class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-500/20 file:text-blue-500 hover:file:bg-blue-500/30 @error('photo') border-red-500 @enderror"
                            onchange="previewImage(this, 'photo-preview')"
                        >
                        <p class="text-xs dark:text-slate-500 text-slate-400 mt-2">Format: JPG, PNG, WEBP. Maks 5MB. @if($item->photo) Memilih file baru akan menggantikan foto lama. @endif</p>
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
                        <p class="text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Preview Foto Baru:</p>
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
                >{{ old('description', $item->description) }}</textarea>
            </div>

            <!-- Total Nilai -->
            <div class="bg-purple-500/10 border border-purple-500/30 rounded-xl p-4 mb-6">
                <div class="flex items-center justify-between">
                    <span class="text-purple-300">
                        <i class="fas fa-calculator mr-2"></i>Total Nilai (Stok x Harga)
                    </span>
                    <span class="text-xl font-bold text-purple-400">
                        Rp {{ number_format($item->stock * ($item->price ?? 0), 0, ',', '.') }}
                    </span>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex items-center gap-4">
                <button 
                    type="submit" 
                    class="px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-indigo-700 transition-all shadow-lg"
                >
                    <i class="fas fa-save mr-2"></i>
                    Update
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
    function togglePhotoInput(checkbox) {
        const uploadContainer = document.getElementById('photo-upload-container');
        const fileInput = document.getElementById('photo');
        const previewContainer = document.getElementById('photo-preview-container');
        
        if (checkbox.checked) {
            // Sembunyikan input file dan kosongkan preview
            uploadContainer.style.opacity = '0.5';
            fileInput.value = '';
            previewContainer.classList.add('hidden');
        } else {
            // Tampilkan kembali
            uploadContainer.style.opacity = '1';
        }
    }

    function previewImage(input, previewId) {
        const previewContainer = document.getElementById(previewId + '-container');
        const previewImage = document.getElementById(previewId);
        const checkbox = document.querySelector('input[name="delete_photo"]');
        
        // Jika ada checkbox hapus, pastikan tidak tercentang
        if (checkbox && input.files && input.files[0]) {
            checkbox.checked = false;
            document.getElementById('photo-upload-container').style.opacity = '1';
        }
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewContainer.classList.remove('hidden');
            }
            
            reader.readAsDataURL(input.files[0]);
        } else {
            previewImage.src = '#';
            if(document.querySelector('input[name="foto_tipe"]:checked')?.value === 'upload') {
                previewContainer.classList.add('hidden');
            }
        }
    }
</script>
@endsection
