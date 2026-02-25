@extends('layouts.app')

@section('title', 'Penyusutan Aset')
@section('header', 'Penyusutan Aset')
@section('breadcrumb', 'Aset / Penyusutan')

@section('content')
<div class="space-y-6">
    <!-- Header & Filter Tahun -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold dark:text-white text-slate-800">Dashboard Penyusutan Aset</h3>
            <p class="text-sm dark:text-slate-400 text-slate-600">Perhitungan penyusutan menggunakan metode Garis Lurus</p>
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

    <!-- Summary Cards - Real Time -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="dark:bg-slate-800/50 bg-white rounded-xl p-4 border dark:border-slate-700/50 border-slate-200">
            <p class="text-xs dark:text-slate-400 text-slate-600">Total Aset Aktif</p>
            <p class="text-2xl font-bold text-blue-400">{{ number_format($realtimeSummary['total_aset']) }}</p>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-xl p-4 border dark:border-slate-700/50 border-slate-200">
            <p class="text-xs dark:text-slate-400 text-slate-600">Nilai Perolehan</p>
            <p class="text-lg font-bold text-emerald-400">Rp {{ number_format($realtimeSummary['total_nilai_perolehan'], 0, ',', '.') }}</p>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-xl p-4 border dark:border-slate-700/50 border-slate-200">
            <p class="text-xs dark:text-slate-400 text-slate-600">Akumulasi Penyusutan</p>
            <p class="text-lg font-bold text-amber-400">Rp {{ number_format($realtimeSummary['total_akumulasi'], 0, ',', '.') }}</p>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-xl p-4 border dark:border-slate-700/50 border-slate-200">
            <p class="text-xs dark:text-slate-400 text-slate-600">Nilai Buku</p>
            <p class="text-lg font-bold text-purple-400">Rp {{ number_format($realtimeSummary['total_nilai_buku'], 0, ',', '.') }}</p>
        </div>
        <div class="dark:bg-slate-800/50 bg-white rounded-xl p-4 border dark:border-slate-700/50 border-slate-200">
            <p class="text-xs dark:text-slate-400 text-slate-600">Aset Habis Umur</p>
            <p class="text-2xl font-bold text-red-400">{{ number_format($realtimeSummary['aset_habis_umur']) }}</p>
        </div>
    </div>

    <!-- Proses Penyusutan Tahunan -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h4 class="font-semibold dark:text-white text-slate-800">Proses Penyusutan Tahunan</h4>
                <p class="text-sm dark:text-slate-400 text-slate-600">Simpan perhitungan penyusutan untuk tahun {{ $tahun }}</p>
            </div>
            <form method="POST" action="{{ route('penyusutan.proses') }}" onsubmit="return confirm('Proses penyusutan untuk tahun {{ $tahun }}? Data yang sudah ada akan diperbarui.')">
                @csrf
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                <button type="submit" class="px-5 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:from-emerald-600 hover:to-teal-700 transition-all shadow-lg">
                    <i class="fas fa-calculator mr-2"></i>Proses Penyusutan {{ $tahun }}
                </button>
            </form>
        </div>
    </div>

    <!-- Laporan Per Golongan -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden">
        <div class="p-6 border-b dark:border-slate-700/50 border-slate-200">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="font-semibold dark:text-white text-slate-800">Rekap Per Golongan Aset</h4>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Berdasarkan standar akuntansi pemerintah</p>
                </div>
                <a href="{{ route('penyusutan.laporan-golongan', ['tahun' => $tahun]) }}" class="text-emerald-400 hover:text-emerald-300 text-sm">
                    Lihat Detail <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="dark:bg-slate-700/30 bg-slate-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Golongan</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Umur</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Jumlah</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Nilai Perolehan</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Akum. Penyusutan</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Nilai Buku</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50 divide-slate-200">
                    @php $totalPerolehan = 0; $totalAkumulasi = 0; $totalNilaiBuku = 0; $totalAset = 0; @endphp
                    @foreach($perGolongan as $golongan => $data)
                    @php 
                        $totalPerolehan += $data['total_nilai_perolehan'];
                        $totalAkumulasi += $data['total_akumulasi'];
                        $totalNilaiBuku += $data['total_nilai_buku'];
                        $totalAset += $data['jumlah_aset'];
                    @endphp
                    <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3">
                            <p class="dark:text-white text-slate-800 font-medium">{{ $data['nama_golongan'] }}</p>
                            <p class="text-xs dark:text-slate-500 text-slate-400">{{ $golongan }}</p>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($data['umur_manfaat'] > 0)
                                <span class="px-2 py-1 text-xs bg-blue-500/20 text-blue-400 rounded-lg">{{ $data['umur_manfaat'] }} th</span>
                            @else
                                <span class="px-2 py-1 text-xs bg-slate-500/20 text-slate-400 rounded-lg">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center dark:text-slate-300 text-slate-700">{{ number_format($data['jumlah_aset']) }}</td>
                        <td class="px-4 py-3 text-right dark:text-slate-300 text-slate-700">Rp {{ number_format($data['total_nilai_perolehan'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-amber-400">Rp {{ number_format($data['total_akumulasi'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-emerald-400 font-semibold">Rp {{ number_format($data['total_nilai_buku'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="dark:bg-slate-700/50 bg-slate-100">
                    <tr>
                        <td class="px-4 py-3 font-bold dark:text-white text-slate-800">TOTAL</td>
                        <td class="px-4 py-3"></td>
                        <td class="px-4 py-3 text-center font-bold dark:text-white text-slate-800">{{ number_format($totalAset) }}</td>
                        <td class="px-4 py-3 text-right font-bold dark:text-white text-slate-800">Rp {{ number_format($totalPerolehan, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-bold text-amber-400">Rp {{ number_format($totalAkumulasi, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-bold text-emerald-400">Rp {{ number_format($totalNilaiBuku, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('penyusutan.laporan-golongan', ['tahun' => $tahun]) }}" class="dark:bg-slate-800/50 bg-white rounded-xl p-6 border dark:border-slate-700/50 border-slate-200 hover:border-emerald-500 transition group">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-500/20 flex items-center justify-center group-hover:bg-emerald-500/30 transition">
                    <i class="fas fa-chart-pie text-emerald-400 text-xl"></i>
                </div>
                <div>
                    <h5 class="font-semibold dark:text-white text-slate-800">Laporan Per Golongan</h5>
                    <p class="text-sm dark:text-slate-400 text-slate-600">Rekap berdasarkan golongan aset</p>
                </div>
            </div>
        </a>
        <a href="{{ route('penyusutan.laporan-tahun-perolehan', ['tahun' => $tahun]) }}" class="dark:bg-slate-800/50 bg-white rounded-xl p-6 border dark:border-slate-700/50 border-slate-200 hover:border-blue-500 transition group">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-500/20 flex items-center justify-center group-hover:bg-blue-500/30 transition">
                    <i class="fas fa-calendar-alt text-blue-400 text-xl"></i>
                </div>
                <div>
                    <h5 class="font-semibold dark:text-white text-slate-800">Laporan Per Tahun Perolehan</h5>
                    <p class="text-sm dark:text-slate-400 text-slate-600">Rekap berdasarkan tahun perolehan</p>
                </div>
            </div>
        </a>
        <a href="{{ route('penyusutan.laporan-detail', ['tahun' => $tahun]) }}" class="dark:bg-slate-800/50 bg-white rounded-xl p-6 border dark:border-slate-700/50 border-slate-200 hover:border-purple-500 transition group">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-purple-500/20 flex items-center justify-center group-hover:bg-purple-500/30 transition">
                    <i class="fas fa-list-alt text-purple-400 text-xl"></i>
                </div>
                <div>
                    <h5 class="font-semibold dark:text-white text-slate-800">Laporan Detail</h5>
                    <p class="text-sm dark:text-slate-400 text-slate-600">Daftar penyusutan per aset</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Info Rumus -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6">
        <h4 class="font-semibold dark:text-white text-slate-800 mb-4"><i class="fas fa-info-circle text-blue-400 mr-2"></i>Rumus Penyusutan (Metode Garis Lurus)</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="dark:bg-slate-700/30 bg-slate-50 rounded-xl p-4">
                <p class="text-sm dark:text-slate-400 text-slate-600 mb-2">Penyusutan Per Tahun:</p>
                <code class="text-emerald-400 text-sm">(Nilai Perolehan - Nilai Residu) / Umur Manfaat</code>
            </div>
            <div class="dark:bg-slate-700/30 bg-slate-50 rounded-xl p-4">
                <p class="text-sm dark:text-slate-400 text-slate-600 mb-2">Penyusutan Per Bulan:</p>
                <code class="text-emerald-400 text-sm">Penyusutan Per Tahun / 12</code>
            </div>
            <div class="dark:bg-slate-700/30 bg-slate-50 rounded-xl p-4">
                <p class="text-sm dark:text-slate-400 text-slate-600 mb-2">Akumulasi Penyusutan:</p>
                <code class="text-emerald-400 text-sm">Penyusutan Per Bulan x Jumlah Bulan</code>
            </div>
            <div class="dark:bg-slate-700/30 bg-slate-50 rounded-xl p-4">
                <p class="text-sm dark:text-slate-400 text-slate-600 mb-2">Nilai Buku:</p>
                <code class="text-emerald-400 text-sm">Nilai Perolehan - Akumulasi Penyusutan</code>
            </div>
        </div>
    </div>
</div>
@endsection
