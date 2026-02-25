@extends('layouts.app')

@section('title', 'Persetujuan Pendaftaran')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white mb-2">Persetujuan Pendaftaran</h1>
        <p class="text-slate-400">Kelola pendaftaran pengguna baru yang menunggu persetujuan</p>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="bg-green-500/20 border border-green-500/50 text-green-300 p-4 mb-6 rounded-xl flex items-center">
            <i class="fas fa-check-circle mr-3 text-lg"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-500/20 border border-red-500/50 text-red-300 p-4 mb-6 rounded-xl flex items-center">
            <i class="fas fa-exclamation-circle mr-3 text-lg"></i>
            {{ session('error') }}
        </div>
    @endif

    <!-- Stats Card -->
    <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-6 mb-6 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-amber-100 text-sm">Menunggu Persetujuan</p>
                <p class="text-white text-3xl font-bold mt-1">{{ $pendingUsers->total() }}</p>
            </div>
            <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                <i class="fas fa-user-clock text-white text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Pending Users Table -->
    <div class="bg-slate-800/50 backdrop-blur-xl rounded-2xl border border-slate-700/50 overflow-hidden">
        @if($pendingUsers->isEmpty())
            <div class="p-12 text-center">
                <div class="w-20 h-20 bg-slate-700/50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user-check text-slate-500 text-3xl"></i>
                </div>
                <h3 class="text-slate-400 text-lg font-medium mb-2">Tidak Ada Pendaftaran</h3>
                <p class="text-slate-500">Semua pendaftaran sudah diproses.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-700/50">
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">Nama</th>
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">Email</th>
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">No. HP</th>
                            <th class="text-left text-slate-300 font-semibold px-6 py-4">Tanggal Daftar</th>
                            <th class="text-center text-slate-300 font-semibold px-6 py-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                        @foreach($pendingUsers as $user)
                            <tr class="hover:bg-slate-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold mr-3">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <span class="text-white font-medium">{{ $user->name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-300">{{ $user->email }}</td>
                                <td class="px-6 py-4 text-slate-300">{{ $user->phone ?? '-' }}</td>
                                <td class="px-6 py-4 text-slate-400">{{ $user->created_at->format('d M Y, H:i') }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center space-x-2">
                                        <form action="{{ route('users.approve', $user->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="bg-green-500/20 text-green-400 hover:bg-green-500/30 px-4 py-2 rounded-lg transition-colors flex items-center" onclick="return confirm('Setujui pendaftaran {{ $user->name }}?')">
                                                <i class="fas fa-check mr-2"></i>
                                                Setujui
                                            </button>
                                        </form>
                                        <form action="{{ route('users.reject', $user->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="bg-red-500/20 text-red-400 hover:bg-red-500/30 px-4 py-2 rounded-lg transition-colors flex items-center" onclick="return confirm('Tolak dan hapus pendaftaran {{ $user->name }}? Aksi ini tidak dapat dibatalkan.')">
                                                <i class="fas fa-times mr-2"></i>
                                                Tolak
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($pendingUsers->hasPages())
                <div class="px-6 py-4 border-t border-slate-700/50">
                    {{ $pendingUsers->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
