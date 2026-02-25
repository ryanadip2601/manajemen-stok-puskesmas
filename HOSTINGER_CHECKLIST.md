# CHECKLIST UPLOAD KE HOSTINGER

## A. PERSIAPAN SEBELUM UPLOAD

### 1. Siapkan File .env Production
```env
APP_NAME=SIMBAR
APP_ENV=production
APP_KEY=base64:GENERATE_NEW_KEY
APP_DEBUG=false
APP_URL=https://namadomain.com

LOG_CHANNEL=single
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nama_database_hostinger
DB_USERNAME=username_database
DB_PASSWORD=password_database

SESSION_DRIVER=file
SESSION_LIFETIME=120

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
```

### 2. File/Folder yang WAJIB Diupload
- [ ] `app/` (semua isi)
- [ ] `bootstrap/` (semua isi)
- [ ] `config/` (semua isi)
- [ ] `database/migrations/`
- [ ] `database/seeders/`
- [ ] `public/` (semua isi - PENTING!)
- [ ] `resources/` (semua isi)
- [ ] `routes/` (semua isi)
- [ ] `storage/app/`
- [ ] `storage/framework/cache/` (folder kosong)
- [ ] `storage/framework/sessions/` (folder kosong)
- [ ] `storage/framework/views/` (folder kosong)
- [ ] `storage/logs/` (folder kosong)
- [ ] `artisan`
- [ ] `composer.json`
- [ ] `composer.lock`
- [ ] `.htaccess` (root)
- [ ] `public/.htaccess`

### 3. File/Folder yang JANGAN Diupload
- [ ] `vendor/` - JANGAN (install via composer di server)
- [ ] `node_modules/` - JANGAN
- [ ] `.git/` - JANGAN
- [ ] `database/database.sqlite` - JANGAN (pakai MySQL)
- [ ] `storage/logs/*.log` - JANGAN
- [ ] `.env` lokal - JANGAN (buat baru di server)
- [ ] File `*.md` dokumentasi - OPSIONAL

---

## B. LANGKAH UPLOAD

### Step 1: Login Hostinger
- [ ] Login ke hPanel Hostinger
- [ ] Buka File Manager atau gunakan FTP/SFTP

### Step 2: Upload File
- [ ] Upload semua file ke `public_html/` atau subfolder
- [ ] Pastikan struktur folder benar

### Step 3: Buat File .env di Server
- [ ] Buat file `.env` baru di root project
- [ ] Isi dengan kredensial production (lihat bagian A.1)
- [ ] Simpan file

### Step 4: Set Permission Folder
```
storage/          -> 775
storage/logs/     -> 775
storage/framework/-> 775
bootstrap/cache/  -> 775
```

---

## C. KONFIGURASI SSH (Jika Tersedia)

### Step 1: Akses SSH
```bash
ssh username@namadomain.com -p 65002
cd public_html
```

### Step 2: Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
```

### Step 3: Generate App Key
```bash
php artisan key:generate
```

### Step 4: Jalankan Migration
```bash
php artisan migrate --force
```

### Step 5: Buat Storage Link
```bash
php artisan storage:link
```

### Step 6: Cache Config (Production)
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 7: Seed Data (Opsional)
```bash
php artisan db:seed --force
```

---

## D. TANPA AKSES SSH (Alternatif)

Jika tidak ada SSH, buat file `public/setup.php`:

```php
<?php
// HAPUS FILE INI SETELAH SELESAI!
$key = $_GET['key'] ?? '';
if ($key !== 'rahasia123') {
    die('Unauthorized');
}

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "<pre>";

// Migration
$kernel->call('migrate', ['--force' => true]);
echo "Migration: OK\n";

// Storage Link
try {
    $kernel->call('storage:link');
    echo "Storage Link: OK\n";
} catch (Exception $e) {
    echo "Storage Link: Already exists\n";
}

// Cache
$kernel->call('config:cache');
echo "Config Cache: OK\n";

$kernel->call('route:cache');
echo "Route Cache: OK\n";

$kernel->call('view:cache');
echo "View Cache: OK\n";

echo "\n✅ SETUP SELESAI!\n";
echo "⚠️ HAPUS FILE INI SEGERA!\n";
echo "</pre>";
```

Akses: `https://namadomain.com/setup.php?key=rahasia123`

---

## E. KONFIGURASI DOMAIN

### Jika Upload ke Subfolder:
Edit `.htaccess` di `public_html/`:
```apache
RewriteEngine On
RewriteRule ^(.*)$ subfolder/public/$1 [L]
```

### Jika Upload Langsung ke public_html:
Pindahkan isi folder `public/` ke `public_html/`
Edit `public_html/index.php`:
```php
// Ubah path
require __DIR__.'/../vendor/autoload.php';
// Menjadi
require __DIR__.'/vendor/autoload.php';

// Dan
$app = require_once __DIR__.'/../bootstrap/app.php';
// Menjadi
$app = require_once __DIR__.'/bootstrap/app.php';
```

---

## F. VERIFIKASI SETELAH UPLOAD

### Checklist Testing:
- [ ] Halaman login tampil dengan benar
- [ ] Bisa login dengan akun admin
- [ ] Dashboard tampil dengan benar
- [ ] Menu navigasi berfungsi
- [ ] Form barang masuk/keluar berfungsi
- [ ] Upload gambar berfungsi
- [ ] Tidak ada error 500

### Jika Ada Error:
1. Cek `storage/logs/laravel.log`
2. Pastikan permission folder storage sudah benar
3. Pastikan `.env` sudah terisi dengan benar
4. Pastikan database sudah dibuat dan kredensial benar

---

## G. KEAMANAN SETELAH UPLOAD

- [ ] Hapus `public/setup.php` jika ada
- [ ] Hapus `public/migrate.php` jika ada
- [ ] Hapus `public/fix_barang_aset.php` jika ada
- [ ] Pastikan `APP_DEBUG=false` di `.env`
- [ ] Pastikan `APP_ENV=production` di `.env`
- [ ] Ganti password default admin

---

## H. TROUBLESHOOTING UMUM

### Error 500:
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
php artisan config:clear
php artisan cache:clear
```

### Halaman Blank:
- Cek error di `storage/logs/laravel.log`
- Pastikan PHP version >= 8.1

### CSS/JS Tidak Muncul:
- Pastikan `APP_URL` di `.env` sudah benar
- Jalankan `php artisan storage:link`

### Database Error:
- Pastikan kredensial database benar
- Pastikan database sudah dibuat di Hostinger
- Jalankan migration ulang

---

**Tanggal Dibuat:** {{ date('Y-m-d') }}
**Project:** SIMBAR - Sistem Manajemen Barang
