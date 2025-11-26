# Panduan Instalasi - Sistem Manajemen Stok Barang

## 📋 Persyaratan Sistem

- PHP >= 8.1
- Composer
- MySQL >= 5.7 atau MariaDB
- Node.js & NPM (untuk TailwindCSS)
- Git

---

## 🚀 Langkah-Langkah Instalasi

### 1. Clone atau Download Project

Jika menggunakan Git:
```bash
git clone <repository-url>
cd website-barang-1.1
```

Atau extract file ZIP ke folder yang diinginkan.

---

### 2. Install Dependencies Laravel

```bash
composer install
```

Jika `composer` belum terinstall, download dari: https://getcomposer.org/

---

### 3. Konfigurasi Environment

Copy file `.env.example` menjadi `.env`:

**Windows:**
```cmd
copy .env.example .env
```

**Linux/Mac:**
```bash
cp .env.example .env
```

Edit file `.env` dan sesuaikan konfigurasi database:

```env
APP_NAME="Manajemen Stok Puskesmas"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=puskesmas_stok
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost:8000
```

---

### 4. Generate Application Key

```bash
php artisan key:generate
```

---

### 5. Buat Database

Buat database baru di MySQL:

```sql
CREATE DATABASE puskesmas_stok;
```

Atau gunakan phpMyAdmin / MySQL Workbench.

---

### 6. Jalankan Migrasi Database

```bash
php artisan migrate
```

Perintah ini akan membuat semua tabel yang diperlukan:
- users
- categories
- units
- items
- stock_in
- stock_out
- logs

---

### 7. Seed Data Awal (Optional)

Buat file seeder untuk data awal atau input manual via aplikasi.

Contoh membuat user admin via Tinker:

```bash
php artisan tinker
```

```php
\App\Models\User::create([
    'name' => 'Admin Puskesmas',
    'email' => 'admin@puskesmas.com',
    'password' => bcrypt('password123'),
    'role' => 'admin'
]);

\App\Models\Unit::create(['name' => 'Box', 'symbol' => 'Box']);
\App\Models\Unit::create(['name' => 'Pcs', 'symbol' => 'Pcs']);
\App\Models\Unit::create(['name' => 'Strip', 'symbol' => 'Strip']);
\App\Models\Unit::create(['name' => 'Botol', 'symbol' => 'Btl']);
\App\Models\Unit::create(['name' => 'Tube', 'symbol' => 'Tube']);

\App\Models\Category::create([
    'name' => 'Obat-obatan',
    'description' => 'Kategori untuk obat-obatan umum'
]);

\App\Models\Category::create([
    'name' => 'Alat Kesehatan',
    'description' => 'Kategori untuk alat kesehatan'
]);

exit
```

---

### 8. Install TailwindCSS (Optional - Sudah menggunakan CDN)

Project ini sudah menggunakan TailwindCSS via CDN, jadi tidak perlu instalasi NPM.

Jika ingin compile sendiri:

```bash
npm install
npm run dev
```

Atau untuk production:

```bash
npm run build
```

---

### 9. Setup Laravel Sanctum untuk API

Publish konfigurasi Sanctum:

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

Jalankan migrasi Sanctum:

```bash
php artisan migrate
```

---

### 10. Jalankan Development Server

```bash
php artisan serve
```

Aplikasi akan berjalan di: **http://localhost:8000**

---

## 🔐 Login Default

Setelah seeding data:

- **Email:** admin@puskesmas.com
- **Password:** password123

---

## 📁 Struktur Folder Project

```
website-barang-1.1/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── AuthController.php
│   │       ├── CategoryController.php
│   │       ├── ItemController.php
│   │       ├── StockInController.php
│   │       ├── StockOutController.php
│   │       └── DashboardController.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Category.php
│   │   ├── Unit.php
│   │   ├── Item.php
│   │   ├── StockIn.php
│   │   ├── StockOut.php
│   │   └── Log.php
│   └── Services/
│       └── StockService.php
├── database/
│   └── migrations/
│       ├── 2024_01_01_000000_create_users_table.php
│       ├── 2024_01_01_000001_create_categories_table.php
│       ├── 2024_01_01_000002_create_units_table.php
│       ├── 2024_01_01_000003_create_items_table.php
│       ├── 2024_01_01_000004_create_stock_in_table.php
│       ├── 2024_01_01_000005_create_stock_out_table.php
│       └── 2024_01_01_000006_create_logs_table.php
├── resources/
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php
│       ├── auth/
│       │   └── login.blade.php
│       ├── dashboard/
│       │   └── index.blade.php
│       ├── categories/
│       │   ├── index.blade.php
│       │   ├── create.blade.php
│       │   └── edit.blade.php
│       ├── items/
│       │   ├── index.blade.php
│       │   ├── create.blade.php
│       │   └── edit.blade.php
│       ├── stock_in/
│       │   ├── index.blade.php
│       │   ├── create.blade.php
│       │   └── edit.blade.php
│       └── stock_out/
│           ├── index.blade.php
│           ├── create.blade.php
│           └── edit.blade.php
├── routes/
│   ├── web.php
│   └── api.php
├── .env.example
├── composer.json
├── DATABASE_DESIGN.md
├── API_DOCUMENTATION.md
└── INSTALLATION.md
```

---

## 🌐 Mengakses Aplikasi

### Web Interface
```
http://localhost:8000
```

### API Endpoint
```
http://localhost:8000/api
```

---

## 🔧 Troubleshooting

### Error: "No application encryption key"
```bash
php artisan key:generate
```

### Error: "SQLSTATE[HY000] [1045] Access denied"
Pastikan konfigurasi database di `.env` sudah benar.

### Error: "Class not found"
```bash
composer dump-autoload
```

### Error: Storage permission
**Linux/Mac:**
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

**Windows:** Tidak perlu setting permission khusus.

### Port 8000 sudah digunakan
```bash
php artisan serve --port=8080
```

---

## 📝 Catatan Penting

1. **Development Mode:** 
   - Set `APP_DEBUG=true` di `.env` untuk development
   - Set `APP_DEBUG=false` untuk production

2. **Database Backup:**
   - Backup database secara berkala
   - Gunakan: `mysqldump -u root -p puskesmas_stok > backup.sql`

3. **Security:**
   - Ganti password default setelah instalasi
   - Jangan gunakan credential default di production
   - Set `APP_ENV=production` untuk production

4. **Performance:**
   - Gunakan `php artisan config:cache` untuk production
   - Gunakan `php artisan route:cache` untuk production
   - Gunakan `php artisan view:cache` untuk production

---

## 🆘 Support

Jika ada masalah atau pertanyaan:
1. Cek dokumentasi Laravel: https://laravel.com/docs
2. Cek file `API_DOCUMENTATION.md` untuk API reference
3. Cek file `DATABASE_DESIGN.md` untuk struktur database

---

## ✅ Checklist Instalasi

- [ ] PHP >= 8.1 terinstall
- [ ] Composer terinstall
- [ ] MySQL/MariaDB terinstall
- [ ] Clone/download project
- [ ] `composer install` berhasil
- [ ] File `.env` sudah dikonfigurasi
- [ ] `php artisan key:generate` berhasil
- [ ] Database sudah dibuat
- [ ] `php artisan migrate` berhasil
- [ ] Data user admin sudah dibuat
- [ ] `php artisan serve` berjalan
- [ ] Bisa login ke aplikasi
- [ ] API bisa diakses

---

## 🎉 Selesai!

Aplikasi siap digunakan. Akses melalui browser:
**http://localhost:8000**
