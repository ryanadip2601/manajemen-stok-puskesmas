@extends('layouts.app')

@section('title', 'Tambah Kategori Aset')
@section('header', 'Tambah Kategori Aset')
@section('breadcrumb', 'Aset / Kategori / Tambah')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('kategori-aset.index') }}" class="p-2 dark:bg-slate-700 bg-slate-200 rounded-lg dark:text-slate-300 text-slate-600 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h3 class="text-xl font-bold dark:text-white text-slate-800">Tambah Kategori Aset</h3>
            <p class="text-sm dark:text-slate-400 text-slate-600">Buat kategori baru untuk pengelompokan aset</p>
        </div>
    </div>

    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 overflow-hidden">
        <div class="p-6 border-b dark:border-slate-700/50 border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center">
                    <i class="fas fa-tags text-emerald-400"></i>
                </div>
                <div>
                    <h4 class="font-semibold dark:text-white text-slate-800">Form Kategori Aset</h4>
                    <p class="text-xs dark:text-slate-400 text-slate-500">Lengkapi data di bawah ini</p>
                </div>
            </div>
        </div>

        <form action="{{ route('kategori-aset.store') }}" method="POST" class="p-6 space-y-6">
            @csrf
            
            <!-- Nama Kategori -->
            <div>
                <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">
                    Nama Kategori <span class="text-red-400">*</span>
                </label>
                <input type="text" name="nama_kategori" value="{{ old('nama_kategori') }}" required placeholder="Contoh: Komputer, Meja, Kursi"
                    class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('nama_kategori') ring-2 ring-red-500 @enderror">
                @error('nama_kategori')<p class="text-red-400 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Golongan Aset & Umur Manfaat -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">
                        Golongan Aset <span class="text-red-400">*</span>
                    </label>
                    <select name="golongan" required id="golongan-select" onchange="updateUmurManfaat()"
                        class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="">Pilih Golongan</option>
                        <option value="tanah" data-umur="0" {{ old('golongan') == 'tanah' ? 'selected' : '' }}>Tanah (Tidak Disusutkan)</option>
                        <option value="peralatan_mesin" data-umur="5" {{ old('golongan', 'peralatan_mesin') == 'peralatan_mesin' ? 'selected' : '' }}>Peralatan dan Mesin (5 tahun)</option>
                        <option value="gedung_bangunan" data-umur="50" {{ old('golongan') == 'gedung_bangunan' ? 'selected' : '' }}>Gedung dan Bangunan (50 tahun)</option>
                        <option value="jalan_irigasi" data-umur="10" {{ old('golongan') == 'jalan_irigasi' ? 'selected' : '' }}>Jalan, Irigasi dan Jaringan (10 tahun)</option>
                        <option value="aset_tetap_lainnya" data-umur="4" {{ old('golongan') == 'aset_tetap_lainnya' ? 'selected' : '' }}>Aset Tetap Lainnya (4 tahun)</option>
                        <option value="kdp" data-umur="0" {{ old('golongan') == 'kdp' ? 'selected' : '' }}>Konstruksi Dalam Pengerjaan (Tidak Disusutkan)</option>
                    </select>
                    @error('golongan')<p class="text-red-400 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">
                        Umur Manfaat (Tahun) <span class="text-red-400">*</span>
                    </label>
                    <input type="number" name="umur_manfaat" id="umur-manfaat" value="{{ old('umur_manfaat', 5) }}" min="0" required
                        class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <p class="text-xs dark:text-slate-500 text-slate-400 mt-1">0 = Tidak disusutkan</p>
                    @error('umur_manfaat')<p class="text-red-400 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <!-- Info Penyusutan -->
            <div id="info-penyusutan" class="p-4 dark:bg-slate-700/30 bg-slate-50 rounded-xl">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center">
                        <i class="fas fa-calculator text-amber-400 text-sm"></i>
                    </div>
                    <div>
                        <p class="dark:text-white text-slate-800 font-medium text-sm">Informasi Penyusutan</p>
                        <p id="penyusutan-info-text" class="text-xs dark:text-slate-400 text-slate-500">
                            Aset dalam kategori ini akan disusutkan selama <span id="info-umur">5</span> tahun menggunakan metode garis lurus.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Kode Golongan -->
            <div>
                <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">
                    Kode Golongan <span class="dark:text-slate-500 text-slate-400">(Opsional)</span>
                </label>
                <input type="text" name="kode_golongan" value="{{ old('kode_golongan') }}" placeholder="Contoh: 1.3.2"
                    class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <p class="text-xs dark:text-slate-500 text-slate-400 mt-1">Kode sesuai standar akuntansi pemerintah</p>
            </div>

            <!-- Deskripsi -->
            <div>
                <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">
                    Deskripsi <span class="dark:text-slate-500 text-slate-400">(Opsional)</span>
                </label>
                <textarea name="deskripsi" rows="3" placeholder="Deskripsi kategori..."
                    class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 resize-none">{{ old('deskripsi') }}</textarea>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3 pt-4 border-t dark:border-slate-700/50 border-slate-200">
                <button type="submit" class="flex-1 md:flex-none px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:from-emerald-600 hover:to-teal-700 transition-all shadow-lg">
                    <i class="fas fa-save mr-2"></i>Simpan
                </button>
                <a href="{{ route('kategori-aset.index') }}" class="px-6 py-3 dark:bg-slate-700 bg-slate-200 dark:text-slate-300 text-slate-700 rounded-xl font-semibold hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function updateUmurManfaat() {
    const select = document.getElementById('golongan-select');
    const umurInput = document.getElementById('umur-manfaat');
    const infoUmur = document.getElementById('info-umur');
    const infoText = document.getElementById('penyusutan-info-text');
    
    const option = select.options[select.selectedIndex];
    const umur = option.dataset.umur || 5;
    
    umurInput.value = umur;
    infoUmur.textContent = umur;
    
    if (umur == 0) {
        infoText.innerHTML = '<span class="text-amber-400">Aset dalam kategori ini TIDAK akan disusutkan</span> (seperti Tanah atau Konstruksi Dalam Pengerjaan).';
    } else {
        infoText.innerHTML = 'Aset dalam kategori ini akan disusutkan selama <span class="font-semibold text-emerald-400">' + umur + ' tahun</span> menggunakan metode garis lurus.';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateUmurManfaat();
});
</script>
@endpush
