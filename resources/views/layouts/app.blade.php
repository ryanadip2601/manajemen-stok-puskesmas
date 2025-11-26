<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Manajemen Stok Barang') - UPTD Puskesmas Karang Rejo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #1e293b; }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #64748b; }
        * { transition: all 0.2s ease; }
        .card-hover { transform: translateY(0); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); }
        .btn-glow:hover { box-shadow: 0 0 20px rgba(59, 130, 246, 0.5); }
        .sidebar-link { position: relative; overflow: hidden; }
        .sidebar-link::before { content: ''; position: absolute; left: 0; top: 0; height: 100%; width: 0; background: linear-gradient(90deg, rgba(255,255,255,0.1), transparent); transition: width 0.3s ease; }
        .sidebar-link:hover::before { width: 100%; }
        .gradient-text { background: linear-gradient(135deg, #60a5fa, #a78bfa); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .pulse { animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .bg-pattern { background-color: #0f172a; background-image: radial-gradient(at 47% 33%, hsl(220, 50%, 15%) 0, transparent 59%), radial-gradient(at 82% 65%, hsl(240, 50%, 12%) 0, transparent 55%); }
    </style>
</head>
<body class="bg-slate-900">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="w-72 bg-gradient-to-b from-slate-800 via-slate-850 to-slate-900 text-white fixed h-full shadow-2xl border-r border-slate-700/50">
            <!-- Logo Section -->
            <div class="p-6 border-b border-slate-700/50">
                <div class="flex flex-col items-center text-center">
                    <div class="w-20 h-20 bg-gradient-to-br from-emerald-400 to-teal-600 rounded-full flex items-center justify-center shadow-lg shadow-emerald-500/30 mb-3">
                        <i class="fas fa-hospital text-white text-3xl"></i>
                    </div>
                    <h1 class="text-lg font-bold text-white">UPTD Puskesmas</h1>
                    <h2 class="text-xl font-extrabold gradient-text">Karang Rejo</h2>
                    <p class="text-xs text-slate-400 mt-1">Kota Balikpapan</p>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="mt-4 px-3">
                <p class="px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Menu Utama</p>
                
                <a href="{{ route('dashboard') }}" class="sidebar-link flex items-center px-4 py-3 mb-1 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-blue-600/80 to-blue-700/80 shadow-lg shadow-blue-500/20' : '' }}">
                    <div class="w-10 h-10 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-blue-500/20 {{ request()->routeIs('dashboard') ? 'bg-blue-500/30' : '' }}">
                        <i class="fas fa-home text-blue-400"></i>
                    </div>
                    <span class="ml-3 font-medium">Dashboard</span>
                </a>
                
                <a href="{{ route('items.index') }}" class="sidebar-link flex items-center px-4 py-3 mb-1 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('items.*') ? 'bg-gradient-to-r from-emerald-600/80 to-emerald-700/80 shadow-lg shadow-emerald-500/20' : '' }}">
                    <div class="w-10 h-10 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-emerald-500/20 {{ request()->routeIs('items.*') ? 'bg-emerald-500/30' : '' }}">
                        <i class="fas fa-box text-emerald-400"></i>
                    </div>
                    <span class="ml-3 font-medium">Data Barang</span>
                </a>

                @if(auth()->user()->isAdmin())
                <a href="{{ route('categories.index') }}" class="sidebar-link flex items-center px-4 py-3 mb-1 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('categories.*') ? 'bg-gradient-to-r from-purple-600/80 to-purple-700/80 shadow-lg shadow-purple-500/20' : '' }}">
                    <div class="w-10 h-10 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-purple-500/20 {{ request()->routeIs('categories.*') ? 'bg-purple-500/30' : '' }}">
                        <i class="fas fa-folder text-purple-400"></i>
                    </div>
                    <span class="ml-3 font-medium">Kategori</span>
                </a>

                <p class="px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 mt-6">Transaksi</p>
                
                <a href="{{ route('stock-in.index') }}" class="sidebar-link flex items-center px-4 py-3 mb-1 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('stock-in.*') ? 'bg-gradient-to-r from-green-600/80 to-green-700/80 shadow-lg shadow-green-500/20' : '' }}">
                    <div class="w-10 h-10 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-green-500/20 {{ request()->routeIs('stock-in.*') ? 'bg-green-500/30' : '' }}">
                        <i class="fas fa-arrow-down text-green-400"></i>
                    </div>
                    <span class="ml-3 font-medium">Barang Masuk</span>
                </a>
                
                <a href="{{ route('stock-out.index') }}" class="sidebar-link flex items-center px-4 py-3 mb-1 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('stock-out.*') ? 'bg-gradient-to-r from-red-600/80 to-red-700/80 shadow-lg shadow-red-500/20' : '' }}">
                    <div class="w-10 h-10 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-red-500/20 {{ request()->routeIs('stock-out.*') ? 'bg-red-500/30' : '' }}">
                        <i class="fas fa-arrow-up text-red-400"></i>
                    </div>
                    <span class="ml-3 font-medium">Barang Keluar</span>
                </a>

                <p class="px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 mt-6">Tools</p>
                
                <a href="{{ route('items.scan') }}" class="sidebar-link flex items-center px-4 py-3 mb-1 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group {{ request()->routeIs('items.scan') ? 'bg-gradient-to-r from-yellow-600/80 to-orange-700/80 shadow-lg shadow-yellow-500/20' : '' }}">
                    <div class="w-10 h-10 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-yellow-500/20 {{ request()->routeIs('items.scan') ? 'bg-yellow-500/30' : '' }}">
                        <i class="fas fa-barcode text-yellow-400"></i>
                    </div>
                    <span class="ml-3 font-medium">Scan Barcode</span>
                </a>
                @endif
            </nav>

            <!-- User Profile at Bottom -->
            <div class="absolute bottom-0 w-72 p-4 border-t border-slate-700/50 bg-slate-800/50">
                <a href="{{ route('profile') }}" class="flex items-center p-3 rounded-xl bg-slate-700/30 hover:bg-slate-700/50 transition-all group">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
                        <i class="fas fa-user text-white text-lg"></i>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="font-semibold text-white text-sm group-hover:text-blue-400 transition">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-400">
                            @if(auth()->user()->isAdmin())
                                <i class="fas fa-crown text-yellow-400 mr-1"></i>Admin
                            @else
                                <i class="fas fa-user-tie text-blue-400 mr-1"></i>Pegawai
                            @endif
                        </p>
                    </div>
                    <i class="fas fa-cog text-slate-500 group-hover:text-white group-hover:rotate-90 transition-all"></i>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 ml-72 bg-pattern min-h-screen">
            <!-- Top Header -->
            <header class="bg-slate-800/80 backdrop-blur-xl shadow-lg border-b border-slate-700/50 sticky top-0 z-10">
                <div class="px-8 py-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-white">@yield('header', 'Dashboard')</h2>
                        <p class="text-sm text-slate-400">@yield('breadcrumb')</p>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <div class="hidden md:flex items-center bg-slate-700/30 rounded-xl px-4 py-2">
                            <i class="fas fa-calendar-alt text-blue-400 mr-2"></i>
                            <span class="text-sm text-slate-300">{{ now()->translatedFormat('l, d F Y') }}</span>
                        </div>

                        <a href="{{ route('profile') }}" class="flex items-center bg-slate-700/30 rounded-xl p-2 pr-4 hover:bg-slate-700/50 transition">
                            <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-teal-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-user text-white"></i>
                            </div>
                            <div class="ml-3 hidden md:block">
                                <p class="text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-slate-400">{{ auth()->user()->isAdmin() ? 'Admin' : 'Pegawai' }}</p>
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
            <div class="p-8">
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

            <footer class="bg-slate-800/50 border-t border-slate-700/50 py-4 px-8">
                <div class="flex items-center justify-between text-sm text-slate-500">
                    <p>&copy; {{ date('Y') }} UPTD Puskesmas Karang Rejo Balikpapan</p>
                    <p>v1.0.0</p>
                </div>
            </footer>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
