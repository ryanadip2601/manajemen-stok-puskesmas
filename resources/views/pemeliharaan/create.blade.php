@extends('layouts.app')

@section('title', 'Buat Laporan Pemeliharaan')
@section('header', 'Buat Laporan Pemeliharaan')
@section('breadcrumb', 'Aset / Pemeliharaan / Tambah')

@php
    $isStaff = auth()->user()->isStaff();
    $storeRoute = $isStaff ? route('staff.pemeliharaan.store') : route('pemeliharaan.store');
    $indexRoute = $isStaff ? route('staff.pemeliharaan.index') : route('pemeliharaan.index');
    $getAsetUrl = $isStaff ? url('/staff/pemeliharaan/get-aset') : url('/pemeliharaan/get-aset');
@endphp

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden">
        <div class="p-6 border-b dark:border-slate-700/50 border-slate-200">
            <h3 class="text-lg font-bold dark:text-white text-slate-800 flex items-center">
                <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center mr-3">
                    <i class="fas fa-tools text-purple-400"></i>
                </div>
                Form Laporan Pemeliharaan
            </h3>
        </div>

        <form action="{{ $storeRoute }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                        Ruangan <span class="text-red-500">*</span>
                    </label>
                    <select name="ruangan_id" id="ruangan_id" required
                        class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">Pilih Ruangan</option>
                        @foreach($ruanganList as $r)
                            <option value="{{ $r->id }}" {{ old('ruangan_id', $selectedAset->ruangan_id ?? '') == $r->id ? 'selected' : '' }}>{{ $r->nama_ruangan }}</option>
                        @endforeach
                    </select>
                    @error('ruangan_id')<p class="text-red-400 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                        Barang Aset <span class="text-red-500">*</span>
                    </label>
                    <select name="barang_aset_id" id="barang_aset_id" required
                        class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">Pilih Ruangan Dulu</option>
                        @foreach($asetList as $a)
                            <option value="{{ $a->id }}" {{ old('barang_aset_id', $selectedAset->id ?? '') == $a->id ? 'selected' : '' }}>
                                [{{ $a->kode_aset }}] {{ $a->nama_aset }} - {{ $a->kondisi_label }}
                            </option>
                        @endforeach
                    </select>
                    @error('barang_aset_id')<p class="text-red-400 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                        Tanggal Pemeliharaan <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="tanggal_pemeliharaan" value="{{ old('tanggal_pemeliharaan', date('Y-m-d')) }}" required
                        class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                        Jenis Pemeliharaan <span class="text-red-500">*</span>
                    </label>
                    <select name="jenis_pemeliharaan" required
                        class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        @foreach($jenisOptions as $key => $label)
                            <option value="{{ $key }}" {{ old('jenis_pemeliharaan', 'perbaikan') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                        Nama Teknisi <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="teknisi" value="{{ old('teknisi') }}" required placeholder="Nama teknisi"
                        class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    @error('teknisi')<p class="text-red-400 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">
                    Deskripsi Masalah <span class="text-red-500">*</span>
                </label>
                <textarea name="deskripsi_masalah" rows="4" required placeholder="Jelaskan masalah yang ditemukan..."
                    class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('deskripsi_masalah') }}</textarea>
                @error('deskripsi_masalah')<p class="text-red-400 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Tindakan Perbaikan</label>
                <textarea name="tindakan_perbaikan" rows="3" placeholder="Tindakan yang dilakukan (opsional)..."
                    class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('tindakan_perbaikan') }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Biaya (Rp)</label>
                    <input type="number" name="biaya" value="{{ old('biaya', 0) }}" min="0"
                        class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Lampiran Foto <span class="text-slate-500 font-normal">(Opsional)</span></label>
                    <input type="file" name="lampiran_foto" accept="image/*"
                        class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-2 dark:text-white text-slate-800 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-purple-500/20 file:text-purple-400">
                    <p class="text-xs dark:text-slate-500 text-slate-400 mt-1">Format: JPG, PNG, JPEG (Maks. 2MB)</p>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Catatan</label>
                <textarea name="catatan" rows="2" placeholder="Catatan tambahan (opsional)..."
                    class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('catatan') }}</textarea>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-xl font-semibold hover:from-purple-600 hover:to-indigo-700 transition-all shadow-lg">
                    <i class="fas fa-save mr-2"></i>Simpan Laporan
                </button>
                <a href="{{ $indexRoute }}" class="px-6 py-3 dark:bg-slate-700 bg-slate-200 dark:text-slate-300 text-slate-700 rounded-xl font-semibold hover:bg-slate-600 hover:text-white transition-all">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('ruangan_id').addEventListener('change', function() {
    const ruanganId = this.value;
    const asetSelect = document.getElementById('barang_aset_id');
    
    if (!ruanganId) {
        asetSelect.innerHTML = '<option value="">Pilih Ruangan Dulu</option>';
        return;
    }
    
    asetSelect.innerHTML = '<option value="">Memuat data aset...</option>';
    
    // Use the correct route based on user role
    const baseUrl = @json($getAsetUrl);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    fetch(`${baseUrl}/${ruanganId}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken || ''
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Server error: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        // Check if error response
        if (data.error) {
            asetSelect.innerHTML = '<option value="">Error: ' + data.error + '</option>';
            return;
        }
        
        // Check if array is empty
        if (!Array.isArray(data) || data.length === 0) {
            asetSelect.innerHTML = '<option value="">Tidak ada aset di ruangan ini</option>';
            return;
        }
        
        asetSelect.innerHTML = '<option value="">-- Pilih Barang Aset --</option>';
        data.forEach(item => {
            let kondisiLabel = 'Baik';
            if (item.kondisi === 'rusak_ringan') kondisiLabel = 'Rusak Ringan';
            else if (item.kondisi === 'rusak_berat') kondisiLabel = 'Rusak Berat';
            
            asetSelect.innerHTML += `<option value="${item.id}">[${item.kode_aset}] ${item.nama_aset} - ${kondisiLabel}</option>`;
        });
    })
    .catch(error => {
        console.error('Fetch error:', error);
        asetSelect.innerHTML = '<option value="">Error memuat data</option>';
    });
});
</script>
@endsection
