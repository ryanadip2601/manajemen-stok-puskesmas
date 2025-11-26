<?php $__env->startSection('title', 'Barang Masuk'); ?>
<?php $__env->startSection('header', 'Barang Masuk'); ?>
<?php $__env->startSection('breadcrumb', 'Transaksi / Barang Masuk'); ?>

<?php $__env->startSection('content'); ?>
    <div class="bg-white rounded-xl shadow-md">
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800">Data Barang Masuk</h3>
            <a href="<?php echo e(route('stock-in.create')); ?>" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition flex items-center">
                <i class="fas fa-plus mr-2"></i>
                Tambah Barang Masuk
            </a>
        </div>

        <div class="p-6">
            <form method="GET" action="<?php echo e(route('stock-in.index')); ?>" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <select 
                        name="item_id" 
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Semua Barang</option>
                        <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($item->id); ?>" <?php echo e(request('item_id') == $item->id ? 'selected' : ''); ?>>
                                <?php echo e($item->name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <input 
                        type="date" 
                        name="start_date" 
                        value="<?php echo e(request('start_date')); ?>"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Tanggal Mulai"
                    >
                    <input 
                        type="date" 
                        name="end_date" 
                        value="<?php echo e(request('end_date')); ?>"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Tanggal Akhir"
                    >
                    <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Nama Barang</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Kategori</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Jumlah</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">User</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Catatan</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php $__empty_1 = true; $__currentLoopData = $stockIns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stockIn): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-800"><?php echo e($stockIn->date->format('d/m/Y')); ?></td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-800"><?php echo e($stockIn->item->name); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?php echo e($stockIn->item->category->name); ?></td>
                                <td class="px-6 py-4">
                                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">
                                        +<?php echo e($stockIn->quantity); ?> <?php echo e($stockIn->item->unit->symbol); ?>

                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?php echo e($stockIn->user->name); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?php echo e($stockIn->notes ?? '-'); ?></td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="<?php echo e(route('stock-in.edit', $stockIn->id)); ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded-lg text-sm transition">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="<?php echo e(route('stock-in.destroy', $stockIn->id)); ?>" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-lg text-sm transition">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                                    <p>Belum ada data barang masuk</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                <?php echo e($stockIns->links()); ?>

            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\Ryana\Downloads\Apk Vibe Coding\website barang 1.1\resources\views/stock_in/index.blade.php ENDPATH**/ ?>