<?php $__env->startSection('title', 'Tambah Barang Masuk'); ?>
<?php $__env->startSection('header', 'Tambah Barang Masuk'); ?>
<?php $__env->startSection('breadcrumb', 'Transaksi / Barang Masuk / Tambah'); ?>

<?php $__env->startSection('content'); ?>
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-xl shadow-md">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-800">Form Tambah Barang Masuk</h3>
            </div>

            <form action="<?php echo e(route('stock-in.store')); ?>" method="POST" class="p-6">
                <?php echo csrf_field(); ?>

                <div class="mb-6">
                    <label for="item_id" class="block text-gray-700 font-semibold mb-2">
                        Pilih Barang <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="item_id" 
                        name="item_id" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 <?php $__errorArgs = ['item_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                        required
                        onchange="updateItemInfo(this)"
                    >
                        <option value="">Pilih Barang</option>
                        <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option 
                                value="<?php echo e($item->id); ?>" 
                                data-stock="<?php echo e($item->stock); ?>"
                                data-unit="<?php echo e($item->unit->symbol); ?>"
                                data-category="<?php echo e($item->category->name); ?>"
                                <?php echo e(old('item_id') == $item->id ? 'selected' : ''); ?>

                            >
                                <?php echo e($item->code); ?> - <?php echo e($item->name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['item_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-red-500 text-sm mt-1"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    <div id="item-info" class="mt-2 text-sm text-gray-600 hidden">
                        <p><strong>Kategori:</strong> <span id="info-category"></span></p>
                        <p><strong>Stok Saat Ini:</strong> <span id="info-stock"></span> <span id="info-unit"></span></p>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="quantity" class="block text-gray-700 font-semibold mb-2">
                        Jumlah Masuk <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        id="quantity" 
                        name="quantity" 
                        value="<?php echo e(old('quantity')); ?>"
                        min="1"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 <?php $__errorArgs = ['quantity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                        placeholder="Masukkan jumlah"
                        required
                    >
                    <?php $__errorArgs = ['quantity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-red-500 text-sm mt-1"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="mb-6">
                    <label for="date" class="block text-gray-700 font-semibold mb-2">
                        Tanggal <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        id="date" 
                        name="date" 
                        value="<?php echo e(old('date', date('Y-m-d'))); ?>"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 <?php $__errorArgs = ['date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                        required
                    >
                    <?php $__errorArgs = ['date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-red-500 text-sm mt-1"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="mb-6">
                    <label for="notes" class="block text-gray-700 font-semibold mb-2">
                        Catatan
                    </label>
                    <textarea 
                        id="notes" 
                        name="notes" 
                        rows="3"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Catatan tambahan (opsional)"
                    ><?php echo e(old('notes')); ?></textarea>
                </div>

                <div class="flex items-center gap-4">
                    <button 
                        type="submit" 
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg transition font-semibold"
                    >
                        <i class="fas fa-save mr-2"></i>
                        Simpan
                    </button>
                    <a 
                        href="<?php echo e(route('stock-in.index')); ?>" 
                        class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg transition font-semibold"
                    >
                        <i class="fas fa-arrow-left mr-2"></i>
                        Kembali
                    </a>
                </div>
            </form>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
function updateItemInfo(select) {
    const option = select.options[select.selectedIndex];
    const infoDiv = document.getElementById('item-info');
    
    if (option.value) {
        document.getElementById('info-category').textContent = option.dataset.category;
        document.getElementById('info-stock').textContent = option.dataset.stock;
        document.getElementById('info-unit').textContent = option.dataset.unit;
        infoDiv.classList.remove('hidden');
    } else {
        infoDiv.classList.add('hidden');
    }
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\Ryana\Downloads\Apk Vibe Coding\website barang 1.1\resources\views/stock_in/create.blade.php ENDPATH**/ ?>