@extends('layouts.app')

@section('title', 'Tambah Barang Aset')
@section('header', 'Tambah Barang Aset')
@section('breadcrumb', 'Aset / Barang Aset / Tambah')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('barang-aset.index') }}" class="p-2 dark:bg-slate-700 bg-slate-200 rounded-lg dark:text-slate-300 text-slate-600 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h3 class="text-xl font-bold dark:text-white text-slate-800">Tambah Barang Aset</h3>
            <p class="text-sm dark:text-slate-400 text-slate-600">Input data aset baru dengan informasi penyusutan</p>
        </div>
    </div>

    <form action="{{ route('barang-aset.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <!-- Section 1: Informasi Dasar -->
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden mb-6">
            <div class="p-6 border-b dark:border-slate-700/50 border-slate-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/20 flex items-center justify-center">
                        <i class="fas fa-info-circle text-blue-400"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold dark:text-white text-slate-800">Informasi Dasar</h4>
                        <p class="text-xs dark:text-slate-400 text-slate-500">Data identitas aset</p>
                    </div>
                </div>
            </div>

            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">
                            Kode Aset <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="kode_aset" value="{{ old('kode_aset', $kodeAset) }}" required
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 font-mono @error('kode_aset') ring-2 ring-red-500 @enderror">
                        @error('kode_aset')<p class="text-red-400 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">
                            Nama Aset <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="nama_aset" value="{{ old('nama_aset') }}" required placeholder="Nama barang aset"
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('nama_aset') ring-2 ring-red-500 @enderror">
                        @error('nama_aset')<p class="text-red-400 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">
                            Kategori Aset <span class="text-red-400">*</span>
                        </label>
                        <select name="kategori_aset_id" id="kategori_aset_id" required onchange="updateKategoriInfo()"
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="">Pilih Kategori</option>
                            @foreach($kategoriList as $k)
                                <option value="{{ $k->id }}" 
                                    data-golongan="{{ $k->golongan }}" 
                                    data-umur="{{ $k->umur_manfaat }}"
                                    data-disusutkan="{{ $k->dapat_disusutkan ? '1' : '0' }}"
                                    {{ old('kategori_aset_id') == $k->id ? 'selected' : '' }}>
                                    {{ $k->nama_kategori }} ({{ $k->umur_manfaat }} th)
                                </option>
                            @endforeach
                        </select>
                        @error('kategori_aset_id')<p class="text-red-400 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">
                            Ruangan <span class="text-red-400">*</span>
                        </label>
                        <select name="ruangan_id" required
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="">Pilih Ruangan</option>
                            @foreach($ruanganList as $r)
                                <option value="{{ $r->id }}" {{ old('ruangan_id') == $r->id ? 'selected' : '' }}>{{ $r->nama_ruangan }}</option>
                            @endforeach
                        </select>
                        @error('ruangan_id')<p class="text-red-400 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <!-- Info Kategori -->
                <div id="kategori-info" class="p-4 dark:bg-slate-700/30 bg-slate-50 rounded-xl hidden">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center">
                            <i class="fas fa-calculator text-amber-400 text-sm"></i>
                        </div>
                        <div>
                            <p class="dark:text-white text-slate-800 font-medium text-sm">Info Penyusutan Kategori</p>
                            <p id="kategori-info-text" class="text-xs dark:text-slate-400 text-slate-500"></p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">
                            Kondisi <span class="text-red-400">*</span>
                        </label>
                        <select name="kondisi" required
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            @foreach($kondisiOptions as $key => $label)
                                <option value="{{ $key }}" {{ old('kondisi', 'baik') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">
                            Tanggal Perolehan <span class="text-red-400">*</span>
                        </label>
                        <input type="date" name="tanggal_masuk" value="{{ old('tanggal_masuk', date('Y-m-d')) }}" required
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">
                            Tahun Perolehan
                        </label>
                        <select name="tahun_perolehan" class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            @for($year = date('Y') + 1; $year >= 1980; $year--)
                                <option value="{{ $year }}" {{ old('tahun_perolehan', date('Y')) == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">
                            Jumlah <span class="text-red-400">*</span>
                        </label>
                        <input type="number" name="jumlah" value="{{ old('jumlah', 1) }}" min="1" required
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Merk / Type</label>
                        <input type="text" name="merk" value="{{ old('merk') }}" placeholder="Merk/Brand/Type"
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Nomor Register</label>
                        <input type="text" name="nomor_register" value="{{ old('nomor_register') }}" placeholder="Nomor inventaris"
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Sumber Dana</label>
                        <select name="sumber_dana" class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="">Pilih Sumber</option>
                            @foreach(\App\Models\BarangAset::getSumberDanaOptions() as $key => $label)
                                <option value="{{ $key }}" {{ old('sumber_dana') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">No. Sertifikat/No. Pabrik/No. Chasis/No. Mesin</label>
                        <input type="text" name="no_sertifikat" value="{{ old('no_sertifikat') }}" placeholder="Nomor identifikasi"
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Asal/Cara Perolehan Barang</label>
                        <input type="text" name="asal_perolehan" value="{{ old('asal_perolehan') }}" placeholder="Contoh: Pembelian, Hibah, dll"
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Ukuran Barang/Konstruksi</label>
                        <input type="text" name="ukuran_konstruksi" value="{{ old('ukuran_konstruksi') }}" placeholder="Contoh: 100x50x30 cm"
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Nilai & Penyusutan -->
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden mb-6">
            <div class="p-6 border-b dark:border-slate-700/50 border-slate-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center">
                        <i class="fas fa-calculator text-emerald-400"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold dark:text-white text-slate-800">Nilai Perolehan & Penyusutan</h4>
                        <p class="text-xs dark:text-slate-400 text-slate-500">Data nilai untuk perhitungan penyusutan</p>
                    </div>
                </div>
            </div>

            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">
                            Harga Perolehan (Rp) <span class="text-red-400">*</span>
                        </label>
                        <input type="text" id="harga_perolehan_display" value="{{ old('harga_perolehan') ? number_format(old('harga_perolehan'), 2, ',', '.') : '0,00' }}" required
                            oninput="formatCurrency(this, 'harga_perolehan')" onblur="formatCurrencyBlur(this, 'harga_perolehan')"
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                            placeholder="0,00">
                        <input type="hidden" name="harga_perolehan" id="harga_perolehan" value="{{ old('harga_perolehan', 0) }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">
                            Nilai Residu (Rp)
                        </label>
                        <input type="text" id="nilai_residu_display" value="{{ old('nilai_residu') ? number_format(old('nilai_residu'), 2, ',', '.') : '0,00' }}"
                            oninput="formatCurrency(this, 'nilai_residu')" onblur="formatCurrencyBlur(this, 'nilai_residu')"
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                            placeholder="0,00">
                        <input type="hidden" name="nilai_residu" id="nilai_residu" value="{{ old('nilai_residu', 0) }}">
                        <p class="text-xs dark:text-slate-500 text-slate-400 mt-1">Nilai sisa di akhir umur manfaat (biasanya 0)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">
                            Umur Manfaat Override (Tahun)
                        </label>
                        <input type="number" name="umur_manfaat_override" id="umur_override" value="{{ old('umur_manfaat_override') }}" min="0" placeholder="Kosongkan jika ikut kategori" onchange="hitungPenyusutan()"
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <p class="text-xs dark:text-slate-500 text-slate-400 mt-1">Isi jika umur manfaat berbeda dari kategori</p>
                    </div>
                </div>

                <!-- Preview Penyusutan -->
                <div id="preview-penyusutan" class="p-4 dark:bg-gradient-to-r dark:from-emerald-900/20 dark:to-teal-900/20 bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl border dark:border-emerald-500/20 border-emerald-200">
                    <h5 class="font-semibold dark:text-emerald-400 text-emerald-700 mb-3"><i class="fas fa-chart-line mr-2"></i>Preview Perhitungan Penyusutan</h5>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="dark:text-slate-400 text-slate-600">Nilai Disusutkan</p>
                            <p class="dark:text-white text-slate-800 font-semibold" id="preview-nilai-disusutkan">Rp 0</p>
                        </div>
                        <div>
                            <p class="dark:text-slate-400 text-slate-600">Penyusutan/Tahun</p>
                            <p class="text-amber-500 font-semibold" id="preview-per-tahun">Rp 0</p>
                        </div>
                        <div>
                            <p class="dark:text-slate-400 text-slate-600">Penyusutan/Bulan</p>
                            <p class="text-amber-500 font-semibold" id="preview-per-bulan">Rp 0</p>
                        </div>
                        <div>
                            <p class="dark:text-slate-400 text-slate-600">Umur Manfaat</p>
                            <p class="text-blue-400 font-semibold" id="preview-umur">- tahun</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Detail & Foto -->
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden mb-6">
            <div class="p-6 border-b dark:border-slate-700/50 border-slate-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-500/20 flex items-center justify-center">
                        <i class="fas fa-file-alt text-purple-400"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold dark:text-white text-slate-800">Detail & Dokumentasi</h4>
                        <p class="text-xs dark:text-slate-400 text-slate-500">Informasi tambahan dan foto aset</p>
                    </div>
                </div>
            </div>

            <div class="p-6 space-y-6">
                <div>
                    <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Spesifikasi</label>
                    <input type="text" name="spesifikasi" value="{{ old('spesifikasi') }}" placeholder="Spesifikasi teknis aset"
                        class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Keterangan</label>
                    <textarea name="keterangan" rows="3" placeholder="Keterangan tambahan..."
                        class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 resize-none">{{ old('keterangan') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Foto Aset</label>
                    <input type="file" name="foto" accept="image/*"
                        class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-2 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-emerald-500/20 file:text-emerald-400 file:font-medium">
                    <p class="text-xs dark:text-slate-500 text-slate-400 mt-1">Format: JPG, PNG. Maksimal 2MB</p>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-4">
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:from-emerald-600 hover:to-teal-700 transition-all shadow-lg">
                <i class="fas fa-save mr-2"></i>Simpan Aset
            </button>
            <a href="{{ route('barang-aset.index') }}" class="px-6 py-3 dark:bg-slate-700 bg-slate-200 dark:text-slate-300 text-slate-700 rounded-xl font-semibold hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                <i class="fas fa-times mr-2"></i>Batal
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
let kategoriUmur = 5;
let kategoriDisusutkan = true;

// Format angka ke format Indonesia (titik sebagai pemisah ribuan, koma sebagai desimal)
function formatNumber(num) {
    return num.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Parse format Indonesia ke angka
function parseNumber(str) {
    if (!str) return 0;
    // Hapus titik (ribuan), ganti koma dengan titik (desimal)
    return parseFloat(str.replace(/\./g, '').replace(',', '.')) || 0;
}

// Format input saat mengetik
function formatCurrency(input, hiddenId) {
    let value = input.value;
    
    // Hapus semua karakter selain angka dan koma
    value = value.replace(/[^\d,]/g, '');
    
    // Ambil bagian sebelum koma dan sesudah koma
    let parts = value.split(',');
    let intPart = parts[0].replace(/\D/g, '');
    let decPart = parts.length > 1 ? parts[1].substring(0, 2) : '';
    
    // Format ribuan dengan titik
    if (intPart) {
        intPart = parseInt(intPart, 10).toLocaleString('id-ID');
    }
    
    // Gabungkan kembali
    if (parts.length > 1) {
        input.value = intPart + ',' + decPart;
    } else {
        input.value = intPart;
    }
    
    // Update hidden field dengan nilai numerik
    let numValue = parseNumber(input.value);
    document.getElementById(hiddenId).value = numValue;
    
    // Trigger perhitungan penyusutan
    hitungPenyusutan();
}

// Format lengkap saat blur (keluar dari field)
function formatCurrencyBlur(input, hiddenId) {
    let value = parseNumber(input.value);
    input.value = formatNumber(value);
    document.getElementById(hiddenId).value = value;
    hitungPenyusutan();
}

function updateKategoriInfo() {
    const select = document.getElementById('kategori_aset_id');
    const infoDiv = document.getElementById('kategori-info');
    const infoText = document.getElementById('kategori-info-text');
    
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        kategoriUmur = parseInt(option.dataset.umur) || 5;
        kategoriDisusutkan = option.dataset.disusutkan === '1';
        const golongan = option.dataset.golongan;
        
        if (!kategoriDisusutkan || kategoriUmur === 0) {
            infoText.innerHTML = `<span class="text-amber-400">Kategori ini TIDAK DISUSUTKAN</span> (Golongan: ${golongan})`;
        } else {
            infoText.innerHTML = `Umur manfaat: <span class="font-semibold text-emerald-400">${kategoriUmur} tahun</span> | Golongan: ${golongan}`;
        }
        infoDiv.classList.remove('hidden');
        hitungPenyusutan();
    } else {
        infoDiv.classList.add('hidden');
    }
}

function hitungPenyusutan() {
    const hargaPerolehan = parseFloat(document.getElementById('harga_perolehan').value) || 0;
    const nilaiResidu = parseFloat(document.getElementById('nilai_residu').value) || 0;
    const umurOverride = document.getElementById('umur_override').value;
    
    const umur = umurOverride ? parseInt(umurOverride) : kategoriUmur;
    const nilaiDisusutkan = hargaPerolehan - nilaiResidu;
    
    let penyusutanPerTahun = 0;
    let penyusutanPerBulan = 0;
    
    if (umur > 0 && kategoriDisusutkan) {
        penyusutanPerTahun = nilaiDisusutkan / umur;
        penyusutanPerBulan = penyusutanPerTahun / 12;
    }
    
    document.getElementById('preview-nilai-disusutkan').textContent = 'Rp ' + formatNumber(nilaiDisusutkan);
    document.getElementById('preview-per-tahun').textContent = 'Rp ' + formatNumber(penyusutanPerTahun);
    document.getElementById('preview-per-bulan').textContent = 'Rp ' + formatNumber(penyusutanPerBulan);
    document.getElementById('preview-umur').textContent = umur > 0 ? umur + ' tahun' : 'Tidak disusutkan';
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    updateKategoriInfo();
    
    // Format initial values
    const hargaDisplay = document.getElementById('harga_perolehan_display');
    const residuDisplay = document.getElementById('nilai_residu_display');
    
    if (hargaDisplay && hargaDisplay.value) {
        formatCurrencyBlur(hargaDisplay, 'harga_perolehan');
    }
    if (residuDisplay && residuDisplay.value) {
        formatCurrencyBlur(residuDisplay, 'nilai_residu');
    }
});
</script>
@endpush
