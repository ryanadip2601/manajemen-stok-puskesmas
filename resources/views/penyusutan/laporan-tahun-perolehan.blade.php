@extends('layouts.app')

@section('title', 'Laporan Per Tahun Perolehan')
@section('header', 'Laporan Per Tahun Perolehan')
@section('breadcrumb', 'Aset / Penyusutan / Per Tahun Perolehan')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('penyusutan.index') }}" class="p-2 dark:bg-slate-700 bg-slate-200 rounded-lg dark:text-slate-300 text-slate-600 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h3 class="text-xl font-bold dark:text-white text-slate-800">Laporan Per Tahun Perolehan</h3>
                <p class="text-sm dark:text-slate-400 text-slate-600">Rekap nilai aset berdasarkan tahun perolehan - Per {{ $tahun }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <form method="GET" class="flex items-center gap-2">
                <select name="tahun" onchange="this.form.submit()" class="dark:bg-slate-700/50 bg-slate-100 rounded-xl px-4 py-2 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    @foreach($tahunList as $t)
                        <option value="{{ $t }}" {{ $tahun == $t ? 'selected' : '' }}>Per Tahun {{ $t }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <!-- Table Per Tahun Perolehan -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden">
        <div class="p-6 border-b dark:border-slate-700/50 border-slate-200">
            <h4 class="font-semibold dark:text-white text-slate-800">Rekap Aset Berdasarkan Tahun Perolehan</h4>
            <p class="text-sm dark:text-slate-400 text-slate-500">Menampilkan nilai penyusutan per tahun perolehan</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="dark:bg-slate-700/30 bg-slate-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Tahun Perolehan</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Jumlah Aset</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Nilai Perolehan</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Penyusutan Tahun Ini</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Akumulasi Penyusutan</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Nilai Buku</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Usia</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50 divide-slate-200">
                    @php 
                        $totalAset = 0;
                        $totalPerolehan = 0;
                        $totalPenyusutan = 0;
                        $totalAkumulasi = 0;
                        $totalNilaiBuku = 0;
                    @endphp
                    @forelse($data as $item)
                    @php
                        $totalAset += $item['jumlah_aset'];
                        $totalPerolehan += $item['total_nilai_perolehan'];
                        $totalPenyusutan += $item['total_penyusutan'];
                        $totalAkumulasi += $item['total_akumulasi'];
                        $totalNilaiBuku += $item['total_nilai_buku'];
                        $usia = $tahun - ($item['tahun_perolehan'] ?? $tahun);
                    @endphp
                    <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-4">
                            <span class="dark:text-white text-slate-800 font-semibold text-lg">{{ $item['tahun_perolehan'] ?? 'Tidak Diketahui' }}</span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="px-3 py-1 bg-blue-500/20 text-blue-400 rounded-lg font-semibold">{{ number_format($item['jumlah_aset']) }}</span>
                        </td>
                        <td class="px-4 py-4 text-right dark:text-slate-300 text-slate-700">Rp {{ number_format($item['total_nilai_perolehan'], 0, ',', '.') }}</td>
                        <td class="px-4 py-4 text-right text-cyan-400">Rp {{ number_format($item['total_penyusutan'], 0, ',', '.') }}</td>
                        <td class="px-4 py-4 text-right text-amber-400">Rp {{ number_format($item['total_akumulasi'], 0, ',', '.') }}</td>
                        <td class="px-4 py-4 text-right text-emerald-400 font-semibold">Rp {{ number_format($item['total_nilai_buku'], 0, ',', '.') }}</td>
                        <td class="px-4 py-4 text-center">
                            <span class="px-2 py-1 text-xs {{ $usia > 5 ? 'bg-amber-500/20 text-amber-400' : 'bg-emerald-500/20 text-emerald-400' }} rounded-lg">{{ $usia }} tahun</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center dark:text-slate-500 text-slate-400">
                            <i class="fas fa-calendar-times text-4xl mb-3 block"></i>
                            Belum ada data penyusutan untuk tahun ini
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($data) > 0)
                <tfoot class="dark:bg-slate-700/50 bg-slate-100">
                    <tr>
                        <td class="px-4 py-4 font-bold dark:text-white text-slate-800">TOTAL</td>
                        <td class="px-4 py-4 text-center font-bold dark:text-white text-slate-800">{{ number_format($totalAset) }}</td>
                        <td class="px-4 py-4 text-right font-bold dark:text-white text-slate-800">Rp {{ number_format($totalPerolehan, 0, ',', '.') }}</td>
                        <td class="px-4 py-4 text-right font-bold text-cyan-400">Rp {{ number_format($totalPenyusutan, 0, ',', '.') }}</td>
                        <td class="px-4 py-4 text-right font-bold text-amber-400">Rp {{ number_format($totalAkumulasi, 0, ',', '.') }}</td>
                        <td class="px-4 py-4 text-right font-bold text-emerald-400">Rp {{ number_format($totalNilaiBuku, 0, ',', '.') }}</td>
                        <td class="px-4 py-4"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    <!-- Info -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6">
        <h4 class="font-semibold dark:text-white text-slate-800 mb-4"><i class="fas fa-lightbulb text-amber-400 mr-2"></i>Catatan</h4>
        <ul class="space-y-2 text-sm dark:text-slate-400 text-slate-600">
            <li><i class="fas fa-check text-emerald-400 mr-2"></i>Aset dengan usia > 5 tahun ditandai dengan warna kuning</li>
            <li><i class="fas fa-check text-emerald-400 mr-2"></i>Nilai buku adalah selisih antara nilai perolehan dan akumulasi penyusutan</li>
            <li><i class="fas fa-check text-emerald-400 mr-2"></i>Aset yang sudah habis umur manfaatnya akan memiliki nilai buku mendekati nilai residu</li>
        </ul>
    </div>
</div>
@endsection
