@extends('layouts.app')

@section('title', 'Edit Ruangan')
@section('header', 'Edit Ruangan')
@section('breadcrumb', 'Aset / Ruangan / Edit')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 overflow-hidden">
        <div class="p-6 border-b dark:border-slate-700/50">
            <h3 class="text-lg font-bold dark:text-white flex items-center">
                <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center mr-3">
                    <i class="fas fa-edit text-blue-400"></i>
                </div>
                Edit Ruangan: {{ $ruangan->nama_ruangan }}
            </h3>
        </div>
        <form action="{{ route('ruangan.update', $ruangan->id) }}" method="POST" class="p-6">
            @csrf @method('PUT')
            <div class="mb-6">
                <label class="block text-sm font-medium dark:text-slate-400 mb-2">Nama Ruangan <span class="text-red-500">*</span></label>
                <input type="text" name="nama_ruangan" value="{{ old('nama_ruangan', $ruangan->nama_ruangan) }}" required
                    class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                @error('nama_ruangan')<p class="text-red-400 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium dark:text-slate-400 mb-2">Lokasi</label>
                <input type="text" name="lokasi" value="{{ old('lokasi', $ruangan->lokasi) }}"
                    class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium dark:text-slate-400 mb-2">Keterangan</label>
                <textarea name="keterangan" rows="3"
                    class="w-full dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-3 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">{{ old('keterangan', $ruangan->keterangan) }}</textarea>
            </div>
            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $ruangan->is_active) ? 'checked' : '' }}
                        class="w-5 h-5 rounded bg-slate-700 border-slate-600 text-emerald-500 focus:ring-emerald-500">
                    <span class="ml-2 dark:text-slate-300">Ruangan Aktif</span>
                </label>
            </div>
            <div class="flex items-center gap-4">
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-indigo-700 transition-all shadow-lg">
                    <i class="fas fa-save mr-2"></i>Update
                </button>
                <a href="{{ route('ruangan.index') }}" class="px-6 py-3 dark:bg-slate-700 bg-slate-200 dark:text-slate-300 rounded-xl font-semibold hover:bg-slate-600 hover:text-white transition-all">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
