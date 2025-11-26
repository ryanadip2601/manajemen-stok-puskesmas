# 🚀 Quick Start Guide - Sistem Manajemen Stok Barang

Panduan cepat untuk menjalankan aplikasi dalam 5 menit!

---

## ⚡ Instalasi Cepat

### 1. Install Dependencies
```bash
composer install
```

### 2. Setup Environment
```bash
copy .env.example .env
php artisan key:generate
```

### 3. Edit Database di .env
```env
DB_DATABASE=puskesmas_stok
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Buat Database
Buka phpMyAdmin atau MySQL CLI:
```sql
CREATE DATABASE puskesmas_stok;
```

### 5. Migrate & Seed
```bash
php artisan migrate
php artisan db:seed
```

### 6. Jalankan Server
```bash
php artisan serve
```

**Akses:** http://localhost:8000

---

## 🔐 Login

- **Email:** admin@puskesmas.com
- **Password:** password123

---

## 📌 Fitur Utama yang Bisa Dicoba

### 1. Dashboard
- Lihat statistik lengkap
- Monitor barang hampir habis
- Riwayat transaksi terbaru

### 2. Kelola Kategori
Menu: **Kategori** → Tambah kategori baru

### 3. Kelola Barang
Menu: **Data Barang** → Tambah barang baru

### 4. Transaksi Barang Masuk
Menu: **Barang Masuk** → Tambah transaksi masuk
- Stok otomatis bertambah

### 5. Transaksi Barang Keluar
Menu: **Barang Keluar** → Tambah transaksi keluar
- Stok otomatis berkurang
- Validasi otomatis jika stok tidak cukup

---

## 🌐 Test API

### Login ke API
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@puskesmas.com",
    "password": "password123"
  }'
```

Response:
```json
{
  "success": true,
  "data": {
    "token": "1|xxxxxxxx",
    "user": {...}
  }
}
```

### Get Dashboard Stats
```bash
curl -X GET http://localhost:8000/api/dashboard \
  -H "Authorization: Bearer {YOUR_TOKEN}"
```

### Get All Items
```bash
curl -X GET http://localhost:8000/api/items \
  -H "Authorization: Bearer {YOUR_TOKEN}"
```

---

## 🎯 Data Awal Setelah Seeding

### Users:
1. Admin: `admin@puskesmas.com` / `password123`
2. Staff: `staff@puskesmas.com` / `password123`

### Units:
- Box, Pcs, Strip, Botol, Tube, Sachet

### Categories:
- Obat-obatan
- Alat Kesehatan
- Vitamin & Suplemen
- Alat Tulis

---

## 🛠️ Troubleshooting Cepat

### Error: Application key not found
```bash
php artisan key:generate
```

### Error: Database connection failed
Cek `.env`:
- `DB_DATABASE` sudah dibuat?
- `DB_USERNAME` dan `DB_PASSWORD` benar?

### Error: Class not found
```bash
composer dump-autoload
```

### Port 8000 sudah dipakai
```bash
php artisan serve --port=8080
```

---

## 📖 Dokumentasi Lengkap

1. **[README.md](README.md)** - Overview aplikasi
2. **[INSTALLATION.md](INSTALLATION.md)** - Panduan instalasi detail
3. **[DATABASE_DESIGN.md](DATABASE_DESIGN.md)** - Struktur database
4. **[API_DOCUMENTATION.md](API_DOCUMENTATION.md)** - API Reference

---

## ✅ Checklist

- [ ] `composer install` selesai
- [ ] `.env` sudah dikonfigurasi
- [ ] Database sudah dibuat
- [ ] `php artisan migrate` berhasil
- [ ] `php artisan db:seed` berhasil
- [ ] Server berjalan di http://localhost:8000
- [ ] Bisa login dengan admin@puskesmas.com

---

## 🎉 Selamat!

Aplikasi siap digunakan! 🚀

Login sekarang: http://localhost:8000
