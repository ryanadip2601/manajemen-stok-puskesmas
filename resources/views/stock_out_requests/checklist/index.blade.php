@extends('layouts.app')

@section('title', 'Checklist Pengambilan Barang')
@section('header', 'Checklist Pengambilan Barang')
@section('breadcrumb', 'Checklist / Permintaan Pengambilan')

@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-yellow-500/20 to-amber-600/20 border border-yellow-500/30 rounded-2xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-400 text-sm">Menunggu</p>
                    <p class="text-white text-2xl font-bold">{{ $counts['pending'] }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-400 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-blue-500/20 to-indigo-600/20 border border-blue-500/30 rounded-2xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-400 text-sm">Disetujui</p>
                    <p class="text-white text-2xl font-bold">{{ $counts['approved'] }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check text-blue-400 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-green-500/20 to-emerald-600/20 border border-green-500/30 rounded-2xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-400 text-sm">Selesai</p>
                    <p class="text-white text-2xl font-bold">{{ $counts['completed'] }}</p>
                </div>
                <div class="w-12 h-12 bg-green-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-double text-green-400 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-red-500/20 to-rose-600/20 border border-red-500/30 rounded-2xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-400 text-sm">Ditolak</p>
                    <p class="text-white text-2xl font-bold">{{ $counts['rejected'] }}</p>
                </div>
                <div class="w-12 h-12 bg-red-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-times text-red-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('checklist.index', ['status' => 'pending']) }}" class="px-4 py-2 rounded-xl font-medium transition {{ $status == 'pending' ? 'bg-yellow-500 text-white' : 'bg-slate-700/50 text-slate-300 hover:bg-slate-700' }}">
            <i class="fas fa-clock mr-2"></i>Menunggu
            @if($counts['pending'] > 0)
                <span class="ml-1 px-2 py-0.5 bg-white/20 rounded-full text-xs">{{ $counts['pending'] }}</span>
            @endif
        </a>
        <a href="{{ route('checklist.index', ['status' => 'approved']) }}" class="px-4 py-2 rounded-xl font-medium transition {{ $status == 'approved' ? 'bg-blue-500 text-white' : 'bg-slate-700/50 text-slate-300 hover:bg-slate-700' }}">
            <i class="fas fa-check mr-2"></i>Disetujui
        </a>
        <a href="{{ route('checklist.index', ['status' => 'completed']) }}" class="px-4 py-2 rounded-xl font-medium transition {{ $status == 'completed' ? 'bg-green-500 text-white' : 'bg-slate-700/50 text-slate-300 hover:bg-slate-700' }}">
            <i class="fas fa-check-double mr-2"></i>Selesai
        </a>
        <a href="{{ route('checklist.index', ['status' => 'rejected']) }}" class="px-4 py-2 rounded-xl font-medium transition {{ $status == 'rejected' ? 'bg-red-500 text-white' : 'bg-slate-700/50 text-slate-300 hover:bg-slate-700' }}">
            <i class="fas fa-times mr-2"></i>Ditolak
        </a>
        <a href="{{ route('checklist.index', ['status' => 'all']) }}" class="px-4 py-2 rounded-xl font-medium transition {{ $status == 'all' ? 'bg-slate-500 text-white' : 'bg-slate-700/50 text-slate-300 hover:bg-slate-700' }}">
            <i class="fas fa-list mr-2"></i>Semua
        </a>
    </div>

    <!-- Table -->
    <div class="bg-slate-800/50 backdrop-blur-xl rounded-2xl border border-slate-700/50 overflow-hidden">
        @if($requests->isEmpty())
            <div class="p-12 text-center">
                <div class="w-20 h-20 bg-slate-700/50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-clipboard-check text-slate-500 text-3xl"></i>
                </div>
                <h3 class="text-slate-400 text-lg font-medium mb-2">Tidak Ada Permintaan</h3>
                <p class="text-slate-500">Belum ada permintaan pengambilan barang dengan status ini.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-700/50">
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">Pemohon</th>
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">Barang</th>
                            <th class="text-center text-slate-300 font-semibold px-6 py-4">Jumlah</th>
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">Tanggal</th>
                            <th class="text-center text-slate-300 font-semibold px-6 py-4">Status</th>
                            <th class="text-center text-slate-300 font-semibold px-6 py-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                        @foreach($requests as $request)
                            <tr class="hover:bg-slate-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center mr-3">
                                            <span class="text-white font-bold">{{ strtoupper(substr($request->user->name, 0, 1)) }}</span>
                                        </div>
                                        <div>
                                            <span class="text-white font-medium">{{ $request->user->name }}</span>
                                            <p class="text-xs text-slate-500">{{ $request->user->getRoleName() }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div>
                                        <span class="text-white font-medium">{{ $request->item->name }}</span>
                                        <p class="text-xs text-slate-500">{{ $request->item->category->name ?? '-' }} | Stok: {{ $request->item->stock }}</p>
                                        @if($request->notes)
                                            <p class="text-xs text-amber-400 mt-1"><i class="fas fa-sticky-note mr-1"></i>{{ $request->notes }}</p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 bg-slate-700 text-white rounded-lg font-semibold">
                                        {{ $request->quantity }} {{ $request->item->unit->symbol ?? '' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-white">{{ $request->request_date->format('d M Y') }}</span>
                                    <p class="text-xs text-slate-500">{{ $request->created_at->format('H:i') }}</p>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 rounded-lg text-sm font-medium {{ $request->getStatusBadgeClass() }}">
                                        {{ $request->getStatusLabel() }}
                                    </span>
                                    @if($request->processedByUser)
                                        <p class="text-xs text-slate-500 mt-1">oleh {{ $request->processedByUser->name }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center space-x-2">
                                        @if($request->isPending())
                                            <!-- Approve Button -->
                                            <form action="{{ route('checklist.approve', $request->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="p-2 bg-green-500/20 text-green-400 rounded-lg hover:bg-green-500/30 transition" title="Setujui" onclick="return confirm('Setujui permintaan ini?')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <!-- Reject Button -->
                                            <button type="button" class="p-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition" title="Tolak" onclick="openRejectModal({{ $request->id }})">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        @elseif($request->isApproved())
                                            <!-- Complete Button -->
                                            <form action="{{ route('checklist.complete', $request->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="px-3 py-1 bg-green-500/20 text-green-400 rounded-lg hover:bg-green-500/30 transition text-sm" onclick="return confirm('Konfirmasi barang sudah diambil? Stok akan berkurang.')">
                                                    <i class="fas fa-check-double mr-1"></i>Selesai
                                                </button>
                                            </form>
                                            <!-- Undo Button -->
                                            <form action="{{ route('checklist.undo', $request->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="px-3 py-1 bg-amber-500/20 text-amber-400 rounded-lg hover:bg-amber-500/30 transition text-sm" onclick="return confirm('Batalkan persetujuan?')">
                                                    <i class="fas fa-undo mr-1"></i>Batal
                                                </button>
                                            </form>
                                        @elseif($request->isCompleted())
                                            <span class="text-green-400 text-sm">
                                                <i class="fas fa-check-circle mr-1"></i>{{ $request->pickup_date ? $request->pickup_date->format('d M') : '-' }}
                                            </span>
                                        @elseif($request->isRejected())
                                            <span class="text-red-400 text-sm" title="{{ $request->reject_reason }}">
                                                <i class="fas fa-ban mr-1"></i>Ditolak
                                            </span>
                                        @else
                                            <span class="text-slate-500">-</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($requests->hasPages())
                <div class="px-6 py-4 border-t border-slate-700/50">
                    {{ $requests->appends(['status' => $status])->links() }}
                </div>
            @endif
        @endif
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center">
    <div class="bg-slate-800 rounded-2xl border border-slate-700 p-6 w-full max-w-md mx-4">
        <h3 class="text-xl font-bold text-white mb-4">
            <i class="fas fa-times-circle text-red-400 mr-2"></i>Tolak Permintaan
        </h3>
        <form id="rejectForm" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-slate-300 font-medium mb-2">Alasan Penolakan (Opsional)</label>
                <textarea name="reject_reason" rows="3" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-red-500 transition resize-none" placeholder="Berikan alasan mengapa permintaan ditolak..."></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2 bg-slate-700 text-white rounded-xl hover:bg-slate-600 transition">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded-xl hover:bg-red-600 transition">
                    <i class="fas fa-times mr-2"></i>Tolak Permintaan
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openRejectModal(id) {
        const modal = document.getElementById('rejectModal');
        const form = document.getElementById('rejectForm');
        form.action = '/checklist/' + id + '/reject';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeRejectModal() {
        const modal = document.getElementById('rejectModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Close modal on outside click
    document.getElementById('rejectModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeRejectModal();
        }
    });
</script>
@endpush
@endsection
