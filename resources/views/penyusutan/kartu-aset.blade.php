@extends('layouts.app')

@section('title', 'Kartu Penyusutan Aset')
@section('header', 'Kartu Penyusutan Aset')
@section('breadcrumb', 'Aset / Penyusutan / Kartu')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('penyusutan.laporan-detail') }}" class="p-2 dark:bg-slate-700 bg-slate-200 rounded-lg dark:text-slate-300 text-slate-600 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h3 class="text-xl font-bold dark:text-white text-slate-800">Kartu Penyusutan Aset</h3>
            <p class="text-sm dark:text-slate-400 text-slate-600">{{ $aset->kode_aset }} - {{ $aset->nama_aset }}</p>
        </div>
    </div>

    <!-- Info Aset -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Data Aset -->
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6">
            <h4 class="font-semibold dark:text-white text-slate-800 mb-4 flex items-center">
                <i class="fas fa-box text-emerald-400 mr-2"></i>Data Aset
            </h4>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="dark:text-slate-400 text-slate-600">Kode Aset</span>
                    <span class="dark:text-white text-slate-800 font-mono">{{ $aset->kode_aset }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="dark:text-slate-400 text-slate-600">Nama Aset</span>
                    <span class="dark:text-white text-slate-800">{{ $aset->nama_aset }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="dark:text-slate-400 text-slate-600">Kategori</span>
                    <span class="px-2 py-1 text-xs bg-blue-500/20 text-blue-400 rounded-lg">{{ $aset->kategoriAset->nama_kategori ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="dark:text-slate-400 text-slate-600">Golongan</span>
                    <span class="dark:text-white text-slate-800">{{ $aset->kategoriAset->golongan_label ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="dark:text-slate-400 text-slate-600">Ruangan</span>
                    <span class="dark:text-white text-slate-800">{{ $aset->ruangan->nama_ruangan ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="dark:text-slate-400 text-slate-600">Tanggal Perolehan</span>
                    <span class="dark:text-white text-slate-800">{{ $aset->tanggal_masuk->format('d/m/Y') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="dark:text-slate-400 text-slate-600">Merk/Type</span>
                    <span class="dark:text-white text-slate-800">{{ $aset->merk ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="dark:text-slate-400 text-slate-600">Kondisi</span>
                    <span class="px-2 py-1 text-xs rounded-lg {{ \App\Models\BarangAset::getKondisiBadgeClass($aset->kondisi) }}">{{ $aset->kondisi_label }}</span>
                </div>
            </div>
        </div>

        <!-- Data Penyusutan -->
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6">
            <h4 class="font-semibold dark:text-white text-slate-800 mb-4 flex items-center">
                <i class="fas fa-calculator text-amber-400 mr-2"></i>Data Penyusutan
            </h4>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="dark:text-slate-400 text-slate-600">Nilai Perolehan</span>
                    <span class="dark:text-white text-slate-800 font-semibold">Rp {{ number_format($hitungSekarang['nilai_perolehan'], 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="dark:text-slate-400 text-slate-600">Nilai Residu</span>
                    <span class="dark:text-white text-slate-800">Rp {{ number_format($hitungSekarang['nilai_residu'], 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="dark:text-slate-400 text-slate-600">Umur Manfaat</span>
                    <span class="dark:text-white text-slate-800">{{ $hitungSekarang['umur_manfaat'] }} tahun</span>
                </div>
                <div class="flex justify-between">
                    <span class="dark:text-slate-400 text-slate-600">Dapat Disusutkan</span>
                    @if($hitungSekarang['dapat_disusutkan'])
                        <span class="px-2 py-1 text-xs bg-emerald-500/20 text-emerald-400 rounded-lg">Ya</span>
                    @else
                        <span class="px-2 py-1 text-xs bg-slate-500/20 text-slate-400 rounded-lg">Tidak</span>
                    @endif
                </div>
                <hr class="dark:border-slate-700 border-slate-200">
                <div class="flex justify-between">
                    <span class="dark:text-slate-400 text-slate-600">Penyusutan/Tahun</span>
                    <span class="text-amber-400">Rp {{ number_format($hitungSekarang['penyusutan_per_tahun'], 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="dark:text-slate-400 text-slate-600">Penyusutan/Bulan</span>
                    <span class="text-amber-400">Rp {{ number_format($hitungSekarang['penyusutan_per_bulan'], 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="dark:text-slate-400 text-slate-600">Usia Aset</span>
                    <span class="dark:text-white text-slate-800">{{ $hitungSekarang['jumlah_bulan'] }} bulan</span>
                </div>
                <hr class="dark:border-slate-700 border-slate-200">
                <div class="flex justify-between">
                    <span class="dark:text-slate-400 text-slate-600">Akumulasi Penyusutan</span>
                    <span class="text-amber-400 font-semibold">Rp {{ number_format($hitungSekarang['akumulasi_penyusutan'], 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="dark:text-slate-400 text-slate-600">Nilai Buku</span>
                    <span class="text-emerald-400 font-bold text-lg">Rp {{ number_format($hitungSekarang['nilai_buku'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Penyusutan -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6">
        <h4 class="font-semibold dark:text-white text-slate-800 mb-4">Progress Penyusutan</h4>
        <div class="mb-2 flex justify-between text-sm">
            <span class="dark:text-slate-400 text-slate-600">{{ number_format($hitungSekarang['persentase_penyusutan'], 1) }}% tersusutkan</span>
            <span class="dark:text-slate-400 text-slate-600">Sisa {{ $hitungSekarang['sisa_umur_tahun'] ?? 0 }} tahun {{ ($hitungSekarang['sisa_umur_bulan'] ?? 0) % 12 }} bulan</span>
        </div>
        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-4">
            <div class="h-4 rounded-full transition-all {{ ($hitungSekarang['sudah_habis'] ?? false) ? 'bg-red-500' : 'bg-gradient-to-r from-emerald-500 to-teal-500' }}" style="width: {{ min(100, $hitungSekarang['persentase_penyusutan']) }}%"></div>
        </div>
        @if($hitungSekarang['sudah_habis'] ?? false)
            <p class="mt-2 text-red-400 text-sm"><i class="fas fa-exclamation-triangle mr-1"></i>Aset ini sudah habis masa penyusutannya</p>
        @endif
    </div>

    <!-- Riwayat Penyusutan -->
    @if($riwayat->count() > 0)
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden">
        <div class="p-6 border-b dark:border-slate-700/50 border-slate-200">
            <h4 class="font-semibold dark:text-white text-slate-800">Riwayat Penyusutan</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="dark:bg-slate-700/30 bg-slate-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Periode</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Nilai Perolehan</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Penyusutan Periode</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Akum. Awal</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Akum. Akhir</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Nilai Buku</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50 divide-slate-200">
                    @foreach($riwayat as $item)
                    <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3 dark:text-white text-slate-800 font-medium">{{ $item->periode_label }}</td>
                        <td class="px-4 py-3 text-right dark:text-slate-300 text-slate-700">Rp {{ number_format($item->nilai_perolehan, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-amber-400">Rp {{ number_format($item->penyusutan_periode, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right dark:text-slate-400 text-slate-600">Rp {{ number_format($item->akumulasi_penyusutan_awal, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-amber-400">Rp {{ number_format($item->akumulasi_penyusutan_akhir, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-emerald-400 font-semibold">Rp {{ number_format($item->nilai_buku_akhir, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Tombol Aksi -->
    <div class="flex gap-3">
        <a href="{{ route('barang-aset.show', $aset->id) }}" class="px-4 py-2 dark:bg-slate-700 bg-slate-200 dark:text-slate-300 text-slate-700 rounded-xl font-medium hover:bg-slate-300 dark:hover:bg-slate-600 transition">
            <i class="fas fa-eye mr-2"></i>Lihat Detail Aset
        </a>
        <a href="{{ route('barang-aset.edit', $aset->id) }}" class="px-4 py-2 bg-amber-500/20 text-amber-400 rounded-xl font-medium hover:bg-amber-500/30 transition">
            <i class="fas fa-edit mr-2"></i>Edit Aset
        </a>
    </div>
</div>
@endsection
