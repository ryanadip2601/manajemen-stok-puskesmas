@extends('layouts.app')

@section('title', 'Riwayat Pemeliharaan - ' . $aset->nama_aset)
@section('header', 'Riwayat Pemeliharaan')
@section('breadcrumb', 'Aset / Pemeliharaan / Riwayat Aset')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold dark:text-white">Riwayat Pemeliharaan</h3>
            <p class="text-sm dark:text-slate-400">Aset: <span class="text-cyan-400">{{ $aset->nama_aset }}</span> ({{ $aset->kode_aset }})</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('pemeliharaan.create', ['aset_id' => $aset->id]) }}" class="px-4 py-2 bg-purple-500/20 text-purple-400 rounded-xl font-medium hover:bg-purple-500/30 transition">
                <i class="fas fa-plus mr-2"></i>Buat Pemeliharaan
            </a>
            <a href="{{ route('barang-aset.show', $aset->id) }}" class="px-4 py-2 dark:bg-slate-700 bg-slate-200 dark:text-slate-300 rounded-xl font-medium hover:bg-slate-600 hover:text-white transition">
                <i class="fas fa-arrow-left mr-2"></i>Kembali
            </a>
        </div>
    </div>

    <!-- Info Aset -->
    <div class="dark:bg-slate-800/50 bg-white rounded-xl p-4 border dark:border-slate-700/50">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <p class="text-xs dark:text-slate-400">Kategori</p>
                <p class="dark:text-white font-medium">{{ $aset->kategoriAset->nama_kategori ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs dark:text-slate-400">Ruangan</p>
                <p class="dark:text-white font-medium">{{ $aset->ruangan->nama_ruangan ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs dark:text-slate-400">Kondisi Saat Ini</p>
                <span class="px-2 py-1 text-xs rounded {{ \App\Models\BarangAset::getKondisiBadgeClass($aset->kondisi) }}">{{ $aset->kondisi_label }}</span>
            </div>
            <div>
                <p class="text-xs dark:text-slate-400">Total Pemeliharaan</p>
                <p class="dark:text-white font-bold text-xl">{{ $pemeliharaan->total() }}</p>
            </div>
        </div>
    </div>

    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="dark:bg-slate-700/30 bg-slate-100">
                    <tr>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 uppercase">Tanggal</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 uppercase">Jenis</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 uppercase">Masalah</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 uppercase">Tindakan</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 uppercase">Teknisi</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 uppercase">Status</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50">
                    @forelse($pemeliharaan as $p)
                    <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50">
                        <td class="px-4 py-4 dark:text-slate-300">
                            {{ $p->tanggal_pemeliharaan->format('d/m/Y') }}
                            @if($p->tanggal_selesai)
                            <p class="text-xs text-emerald-400">Selesai: {{ $p->tanggal_selesai->format('d/m/Y') }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="px-2 py-1 text-xs rounded {{ \App\Models\PemeliharaanAset::getJenisBadgeClass($p->jenis_pemeliharaan) }}">{{ $p->jenis_label }}</span>
                        </td>
                        <td class="px-4 py-4 dark:text-slate-300 max-w-xs">{{ Str::limit($p->deskripsi_masalah, 50) }}</td>
                        <td class="px-4 py-4 dark:text-slate-300 max-w-xs">{{ $p->tindakan_perbaikan ? Str::limit($p->tindakan_perbaikan, 50) : '-' }}</td>
                        <td class="px-4 py-4 dark:text-slate-300">{{ $p->teknisi }}</td>
                        <td class="px-4 py-4 text-center">
                            <span class="px-2 py-1 text-xs rounded {{ \App\Models\PemeliharaanAset::getStatusBadgeClass($p->status) }}">{{ $p->status_label }}</span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <a href="{{ route('pemeliharaan.show', $p->id) }}" class="p-2 bg-blue-500/20 text-blue-400 rounded-lg hover:bg-blue-500/30 transition"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-6 py-12 text-center dark:text-slate-500">Belum ada riwayat pemeliharaan untuk aset ini</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($pemeliharaan->hasPages())<div class="p-4 border-t dark:border-slate-700/50">{{ $pemeliharaan->links() }}</div>@endif
    </div>
</div>
@endsection
