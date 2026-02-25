@extends('layouts.app')

@section('title', 'Edit Laporan Pemeliharaan')
@section('header', 'Edit Laporan Pemeliharaan')
@section('breadcrumb', 'Aset / Pemeliharaan / Edit')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 overflow-hidden">
        <div class="p-6 border-b dark:border-slate-700/50">
            <h3 class="text-lg font-bold dark:text-white flex items-center">
                <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center mr-3">
                    <i class="fas fa-edit text-blue-400"></i>
                </div>
                Edit Laporan Pemeliharaan #{{ $pemeliharaan->id }}
            </h3>
        </div>

        <form action="{{ route('pemeliharaan.update', $pemeliharaan->id) }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium dark:text-slate-400 mb-2">Ruangan <span class="text-red-500">*</span></label>
                    <select name="ruangan_id" id="ruangan_id" required class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                        @foreach($ruanganList as $r)
                            <option value="{{ $r->id }}" {{ old('ruangan_id', $pemeliharaan->ruangan_id) == $r->id ? 'selected' : '' }}>{{ $r->nama_ruangan }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium dark:text-slate-400 mb-2">Barang Aset <span class="text-red-500">*</span></label>
                    <select name="barang_aset_id" id="barang_aset_id" required class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                        @foreach($asetList as $a)
                            <option value="{{ $a->id }}" {{ old('barang_aset_id', $pemeliharaan->barang_aset_id) == $a->id ? 'selected' : '' }}>[{{ $a->kode_aset }}] {{ $a->nama_aset }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium dark:text-slate-400 mb-2">Tanggal <span class="text-red-500">*</span></label>
                    <input type="date" name="tanggal_pemeliharaan" value="{{ old('tanggal_pemeliharaan', $pemeliharaan->tanggal_pemeliharaan->format('Y-m-d')) }}" required class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium dark:text-slate-400 mb-2">Jenis <span class="text-red-500">*</span></label>
                    <select name="jenis_pemeliharaan" required class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                        @foreach($jenisOptions as $key => $label)
                            <option value="{{ $key }}" {{ old('jenis_pemeliharaan', $pemeliharaan->jenis_pemeliharaan) == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium dark:text-slate-400 mb-2">Status <span class="text-red-500">*</span></label>
                    <select name="status" required class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                        @foreach($statusOptions as $key => $label)
                            <option value="{{ $key }}" {{ old('status', $pemeliharaan->status) == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium dark:text-slate-400 mb-2">Teknisi <span class="text-red-500">*</span></label>
                    <input type="text" name="teknisi" value="{{ old('teknisi', $pemeliharaan->teknisi) }}" required class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium dark:text-slate-400 mb-2">Deskripsi Masalah <span class="text-red-500">*</span></label>
                <textarea name="deskripsi_masalah" rows="4" required class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('deskripsi_masalah', $pemeliharaan->deskripsi_masalah) }}</textarea>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium dark:text-slate-400 mb-2">Tindakan Perbaikan</label>
                <textarea name="tindakan_perbaikan" rows="3" class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('tindakan_perbaikan', $pemeliharaan->tindakan_perbaikan) }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium dark:text-slate-400 mb-2">Biaya (Rp)</label>
                    <input type="number" name="biaya" value="{{ old('biaya', $pemeliharaan->biaya) }}" min="0" class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium dark:text-slate-400 mb-2">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai', $pemeliharaan->tanggal_selesai?->format('Y-m-d')) }}" class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium dark:text-slate-400 mb-2">Lampiran Foto <span class="text-slate-500 font-normal">(Opsional)</span></label>
                    @if($pemeliharaan->lampiran_foto)
                        <div class="mb-2"><img src="{{ $pemeliharaan->lampiran_foto_url }}" class="w-16 h-16 object-cover rounded"></div>
                    @endif
                    <input type="file" name="lampiran_foto" accept="image/*" class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-2 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-purple-500/20 file:text-purple-400">
                    <p class="text-xs dark:text-slate-500 text-slate-400 mt-1">Format: JPG, PNG, JPEG (Maks. 2MB)</p>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium dark:text-slate-400 mb-2">Catatan</label>
                <textarea name="catatan" rows="2" class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('catatan', $pemeliharaan->catatan) }}</textarea>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-indigo-700 transition-all shadow-lg">
                    <i class="fas fa-save mr-2"></i>Update
                </button>
                <a href="{{ route('pemeliharaan.index') }}" class="px-6 py-3 dark:bg-slate-700 bg-slate-200 dark:text-slate-300 rounded-xl font-semibold hover:bg-slate-600 hover:text-white transition-all">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
