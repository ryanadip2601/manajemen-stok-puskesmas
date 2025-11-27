<?php $__env->startSection('title', 'Profil Saya'); ?>
<?php $__env->startSection('header', 'Profil Saya'); ?>
<?php $__env->startSection('breadcrumb', 'Kelola informasi akun dan password Anda'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-4xl mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Profile Info Card -->
        <div class="card-hover bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700/50 overflow-hidden">
            <div class="p-6 border-b border-slate-700/50">
                <h3 class="text-lg font-bold text-white flex items-center">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/20 flex items-center justify-center mr-3">
                        <i class="fas fa-user text-blue-400"></i>
                    </div>
                    Informasi Profil
                </h3>
            </div>
            
            <div class="p-6">
                <form action="<?php echo e(route('profile.update')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>
                    
                    <div class="mb-4">
                        <label class="block text-slate-400 text-sm font-medium mb-2">
                            <i class="fas fa-user mr-2"></i>Nama Lengkap
                        </label>
                        <input 
                            type="text" 
                            name="name" 
                            value="<?php echo e(old('name', auth()->user()->name)); ?>"
                            class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-blue-500"
                            required
                        >
                        <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-red-400 text-sm mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="mb-4">
                        <label class="block text-slate-400 text-sm font-medium mb-2">
                            <i class="fas fa-envelope mr-2"></i>Email / Username
                        </label>
                        <input 
                            type="text" 
                            value="<?php echo e(auth()->user()->email); ?>"
                            class="w-full bg-slate-700/30 border border-slate-600 rounded-xl px-4 py-3 text-slate-400 cursor-not-allowed"
                            disabled
                        >
                        <p class="text-slate-500 text-xs mt-1">Email tidak dapat diubah</p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-slate-400 text-sm font-medium mb-2">
                            <i class="fab fa-whatsapp mr-2"></i>Nomor WhatsApp
                        </label>
                        <input 
                            type="text" 
                            name="phone" 
                            value="<?php echo e(old('phone', auth()->user()->phone)); ?>"
                            class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-blue-500"
                            placeholder="08xxxxxxxxxx"
                        >
                    </div>

                    <div class="mb-6">
                        <label class="block text-slate-400 text-sm font-medium mb-2">
                            <i class="fas fa-shield-alt mr-2"></i>Role
                        </label>
                        <div class="flex items-center">
                            <?php if(auth()->user()->isAdmin()): ?>
                                <span class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-4 py-2 rounded-xl font-bold text-sm">
                                    <i class="fas fa-crown mr-2"></i>Administrator
                                </span>
                            <?php else: ?>
                                <span class="bg-gradient-to-r from-blue-500 to-cyan-500 text-white px-4 py-2 rounded-xl font-bold text-sm">
                                    <i class="fas fa-user-tie mr-2"></i>Pegawai
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-blue-600 text-white py-3 rounded-xl font-bold hover:from-blue-600 hover:to-blue-700 transition-all">
                        <i class="fas fa-save mr-2"></i>Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>

        <!-- Change Password Card -->
        <div class="card-hover bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700/50 overflow-hidden">
            <div class="p-6 border-b border-slate-700/50">
                <h3 class="text-lg font-bold text-white flex items-center">
                    <div class="w-10 h-10 rounded-xl bg-orange-500/20 flex items-center justify-center mr-3">
                        <i class="fas fa-lock text-orange-400"></i>
                    </div>
                    Ganti Password
                </h3>
            </div>
            
            <div class="p-6">
                <form action="<?php echo e(route('profile.password')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>
                    
                    <div class="mb-4">
                        <label class="block text-slate-400 text-sm font-medium mb-2">
                            <i class="fas fa-key mr-2"></i>Password Saat Ini
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                name="current_password"
                                id="current_password"
                                class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-orange-500"
                                placeholder="Masukkan password saat ini"
                                required
                            >
                            <button type="button" onclick="togglePassword('current_password', 'icon1')" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-slate-500 hover:text-slate-300">
                                <i class="fas fa-eye" id="icon1"></i>
                            </button>
                        </div>
                        <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-red-400 text-sm mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="mb-4">
                        <label class="block text-slate-400 text-sm font-medium mb-2">
                            <i class="fas fa-lock mr-2"></i>Password Baru
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                name="password"
                                id="new_password"
                                class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-orange-500"
                                placeholder="Minimal 6 karakter"
                                required
                            >
                            <button type="button" onclick="togglePassword('new_password', 'icon2')" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-slate-500 hover:text-slate-300">
                                <i class="fas fa-eye" id="icon2"></i>
                            </button>
                        </div>
                        <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-red-400 text-sm mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="mb-6">
                        <label class="block text-slate-400 text-sm font-medium mb-2">
                            <i class="fas fa-lock mr-2"></i>Konfirmasi Password Baru
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                name="password_confirmation"
                                id="confirm_password"
                                class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-orange-500"
                                placeholder="Ulangi password baru"
                                required
                            >
                            <button type="button" onclick="togglePassword('confirm_password', 'icon3')" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-slate-500 hover:text-slate-300">
                                <i class="fas fa-eye" id="icon3"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-gradient-to-r from-orange-500 to-red-500 text-white py-3 rounded-xl font-bold hover:from-orange-600 hover:to-red-600 transition-all">
                        <i class="fas fa-key mr-2"></i>Ganti Password
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Account Info -->
    <div class="mt-6 bg-slate-800/50 rounded-2xl border border-slate-700/50 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="w-16 h-16 bg-gradient-to-br from-emerald-400 to-teal-600 rounded-2xl flex items-center justify-center mr-4">
                    <i class="fas fa-user text-white text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-white"><?php echo e(auth()->user()->name); ?></h3>
                    <p class="text-slate-400"><?php echo e(auth()->user()->email); ?></p>
                    <p class="text-slate-500 text-sm">Bergabung sejak <?php echo e(auth()->user()->created_at->translatedFormat('d F Y')); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\Ryana\Downloads\Apk Vibe Coding\website barang 1.1\resources\views/auth/profile.blade.php ENDPATH**/ ?>