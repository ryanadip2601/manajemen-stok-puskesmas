<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('header', 'Dashboard'); ?>
<?php $__env->startSection('breadcrumb', 'Selamat datang di Sistem Manajemen Stok Barang'); ?>

<?php $__env->startSection('content'); ?>
    <!-- Traktir Kopi Section -->
    <div x-data="{ showDonate: false, copied: false }" class="mb-6">
        <div class="dark:bg-gradient-to-r dark:from-amber-900/30 dark:via-orange-900/20 dark:to-yellow-900/30 bg-gradient-to-r from-amber-100 via-orange-50 to-yellow-100 rounded-2xl border dark:border-amber-500/30 border-amber-300 overflow-hidden">
            <div class="p-4 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-amber-400 to-orange-500 rounded-2xl flex items-center justify-center shadow-lg shadow-amber-500/30 animate-bounce">
                        <i class="fas fa-mug-hot text-white text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold dark:text-amber-300 text-amber-700 flex items-center gap-2">
                            <i class="fas fa-heart text-red-400 animate-pulse"></i>
                            Traktir Kopi
                        </h3>
                        <p class="text-sm dark:text-slate-400 text-slate-600">Dukung pengembang dengan secangkir kopi!</p>
                    </div>
                </div>
                <button @click="showDonate = !showDonate" 
                        class="px-6 py-3 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-bold rounded-xl shadow-lg hover:shadow-amber-500/30 transition-all transform hover:scale-105 flex items-center gap-2">
                    <i class="fas fa-coffee"></i>
                    <span x-text="showDonate ? 'Tutup' : 'Donasi Sekarang'"></span>
                    <i class="fas fa-chevron-down transition-transform" :class="{ 'rotate-180': showDonate }"></i>
                </button>
            </div>
            
            <!-- Expandable Payment Section -->
            <div x-show="showDonate" x-collapse x-cloak class="border-t dark:border-amber-500/20 border-amber-300">
                <div class="p-6 dark:bg-slate-800/50 bg-white/50">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- DANA -->
                        <div class="dark:bg-slate-700/50 bg-white rounded-xl p-5 border-2 border-blue-500/30 hover:border-blue-500 transition-all group">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center">
                                    <span class="text-white font-black text-lg">DANA</span>
                                </div>
                                <div>
                                    <p class="font-bold dark:text-white text-slate-800">DANA</p>
                                    <p class="text-xs dark:text-slate-400 text-slate-500">Transfer via DANA</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between dark:bg-slate-600/50 bg-slate-100 rounded-lg p-3">
                                <div>
                                    <p class="text-xs dark:text-slate-400 text-slate-500">Nomor DANA</p>
                                    <p class="text-xl font-bold dark:text-white text-slate-800 font-mono">085348440470</p>
                                </div>
                                <button @click="navigator.clipboard.writeText('085348440470'); copied = 'dana'; setTimeout(() => copied = false, 2000)" 
                                        class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-semibold transition flex items-center gap-2">
                                    <i class="fas" :class="copied === 'dana' ? 'fa-check' : 'fa-copy'"></i>
                                    <span x-text="copied === 'dana' ? 'Tersalin!' : 'Salin'"></span>
                                </button>
                            </div>
                        </div>
                        
                        <!-- GoPay -->
                        <div class="dark:bg-slate-700/50 bg-white rounded-xl p-5 border-2 border-green-500/30 hover:border-green-500 transition-all group">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center">
                                    <i class="fas fa-wallet text-white text-xl"></i>
                                </div>
                                <div>
                                    <p class="font-bold dark:text-white text-slate-800">GoPay</p>
                                    <p class="text-xs dark:text-slate-400 text-slate-500">Transfer via GoPay</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between dark:bg-slate-600/50 bg-slate-100 rounded-lg p-3">
                                <div>
                                    <p class="text-xs dark:text-slate-400 text-slate-500">Nomor GoPay</p>
                                    <p class="text-xl font-bold dark:text-white text-slate-800 font-mono">085348440470</p>
                                </div>
                                <button @click="navigator.clipboard.writeText('085348440470'); copied = 'gopay'; setTimeout(() => copied = false, 2000)" 
                                        class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg font-semibold transition flex items-center gap-2">
                                    <i class="fas" :class="copied === 'gopay' ? 'fa-check' : 'fa-copy'"></i>
                                    <span x-text="copied === 'gopay' ? 'Tersalin!' : 'Salin'"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Thank You Message -->
                    <div class="mt-4 text-center">
                        <p class="text-sm dark:text-slate-400 text-slate-600">
                            <i class="fas fa-heart text-red-400 mr-1"></i>
                            Terima kasih atas dukungan Anda! Setiap donasi sangat berarti untuk pengembangan aplikasi ini.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div onclick="showModal('items')" class="card-hover bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700/50 cursor-pointer hover:border-blue-500/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm font-medium mb-1">Total Barang</p>
                    <h3 class="text-4xl font-bold text-white"><?php echo e($total_items); ?></h3>
                    <p class="text-blue-400 text-xs mt-2"><i class="fas fa-eye mr-1"></i>Klik untuk lihat</p>
                </div>
                <div class="w-16 h-16 bg-blue-500/20 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-box text-blue-400 text-2xl"></i>
                </div>
            </div>
            <div class="mt-4 h-1 bg-slate-700 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-blue-500 to-blue-400 rounded-full" style="width: 75%"></div>
            </div>
        </div>

        <div onclick="showModal('stock')" class="card-hover bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700/50 cursor-pointer hover:border-emerald-500/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm font-medium mb-1">Total Stok</p>
                    <h3 class="text-4xl font-bold text-white"><?php echo e(number_format($total_stock)); ?></h3>
                    <p class="text-emerald-400 text-xs mt-2"><i class="fas fa-eye mr-1"></i>Klik untuk lihat</p>
                </div>
                <div class="w-16 h-16 bg-emerald-500/20 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-cubes text-emerald-400 text-2xl"></i>
                </div>
            </div>
            <div class="mt-4 h-1 bg-slate-700 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-emerald-500 to-emerald-400 rounded-full" style="width: 85%"></div>
            </div>
        </div>

        <div onclick="showModal('lowstock')" class="card-hover bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700/50 cursor-pointer hover:border-red-500/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm font-medium mb-1">Stok Menipis</p>
                    <h3 class="text-4xl font-bold text-white"><?php echo e($low_stock_count); ?></h3>
                    <p class="text-red-400 text-xs mt-2"><i class="fas fa-eye mr-1"></i>Klik untuk lihat</p>
                </div>
                <div class="w-16 h-16 bg-red-500/20 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-400 text-2xl"></i>
                </div>
            </div>
            <div class="mt-4 h-1 bg-slate-700 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-red-500 to-orange-400 rounded-full" style="width: <?php echo e($total_items > 0 ? min(($low_stock_count / $total_items) * 100, 100) : 0); ?>%"></div>
            </div>
        </div>

        <div onclick="showModal('category')" class="card-hover bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700/50 cursor-pointer hover:border-purple-500/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm font-medium mb-1">Kategori</p>
                    <h3 class="text-4xl font-bold text-white"><?php echo e($categories_count); ?></h3>
                    <p class="text-purple-400 text-xs mt-2"><i class="fas fa-eye mr-1"></i>Klik untuk lihat</p>
                </div>
                <div class="w-16 h-16 bg-purple-500/20 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-folder text-purple-400 text-2xl"></i>
                </div>
            </div>
            <div class="mt-4 h-1 bg-slate-700 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-purple-500 to-pink-400 rounded-full" style="width: 100%"></div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="card-hover bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700/50">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-white flex items-center">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/20 flex items-center justify-center mr-3">
                        <i class="fas fa-chart-line text-blue-400"></i>
                    </div>
                    Aktivitas Stok
                </h3>
                <span class="text-xs text-slate-500 bg-slate-700/50 px-3 py-1 rounded-full">6 Bulan Terakhir</span>
            </div>
            <div class="h-64"><canvas id="stockChart"></canvas></div>
        </div>

        <div class="card-hover bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700/50">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-white flex items-center">
                    <div class="w-10 h-10 rounded-xl bg-purple-500/20 flex items-center justify-center mr-3">
                        <i class="fas fa-chart-pie text-purple-400"></i>
                    </div>
                    Distribusi Kategori
                </h3>
                <span class="text-xs text-slate-500 bg-slate-700/50 px-3 py-1 rounded-full">Per Kategori</span>
            </div>
            <div class="h-64"><canvas id="categoryChart"></canvas></div>
        </div>
    </div>

    <!-- Top Items Chart -->
    <div class="card-hover bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700/50 mb-8">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-white flex items-center">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center mr-3">
                    <i class="fas fa-chart-bar text-emerald-400"></i>
                </div>
                Top 5 Barang Terbanyak
            </h3>
            <span class="text-xs text-slate-500 bg-slate-700/50 px-3 py-1 rounded-full">Berdasarkan Stok</span>
        </div>
        <div class="h-64"><canvas id="topItemsChart"></canvas></div>
    </div>

    <?php if(auth()->user()->isAdmin()): ?>
    <!-- Recent Transactions - Admin Only -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="card-hover bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700/50 overflow-hidden">
            <div class="p-6 border-b border-slate-700/50 flex items-center justify-between">
                <h3 class="text-lg font-bold text-white flex items-center">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center mr-3">
                        <i class="fas fa-arrow-down text-emerald-400"></i>
                    </div>
                    Barang Masuk Terbaru
                </h3>
                <a href="<?php echo e(route('stock-in.index')); ?>" class="text-xs text-blue-400 hover:text-blue-300">Lihat Semua <i class="fas fa-arrow-right ml-1"></i></a>
            </div>
            <div class="p-4 max-h-80 overflow-y-auto">
                <?php $__empty_1 = true; $__currentLoopData = $recent_stock_in; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stock): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-700/30 transition-all">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 flex items-center justify-center"><i class="fas fa-box text-emerald-400"></i></div>
                            <div class="ml-4">
                                <p class="font-semibold text-white"><?php echo e($stock->item->name); ?></p>
                                <p class="text-xs text-slate-500"><?php echo e($stock->date->format('d/m/Y')); ?></p>
                            </div>
                        </div>
                        <span class="bg-emerald-500/20 text-emerald-400 px-4 py-2 rounded-xl text-sm font-bold">+<?php echo e($stock->quantity); ?></span>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="text-center py-8"><i class="fas fa-inbox text-slate-500 text-3xl mb-2"></i><p class="text-slate-500">Belum ada transaksi</p></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-hover bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700/50 overflow-hidden">
            <div class="p-6 border-b border-slate-700/50 flex items-center justify-between">
                <h3 class="text-lg font-bold text-white flex items-center">
                    <div class="w-10 h-10 rounded-xl bg-red-500/20 flex items-center justify-center mr-3">
                        <i class="fas fa-arrow-up text-red-400"></i>
                    </div>
                    Barang Keluar Terbaru
                </h3>
                <a href="<?php echo e(route('stock-out.index')); ?>" class="text-xs text-blue-400 hover:text-blue-300">Lihat Semua <i class="fas fa-arrow-right ml-1"></i></a>
            </div>
            <div class="p-4 max-h-80 overflow-y-auto">
                <?php $__empty_1 = true; $__currentLoopData = $recent_stock_out; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stock): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-700/30 transition-all">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-xl bg-red-500/10 flex items-center justify-center"><i class="fas fa-box text-red-400"></i></div>
                            <div class="ml-4">
                                <p class="font-semibold text-white"><?php echo e($stock->item->name); ?></p>
                                <p class="text-xs text-slate-500"><?php echo e($stock->date->format('d/m/Y')); ?></p>
                            </div>
                        </div>
                        <span class="bg-red-500/20 text-red-400 px-4 py-2 rounded-xl text-sm font-bold">-<?php echo e($stock->quantity); ?></span>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="text-center py-8"><i class="fas fa-inbox text-slate-500 text-3xl mb-2"></i><p class="text-slate-500">Belum ada transaksi</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal -->
    <div id="dataModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-slate-800 rounded-2xl border border-slate-700 w-full max-w-4xl max-h-[85vh] overflow-hidden">
            <div class="p-6 border-b border-slate-700 flex items-center justify-between">
                <div class="flex items-center">
                    <button id="backBtn" onclick="goBack()" class="hidden w-10 h-10 rounded-xl bg-slate-700 hover:bg-slate-600 flex items-center justify-center mr-3 transition">
                        <i class="fas fa-arrow-left text-slate-400"></i>
                    </button>
                    <div id="modalIcon" class="w-10 h-10 rounded-xl bg-blue-500/20 flex items-center justify-center mr-3">
                        <i class="fas fa-box text-blue-400"></i>
                    </div>
                    <span id="modalTitleText" class="text-xl font-bold text-white">Data</span>
                </div>
                <button onclick="closeModal()" class="w-10 h-10 rounded-xl bg-slate-700 hover:bg-red-500 flex items-center justify-center transition">
                    <i class="fas fa-times text-slate-400 hover:text-white"></i>
                </button>
            </div>
            <div id="modalContent" class="p-6 overflow-y-auto max-h-[65vh]"></div>
        </div>
    </div>

    <script>
        const allItems = <?php echo json_encode($all_items ?? [], 15, 512) ?>;
        const lowStockItems = <?php echo json_encode($low_stock_items ?? [], 15, 512) ?>;
        const categories = <?php echo json_encode($categories ?? [], 15, 512) ?>;
        let modalHistory = [];
    </script>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const chartData = <?php echo json_encode($chart_data, 15, 512) ?>;
    Chart.defaults.color = '#94a3b8';
    Chart.defaults.borderColor = '#334155';

    new Chart(document.getElementById('stockChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: chartData.months,
            datasets: [
                { label: 'Barang Masuk', data: chartData.stock_in, borderColor: 'rgb(34, 197, 94)', backgroundColor: 'rgba(34, 197, 94, 0.1)', tension: 0.4, fill: true, borderWidth: 3, pointRadius: 5 },
                { label: 'Barang Keluar', data: chartData.stock_out, borderColor: 'rgb(239, 68, 68)', backgroundColor: 'rgba(239, 68, 68, 0.1)', tension: 0.4, fill: true, borderWidth: 3, pointRadius: 5 }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } }
    });

    new Chart(document.getElementById('categoryChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: chartData.category_names,
            datasets: [{ data: chartData.category_stocks, backgroundColor: ['rgba(59, 130, 246, 0.8)', 'rgba(34, 197, 94, 0.8)', 'rgba(249, 115, 22, 0.8)', 'rgba(168, 85, 247, 0.8)', 'rgba(236, 72, 153, 0.8)', 'rgba(20, 184, 166, 0.8)'], borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'right' } } }
    });

    new Chart(document.getElementById('topItemsChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: chartData.top_item_names,
            datasets: [{ label: 'Jumlah Stok', data: chartData.top_item_stocks, backgroundColor: ['rgba(59, 130, 246, 0.8)', 'rgba(34, 197, 94, 0.8)', 'rgba(249, 115, 22, 0.8)', 'rgba(168, 85, 247, 0.8)', 'rgba(236, 72, 153, 0.8)'], borderRadius: 8 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } }
    });

    function showModal(type, categoryId = null) {
        const modal = document.getElementById('dataModal');
        const titleText = document.getElementById('modalTitleText');
        const modalIcon = document.getElementById('modalIcon');
        const content = document.getElementById('modalContent');
        const backBtn = document.getElementById('backBtn');
        
        let html = '';
        let iconClass = '';
        let iconBg = '';
        
        if (type === 'items' || type === 'stock') {
            modalHistory = [];
            backBtn.classList.add('hidden');
            titleText.textContent = type === 'items' ? 'Daftar Semua Barang' : 'Daftar Stok Barang';
            iconClass = type === 'items' ? 'fa-box text-blue-400' : 'fa-cubes text-emerald-400';
            iconBg = type === 'items' ? 'bg-blue-500/20' : 'bg-emerald-500/20';
            
            if (allItems.length === 0) {
                html = '<div class="text-center py-8"><i class="fas fa-inbox text-slate-500 text-4xl mb-3"></i><p class="text-slate-400">Belum ada data barang</p></div>';
            } else {
                html = '<div class="space-y-3">';
                allItems.forEach(item => {
                    html += `
                        <div class="flex items-center justify-between p-4 bg-slate-700/30 rounded-xl hover:bg-slate-700/50 transition">
                            <div class="flex items-center">
                                <div class="w-12 h-12 rounded-xl bg-slate-600/50 flex items-center justify-center mr-4"><i class="fas fa-box text-slate-400"></i></div>
                                <div>
                                    <p class="font-semibold text-white">${item.name}</p>
                                    <p class="text-xs text-slate-500">${item.code} • ${item.category?.name || '-'}</p>
                                </div>
                            </div>
                            <span class="bg-emerald-500/20 text-emerald-400 px-4 py-2 rounded-xl font-bold">${item.stock} ${item.unit?.symbol || ''}</span>
                        </div>`;
                });
                html += '</div>';
            }
        } else if (type === 'lowstock') {
            modalHistory = [];
            backBtn.classList.add('hidden');
            titleText.textContent = 'Barang Stok Menipis';
            iconClass = 'fa-exclamation-triangle text-red-400';
            iconBg = 'bg-red-500/20';
            
            if (lowStockItems.length === 0) {
                html = '<div class="text-center py-8"><i class="fas fa-check-circle text-emerald-400 text-4xl mb-3"></i><p class="text-slate-400">Tidak ada barang yang stoknya menipis</p></div>';
            } else {
                html = '<div class="space-y-3">';
                lowStockItems.forEach(item => {
                    const status = item.stock === 0 ? '<span class="bg-red-500 text-white px-2 py-1 rounded text-xs">HABIS</span>' : '<span class="bg-orange-500/20 text-orange-400 px-2 py-1 rounded text-xs">MENIPIS</span>';
                    html += `
                        <div class="flex items-center justify-between p-4 bg-red-500/10 border border-red-500/30 rounded-xl">
                            <div class="flex items-center">
                                <div class="w-12 h-12 rounded-xl bg-red-500/20 flex items-center justify-center mr-4"><i class="fas fa-exclamation-triangle text-red-400"></i></div>
                                <div>
                                    <p class="font-semibold text-white">${item.name}</p>
                                    <p class="text-xs text-slate-500">${item.code} • Min: ${item.minimum_stock}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="bg-red-500/20 text-red-400 px-4 py-2 rounded-xl font-bold">${item.stock} ${item.unit?.symbol || ''}</span>
                                ${status}
                            </div>
                        </div>`;
                });
                html += '</div>';
            }
        } else if (type === 'category') {
            modalHistory = [];
            backBtn.classList.add('hidden');
            titleText.textContent = 'Daftar Kategori';
            iconClass = 'fa-folder text-purple-400';
            iconBg = 'bg-purple-500/20';
            
            html = '<p class="text-slate-400 text-sm mb-4"><i class="fas fa-info-circle mr-2"></i>Klik kategori untuk melihat daftar barang</p>';
            html += '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
            categories.forEach(cat => {
                html += `
                    <div onclick="showCategoryItems(${cat.id})" class="p-4 bg-slate-700/30 rounded-xl hover:bg-purple-500/20 hover:border-purple-500/50 border border-transparent cursor-pointer transition group">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-12 h-12 rounded-xl bg-purple-500/20 flex items-center justify-center mr-3 group-hover:bg-purple-500/30">
                                    <i class="fas fa-folder text-purple-400"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-white group-hover:text-purple-400">${cat.name}</p>
                                    <p class="text-xs text-slate-500">${cat.items_count || 0} barang</p>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-slate-500 group-hover:text-purple-400"></i>
                        </div>
                        ${cat.description ? `<p class="text-sm text-slate-400 mt-2 pl-15">${cat.description}</p>` : ''}
                    </div>`;
            });
            html += '</div>';
        } else if (type === 'categoryItems' && categoryId) {
            const cat = categories.find(c => c.id === categoryId);
            if (!cat) return;
            
            backBtn.classList.remove('hidden');
            modalHistory.push('category');
            titleText.textContent = `Barang: ${cat.name}`;
            iconClass = 'fa-boxes text-purple-400';
            iconBg = 'bg-purple-500/20';
            
            if (!cat.items || cat.items.length === 0) {
                html = '<div class="text-center py-8"><i class="fas fa-inbox text-slate-500 text-4xl mb-3"></i><p class="text-slate-400">Tidak ada barang dalam kategori ini</p></div>';
            } else {
                html = '<div class="space-y-3">';
                cat.items.forEach(item => {
                    const stockColor = item.stock <= item.minimum_stock ? 'bg-red-500/20 text-red-400' : 'bg-emerald-500/20 text-emerald-400';
                    html += `
                        <div class="flex items-center justify-between p-4 bg-slate-700/30 rounded-xl hover:bg-slate-700/50 transition">
                            <div class="flex items-center">
                                <div class="w-12 h-12 rounded-xl bg-purple-500/10 flex items-center justify-center mr-4"><i class="fas fa-box text-purple-400"></i></div>
                                <div>
                                    <p class="font-semibold text-white">${item.name}</p>
                                    <p class="text-xs text-slate-500">${item.code}</p>
                                </div>
                            </div>
                            <span class="${stockColor} px-4 py-2 rounded-xl font-bold">${item.stock} ${item.unit?.symbol || ''}</span>
                        </div>`;
                });
                html += '</div>';
            }
        }
        
        modalIcon.className = `w-10 h-10 rounded-xl ${iconBg} flex items-center justify-center mr-3`;
        modalIcon.innerHTML = `<i class="fas ${iconClass}"></i>`;
        content.innerHTML = html;
        modal.classList.remove('hidden');
    }

    function showCategoryItems(categoryId) {
        showModal('categoryItems', categoryId);
    }

    function goBack() {
        if (modalHistory.length > 0) {
            const prev = modalHistory.pop();
            showModal(prev);
        }
    }

    function closeModal() {
        document.getElementById('dataModal').classList.add('hidden');
        modalHistory = [];
    }

    document.getElementById('dataModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeModal();
    });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\Ryana\Downloads\Apk Vibe Coding\website barang 1.1\resources\views/dashboard/index.blade.php ENDPATH**/ ?>