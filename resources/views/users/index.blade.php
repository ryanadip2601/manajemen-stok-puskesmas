@extends('layouts.app')

@section('title', 'Manajemen User')
@section('header', 'Manajemen User')
@section('breadcrumb', 'User / Daftar User')

@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
        <div class="bg-gradient-to-br from-slate-600/20 to-slate-700/20 border border-slate-500/30 rounded-2xl p-4">
            <div class="text-center">
                <p class="text-slate-400 text-xs">Total User</p>
                <p class="text-white text-2xl font-bold">{{ $stats['total'] }}</p>
            </div>
        </div>
        <div class="bg-gradient-to-br from-green-500/20 to-emerald-600/20 border border-green-500/30 rounded-2xl p-4">
            <div class="text-center">
                <p class="text-green-400 text-xs">Aktif</p>
                <p class="text-white text-2xl font-bold">{{ $stats['approved'] }}</p>
            </div>
        </div>
        <div class="bg-gradient-to-br from-yellow-500/20 to-amber-600/20 border border-yellow-500/30 rounded-2xl p-4">
            <div class="text-center">
                <p class="text-yellow-400 text-xs">Pending</p>
                <p class="text-white text-2xl font-bold">{{ $stats['pending'] }}</p>
            </div>
        </div>
        <div class="bg-gradient-to-br from-cyan-500/20 to-blue-600/20 border border-cyan-500/30 rounded-2xl p-4">
            <div class="text-center">
                <p class="text-cyan-400 text-xs">Online</p>
                <p class="text-white text-2xl font-bold">{{ $stats['online'] }}</p>
            </div>
        </div>
        <div class="bg-gradient-to-br from-red-500/20 to-rose-600/20 border border-red-500/30 rounded-2xl p-4">
            <div class="text-center">
                <p class="text-red-400 text-xs">Admin</p>
                <p class="text-white text-2xl font-bold">{{ $stats['admin'] }}</p>
            </div>
        </div>
        <div class="bg-gradient-to-br from-blue-500/20 to-indigo-600/20 border border-blue-500/30 rounded-2xl p-4">
            <div class="text-center">
                <p class="text-blue-400 text-xs">Admin Gudang</p>
                <p class="text-white text-2xl font-bold">{{ $stats['admin_gudang'] }}</p>
            </div>
        </div>
        <div class="bg-gradient-to-br from-purple-500/20 to-violet-600/20 border border-purple-500/30 rounded-2xl p-4">
            <div class="text-center">
                <p class="text-purple-400 text-xs">Pegawai</p>
                <p class="text-white text-2xl font-bold">{{ $stats['staff'] }}</p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-slate-800/50 backdrop-blur-xl rounded-2xl border border-slate-700/50 p-4">
        <form action="{{ route('users.index') }}" method="GET" class="flex flex-wrap gap-4 items-end">
            <!-- Search -->
            <div class="flex-1 min-w-[200px]">
                <label class="block text-slate-400 text-sm mb-1">Cari</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="Nama, email, atau HP..." class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-2 text-white focus:outline-none focus:border-blue-500">
            </div>
            
            <!-- Filter Status -->
            <div class="w-40">
                <label class="block text-slate-400 text-sm mb-1">Status</label>
                <select name="filter" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-2 text-white focus:outline-none focus:border-blue-500">
                    <option value="all" {{ $filter == 'all' ? 'selected' : '' }}>Semua</option>
                    <option value="approved" {{ $filter == 'approved' ? 'selected' : '' }}>Aktif</option>
                    <option value="pending" {{ $filter == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="online" {{ $filter == 'online' ? 'selected' : '' }}>Online</option>
                    <option value="today" {{ $filter == 'today' ? 'selected' : '' }}>Login Hari Ini</option>
                </select>
            </div>
            
            <!-- Filter Role -->
            <div class="w-40">
                <label class="block text-slate-400 text-sm mb-1">Role</label>
                <select name="role" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-2 text-white focus:outline-none focus:border-blue-500">
                    <option value="all" {{ $role == 'all' ? 'selected' : '' }}>Semua Role</option>
                    <option value="admin" {{ $role == 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="admin_gudang" {{ $role == 'admin_gudang' ? 'selected' : '' }}>Admin Gudang</option>
                    <option value="staff" {{ $role == 'staff' ? 'selected' : '' }}>Pegawai</option>
                </select>
            </div>
            
            <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-xl hover:bg-blue-600 transition">
                <i class="fas fa-search mr-2"></i>Filter
            </button>
            <button type="button" onclick="resetFilters()" class="px-6 py-2 bg-slate-600 text-white rounded-xl hover:bg-slate-500 transition">
                <i class="fas fa-redo mr-2"></i>Reset
            </button>
        </form>
    </div>

    <script>
        function resetFilters() {
            // Clear all input fields
            document.querySelector('input[name="search"]').value = '';
            document.querySelector('select[name="filter"]').value = 'all';
            document.querySelector('select[name="role"]').value = 'all';
            // Redirect to clean URL
            window.location.href = '{{ route("users.index") }}';
        }
    </script>

    <!-- Quick Links -->
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('users.login-history') }}" class="px-4 py-2 bg-indigo-500/20 text-indigo-400 rounded-xl hover:bg-indigo-500/30 transition">
            <i class="fas fa-history mr-2"></i>Login History
        </a>
        <a href="{{ route('users.activity-logs') }}" class="px-4 py-2 bg-emerald-500/20 text-emerald-400 rounded-xl hover:bg-emerald-500/30 transition">
            <i class="fas fa-list-alt mr-2"></i>Activity Logs
        </a>
    </div>

    <!-- Table -->
    <div class="bg-slate-800/50 backdrop-blur-xl rounded-2xl border border-slate-700/50 overflow-hidden">
        @if($users->isEmpty())
            <div class="p-12 text-center">
                <div class="w-20 h-20 bg-slate-700/50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-users text-slate-500 text-3xl"></i>
                </div>
                <h3 class="text-slate-400 text-lg font-medium mb-2">Tidak Ada User</h3>
                <p class="text-slate-500">Tidak ada user yang ditemukan dengan filter ini.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-700/50">
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">User</th>
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">Kontak</th>
                            <th class="text-center text-slate-300 font-semibold px-6 py-4">Role</th>
                            <th class="text-center text-slate-300 font-semibold px-6 py-4">Status</th>
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">Terdaftar</th>
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">Aktivitas Terakhir</th>
                            <th class="text-center text-slate-300 font-semibold px-6 py-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                        @foreach($users as $user)
                            <tr class="hover:bg-slate-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-12 h-12 {{ $user->getRoleBadgeClass() }} rounded-full flex items-center justify-center mr-3 relative">
                                            <span class="text-white font-bold text-lg">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                            @if($user->isOnline())
                                                <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2 border-slate-800"></span>
                                            @endif
                                        </div>
                                        <div>
                                            <span class="text-white font-medium">{{ $user->name }}</span>
                                            @if($user->id === auth()->id())
                                                <span class="ml-2 text-xs bg-blue-500/30 text-blue-400 px-2 py-0.5 rounded-full">Anda</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm">
                                        <p class="text-slate-300"><i class="fas fa-envelope text-slate-500 w-4 mr-1"></i>{{ $user->email }}</p>
                                        <p class="text-slate-400"><i class="fas fa-phone text-slate-500 w-4 mr-1"></i>{{ $user->phone ?? '-' }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 rounded-lg text-sm font-medium {{ $user->getRoleBadgeClass() }} bg-opacity-20 text-white">
                                        @if($user->isAdmin())
                                            <i class="fas fa-crown mr-1"></i>
                                        @elseif($user->isAdminGudang())
                                            <i class="fas fa-warehouse mr-1"></i>
                                        @else
                                            <i class="fas fa-user mr-1"></i>
                                        @endif
                                        {{ $user->getRoleName() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($user->is_approved)
                                        @if($user->isOnline())
                                            <span class="px-3 py-1 bg-green-500/20 text-green-400 rounded-lg text-sm">
                                                <i class="fas fa-circle text-xs mr-1 animate-pulse"></i>Online
                                            </span>
                                        @else
                                            <span class="px-3 py-1 bg-slate-500/20 text-slate-400 rounded-lg text-sm">
                                                <i class="fas fa-check-circle mr-1"></i>Aktif
                                            </span>
                                        @endif
                                    @else
                                        <span class="px-3 py-1 bg-yellow-500/20 text-yellow-400 rounded-lg text-sm">
                                            <i class="fas fa-clock mr-1"></i>Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm">
                                        <p class="text-white">{{ $user->created_at->format('d M Y') }}</p>
                                        <p class="text-slate-500">{{ $user->created_at->format('H:i') }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($user->last_activity_at)
                                        @php
                                            $lastActivity = $user->last_activity_at instanceof \Carbon\Carbon 
                                                ? $user->last_activity_at 
                                                : \Carbon\Carbon::parse($user->last_activity_at);
                                        @endphp
                                        <div class="text-sm">
                                            <p class="text-white">{{ $lastActivity->diffForHumans() }}</p>
                                            <p class="text-slate-500">{{ $user->last_ip_address ?? '-' }}</p>
                                        </div>
                                    @else
                                        <span class="text-slate-500">Belum pernah login</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center space-x-2">
                                        <a href="{{ route('users.show', $user->id) }}" class="p-2 bg-blue-500/20 text-blue-400 rounded-lg hover:bg-blue-500/30 transition" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if(auth()->user()->isAdmin() && $user->id !== auth()->id())
                                            <a href="{{ route('users.edit-role', $user->id) }}" class="p-2 bg-amber-500/20 text-amber-400 rounded-lg hover:bg-amber-500/30 transition" title="Ubah Role">
                                                <i class="fas fa-user-cog"></i>
                                            </a>
                                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus user {{ $user->name }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($users->hasPages())
                <div class="px-6 py-4 border-t border-slate-700/50">
                    {{ $users->appends(request()->query())->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
