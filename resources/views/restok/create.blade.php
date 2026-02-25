@extends('layouts.app')

@section('title', 'Ajukan Restok Barang')
@section('header', 'Ajukan Restok Barang')
@section('breadcrumb', 'Ajukan permintaan restok untuk barang yang stoknya habis atau menipis')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl border dark:border-slate-700/50 border-slate-200 shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-orange-500 to-amber-500 px-6 py-4">
            <h2 class="text-xl font-bold text-white flex items-center">
                <i class="fas fa-boxes-stacked mr-3"></i>
                Form Permintaan Restok Barang
            </h2>
            <p class="text-orange-100 text-sm mt-1">Ajukan restok untuk barang yang stoknya habis atau menipis</p>
        </div>

        <!-- Info Box -->
        <div class="px-6 pt-6">
            <div class="dark:bg-amber-500/10 bg-amber-50 border dark:border-amber-500/30 border-amber-200 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <i class="fas fa-info-circle text-amber-400 mt-0.5"></i>
                    <div class="text-sm dark:text-amber-300 text-amber-700">
                        <p class="font-semibold mb-1">Informasi Penting:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Fitur ini untuk meminta restok barang yang <strong>sudah terdaftar</strong> di sistem tapi stoknya habis/menipis</li>
                            <li>Admin akan meninjau permintaan Anda dan melakukan pengadaan barang</li>
                            <li>Anda akan menerima <strong>notifikasi otomatis</strong> ketika barang sudah tersedia</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form -->
        <form action="{{ route('permintaan-restok.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            <!-- Pilih Barang -->
            <div>
                <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                    Pilih Barang <span class="text-red-400">*</span>
                </label>
                <select name="item_id" id="item_id" required
                    class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 text-sm focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    <option value="">-- Pilih Barang --</option>
                    @foreach($items as $item)
                        <option value="{{ $item->id }}" 
                            data-stock="{{ $item->stock }}" 
                            data-min="{{ $item->minimum_stock }}"
                            data-unit="{{ $item->unit->name ?? '' }}"
                            data-category="{{ $item->category->name ?? '' }}"
                            {{ old('item_id') == $item->id ? 'selected' : '' }}>
                            {{ $item->name }} (Stok: {{ $item->stock }} {{ $item->unit->symbol ?? '' }} | Min: {{ $item->minimum_stock }})
                        </option>
                    @endforeach
                </select>
                @error('item_id')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror

                <!-- Item Info Card -->
                <div id="itemInfo" class="hidden mt-3 dark:bg-slate-700/30 bg-slate-50 border dark:border-slate-600/50 border-slate-200 rounded-xl p-4">
                    <div class="grid grid-cols-3 gap-4 text-sm">
                        <div>
                            <p class="dark:text-slate-400 text-slate-500">Kategori</p>
                            <p class="font-semibold dark:text-white text-slate-800" id="infoCategory">-</p>
                        </div>
                        <div>
                            <p class="dark:text-slate-400 text-slate-500">Stok Saat Ini</p>
                            <p class="font-bold text-lg" id="infoStock">-</p>
                        </div>
                        <div>
                            <p class="dark:text-slate-400 text-slate-500">Stok Minimum</p>
                            <p class="font-semibold dark:text-white text-slate-800" id="infoMin">-</p>
                        </div>
                    </div>
                </div>
            </div>

            @if($items->isEmpty())
                <div class="dark:bg-emerald-500/10 bg-emerald-50 border dark:border-emerald-500/30 border-emerald-200 rounded-xl p-4">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-check-circle text-emerald-400"></i>
                        <p class="text-sm dark:text-emerald-300 text-emerald-700 font-semibold">Semua barang memiliki stok yang cukup. Tidak ada barang yang perlu direstok saat ini.</p>
                    </div>
                </div>
            @endif

            <!-- Jumlah Diminta -->
            <div>
                <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                    Jumlah yang Dibutuhkan <span class="text-red-400">*</span>
                </label>
                <input type="number" name="jumlah_diminta" value="{{ old('jumlah_diminta') }}" min="1" max="99999" required
                    class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 text-sm focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                    placeholder="Masukkan jumlah yang dibutuhkan...">
                @error('jumlah_diminta')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Prioritas -->
            <div>
                <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                    Prioritas <span class="text-red-400">*</span>
                </label>
                <div class="grid grid-cols-3 gap-3">
                    <label class="relative cursor-pointer">
                        <input type="radio" name="prioritas" value="rendah" class="peer sr-only" {{ old('prioritas', 'sedang') == 'rendah' ? 'checked' : '' }}>
                        <div class="p-3 rounded-xl border-2 dark:border-slate-600 border-slate-300 peer-checked:border-green-500 peer-checked:bg-green-500/10 transition text-center">
                            <i class="fas fa-arrow-down text-green-400 text-lg mb-1"></i>
                            <p class="text-sm font-semibold dark:text-slate-300 text-slate-700">Rendah</p>
                        </div>
                    </label>
                    <label class="relative cursor-pointer">
                        <input type="radio" name="prioritas" value="sedang" class="peer sr-only" {{ old('prioritas', 'sedang') == 'sedang' ? 'checked' : '' }}>
                        <div class="p-3 rounded-xl border-2 dark:border-slate-600 border-slate-300 peer-checked:border-amber-500 peer-checked:bg-amber-500/10 transition text-center">
                            <i class="fas fa-minus text-amber-400 text-lg mb-1"></i>
                            <p class="text-sm font-semibold dark:text-slate-300 text-slate-700">Sedang</p>
                        </div>
                    </label>
                    <label class="relative cursor-pointer">
                        <input type="radio" name="prioritas" value="tinggi" class="peer sr-only" {{ old('prioritas') == 'tinggi' ? 'checked' : '' }}>
                        <div class="p-3 rounded-xl border-2 dark:border-slate-600 border-slate-300 peer-checked:border-red-500 peer-checked:bg-red-500/10 transition text-center">
                            <i class="fas fa-arrow-up text-red-400 text-lg mb-1"></i>
                            <p class="text-sm font-semibold dark:text-slate-300 text-slate-700">Tinggi</p>
                        </div>
                    </label>
                </div>
                @error('prioritas')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Keterangan -->
            <div>
                <label class="block text-sm font-semibold dark:text-slate-300 text-slate-700 mb-2">
                    Keterangan / Alasan
                </label>
                <textarea name="keterangan" rows="4"
                    class="w-full dark:bg-slate-700/50 bg-slate-100 border dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 text-sm focus:ring-2 focus:ring-orange-500 focus:border-transparent resize-none"
                    placeholder="Jelaskan alasan mengapa barang ini perlu direstok...">{{ old('keterangan') }}</textarea>
                @error('keterangan')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-between pt-4 border-t dark:border-slate-700/50 border-slate-200">
                <a href="{{ route('permintaan-restok.index') }}" class="px-6 py-3 dark:bg-slate-600 bg-slate-300 dark:hover:bg-slate-500 hover:bg-slate-400 dark:text-white text-slate-700 rounded-xl font-semibold transition">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
                @if($items->isNotEmpty())
                <button type="submit" class="px-8 py-3 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white rounded-xl font-semibold transition shadow-lg shadow-orange-500/25">
                    <i class="fas fa-paper-plane mr-2"></i>Ajukan Permintaan
                </button>
                @endif
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('item_id');
    const infoCard = document.getElementById('itemInfo');
    const infoCategory = document.getElementById('infoCategory');
    const infoStock = document.getElementById('infoStock');
    const infoMin = document.getElementById('infoMin');

    select.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        if (this.value) {
            const stock = parseInt(option.dataset.stock);
            infoCategory.textContent = option.dataset.category || '-';
            infoStock.textContent = stock + ' ' + (option.dataset.unit || '');
            infoStock.className = 'font-bold text-lg ' + (stock === 0 ? 'text-red-400' : 'text-amber-400');
            infoMin.textContent = option.dataset.min + ' ' + (option.dataset.unit || '');
            infoCard.classList.remove('hidden');
        } else {
            infoCard.classList.add('hidden');
        }
    });

    // Trigger on load if old value
    if (select.value) {
        select.dispatchEvent(new Event('change'));
    }
});
</script>
@endsection
