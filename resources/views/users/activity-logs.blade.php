@extends('layouts.app')

@section('title', 'Activity Logs')
@section('header', 'Activity Logs')
@section('breadcrumb', 'User / Activity Logs')

@section('content')
<div class="space-y-6">
    <!-- Back Button -->
    <div>
        <a href="{{ route('users.index') }}" class="inline-flex items-center px-4 py-2 bg-slate-700 text-white rounded-xl hover:bg-slate-600 transition">
            <i class="fas fa-arrow-left mr-2"></i>Kembali ke Daftar User
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-slate-800/50 backdrop-blur-xl rounded-2xl border border-slate-700/50 p-4">
        <form action="{{ route('users.activity-logs') }}" method="GET" class="flex flex-wrap gap-4 items-end">
            <!-- User Filter -->
            <div class="w-48">
                <label class="block text-slate-400 text-sm mb-1">User</label>
                <select name="user_id" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-2 text-white focus:outline-none focus:border-blue-500">
                    <option value="">Semua User</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ $userId == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <!-- Action Filter -->
            <div class="w-48">
                <label class="block text-slate-400 text-sm mb-1">Tipe Aktivitas</label>
                <select name="action" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-2 text-white focus:outline-none focus:border-blue-500">
                    <option value="">Semua Aktivitas</option>
                    @foreach($actions as $act)
                        <option value="{{ $act }}" {{ $action == $act ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $act)) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Date From -->
            <div class="w-40">
                <label class="block text-slate-400 text-sm mb-1">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-2 text-white focus:outline-none focus:border-blue-500">
            </div>

            <!-- Date To -->
            <div class="w-40">
                <label class="block text-slate-400 text-sm mb-1">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-2 text-white focus:outline-none focus:border-blue-500">
            </div>
            
            <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-xl hover:bg-blue-600 transition">
                <i class="fas fa-filter mr-2"></i>Filter
            </button>
            <a href="{{ route('users.activity-logs') }}" class="px-6 py-2 bg-slate-600 text-white rounded-xl hover:bg-slate-500 transition inline-flex items-center">
                <i class="fas fa-undo mr-2"></i>Reset Filter
            </a>
        </form>
        
        <!-- Tombol Hapus Semua Data -->
        <form action="{{ route('users.activity-logs.clear') }}" method="POST" class="mt-4 pt-4 border-t border-slate-700/50" onsubmit="return confirm('PERINGATAN!\n\nAnda akan menghapus SEMUA data activity logs.\nTindakan ini tidak dapat dibatalkan!\n\nLanjutkan?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-6 py-2 bg-red-500 text-white rounded-xl hover:bg-red-600 transition inline-flex items-center">
                <i class="fas fa-trash-alt mr-2"></i>Hapus Semua Activity Logs
            </button>
            <span class="text-slate-500 text-sm ml-3">Total: {{ $logs->total() }} records</span>
        </form>
    </div>

    <!-- Info Notice -->
    <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-3">
        <div class="flex items-center text-blue-300 text-sm">
            <i class="fas fa-info-circle mr-2"></i>
            <span>Menampilkan maksimal <strong>100 aktivitas terakhir</strong> untuk performa yang optimal.</span>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-slate-800/50 backdrop-blur-xl rounded-2xl border border-slate-700/50 overflow-hidden">
        @if($logs->isEmpty())
            <div class="p-12 text-center">
                <div class="w-20 h-20 bg-slate-700/50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-list-alt text-slate-500 text-3xl"></i>
                </div>
                <h3 class="text-slate-400 text-lg font-medium mb-2">Tidak Ada Data</h3>
                <p class="text-slate-500">Tidak ada log aktivitas yang ditemukan.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-700/50">
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">Waktu</th>
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">User</th>
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">Aksi</th>
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                        @foreach($logs as $log)
                            <tr class="hover:bg-slate-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="text-sm">
                                        <p class="text-white">{{ $log->created_at->format('d M Y') }}</p>
                                        <p class="text-slate-500">{{ $log->created_at->format('H:i:s') }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($log->user)
                                        <a href="{{ route('users.show', $log->user->id) }}" class="flex items-center hover:text-blue-400 transition">
                                            <div class="w-10 h-10 {{ $log->user->getRoleBadgeClass() }} rounded-full flex items-center justify-center mr-3">
                                                <span class="text-white font-bold">{{ strtoupper(substr($log->user->name, 0, 1)) }}</span>
                                            </div>
                                            <div>
                                                <span class="text-white font-medium">{{ $log->user->name }}</span>
                                                <p class="text-xs text-slate-500">{{ $log->user->getRoleName() }}</p>
                                            </div>
                                        </a>
                                    @else
                                        <span class="text-slate-500">User dihapus</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $actionColor = match(true) {
                                            str_contains($log->action, 'login') => 'bg-green-500/20 text-green-400',
                                            str_contains($log->action, 'logout') => 'bg-red-500/20 text-red-400',
                                            str_contains($log->action, 'create') || str_contains($log->action, 'store') => 'bg-blue-500/20 text-blue-400',
                                            str_contains($log->action, 'update') || str_contains($log->action, 'edit') => 'bg-amber-500/20 text-amber-400',
                                            str_contains($log->action, 'delete') || str_contains($log->action, 'reject') => 'bg-red-500/20 text-red-400',
                                            str_contains($log->action, 'approve') || str_contains($log->action, 'complete') => 'bg-emerald-500/20 text-emerald-400',
                                            default => 'bg-slate-500/20 text-slate-400'
                                        };
                                    @endphp
                                    <span class="px-3 py-1 rounded-lg text-sm font-medium {{ $actionColor }}">
                                        {{ ucwords(str_replace('_', ' ', $log->action)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-slate-300 text-sm">{{ $log->description }}</p>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="px-6 py-4 border-t border-slate-700/50">
                    {{ $logs->appends(request()->query())->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
