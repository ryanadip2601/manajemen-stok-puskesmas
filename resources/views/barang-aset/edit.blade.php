@extends('layouts.app')

@section('title', 'Edit Barang Aset')
@section('header', 'Edit Barang Aset')
@section('breadcrumb', 'Aset / Barang Aset / Edit')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('barang-aset.index') }}" class="p-2 dark:bg-slate-700 bg-slate-200 rounded-lg dark:text-slate-300 text-slate-600 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h3 class="text-xl font-bold dark:text-white text-slate-800">Edit Barang Aset</h3>
            <p class="text-sm dark:text-slate-400 text-slate-600 font-mono">{{ $aset->kode_aset }} - {{ $aset->nama_aset }}</p>
        </div>
    </div>

    <form action="{{ route('barang-aset.update', $aset->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

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
                        <input type="text" name="kode_aset" value="{{ old('kode_aset', $aset->kode_aset) }}" required
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 font-mono">
                        @error('kode_aset')<p class="text-red-400 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">
                            Nama Aset <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="nama_aset" value="{{ old('nama_aset', $aset->nama_aset) }}" required
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
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
                            @foreach($kategoriList as $k)
                                <option value="{{ $k->id }}" 
                                    data-golongan="{{ $k->golongan }}" 
                                    data-umur="{{ $k->umur_manfaat }}"
                                    data-disusutkan="{{ $k->dapat_disusutkan ? '1' : '0' }}"
                                    {{ old('kategori_aset_id', $aset->kategori_aset_id) == $k->id ? 'selected' : '' }}>
                                    {{ $k->nama_kategori }} ({{ $k->umur_manfaat }} th)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">
                            Ruangan <span class="text-red-400">*</span>
                        </label>
                        <select name="ruangan_id" required
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            @foreach($ruanganList as $r)
                                <option value="{{ $r->id }}" {{ old('ruangan_id', $aset->ruangan_id) == $r->id ? 'selected' : '' }}>{{ $r->nama_ruangan }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Kondisi</label>
                        <select name="kondisi" required
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            @foreach($kondisiOptions as $key => $label)
                                <option value="{{ $key }}" {{ old('kondisi', $aset->kondisi) == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Tanggal Perolehan</label>
                        <input type="date" name="tanggal_masuk" value="{{ old('tanggal_masuk', $aset->tanggal_masuk->format('Y-m-d')) }}" required
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Tahun Perolehan</label>
                        <select name="tahun_perolehan" class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            @for($year = date('Y') + 1; $year >= 1980; $year--)
                                <option value="{{ $year }}" {{ old('tahun_perolehan', $aset->tahun_perolehan ?? $aset->tanggal_masuk->year) == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Jumlah</label>
                        <input type="number" name="jumlah" value="{{ old('jumlah', $aset->jumlah) }}" min="1" required
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Merk / Type</label>
                        <input type="text" name="merk" value="{{ old('merk', $aset->merk) }}"
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Nomor Register</label>
                        <input type="text" name="nomor_register" value="{{ old('nomor_register', $aset->nomor_register) }}"
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Sumber Dana</label>
                        <select name="sumber_dana" class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="">Pilih Sumber</option>
                            @foreach(\App\Models\BarangAset::getSumberDanaOptions() as $key => $label)
                                <option value="{{ $key }}" {{ old('sumber_dana', $aset->sumber_dana) == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">No. Sertifikat/No. Pabrik/No. Chasis/No. Mesin</label>
                        <input type="text" name="no_sertifikat" value="{{ old('no_sertifikat', $aset->no_sertifikat) }}" placeholder="Nomor identifikasi"
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Asal/Cara Perolehan Barang</label>
                        <input type="text" name="asal_perolehan" value="{{ old('asal_perolehan', $aset->asal_perolehan) }}" placeholder="Contoh: Pembelian, Hibah, dll"
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Ukuran Barang/Konstruksi</label>
                        <input type="text" name="ukuran_konstruksi" value="{{ old('ukuran_konstruksi', $aset->ukuran_konstruksi) }}" placeholder="Contoh: 100x50x30 cm"
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
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Harga Perolehan (Rp)</label>
                        <input type="text" id="harga_perolehan_display" value="{{ number_format(old('harga_perolehan', $aset->harga_perolehan ?? 0), 2, ',', '.') }}"
                            oninput="formatCurrency(this, 'harga_perolehan')" onblur="formatCurrencyBlur(this, 'harga_perolehan')"
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                            placeholder="0,00">
                        <input type="hidden" name="harga_perolehan" id="harga_perolehan" value="{{ old('harga_perolehan', $aset->harga_perolehan ?? 0) }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Nilai Residu (Rp)</label>
                        <input type="text" id="nilai_residu_display" value="{{ number_format(old('nilai_residu', $aset->nilai_residu ?? 0), 2, ',', '.') }}"
                            oninput="formatCurrency(this, 'nilai_residu')" onblur="formatCurrencyBlur(this, 'nilai_residu')"
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                            placeholder="0,00">
                        <input type="hidden" name="nilai_residu" id="nilai_residu" value="{{ old('nilai_residu', $aset->nilai_residu ?? 0) }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Umur Manfaat Override</label>
                        <input type="number" name="umur_manfaat_override" id="umur_override" value="{{ old('umur_manfaat_override', $aset->umur_manfaat_override) }}" min="0" placeholder="Kosongkan jika ikut kategori" onchange="hitungPenyusutan()"
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>

                <!-- Current Depreciation Info -->
                @php
                    $penyusutan = $aset->hitungPenyusutan();
                @endphp
                <div class="p-4 dark:bg-gradient-to-r dark:from-amber-900/20 dark:to-orange-900/20 bg-gradient-to-r from-amber-50 to-orange-50 rounded-xl border dark:border-amber-500/20 border-amber-200">
                    <h5 class="font-semibold dark:text-amber-400 text-amber-700 mb-3"><i class="fas fa-chart-pie mr-2"></i>Status Penyusutan Saat Ini</h5>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                        <div>
                            <p class="dark:text-slate-400 text-slate-600">Nilai Perolehan</p>
                            <p class="dark:text-white text-slate-800 font-semibold">Rp {{ number_format($penyusutan['nilai_perolehan'], 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="dark:text-slate-400 text-slate-600">Akum. Penyusutan</p>
                            <p class="text-amber-500 font-semibold">Rp {{ number_format($penyusutan['akumulasi_penyusutan'], 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="dark:text-slate-400 text-slate-600">Nilai Buku</p>
                            <p class="text-emerald-500 font-bold">Rp {{ number_format($penyusutan['nilai_buku'], 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="dark:text-slate-400 text-slate-600">Usia Aset</p>
                            <p class="text-blue-400 font-semibold">{{ $penyusutan['jumlah_bulan'] }} bulan</p>
                        </div>
                        <div>
                            <p class="dark:text-slate-400 text-slate-600">Tersusutkan</p>
                            <p class="font-semibold {{ ($penyusutan['sudah_habis'] ?? false) ? 'text-red-400' : 'text-cyan-400' }}">{{ number_format($penyusutan['persentase_penyusutan'], 1) }}%</p>
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
                    <input type="text" name="spesifikasi" value="{{ old('spesifikasi', $aset->spesifikasi) }}"
                        class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Keterangan</label>
                    <textarea name="keterangan" rows="3"
                        class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 resize-none">{{ old('keterangan', $aset->keterangan) }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Foto Baru (Opsional)</label>
                        <input type="file" name="foto" accept="image/*"
                            class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-2 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-emerald-500/20 file:text-emerald-400 file:font-medium">
                    </div>
                    @if($aset->foto)
                    <div>
                        <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Foto Saat Ini</label>
                        <img src="{{ $aset->foto_url }}" alt="Foto aset" class="h-24 rounded-lg object-cover">
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-4">
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-indigo-700 transition-all shadow-lg">
                <i class="fas fa-save mr-2"></i>Update Aset
            </button>
            <a href="{{ route('barang-aset.show', $aset->id) }}" class="px-6 py-3 dark:bg-slate-700 bg-slate-200 dark:text-slate-300 text-slate-700 rounded-xl font-semibold hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                <i class="fas fa-eye mr-2"></i>Lihat Detail
            </a>
            <a href="{{ route('penyusutan.kartu', $aset->id) }}" class="px-6 py-3 bg-amber-500/20 text-amber-400 rounded-xl font-semibold hover:bg-amber-500/30 transition">
                <i class="fas fa-calculator mr-2"></i>Kartu Penyusutan
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
let kategoriUmur = {{ $aset->kategoriAset->umur_manfaat ?? 5 }};
let kategoriDisusutkan = {{ ($aset->kategoriAset->dapat_disusutkan ?? true) ? 'true' : 'false' }};

// Format angka ke format Indonesia (titik sebagai pemisah ribuan, koma sebagai desimal)
function formatNumber(num) {
    return num.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Parse format Indonesia ke angka
function parseNumber(str) {
    if (!str) return 0;
    return parseFloat(str.replace(/\./g, '').replace(',', '.')) || 0;
}

// Format input saat mengetik
function formatCurrency(input, hiddenId) {
    let value = input.value;
    value = value.replace(/[^\d,]/g, '');
    
    let parts = value.split(',');
    let intPart = parts[0].replace(/\D/g, '');
    let decPart = parts.length > 1 ? parts[1].substring(0, 2) : '';
    
    if (intPart) {
        intPart = parseInt(intPart, 10).toLocaleString('id-ID');
    }
    
    if (parts.length > 1) {
        input.value = intPart + ',' + decPart;
    } else {
        input.value = intPart;
    }
    
    let numValue = parseNumber(input.value);
    document.getElementById(hiddenId).value = numValue;
}

// Format lengkap saat blur
function formatCurrencyBlur(input, hiddenId) {
    let value = parseNumber(input.value);
    input.value = formatNumber(value);
    document.getElementById(hiddenId).value = value;
}

function updateKategoriInfo() {
    const select = document.getElementById('kategori_aset_id');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        kategoriUmur = parseInt(option.dataset.umur) || 5;
        kategoriDisusutkan = option.dataset.disusutkan === '1';
    }
}

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
