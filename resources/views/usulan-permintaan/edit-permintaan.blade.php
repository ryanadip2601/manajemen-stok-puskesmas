@extends('layouts.app')

@section('title', 'Edit Permintaan Barang Habis Pakai')
@section('header', 'Edit Permintaan Barang Habis Pakai')
@section('breadcrumb', 'Perbarui data permintaan barang habis pakai')

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
                        <h3 class="text-lg font-bold dark:text-white text-slate-800">Edit Permintaan: {{ $usulan->kode_permintaan }}</h3>
                        <p class="text-sm dark:text-slate-400 text-slate-500">Perbarui data permintaan barang habis pakai</p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-semibold border {{ \App\Models\UsulanPermintaanBarang::getStatusBadgeClass($usulan->status) }}">
                    {{ $usulan->status_label }}
                </span>
            </div>
        </div>

        @if($usulan->status === 'revisi' && $usulan->permintaan_revisi)
        <div class="px-6 py-4 bg-purple-500/10 border-b dark:border-slate-700/50 border-slate-200">
            <div class="flex items-start">
                <i class="fas fa-exclamation-triangle text-purple-400 mt-1 mr-3"></i>
                <div>
                    <p class="font-semibold text-purple-400">Permintaan Revisi dari Admin:</p>
                    <p class="dark:text-purple-300 text-purple-700 mt-1">{{ $usulan->permintaan_revisi }}</p>
                </div>
            </div>
        </div>
        @endif

        <form action="{{ route('usulan-permintaan-barang.update', $usulan->id) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
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

            <!-- Ruangan Tujuan -->
            <div>
                <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                    <i class="fas fa-door-open mr-2 text-blue-400"></i>Ruangan Tujuan <span class="text-red-500">*</span>
                </label>
                <select name="ruangan_id" required class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('ruangan_id') border-red-500 @enderror">
                    <option value="">-- Pilih Ruangan --</option>
                    @foreach($ruanganList as $ruangan)
                        <option value="{{ $ruangan->id }}" {{ old('ruangan_id', $usulan->ruangan_id) == $ruangan->id ? 'selected' : '' }}>{{ $ruangan->nama_ruangan }}</option>
                    @endforeach
                </select>
                @error('ruangan_id')
                    <p class="text-red-400 text-sm mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                @enderror
            </div>

            <!-- Nama Barang -->
            <div>
                <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                    <i class="fas fa-box mr-2 text-emerald-400"></i>Nama Barang <span class="text-red-500">*</span>
                </label>
                <input type="text" name="nama_barang" value="{{ old('nama_barang', $usulan->nama_barang) }}" required placeholder="Contoh: Kertas HVS A4, Tinta Printer, Spidol, dll" class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('nama_barang') border-red-500 @enderror">
                @error('nama_barang')
                    <p class="text-red-400 text-sm mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                @enderror
            </div>

            <!-- Jumlah & Prioritas -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                        <i class="fas fa-sort-numeric-up mr-2 text-amber-400"></i>Jumlah <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="jumlah" value="{{ old('jumlah', $usulan->jumlah) }}" min="1" required class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('jumlah') border-red-500 @enderror">
                    @error('jumlah')
                        <p class="text-red-400 text-sm mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                        <i class="fas fa-flag mr-2 text-red-400"></i>Prioritas <span class="text-red-500">*</span>
                    </label>
                    <select name="prioritas" required class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('prioritas') border-red-500 @enderror">
                        <option value="rendah" {{ old('prioritas', $usulan->prioritas) == 'rendah' ? 'selected' : '' }}>Rendah</option>
                        <option value="sedang" {{ old('prioritas', $usulan->prioritas) == 'sedang' ? 'selected' : '' }}>Sedang</option>
                        <option value="tinggi" {{ old('prioritas', $usulan->prioritas) == 'tinggi' ? 'selected' : '' }}>Tinggi</option>
                    </select>
                    @error('prioritas')
                        <p class="text-red-400 text-sm mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Foto Barang -->
            <div class="space-y-4">
                <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700">
                    <i class="fas fa-camera mr-2 text-pink-400"></i>Foto Barang <span class="font-normal dark:text-slate-500 text-slate-400">(Opsional)</span>
                </label>

                <div class="dark:bg-slate-700/30 bg-slate-50 p-4 rounded-xl border dark:border-slate-600 border-slate-200">
                    <!-- Preview Foto Saat Ini -->
                    @if($usulan->foto_barang)
                        <div class="mb-4">
                            <p class="text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">Foto Saat Ini:</p>
                            <div class="relative inline-block">
                                <img src="{{ $usulan->tipe_foto_barang === 'url' ? $usulan->foto_barang : asset($usulan->foto_barang) }}" 
                                     alt="Foto {{ $usulan->nama_barang }}" 
                                     class="h-32 w-auto object-cover rounded-xl border dark:border-slate-600 border-slate-300">
                            </div>
                            <p class="text-xs dark:text-slate-400 text-slate-500 mt-2">Upload foto baru di bawah ini untuk mengganti foto saat ini.</p>
                        </div>
                    @endif

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
                                <i class="fas fa-link mr-2"></i>Pakai Link / Google
                            </div>
                        </label>
                    </div>

                    <!-- Mode Upload File -->
                    <div x-show="photoMode === 'upload'" x-transition>
                        <input type="file" name="foto_upload" accept="image/*"
                            class="w-full text-sm dark:text-slate-300 text-slate-700 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-emerald-500/20 file:text-emerald-400 hover:file:bg-emerald-500/30 transition-all dark:bg-slate-800 bg-white border dark:border-slate-600 border-slate-300 rounded-xl"
                            onchange="previewImage(this, 'photo-preview')">
                        <p class="text-xs dark:text-slate-500 text-slate-400 mt-2">Format: JPG, PNG, WEBP. Maks 5MB.</p>
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

                    <!-- Area Preview Baru -->
                    <div id="photo-preview-container" class="mt-4 hidden" :class="{'hidden': photoMode === 'url' && !fotoUrl}">
                        <p class="text-xs dark:text-slate-400 text-slate-500 mb-2">Preview Foto Baru:</p>
                        <img id="photo-preview" :src="photoMode === 'url' ? fotoUrl : '#'" alt="Preview" class="h-32 w-auto object-cover rounded-xl border dark:border-slate-600 border-slate-300 bg-slate-100 dark:bg-slate-800">
                    </div>
                </div>
            </div>

            <!-- Keterangan -->
            <div>
                <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                    <i class="fas fa-comment-alt mr-2 text-slate-400"></i>Keterangan/Alasan <span class="font-normal dark:text-slate-500 text-slate-400">(Opsional)</span>
                </label>
                <textarea name="keterangan" rows="3" placeholder="Jelaskan alasan atau kebutuhan permintaan barang ini..." class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('keterangan', $usulan->keterangan) }}</textarea>
                @error('keterangan')
                    <p class="text-red-400 text-sm mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                @enderror
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-between pt-4 border-t dark:border-slate-700/50 border-slate-200">
                <a href="{{ route('usulan-permintaan-barang.index') }}" class="px-6 py-3 dark:bg-slate-700 bg-slate-200 dark:hover:bg-slate-600 hover:bg-slate-300 dark:text-white text-slate-700 rounded-xl font-semibold transition">
                    <i class="fas fa-arrow-left mr-2"></i>Batal
                </a>
                <button type="submit" class="px-8 py-3 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white rounded-xl font-semibold transition shadow-lg">
                    <i class="fas fa-save mr-2"></i>Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function permintaanForm() {
    return {
        photoMode: 'upload', // 'upload' atau 'url'
        fotoUrl: '',
        
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
@endpush
@endsection
