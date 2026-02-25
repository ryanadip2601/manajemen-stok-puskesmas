# 📦 Sistem Manajemen Stok Barang - UPTD Puskesmas Karang Rejo

Aplikasi web fullstack untuk mengelola stok barang masuk dan barang keluar di UPTD Puskesmas Karang Rejo.

## 🎯 Fitur Utama

### 1. **Manajemen Master Data**
- ✅ Kategori Barang
- ✅ Data Barang (Items)
- ✅ Satuan (Units)

### 2. **Manajemen Transaksi**
- ✅ Barang Masuk (Stock In) - Otomatis menambah stok
- ✅ Barang Keluar (Stock Out) - Otomatis mengurangi stok
- ✅ Validasi stok tidak boleh minus

### 3. **Dashboard**
- ✅ Total Barang
- ✅ Total Stok
- ✅ Barang Hampir Habis (Alert)
- ✅ Riwayat Transaksi Terbaru
- ✅ Kategori

### 4. **Sistem Logging**
- ✅ Pencatatan semua aktivitas user
- ✅ Riwayat transaksi lengkap

### 5. **REST API**
- ✅ Authentication dengan Laravel Sanctum
- ✅ CRUD untuk semua modul
- ✅ Response JSON terstruktur

### 6. **User Management**
- ✅ Role: Admin & Staff
- ✅ Authentication & Authorization

## 🛠️ Teknologi

- **Backend:** Laravel 10.x (PHP 8.1+)
- **Frontend:** Blade Template + TailwindCSS
- **Database:** MySQL
- **API:** REST API dengan Laravel Sanctum
- **Icons:** Font Awesome 6

## 📋 Persyaratan

- PHP >= 8.1
- Composer
- MySQL >= 5.7
- Node.js & NPM (optional untuk TailwindCSS compilation)

## 🚀 Instalasi

Lihat file [INSTALLATION.md](INSTALLATION.md) untuk panduan lengkap.

**Quick Start:**

```bash
# Clone project
git clone <repo-url>
cd website-barang-1.1

# Install dependencies
composer install

# Setup environment
copy .env.example .env
php artisan key:generate

# Setup database
# Edit .env dan sesuaikan DB_DATABASE, DB_USERNAME, DB_PASSWORD

# Migrate database
php artisan migrate

# Jalankan server
php artisan serve
```

Akses: http://localhost:8000

## 🌐 Deployment ke Hostinger

1. ZIP folder project
2. Upload ke `public_html` di Hostinger
3. Buat database MySQL di hPanel
4. Edit `.env` dengan kredensial database
5. Akses `https://domainanda.com/setup.php` untuk setup database
6. Hapus `setup.php` setelah selesai

Lihat **[HOSTINGER_DEPLOY_GUIDE.md](HOSTINGER_DEPLOY_GUIDE.md)** untuk panduan lengkap.

## 📚 Dokumentasi

1. **[HOSTINGER_DEPLOY_GUIDE.md](HOSTINGER_DEPLOY_GUIDE.md)** - Panduan deploy ke Hostinger
2. **[DEPLOY_CHECKLIST.md](DEPLOY_CHECKLIST.md)** - Checklist deployment
3. **[DATABASE_DESIGN.md](DATABASE_DESIGN.md)** - Desain database lengkap dengan ERD
4. **[API_DOCUMENTATION.md](API_DOCUMENTATION.md)** - Dokumentasi REST API lengkap
5. **[INSTALLATION.md](INSTALLATION.md)** - Panduan instalasi lokal

## 🎨 Screenshots

### Dashboard
Dashboard menampilkan statistik lengkap, barang hampir habis, dan transaksi terbaru.

### Data Barang
Manajemen barang dengan fitur search, filter kategori, dan alert stok rendah.

### Barang Masuk/Keluar
Form transaksi yang user-friendly dengan validasi otomatis.

## 🔐 Login Default

Setelah seeding data:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@admin.com | password |
| Admin Gudang | gudang@admin.com | password |
| Staff | staff@admin.com | password |

**⚠️ Segera ganti password setelah login!**

## 📁 Struktur Project

```
├── app/
│   ├── Http/Controllers/    # Controllers
│   ├── Models/              # Eloquent Models
│   └── Services/            # Business Logic (StockService)
├── database/
│   └── migrations/          # Database Migrations
├── resources/
│   └── views/               # Blade Templates
├── routes/
│   ├── web.php             # Web Routes
│   └── api.php             # API Routes
└── public/                  # Public Assets
```

## 🔄 Alur Kerja Stok Otomatis

### Barang Masuk:
```
User input → Validasi → Create record → Item.stock += quantity → Log aktivitas
```

### Barang Keluar:
```
User input → Cek stok → Create record → Item.stock -= quantity → Log aktivitas
                ↓
         Stok < quantity?
              ↓ Ya
         Show error
```

## 🌐 API Endpoints

### Authentication
- `POST /api/login` - Login
- `POST /api/logout` - Logout
- `GET /api/me` - Get current user

### Categories
- `GET /api/categories` - List categories
- `POST /api/categories` - Create category
- `PUT /api/categories/{id}` - Update category
- `DELETE /api/categories/{id}` - Delete category

### Items
- `GET /api/items` - List items
- `POST /api/items` - Create item
- `PUT /api/items/{id}` - Update item
- `DELETE /api/items/{id}` - Delete item

### Stock In
- `GET /api/stock-in` - List stock in
- `POST /api/stock-in` - Create stock in
- `PUT /api/stock-in/{id}` - Update stock in
- `DELETE /api/stock-in/{id}` - Delete stock in

### Stock Out
- `GET /api/stock-out` - List stock out
- `POST /api/stock-out` - Create stock out
- `PUT /api/stock-out/{id}` - Update stock out
- `DELETE /api/stock-out/{id}` - Delete stock out

Lihat [API_DOCUMENTATION.md](API_DOCUMENTATION.md) untuk detail lengkap.

## 🤝 Contributing

Proyek ini dibuat untuk UPTD Puskesmas Karang Rejo.

## 📄 License

MIT License

## 👨‍💻 Developer

Developed with ❤️ for UPTD Puskesmas Karang Rejo

---

**© 2024 UPTD Puskesmas Karang Rejo**
