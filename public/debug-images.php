<?php

/**
 * Debug script untuk cek path gambar
 * Jalankan: https://yourdomain.com/debug-images.php
 * HAPUS FILE INI SETELAH DEBUG SELESAI!
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;

echo "<pre style='font-family: monospace; background: #1e293b; color: #e2e8f0; padding: 20px; border-radius: 10px;'>";
echo "<h2 style='color: #34d399;'>🔍 DEBUG GAMBAR USULAN PENERIMAAN</h2>\n\n";

// Get latest 5 usulans
$usulans = DB::table('usulan_penerimaan_barang')
    ->whereNotNull('foto_barang')
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();

foreach ($usulans as $usulan) {
    echo "-----------------------------------\n";
    echo "<span style='color: #60a5fa;'>ID: {$usulan->id} | Kode: {$usulan->kode_usulan}</span>\n";
    
    $fotoBarang = json_decode($usulan->foto_barang, true);
    
    if (!is_array($fotoBarang)) {
        echo "⚠️ foto_barang bukan array: " . $usulan->foto_barang . "\n\n";
        continue;
    }
    
    echo "foto_barang dari database:\n";
    foreach ($fotoBarang as $index => $foto) {
        echo "  [{$index}] " . $foto . "\n";
        
        // Check if starts with uploads/
        if (str_starts_with($foto, 'uploads/')) {
            $fullPath = public_path($foto);
            $url = asset($foto);
            echo "       → Path type: <span style='color: #34d399;'>uploads/</span>\n";
        } else {
            $fullPath = storage_path('app/public/' . $foto);
            $url = asset('storage/' . $foto);
            echo "       → Path type: <span style='color: #fbbf24;'>storage/</span>\n";
        }
        
        echo "       → Full path: " . $fullPath . "\n";
        echo "       → File exists: " . (file_exists($fullPath) ? "<span style='color: #34d399;'>YES ✅</span>" : "<span style='color: #f87171;'>NO ❌</span>") . "\n";
        echo "       → Generated URL: " . $url . "\n";
    }
    echo "\n";
}

echo "-----------------------------------\n";
echo "\n<h3 style='color: #60a5fa;'>📁 CEK FOLDER UPLOADS</h3>\n";

$uploadsPath = public_path('uploads/usulan-penerimaan');
echo "Path: " . $uploadsPath . "\n";
echo "Folder exists: " . (is_dir($uploadsPath) ? "<span style='color: #34d399;'>YES ✅</span>" : "<span style='color: #f87171;'>NO ❌</span>") . "\n";

if (is_dir($uploadsPath)) {
    $files = scandir($uploadsPath);
    $imageFiles = array_filter($files, fn($f) => $f !== '.' && $f !== '..' && $f !== '.gitkeep');
    echo "Total files: " . count($imageFiles) . "\n";
    if (count($imageFiles) > 0) {
        echo "Files:\n";
        foreach (array_slice($imageFiles, 0, 10) as $file) {
            echo "  - " . $file . "\n";
        }
        if (count($imageFiles) > 10) {
            echo "  ... dan " . (count($imageFiles) - 10) . " file lainnya\n";
        }
    }
}

echo "\n<h3 style='color: #60a5fa;'>📁 CEK FOLDER STORAGE</h3>\n";

$storagePath = storage_path('app/public/usulan-penerimaan');
echo "Path: " . $storagePath . "\n";
echo "Folder exists: " . (is_dir($storagePath) ? "<span style='color: #34d399;'>YES ✅</span>" : "<span style='color: #f87171;'>NO ❌</span>") . "\n";

if (is_dir($storagePath)) {
    $files = scandir($storagePath);
    $imageFiles = array_filter($files, fn($f) => $f !== '.' && $f !== '..' && $f !== '.gitkeep');
    echo "Total files: " . count($imageFiles) . "\n";
}

echo "\n<span style='color: #f87171; font-weight: bold;'>⚠️ HAPUS FILE INI (debug-images.php) SETELAH DEBUG SELESAI!</span>\n";
echo "</pre>";
