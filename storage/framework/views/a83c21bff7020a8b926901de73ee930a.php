<?php $__env->startSection('title', 'Laporan Bulanan'); ?>
<?php $__env->startSection('header', 'Laporan Bulanan'); ?>
<?php $__env->startSection('breadcrumb', 'Laporan / Bulanan'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
    <!-- Filter Section -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6 transition-colors duration-300">
        <form method="GET" action="<?php echo e(route('reports.monthly')); ?>" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Bulan</label>
                <select name="month" class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                    <?php $__currentLoopData = $months; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $num => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($num); ?>" <?php echo e($month == $num ? 'selected' : ''); ?>><?php echo e($name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-medium dark:text-slate-400 text-slate-600 mb-2">Tahun</label>
                <select name="year" class="w-full dark:bg-slate-700/50 bg-slate-100 dark:border-slate-600 border-slate-300 rounded-xl px-4 py-3 dark:text-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                    <?php $__currentLoopData = $years; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $y): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($y); ?>" <?php echo e($year == $y ? 'selected' : ''); ?>><?php echo e($y); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:from-emerald-600 hover:to-teal-700 transition-all shadow-lg">
                <i class="fas fa-filter mr-2"></i>Filter
            </button>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6 transition-colors duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Total Barang Masuk</p>
                    <p class="text-3xl font-bold text-green-400 mt-1"><?php echo e(number_format($summary['total_stock_in'])); ?></p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-green-500/20 flex items-center justify-center">
                    <i class="fas fa-arrow-down text-green-400 text-xl"></i>
                </div>
            </div>
            <p class="text-xs dark:text-slate-500 text-slate-400 mt-2"><?php echo e($summary['transaction_count_in']); ?> transaksi</p>
        </div>

        <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6 transition-colors duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Total Barang Keluar</p>
                    <p class="text-3xl font-bold text-red-400 mt-1"><?php echo e(number_format($summary['total_stock_out'])); ?></p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-red-500/20 flex items-center justify-center">
                    <i class="fas fa-arrow-up text-red-400 text-xl"></i>
                </div>
            </div>
            <p class="text-xs dark:text-slate-500 text-slate-400 mt-2"><?php echo e($summary['transaction_count_out']); ?> transaksi</p>
        </div>

        <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6 transition-colors duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Selisih Stok</p>
                    <p class="text-3xl font-bold <?php echo e($summary['total_stock_in'] - $summary['total_stock_out'] >= 0 ? 'text-blue-400' : 'text-orange-400'); ?> mt-1">
                        <?php echo e($summary['total_stock_in'] - $summary['total_stock_out'] >= 0 ? '+' : ''); ?><?php echo e(number_format($summary['total_stock_in'] - $summary['total_stock_out'])); ?>

                    </p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-blue-500/20 flex items-center justify-center">
                    <i class="fas fa-exchange-alt text-blue-400 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 p-6 transition-colors duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm dark:text-slate-400 text-slate-500">Total Transaksi</p>
                    <p class="text-3xl font-bold text-purple-400 mt-1"><?php echo e(number_format($summary['transaction_count_in'] + $summary['transaction_count_out'])); ?></p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-purple-500/20 flex items-center justify-center">
                    <i class="fas fa-receipt text-purple-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Buttons -->
    <div class="flex flex-wrap gap-3">
        <a href="<?php echo e(route('reports.monthly.export', ['month' => $month, 'year' => $year, 'type' => 'all'])); ?>" 
           class="px-5 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 text-white rounded-xl font-semibold hover:from-cyan-600 hover:to-blue-700 transition-all shadow-lg inline-flex items-center">
            <i class="fas fa-file-csv mr-2"></i>Export Semua (CSV)
        </a>
        <a href="<?php echo e(route('reports.monthly.export', ['month' => $month, 'year' => $year, 'type' => 'in'])); ?>" 
           class="px-5 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl font-semibold hover:from-green-600 hover:to-emerald-700 transition-all shadow-lg inline-flex items-center">
            <i class="fas fa-download mr-2"></i>Export Barang Masuk
        </a>
        <a href="<?php echo e(route('reports.monthly.export', ['month' => $month, 'year' => $year, 'type' => 'out'])); ?>" 
           class="px-5 py-3 bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-xl font-semibold hover:from-red-600 hover:to-rose-700 transition-all shadow-lg inline-flex items-center">
            <i class="fas fa-download mr-2"></i>Export Barang Keluar
        </a>
    </div>

    <!-- Stock In Table -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden transition-colors duration-300">
        <div class="p-6 border-b dark:border-slate-700/50 border-slate-200">
            <h3 class="text-lg font-bold dark:text-white text-slate-800 flex items-center">
                <div class="w-10 h-10 rounded-lg bg-green-500/20 flex items-center justify-center mr-3">
                    <i class="fas fa-arrow-down text-green-400"></i>
                </div>
                Barang Masuk - <?php echo e($months[$month]); ?> <?php echo e($year); ?>

            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="dark:bg-slate-700/30 bg-slate-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Tanggal</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Kode</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Nama Barang</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Kategori</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Jumlah</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50 divide-slate-200">
                    <?php $__empty_1 = true; $__currentLoopData = $stockIn; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 dark:text-slate-300 text-slate-700"><?php echo e($record->date->format('d/m/Y')); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-mono dark:bg-slate-600/50 bg-slate-200 rounded dark:text-slate-300 text-slate-600"><?php echo e($record->item->code ?? '-'); ?></span>
                        </td>
                        <td class="px-6 py-4 dark:text-white text-slate-800 font-medium"><?php echo e($record->item->name ?? '-'); ?></td>
                        <td class="px-6 py-4 dark:text-slate-400 text-slate-600"><?php echo e($record->item->category->name ?? '-'); ?></td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 bg-green-500/20 text-green-400 rounded-lg font-semibold">+<?php echo e($record->quantity); ?> <?php echo e($record->item->unit->symbol ?? ''); ?></span>
                        </td>
                        <td class="px-6 py-4 dark:text-slate-400 text-slate-600 text-sm"><?php echo e($record->notes ?? '-'); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center dark:text-slate-500 text-slate-400">
                            <i class="fas fa-inbox text-4xl mb-3 block"></i>
                            Tidak ada data barang masuk
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Stock Out Table -->
    <div class="dark:bg-slate-800/50 bg-white rounded-2xl shadow-xl dark:border dark:border-slate-700/50 border border-slate-200 overflow-hidden transition-colors duration-300">
        <div class="p-6 border-b dark:border-slate-700/50 border-slate-200">
            <h3 class="text-lg font-bold dark:text-white text-slate-800 flex items-center">
                <div class="w-10 h-10 rounded-lg bg-red-500/20 flex items-center justify-center mr-3">
                    <i class="fas fa-arrow-up text-red-400"></i>
                </div>
                Barang Keluar - <?php echo e($months[$month]); ?> <?php echo e($year); ?>

            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="dark:bg-slate-700/30 bg-slate-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Tanggal</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Kode</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Nama Barang</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Kategori</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Jumlah</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold dark:text-slate-400 text-slate-600 uppercase">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700/50 divide-slate-200">
                    <?php $__empty_1 = true; $__currentLoopData = $stockOut; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="dark:hover:bg-slate-700/30 hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 dark:text-slate-300 text-slate-700"><?php echo e($record->date->format('d/m/Y')); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-mono dark:bg-slate-600/50 bg-slate-200 rounded dark:text-slate-300 text-slate-600"><?php echo e($record->item->code ?? '-'); ?></span>
                        </td>
                        <td class="px-6 py-4 dark:text-white text-slate-800 font-medium"><?php echo e($record->item->name ?? '-'); ?></td>
                        <td class="px-6 py-4 dark:text-slate-400 text-slate-600"><?php echo e($record->item->category->name ?? '-'); ?></td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 bg-red-500/20 text-red-400 rounded-lg font-semibold">-<?php echo e($record->quantity); ?> <?php echo e($record->item->unit->symbol ?? ''); ?></span>
                        </td>
                        <td class="px-6 py-4 dark:text-slate-400 text-slate-600 text-sm"><?php echo e($record->notes ?? '-'); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center dark:text-slate-500 text-slate-400">
                            <i class="fas fa-inbox text-4xl mb-3 block"></i>
                            Tidak ada data barang keluar
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\Ryana\Downloads\Apk Vibe Coding\website barang 1.1\resources\views/reports/monthly.blade.php ENDPATH**/ ?>