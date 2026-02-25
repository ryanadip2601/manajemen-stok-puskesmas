# 📦 DAFTAR FILE UNTUK UPLOAD KE HOSTINGER

**Tanggal:** 8 Desember 2025
**Project:** Sistem Manajemen Aset - UPTD Puskesmas Karang Rejo

---

## 🔴 FILE PENTING - WAJIB UPLOAD

### 1. Database (SANGAT PENTING!)
```
database/database.sqlite
```
> ⚠️ File ini berisi semua data barang aset yang sudah diimport (383 item)

### 2. File Konfigurasi
```
.env                          (sesuaikan dengan server Hostinger)
.htaccess                     (untuk URL rewriting)
composer.json
composer.lock
public/.htaccess
```

### 3. Controllers (Baru & Modified)
```
app/Http/Controllers/AuthController.php
app/Http/Controllers/BarangAsetController.php          ⭐ BARU
app/Http/Controllers/CategoryController.php
app/Http/Controllers/ItemController.php
app/Http/Controllers/KategoriAsetController.php        ⭐ BARU
app/Http/Controllers/PemeliharaanAsetController.php    ⭐ BARU
app/Http/Controllers/PenyusutanController.php          ⭐ BARU
app/Http/Controllers/ReportController.php
app/Http/Controllers/RuanganController.php             ⭐ BARU
app/Http/Controllers/StockOutController.php
app/Http/Controllers/StockOutRequestController.php     ⭐ BARU
app/Http/Controllers/UserMonitorController.php         ⭐ BARU
app/Http/Controllers/UsulanPenerimaanController.php    ⭐ BARU
```

### 4. Models (Baru & Modified)
```
app/Models/BarangAset.php           ⭐ BARU
app/Models/Item.php
app/Models/KategoriAset.php         ⭐ BARU
app/Models/LoginHistory.php         ⭐ BARU
app/Models/PemeliharaanAset.php     ⭐ BARU
app/Models/PenyusutanAset.php       ⭐ BARU
app/Models/Ruangan.php              ⭐ BARU
app/Models/StockOutRequest.php      ⭐ BARU
app/Models/User.php
app/Models/UsulanPenerimaanBarang.php ⭐ BARU
```

### 5. Middleware (Baru)
```
app/Http/Middleware/StaffAsetAccess.php     ⭐ BARU
app/Http/Middleware/UpdateLastActivity.php  ⭐ BARU
app/Http/Middleware/WarehouseAccess.php     ⭐ BARU
app/Http/Kernel.php
```

### 6. Services (Baru)
```
app/Services/PenyusutanService.php  ⭐ BARU
```

### 7. Database Migrations (Baru)
```
database/migrations/2024_01_03_000001_add_approval_to_users_table.php
database/migrations/2024_01_03_000002_create_password_reset_tokens_table.php
database/migrations/2024_01_04_000001_update_role_enum_in_users_table.php
database/migrations/2024_01_05_000001_create_stock_out_requests_table.php
database/migrations/2024_01_06_000001_create_login_histories_table.php
database/migrations/2024_01_07_000001_add_perolehan_and_price_to_items_table.php
database/migrations/2024_01_08_000001_create_ruangan_table.php
database/migrations/2024_01_08_000002_create_kategori_aset_table.php
database/migrations/2024_01_08_000003_create_barang_aset_table.php
database/migrations/2024_01_08_000004_create_pemeliharaan_aset_table.php
database/migrations/2024_01_09_000001_add_penyusutan_fields_to_kategori_aset.php
database/migrations/2024_01_09_000002_add_penyusutan_fields_to_barang_aset.php
database/migrations/2024_01_09_000003_create_penyusutan_aset_table.php
database/migrations/2024_01_10_000001_add_remember_token_to_users_table.php
database/migrations/2024_01_11_000001_create_usulan_penerimaan_barang_table.php
database/migrations/2024_01_12_000001_add_additional_fields_to_barang_aset.php
```

### 8. Database Seeders (Baru)
```
database/seeders/DatabaseSeeder.php
database/seeders/ImportBarangAsetSeeder.php   ⭐ BARU
database/seeders/SyncBarangAsetSeeder.php     ⭐ BARU
database/seeders/UpdateAdminSeeder.php        ⭐ BARU
```

### 9. Routes
```
routes/web.php
```

### 10. Views - Auth
```
resources/views/auth/forgot-password.blade.php
resources/views/auth/login.blade.php
resources/views/auth/pending-users.blade.php    ⭐ BARU
resources/views/auth/register.blade.php
resources/views/auth/reset-password.blade.php
```

### 11. Views - Modul Baru (FOLDER LENGKAP)
```
resources/views/barang-aset/          ⭐ FOLDER BARU
resources/views/kategori-aset/        ⭐ FOLDER BARU
resources/views/pemeliharaan/         ⭐ FOLDER BARU
resources/views/penyusutan/           ⭐ FOLDER BARU
resources/views/ruangan/              ⭐ FOLDER BARU
resources/views/stock_out_requests/   ⭐ FOLDER BARU
resources/views/users/                ⭐ FOLDER BARU
resources/views/usulan-penerimaan/    ⭐ FOLDER BARU
resources/views/emails/               ⭐ FOLDER BARU
resources/views/stock_out/staff/      ⭐ FOLDER BARU
```

### 12. Views - Modified
```
resources/views/categories/create.blade.php
resources/views/categories/edit.blade.php
resources/views/categories/index.blade.php
resources/views/dashboard/index.blade.php
resources/views/items/create.blade.php
resources/views/items/edit.blade.php
resources/views/items/index.blade.php
resources/views/layouts/app.blade.php
resources/views/reports/monthly.blade.php
resources/views/stock_in/create.blade.php
resources/views/stock_in/edit.blade.php
resources/views/stock_in/index.blade.php
resources/views/stock_out/create.blade.php
resources/views/stock_out/edit.blade.php
resources/views/stock_out/index.blade.php
```

### 13. Config
```
config/database.php
config/mail.php
```

### 14. Public Files
```
public/.htaccess
public/setup.php        (untuk setup database di server)
public/migrate.php      (untuk jalankan migrasi di server)
```

---

## 📁 CARA UPLOAD KE HOSTINGER

### Opsi 1: Upload Semua (Direkomendasikan)
Upload seluruh folder project ke `public_html` di Hostinger.

### Opsi 2: Upload File yang Berubah Saja
1. Gunakan FileZilla atau File Manager Hostinger
2. Upload semua file di atas ke lokasi yang sesuai
3. Pastikan struktur folder sama

---

## ⚙️ LANGKAH SETELAH UPLOAD

### 1. Setup Database di Hostinger
Akses: `https://domain-anda.com/setup.php`

### 2. Jalankan Migrasi
Akses: `https://domain-anda.com/migrate.php`

### 3. Hapus File Setup (PENTING!)
Setelah selesai, hapus file berikut dari server:
- `public/setup.php`
- `public/migrate.php`

### 4. Set Permission
```
storage/          -> 775
bootstrap/cache/  -> 775
```

---

## 📊 DATA YANG SUDAH TERIMPORT

- **383 Barang Aset**
- **30 Ruangan**
- **15 Kategori**
- **Total Nilai: Rp 1.964.731.517**

---

## 🔐 LOGIN DEFAULT

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@admin.com | password |
| Admin Gudang | gudang@admin.com | password |
| Staff | staff@admin.com | password |

**⚠️ SEGERA GANTI PASSWORD SETELAH LOGIN!**
