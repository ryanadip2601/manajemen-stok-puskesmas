@extends('layouts.app')

@section('title', 'Tambah Ruangan')
@section('header', 'Tambah Ruangan')
@section('breadcrumb', 'Aset / Ruangan / Tambah')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 overflow-hidden">
        <div class="p-6 border-b dark:border-slate-700/50">
            <h3 class="text-lg font-bold dark:text-white flex items-center">
                <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center mr-3">
                    <i class="fas fa-door-open text-emerald-400"></i>
                </div>
                Form Tambah Ruangan
            </h3>
        </div>
        <form action="{{ route('ruangan.store') }}" method="POST" class="p-6">
            @csrf
            <div class="mb-6">
                <label class="block text-sm font-medium dark:text-slate-400 mb-2">Nama Ruangan <span class="text-red-500">*</span></label>
                <input type="text" name="nama_ruangan" value="{{ old('nama_ruangan') }}" required placeholder="Contoh: Ruang Server"
                    class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('nama_ruangan') border-red-500 @enderror">
                @error('nama_ruangan')<p class="text-red-400 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium dark:text-slate-400 mb-2">Lokasi</label>
                <input type="text" name="lokasi" value="{{ old('lokasi') }}" placeholder="Contoh: Lantai 2, Gedung A"
                    class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium dark:text-slate-400 mb-2">Keterangan</label>
                <textarea name="keterangan" rows="3" placeholder="Keterangan tambahan..."
                    class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">{{ old('keterangan') }}</textarea>
            </div>
            <div class="flex items-center gap-4">
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:from-emerald-600 hover:to-teal-700 transition-all shadow-lg">
                    <i class="fas fa-save mr-2"></i>Simpan
                </button>
                <a href="{{ route('ruangan.index') }}" class="px-6 py-3 dark:bg-slate-700 bg-slate-200 dark:text-slate-300 rounded-xl font-semibold hover:bg-slate-600 hover:text-white transition-all">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
