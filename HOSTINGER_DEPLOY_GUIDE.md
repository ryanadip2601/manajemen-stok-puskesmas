# Panduan Deploy Laravel ke Hostinger hPanel

## Langkah-Langkah Lengkap

---

## STEP 1: Persiapan Project Laravel

### 1.1 Buat File .htaccess di Root
Buat file `.htaccess` di folder root project (bukan di public):

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

### 1.2 Update File `public/.htaccess`
Pastikan file `public/.htaccess` berisi:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

### 1.3 Siapkan File untuk Upload
Jalankan perintah ini di terminal/cmd:

```bash
# 1. Install dependencies production
composer install --optimize-autoloader --no-dev

# 2. Generate APP_KEY (jika belum ada)
php artisan key:generate

# 3. Clear semua cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 4. Optimize untuk production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## STEP 2: Login ke Hostinger hPanel

1. Buka https://hpanel.hostinger.com/
2. Login dengan akun Hostinger Anda
3. Pilih hosting/domain yang akan digunakan
4. Anda akan masuk ke Dashboard hPanel

---

## STEP 3: Buat Database MySQL

### 3.1 Buat Database
1. Di hPanel, klik menu **Databases** > **MySQL Databases**
2. Isi form:
   - **Database name**: `simbar_db` (atau nama lain)
   - **Username**: `simbar_user` (atau nama lain)
   - **Password**: Buat password yang kuat
3. Klik **Create**
4. **CATAT** informasi berikut:
   - Database name: `u123456789_simbar_db`
   - Username: `u123456789_simbar_user`
   - Password: `password_anda`
   - Host: `localhost` (biasanya)

---

## STEP 4: Upload File Project

### 4.1 Via File Manager (Mudah)
1. Di hPanel, klik **Files** > **File Manager**
2. Masuk ke folder `public_html`
3. **HAPUS** semua file default (index.html, dll)
4. Klik **Upload** (pojok kanan atas)
5. Upload file ZIP project Anda
6. Setelah selesai, **Extract** file ZIP tersebut
7. Pastikan struktur folder seperti ini:
   ```
   public_html/
   ├── app/
   ├── bootstrap/
   ├── config/
   ├── database/
   ├── public/
   ├── resources/
   ├── routes/
   ├── storage/
   ├── vendor/
   ├── .env
   ├── .htaccess
   ├── artisan
   └── composer.json
   ```

### 4.2 Via FTP (Alternatif untuk file besar)
1. Di hPanel, klik **Files** > **FTP Accounts**
2. Catat kredensial FTP:
   - Host: `ftp.domainanda.com`
   - Username: (lihat di hPanel)
   - Password: (password hosting)
   - Port: 21
3. Gunakan FileZilla atau WinSCP untuk upload

---

## STEP 5: Konfigurasi File .env

### 5.1 Edit File .env
1. Di File Manager, buka folder `public_html`
2. Klik kanan file `.env` > **Edit**
3. Update konfigurasi berikut:

```env
APP_NAME=SIMBAR
APP_ENV=production
APP_KEY=base64:XXXXXXXXX (jangan diubah jika sudah ada)
APP_DEBUG=false
APP_URL=https://domainanda.com

LOG_CHANNEL=stack
LOG_LEVEL=error

# Database MySQL Hostinger
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_simbar_db
DB_USERNAME=u123456789_simbar_user
DB_PASSWORD=password_anda

# Session & Cache
SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync

# Mail (Opsional - untuk reset password)
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=email@domainanda.com
MAIL_PASSWORD=password_email
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=noreply@domainanda.com
MAIL_FROM_NAME="SIMBAR"
```

4. Klik **Save**

---

## STEP 6: Set Permission Folder

### 6.1 Via File Manager
1. Buka File Manager
2. Klik kanan folder `storage` > **Permissions**
3. Set permission ke `775` atau `777`
4. Centang "Apply to subdirectories"
5. Klik **Change Permissions**

6. Lakukan hal yang sama untuk folder:
   - `storage/` (775)
   - `storage/logs/` (775)
   - `storage/framework/` (775)
   - `storage/framework/cache/` (775)
   - `storage/framework/sessions/` (775)
   - `storage/framework/views/` (775)
   - `bootstrap/cache/` (775)

### 6.2 Via SSH (Jika tersedia)
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

---

## STEP 7: Jalankan Migrasi Database

### 7.1 Via SSH Terminal (Recommended)
1. Di hPanel, klik **Advanced** > **SSH Access**
2. Aktifkan SSH jika belum aktif
3. Gunakan terminal/PuTTY untuk connect:
   ```bash
   ssh u123456789@domainanda.com -p 65002
   ```
4. Masuk ke folder project:
   ```bash
   cd public_html
   ```
5. Jalankan migrasi:
   ```bash
   php artisan migrate --force
   ```
6. Jalankan seeder (jika ada):
   ```bash
   php artisan db:seed --force
   ```

### 7.2 Via Web Route (Alternatif jika tidak ada SSH)
1. Buat file `public_html/routes/web.php` dan tambahkan route sementara:
   ```php
   Route::get('/run-migration', function() {
       Artisan::call('migrate', ['--force' => true]);
       Artisan::call('db:seed', ['--force' => true]);
       return 'Migration completed!';
   });
   ```
2. Akses `https://domainanda.com/run-migration`
3. **HAPUS** route ini setelah selesai!

---

## STEP 8: Konfigurasi Domain & SSL

### 8.1 Pasang SSL (HTTPS)
1. Di hPanel, klik **Security** > **SSL**
2. Pilih **Install SSL**
3. Pilih domain Anda
4. Pilih **Let's Encrypt** (gratis)
5. Klik **Install**
6. Tunggu beberapa menit

### 8.2 Force HTTPS
1. Di hPanel, klik **Security** > **SSL**
2. Aktifkan **Force HTTPS**

Atau tambahkan di `.htaccess` root:
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## STEP 9: Konfigurasi PHP

### 9.1 Cek Versi PHP
1. Di hPanel, klik **Advanced** > **PHP Configuration**
2. Pastikan versi PHP **8.2** atau lebih tinggi
3. Jika tidak, ubah ke PHP 8.2

### 9.2 PHP Extensions
Pastikan extensions berikut aktif:
- ✅ BCMath
- ✅ Ctype
- ✅ Fileinfo
- ✅ JSON
- ✅ Mbstring
- ✅ OpenSSL
- ✅ PDO
- ✅ PDO_MySQL
- ✅ Tokenizer
- ✅ XML

---

## STEP 10: Testing

### 10.1 Akses Website
1. Buka browser
2. Akses `https://domainanda.com`
3. Anda akan melihat halaman login SIMBAR

### 10.2 Login Default
- **Username**: admin@example.com
- **Password**: password (atau sesuai seeder Anda)

### 10.3 Cek Fungsi
- ✅ Login/Logout
- ✅ Dashboard
- ✅ CRUD Barang
- ✅ Stok Masuk/Keluar
- ✅ Laporan

---

## TROUBLESHOOTING

### Error 500 Internal Server Error
```bash
# Cek log error
cat storage/logs/laravel.log

# Clear cache
php artisan cache:clear
php artisan config:clear

# Fix permission
chmod -R 775 storage bootstrap/cache
```

### Error "Class Not Found"
```bash
composer dump-autoload
php artisan config:cache
```

### Error Database Connection
1. Cek kredensial di `.env`
2. Pastikan database sudah dibuat
3. Cek host: biasanya `localhost` di Hostinger

### Halaman Blank Putih
1. Set `APP_DEBUG=true` sementara di `.env`
2. Refresh halaman untuk lihat error
3. Perbaiki error
4. Kembalikan `APP_DEBUG=false`

### Error Permission Denied
```bash
chmod -R 777 storage
chmod -R 777 bootstrap/cache
```

---

## TIPS KEAMANAN

### 1. Jangan Lupa
- ❌ Jangan set `APP_DEBUG=true` di production
- ❌ Jangan expose `.env` file
- ❌ Jangan gunakan password default

### 2. Update .env Production
```env
APP_ENV=production
APP_DEBUG=false
```

### 3. Hapus Route Debug
Hapus route `/run-migration` setelah selesai

### 4. Backup Rutin
Di hPanel > **Files** > **Backups** untuk backup otomatis

---

## CHECKLIST DEPLOYMENT

- [ ] Upload semua file project
- [ ] Buat database MySQL
- [ ] Update file `.env` dengan kredensial database
- [ ] Set permission folder storage & bootstrap/cache
- [ ] Jalankan migration
- [ ] Jalankan seeder
- [ ] Pasang SSL
- [ ] Force HTTPS
- [ ] Set PHP version 8.2+
- [ ] Test login
- [ ] Test semua fitur
- [ ] Set APP_DEBUG=false
- [ ] Hapus route debug

---

## BANTUAN

Jika mengalami kendala:
1. Cek error log: `storage/logs/laravel.log`
2. Hubungi support Hostinger via live chat
3. Dokumentasi Laravel: https://laravel.com/docs

---

**Selamat! Website SIMBAR Anda sudah online!** 🎉
