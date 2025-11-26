<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('header', 'Dashboard'); ?>
<?php $__env->startSection('breadcrumb', 'Halaman Utama / Dashboard'); ?>

<?php $__env->startSection('content'); ?>
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-semibold mb-1">Total Barang</p>
                    <h3 class="text-3xl font-bold text-gray-800"><?php echo e($total_items); ?></h3>
                </div>
                <div class="bg-blue-100 rounded-full p-4">
                    <i class="fas fa-box text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-semibold mb-1">Total Stok</p>
                    <h3 class="text-3xl font-bold text-gray-800"><?php echo e($total_stock); ?></h3>
                </div>
                <div class="bg-green-100 rounded-full p-4">
                    <i class="fas fa-cubes text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-red-500 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-semibold mb-1">Barang Hampir Habis</p>
                    <h3 class="text-3xl font-bold text-gray-800"><?php echo e($low_stock_count); ?></h3>
                </div>
                <div class="bg-red-100 rounded-full p-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-purple-500 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-semibold mb-1">Kategori</p>
                    <h3 class="text-3xl font-bold text-gray-800"><?php echo e($categories_count); ?></h3>
                </div>
                <div class="bg-purple-100 rounded-full p-4">
                    <i class="fas fa-folder text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Recent Stock In -->
        <div class="bg-white rounded-xl shadow-md">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 flex items-center">
                    <i class="fas fa-arrow-down text-green-600 mr-2"></i>
                    Barang Masuk Terbaru
                </h3>
            </div>
            <div class="p-6">
                <?php $__empty_1 = true; $__currentLoopData = $recent_stock_in; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stock): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                        <div class="flex-1">
                            <p class="font-semibold text-gray-800"><?php echo e($stock->item->name); ?></p>
                            <p class="text-sm text-gray-500"><?php echo e($stock->user->name); ?> • <?php echo e($stock->date->format('d/m/Y')); ?></p>
                        </div>
                        <div class="text-right">
                            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">
                                +<?php echo e($stock->quantity); ?> <?php echo e($stock->item->unit->symbol); ?>

                            </span>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="text-gray-500 text-center py-4">Belum ada transaksi barang masuk</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Stock Out -->
        <div class="bg-white rounded-xl shadow-md">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 flex items-center">
                    <i class="fas fa-arrow-up text-red-600 mr-2"></i>
                    Barang Keluar Terbaru
                </h3>
            </div>
            <div class="p-6">
                <?php $__empty_1 = true; $__currentLoopData = $recent_stock_out; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stock): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                        <div class="flex-1">
                            <p class="font-semibold text-gray-800"><?php echo e($stock->item->name); ?></p>
                            <p class="text-sm text-gray-500"><?php echo e($stock->user->name); ?> • <?php echo e($stock->date->format('d/m/Y')); ?></p>
                        </div>
                        <div class="text-right">
                            <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-semibold">
                                -<?php echo e($stock->quantity); ?> <?php echo e($stock->item->unit->symbol); ?>

                            </span>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="text-gray-500 text-center py-4">Belum ada transaksi barang keluar</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Low Stock Items -->
    <?php if($low_stock_items->count() > 0): ?>
        <div class="bg-white rounded-xl shadow-md">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                    Barang Hampir Habis
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Kode</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Nama Barang</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Kategori</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Stok Saat Ini</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Stok Minimum</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php $__currentLoopData = $low_stock_items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-800"><?php echo e($item->code); ?></td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-800"><?php echo e($item->name); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?php echo e($item->category->name); ?></td>
                                <td class="px-6 py-4">
                                    <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-semibold">
                                        <?php echo e($item->stock); ?> <?php echo e($item->unit->symbol); ?>

                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?php echo e($item->minimum_stock); ?> <?php echo e($item->unit->symbol); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\Ryana\Downloads\Apk Vibe Coding\website barang 1.1\resources\views/dashboard/index.blade.php ENDPATH**/ ?>