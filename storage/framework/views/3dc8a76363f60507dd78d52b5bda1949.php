<?php $__env->startSection('title', 'Daftar Kategori'); ?>
<?php $__env->startSection('header', 'Daftar Kategori'); ?>
<?php $__env->startSection('breadcrumb', 'Master Data / Kategori'); ?>

<?php $__env->startSection('content'); ?>
    <div class="bg-white rounded-xl shadow-md">
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800">Data Kategori</h3>
            <a href="<?php echo e(route('categories.create')); ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition flex items-center">
                <i class="fas fa-plus mr-2"></i>
                Tambah Kategori
            </a>
        </div>

        <div class="p-6">
            <form method="GET" action="<?php echo e(route('categories.index')); ?>" class="mb-6">
                <div class="flex gap-4">
                    <input 
                        type="text" 
                        name="search" 
                        value="<?php echo e(request('search')); ?>"
                        placeholder="Cari kategori..." 
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg transition">
                        <i class="fas fa-search mr-2"></i>Cari
                    </button>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">No</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Nama Kategori</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Deskripsi</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Jumlah Barang</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php $__empty_1 = true; $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-800"><?php echo e($loop->iteration + ($categories->currentPage() - 1) * $categories->perPage()); ?></td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-800"><?php echo e($category->name); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?php echo e($category->description ?? '-'); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-800">
                                    <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-semibold">
                                        <?php echo e($category->items_count); ?> barang
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="<?php echo e(route('categories.edit', $category->id)); ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded-lg text-sm transition">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="<?php echo e(route('categories.destroy', $category->id)); ?>" method="POST" onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">
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
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-folder-open text-4xl mb-3 text-gray-300"></i>
                                    <p>Belum ada data kategori</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                <?php echo e($categories->links()); ?>

            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\Ryana\Downloads\Apk Vibe Coding\website barang 1.1\resources\views/categories/index.blade.php ENDPATH**/ ?>