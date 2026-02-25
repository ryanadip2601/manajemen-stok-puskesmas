# SIMBAR - Deployment Checklist Hostinger

## Quick Start (5 Menit)

### Step 1: Persiapan Lokal
```bash
# Di folder project
composer install --optimize-autoloader --no-dev
php artisan key:generate
```

### Step 2: ZIP Project
- Klik kanan folder project → **Send to** → **Compressed (zip)**
- Atau gunakan 7-Zip/WinRAR

### Step 3: Upload ke Hostinger
1. Login https://hpanel.hostinger.com
2. **Files** → **File Manager**
3. Masuk folder `public_html`
4. Hapus semua file default
5. Upload file ZIP
6. Klik kanan ZIP → **Extract**

### Step 4: Buat Database
1. **Databases** → **MySQL Databases**
2. Buat database baru, catat:
   - Database: `u123456789_simbar`
   - User: `u123456789_user`
   - Password: `xxxxxxxx`

### Step 5: Edit .env
1. Di File Manager, edit `.env`
2. Update:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domainanda.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=u123456789_simbar
DB_USERNAME=u123456789_user
DB_PASSWORD=password_anda
```

### Step 6: Setup Database
1. Buka browser: `https://domainanda.com/setup.php`
2. Masukkan key: `simbar2024`
3. Klik **Run Migrations**
4. Klik **Run Seeders**
5. Klik **Optimize Cache**
6. **HAPUS file setup.php** setelah selesai!

### Step 7: Login
- URL: `https://domainanda.com`
- Email: `admin@admin.com`
- Password: `password`

---

## Checklist Detail

### Persiapan Lokal
- [ ] Install dependencies: `composer install --no-dev`
- [ ] Generate key: `php artisan key:generate`
- [ ] Clear cache: `php artisan cache:clear`
- [ ] ZIP seluruh folder project

### Upload Hostinger
- [ ] Login ke hPanel
- [ ] Buat database MySQL baru
- [ ] Catat kredensial database
- [ ] Upload ZIP ke `public_html`
- [ ] Extract file ZIP
- [ ] Hapus file ZIP (hemat storage)

### Konfigurasi
- [ ] Edit file `.env` dengan kredensial database
- [ ] Set `APP_DEBUG=false`
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_URL` dengan domain Anda

### Permission Folder
- [ ] `storage/` → 775
- [ ] `storage/logs/` → 775
- [ ] `storage/framework/` → 775
- [ ] `bootstrap/cache/` → 775

### Setup Database
- [ ] Akses `/setup.php`
- [ ] Run Migrations
- [ ] Run Seeders
- [ ] Optimize Cache
- [ ] Storage Link

### SSL & Security
- [ ] Install SSL (Let's Encrypt)
- [ ] Aktifkan Force HTTPS
- [ ] Hapus file `setup.php`
- [ ] Ganti password default

### Testing
- [ ] Test login semua role
- [ ] Test CRUD barang
- [ ] Test stok masuk/keluar
- [ ] Test laporan
- [ ] Test responsive mobile

---

## File yang Dibuat untuk Deployment

| File | Fungsi |
|------|--------|
| `.htaccess` (root) | Redirect ke folder public |
| `public/.htaccess` | URL rewriting Laravel |
| `public/setup.php` | Tool setup tanpa SSH |
| `backup_database.php` | Script backup database |
| `.env.example` | Template konfigurasi |
| `HOSTINGER_DEPLOY_GUIDE.md` | Panduan lengkap |
| `DEPLOY_CHECKLIST.md` | Checklist ini |

---

## Default Login Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@admin.com | password |
| Admin Gudang | gudang@admin.com | password |
| Staff | staff@admin.com | password |

**⚠️ GANTI PASSWORD SETELAH LOGIN!**

---

## Troubleshooting

### Error 500
```
1. Set APP_DEBUG=true sementara
2. Refresh, lihat error
3. Perbaiki error
4. Set APP_DEBUG=false
```

### Error Database
```
1. Cek kredensial di .env
2. Pastikan database sudah dibuat
3. Cek user punya akses ke database
```

### Error Permission
```
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Halaman Blank
```
1. Cek storage/logs/laravel.log
2. Clear cache via setup.php
3. Regenerate cache
```

---

## Backup & Restore

### Backup Database
```bash
# Via SSH
mysqldump -u user -p database > backup.sql

# Via PHP
php backup_database.php
```

### Restore Database
```bash
# Via phpMyAdmin
1. Login phpMyAdmin
2. Pilih database
3. Tab Import
4. Upload file .sql
5. Klik Go
```

---

## Support

- Dokumentasi Laravel: https://laravel.com/docs
- Hostinger Support: Live Chat di hPanel
- File Log: `storage/logs/laravel.log`

---

**Good luck! 🚀**
