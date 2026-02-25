@extends('layouts.app')

@section('title', 'Buat Usulan Penerimaan Barang')
@section('header', 'Buat Usulan Penerimaan Barang')
@section('breadcrumb', 'Ajukan usulan penerimaan barang baru ke ruangan')

@section('content')
<div class="max-w-4xl mx-auto" x-data="usulanForm()">
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 bg-gradient-to-r from-emerald-500/20 to-teal-500/20 border-b dark:border-slate-700/50 border-slate-200">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                    <i class="fas fa-plus-circle text-white text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold dark:text-white text-slate-800">Form Usulan Penerimaan Barang</h3>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Isi form berikut untuk mengusulkan penerimaan barang</p>
                </div>
            </div>
        </div>

        <form action="{{ route('usulan-penerimaan.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf

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
                        <p class="font-semibold mb-1">Petunjuk Pengisian:</p>
                        <ul class="list-disc list-inside space-y-1 dark:text-blue-400 text-blue-600">
                            <li>Pilih ruangan tujuan penempatan barang</li>
                            <li>Isi data barang dengan lengkap dan akurat</li>
                            <li>Upload foto barang jika tersedia (opsional, maks. 5 foto)</li>
                            <li>Usulan akan diverifikasi oleh admin sebelum disetujui</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Ruangan & Kategori -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                        <i class="fas fa-door-open mr-2 text-blue-400"></i>Ruangan Tujuan <span class="text-red-500">*</span>
                    </label>
                    <select name="ruangan_id" required class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('ruangan_id') border-red-500 @enderror">
                        <option value="">-- Pilih Ruangan --</option>
                        @foreach($ruanganList as $ruangan)
                            <option value="{{ $ruangan->id }}" {{ old('ruangan_id') == $ruangan->id ? 'selected' : '' }}>{{ $ruangan->nama_ruangan }} - {{ $ruangan->lokasi }}</option>
                        @endforeach
                    </select>
                    @error('ruangan_id')
                        <p class="text-red-400 text-sm mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                        <i class="fas fa-tags mr-2 text-indigo-400"></i>Kategori Aset (Opsional)
                    </label>
                    <select name="kategori_aset_id" class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">-- Pilih Kategori (Admin dapat mengubah) --</option>
                        @foreach($kategoriList as $kategori)
                            <option value="{{ $kategori->id }}" {{ old('kategori_aset_id') == $kategori->id ? 'selected' : '' }}>{{ $kategori->nama_kategori }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Nama Barang & Merk -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                        <i class="fas fa-box mr-2 text-emerald-400"></i>Nama Barang <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="nama_barang" value="{{ old('nama_barang') }}" required placeholder="Contoh: Kursi Kantor Ergonomis" class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('nama_barang') border-red-500 @enderror">
                    @error('nama_barang')
                        <p class="text-red-400 text-sm mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                        <i class="fas fa-trademark mr-2 text-purple-400"></i>Merk/Brand
                    </label>
                    <input type="text" name="merk" value="{{ old('merk') }}" placeholder="Contoh: Chitose, Brother, dll" class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>

            <!-- Spesifikasi -->
            <div>
                <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                    <i class="fas fa-list-alt mr-2 text-cyan-400"></i>Spesifikasi
                </label>
                <textarea name="spesifikasi" rows="3" placeholder="Jelaskan spesifikasi barang: warna, ukuran, material, dll" class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('spesifikasi') }}</textarea>
            </div>

            <!-- Jumlah, Kondisi, Kelengkapan -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                        <i class="fas fa-sort-numeric-up mr-2 text-amber-400"></i>Jumlah <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="jumlah" value="{{ old('jumlah', 1) }}" min="1" required class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('jumlah') border-red-500 @enderror">
                    @error('jumlah')
                        <p class="text-red-400 text-sm mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                        <i class="fas fa-heartbeat mr-2 text-green-400"></i>Kondisi Barang <span class="text-red-500">*</span>
                    </label>
                    <select name="kondisi_barang" required class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('kondisi_barang') border-red-500 @enderror">
                        @foreach(\App\Models\UsulanPenerimaanBarang::getKondisiOptions() as $key => $label)
                            <option value="{{ $key }}" {{ old('kondisi_barang') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('kondisi_barang')
                        <p class="text-red-400 text-sm mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                        <i class="fas fa-check-double mr-2 text-teal-400"></i>Kelengkapan <span class="text-red-500">*</span>
                    </label>
                    <select name="kelengkapan" required class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('kelengkapan') border-red-500 @enderror">
                        @foreach(\App\Models\UsulanPenerimaanBarang::getKelengkapanOptions() as $key => $label)
                            <option value="{{ $key }}" {{ old('kelengkapan') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('kelengkapan')
                        <p class="text-red-400 text-sm mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Estimasi Harga, Sumber, Tanggal Penerimaan -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                        <i class="fas fa-money-bill-wave mr-2 text-green-400"></i>Estimasi Harga (Rp)
                    </label>
                    <input type="number" name="estimasi_harga" value="{{ old('estimasi_harga') }}" min="0" step="1000" placeholder="0" class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                        <i class="fas fa-truck mr-2 text-orange-400"></i>Sumber Barang
                    </label>
                    <select name="sumber_barang" class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">-- Pilih Sumber --</option>
                        @foreach(\App\Models\UsulanPenerimaanBarang::getSumberBarangOptions() as $key => $label)
                            <option value="{{ $key }}" {{ old('sumber_barang') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                        <i class="fas fa-calendar-check mr-2 text-blue-400"></i>Tanggal Penerimaan Fisik
                    </label>
                    <input type="date" name="tanggal_penerimaan_fisik" value="{{ old('tanggal_penerimaan_fisik') }}" class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>

            <!-- Prioritas -->
            <div>
                <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                    <i class="fas fa-flag mr-2 text-red-400"></i>Prioritas <span class="text-red-500">*</span>
                </label>
                <div class="flex flex-wrap gap-4">
                    @foreach(\App\Models\UsulanPenerimaanBarang::getPrioritasOptions() as $key => $label)
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="prioritas" value="{{ $key }}" {{ old('prioritas', 'sedang') == $key ? 'checked' : '' }} required class="w-4 h-4 text-blue-500 border-slate-300 focus:ring-blue-500">
                        <span class="ml-2 text-sm dark:text-slate-300 text-slate-700">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <!-- Foto Barang -->
            <div class="space-y-4">
                <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700">
                    <i class="fas fa-camera mr-2 text-pink-400"></i>Foto Barang <span class="font-normal dark:text-slate-500 text-slate-400">(Opsional)</span>
                </label>

                <div class="dark:bg-slate-700/30 bg-slate-50 p-4 rounded-xl border dark:border-slate-600 border-slate-200">
                    <div class="flex gap-4 mb-4">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="radio" name="foto_tipe" value="upload" x-model="photoMode" class="sr-only peer">
                            <div class="px-4 py-2 text-sm font-medium rounded-lg dark:text-slate-300 text-slate-700 dark:bg-slate-800 bg-white border dark:border-slate-600 border-slate-300 peer-checked:border-emerald-500 peer-checked:text-emerald-500 peer-checked:bg-emerald-50/10 transition-all cursor-pointer">
                                <i class="fas fa-upload mr-2"></i>Upload File (Maks 5)
                            </div>
                        </label>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="radio" name="foto_tipe" value="url" x-model="photoMode" class="sr-only peer">
                            <div class="px-4 py-2 text-sm font-medium rounded-lg dark:text-slate-300 text-slate-700 dark:bg-slate-800 bg-white border dark:border-slate-600 border-slate-300 peer-checked:border-blue-500 peer-checked:text-blue-500 peer-checked:bg-blue-50/10 transition-all cursor-pointer">
                                <i class="fas fa-link mr-2"></i>Pakai Link / Google
                            </div>
                        </label>
                    </div>

                    <!-- Mode Upload File (Multi) -->
                    <div x-show="photoMode === 'upload'" x-transition>
                        <div class="relative">
                            <input type="file" name="foto_barang[]" id="foto_barang" multiple accept="image/jpeg,image/png,image/jpg,image/webp" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                            <div class="border-2 border-dashed dark:border-slate-600 border-slate-300 rounded-xl p-6 text-center dark:hover:border-blue-500 hover:border-blue-400 transition pointer-events-none" id="dropzone">
                                <div class="space-y-2" id="dropzone-content">
                                    <div class="w-16 h-16 mx-auto dark:bg-slate-700 bg-slate-200 rounded-full flex items-center justify-center">
                                        <i class="fas fa-cloud-upload-alt dark:text-slate-400 text-slate-500 text-2xl"></i>
                                    </div>
                                    <p class="dark:text-slate-300 text-slate-700 font-medium">Klik atau seret foto ke sini</p>
                                    <p class="text-sm dark:text-slate-500 text-slate-400">Format: JPEG, PNG, JPG, WEBP (Maks. 5MB per file)</p>
                                </div>
                            </div>
                        </div>
                        <div id="preview-container" class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-4"></div>
                        @error('foto_barang')
                            <p class="text-red-400 text-sm mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                        @enderror
                        @error('foto_barang.*')
                            <p class="text-red-400 text-sm mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
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

                    <!-- Area Preview URL -->
                    <div id="photo-preview-container" class="mt-4 hidden" :class="{'hidden': photoMode === 'url' && !fotoUrl}">
                        <p class="text-xs dark:text-slate-400 text-slate-500 mb-2">Preview Foto:</p>
                        <img id="photo-preview" :src="photoMode === 'url' ? fotoUrl : '#'" alt="Preview" class="h-40 w-auto object-cover rounded-xl border dark:border-slate-600 border-slate-300 bg-slate-100 dark:bg-slate-800">
                    </div>
                </div>
            </div>

            <!-- Keterangan -->
            <div>
                <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                    <i class="fas fa-comment-alt mr-2 text-slate-400"></i>Keterangan Tambahan
                </label>
                <textarea name="keterangan_user" rows="3" placeholder="Tambahkan keterangan atau catatan lainnya..." class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('keterangan_user') }}</textarea>
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-between pt-4 border-t dark:border-slate-700/50 border-slate-200">
                <a href="{{ route('usulan-penerimaan.index') }}" class="px-6 py-3 dark:bg-slate-700 bg-slate-200 dark:hover:bg-slate-600 hover:bg-slate-300 dark:text-white text-slate-700 rounded-xl font-semibold transition">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
                <button type="submit" class="px-8 py-3 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white rounded-xl font-semibold transition shadow-lg shadow-emerald-500/25">
                    <i class="fas fa-paper-plane mr-2"></i>Kirim Usulan
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function usulanForm() {
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

document.addEventListener('DOMContentLoaded', function() {
    const dropzone = document.getElementById('dropzone');
    const dropzoneWrapper = dropzone.parentElement;
    const fileInput = document.getElementById('foto_barang');
    const previewContainer = document.getElementById('preview-container');
    const dropzoneContent = document.getElementById('dropzone-content');
    const form = document.querySelector('form');

    // Drag and drop handlers on wrapper
    dropzoneWrapper.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.add('border-blue-500');
    });

    dropzoneWrapper.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.remove('border-blue-500');
    });

    dropzoneWrapper.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.remove('border-blue-500');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            try {
                const dt = new DataTransfer();
                Array.from(files).slice(0, 5).forEach(function(file) {
                    if (file.type.match(/^image\/(jpeg|png|jpg|webp)$/)) {
                        dt.items.add(file);
                    }
                });
                fileInput.files = dt.files;
                handleFiles(dt.files);
            } catch(err) {
                console.error('Error handling dropped files:', err);
            }
        }
    });

    // File input change handler
    fileInput.addEventListener('change', function(e) {
        handleFiles(this.files);
    });

    function handleFiles(files) {
        previewContainer.innerHTML = '';
        
        if (!files || files.length === 0) {
            dropzone.classList.remove('hidden');
            return;
        }

        dropzone.classList.add('hidden');
        
        Array.from(files).slice(0, 5).forEach(function(file, index) {
            if (!file.type.match(/^image\//)) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'relative group';
                div.innerHTML = 
                    '<img src="' + e.target.result + '" class="w-full h-24 object-cover rounded-lg border dark:border-slate-600 border-slate-300">' +
                    '<div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 rounded-lg transition flex items-center justify-center">' +
                    '<span class="text-white text-xs">Foto ' + (index + 1) + '</span>' +
                    '</div>';
                previewContainer.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }

    // Form submit handler - show loading state
    form.addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mengirim...';
        }
        return true;
    });
});
</script>
@endpush
@endsection
