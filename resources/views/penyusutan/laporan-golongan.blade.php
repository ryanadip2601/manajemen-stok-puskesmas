@extends('layouts.app')

@section('title', 'Laporan Penyusutan Per Golongan')
@section('header', 'Laporan Per Golongan')
@section('breadcrumb', 'Aset / Penyusutan / Laporan Golongan')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('penyusutan.index') }}" class="p-2 dark:bg-slate-700 bg-slate-200 rounded-lg dark:text-slate-300 text-slate-600 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h3 class="text-xl font-bold dark:text-white text-slate-800">Laporan Penyusutan Per Golongan</h3>
                <p class="text-sm dark:text-slate-400 text-slate-600">Rekap nilai aset berdasarkan golongan - Tahun {{ $tahun }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <form method="GET" class="flex items-center gap-2">
                <select name="tahun" onchange="this.form.submit()" class="dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-2 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    @foreach($tahunList as $t)
                        <option value="{{ $t }}" {{ $tahun == $t ? 'selected' : '' }}>Tahun {{ $t }}</option>
                    @endforeach
                </select>
            </form>
            <a href="{{ route('penyusutan.export', ['tahun' => $tahun]) }}" class="px-4 py-2 bg-cyan-500/20 text-cyan-400 rounded-xl font-medium hover:bg-cyan-500/30 transition">
                <i class="fas fa-download mr-2"></i>Export
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="dark:bg-slate-800/50 bg-white rounded-xl p-4 border dark:border-slate-700/50 border-slate-200">
            <p class="text-xs dark:text-slate-400 text-slate-600">Total Aset</p>
            <p class="text-2xl font-bold text-blue-400">{{ number_format($total['jumlah_aset']) }}</p>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-xl p-4 border dark:border-slate-700/50 border-slate-200">
            <p class="text-xs dark:text-slate-400 text-slate-600">Nilai Perolehan</p>
            <p class="text-lg font-bold text-emerald-400">Rp {{ number_format($total['total_nilai_perolehan'], 0, ',', '.') }}</p>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-xl p-4 border dark:border-slate-700/50 border-slate-200">
            <p class="text-xs dark:text-slate-400 text-slate-600">Akumulasi Penyusutan</p>
            <p class="text-lg font-bold text-amber-400">Rp {{ number_format($total['total_akumulasi'], 0, ',', '.') }}</p>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-xl p-4 border dark:border-slate-700/50 border-slate-200">
            <p class="text-xs dark:text-slate-400 text-slate-600">Nilai Buku</p>
            <p class="text-lg font-bold text-purple-400">Rp {{ number_format($total['total_nilai_buku'], 0, ',', '.') }}</p>
        </div>
    </div>

    <!-- Table Per Golongan -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden">
        <div class="p-6 border-b dark:border-slate-700/50 border-slate-200">
            <h4 class="font-semibold dark:text-white text-slate-800">Rekap Per Golongan Aset</h4>
            <p class="text-sm dark:text-slate-400 text-slate-500">Berdasarkan standar akuntansi pemerintah</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="dark:bg-slate-700/30 bg-slate-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Golongan Aset</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Umur Manfaat</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Jumlah Aset</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Nilai Perolehan</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Penyusutan Tahun Ini</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Akumulasi Penyusutan</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Nilai Buku</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50 divide-slate-200">
                    @php $no = 1; @endphp
                    @foreach($data as $golongan => $item)
                    <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-4 dark:text-slate-400 text-slate-600">{{ $no++ }}</td>
                        <td class="px-4 py-4">
                            <p class="dark:text-white text-slate-800 font-medium">{{ $item['nama_golongan'] }}</p>
                            <p class="text-xs dark:text-slate-500 text-slate-400 font-mono">{{ $golongan }}</p>
                        </td>
                        <td class="px-4 py-4 text-center">
                            @if($item['umur_manfaat'] > 0)
                                <span class="px-3 py-1 text-sm bg-blue-500/20 text-blue-400 rounded-lg">{{ $item['umur_manfaat'] }} tahun</span>
                            @else
                                <span class="px-3 py-1 text-sm bg-slate-500/20 text-slate-400 rounded-lg">Tidak disusutkan</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 rounded-lg font-semibold">{{ number_format($item['jumlah_aset']) }}</span>
                        </td>
                        <td class="px-4 py-4 text-right dark:text-slate-300 text-slate-700">Rp {{ number_format($item['total_nilai_perolehan'], 0, ',', '.') }}</td>
                        <td class="px-4 py-4 text-right text-cyan-400">Rp {{ number_format($item['total_penyusutan_tahun'], 0, ',', '.') }}</td>
                        <td class="px-4 py-4 text-right text-amber-400">Rp {{ number_format($item['total_akumulasi'], 0, ',', '.') }}</td>
                        <td class="px-4 py-4 text-right text-emerald-400 font-semibold">Rp {{ number_format($item['total_nilai_buku'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="dark:bg-slate-700/50 bg-slate-100">
                    <tr>
                        <td colspan="3" class="px-4 py-4 font-bold dark:text-white text-slate-800">TOTAL</td>
                        <td class="px-4 py-4 text-center font-bold dark:text-white text-slate-800">{{ number_format($total['jumlah_aset']) }}</td>
                        <td class="px-4 py-4 text-right font-bold dark:text-white text-slate-800">Rp {{ number_format($total['total_nilai_perolehan'], 0, ',', '.') }}</td>
                        <td class="px-4 py-4 text-right font-bold text-cyan-400">Rp {{ number_format($total['total_penyusutan_tahun'], 0, ',', '.') }}</td>
                        <td class="px-4 py-4 text-right font-bold text-amber-400">Rp {{ number_format($total['total_akumulasi'], 0, ',', '.') }}</td>
                        <td class="px-4 py-4 text-right font-bold text-emerald-400">Rp {{ number_format($total['total_nilai_buku'], 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Info Golongan -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6">
        <h4 class="font-semibold dark:text-white text-slate-800 mb-4"><i class="fas fa-info-circle text-blue-400 mr-2"></i>Keterangan Golongan Aset</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="dark:bg-slate-700/30 bg-slate-50 rounded-xl p-4">
                <p class="font-medium dark:text-white text-slate-800">Tanah</p>
                <p class="text-sm dark:text-slate-400 text-slate-600">Tidak disusutkan karena memiliki masa manfaat tidak terbatas</p>
            </div>
            <div class="dark:bg-slate-700/30 bg-slate-50 rounded-xl p-4">
                <p class="font-medium dark:text-white text-slate-800">Peralatan dan Mesin</p>
                <p class="text-sm dark:text-slate-400 text-slate-600">Umur manfaat 5 tahun. Termasuk kendaraan, komputer, dll.</p>
            </div>
            <div class="dark:bg-slate-700/30 bg-slate-50 rounded-xl p-4">
                <p class="font-medium dark:text-white text-slate-800">Gedung dan Bangunan</p>
                <p class="text-sm dark:text-slate-400 text-slate-600">Umur manfaat 50 tahun. Termasuk gedung kantor, rumah dinas, dll.</p>
            </div>
            <div class="dark:bg-slate-700/30 bg-slate-50 rounded-xl p-4">
                <p class="font-medium dark:text-white text-slate-800">Jalan, Irigasi dan Jaringan</p>
                <p class="text-sm dark:text-slate-400 text-slate-600">Umur manfaat 10 tahun. Termasuk jalan, jembatan, instalasi listrik, dll.</p>
            </div>
            <div class="dark:bg-slate-700/30 bg-slate-50 rounded-xl p-4">
                <p class="font-medium dark:text-white text-slate-800">Aset Tetap Lainnya</p>
                <p class="text-sm dark:text-slate-400 text-slate-600">Umur manfaat 4 tahun. Termasuk buku, barang seni, hewan, dll.</p>
            </div>
            <div class="dark:bg-slate-700/30 bg-slate-50 rounded-xl p-4">
                <p class="font-medium dark:text-white text-slate-800">Konstruksi Dalam Pengerjaan</p>
                <p class="text-sm dark:text-slate-400 text-slate-600">Tidak disusutkan sampai selesai dan direklasifikasi</p>
            </div>
        </div>
    </div>
</div>
@endsection
