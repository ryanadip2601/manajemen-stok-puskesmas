<?php

/**
 * Script untuk migrasi gambar dari storage ke public/uploads
 * DAN update path di database
 * 
 * Jalankan di browser: https://yourdomain.com/migrate-images.php
 * HAPUS FILE INI SETELAH MIGRASI SELESAI!
 */

// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

echo "<pre style='font-family: monospace; background: #1e293b; color: #e2e8f0; padding: 20px; border-radius: 10px;'>";
echo "<h2 style='color: #34d399;'>🔄 MIGRASI GAMBAR KE PUBLIC/UPLOADS</h2>\n\n";

// Path
$storagePath = storage_path('app/public');
$publicUploadsPath = public_path('uploads');

// Pastikan folder uploads ada
$folders = ['usulan-penerimaan', 'pemeliharaan', 'aset'];
foreach ($folders as $folder) {
    $path = $publicUploadsPath . '/' . $folder;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        echo "📁 Folder dibuat: uploads/$folder\n";
    }
}

echo "\n<h3 style='color: #60a5fa;'>1️⃣ Migrasi file gambar...</h3>\n";

$totalCopied = 0;

// Copy files dari storage ke public/uploads
foreach ($folders as $folder) {
    $sourcePath = $storagePath . '/' . $folder;
    $destPath = $publicUploadsPath . '/' . $folder;
    
    if (!is_dir($sourcePath)) {
        echo "⏭️ Skip folder '$folder' (tidak ada di storage)\n";
        continue;
    }
    
    $files = File::files($sourcePath);
    $count = 0;
    
    foreach ($files as $file) {
        $filename = $file->getFilename();
        $destFile = $destPath . '/' . $filename;
        
        if (!file_exists($destFile)) {
            File::copy($file->getPathname(), $destFile);
            $count++;
        }
    }
    
    echo "✅ Folder '$folder': $count file dicopy\n";
    $totalCopied += $count;
}

echo "\n<h3 style='color: #60a5fa;'>2️⃣ Update path di database...</h3>\n";

$totalUpdated = 0;

// Update UsulanPenerimaanBarang
$usulans = DB::table('usulan_penerimaan_barang')
    ->whereNotNull('foto_barang')
    ->get();

foreach ($usulans as $usulan) {
    $fotoBarang = json_decode($usulan->foto_barang, true);
    if (!is_array($fotoBarang)) continue;
    
    $updated = false;
    foreach ($fotoBarang as $key => $foto) {
        // Jika belum dimulai dengan 'uploads/'
        if (!str_starts_with($foto, 'uploads/')) {
            // Contoh: 'usulan-penerimaan/xxx.jpg' -> 'uploads/usulan-penerimaan/xxx.jpg'
            $fotoBarang[$key] = 'uploads/' . $foto;
            $updated = true;
        }
    }
    
    if ($updated) {
        DB::table('usulan_penerimaan_barang')
            ->where('id', $usulan->id)
            ->update(['foto_barang' => json_encode($fotoBarang)]);
        $totalUpdated++;
    }
}
echo "✅ UsulanPenerimaanBarang: $totalUpdated record diupdate\n";

// Update BarangAset
$asets = DB::table('barang_aset')
    ->whereNotNull('foto')
    ->where('foto', 'NOT LIKE', 'uploads/%')
    ->get();

$asetCount = 0;
foreach ($asets as $aset) {
    DB::table('barang_aset')
        ->where('id', $aset->id)
        ->update(['foto' => 'uploads/' . $aset->foto]);
    $asetCount++;
}
echo "✅ BarangAset: $asetCount record diupdate\n";

// Update PemeliharaanAset
$pemeliharaans = DB::table('pemeliharaan_aset')
    ->whereNotNull('lampiran_foto')
    ->where('lampiran_foto', 'NOT LIKE', 'uploads/%')
    ->get();

$pemeliharaanCount = 0;
foreach ($pemeliharaans as $p) {
    DB::table('pemeliharaan_aset')
        ->where('id', $p->id)
        ->update(['lampiran_foto' => 'uploads/' . $p->lampiran_foto]);
    $pemeliharaanCount++;
}
echo "✅ PemeliharaanAset: $pemeliharaanCount record diupdate\n";

echo "\n<h3 style='color: #34d399;'>✅ MIGRASI SELESAI!</h3>\n";
echo "📄 Total file dicopy: $totalCopied\n";
echo "📝 Total database diupdate: " . ($totalUpdated + $asetCount + $pemeliharaanCount) . "\n\n";

echo "<span style='color: #f87171; font-weight: bold;'>⚠️ PENTING: HAPUS FILE INI (migrate-images.php) SETELAH SELESAI!</span>\n";

echo "</pre>";
