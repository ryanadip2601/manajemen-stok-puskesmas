@extends('layouts.app')

@section('title', 'Riwayat Pemeliharaan - ' . $ruangan->nama_ruangan)
@section('header', 'Riwayat Pemeliharaan')
@section('breadcrumb', 'Aset / Pemeliharaan / Riwayat Ruangan')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold dark:text-white">Riwayat Pemeliharaan</h3>
            <p class="text-sm dark:text-slate-400">Ruangan: <span class="text-blue-400">{{ $ruangan->nama_ruangan }}</span></p>
        </div>
        <a href="{{ route('ruangan.show', $ruangan->id) }}" class="px-4 py-2 dark:bg-slate-700 bg-slate-200 dark:text-slate-300 rounded-xl font-medium hover:bg-slate-600 hover:text-white transition">
            <i class="fas fa-arrow-left mr-2"></i>Kembali
        </a>
    </div>

    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="dark:bg-slate-700/30 bg-slate-100">
                    <tr>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 uppercase">Tanggal</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 uppercase">Aset</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 uppercase">Jenis</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 uppercase">Masalah</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold dark:text-slate-400 uppercase">Teknisi</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 uppercase">Status</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold dark:text-slate-400 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50">
                    @forelse($pemeliharaan as $p)
                    <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50">
                        <td class="px-4 py-4 dark:text-slate-300">{{ $p->tanggal_pemeliharaan->format('d/m/Y') }}</td>
                        <td class="px-4 py-4">
                            <p class="dark:text-white font-medium">{{ $p->barangAset->nama_aset ?? '-' }}</p>
                            <p class="text-xs dark:text-slate-500 font-mono">{{ $p->barangAset->kode_aset ?? '-' }}</p>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="px-2 py-1 text-xs rounded {{ \App\Models\PemeliharaanAset::getJenisBadgeClass($p->jenis_pemeliharaan) }}">{{ $p->jenis_label }}</span>
                        </td>
                        <td class="px-4 py-4 dark:text-slate-300 max-w-xs truncate">{{ Str::limit($p->deskripsi_masalah, 50) }}</td>
                        <td class="px-4 py-4 dark:text-slate-300">{{ $p->teknisi }}</td>
                        <td class="px-4 py-4 text-center">
                            <span class="px-2 py-1 text-xs rounded {{ \App\Models\PemeliharaanAset::getStatusBadgeClass($p->status) }}">{{ $p->status_label }}</span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <a href="{{ route('pemeliharaan.show', $p->id) }}" class="p-2 bg-blue-500/20 text-blue-400 rounded-lg hover:bg-blue-500/30 transition"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-6 py-12 text-center dark:text-slate-500">Belum ada riwayat pemeliharaan di ruangan ini</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($pemeliharaan->hasPages())<div class="p-4 border-t dark:border-slate-700/50">{{ $pemeliharaan->links() }}</div>@endif
    </div>
</div>
@endsection
