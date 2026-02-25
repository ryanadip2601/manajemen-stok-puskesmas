<?php
/**
 * Script untuk merge database online dan lokal
 * Strategi: Gunakan database lokal sebagai base, tambahkan data unik dari online
 */

$onlineDb = 'C:/Users/Ryana/Downloads/database_online.sqlite';
$localDb = 'C:/Users/Ryana/Downloads/Apk Vibe Coding/website barang 1.1/database/database.sqlite';
$mergedDb = 'C:/Users/Ryana/Downloads/Apk Vibe Coding/website barang 1.1/database/database_merged.sqlite';

echo "=== MERGE DATABASE ===\n\n";

// Copy local database as base for merged
if (file_exists($mergedDb)) {
    unlink($mergedDb);
}
copy($localDb, $mergedDb);
echo "✓ Database lokal di-copy sebagai base\n";

// Connect to all databases
try {
    $online = new PDO("sqlite:$onlineDb");
    $online->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $merged = new PDO("sqlite:$mergedDb");
    $merged->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Semua database terhubung\n\n";
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}

// Function to get all data from table
function getAllData($pdo, $table) {
    try {
        return $pdo->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// Function to get columns
function getColumns($pdo, $table) {
    try {
        $stmt = $pdo->query("PRAGMA table_info($table)");
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
    } catch (Exception $e) {
        return [];
    }
}

// ============================================
// 1. MERGE USERS (by email - unique)
// ============================================
echo "=== MERGING USERS ===\n";
$onlineUsers = getAllData($online, 'users');
$mergedEmails = array_column(getAllData($merged, 'users'), 'email');

$addedUsers = 0;
foreach ($onlineUsers as $user) {
    if (!in_array($user['email'], $mergedEmails)) {
        $columns = array_keys($user);
        $placeholders = array_fill(0, count($columns), '?');
        $sql = "INSERT INTO users (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
        try {
            $merged->prepare($sql)->execute(array_values($user));
            $addedUsers++;
            echo "  + User: {$user['email']}\n";
        } catch (Exception $e) {
            echo "  ! Skip user {$user['email']}: " . $e->getMessage() . "\n";
        }
    }
}
echo "Total user ditambahkan: $addedUsers\n\n";

// ============================================
// 2. MERGE ITEMS (by name or id)
// ============================================
echo "=== MERGING ITEMS ===\n";
$onlineItems = getAllData($online, 'items');
$mergedItemNames = array_column(getAllData($merged, 'items'), 'name');

$addedItems = 0;
foreach ($onlineItems as $item) {
    if (!in_array($item['name'], $mergedItemNames)) {
        // Reset ID untuk auto-increment
        unset($item['id']);
        $columns = array_keys($item);
        $placeholders = array_fill(0, count($columns), '?');
        $sql = "INSERT INTO items (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
        try {
            $merged->prepare($sql)->execute(array_values($item));
            $addedItems++;
        } catch (Exception $e) {
            // Skip
        }
    }
}
echo "Total items ditambahkan: $addedItems\n\n";

// ============================================
// 3. MERGE RUANGAN (by nama_ruangan)
// ============================================
echo "=== MERGING RUANGAN ===\n";
$onlineRuangan = getAllData($online, 'ruangan');
$mergedRuanganNames = array_column(getAllData($merged, 'ruangan'), 'nama_ruangan');

$addedRuangan = 0;
foreach ($onlineRuangan as $ruangan) {
    if (!in_array($ruangan['nama_ruangan'], $mergedRuanganNames)) {
        unset($ruangan['id']);
        $columns = array_keys($ruangan);
        $placeholders = array_fill(0, count($columns), '?');
        $sql = "INSERT INTO ruangan (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
        try {
            $merged->prepare($sql)->execute(array_values($ruangan));
            $addedRuangan++;
            echo "  + Ruangan: {$ruangan['nama_ruangan']}\n";
        } catch (Exception $e) {
            echo "  ! Skip: " . $e->getMessage() . "\n";
        }
    }
}
echo "Total ruangan ditambahkan: $addedRuangan\n\n";

// ============================================
// 4. MERGE BARANG_ASET (by kode_barang - unique)
// ============================================
echo "=== MERGING BARANG_ASET ===\n";
$onlineBarang = getAllData($online, 'barang_aset');
$mergedBarangKodes = array_column(getAllData($merged, 'barang_aset'), 'kode_barang');

$addedBarang = 0;
$updatedBarang = 0;
foreach ($onlineBarang as $barang) {
    if (!in_array($barang['kode_barang'], $mergedBarangKodes)) {
        unset($barang['id']);
        $columns = array_keys($barang);
        $placeholders = array_fill(0, count($columns), '?');
        $sql = "INSERT INTO barang_aset (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
        try {
            $merged->prepare($sql)->execute(array_values($barang));
            $addedBarang++;
        } catch (Exception $e) {
            // Skip
        }
    }
}
echo "Total barang_aset ditambahkan dari online: $addedBarang\n\n";

// ============================================
// 5. MERGE KATEGORI_ASET
// ============================================
echo "=== MERGING KATEGORI_ASET ===\n";
$onlineKategori = getAllData($online, 'kategori_aset');
$mergedKategoriNames = array_column(getAllData($merged, 'kategori_aset'), 'nama_kategori');

$addedKategori = 0;
foreach ($onlineKategori as $kategori) {
    if (!in_array($kategori['nama_kategori'], $mergedKategoriNames)) {
        unset($kategori['id']);
        $columns = array_keys($kategori);
        $placeholders = array_fill(0, count($columns), '?');
        $sql = "INSERT INTO kategori_aset (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
        try {
            $merged->prepare($sql)->execute(array_values($kategori));
            $addedKategori++;
            echo "  + Kategori: {$kategori['nama_kategori']}\n";
        } catch (Exception $e) {
            // Skip
        }
    }
}
echo "Total kategori_aset ditambahkan: $addedKategori\n\n";

// ============================================
// 6. MERGE CATEGORIES
// ============================================
echo "=== MERGING CATEGORIES ===\n";
$onlineCategories = getAllData($online, 'categories');
$mergedCatNames = array_column(getAllData($merged, 'categories'), 'name');

$addedCat = 0;
foreach ($onlineCategories as $cat) {
    if (!in_array($cat['name'], $mergedCatNames)) {
        unset($cat['id']);
        $columns = array_keys($cat);
        $placeholders = array_fill(0, count($columns), '?');
        $sql = "INSERT INTO categories (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
        try {
            $merged->prepare($sql)->execute(array_values($cat));
            $addedCat++;
            echo "  + Category: {$cat['name']}\n";
        } catch (Exception $e) {
            // Skip
        }
    }
}
echo "Total categories ditambahkan: $addedCat\n\n";

// ============================================
// FINAL COUNT
// ============================================
echo "=== HASIL AKHIR DATABASE MERGED ===\n";
$tables = ['users', 'items', 'categories', 'barang_aset', 'kategori_aset', 'ruangan', 'pemeliharaan_aset'];
foreach ($tables as $table) {
    $count = $merged->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    echo sprintf("%-20s : %d records\n", $table, $count);
}

echo "\n✅ DATABASE MERGED BERHASIL DIBUAT!\n";
echo "📁 Lokasi: $mergedDb\n";
echo "\n⚠️ LANGKAH SELANJUTNYA:\n";
echo "1. Cek database_merged.sqlite\n";
echo "2. Jika OK, rename menjadi database.sqlite\n";
echo "3. Upload ke Hostinger\n";
