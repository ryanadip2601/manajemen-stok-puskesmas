<?php
/**
 * SETUP MIGRATION - HAPUS FILE INI SETELAH SELESAI!
 * Akses: https://domainanda.com/migrate.php?key=rahasia123
 */

// Ganti dengan kata sandi Anda sendiri
$secretKey = 'rahasia123';

if (!isset($_GET['key']) || $_GET['key'] !== $secretKey) {
    http_response_code(404);
    die('404 - Not Found');
}

// Load Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo '<!DOCTYPE html>
<html>
<head>
    <title>Setup Migration</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a2e; color: #0f0; }
        h1 { color: #0ff; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .warning { color: #ff0; }
        pre { background: #000; padding: 15px; border-radius: 5px; overflow-x: auto; }
        a { color: #0ff; }
    </style>
</head>
<body>
    <h1>🚀 Setup Migration SIMBAR</h1>
    <pre>';

try {
    // 1. Migrate
    echo "📦 Running migrations...\n";
    Artisan::call('migrate', ['--force' => true]);
    echo '<span class="success">✅ ' . trim(Artisan::output()) . '</span>' . "\n\n";
    
    // 2. Config clear
    echo "🔧 Clearing config cache...\n";
    Artisan::call('config:clear');
    echo '<span class="success">✅ Config cleared</span>' . "\n\n";
    
    // 3. Route clear  
    echo "🛣️ Clearing route cache...\n";
    Artisan::call('route:clear');
    echo '<span class="success">✅ Route cleared</span>' . "\n\n";
    
    // 4. View clear
    echo "👁️ Clearing view cache...\n";
    Artisan::call('view:clear');
    echo '<span class="success">✅ View cleared</span>' . "\n\n";
    
    // 5. Storage link
    echo "🔗 Creating storage link...\n";
    try {
        Artisan::call('storage:link');
        echo '<span class="success">✅ Storage linked</span>' . "\n\n";
    } catch (Exception $e) {
        echo '<span class="warning">⚠️ Storage link already exists</span>' . "\n\n";
    }
    
    echo '</pre>
    <h2 class="success">✅ SETUP BERHASIL!</h2>
    <p class="error"><strong>⚠️ PENTING: HAPUS FILE migrate.php SETELAH SELESAI!</strong></p>
    <p><a href="/">← Kembali ke Website</a></p>';
    
} catch (Exception $e) {
    echo '</pre>
    <h2 class="error">❌ ERROR:</h2>
    <pre class="error">' . $e->getMessage() . '</pre>
    <p><a href="/">← Kembali ke Website</a></p>';
}

echo '</body></html>';
