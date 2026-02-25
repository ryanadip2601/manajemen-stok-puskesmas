<!DOCTYPE html>
<html lang="id" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIMBAR') - Sistem Manajemen Barang</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        light: {
                            bg: '#f8fafc',
                            card: '#ffffff',
                            sidebar: '#1e293b',
                            text: '#1e293b',
                            muted: '#64748b'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        .dark ::-webkit-scrollbar-track { background: #1e293b; }
        .dark ::-webkit-scrollbar-thumb { background: #475569; border-radius: 4px; }
        .dark ::-webkit-scrollbar-thumb:hover { background: #64748b; }
        ::-webkit-scrollbar-track { background: #e2e8f0; }
        ::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #64748b; }
        .card-hover { transform: translateY(0); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); }
        .btn-glow:hover { box-shadow: 0 0 20px rgba(59, 130, 246, 0.5); }
        .sidebar-link { position: relative; overflow: hidden; }
        .sidebar-link::before { content: ''; position: absolute; left: 0; top: 0; height: 100%; width: 0; background: linear-gradient(90deg, rgba(255,255,255,0.1), transparent); transition: width 0.3s ease; }
        .sidebar-link:hover::before { width: 100%; }
        .gradient-text { background: linear-gradient(135deg, #34d399, #06b6d4); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .pulse { animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .dark .bg-pattern { background-color: #0f172a; background-image: radial-gradient(at 47% 33%, hsl(220, 50%, 15%) 0, transparent 59%), radial-gradient(at 82% 65%, hsl(240, 50%, 12%) 0, transparent 55%); }
        .bg-pattern { background-color: #f1f5f9; background-image: radial-gradient(at 47% 33%, hsl(220, 30%, 96%) 0, transparent 59%), radial-gradient(at 82% 65%, hsl(240, 30%, 96%) 0, transparent 55%); }
        .theme-toggle { transition: all 0.3s ease; }
        .theme-toggle:hover { transform: rotate(180deg); }
        [x-cloak] { display: none !important; }
        .rotate-180 { transform: rotate(180deg); }
        
        /* Sidebar Minimize Styles */
        .sidebar-minimized .sidebar-link { justify-content: center; padding-left: 0.5rem; padding-right: 0.5rem; }
        .sidebar-minimized .sidebar-link span:not(.badge) { display: none; }
        .sidebar-minimized .menu-header { justify-content: center; }
        .sidebar-minimized .menu-header span { display: none; }
        .sidebar-minimized .menu-header i.fa-chevron-down { display: none; }
        
        /* Tooltip for minimized sidebar */
        [data-tooltip] { position: relative; }
        [data-tooltip]:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-left: 10px;
            padding: 6px 12px;
            background: #1e293b;
            color: white;
            font-size: 12px;
            white-space: nowrap;
            border-radius: 6px;
            z-index: 100;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body class="dark:bg-slate-900 bg-slate-100 transition-colors duration-300" x-data="{ sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false' }" x-init="$watch('sidebarOpen', val => localStorage.setItem('sidebarOpen', val))">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside :class="[sidebarOpen ? 'w-72' : 'w-20 sidebar-minimized']" class="bg-gradient-to-b from-slate-800 via-slate-850 to-slate-900 text-white fixed h-full shadow-2xl border-r border-slate-700/50 transition-all duration-300 z-20">
            <!-- Logo Section -->
            <div class="p-4 border-b border-slate-700/50 relative">
                <!-- Toggle Button -->
                <button @click="sidebarOpen = !sidebarOpen" 
                    class="absolute -right-3 top-1/2 -translate-y-1/2 w-6 h-6 bg-slate-700 hover:bg-slate-600 rounded-full flex items-center justify-center shadow-lg border border-slate-600 transition-all z-30 group">
                    <i class="fas fa-chevron-left text-white text-xs transition-transform duration-300" :class="{ 'rotate-180': !sidebarOpen }"></i>
                </button>
                
                <div class="flex flex-col items-center text-center">
                    <div :class="sidebarOpen ? 'w-16 h-16 rounded-2xl' : 'w-12 h-12 rounded-xl'" class="bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/20 transition-all duration-300" :class="sidebarOpen ? 'mb-3' : 'mb-0'">
                        <i class="fas fa-boxes-stacked text-white" :class="sidebarOpen ? 'text-2xl' : 'text-lg'"></i>
                    </div>
                    <template x-if="sidebarOpen">
                        <div>
                            <h1 class="text-2xl font-black gradient-text tracking-wide">SIMBAR</h1>
                            <p class="text-xs text-slate-400 mt-1">Sistem Manajemen Barang</p>
                            <p class="text-xs text-slate-500">Puskesmas Karang Rejo</p>
                        </div>
                    </template>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="mt-4 overflow-y-auto transition-all duration-300" :class="sidebarOpen ? 'px-3' : 'px-2'" style="max-height: calc(100vh - 280px);" x-data="{ 
                menuUtama: {{ request()->routeIs('dashboard') || request()->routeIs('items.*') || request()->routeIs('categories.*') ? 'true' : 'false' }},
                menuUser: {{ request()->routeIs('users.*') ? 'true' : 'false' }},
                menuAset: {{ request()->routeIs('barang-aset.*') || request()->routeIs('ruangan.*') || request()->routeIs('kategori-aset.*') || request()->routeIs('pemeliharaan.*') || request()->routeIs('penyusutan.*') ? 'true' : 'false' }},
                menuAsetStaff: {{ request()->routeIs('staff.pemeliharaan.*') || request()->routeIs('staff.barang-aset.*') ? 'true' : 'false' }},
                menuTransaksi: {{ request()->routeIs('stock-in.*') || request()->routeIs('stock-out.*') ? 'true' : 'false' }},
                menuChecklist: {{ request()->routeIs('checklist.*') || request()->routeIs('usulan-penerimaan.admin.*') || request()->routeIs('permintaan-barang.admin.*') ? 'true' : 'false' }},
                menuPegawai: {{ request()->routeIs('stock-request.staff.*') ? 'true' : 'false' }},
                menuUsulan: {{ request()->routeIs('usulan-penerimaan.*') && !request()->routeIs('usulan-penerimaan.admin.*') ? 'true' : 'false' }},
                menuPermintaan: {{ request()->routeIs('permintaan-barang.*') && !request()->routeIs('permintaan-barang.admin.*') ? 'true' : 'false' }},
                menuLaporan: {{ request()->routeIs('reports.*') ? 'true' : 'false' }}
            }">
                <!-- Menu Utama - Semua User -->
                <div class="mb-2">
                    <button @click="menuUtama = !menuUtama" class="w-full flex items-center justify-between px-4 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider hover:text-white transition-colors group" :class="{ 'justify-center': !sidebarOpen }">
                        <span class="flex items-center">
                            <i class="fas fa-th-large text-blue-400" :class="sidebarOpen ? 'mr-2' : 'mr-0'"></i>
                            <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">Menu Utama</span>
                        </span>
                        <i x-show="sidebarOpen" class="fas fa-chevron-down text-slate-500 group-hover:text-white transition-transform duration-300" :class="{ 'rotate-180': menuUtama }"></i>
                    </button>
                    <div x-show="menuUtama || !sidebarOpen" x-collapse x-cloak class="mt-1 space-y-1">
                        <a href="{{ route('dashboard') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-blue-600/80 to-blue-700/80 shadow-lg shadow-blue-500/20' : '' }}" :class="{ 'justify-center px-2': !sidebarOpen }" :title="!sidebarOpen ? 'Dashboard' : ''">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-blue-500/20 {{ request()->routeIs('dashboard') ? 'bg-blue-500/30' : '' }}">
                                <i class="fas fa-home text-blue-400 text-sm"></i>
                            </div>
                            <span x-show="sidebarOpen" x-transition class="ml-3 font-medium text-sm whitespace-nowrap">Dashboard</span>
                        </a>
                        
                        <a href="{{ route('items.index') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('items.*') && !request()->routeIs('items.scan') ? 'bg-gradient-to-r from-emerald-600/80 to-emerald-700/80 shadow-lg shadow-emerald-500/20' : '' }}" :class="{ 'justify-center px-2': !sidebarOpen }" :title="!sidebarOpen ? 'Data Barang' : ''">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-emerald-500/20 {{ request()->routeIs('items.*') && !request()->routeIs('items.scan') ? 'bg-emerald-500/30' : '' }}">
                                <i class="fas fa-box text-emerald-400 text-sm"></i>
                            </div>
                            <span x-show="sidebarOpen" x-transition class="ml-3 font-medium text-sm whitespace-nowrap">Data Barang</span>
                        </a>

                        {{-- Kategori - Admin & Admin Gudang --}}
                        @if(auth()->user()->canManageWarehouse())
                        <a href="{{ route('categories.index') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('categories.*') ? 'bg-gradient-to-r from-purple-600/80 to-purple-700/80 shadow-lg shadow-purple-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-purple-500/20 {{ request()->routeIs('categories.*') ? 'bg-purple-500/30' : '' }}">
                                <i class="fas fa-folder text-purple-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Kategori</span>
                        </a>
                        @endif

                        {{-- Persetujuan User - Admin Only --}}
                        @if(auth()->user()->isAdmin())
                        @php
                            $pendingCount = \App\Models\User::where('is_approved', false)->count();
                        @endphp
                        <a href="{{ route('users.pending') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('users.pending') ? 'bg-gradient-to-r from-amber-600/80 to-orange-700/80 shadow-lg shadow-amber-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-amber-500/20 {{ request()->routeIs('users.pending') ? 'bg-amber-500/30' : '' }}">
                                <i class="fas fa-user-clock text-amber-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Persetujuan User</span>
                            @if($pendingCount > 0)
                                <span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full animate-pulse">{{ $pendingCount }}</span>
                            @endif
                        </a>
                        @endif
                    </div>
                </div>

                {{-- Menu Manajemen User - Admin Only --}}
                @if(auth()->user()->isAdmin())
                <div class="mb-2">
                    <button @click="menuUser = !menuUser" class="w-full flex items-center justify-between px-4 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider hover:text-white transition-colors group">
                        <span class="flex items-center">
                            <i class="fas fa-users-cog mr-2 text-rose-400"></i>
                            Manajemen User
                        </span>
                        <i class="fas fa-chevron-down text-slate-500 group-hover:text-white transition-transform duration-300" :class="{ 'rotate-180': menuUser }"></i>
                    </button>
                    <div x-show="menuUser" x-collapse x-cloak class="mt-1 space-y-1">
                        <a href="{{ route('users.index') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('users.index') || request()->routeIs('users.show') || request()->routeIs('users.edit-role') ? 'bg-gradient-to-r from-rose-600/80 to-pink-700/80 shadow-lg shadow-rose-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-rose-500/20 {{ request()->routeIs('users.index') || request()->routeIs('users.show') || request()->routeIs('users.edit-role') ? 'bg-rose-500/30' : '' }}">
                                <i class="fas fa-users text-rose-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Daftar User</span>
                        </a>

                        <a href="{{ route('users.login-history') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('users.login-history') ? 'bg-gradient-to-r from-indigo-600/80 to-violet-700/80 shadow-lg shadow-indigo-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-indigo-500/20 {{ request()->routeIs('users.login-history') ? 'bg-indigo-500/30' : '' }}">
                                <i class="fas fa-history text-indigo-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Login History</span>
                        </a>

                        <a href="{{ route('users.activity-logs') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('users.activity-logs') ? 'bg-gradient-to-r from-teal-600/80 to-cyan-700/80 shadow-lg shadow-teal-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-teal-500/20 {{ request()->routeIs('users.activity-logs') ? 'bg-teal-500/30' : '' }}">
                                <i class="fas fa-list-alt text-teal-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Activity Logs</span>
                        </a>
                    </div>
                </div>
                @endif

                {{-- Menu Aset Per Ruangan - Admin & Admin Gudang --}}
                @if(auth()->user()->canManageWarehouse())
                <div class="mb-2">
                    <button @click="menuAset = !menuAset" class="w-full flex items-center justify-between px-4 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider hover:text-white transition-colors group">
                        <span class="flex items-center">
                            <i class="fas fa-building mr-2 text-cyan-400"></i>
                            Aset Per Ruangan
                        </span>
                        <i class="fas fa-chevron-down text-slate-500 group-hover:text-white transition-transform duration-300" :class="{ 'rotate-180': menuAset }"></i>
                    </button>
                    <div x-show="menuAset" x-collapse x-cloak class="mt-1 space-y-1">
                        <a href="{{ route('barang-aset.index') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('barang-aset.*') ? 'bg-gradient-to-r from-cyan-600/80 to-blue-700/80 shadow-lg shadow-cyan-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-cyan-500/20 {{ request()->routeIs('barang-aset.*') ? 'bg-cyan-500/30' : '' }}">
                                <i class="fas fa-boxes text-cyan-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Barang Aset</span>
                        </a>

                        <a href="{{ route('ruangan.index') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('ruangan.*') ? 'bg-gradient-to-r from-blue-600/80 to-indigo-700/80 shadow-lg shadow-blue-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-blue-500/20 {{ request()->routeIs('ruangan.*') ? 'bg-blue-500/30' : '' }}">
                                <i class="fas fa-door-open text-blue-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Ruangan</span>
                        </a>

                        <a href="{{ route('kategori-aset.index') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('kategori-aset.*') ? 'bg-gradient-to-r from-indigo-600/80 to-violet-700/80 shadow-lg shadow-indigo-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-indigo-500/20 {{ request()->routeIs('kategori-aset.*') ? 'bg-indigo-500/30' : '' }}">
                                <i class="fas fa-tags text-indigo-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Kategori Aset</span>
                        </a>

                        @php
                            $pendingMaintenance = \App\Models\PemeliharaanAset::whereIn('status', ['menunggu', 'proses'])->count();
                        @endphp
                        <a href="{{ route('pemeliharaan.index') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('pemeliharaan.*') ? 'bg-gradient-to-r from-purple-600/80 to-fuchsia-700/80 shadow-lg shadow-purple-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-purple-500/20 {{ request()->routeIs('pemeliharaan.*') ? 'bg-purple-500/30' : '' }}">
                                <i class="fas fa-tools text-purple-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Pemeliharaan</span>
                            @if($pendingMaintenance > 0)
                                <span class="ml-auto bg-amber-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $pendingMaintenance }}</span>
                            @endif
                        </a>

                        <a href="{{ route('penyusutan.index') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('penyusutan.*') ? 'bg-gradient-to-r from-amber-600/80 to-yellow-700/80 shadow-lg shadow-amber-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-amber-500/20 {{ request()->routeIs('penyusutan.*') ? 'bg-amber-500/30' : '' }}">
                                <i class="fas fa-calculator text-amber-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Penyusutan</span>
                        </a>
                    </div>
                </div>
                @endif

                {{-- Menu Transaksi - Admin & Admin Gudang --}}
                @if(auth()->user()->canManageWarehouse())
                <div class="mb-2">
                    <button @click="menuTransaksi = !menuTransaksi" class="w-full flex items-center justify-between px-4 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider hover:text-white transition-colors group">
                        <span class="flex items-center">
                            <i class="fas fa-exchange-alt mr-2 text-green-400"></i>
                            Transaksi
                        </span>
                        <i class="fas fa-chevron-down text-slate-500 group-hover:text-white transition-transform duration-300" :class="{ 'rotate-180': menuTransaksi }"></i>
                    </button>
                    <div x-show="menuTransaksi" x-collapse x-cloak class="mt-1 space-y-1">
                        <a href="{{ route('stock-in.index') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('stock-in.*') ? 'bg-gradient-to-r from-green-600/80 to-green-700/80 shadow-lg shadow-green-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-green-500/20 {{ request()->routeIs('stock-in.*') ? 'bg-green-500/30' : '' }}">
                                <i class="fas fa-arrow-down text-green-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Barang Masuk</span>
                        </a>
                        
                        <a href="{{ route('stock-out.index') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('stock-out.*') && !request()->routeIs('stock-out.staff.*') ? 'bg-gradient-to-r from-red-600/80 to-red-700/80 shadow-lg shadow-red-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-red-500/20 {{ request()->routeIs('stock-out.*') && !request()->routeIs('stock-out.staff.*') ? 'bg-red-500/30' : '' }}">
                                <i class="fas fa-arrow-up text-red-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Barang Keluar</span>
                        </a>
                    </div>
                </div>

                <!-- Menu Checklist - Admin & Admin Gudang -->
                @php
                    $totalChecklistBadge = \App\Models\StockOutRequest::where('status', 'pending')->count() 
                        + \App\Models\UsulanPenerimaanBarang::menungguVerifikasi()->count()
                        + \App\Models\PemeliharaanAset::where('status', 'menunggu')->count()
                        + \App\Models\PermintaanBarang::menungguVerifikasi()->count();
                @endphp
                <div class="mb-2">
                    <button @click="menuChecklist = !menuChecklist" class="w-full flex items-center justify-between px-4 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider hover:text-white transition-colors group">
                        <span class="flex items-center">
                            <i class="fas fa-clipboard-check mr-2 text-pink-400"></i>
                            Checklist
                            @if($totalChecklistBadge > 0)
                                <span class="ml-2 bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full animate-pulse">{{ $totalChecklistBadge }}</span>
                            @endif
                        </span>
                        <i class="fas fa-chevron-down text-slate-500 group-hover:text-white transition-transform duration-300" :class="{ 'rotate-180': menuChecklist }"></i>
                    </button>
                    <div x-show="menuChecklist" x-collapse x-cloak class="mt-1 space-y-1">
                        @php
                            $pendingRequestCount = \App\Models\StockOutRequest::where('status', 'pending')->count();
                            $approvedRequestCount = \App\Models\StockOutRequest::where('status', 'approved')->count();
                            $pendingUsulanCount = \App\Models\UsulanPenerimaanBarang::menungguVerifikasi()->count();
                            $pendingPemeliharaanCount = \App\Models\PemeliharaanAset::where('status', 'menunggu')->count();
                            $pendingPermintaanBarangCount = \App\Models\PermintaanBarang::menungguVerifikasi()->count();
                            $totalPendingChecklist = $pendingRequestCount + $pendingUsulanCount + $pendingPemeliharaanCount + $pendingPermintaanBarangCount;
                        @endphp
                        <a href="{{ route('checklist.index', ['status' => 'pending']) }}" id="sidebar-checklist-link" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('checklist.*') ? 'bg-gradient-to-r from-pink-600/80 to-rose-700/80 shadow-lg shadow-pink-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-pink-500/20 {{ request()->routeIs('checklist.*') ? 'bg-pink-500/30' : '' }}">
                                <i class="fas fa-tasks text-pink-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Permintaan Barang</span>
                            <span id="pending-request-badge" class="ml-auto bg-yellow-500 text-white text-xs font-bold px-2 py-0.5 rounded-full {{ $pendingRequestCount > 0 ? 'animate-pulse' : 'hidden' }}">{{ $pendingRequestCount }}</span>
                        </a>

                        <a href="{{ route('usulan-penerimaan.admin.index') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('usulan-penerimaan.admin.*') ? 'bg-gradient-to-r from-teal-600/80 to-cyan-700/80 shadow-lg shadow-teal-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-teal-500/20 {{ request()->routeIs('usulan-penerimaan.admin.*') ? 'bg-teal-500/30' : '' }}">
                                <i class="fas fa-file-import text-teal-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Usulan Permintaan Barang Aset</span>
                            @if($pendingUsulanCount > 0)
                                <span class="ml-auto bg-teal-500 text-white text-xs font-bold px-2 py-0.5 rounded-full animate-pulse">{{ $pendingUsulanCount }}</span>
                            @endif
                        </a>

                        <a href="{{ route('permintaan-barang.admin.index') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('permintaan-barang.admin.*') ? 'bg-gradient-to-r from-orange-600/80 to-amber-700/80 shadow-lg shadow-orange-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-orange-500/20 {{ request()->routeIs('permintaan-barang.admin.*') ? 'bg-orange-500/30' : '' }}">
                                <i class="fas fa-hand-holding-medical text-orange-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Permintaan Barang</span>
                            @if($pendingPermintaanBarangCount > 0)
                                <span class="ml-auto bg-orange-500 text-white text-xs font-bold px-2 py-0.5 rounded-full animate-pulse">{{ $pendingPermintaanBarangCount }}</span>
                            @endif
                        </a>

                        <a href="{{ route('pemeliharaan.index', ['status' => 'menunggu']) }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('pemeliharaan.*') && !request()->routeIs('staff.pemeliharaan.*') ? 'bg-gradient-to-r from-purple-600/80 to-fuchsia-700/80 shadow-lg shadow-purple-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-purple-500/20 {{ request()->routeIs('pemeliharaan.*') && !request()->routeIs('staff.pemeliharaan.*') ? 'bg-purple-500/30' : '' }}">
                                <i class="fas fa-wrench text-purple-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Perbaikan Barang</span>
                            @if($pendingPemeliharaanCount > 0)
                                <span class="ml-auto bg-purple-500 text-white text-xs font-bold px-2 py-0.5 rounded-full animate-pulse">{{ $pendingPemeliharaanCount }}</span>
                            @endif
                        </a>
                    </div>
                </div>

                @endif

                {{-- Menu Pegawai - Staff Only --}}
                @if(auth()->user()->isStaff())
                <div class="mb-2">
                    <button @click="menuPegawai = !menuPegawai" class="w-full flex items-center justify-between px-4 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider hover:text-white transition-colors group">
                        <span class="flex items-center">
                            <i class="fas fa-hand-holding-box mr-2 text-orange-400"></i>
                            Pengambilan Barang
                        </span>
                        <i class="fas fa-chevron-down text-slate-500 group-hover:text-white transition-transform duration-300" :class="{ 'rotate-180': menuPegawai }"></i>
                    </button>
                    <div x-show="menuPegawai" x-collapse x-cloak class="mt-1 space-y-1">
                        <a href="{{ route('stock-request.staff.index') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('stock-request.staff.index') ? 'bg-gradient-to-r from-orange-600/80 to-orange-700/80 shadow-lg shadow-orange-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-orange-500/20 {{ request()->routeIs('stock-request.staff.index') ? 'bg-orange-500/30' : '' }}">
                                <i class="fas fa-list text-orange-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Riwayat Permintaan</span>
                        </a>

                        <a href="{{ route('stock-request.staff.create') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('stock-request.staff.create') ? 'bg-gradient-to-r from-teal-600/80 to-teal-700/80 shadow-lg shadow-teal-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-teal-500/20 {{ request()->routeIs('stock-request.staff.create') ? 'bg-teal-500/30' : '' }}">
                                <i class="fas fa-plus text-teal-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Ajukan Pengambilan</span>
                        </a>
                    </div>
                </div>

                {{-- Menu Barang Aset - Staff (Hanya Pemeliharaan) --}}
                <div class="mb-2">
                    <button @click="menuAsetStaff = !menuAsetStaff" class="w-full flex items-center justify-between px-4 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider hover:text-white transition-colors group">
                        <span class="flex items-center">
                            <i class="fas fa-building mr-2 text-cyan-400"></i>
                            Barang Aset
                        </span>
                        <i class="fas fa-chevron-down text-slate-500 group-hover:text-white transition-transform duration-300" :class="{ 'rotate-180': menuAsetStaff }"></i>
                    </button>
                    <div x-show="menuAsetStaff" x-collapse x-cloak class="mt-1 space-y-1">
                        {{-- Lihat Daftar Aset (Read Only) --}}
                        <a href="{{ route('staff.barang-aset.index') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('staff.barang-aset.*') ? 'bg-gradient-to-r from-cyan-600/80 to-blue-700/80 shadow-lg shadow-cyan-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-cyan-500/20 {{ request()->routeIs('staff.barang-aset.*') ? 'bg-cyan-500/30' : '' }}">
                                <i class="fas fa-boxes text-cyan-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Daftar Aset</span>
                        </a>

                        {{-- Pemeliharaan --}}
                        @php
                            $myMaintenanceCount = \App\Models\PemeliharaanAset::where('created_by', auth()->id())
                                ->whereIn('status', ['menunggu', 'proses'])->count();
                        @endphp
                        <a href="{{ route('staff.pemeliharaan.index') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('staff.pemeliharaan.index') || request()->routeIs('staff.pemeliharaan.show') ? 'bg-gradient-to-r from-purple-600/80 to-fuchsia-700/80 shadow-lg shadow-purple-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-purple-500/20 {{ request()->routeIs('staff.pemeliharaan.index') || request()->routeIs('staff.pemeliharaan.show') ? 'bg-purple-500/30' : '' }}">
                                <i class="fas fa-tools text-purple-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Pemeliharaan</span>
                            @if($myMaintenanceCount > 0)
                                <span class="ml-auto bg-amber-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $myMaintenanceCount }}</span>
                            @endif
                        </a>

                        {{-- Ajukan Pemeliharaan Baru --}}
                        <a href="{{ route('staff.pemeliharaan.create') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('staff.pemeliharaan.create') ? 'bg-gradient-to-r from-emerald-600/80 to-teal-700/80 shadow-lg shadow-emerald-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-emerald-500/20 {{ request()->routeIs('staff.pemeliharaan.create') ? 'bg-emerald-500/30' : '' }}">
                                <i class="fas fa-plus-circle text-emerald-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Ajukan Pemeliharaan</span>
                        </a>
                    </div>
                </div>
                @endif

                {{-- Menu Usulan Permintaan Barang Aset - Semua User --}}
                <div class="mb-2">
                    <button @click="menuUsulan = !menuUsulan" class="w-full flex items-center justify-between px-4 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider hover:text-white transition-colors group">
                        <span class="flex items-center">
                            <i class="fas fa-file-import mr-2 text-teal-400"></i>
                            Usulan Permintaan Barang Aset
                        </span>
                        <i class="fas fa-chevron-down text-slate-500 group-hover:text-white transition-transform duration-300" :class="{ 'rotate-180': menuUsulan }"></i>
                    </button>
                    <div x-show="menuUsulan" x-collapse x-cloak class="mt-1 space-y-1">
                        @php
                            $myUsulanPendingCount = \App\Models\UsulanPenerimaanBarang::byUser(auth()->id())->whereIn('status', ['pending', 'revisi'])->count();
                        @endphp
                        <a href="{{ route('usulan-penerimaan.index') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('usulan-penerimaan.index') || request()->routeIs('usulan-penerimaan.show') ? 'bg-gradient-to-r from-teal-600/80 to-cyan-700/80 shadow-lg shadow-teal-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-teal-500/20 {{ request()->routeIs('usulan-penerimaan.index') || request()->routeIs('usulan-penerimaan.show') ? 'bg-teal-500/30' : '' }}">
                                <i class="fas fa-list text-teal-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Daftar Usulan Saya</span>
                            @if($myUsulanPendingCount > 0)
                                <span class="ml-auto bg-amber-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $myUsulanPendingCount }}</span>
                            @endif
                        </a>

                        <a href="{{ route('usulan-penerimaan.create') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('usulan-penerimaan.create') ? 'bg-gradient-to-r from-emerald-600/80 to-green-700/80 shadow-lg shadow-emerald-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-emerald-500/20 {{ request()->routeIs('usulan-penerimaan.create') ? 'bg-emerald-500/30' : '' }}">
                                <i class="fas fa-plus-circle text-emerald-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Buat Usulan Baru</span>
                        </a>
                    </div>
                </div>

                {{-- Menu Permintaan Barang BHP - Semua User --}}
                <div class="mb-2">
                    <button @click="menuPermintaan = !menuPermintaan" class="w-full flex items-center justify-between px-4 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider hover:text-white transition-colors group">
                        <span class="flex items-center">
                            <i class="fas fa-hand-holding-medical mr-2 text-orange-400"></i>
                            Permintaan Barang BHP
                        </span>
                        <i class="fas fa-chevron-down text-slate-500 group-hover:text-white transition-transform duration-300" :class="{ 'rotate-180': menuPermintaan }"></i>
                    </button>
                    <div x-show="menuPermintaan" x-collapse x-cloak class="mt-1 space-y-1">
                        @php
                            $myPermintaanBarangPendingCount = \App\Models\PermintaanBarang::byUser(auth()->id())->whereIn('status', ['pending', 'revisi'])->count();
                            $myPermintaanBarangTersediaCount = \App\Models\PermintaanBarang::byUser(auth()->id())->tersedia()->count();
                        @endphp
                        <a href="{{ route('permintaan-barang.index') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('permintaan-barang.index') || request()->routeIs('permintaan-barang.show') ? 'bg-gradient-to-r from-orange-600/80 to-amber-700/80 shadow-lg shadow-orange-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-orange-500/20 {{ request()->routeIs('permintaan-barang.index') || request()->routeIs('permintaan-barang.show') ? 'bg-orange-500/30' : '' }}">
                                <i class="fas fa-list text-orange-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Daftar Permintaan Saya</span>
                            @if($myPermintaanBarangPendingCount > 0)
                                <span class="ml-auto bg-amber-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $myPermintaanBarangPendingCount }}</span>
                            @endif
                            @if($myPermintaanBarangTersediaCount > 0)
                                <span class="ml-auto bg-emerald-500 text-white text-xs font-bold px-2 py-0.5 rounded-full animate-pulse">{{ $myPermintaanBarangTersediaCount }}</span>
                            @endif
                        </a>

                        <a href="{{ route('permintaan-barang.create') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('permintaan-barang.create') ? 'bg-gradient-to-r from-emerald-600/80 to-green-700/80 shadow-lg shadow-emerald-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-emerald-500/20 {{ request()->routeIs('permintaan-barang.create') ? 'bg-emerald-500/30' : '' }}">
                                <i class="fas fa-plus-circle text-emerald-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Ajukan Permintaan</span>
                        </a>
                    </div>
                </div>

                {{-- Menu Laporan - Admin Only --}}
                @if(auth()->user()->isAdmin())
                <div class="mb-2">
                    <button @click="menuLaporan = !menuLaporan" class="w-full flex items-center justify-between px-4 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider hover:text-white transition-colors group">
                        <span class="flex items-center">
                            <i class="fas fa-file-alt mr-2 text-cyan-400"></i>
                            Laporan
                        </span>
                        <i class="fas fa-chevron-down text-slate-500 group-hover:text-white transition-transform duration-300" :class="{ 'rotate-180': menuLaporan }"></i>
                    </button>
                    <div x-show="menuLaporan" x-collapse x-cloak class="mt-1 space-y-1">
                        <a href="{{ route('reports.index') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('reports.index') ? 'bg-gradient-to-r from-cyan-600/80 to-cyan-700/80 shadow-lg shadow-cyan-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-cyan-500/20 {{ request()->routeIs('reports.index') ? 'bg-cyan-500/30' : '' }}">
                                <i class="fas fa-th-large text-cyan-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Semua Laporan</span>
                        </a>

                        <a href="{{ route('reports.barang-masuk') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('reports.barang-masuk') ? 'bg-gradient-to-r from-green-600/80 to-green-700/80 shadow-lg shadow-green-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-green-500/20 {{ request()->routeIs('reports.barang-masuk') ? 'bg-green-500/30' : '' }}">
                                <i class="fas fa-arrow-down text-green-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Barang Masuk</span>
                        </a>

                        <a href="{{ route('reports.barang-keluar') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('reports.barang-keluar') ? 'bg-gradient-to-r from-red-600/80 to-red-700/80 shadow-lg shadow-red-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-red-500/20 {{ request()->routeIs('reports.barang-keluar') ? 'bg-red-500/30' : '' }}">
                                <i class="fas fa-arrow-up text-red-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Barang Keluar</span>
                        </a>

                        <a href="{{ route('reports.pengambilan-barang') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('reports.pengambilan-barang') ? 'bg-gradient-to-r from-amber-600/80 to-amber-700/80 shadow-lg shadow-amber-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-amber-500/20 {{ request()->routeIs('reports.pengambilan-barang') ? 'bg-amber-500/30' : '' }}">
                                <i class="fas fa-hand-holding text-amber-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Pengambilan Barang</span>
                        </a>

                        <a href="{{ route('reports.permintaan-bhp') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('reports.permintaan-bhp') ? 'bg-gradient-to-r from-purple-600/80 to-purple-700/80 shadow-lg shadow-purple-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-purple-500/20 {{ request()->routeIs('reports.permintaan-bhp') ? 'bg-purple-500/30' : '' }}">
                                <i class="fas fa-clipboard-list text-purple-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Permintaan BHP</span>
                        </a>

                        <a href="{{ route('reports.usulan-aset') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('reports.usulan-aset') ? 'bg-gradient-to-r from-blue-600/80 to-blue-700/80 shadow-lg shadow-blue-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-blue-500/20 {{ request()->routeIs('reports.usulan-aset') ? 'bg-blue-500/30' : '' }}">
                                <i class="fas fa-file-signature text-blue-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Usulan Aset</span>
                        </a>

                        <a href="{{ route('reports.pemeliharaan') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('reports.pemeliharaan') ? 'bg-gradient-to-r from-orange-600/80 to-orange-700/80 shadow-lg shadow-orange-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-orange-500/20 {{ request()->routeIs('reports.pemeliharaan') ? 'bg-orange-500/30' : '' }}">
                                <i class="fas fa-tools text-orange-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Pemeliharaan</span>
                        </a>

                        <a href="{{ route('reports.monthly') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('reports.monthly') ? 'bg-gradient-to-r from-cyan-600/80 to-cyan-700/80 shadow-lg shadow-cyan-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-cyan-500/20 {{ request()->routeIs('reports.monthly') ? 'bg-cyan-500/30' : '' }}">
                                <i class="fas fa-calendar-alt text-cyan-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Laporan Bulanan</span>
                        </a>

                        <a href="{{ route('reports.yearly') }}" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('reports.yearly') ? 'bg-gradient-to-r from-indigo-600/80 to-indigo-700/80 shadow-lg shadow-indigo-500/20' : '' }}">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-indigo-500/20 {{ request()->routeIs('reports.yearly') ? 'bg-indigo-500/30' : '' }}">
                                <i class="fas fa-chart-line text-indigo-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Laporan Tahunan</span>
                        </a>
                    </div>
                </div>
                @endif
            </nav>

            <!-- User Profile at Bottom -->
            <div class="absolute bottom-0 p-4 border-t border-slate-700/50 bg-slate-800/50 transition-all duration-300" :class="sidebarOpen ? 'w-72' : 'w-20'">
                <a href="{{ route('profile') }}" class="flex items-center p-3 rounded-xl bg-slate-700/30 hover:bg-slate-700/50 transition-all group" :class="{ 'justify-center p-2': !sidebarOpen }" :title="!sidebarOpen ? '{{ auth()->user()->name }}' : ''">
                    <div :class="sidebarOpen ? 'w-12 h-12' : 'w-10 h-10'" class="{{ auth()->user()->getRoleBadgeClass() }} rounded-full flex items-center justify-center shadow-lg transition-all">
                        <i class="fas fa-user text-white" :class="sidebarOpen ? 'text-lg' : 'text-sm'"></i>
                    </div>
                    <template x-if="sidebarOpen">
                        <div class="ml-3 flex-1">
                            <p class="font-semibold text-white text-sm group-hover:text-blue-400 transition">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-400">
                                @if(auth()->user()->isAdmin())
                                    <i class="fas fa-crown text-yellow-400 mr-1"></i>Administrator
                                @elseif(auth()->user()->isAdminGudang())
                                    <i class="fas fa-warehouse text-blue-400 mr-1"></i>Admin Gudang
                                @else
                                    <i class="fas fa-user-tie text-green-400 mr-1"></i>Pegawai
                                @endif
                            </p>
                        </div>
                    </template>
                    <i x-show="sidebarOpen" class="fas fa-cog text-slate-500 group-hover:text-white group-hover:rotate-90 transition-all"></i>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 w-full min-w-0 bg-pattern min-h-screen transition-all duration-300 overflow-x-hidden" :class="sidebarOpen ? 'ml-72' : 'ml-20'">
            <!-- Top Header -->
            <header class="dark:bg-slate-800/80 bg-white/80 backdrop-blur-xl shadow-lg dark:border-b dark:border-slate-700/50 border-b border-slate-200 sticky top-0 z-10 transition-colors duration-300">
                <div class="px-8 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <!-- Toggle Sidebar Button -->
                        <button @click="sidebarOpen = !sidebarOpen" 
                            class="w-10 h-10 rounded-xl dark:bg-slate-700/50 bg-slate-100 flex items-center justify-center hover:bg-slate-200 dark:hover:bg-slate-700 transition-all group"
                            title="Toggle Sidebar">
                            <i class="fas fa-bars text-slate-500 group-hover:text-slate-700 dark:group-hover:text-white transition-colors"></i>
                        </button>
                        <div>
                            <h2 class="text-2xl font-bold dark:text-white text-slate-800">@yield('header', 'Dashboard')</h2>
                            <p class="text-sm dark:text-slate-400 text-slate-500">@yield('breadcrumb')</p>
                        </div>
                        
                        <!-- Traktir Kopi Button -->
                        <div x-data="{ showKopi: false, copied: false }" class="relative">
                            <button 
                                @click="showKopi = !showKopi"
                                class="group flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-amber-500 via-orange-500 to-amber-500 bg-[length:200%_100%] hover:bg-right rounded-full text-white font-semibold text-sm shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 transition-all duration-500 hover:scale-105"
                            >
                                <i class="fas fa-mug-hot group-hover:animate-bounce"></i>
                                <span class="hidden sm:inline">Traktir Kopi</span>
                                <i class="fas fa-heart text-red-300 text-xs animate-pulse"></i>
                            </button>

                            <!-- Dropdown Panel -->
                            <div 
                                x-show="showKopi" 
                                @click.away="showKopi = false"
                                x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0 transform -translate-y-4 scale-95"
                                x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
                                x-transition:leave="transition ease-in duration-200"
                                x-transition:leave-start="opacity-100 transform scale-100"
                                x-transition:leave-end="opacity-0 transform scale-95"
                                x-cloak
                                class="absolute left-0 mt-3 w-80 dark:bg-slate-800 bg-white rounded-2xl shadow-2xl border dark:border-slate-700 border-slate-200 overflow-hidden z-50"
                            >
                                <!-- Header dengan animasi -->
                                <div class="relative px-5 py-4 bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 overflow-hidden">
                                    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"30\" height=\"30\" viewBox=\"0 0 30 30\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Ccircle cx=\"15\" cy=\"15\" r=\"2\" fill=\"white\" fill-opacity=\"0.1\"/%3E%3C/svg%3E')] opacity-50"></div>
                                    <div class="relative flex items-center gap-4">
                                        <div class="w-14 h-14 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center shadow-lg">
                                            <i class="fas fa-mug-hot text-white text-2xl"></i>
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-white text-lg flex items-center gap-2">
                                                Traktir Kopi
                                                <i class="fas fa-heart text-red-200 text-sm animate-pulse"></i>
                                            </h3>
                                            <p class="text-sm text-amber-100">Dukung pengembang aplikasi ini!</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Options -->
                                <div class="p-4 space-y-3">
                                    <!-- DANA -->
                                    <div class="group dark:bg-slate-700/50 bg-slate-50 rounded-xl p-4 border-2 dark:border-slate-600 border-slate-200 hover:border-blue-500 dark:hover:border-blue-500 transition-all cursor-pointer hover:shadow-lg hover:shadow-blue-500/10">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                                                    <span class="text-white font-black text-lg">D</span>
                                                </div>
                                                <div>
                                                    <p class="font-bold dark:text-white text-slate-800">DANA</p>
                                                    <p class="text-sm dark:text-blue-400 text-blue-600 font-mono font-semibold">085348440470</p>
                                                </div>
                                            </div>
                                            <button @click.stop="navigator.clipboard.writeText('085348440470'); copied = 'dana'; setTimeout(() => copied = false, 2000)" 
                                                    class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-xl text-sm font-bold transition-all hover:scale-105 shadow-lg shadow-blue-500/25">
                                                <span x-show="copied !== 'dana'"><i class="fas fa-copy mr-1"></i>Salin</span>
                                                <span x-show="copied === 'dana'" x-cloak><i class="fas fa-check mr-1"></i>OK!</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- GoPay -->
                                    <div class="group dark:bg-slate-700/50 bg-slate-50 rounded-xl p-4 border-2 dark:border-slate-600 border-slate-200 hover:border-green-500 dark:hover:border-green-500 transition-all cursor-pointer hover:shadow-lg hover:shadow-green-500/10">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                                                    <i class="fas fa-wallet text-white text-lg"></i>
                                                </div>
                                                <div>
                                                    <p class="font-bold dark:text-white text-slate-800">GoPay</p>
                                                    <p class="text-sm dark:text-green-400 text-green-600 font-mono font-semibold">085348440470</p>
                                                </div>
                                            </div>
                                            <button @click.stop="navigator.clipboard.writeText('085348440470'); copied = 'gopay'; setTimeout(() => copied = false, 2000)" 
                                                    class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-xl text-sm font-bold transition-all hover:scale-105 shadow-lg shadow-green-500/25">
                                                <span x-show="copied !== 'gopay'"><i class="fas fa-copy mr-1"></i>Salin</span>
                                                <span x-show="copied === 'gopay'" x-cloak><i class="fas fa-check mr-1"></i>OK!</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer -->
                                <div class="px-4 py-3 dark:bg-slate-900/50 bg-gradient-to-r from-slate-100 to-slate-50 dark:from-slate-900 dark:to-slate-800 border-t dark:border-slate-700 border-slate-200">
                                    <p class="text-sm dark:text-slate-300 text-slate-600 text-center flex items-center justify-center gap-2">
                                        <i class="fas fa-heart text-red-500 animate-pulse"></i>
                                        <span>Terima kasih atas dukungannya!</span>
                                        <i class="fas fa-pray text-amber-500"></i>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        @if(auth()->user()->canManageWarehouse())
                        @php
                            $headerPendingRequest = \App\Models\StockOutRequest::where('status', 'pending')->count();
                            $headerPendingUsulan = \App\Models\UsulanPenerimaanBarang::menungguVerifikasi()->count();
                            $headerPendingPemeliharaan = \App\Models\PemeliharaanAset::where('status', 'menunggu')->count();
                            $headerPendingPermintaanBarang = \App\Models\PermintaanBarang::menungguVerifikasi()->count();
                            $headerTotalPending = $headerPendingRequest + $headerPendingUsulan + $headerPendingPemeliharaan + $headerPendingPermintaanBarang;
                        @endphp
                        <!-- Notification Bell Dropdown -->
                        <div class="relative" x-data="{ notifOpen: false, activeTab: 'permintaan' }" @click.away="notifOpen = false">
                            <button 
                                @click="notifOpen = !notifOpen"
                                class="w-10 h-10 rounded-xl dark:bg-slate-700/30 bg-white shadow flex items-center justify-center hover:bg-slate-200 dark:hover:bg-slate-700/50 transition-all relative"
                                title="Notifikasi"
                            >
                                <i class="fas fa-bell text-amber-500"></i>
                                <span id="header-notif-badge" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center {{ $headerTotalPending > 0 ? 'animate-pulse' : 'hidden' }}">{{ $headerTotalPending }}</span>
                            </button>

                            <!-- Dropdown Panel -->
                            <div 
                                x-show="notifOpen" 
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform scale-95"
                                x-transition:enter-end="opacity-100 transform scale-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 transform scale-100"
                                x-transition:leave-end="opacity-0 transform scale-95"
                                x-cloak
                                class="absolute right-0 mt-3 w-[420px] dark:bg-slate-800 bg-white rounded-2xl shadow-2xl border dark:border-slate-700 border-slate-200 overflow-hidden z-50"
                            >
                                <!-- Header with Tabs -->
                                <div class="px-4 py-3 dark:bg-slate-900/50 bg-slate-50 border-b dark:border-slate-700 border-slate-200">
                                    <h3 class="font-bold dark:text-white text-slate-800 text-sm mb-3 flex items-center">
                                        <i class="fas fa-bell text-amber-500 mr-2"></i>
                                        Notifikasi Menunggu
                                        <span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $headerTotalPending }}</span>
                                    </h3>
                                    <div class="flex gap-1">
                                        <button @click="activeTab = 'permintaan'" 
                                            :class="activeTab === 'permintaan' ? 'bg-pink-500 text-white' : 'dark:bg-slate-700 bg-slate-200 dark:text-slate-300 text-slate-600 hover:bg-pink-500/20'"
                                            class="flex-1 px-3 py-2 rounded-lg text-xs font-semibold transition-all flex items-center justify-center gap-1">
                                            <i class="fas fa-hand-holding"></i>
                                            <span>Permintaan</span>
                                            @if($headerPendingRequest > 0)<span class="bg-white/20 px-1.5 rounded">{{ $headerPendingRequest }}</span>@endif
                                        </button>
                                        <button @click="activeTab = 'usulan'" 
                                            :class="activeTab === 'usulan' ? 'bg-teal-500 text-white' : 'dark:bg-slate-700 bg-slate-200 dark:text-slate-300 text-slate-600 hover:bg-teal-500/20'"
                                            class="flex-1 px-3 py-2 rounded-lg text-xs font-semibold transition-all flex items-center justify-center gap-1">
                                            <i class="fas fa-file-import"></i>
                                            <span>Usulan</span>
                                            @if($headerPendingUsulan > 0)<span class="bg-white/20 px-1.5 rounded">{{ $headerPendingUsulan }}</span>@endif
                                        </button>
                                        <button @click="activeTab = 'pemeliharaan'" 
                                            :class="activeTab === 'pemeliharaan' ? 'bg-purple-500 text-white' : 'dark:bg-slate-700 bg-slate-200 dark:text-slate-300 text-slate-600 hover:bg-purple-500/20'"
                                            class="flex-1 px-3 py-2 rounded-lg text-xs font-semibold transition-all flex items-center justify-center gap-1">
                                            <i class="fas fa-wrench"></i>
                                            <span>Perbaikan</span>
                                            @if($headerPendingPemeliharaan > 0)<span class="bg-white/20 px-1.5 rounded">{{ $headerPendingPemeliharaan }}</span>@endif
                                        </button>
                                        <button @click="activeTab = 'permintaan_barang'" 
                                            :class="activeTab === 'permintaan_barang' ? 'bg-orange-500 text-white' : 'dark:bg-slate-700 bg-slate-200 dark:text-slate-300 text-slate-600 hover:bg-orange-500/20'"
                                            class="flex-1 px-3 py-2 rounded-lg text-xs font-semibold transition-all flex items-center justify-center gap-1">
                                            <i class="fas fa-hand-holding-medical"></i>
                                            <span>Permintaan Brg</span>
                                            @if($headerPendingPermintaanBarang > 0)<span class="bg-white/20 px-1.5 rounded">{{ $headerPendingPermintaanBarang }}</span>@endif
                                        </button>
                                    </div>
                                </div>

                                <!-- Tab: Permintaan Barang -->
                                <div x-show="activeTab === 'permintaan'" class="max-h-72 overflow-y-auto">
                                    @php
                                        $recentPendingRequests = \App\Models\StockOutRequest::with(['user', 'item'])
                                            ->where('status', 'pending')
                                            ->latest()
                                            ->take(5)
                                            ->get();
                                    @endphp
                                    @forelse($recentPendingRequests as $request)
                                    <div class="px-4 py-3 hover:dark:bg-slate-700/50 hover:bg-slate-50 transition-colors border-b dark:border-slate-700/50 border-slate-100 last:border-0">
                                        <div class="flex items-start">
                                            <div class="w-9 h-9 bg-gradient-to-br from-pink-500 to-rose-500 rounded-full flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-hand-holding text-white text-xs"></i>
                                            </div>
                                            <div class="ml-3 flex-1 min-w-0">
                                                <p class="font-semibold dark:text-white text-slate-800 text-sm truncate">{{ $request->user->name }}</p>
                                                <p class="text-xs dark:text-slate-400 text-slate-500">{{ $request->item->name ?? '-' }} ({{ $request->quantity }} unit)</p>
                                            </div>
                                            <span class="text-xs dark:text-slate-500 text-slate-400">{{ $request->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="px-4 py-8 text-center">
                                        <i class="fas fa-inbox text-slate-400 text-2xl mb-2"></i>
                                        <p class="dark:text-slate-400 text-slate-500 text-sm">Tidak ada permintaan menunggu</p>
                                    </div>
                                    @endforelse
                                    <div class="px-4 py-2 border-t dark:border-slate-700 border-slate-200">
                                        <a href="{{ route('checklist.index', ['status' => 'pending']) }}" class="block text-center py-2 text-pink-500 hover:text-pink-400 text-sm font-semibold">
                                            <i class="fas fa-external-link-alt mr-1"></i>Lihat Semua
                                        </a>
                                    </div>
                                </div>

                                <!-- Tab: Usulan Permintaan Barang Aset -->
                                <div x-show="activeTab === 'usulan'" x-cloak class="max-h-72 overflow-y-auto">
                                    @php
                                        $recentPendingUsulan = \App\Models\UsulanPenerimaanBarang::with(['user', 'ruangan'])
                                            ->menungguVerifikasi()
                                            ->latest()
                                            ->take(5)
                                            ->get();
                                    @endphp
                                    @forelse($recentPendingUsulan as $usulan)
                                    <div class="px-4 py-3 hover:dark:bg-slate-700/50 hover:bg-slate-50 transition-colors border-b dark:border-slate-700/50 border-slate-100 last:border-0">
                                        <div class="flex items-start">
                                            <div class="w-9 h-9 bg-gradient-to-br from-teal-500 to-cyan-500 rounded-full flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-file-import text-white text-xs"></i>
                                            </div>
                                            <div class="ml-3 flex-1 min-w-0">
                                                <p class="font-semibold dark:text-white text-slate-800 text-sm truncate">{{ $usulan->nama_barang }}</p>
                                                <p class="text-xs dark:text-slate-400 text-slate-500">{{ $usulan->user->name }} - {{ $usulan->ruangan->nama ?? '-' }}</p>
                                            </div>
                                            <span class="text-xs dark:text-slate-500 text-slate-400">{{ $usulan->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="px-4 py-8 text-center">
                                        <i class="fas fa-inbox text-slate-400 text-2xl mb-2"></i>
                                        <p class="dark:text-slate-400 text-slate-500 text-sm">Tidak ada usulan menunggu</p>
                                    </div>
                                    @endforelse
                                    <div class="px-4 py-2 border-t dark:border-slate-700 border-slate-200">
                                        <a href="{{ route('usulan-penerimaan.admin.index') }}" class="block text-center py-2 text-teal-500 hover:text-teal-400 text-sm font-semibold">
                                            <i class="fas fa-external-link-alt mr-1"></i>Lihat Semua
                                        </a>
                                    </div>
                                </div>

                                <!-- Tab: Pemeliharaan -->
                                <div x-show="activeTab === 'pemeliharaan'" x-cloak class="max-h-72 overflow-y-auto">
                                    @php
                                        $recentPendingPemeliharaan = \App\Models\PemeliharaanAset::with(['barangAset', 'createdBy'])
                                            ->where('status', 'menunggu')
                                            ->latest()
                                            ->take(5)
                                            ->get();
                                    @endphp
                                    @forelse($recentPendingPemeliharaan as $pemeliharaan)
                                    <div class="px-4 py-3 hover:dark:bg-slate-700/50 hover:bg-slate-50 transition-colors border-b dark:border-slate-700/50 border-slate-100 last:border-0">
                                        <div class="flex items-start">
                                            <div class="w-9 h-9 bg-gradient-to-br from-purple-500 to-fuchsia-500 rounded-full flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-wrench text-white text-xs"></i>
                                            </div>
                                            <div class="ml-3 flex-1 min-w-0">
                                                <p class="font-semibold dark:text-white text-slate-800 text-sm truncate">{{ $pemeliharaan->barangAset->nama_barang ?? '-' }}</p>
                                                <p class="text-xs dark:text-slate-400 text-slate-500">{{ $pemeliharaan->createdBy->name ?? '-' }} - {{ ucfirst($pemeliharaan->jenis_pemeliharaan) }}</p>
                                            </div>
                                            <span class="text-xs dark:text-slate-500 text-slate-400">{{ $pemeliharaan->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="px-4 py-8 text-center">
                                        <i class="fas fa-inbox text-slate-400 text-2xl mb-2"></i>
                                        <p class="dark:text-slate-400 text-slate-500 text-sm">Tidak ada perbaikan menunggu</p>
                                    </div>
                                    @endforelse
                                    <div class="px-4 py-2 border-t dark:border-slate-700 border-slate-200">
                                        <a href="{{ route('pemeliharaan.index', ['status' => 'menunggu']) }}" class="block text-center py-2 text-purple-500 hover:text-purple-400 text-sm font-semibold">
                                            <i class="fas fa-external-link-alt mr-1"></i>Lihat Semua
                                        </a>
                                    </div>
                                </div>

                                <!-- Tab: Permintaan Barang -->
                                <div x-show="activeTab === 'permintaan_barang'" x-cloak class="max-h-72 overflow-y-auto">
                                    @php
                                        $recentPendingPermintaanBarang = \App\Models\PermintaanBarang::with(['user', 'item'])
                                            ->menungguVerifikasi()
                                            ->latest()
                                            ->take(5)
                                            ->get();
                                    @endphp
                                    @forelse($recentPendingPermintaanBarang as $pb)
                                    <div class="px-4 py-3 hover:dark:bg-slate-700/50 hover:bg-slate-50 transition-colors border-b dark:border-slate-700/50 border-slate-100 last:border-0">
                                        <div class="flex items-start">
                                            <div class="w-9 h-9 bg-gradient-to-br from-orange-500 to-amber-500 rounded-full flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-hand-holding-medical text-white text-xs"></i>
                                            </div>
                                            <div class="ml-3 flex-1 min-w-0">
                                                <p class="font-semibold dark:text-white text-slate-800 text-sm truncate">{{ $pb->nama_barang ?? ($pb->item->name ?? '-') }}</p>
                                                <p class="text-xs dark:text-slate-400 text-slate-500">
                                                    {{ $pb->user->name }} - 
                                                    <span class="px-1.5 py-0.5 rounded text-xs {{ $pb->tipe === 'barang_baru' ? 'bg-blue-500/20 text-blue-400' : 'bg-amber-500/20 text-amber-400' }}">
                                                        {{ $pb->tipe === 'barang_baru' ? 'Baru' : 'Restok' }}
                                                    </span>
                                                </p>
                                            </div>
                                            <span class="text-xs dark:text-slate-500 text-slate-400">{{ $pb->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="px-4 py-8 text-center">
                                        <i class="fas fa-inbox text-slate-400 text-2xl mb-2"></i>
                                        <p class="dark:text-slate-400 text-slate-500 text-sm">Tidak ada permintaan barang menunggu</p>
                                    </div>
                                    @endforelse
                                    <div class="px-4 py-2 border-t dark:border-slate-700 border-slate-200">
                                        <a href="{{ route('permintaan-barang.admin.index') }}" class="block text-center py-2 text-orange-500 hover:text-orange-400 text-sm font-semibold">
                                            <i class="fas fa-external-link-alt mr-1"></i>Lihat Semua
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sound Notification Toggle -->
                        <div class="relative" x-data="{ soundEnabled: localStorage.getItem('notificationEnabled') !== 'false', showTestMenu: false }">
                            <button 
                                @click="soundEnabled = !soundEnabled; localStorage.setItem('notificationEnabled', soundEnabled); window.toggleRequestNotification && window.toggleRequestNotification();"
                                @contextmenu.prevent="showTestMenu = !showTestMenu"
                                class="w-10 h-10 rounded-xl dark:bg-slate-700/30 bg-white shadow flex items-center justify-center hover:bg-slate-200 dark:hover:bg-slate-700/50 transition-all"
                                :title="soundEnabled ? 'Klik: Matikan Suara | Klik Kanan: Test Suara' : 'Klik: Aktifkan Suara | Klik Kanan: Test Suara'"
                            >
                                <i class="fas fa-volume-up text-green-500" x-show="soundEnabled"></i>
                                <i class="fas fa-volume-mute text-slate-400" x-show="!soundEnabled"></i>
                            </button>
                            
                            <!-- Test Sound Dropdown -->
                            <div 
                                x-show="showTestMenu" 
                                @click.away="showTestMenu = false"
                                x-transition
                                x-cloak
                                class="absolute right-0 mt-2 w-48 dark:bg-slate-800 bg-white rounded-xl shadow-xl border dark:border-slate-700 border-slate-200 overflow-hidden z-50"
                            >
                                <button 
                                    @click="window.testNotificationSound && window.testNotificationSound(); showTestMenu = false"
                                    class="w-full px-4 py-3 text-left text-sm dark:text-white text-slate-700 hover:dark:bg-slate-700 hover:bg-slate-100 flex items-center transition"
                                >
                                    <i class="fas fa-play-circle text-green-500 mr-3"></i>
                                    Test Suara Notifikasi
                                </button>
                                <div class="px-4 py-2 text-xs dark:text-slate-500 text-slate-400 border-t dark:border-slate-700 border-slate-200">
                                    <i class="fas fa-info-circle mr-1"></i> Klik kanan untuk menu ini
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Dark/Light Mode Toggle -->
                        <button 
                            @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)"
                            class="w-10 h-10 rounded-xl dark:bg-slate-700/30 bg-white shadow flex items-center justify-center hover:bg-slate-200 dark:hover:bg-slate-700/50 transition-all theme-toggle"
                            :title="darkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                        >
                            <i class="fas fa-sun text-yellow-400" x-show="darkMode"></i>
                            <i class="fas fa-moon text-slate-600" x-show="!darkMode"></i>
                        </button>

                        <div class="hidden md:flex items-center dark:bg-slate-700/30 bg-white shadow rounded-xl px-4 py-2">
                            <i class="fas fa-calendar-alt text-blue-400 mr-2"></i>
                            <span class="text-sm dark:text-slate-300 text-slate-600">{{ now()->translatedFormat('l, d F Y') }}</span>
                        </div>

                        <a href="{{ route('profile') }}" class="flex items-center dark:bg-slate-700/30 bg-white shadow rounded-xl p-2 pr-4 hover:bg-slate-200 dark:hover:bg-slate-700/50 transition">
                            <div class="w-10 h-10 {{ auth()->user()->getRoleBadgeClass() }} rounded-lg flex items-center justify-center">
                                <i class="fas fa-user text-white"></i>
                            </div>
                            <div class="ml-3 hidden md:block">
                                <p class="text-sm font-semibold dark:text-white text-slate-700">{{ auth()->user()->name }}</p>
                                <p class="text-xs dark:text-slate-400 text-slate-500">{{ auth()->user()->getRoleName() }}</p>
                            </div>
                        </a>

                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="w-10 h-10 rounded-xl bg-red-500/20 flex items-center justify-center hover:bg-red-500 transition-all group btn-glow" title="Logout">
                                <i class="fas fa-sign-out-alt text-red-400 group-hover:text-white"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="p-8 w-full max-w-full">
                @if(session('success'))
                    <div class="bg-emerald-500/20 border border-emerald-500/50 text-emerald-300 p-4 mb-6 rounded-xl shadow-lg backdrop-blur-sm">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-lg bg-emerald-500/30 flex items-center justify-center mr-4">
                                <i class="fas fa-check-circle text-emerald-400"></i>
                            </div>
                            <p>{{ session('success') }}</p>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-500/20 border border-red-500/50 text-red-300 p-4 mb-6 rounded-xl shadow-lg backdrop-blur-sm">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-lg bg-red-500/30 flex items-center justify-center mr-4">
                                <i class="fas fa-exclamation-circle text-red-400"></i>
                            </div>
                            <p>{{ session('error') }}</p>
                        </div>
                    </div>
                @endif

                @yield('content')
            </div>

            <footer class="dark:bg-slate-800/50 bg-white/50 dark:border-t dark:border-slate-700/50 border-t border-slate-200 py-4 px-8 transition-colors duration-300">
                <div class="flex items-center justify-between text-sm dark:text-slate-500 text-slate-600">
                    <p>&copy; {{ date('Y') }} <span class="text-emerald-500 font-semibold">SIMBAR</span> - Puskesmas Karang Rejo</p>
                    <p>v1.1.0</p>
                </div>
            </footer>
        </main>
    </div>

    @stack('scripts')

    {{-- Notification Toast Container --}}
    <div id="notification-container" class="fixed top-4 right-4 z-50 space-y-3" style="max-width: 400px;"></div>

    {{-- Sound Notification System for Admin & Admin Gudang --}}
    @if(auth()->user()->canManageWarehouse())
    <script>
        (function() {
            // Audio system variables
            let audioContext = null;
            let audioUnlocked = false;
            let lastKnownRequestId = localStorage.getItem('lastKnownRequestId') || 0;
            let lastPendingCount = {{ $pendingRequestCount ?? 0 }};
            let notificationEnabled = localStorage.getItem('notificationEnabled') !== 'false';
            let isFirstLoad = true;

            // Create and cache audio buffer for notification sound
            let notificationBuffer = null;

            // Initialize Audio Context
            function initAudioContext() {
                if (!audioContext) {
                    try {
                        audioContext = new (window.AudioContext || window.webkitAudioContext)();
                        console.log('AudioContext created, state:', audioContext.state);
                    } catch (e) {
                        console.error('Failed to create AudioContext:', e);
                        return null;
                    }
                }
                return audioContext;
            }

            // Unlock audio on user interaction (CRITICAL for mobile/modern browsers)
            function unlockAudio() {
                if (audioUnlocked) return;
                
                const ctx = initAudioContext();
                if (!ctx) return;

                // Resume audio context if suspended
                if (ctx.state === 'suspended') {
                    ctx.resume().then(() => {
                        console.log('AudioContext resumed successfully');
                        audioUnlocked = true;
                        createNotificationBuffer();
                    }).catch(e => console.error('Failed to resume AudioContext:', e));
                } else {
                    audioUnlocked = true;
                    createNotificationBuffer();
                }

                // Play silent buffer to fully unlock
                try {
                    const buffer = ctx.createBuffer(1, 1, 22050);
                    const source = ctx.createBufferSource();
                    source.buffer = buffer;
                    source.connect(ctx.destination);
                    source.start(0);
                    console.log('Audio unlocked with silent buffer');
                } catch (e) {
                    console.log('Silent buffer play failed:', e);
                }
            }

            // Create notification sound buffer (pre-generate for instant playback)
            function createNotificationBuffer() {
                const ctx = initAudioContext();
                if (!ctx || notificationBuffer) return;

                try {
                    const sampleRate = ctx.sampleRate;
                    const duration = 1.2; // Total duration in seconds
                    const buffer = ctx.createBuffer(1, sampleRate * duration, sampleRate);
                    const data = buffer.getChannelData(0);

                    // Notification chime frequencies (C5, E5, G5 - Major chord)
                    const chimes = [
                        { freq: 523.25, start: 0, dur: 0.2 },
                        { freq: 659.25, start: 0.15, dur: 0.2 },
                        { freq: 783.99, start: 0.3, dur: 0.3 },
                        // Repeat
                        { freq: 523.25, start: 0.6, dur: 0.15 },
                        { freq: 659.25, start: 0.7, dur: 0.15 },
                        { freq: 783.99, start: 0.8, dur: 0.25 },
                    ];

                    // Generate waveform
                    for (let i = 0; i < data.length; i++) {
                        const t = i / sampleRate;
                        let sample = 0;

                        chimes.forEach(chime => {
                            if (t >= chime.start && t < chime.start + chime.dur) {
                                const localT = t - chime.start;
                                const envelope = Math.sin(Math.PI * localT / chime.dur); // Smooth envelope
                                sample += Math.sin(2 * Math.PI * chime.freq * localT) * envelope * 0.3;
                            }
                        });

                        data[i] = Math.max(-1, Math.min(1, sample));
                    }

                    notificationBuffer = buffer;
                    console.log('Notification buffer created successfully');
                } catch (e) {
                    console.error('Failed to create notification buffer:', e);
                }
            }

            // Play notification sound (IMPROVED VERSION)
            function playNotificationSound() {
                if (!notificationEnabled) {
                    console.log('Notification sound disabled');
                    return;
                }

                console.log('Attempting to play notification sound...');

                // Method 1: Try Web Audio API with pre-built buffer
                if (playWithWebAudio()) {
                    console.log('Sound played with Web Audio API');
                    return;
                }

                // Method 2: Fallback to HTML5 Audio with data URI
                playWithHTML5Audio();
            }

            // Play using Web Audio API
            function playWithWebAudio() {
                try {
                    const ctx = initAudioContext();
                    if (!ctx) return false;

                    // Resume if suspended
                    if (ctx.state === 'suspended') {
                        ctx.resume();
                    }

                    // Use pre-built buffer if available
                    if (notificationBuffer) {
                        const source = ctx.createBufferSource();
                        const gainNode = ctx.createGain();
                        
                        source.buffer = notificationBuffer;
                        source.connect(gainNode);
                        gainNode.connect(ctx.destination);
                        gainNode.gain.value = 0.5;
                        
                        source.start(0);
                        return true;
                    }

                    // Generate sound on-the-fly if buffer not ready
                    const frequencies = [523.25, 659.25, 783.99];
                    const duration = 0.2;
                    const gap = 0.12;

                    // First chime
                    frequencies.forEach((freq, index) => {
                        playTone(ctx, freq, ctx.currentTime + (index * (duration + gap)), duration, 0.4);
                    });

                    // Second chime (repeat)
                    setTimeout(() => {
                        if (ctx.state === 'running') {
                            frequencies.forEach((freq, index) => {
                                playTone(ctx, freq, ctx.currentTime + (index * (duration + gap)), duration, 0.3);
                            });
                        }
                    }, 500);

                    return true;
                } catch (e) {
                    console.error('Web Audio playback failed:', e);
                    return false;
                }
            }

            // Helper function to play a single tone
            function playTone(ctx, frequency, startTime, duration, volume) {
                const oscillator = ctx.createOscillator();
                const gainNode = ctx.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(ctx.destination);

                oscillator.frequency.value = frequency;
                oscillator.type = 'sine';

                gainNode.gain.setValueAtTime(0.001, startTime);
                gainNode.gain.exponentialRampToValueAtTime(volume, startTime + 0.02);
                gainNode.gain.exponentialRampToValueAtTime(0.001, startTime + duration);

                oscillator.start(startTime);
                oscillator.stop(startTime + duration + 0.1);
            }

            // Fallback: Play using HTML5 Audio with base64 encoded sound
            function playWithHTML5Audio() {
                try {
                    // Base64 encoded short notification beep (WAV format)
                    const audioData = 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdH2LkZmXk4l9b2NhZ3WEj5qhnpmQgnJiVVJcboCRn6iooJOCbVtOTVZpf5KkrrGpmIR0X09HSlxzh5uttLaqm4dvXU5IR1hxiZ2xt7qynohzXk5FQ1Nrf5OovL+7sZ6IcF1MQkJQaYGWqr3CvLGejXNfTkVDUGqDmay+wcK1oYxzX09FRFJ0h5eswMXDuKSQeWNTS0dRbYOYrcHIxbynlHtnVkxIUG2Fl6zByMW8qJV8aFdNSVBug5esv8bEu6eWfWhXTkpRcIWXrL/FxLqnlnxoV05KUnGFl6y/xcS5ppZ8aFdOSlJyhpeswMXEuaaVfGhXTkpTcoaYrMDGxbmnln1pWE9LU3OGmKzAxsW5p5Z9aVhQS1R0h5iswMbFuaeWfWlYUExUdIiZrMHGxbmnln1pWFBMVXWImqzBxsW5p5Z9aVhQTFV1iJqswcbGuaeWfmpZUU1Wd4mbrMHHxrqol35qWVFNV3eJm6zCx8a6qJd+allRTld3iZyswsfHu6mYf2taUk5YeIqcrMLIx7upmH9rWlJOWHiKnK3Cx8e7qZl/a1pSTll5i52twsjHu6mZgGxbU09ZeYudrsPIyLyqmoFsW1NQWnqMnq7DyMi8qpqBbFtTUFp6jJ6uw8nJvauagm1cVFBbe42fr8PJyb2rmoJtXFRRW3yOoK/Eycm9q5uDbVxUUVx8jqCwxMnJvauag21cVFFdfY+hssTKyr6snINuXVVSXX2PoLHFysq+rJyEbl5VUl5+kKGxxcrKvqychG5eVlJef5ChscXLy76snYRuXlZTX4CRorLGy8u/rZ2Fb19XU1+AkaKyxsvLv62dhW9fV1RggpKjs8bMzMCunoZwYFhUYIKSo7PGzMzArp6GcGBYVWGDk6SzxszMwK6ehnBgWFVhg5OktMfNzcGvn4dwYVlWYYSUpLTHzc3Br5+HcWFZVmKFlKW0x83Nwa+fh3FhWlZihZWltcjOzsKwoIhyYlpXY4aVpbXIzs7CsKCIcmJaV2OGlqW1yM7OwrGgiHJiW1hjh5amtsnPz8OxoYlzY1tYZIeWprfJz8/DsaGJc2NbWWSImKa3ydDQw7KiinRkXFlkiJint8rQ0MOyoop0ZFxZZYmYp7fK0NDDsqKKdGRcWmWJmKi4ytHRxLOjinVlXVplipiot8rR0cSzo4t1ZV1aZoqZqLjL0dHEs6SLdWVeW2aLmam4y9HRxLSkjHZmXltnipmpuMvS0sW0pIx2Zl9bZ4uaqbnM0tLFtaWMdmZfXGeNm6m5zNLSxbWljXdnYFxojZuquc3T08a2po13Z2BdaI6cqrrN09PGtqaNd2hgXWmOnKq6zdPTxrano3hoYV1pjpyrus7U1Me3p6R5aGFfaY+dq7vO1NTHt6ekeWhiX2qQnau7ztTUx7eopXppYl9qkJ2su8/V1ci4qKV6amJgapGerLzP1dXIuKmmempiYGuRnqy8z9XVyLippnprY2FrkZ+tvNDW1sm5qqd7a2NhbJKfrb3Q1tbJuaqne2tjYWySoK290NbWybmrp3trZGJsk6CtvdDX18q6q6h8bGRibJOhrr7R19fKuquofGxkYm2Uoa6+0dfXyrqsqHxsZWNtlKKuv9LY2Mu7rah9bWVjbpWir7/S2NjLu62pfW1lY26Voq+/0tjYy7ytp31tZmRulqOwwNPZ2cy8rql+bmZkb5ajsMDT2dnMvK6pfm5mZG+WpLDA09nZzLyvqn5uZ2VwlqSwwdTa2s29r6p/b2dlcJelscHU2trNva+qf29nZXCXpbHB1Nrazb2wq39wZ2ZxmKWxwtXb28++sKt/cGhmcZilssLV29vPvrGsgHBoZnKZprLC1dvbz76xrIBwaGZymqeyw9bc3NC/sq2AcWlncpmnt8PW3NzQv7KtgHFpZ3OapreD1tzc0L+yroBxaWdzmqa4g9bc3NG/sq2BcmpoYA==';
                    
                    const audio = new Audio(audioData);
                    audio.volume = 0.6;
                    
                    const playPromise = audio.play();
                    if (playPromise !== undefined) {
                        playPromise.then(() => {
                            console.log('HTML5 Audio played successfully');
                        }).catch(e => {
                            console.error('HTML5 Audio failed:', e);
                        });
                    }
                } catch (e) {
                    console.error('HTML5 Audio fallback failed:', e);
                }
            }

            // Test sound function (exposed globally)
            window.testNotificationSound = function() {
                unlockAudio();
                setTimeout(() => {
                    const wasEnabled = notificationEnabled;
                    notificationEnabled = true;
                    playNotificationSound();
                    notificationEnabled = wasEnabled;
                }, 100);
            };

            // Toggle notification function
            window.toggleRequestNotification = function() {
                notificationEnabled = !notificationEnabled;
                localStorage.setItem('notificationEnabled', notificationEnabled);
                
                if (notificationEnabled) {
                    unlockAudio();
                }
                
                return notificationEnabled;
            };

            // Show toast notification
            function showToastNotification(data) {
                const container = document.getElementById('notification-container');
                if (!container) return;

                const toast = document.createElement('div');
                toast.className = 'bg-gradient-to-r from-pink-600 to-rose-600 text-white p-4 rounded-xl shadow-2xl transform transition-all duration-500 translate-x-full opacity-0';
                toast.innerHTML = `
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-bell text-white animate-bounce"></i>
                            </div>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="font-bold text-sm">Permintaan Barang Baru!</p>
                            <p class="text-xs text-pink-100 mt-1">
                                <strong>${data.user_name}</strong> mengajukan pengambilan 
                                <strong>${data.item_name}</strong> (${data.quantity} unit)
                            </p>
                        </div>
                        <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-white/70 hover:text-white">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="mt-3 flex space-x-2">
                        <a href="{{ route('checklist.index', ['status' => 'pending']) }}" class="flex-1 bg-white/20 hover:bg-white/30 text-center py-2 rounded-lg text-xs font-semibold transition">
                            <i class="fas fa-check-circle mr-1"></i> Lihat Permintaan
                        </a>
                    </div>
                `;

                container.appendChild(toast);

                // Animate in
                setTimeout(() => {
                    toast.classList.remove('translate-x-full', 'opacity-0');
                }, 100);

                // Auto remove after 10 seconds
                setTimeout(() => {
                    toast.classList.add('translate-x-full', 'opacity-0');
                    setTimeout(() => toast.remove(), 500);
                }, 10000);
            }

            // Update badge count
            function updateBadge(count) {
                // Update sidebar badge
                const badge = document.getElementById('pending-request-badge');
                if (badge) {
                    badge.textContent = count;
                    if (count > 0) {
                        badge.classList.remove('hidden');
                        badge.classList.add('animate-pulse');
                    } else {
                        badge.classList.add('hidden');
                        badge.classList.remove('animate-pulse');
                    }
                }

                // Update header notification badge
                const headerBadge = document.getElementById('header-notif-badge');
                if (headerBadge) {
                    headerBadge.textContent = count;
                    if (count > 0) {
                        headerBadge.classList.remove('hidden');
                        headerBadge.classList.add('animate-pulse');
                    } else {
                        headerBadge.classList.add('hidden');
                        headerBadge.classList.remove('animate-pulse');
                    }
                }

                // Update header notification count
                const headerCount = document.getElementById('header-notif-count');
                if (headerCount) {
                    headerCount.textContent = count;
                }
            }

            // Update notification list dropdown
            function updateNotificationList(requests) {
                const listContainer = document.getElementById('notification-list');
                if (!listContainer) return;

                if (requests.length === 0) {
                    listContainer.innerHTML = `
                        <div class="px-4 py-8 text-center">
                            <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-inbox text-slate-400 text-2xl"></i>
                            </div>
                            <p class="dark:text-slate-400 text-slate-500 text-sm">Tidak ada permintaan menunggu</p>
                        </div>
                    `;
                    return;
                }

                let html = '';
                requests.forEach(request => {
                    html += `
                        <div class="px-4 py-3 hover:dark:bg-slate-700/50 hover:bg-slate-50 transition-colors border-b dark:border-slate-700/50 border-slate-100 last:border-0">
                            <div class="flex items-start">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-user text-white text-sm"></i>
                                </div>
                                <div class="ml-3 flex-1 min-w-0">
                                    <p class="font-semibold dark:text-white text-slate-800 text-sm truncate">${request.user_name}</p>
                                    <p class="text-xs dark:text-slate-400 text-slate-500 mt-0.5">
                                        Mengajukan <span class="font-medium dark:text-pink-400 text-pink-600">${request.item_name}</span>
                                    </p>
                                    <p class="text-xs dark:text-slate-500 text-slate-400 mt-0.5">
                                        Jumlah: <span class="font-semibold">${request.quantity}</span> unit
                                    </p>
                                </div>
                                <div class="text-right flex-shrink-0 ml-2">
                                    <p class="text-xs font-medium dark:text-amber-400 text-amber-600">
                                        <i class="fas fa-clock mr-1"></i>${request.created_at_formatted.split(',')[0]}
                                    </p>
                                    <p class="text-xs dark:text-slate-500 text-slate-400">${request.created_at_formatted.split(',')[1] || ''}</p>
                                </div>
                            </div>
                        </div>
                    `;
                });

                listContainer.innerHTML = html;
            }

            // Check for new pending requests
            async function checkPendingRequests() {
                try {
                    const response = await fetch('{{ route("api.pending-requests") }}', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) return;

                    const data = await response.json();
                    
                    // Update all badges
                    updateBadge(data.count);

                    // Update notification list
                    if (data.requests) {
                        updateNotificationList(data.requests);
                    }

                    // Check if there's a new request (only after first load)
                    if (!isFirstLoad && data.latest) {
                        const latestId = data.latest.id;
                        const storedId = parseInt(localStorage.getItem('lastKnownRequestId') || '0');
                        
                        if (latestId > storedId) {
                            // New request detected!
                            playNotificationSound();
                            showToastNotification(data.latest);
                            localStorage.setItem('lastKnownRequestId', latestId);
                        }
                    } else if (isFirstLoad && data.latest) {
                        // On first load, just store the latest ID
                        localStorage.setItem('lastKnownRequestId', data.latest.id);
                        isFirstLoad = false;
                    } else {
                        isFirstLoad = false;
                    }

                    lastPendingCount = data.count;
                } catch (e) {
                    console.log('Failed to check pending requests:', e);
                }
            }

            // Unlock audio on ANY user interaction (critical for browsers)
            const userInteractionEvents = ['click', 'touchstart', 'keydown', 'mousedown'];
            
            function handleUserInteraction() {
                unlockAudio();
                // Remove all listeners after first interaction
                userInteractionEvents.forEach(event => {
                    document.removeEventListener(event, handleUserInteraction);
                });
                console.log('User interaction detected, audio system initialized');
            }

            userInteractionEvents.forEach(event => {
                document.addEventListener(event, handleUserInteraction, { once: false, passive: true });
            });

            // Start polling when page loads
            document.addEventListener('DOMContentLoaded', function() {
                // Initial check
                checkPendingRequests();

                // Poll every 10 seconds
                setInterval(checkPendingRequests, 10000);
                
                // Try to init audio context (may be blocked until user interaction)
                initAudioContext();
            });
        })();
    </script>
    @endif

    <!-- Popup Notifikasi Restok Barang Tersedia -->
    @auth
    <div x-data="restokPopup()" x-init="checkNotifications()" x-cloak>
        <!-- Overlay -->
        <div x-show="notifications.length > 0" 
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/50 z-[60]"
            @click="closeAll()">
        </div>

        <!-- Popup -->
        <div x-show="notifications.length > 0"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-90 translate-y-4"
            x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-90"
            class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-[70] w-full max-w-md">
            <div class="dark:bg-slate-800 bg-white rounded-2xl shadow-2xl border dark:border-slate-700 border-slate-200 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-emerald-500 to-teal-500 px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                            <i class="fas fa-check-circle text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-white font-bold text-lg">Barang Sudah Tersedia!</h3>
                            <p class="text-emerald-100 text-sm">Permintaan restok Anda telah terpenuhi</p>
                        </div>
                    </div>
                    <button @click="closeAll()" class="w-8 h-8 bg-white/20 hover:bg-white/30 rounded-lg flex items-center justify-center transition" title="Tutup">
                        <i class="fas fa-times text-white"></i>
                    </button>
                </div>

                <!-- Content -->
                <div class="p-6 max-h-80 overflow-y-auto space-y-3">
                    <template x-for="notif in notifications" :key="notif.id">
                        <div class="dark:bg-slate-700/50 bg-slate-50 rounded-xl p-4 border dark:border-slate-600/50 border-slate-200">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-emerald-500/20 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-box-open text-emerald-400"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold dark:text-white text-slate-800 truncate" x-text="notif.item_name"></p>
                                    <p class="text-sm dark:text-slate-400 text-slate-500">
                                        Stok tersedia: <span class="font-bold text-emerald-400" x-text="notif.item_stock"></span> |
                                        Diminta: <span class="font-semibold" x-text="notif.jumlah_diminta"></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t dark:border-slate-700 border-slate-200 flex items-center justify-between">
                    <a href="{{ route('permintaan-barang.index') }}" class="text-sm text-emerald-500 hover:text-emerald-400 font-semibold">
                        <i class="fas fa-list mr-1"></i>Lihat Semua Permintaan
                    </a>
                    <button @click="closeAll()" class="px-6 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-semibold text-sm transition">
                        <i class="fas fa-check mr-1"></i>Mengerti
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function restokPopup() {
        return {
            notifications: [],
            checkNotifications() {
                fetch('{{ route("api.permintaan-barang.notifications") }}')
                    .then(res => res.json())
                    .then(data => {
                        if (data.count > 0) {
                            this.notifications = data.notifications;
                        }
                    })
                    .catch(() => {});
            },
            closeAll() {
                const ids = this.notifications.map(n => n.id);
                fetch('{{ route("api.permintaan-barang.mark-read") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ ids: ids })
                }).then(() => {
                    this.notifications = [];
                }).catch(() => {
                    this.notifications = [];
                });
            }
        };
    }
    </script>
    @endauth
</body>
</html>
