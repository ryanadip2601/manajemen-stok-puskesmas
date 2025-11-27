<!DOCTYPE html>
<html lang="id" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $__env->yieldContent('title', 'SIMBAR'); ?> - Sistem Manajemen Barang</title>
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
    </style>
</head>
<body class="dark:bg-slate-900 bg-slate-100 transition-colors duration-300">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="w-72 bg-gradient-to-b from-slate-800 via-slate-850 to-slate-900 text-white fixed h-full shadow-2xl border-r border-slate-700/50">
            <!-- Logo Section -->
            <div class="p-6 border-b border-slate-700/50">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-emerald-400 to-teal-600 rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-500/20 mb-3">
                        <i class="fas fa-boxes-stacked text-white text-2xl"></i>
                    </div>
                    <h1 class="text-2xl font-black gradient-text tracking-wide">SIMBAR</h1>
                    <p class="text-xs text-slate-400 mt-1">Sistem Manajemen Barang</p>
                    <p class="text-xs text-slate-500">Puskesmas Karang Rejo</p>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="mt-4 px-3 overflow-y-auto" style="max-height: calc(100vh - 280px);" x-data="{ 
                menuUtama: <?php echo e(request()->routeIs('dashboard') || request()->routeIs('items.*') || request()->routeIs('categories.*') ? 'true' : 'false'); ?>,
                menuTransaksi: <?php echo e(request()->routeIs('stock-in.*') || request()->routeIs('stock-out.*') ? 'true' : 'false'); ?>,
                menuTools: <?php echo e(request()->routeIs('items.scan') ? 'true' : 'false'); ?>,
                menuLaporan: <?php echo e(request()->routeIs('reports.*') ? 'true' : 'false'); ?>

            }">
                <!-- Menu Utama -->
                <div class="mb-2">
                    <button @click="menuUtama = !menuUtama" class="w-full flex items-center justify-between px-4 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider hover:text-white transition-colors group">
                        <span class="flex items-center">
                            <i class="fas fa-th-large mr-2 text-blue-400"></i>
                            Menu Utama
                        </span>
                        <i class="fas fa-chevron-down text-slate-500 group-hover:text-white transition-transform duration-300" :class="{ 'rotate-180': menuUtama }"></i>
                    </button>
                    <div x-show="menuUtama" x-collapse x-cloak class="mt-1 space-y-1">
                        <a href="<?php echo e(route('dashboard')); ?>" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group <?php echo e(request()->routeIs('dashboard') ? 'bg-gradient-to-r from-blue-600/80 to-blue-700/80 shadow-lg shadow-blue-500/20' : ''); ?>">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-blue-500/20 <?php echo e(request()->routeIs('dashboard') ? 'bg-blue-500/30' : ''); ?>">
                                <i class="fas fa-home text-blue-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Dashboard</span>
                        </a>
                        
                        <a href="<?php echo e(route('items.index')); ?>" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group <?php echo e(request()->routeIs('items.*') && !request()->routeIs('items.scan') ? 'bg-gradient-to-r from-emerald-600/80 to-emerald-700/80 shadow-lg shadow-emerald-500/20' : ''); ?>">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-emerald-500/20 <?php echo e(request()->routeIs('items.*') && !request()->routeIs('items.scan') ? 'bg-emerald-500/30' : ''); ?>">
                                <i class="fas fa-box text-emerald-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Data Barang</span>
                        </a>

                        <?php if(auth()->user()->isAdmin()): ?>
                        <a href="<?php echo e(route('categories.index')); ?>" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group <?php echo e(request()->routeIs('categories.*') ? 'bg-gradient-to-r from-purple-600/80 to-purple-700/80 shadow-lg shadow-purple-500/20' : ''); ?>">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-purple-500/20 <?php echo e(request()->routeIs('categories.*') ? 'bg-purple-500/30' : ''); ?>">
                                <i class="fas fa-folder text-purple-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Kategori</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if(auth()->user()->isAdmin()): ?>
                <!-- Menu Transaksi -->
                <div class="mb-2">
                    <button @click="menuTransaksi = !menuTransaksi" class="w-full flex items-center justify-between px-4 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider hover:text-white transition-colors group">
                        <span class="flex items-center">
                            <i class="fas fa-exchange-alt mr-2 text-green-400"></i>
                            Transaksi
                        </span>
                        <i class="fas fa-chevron-down text-slate-500 group-hover:text-white transition-transform duration-300" :class="{ 'rotate-180': menuTransaksi }"></i>
                    </button>
                    <div x-show="menuTransaksi" x-collapse x-cloak class="mt-1 space-y-1">
                        <a href="<?php echo e(route('stock-in.index')); ?>" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group <?php echo e(request()->routeIs('stock-in.*') ? 'bg-gradient-to-r from-green-600/80 to-green-700/80 shadow-lg shadow-green-500/20' : ''); ?>">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-green-500/20 <?php echo e(request()->routeIs('stock-in.*') ? 'bg-green-500/30' : ''); ?>">
                                <i class="fas fa-arrow-down text-green-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Barang Masuk</span>
                        </a>
                        
                        <a href="<?php echo e(route('stock-out.index')); ?>" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group <?php echo e(request()->routeIs('stock-out.*') ? 'bg-gradient-to-r from-red-600/80 to-red-700/80 shadow-lg shadow-red-500/20' : ''); ?>">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-red-500/20 <?php echo e(request()->routeIs('stock-out.*') ? 'bg-red-500/30' : ''); ?>">
                                <i class="fas fa-arrow-up text-red-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Barang Keluar</span>
                        </a>
                    </div>
                </div>

                <!-- Menu Tools -->
                <div class="mb-2">
                    <button @click="menuTools = !menuTools" class="w-full flex items-center justify-between px-4 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider hover:text-white transition-colors group">
                        <span class="flex items-center">
                            <i class="fas fa-tools mr-2 text-yellow-400"></i>
                            Tools
                        </span>
                        <i class="fas fa-chevron-down text-slate-500 group-hover:text-white transition-transform duration-300" :class="{ 'rotate-180': menuTools }"></i>
                    </button>
                    <div x-show="menuTools" x-collapse x-cloak class="mt-1 space-y-1">
                        <a href="<?php echo e(route('items.scan')); ?>" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group <?php echo e(request()->routeIs('items.scan') ? 'bg-gradient-to-r from-yellow-600/80 to-orange-700/80 shadow-lg shadow-yellow-500/20' : ''); ?>">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-yellow-500/20 <?php echo e(request()->routeIs('items.scan') ? 'bg-yellow-500/30' : ''); ?>">
                                <i class="fas fa-barcode text-yellow-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Scan Barcode</span>
                        </a>
                    </div>
                </div>

                <!-- Menu Laporan -->
                <div class="mb-2">
                    <button @click="menuLaporan = !menuLaporan" class="w-full flex items-center justify-between px-4 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider hover:text-white transition-colors group">
                        <span class="flex items-center">
                            <i class="fas fa-file-alt mr-2 text-cyan-400"></i>
                            Laporan
                        </span>
                        <i class="fas fa-chevron-down text-slate-500 group-hover:text-white transition-transform duration-300" :class="{ 'rotate-180': menuLaporan }"></i>
                    </button>
                    <div x-show="menuLaporan" x-collapse x-cloak class="mt-1 space-y-1">
                        <a href="<?php echo e(route('reports.monthly')); ?>" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group <?php echo e(request()->routeIs('reports.monthly') ? 'bg-gradient-to-r from-cyan-600/80 to-cyan-700/80 shadow-lg shadow-cyan-500/20' : ''); ?>">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-cyan-500/20 <?php echo e(request()->routeIs('reports.monthly') ? 'bg-cyan-500/30' : ''); ?>">
                                <i class="fas fa-calendar-alt text-cyan-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Laporan Bulanan</span>
                        </a>

                        <a href="<?php echo e(route('reports.yearly')); ?>" class="sidebar-link flex items-center px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 group <?php echo e(request()->routeIs('reports.yearly') ? 'bg-gradient-to-r from-indigo-600/80 to-indigo-700/80 shadow-lg shadow-indigo-500/20' : ''); ?>">
                            <div class="w-9 h-9 rounded-lg bg-slate-700/50 flex items-center justify-center group-hover:bg-indigo-500/20 <?php echo e(request()->routeIs('reports.yearly') ? 'bg-indigo-500/30' : ''); ?>">
                                <i class="fas fa-chart-line text-indigo-400 text-sm"></i>
                            </div>
                            <span class="ml-3 font-medium text-sm">Laporan Tahunan</span>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </nav>

            <!-- User Profile at Bottom -->
            <div class="absolute bottom-0 w-72 p-4 border-t border-slate-700/50 bg-slate-800/50">
                <a href="<?php echo e(route('profile')); ?>" class="flex items-center p-3 rounded-xl bg-slate-700/30 hover:bg-slate-700/50 transition-all group">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
                        <i class="fas fa-user text-white text-lg"></i>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="font-semibold text-white text-sm group-hover:text-blue-400 transition"><?php echo e(auth()->user()->name); ?></p>
                        <p class="text-xs text-slate-400">
                            <?php if(auth()->user()->isAdmin()): ?>
                                <i class="fas fa-crown text-yellow-400 mr-1"></i>Admin
                            <?php else: ?>
                                <i class="fas fa-user-tie text-blue-400 mr-1"></i>Pegawai
                            <?php endif; ?>
                        </p>
                    </div>
                    <i class="fas fa-cog text-slate-500 group-hover:text-white group-hover:rotate-90 transition-all"></i>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 ml-72 bg-pattern min-h-screen transition-colors duration-300">
            <!-- Top Header -->
            <header class="dark:bg-slate-800/80 bg-white/80 backdrop-blur-xl shadow-lg dark:border-b dark:border-slate-700/50 border-b border-slate-200 sticky top-0 z-10 transition-colors duration-300">
                <div class="px-8 py-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold dark:text-white text-slate-800"><?php echo $__env->yieldContent('header', 'Dashboard'); ?></h2>
                        <p class="text-sm dark:text-slate-400 text-slate-500"><?php echo $__env->yieldContent('breadcrumb'); ?></p>
                    </div>
                    
                    <div class="flex items-center space-x-4">
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
                            <span class="text-sm dark:text-slate-300 text-slate-600"><?php echo e(now()->translatedFormat('l, d F Y')); ?></span>
                        </div>

                        <a href="<?php echo e(route('profile')); ?>" class="flex items-center dark:bg-slate-700/30 bg-white shadow rounded-xl p-2 pr-4 hover:bg-slate-200 dark:hover:bg-slate-700/50 transition">
                            <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-teal-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-user text-white"></i>
                            </div>
                            <div class="ml-3 hidden md:block">
                                <p class="text-sm font-semibold dark:text-white text-slate-700"><?php echo e(auth()->user()->name); ?></p>
                                <p class="text-xs dark:text-slate-400 text-slate-500"><?php echo e(auth()->user()->isAdmin() ? 'Admin' : 'Pegawai'); ?></p>
                            </div>
                        </a>

                        <form action="<?php echo e(route('logout')); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="w-10 h-10 rounded-xl bg-red-500/20 flex items-center justify-center hover:bg-red-500 transition-all group btn-glow" title="Logout">
                                <i class="fas fa-sign-out-alt text-red-400 group-hover:text-white"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="p-8">
                <?php if(session('success')): ?>
                    <div class="bg-emerald-500/20 border border-emerald-500/50 text-emerald-300 p-4 mb-6 rounded-xl shadow-lg backdrop-blur-sm">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-lg bg-emerald-500/30 flex items-center justify-center mr-4">
                                <i class="fas fa-check-circle text-emerald-400"></i>
                            </div>
                            <p><?php echo e(session('success')); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if(session('error')): ?>
                    <div class="bg-red-500/20 border border-red-500/50 text-red-300 p-4 mb-6 rounded-xl shadow-lg backdrop-blur-sm">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-lg bg-red-500/30 flex items-center justify-center mr-4">
                                <i class="fas fa-exclamation-circle text-red-400"></i>
                            </div>
                            <p><?php echo e(session('error')); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php echo $__env->yieldContent('content'); ?>
            </div>

            <footer class="dark:bg-slate-800/50 bg-white/50 dark:border-t dark:border-slate-700/50 border-t border-slate-200 py-4 px-8 transition-colors duration-300">
                <div class="flex items-center justify-between text-sm dark:text-slate-500 text-slate-600">
                    <p>&copy; <?php echo e(date('Y')); ?> <span class="text-emerald-500 font-semibold">SIMBAR</span> - Puskesmas Karang Rejo</p>
                    <p>v1.0.0</p>
                </div>
            </footer>
        </main>
    </div>

    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\Users\Ryana\Downloads\Apk Vibe Coding\website barang 1.1\resources\views/layouts/app.blade.php ENDPATH**/ ?>