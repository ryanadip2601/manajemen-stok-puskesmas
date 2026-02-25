@extends('layouts.app')

@section('title', 'Detail User - ' . $user->name)
@section('header', 'Detail User')
@section('breadcrumb', 'User / Detail / ' . $user->name)

@section('content')
<div class="space-y-6">
    <!-- Back Button -->
    <div>
        <a href="{{ route('users.index') }}" class="inline-flex items-center px-4 py-2 bg-slate-700 text-white rounded-xl hover:bg-slate-600 transition">
            <i class="fas fa-arrow-left mr-2"></i>Kembali
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Profile Card -->
        <div class="lg:col-span-1">
            <div class="bg-slate-800/50 backdrop-blur-xl rounded-2xl border border-slate-700/50 overflow-hidden">
                <!-- Header with avatar -->
                <div class="bg-gradient-to-br {{ $user->getRoleBadgeClass() }} p-8 text-center">
                    <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4 relative">
                        <span class="text-white font-bold text-4xl">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                        @if($user->isOnline())
                            <span class="absolute bottom-1 right-1 w-5 h-5 bg-green-500 rounded-full border-4 border-white/20 animate-pulse"></span>
                        @endif
                    </div>
                    <h2 class="text-white text-2xl font-bold">{{ $user->name }}</h2>
                    <p class="text-white/80 mt-1">{{ $user->getRoleName() }}</p>
                    @if($user->isOnline())
                        <span class="inline-block mt-3 px-4 py-1 bg-green-500/30 text-green-200 rounded-full text-sm">
                            <i class="fas fa-circle text-xs mr-1 animate-pulse"></i>Sedang Online
                        </span>
                    @endif
                </div>

                <!-- Info -->
                <div class="p-6 space-y-4">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-blue-500/20 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-envelope text-blue-400"></i>
                        </div>
                        <div>
                            <p class="text-slate-400 text-xs">Email</p>
                            <p class="text-white">{{ $user->email }}</p>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-green-500/20 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-phone text-green-400"></i>
                        </div>
                        <div>
                            <p class="text-slate-400 text-xs">No. Telepon</p>
                            <p class="text-white">{{ $user->phone ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-purple-500/20 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-calendar text-purple-400"></i>
                        </div>
                        <div>
                            <p class="text-slate-400 text-xs">Tanggal Daftar</p>
                            <p class="text-white">{{ $user->created_at->format('d F Y, H:i') }}</p>
                        </div>
                    </div>

                    @if($user->is_approved)
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-emerald-500/20 rounded-xl flex items-center justify-center mr-4">
                                <i class="fas fa-check-circle text-emerald-400"></i>
                            </div>
                            <div>
                                <p class="text-slate-400 text-xs">Disetujui Pada</p>
                                @php
                                    $approvedAt = $user->approved_at instanceof \Carbon\Carbon 
                                        ? $user->approved_at 
                                        : ($user->approved_at ? \Carbon\Carbon::parse($user->approved_at) : null);
                                @endphp
                                <p class="text-white">{{ $approvedAt ? $approvedAt->format('d F Y, H:i') : '-' }}</p>
                                @if($user->approvedByUser)
                                    <p class="text-slate-500 text-xs">oleh {{ $user->approvedByUser->name }}</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-cyan-500/20 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-clock text-cyan-400"></i>
                        </div>
                        <div>
                            <p class="text-slate-400 text-xs">Login Terakhir</p>
                            @php
                                $lastLogin = $user->last_login_at instanceof \Carbon\Carbon 
                                    ? $user->last_login_at 
                                    : ($user->last_login_at ? \Carbon\Carbon::parse($user->last_login_at) : null);
                            @endphp
                            <p class="text-white">{{ $lastLogin ? $lastLogin->format('d F Y, H:i') : 'Belum pernah login' }}</p>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-amber-500/20 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-wifi text-amber-400"></i>
                        </div>
                        <div>
                            <p class="text-slate-400 text-xs">Aktivitas Terakhir</p>
                            @php
                                $lastActivity = $user->last_activity_at instanceof \Carbon\Carbon 
                                    ? $user->last_activity_at 
                                    : ($user->last_activity_at ? \Carbon\Carbon::parse($user->last_activity_at) : null);
                            @endphp
                            <p class="text-white">{{ $lastActivity ? $lastActivity->diffForHumans() : '-' }}</p>
                            @if($user->last_ip_address)
                                <p class="text-slate-500 text-xs">IP: {{ $user->last_ip_address }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                @if(auth()->user()->isAdmin() && $user->id !== auth()->id())
                    <div class="p-6 border-t border-slate-700/50 space-y-3">
                        <a href="{{ route('users.edit-role', $user->id) }}" class="block w-full px-4 py-3 bg-amber-500/20 text-amber-400 rounded-xl hover:bg-amber-500/30 transition text-center">
                            <i class="fas fa-user-cog mr-2"></i>Ubah Role
                        </a>
                        <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Hapus user {{ $user->name }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full px-4 py-3 bg-red-500/20 text-red-400 rounded-xl hover:bg-red-500/30 transition">
                                <i class="fas fa-trash mr-2"></i>Hapus User
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            <!-- Stats Card -->
            <div class="bg-slate-800/50 backdrop-blur-xl rounded-2xl border border-slate-700/50 p-6 mt-6">
                <h3 class="text-white font-bold mb-4"><i class="fas fa-chart-bar text-blue-400 mr-2"></i>Statistik</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-slate-700/30 rounded-xl p-4 text-center">
                        <p class="text-3xl font-bold text-blue-400">{{ $userStats['total_logins'] }}</p>
                        <p class="text-slate-400 text-sm">Total Login</p>
                    </div>
                    <div class="bg-slate-700/30 rounded-xl p-4 text-center">
                        <p class="text-3xl font-bold text-emerald-400">{{ $userStats['total_activities'] }}</p>
                        <p class="text-slate-400 text-sm">Aktivitas</p>
                    </div>
                    <div class="bg-slate-700/30 rounded-xl p-4 text-center">
                        <p class="text-3xl font-bold text-amber-400">{{ $userStats['total_requests'] }}</p>
                        <p class="text-slate-400 text-sm">Permintaan</p>
                    </div>
                    <div class="bg-slate-700/30 rounded-xl p-4 text-center">
                        <p class="text-3xl font-bold text-green-400">{{ $userStats['approved_requests'] }}</p>
                        <p class="text-slate-400 text-sm">Selesai</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Section -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Login History -->
            <div class="bg-slate-800/50 backdrop-blur-xl rounded-2xl border border-slate-700/50 overflow-hidden">
                <div class="p-6 border-b border-slate-700/50 flex items-center justify-between">
                    <h3 class="text-white font-bold"><i class="fas fa-history text-indigo-400 mr-2"></i>Riwayat Login Terakhir</h3>
                    <a href="{{ route('users.login-history', ['user_id' => $user->id]) }}" class="text-blue-400 text-sm hover:text-blue-300">
                        Lihat Semua <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                @if($loginHistories->isEmpty())
                    <div class="p-6 text-center text-slate-500">
                        <i class="fas fa-info-circle mr-2"></i>Belum ada riwayat login
                    </div>
                @else
                    <div class="divide-y divide-slate-700/50">
                        @foreach($loginHistories as $history)
                            <div class="p-4 flex items-center hover:bg-slate-700/30 transition">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center mr-4 {{ $history->action === 'login' ? 'bg-green-500/20' : 'bg-red-500/20' }}">
                                    <i class="fas fa-{{ $history->action === 'login' ? 'sign-in-alt' : 'sign-out-alt' }} {{ $history->action === 'login' ? 'text-green-400' : 'text-red-400' }}"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <span class="px-2 py-0.5 rounded text-xs font-medium {{ $history->getActionBadgeClass() }}">
                                            {{ $history->getActionLabel() }}
                                        </span>
                                        <span class="text-slate-400 text-sm ml-2">{{ $history->created_at->format('d M Y, H:i:s') }}</span>
                                    </div>
                                    <p class="text-slate-500 text-xs mt-1">
                                        <i class="fas fa-desktop mr-1"></i>{{ $history->device }} |
                                        <i class="fas fa-globe mr-1"></i>{{ $history->browser }} |
                                        <i class="fas fa-laptop mr-1"></i>{{ $history->platform }} |
                                        <i class="fas fa-map-marker-alt mr-1"></i>{{ $history->ip_address }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Activity Logs -->
            <div class="bg-slate-800/50 backdrop-blur-xl rounded-2xl border border-slate-700/50 overflow-hidden">
                <div class="p-6 border-b border-slate-700/50 flex items-center justify-between">
                    <h3 class="text-white font-bold"><i class="fas fa-list-alt text-emerald-400 mr-2"></i>Aktivitas Terakhir</h3>
                    <a href="{{ route('users.activity-logs', ['user_id' => $user->id]) }}" class="text-blue-400 text-sm hover:text-blue-300">
                        Lihat Semua <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                @if($activityLogs->isEmpty())
                    <div class="p-6 text-center text-slate-500">
                        <i class="fas fa-info-circle mr-2"></i>Belum ada aktivitas
                    </div>
                @else
                    <div class="divide-y divide-slate-700/50 max-h-96 overflow-y-auto">
                        @foreach($activityLogs as $log)
                            <div class="p-4 hover:bg-slate-700/30 transition">
                                <div class="flex items-start">
                                    <div class="w-8 h-8 bg-slate-700/50 rounded-lg flex items-center justify-center mr-3 mt-0.5">
                                        <i class="fas fa-circle text-xs text-blue-400"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-white text-sm">{{ $log->description }}</p>
                                        <p class="text-slate-500 text-xs mt-1">
                                            <i class="fas fa-clock mr-1"></i>{{ $log->created_at->format('d M Y, H:i:s') }}
                                            <span class="ml-2 px-2 py-0.5 bg-slate-700/50 rounded text-slate-400">{{ $log->action }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Stock Out Requests (untuk staff) -->
            @if($stockRequests->isNotEmpty())
                <div class="bg-slate-800/50 backdrop-blur-xl rounded-2xl border border-slate-700/50 overflow-hidden">
                    <div class="p-6 border-b border-slate-700/50">
                        <h3 class="text-white font-bold"><i class="fas fa-box text-amber-400 mr-2"></i>Permintaan Pengambilan Barang</h3>
                    </div>
                    <div class="divide-y divide-slate-700/50">
                        @foreach($stockRequests as $request)
                            <div class="p-4 hover:bg-slate-700/30 transition">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-white font-medium">{{ $request->item->name ?? 'Barang dihapus' }}</p>
                                        <p class="text-slate-400 text-sm">Qty: {{ $request->quantity }} | {{ $request->request_date->format('d M Y') }}</p>
                                    </div>
                                    <span class="px-3 py-1 rounded-lg text-sm font-medium {{ $request->getStatusBadgeClass() }}">
                                        {{ $request->getStatusLabel() }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
