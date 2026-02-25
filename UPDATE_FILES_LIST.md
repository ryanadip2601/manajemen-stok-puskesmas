# DAFTAR FILE YANG PERLU DIUPDATE KE HOSTINGER

## A. FILE YANG DIMODIFIKASI (REPLACE/TIMPA)

### Controllers (app/Http/Controllers/)
- [ ] `AuthController.php`
- [ ] `CategoryController.php`
- [ ] `ItemController.php`
- [ ] `ReportController.php`
- [ ] `StockInController.php`
- [ ] `StockOutController.php`

### Models (app/Models/)
- [ ] `Item.php`
- [ ] `User.php`

### Middleware (app/Http/)
- [ ] `Kernel.php`

### Config (config/)
- [ ] `database.php`
- [ ] `mail.php`

### Views (resources/views/)
- [ ] `auth/forgot-password.blade.php`
- [ ] `auth/login.blade.php`
- [ ] `auth/register.blade.php`
- [ ] `auth/reset-password.blade.php`
- [ ] `categories/create.blade.php`
- [ ] `categories/edit.blade.php`
- [ ] `categories/index.blade.php`
- [ ] `dashboard/index.blade.php`
- [ ] `items/create.blade.php`
- [ ] `items/edit.blade.php`
- [ ] `items/index.blade.php`
- [ ] `layouts/app.blade.php`
- [ ] `reports/monthly.blade.php`
- [ ] `stock_in/create.blade.php`
- [ ] `stock_in/edit.blade.php`
- [ ] `stock_in/index.blade.php`
- [ ] `stock_out/create.blade.php`
- [ ] `stock_out/edit.blade.php`
- [ ] `stock_out/index.blade.php`

### Routes
- [ ] `routes/web.php`

### Database
- [ ] `database/seeders/DatabaseSeeder.php`

### Root Files
- [ ] `composer.json`
- [ ] `composer.lock`

---

## B. FILE BARU (UPLOAD)

### Controllers Baru (app/Http/Controllers/)
- [ ] `BarangAsetController.php`
- [ ] `KategoriAsetController.php`
- [ ] `PemeliharaanAsetController.php`
- [ ] `PenyusutanController.php`
- [ ] `RuanganController.php`
- [ ] `StockOutRequestController.php`
- [ ] `UserMonitorController.php`
- [ ] `UsulanPenerimaanController.php`

### Middleware Baru (app/Http/Middleware/)
- [ ] `StaffAsetAccess.php`
- [ ] `UpdateLastActivity.php`
- [ ] `WarehouseAccess.php`

### Models Baru (app/Models/)
- [ ] `BarangAset.php`
- [ ] `KategoriAset.php`
- [ ] `LoginHistory.php`
- [ ] `PemeliharaanAset.php`
- [ ] `PenyusutanAset.php`
- [ ] `Ruangan.php`
- [ ] `StockOutRequest.php`
- [ ] `UsulanPenerimaanBarang.php`

### Services Baru (app/Services/)
- [ ] `PenyusutanService.php`

### Migrations Baru (database/migrations/)
- [ ] `2024_01_03_000001_add_approval_to_users_table.php`
- [ ] `2024_01_03_000002_create_password_reset_tokens_table.php`
- [ ] `2024_01_04_000001_update_role_enum_in_users_table.php`
- [ ] `2024_01_05_000001_create_stock_out_requests_table.php`
- [ ] `2024_01_06_000001_create_login_histories_table.php`
- [ ] `2024_01_07_000001_add_perolehan_and_price_to_items_table.php`
- [ ] `2024_01_08_000001_create_ruangan_table.php`
- [ ] `2024_01_08_000002_create_kategori_aset_table.php`
- [ ] `2024_01_08_000003_create_barang_aset_table.php`
- [ ] `2024_01_08_000004_create_pemeliharaan_aset_table.php`
- [ ] `2024_01_09_000001_add_penyusutan_fields_to_kategori_aset.php`
- [ ] `2024_01_09_000002_add_penyusutan_fields_to_barang_aset.php`
- [ ] `2024_01_09_000003_create_penyusutan_aset_table.php`
- [ ] `2024_01_10_000001_add_remember_token_to_users_table.php`
- [ ] `2024_01_11_000001_create_usulan_penerimaan_barang_table.php`
- [ ] `2024_01_12_000001_add_additional_fields_to_barang_aset.php`

### Views Baru (resources/views/)

#### Auth
- [ ] `auth/pending-users.blade.php`

#### Barang Aset (FOLDER BARU)
- [ ] `barang-aset/` (semua file di dalamnya)

#### Emails (FOLDER BARU)
- [ ] `emails/` (semua file di dalamnya)

#### Items
- [ ] `items/import.blade.php`

#### Kategori Aset (FOLDER BARU)
- [ ] `kategori-aset/` (semua file di dalamnya)

#### Pemeliharaan (FOLDER BARU)
- [ ] `pemeliharaan/` (semua file di dalamnya)

#### Penyusutan (FOLDER BARU)
- [ ] `penyusutan/` (semua file di dalamnya)

#### Ruangan (FOLDER BARU)
- [ ] `ruangan/` (semua file di dalamnya)

#### Stock Out Staff (FOLDER BARU)
- [ ] `stock_out/staff/` (semua file di dalamnya)

#### Stock Out Requests (FOLDER BARU)
- [ ] `stock_out_requests/` (semua file di dalamnya)

#### Users (FOLDER BARU)
- [ ] `users/` (semua file di dalamnya)

#### Usulan Penerimaan (FOLDER BARU)
- [ ] `usulan-penerimaan/` (semua file di dalamnya)

### Public Files
- [ ] `public/.htaccess`

### Root Files
- [ ] `.htaccess`

---

## C. FILE YANG DIHAPUS (HAPUS DI SERVER)

- [ ] `MIGRATION_SUMMARY.md`
- [ ] `MYSQL_TO_POSTGRES_MIGRATION.md`
- [ ] `POSTGRESQL_MIGRATION_CHECKLIST.md`
- [ ] `POSTGRESQL_QUICK_START.md`
- [ ] `RAILWAY_DEPLOY.md`
- [ ] `README_POSTGRESQL.md`
- [ ] `VERCEL_404_FIX.md`
- [ ] `VERCEL_DEPLOYMENT_GUIDE.md`
- [ ] `VERCEL_VITE_FIX.md`
- [ ] `nixpacks.toml`
- [ ] `railway.json`
- [ ] `start.sh`
- [ ] `resources/views/auth/verify-code.blade.php`

---

## D. JANGAN DIUPLOAD (ABAIKAN)

- [ ] `database/database.sqlite` - Gunakan MySQL di server
- [ ] `database/database_backup.sqlite`
- [ ] `storage/framework/sessions/*`
- [ ] `storage/framework/views/*`
- [ ] `backup_database.php`
- [ ] `merge_databases.php`
- [ ] `public/fix_barang_aset.php`
- [ ] `public/migrate.php`
- [ ] `public/setup.php`
- [ ] File-file `.md` dokumentasi

---

## E. SETELAH UPLOAD - JALANKAN MIGRATION

Karena ada migration baru, setelah upload jalankan:

```bash
php artisan migrate --force
```

Atau via browser (jika tidak ada SSH):
```
https://domain.com/run-setup/rahasia123
```

---

## F. STRUKTUR FOLDER UPLOAD

```
hostinger/public_html/
├── app/
│   ├── Http/
│   │   ├── Controllers/     <- UPDATE + TAMBAH BARU
│   │   └── Middleware/      <- TAMBAH BARU
│   ├── Models/              <- UPDATE + TAMBAH BARU
│   └── Services/            <- TAMBAH BARU
├── config/                  <- UPDATE
├── database/
│   └── migrations/          <- TAMBAH BARU
├── resources/
│   └── views/               <- UPDATE + TAMBAH FOLDER BARU
├── routes/
│   └── web.php              <- UPDATE
├── public/
│   └── .htaccess            <- UPDATE
├── composer.json            <- UPDATE
├── composer.lock            <- UPDATE
└── .htaccess                <- UPDATE
```

---

**PENTING:** Setelah upload `composer.json` dan `composer.lock`, jalankan:
```bash
composer install --no-dev --optimize-autoloader
```
