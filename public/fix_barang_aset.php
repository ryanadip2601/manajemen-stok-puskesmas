<?php
/**
 * FIX BARANG ASET TABLE - HAPUS FILE INI SETELAH SELESAI!
 * Akses: https://domainanda.com/fix_barang_aset.php?key=rahasia123
 */

$secretKey = 'rahasia123';

if (!isset($_GET['key']) || $_GET['key'] !== $secretKey) {
    http_response_code(404);
    die('404 - Not Found');
}

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo '<!DOCTYPE html>
<html>
<head>
    <title>Fix Barang Aset Table</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a2e; color: #0f0; }
        h1 { color: #0ff; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .warning { color: #ff0; }
        .info { color: #0ff; }
        pre { background: #000; padding: 15px; border-radius: 5px; overflow-x: auto; }
        a { color: #0ff; }
    </style>
</head>
<body>
    <h1>🔧 Fix Barang Aset Table</h1>
    <pre>';

try {
    $connection = config('database.default');
    echo "<span class='info'>Database Connection: {$connection}</span>\n\n";
    
    // Kolom yang perlu ditambahkan
    $columnsToAdd = [
        'no_sertifikat' => "VARCHAR(255) NULL COMMENT 'No. Sertifikat/No. Pabrik/No. Chasis/No. Mesin'",
        'asal_perolehan' => "VARCHAR(255) NULL COMMENT 'Asal/Cara Perolehan Barang'",
        'ukuran_konstruksi' => "VARCHAR(255) NULL COMMENT 'Ukuran Barang/Konstruksi'",
    ];
    
    // Cek dan tambahkan kolom yang belum ada
    foreach ($columnsToAdd as $column => $definition) {
        if (!Schema::hasColumn('barang_aset', $column)) {
            echo "Adding column: {$column}... ";
            
            if ($connection === 'sqlite') {
                DB::statement("ALTER TABLE barang_aset ADD COLUMN {$column} VARCHAR(255) NULL");
            } else {
                // MySQL
                DB::statement("ALTER TABLE barang_aset ADD COLUMN {$column} {$definition}");
            }
            
            echo "<span class='success'>✅ Added!</span>\n";
        } else {
            echo "<span class='warning'>⚠️ Column {$column} already exists</span>\n";
        }
    }
    
    // Tampilkan semua kolom saat ini
    echo "\n<span class='info'>Current columns in barang_aset:</span>\n";
    $columns = Schema::getColumnListing('barang_aset');
    foreach ($columns as $col) {
        echo "  - {$col}\n";
    }
    
    // Clear cache
    echo "\n<span class='info'>Clearing caches...</span>\n";
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    echo "<span class='success'>✅ Caches cleared!</span>\n";
    
    echo '</pre>
    <h2 class="success">✅ FIX BERHASIL!</h2>
    <p class="error"><strong>⚠️ PENTING: HAPUS FILE fix_barang_aset.php SETELAH SELESAI!</strong></p>
    <p><a href="/">← Kembali ke Website</a></p>';
    
} catch (Exception $e) {
    echo '</pre>
    <h2 class="error">❌ ERROR:</h2>
    <pre class="error">' . $e->getMessage() . "\n\n" . $e->getTraceAsString() . '</pre>
    <p><a href="/">← Kembali ke Website</a></p>';
}

echo '</body></html>';
