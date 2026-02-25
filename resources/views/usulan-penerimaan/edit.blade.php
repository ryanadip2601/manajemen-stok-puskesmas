@extends('layouts.app')

@section('title', 'Edit Usulan Penerimaan')
@section('header', 'Edit Usulan Penerimaan')
@section('breadcrumb', 'Perbarui data usulan penerimaan barang')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 bg-gradient-to-r from-amber-500/20 to-orange-500/20 border-b dark:border-slate-700/50 border-slate-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-500 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                        <i class="fas fa-edit text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold dark:text-white text-slate-800">Edit Usulan: {{ $usulan->kode_usulan }}</h3>
                        <p class="text-sm dark:text-slate-400 text-slate-500">Perbarui data usulan penerimaan barang</p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-semibold border {{ \App\Models\UsulanPenerimaanBarang::getStatusBadgeClass($usulan->status) }}">
                    {{ $usulan->status_label }}
                </span>
            </div>
        </div>

        @if($usulan->is_revisi && $usulan->permintaan_revisi)
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

        <form action="{{ route('usulan-penerimaan.update', $usulan->id) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Ruangan & Kategori -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                        <i class="fas fa-door-open mr-2 text-blue-400"></i>Ruangan Tujuan <span class="text-red-500">*</span>
                    </label>
                    <select name="ruangan_id" required class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500">
                        @foreach($ruanganList as $ruangan)
                            <option value="{{ $ruangan->id }}" {{ old('ruangan_id', $usulan->ruangan_id) == $ruangan->id ? 'selected' : '' }}>{{ $ruangan->nama_ruangan }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                        <i class="fas fa-tags mr-2 text-indigo-400"></i>Kategori Aset
                    </label>
                    <select name="kategori_aset_id" class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($kategoriList as $kategori)
                            <option value="{{ $kategori->id }}" {{ old('kategori_aset_id', $usulan->kategori_aset_id) == $kategori->id ? 'selected' : '' }}>{{ $kategori->nama_kategori }}</option>
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
                    <input type="text" name="nama_barang" value="{{ old('nama_barang', $usulan->nama_barang) }}" required class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                        <i class="fas fa-trademark mr-2 text-purple-400"></i>Merk/Brand
                    </label>
                    <input type="text" name="merk" value="{{ old('merk', $usulan->merk) }}" class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <!-- Spesifikasi -->
            <div>
                <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                    <i class="fas fa-list-alt mr-2 text-cyan-400"></i>Spesifikasi
                </label>
                <textarea name="spesifikasi" rows="3" class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500">{{ old('spesifikasi', $usulan->spesifikasi) }}</textarea>
            </div>

            <!-- Jumlah, Kondisi, Kelengkapan -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                        <i class="fas fa-sort-numeric-up mr-2 text-amber-400"></i>Jumlah <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="jumlah" value="{{ old('jumlah', $usulan->jumlah) }}" min="1" required class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                        <i class="fas fa-heartbeat mr-2 text-green-400"></i>Kondisi Barang <span class="text-red-500">*</span>
                    </label>
                    <select name="kondisi_barang" required class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500">
                        @foreach(\App\Models\UsulanPenerimaanBarang::getKondisiOptions() as $key => $label)
                            <option value="{{ $key }}" {{ old('kondisi_barang', $usulan->kondisi_barang) == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                        <i class="fas fa-check-double mr-2 text-teal-400"></i>Kelengkapan <span class="text-red-500">*</span>
                    </label>
                    <select name="kelengkapan" required class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500">
                        @foreach(\App\Models\UsulanPenerimaanBarang::getKelengkapanOptions() as $key => $label)
                            <option value="{{ $key }}" {{ old('kelengkapan', $usulan->kelengkapan) == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Estimasi Harga, Sumber, Tanggal -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                        <i class="fas fa-money-bill-wave mr-2 text-green-400"></i>Estimasi Harga (Rp)
                    </label>
                    <input type="number" name="estimasi_harga" value="{{ old('estimasi_harga', $usulan->estimasi_harga) }}" min="0" class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                        <i class="fas fa-truck mr-2 text-orange-400"></i>Sumber Barang
                    </label>
                    <select name="sumber_barang" class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Pilih Sumber --</option>
                        @foreach(\App\Models\UsulanPenerimaanBarang::getSumberBarangOptions() as $key => $label)
                            <option value="{{ $key }}" {{ old('sumber_barang', $usulan->sumber_barang) == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                        <i class="fas fa-calendar-check mr-2 text-blue-400"></i>Tanggal Penerimaan
                    </label>
                    <input type="date" name="tanggal_penerimaan_fisik" value="{{ old('tanggal_penerimaan_fisik', $usulan->tanggal_penerimaan_fisik?->format('Y-m-d')) }}" class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500">
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
                        <input type="radio" name="prioritas" value="{{ $key }}" {{ old('prioritas', $usulan->prioritas) == $key ? 'checked' : '' }} required class="w-4 h-4 text-blue-500">
                        <span class="ml-2 text-sm dark:text-slate-300 text-slate-700">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <!-- Foto Existing -->
            @if($usulan->foto_barang && count($usulan->foto_barang) > 0)
            <div>
                <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                    <i class="fas fa-images mr-2 text-pink-400"></i>Foto Saat Ini
                </label>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    @foreach($usulan->foto_barang as $index => $foto)
                    <div class="relative group">
                        <img src="{{ str_starts_with($foto, 'http') ? $foto : asset($foto) }}" class="w-full h-24 object-cover rounded-lg border dark:border-slate-600 border-slate-300">
                        <label class="absolute bottom-2 left-2 flex items-center">
                            <input type="checkbox" name="hapus_foto[]" value="{{ $foto }}" class="w-4 h-4 text-red-500 rounded">
                            <span class="ml-1 text-xs text-white bg-red-500/80 px-2 py-0.5 rounded">Hapus</span>
                        </label>
                    </div>
                    @endforeach
                </div>
                <p class="text-sm dark:text-slate-500 text-slate-400 mt-2">Centang untuk menghapus foto</p>
            </div>
            @endif

            <!-- Tambah Foto Baru -->
            <div class="space-y-4">
                <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700">
                    <i class="fas fa-camera mr-2 text-pink-400"></i>Tambah Foto Baru <span class="font-normal dark:text-slate-500 text-slate-400">(Opsional)</span>
                </label>

                <div class="dark:bg-slate-700/30 bg-slate-50 p-4 rounded-xl border dark:border-slate-600 border-slate-200">
                    <div class="flex gap-4 mb-4">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="radio" name="foto_tipe" value="upload" x-model="photoMode" class="sr-only peer">
                            <div class="px-4 py-2 text-sm font-medium rounded-lg dark:text-slate-300 text-slate-700 dark:bg-slate-800 bg-white border dark:border-slate-600 border-slate-300 peer-checked:border-emerald-500 peer-checked:text-emerald-500 peer-checked:bg-emerald-50/10 transition-all cursor-pointer">
                                <i class="fas fa-upload mr-2"></i>Upload File Baru (Maks 5)
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
                        <input type="file" name="foto_barang_baru[]" multiple accept="image/jpeg,image/png,image/jpg,image/webp" 
                            class="w-full text-sm dark:text-slate-300 text-slate-700 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-emerald-500/20 file:text-emerald-400 hover:file:bg-emerald-500/30 transition-all dark:bg-slate-800 bg-white border dark:border-slate-600 border-slate-300 rounded-xl">
                        <p class="text-xs dark:text-slate-500 text-slate-400 mt-2">Format: JPG, PNG, WEBP. Maks 5MB per file.</p>
                        @error('foto_barang_baru.*')
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
                </div>
            </div>

            <!-- Keterangan -->
            <div>
                <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                    <i class="fas fa-comment-alt mr-2 text-slate-400"></i>Keterangan
                </label>
                <textarea name="keterangan_user" rows="3" class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:ring-2 focus:ring-blue-500">{{ old('keterangan_user', $usulan->keterangan_user) }}</textarea>
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-between pt-4 border-t dark:border-slate-700/50 border-slate-200">
                <a href="{{ route('usulan-penerimaan.index') }}" class="px-6 py-3 dark:bg-slate-700 bg-slate-200 dark:hover:bg-slate-600 hover:bg-slate-300 dark:text-white text-slate-700 rounded-xl font-semibold transition">
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
</script>
@endpush
@endsection
