@extends('layouts.app')

@section('title', 'Login History')
@section('header', 'Login History')
@section('breadcrumb', 'User / Login History')

@section('content')
<div class="space-y-6">
    <!-- Back Button -->
    <div>
        <a href="{{ route('users.index') }}" class="inline-flex items-center px-4 py-2 bg-slate-700 text-white rounded-xl hover:bg-slate-600 transition">
            <i class="fas fa-arrow-left mr-2"></i>Kembali ke Daftar User
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gradient-to-br from-green-500/20 to-emerald-600/20 border border-green-500/30 rounded-2xl p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-400 text-sm">Total Login</p>
                    <p class="text-white text-3xl font-bold">{{ $stats['total_logins'] }}</p>
                </div>
                <div class="w-14 h-14 bg-green-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-sign-in-alt text-green-400 text-2xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-red-500/20 to-rose-600/20 border border-red-500/30 rounded-2xl p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-400 text-sm">Total Logout</p>
                    <p class="text-white text-3xl font-bold">{{ $stats['total_logouts'] }}</p>
                </div>
                <div class="w-14 h-14 bg-red-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-sign-out-alt text-red-400 text-2xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-blue-500/20 to-indigo-600/20 border border-blue-500/30 rounded-2xl p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-400 text-sm">Unique Users</p>
                    <p class="text-white text-3xl font-bold">{{ $stats['unique_users'] }}</p>
                </div>
                <div class="w-14 h-14 bg-blue-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-users text-blue-400 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-slate-800/50 backdrop-blur-xl rounded-2xl border border-slate-700/50 p-4">
        <form action="{{ route('users.login-history') }}" method="GET" class="flex flex-wrap gap-4 items-end">
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
            <div class="w-36">
                <label class="block text-slate-400 text-sm mb-1">Aksi</label>
                <select name="action" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-2 text-white focus:outline-none focus:border-blue-500">
                    <option value="">Semua</option>
                    <option value="login" {{ $action == 'login' ? 'selected' : '' }}>Login</option>
                    <option value="logout" {{ $action == 'logout' ? 'selected' : '' }}>Logout</option>
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
            <a href="{{ route('users.login-history') }}" class="px-6 py-2 bg-slate-600 text-white rounded-xl hover:bg-slate-500 transition inline-flex items-center">
                <i class="fas fa-undo mr-2"></i>Reset Filter
            </a>
        </form>
        
        <!-- Tombol Hapus Semua Data -->
        <form action="{{ route('users.login-history.clear') }}" method="POST" class="mt-4 pt-4 border-t border-slate-700/50" onsubmit="return confirm('PERINGATAN!\n\nAnda akan menghapus SEMUA data login history.\nTindakan ini tidak dapat dibatalkan!\n\nLanjutkan?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-6 py-2 bg-red-500 text-white rounded-xl hover:bg-red-600 transition inline-flex items-center">
                <i class="fas fa-trash-alt mr-2"></i>Hapus Semua Login History
            </button>
            <span class="text-slate-500 text-sm ml-3">Total: {{ $stats['total_logins'] + $stats['total_logouts'] }} records</span>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-slate-800/50 backdrop-blur-xl rounded-2xl border border-slate-700/50 overflow-hidden">
        @if($histories->isEmpty())
            <div class="p-12 text-center">
                <div class="w-20 h-20 bg-slate-700/50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-history text-slate-500 text-3xl"></i>
                </div>
                <h3 class="text-slate-400 text-lg font-medium mb-2">Tidak Ada Data</h3>
                <p class="text-slate-500">Tidak ada riwayat login yang ditemukan.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-700/50">
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">User</th>
                            <th class="text-center text-slate-300 font-semibold px-6 py-4">Aksi</th>
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">Waktu</th>
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">IP Address</th>
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">Device</th>
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">Browser</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                        @foreach($histories as $history)
                            <tr class="hover:bg-slate-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    @if($history->user)
                                        <a href="{{ route('users.show', $history->user->id) }}" class="flex items-center hover:text-blue-400 transition">
                                            <div class="w-10 h-10 {{ $history->user->getRoleBadgeClass() }} rounded-full flex items-center justify-center mr-3">
                                                <span class="text-white font-bold">{{ strtoupper(substr($history->user->name, 0, 1)) }}</span>
                                            </div>
                                            <div>
                                                <span class="text-white font-medium">{{ $history->user->name }}</span>
                                                <p class="text-xs text-slate-500">{{ $history->user->getRoleName() }}</p>
                                            </div>
                                        </a>
                                    @else
                                        <span class="text-slate-500">User dihapus</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 rounded-lg text-sm font-medium {{ $history->getActionBadgeClass() }}">
                                        <i class="fas fa-{{ $history->action === 'login' ? 'sign-in-alt' : 'sign-out-alt' }} mr-1"></i>
                                        {{ $history->getActionLabel() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm">
                                        <p class="text-white">{{ $history->created_at->format('d M Y') }}</p>
                                        <p class="text-slate-500">{{ $history->created_at->format('H:i:s') }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-slate-300">{{ $history->ip_address }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <i class="fas fa-{{ $history->device === 'Mobile' ? 'mobile-alt' : ($history->device === 'Tablet' ? 'tablet-alt' : 'desktop') }} text-slate-500 mr-2"></i>
                                        <div>
                                            <p class="text-white text-sm">{{ $history->device }}</p>
                                            <p class="text-slate-500 text-xs">{{ $history->platform }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-slate-300">{{ $history->browser }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($histories->hasPages())
                <div class="px-6 py-4 border-t border-slate-700/50">
                    {{ $histories->appends(request()->query())->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
