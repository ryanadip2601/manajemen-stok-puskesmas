<?php
/**
 * ============================================================
 * SIMBAR - Setup & Migration Tool untuk Hostinger
 * ============================================================
 * 
 * CARA PENGGUNAAN:
 * 1. Upload semua file ke public_html di Hostinger
 * 2. Pastikan .env sudah dikonfigurasi dengan benar
 * 3. Akses: https://domainanda.com/setup.php
 * 4. HAPUS FILE INI SETELAH SELESAI!
 * 
 * ============================================================
 */

// Security: Hanya bisa diakses sekali atau dengan key
$SETUP_KEY = 'simbar2024'; // Ganti dengan key rahasia Anda

// Cek authorization
$authorized = false;
if (isset($_GET['key']) && $_GET['key'] === $SETUP_KEY) {
    $authorized = true;
}

if (!$authorized && file_exists(__DIR__ . '/../storage/setup_completed.lock')) {
    die('Setup sudah selesai. Hapus file ini untuk keamanan.');
}

// Load Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Start HTML
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIMBAR - Setup Tool</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-900 text-white min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-gradient-to-br from-emerald-400 to-teal-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                <i class="fas fa-boxes-stacked text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-emerald-400 to-teal-400 bg-clip-text text-transparent">SIMBAR Setup Tool</h1>
            <p class="text-slate-400 mt-2">Sistem Manajemen Barang - Puskesmas Karang Rejo</p>
        </div>

        <?php if (!$authorized): ?>
        <!-- Authorization Form -->
        <div class="bg-slate-800 rounded-2xl p-8 border border-slate-700 max-w-md mx-auto">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                <i class="fas fa-lock text-amber-400"></i>
                Masukkan Setup Key
            </h2>
            <form method="GET" class="space-y-4">
                <input type="text" name="key" placeholder="Setup Key..." 
                       class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-xl focus:border-emerald-500 focus:outline-none">
                <button type="submit" class="w-full py-3 bg-gradient-to-r from-emerald-500 to-teal-500 rounded-xl font-bold hover:from-emerald-600 hover:to-teal-600 transition">
                    <i class="fas fa-unlock mr-2"></i>Masuk
                </button>
            </form>
            <p class="text-xs text-slate-500 mt-4 text-center">Default key: simbar2024</p>
        </div>
        <?php else: ?>

        <!-- Main Setup Panel -->
        <div class="space-y-6">
            <!-- System Check -->
            <div class="bg-slate-800 rounded-2xl p-6 border border-slate-700">
                <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                    <i class="fas fa-check-circle text-emerald-400"></i>
                    System Check
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php
                    $checks = [
                        ['PHP Version >= 8.1', version_compare(PHP_VERSION, '8.1.0', '>='), PHP_VERSION],
                        ['PDO Extension', extension_loaded('pdo'), extension_loaded('pdo') ? 'Loaded' : 'Missing'],
                        ['PDO MySQL', extension_loaded('pdo_mysql'), extension_loaded('pdo_mysql') ? 'Loaded' : 'Missing'],
                        ['OpenSSL', extension_loaded('openssl'), extension_loaded('openssl') ? 'Loaded' : 'Missing'],
                        ['Mbstring', extension_loaded('mbstring'), extension_loaded('mbstring') ? 'Loaded' : 'Missing'],
                        ['Tokenizer', extension_loaded('tokenizer'), extension_loaded('tokenizer') ? 'Loaded' : 'Missing'],
                        ['XML', extension_loaded('xml'), extension_loaded('xml') ? 'Loaded' : 'Missing'],
                        ['Ctype', extension_loaded('ctype'), extension_loaded('ctype') ? 'Loaded' : 'Missing'],
                        ['JSON', extension_loaded('json'), extension_loaded('json') ? 'Loaded' : 'Missing'],
                        ['Fileinfo', extension_loaded('fileinfo'), extension_loaded('fileinfo') ? 'Loaded' : 'Missing'],
                        ['Storage Writable', is_writable(__DIR__ . '/../storage'), is_writable(__DIR__ . '/../storage') ? 'Writable' : 'Not Writable'],
                        ['Bootstrap Cache Writable', is_writable(__DIR__ . '/../bootstrap/cache'), is_writable(__DIR__ . '/../bootstrap/cache') ? 'Writable' : 'Not Writable'],
                    ];
                    
                    foreach ($checks as $check): ?>
                    <div class="flex items-center justify-between p-3 bg-slate-700/50 rounded-lg">
                        <span class="text-sm"><?= $check[0] ?></span>
                        <span class="flex items-center gap-2">
                            <span class="text-xs text-slate-400"><?= $check[2] ?></span>
                            <?php if ($check[1]): ?>
                                <i class="fas fa-check-circle text-emerald-400"></i>
                            <?php else: ?>
                                <i class="fas fa-times-circle text-red-400"></i>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Database Connection -->
            <div class="bg-slate-800 rounded-2xl p-6 border border-slate-700">
                <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                    <i class="fas fa-database text-blue-400"></i>
                    Database Connection
                </h2>
                <?php
                $dbConnected = false;
                $dbError = '';
                try {
                    DB::connection()->getPdo();
                    $dbConnected = true;
                    $dbName = DB::connection()->getDatabaseName();
                } catch (Exception $e) {
                    $dbError = $e->getMessage();
                }
                ?>
                
                <?php if ($dbConnected): ?>
                <div class="p-4 bg-emerald-500/20 border border-emerald-500/50 rounded-xl">
                    <p class="flex items-center gap-2 text-emerald-400">
                        <i class="fas fa-check-circle"></i>
                        <span>Terhubung ke database: <strong><?= $dbName ?></strong></span>
                    </p>
                </div>
                <?php else: ?>
                <div class="p-4 bg-red-500/20 border border-red-500/50 rounded-xl">
                    <p class="flex items-center gap-2 text-red-400">
                        <i class="fas fa-times-circle"></i>
                        <span>Koneksi gagal: <?= $dbError ?></span>
                    </p>
                    <p class="text-sm text-slate-400 mt-2">Periksa konfigurasi database di file .env</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <?php if ($dbConnected): ?>
            <div class="bg-slate-800 rounded-2xl p-6 border border-slate-700">
                <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                    <i class="fas fa-cogs text-purple-400"></i>
                    Setup Actions
                </h2>
                
                <div class="space-y-4">
                    <?php
                    $action = $_POST['action'] ?? '';
                    $result = '';
                    $success = false;
                    
                    if ($action === 'migrate') {
                        try {
                            Artisan::call('migrate', ['--force' => true]);
                            $result = Artisan::output();
                            $success = true;
                        } catch (Exception $e) {
                            $result = $e->getMessage();
                        }
                    } elseif ($action === 'seed') {
                        try {
                            Artisan::call('db:seed', ['--force' => true]);
                            $result = Artisan::output();
                            $success = true;
                        } catch (Exception $e) {
                            $result = $e->getMessage();
                        }
                    } elseif ($action === 'fresh') {
                        try {
                            Artisan::call('migrate:fresh', ['--force' => true, '--seed' => true]);
                            $result = Artisan::output();
                            $success = true;
                        } catch (Exception $e) {
                            $result = $e->getMessage();
                        }
                    } elseif ($action === 'cache') {
                        try {
                            Artisan::call('config:cache');
                            Artisan::call('route:cache');
                            Artisan::call('view:cache');
                            $result = "Config, Route, dan View berhasil di-cache!";
                            $success = true;
                        } catch (Exception $e) {
                            $result = $e->getMessage();
                        }
                    } elseif ($action === 'clear') {
                        try {
                            Artisan::call('cache:clear');
                            Artisan::call('config:clear');
                            Artisan::call('route:clear');
                            Artisan::call('view:clear');
                            $result = "Semua cache berhasil dihapus!";
                            $success = true;
                        } catch (Exception $e) {
                            $result = $e->getMessage();
                        }
                    } elseif ($action === 'link') {
                        try {
                            Artisan::call('storage:link');
                            $result = Artisan::output();
                            $success = true;
                        } catch (Exception $e) {
                            $result = $e->getMessage();
                        }
                    } elseif ($action === 'complete') {
                        file_put_contents(__DIR__ . '/../storage/setup_completed.lock', date('Y-m-d H:i:s'));
                        $result = "Setup selesai! Silakan hapus file setup.php untuk keamanan.";
                        $success = true;
                    }
                    
                    if ($result): ?>
                    <div class="p-4 <?= $success ? 'bg-emerald-500/20 border-emerald-500/50' : 'bg-red-500/20 border-red-500/50' ?> border rounded-xl mb-4">
                        <pre class="text-sm whitespace-pre-wrap <?= $success ? 'text-emerald-300' : 'text-red-300' ?>"><?= htmlspecialchars($result) ?></pre>
                    </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <form method="POST" class="contents">
                            <button type="submit" name="action" value="migrate" 
                                    class="p-4 bg-blue-500/20 hover:bg-blue-500/30 border border-blue-500/50 rounded-xl text-left transition">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-database text-blue-400 text-xl"></i>
                                    <div>
                                        <p class="font-bold">Run Migrations</p>
                                        <p class="text-xs text-slate-400">Buat tabel database</p>
                                    </div>
                                </div>
                            </button>
                            
                            <button type="submit" name="action" value="seed" 
                                    class="p-4 bg-green-500/20 hover:bg-green-500/30 border border-green-500/50 rounded-xl text-left transition">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-seedling text-green-400 text-xl"></i>
                                    <div>
                                        <p class="font-bold">Run Seeders</p>
                                        <p class="text-xs text-slate-400">Isi data awal</p>
                                    </div>
                                </div>
                            </button>
                            
                            <button type="submit" name="action" value="fresh" 
                                    class="p-4 bg-amber-500/20 hover:bg-amber-500/30 border border-amber-500/50 rounded-xl text-left transition">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-sync text-amber-400 text-xl"></i>
                                    <div>
                                        <p class="font-bold">Fresh Migration + Seed</p>
                                        <p class="text-xs text-slate-400">Reset & isi ulang database</p>
                                    </div>
                                </div>
                            </button>
                            
                            <button type="submit" name="action" value="link" 
                                    class="p-4 bg-purple-500/20 hover:bg-purple-500/30 border border-purple-500/50 rounded-xl text-left transition">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-link text-purple-400 text-xl"></i>
                                    <div>
                                        <p class="font-bold">Storage Link</p>
                                        <p class="text-xs text-slate-400">Link folder storage</p>
                                    </div>
                                </div>
                            </button>
                            
                            <button type="submit" name="action" value="cache" 
                                    class="p-4 bg-cyan-500/20 hover:bg-cyan-500/30 border border-cyan-500/50 rounded-xl text-left transition">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-bolt text-cyan-400 text-xl"></i>
                                    <div>
                                        <p class="font-bold">Optimize Cache</p>
                                        <p class="text-xs text-slate-400">Cache config, route, view</p>
                                    </div>
                                </div>
                            </button>
                            
                            <button type="submit" name="action" value="clear" 
                                    class="p-4 bg-red-500/20 hover:bg-red-500/30 border border-red-500/50 rounded-xl text-left transition">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-trash text-red-400 text-xl"></i>
                                    <div>
                                        <p class="font-bold">Clear All Cache</p>
                                        <p class="text-xs text-slate-400">Hapus semua cache</p>
                                    </div>
                                </div>
                            </button>
                        </form>
                    </div>

                    <hr class="border-slate-700 my-6">

                    <form method="POST">
                        <button type="submit" name="action" value="complete" 
                                class="w-full p-4 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 rounded-xl font-bold transition">
                            <i class="fas fa-check-double mr-2"></i>
                            Selesaikan Setup & Kunci
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Login Info -->
            <div class="bg-slate-800 rounded-2xl p-6 border border-slate-700">
                <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                    <i class="fas fa-user-shield text-emerald-400"></i>
                    Default Login
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 bg-slate-700/50 rounded-xl">
                        <p class="text-sm text-slate-400">Admin Email</p>
                        <p class="font-mono font-bold">admin@admin.com</p>
                    </div>
                    <div class="p-4 bg-slate-700/50 rounded-xl">
                        <p class="text-sm text-slate-400">Password</p>
                        <p class="font-mono font-bold">password</p>
                    </div>
                </div>
                <p class="text-xs text-amber-400 mt-4">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Segera ganti password setelah login!
                </p>
            </div>

            <!-- Warning -->
            <div class="bg-red-500/20 border border-red-500/50 rounded-2xl p-6">
                <h2 class="text-xl font-bold mb-2 flex items-center gap-2 text-red-400">
                    <i class="fas fa-exclamation-triangle"></i>
                    PENTING!
                </h2>
                <p class="text-red-300">
                    Setelah setup selesai, <strong>HAPUS FILE setup.php</strong> ini dari server untuk keamanan!
                </p>
            </div>
        </div>

        <?php endif; ?>

        <footer class="text-center mt-8 text-slate-500 text-sm">
            <p>&copy; <?= date('Y') ?> SIMBAR - Puskesmas Karang Rejo</p>
        </footer>
    </div>
</body>
</html>
