# 🚀 Deploy ke Railway.app

## Langkah-langkah Deployment

### STEP 1: Push ke GitHub

```bash
git add .
git commit -m "Prepare for Railway deployment"
git push origin main
```

### STEP 2: Buat Akun Railway

1. Buka: https://railway.app
2. Klik **Login** atau **Sign Up**
3. Login dengan **GitHub** (RECOMMENDED)

### STEP 3: Buat Project Baru

1. Klik **New Project**
2. Pilih **Deploy from GitHub repo**
3. Pilih repository: `manajemen-stok-puskesmas`
4. Klik **Deploy Now**

### STEP 4: Tambahkan PostgreSQL Database

1. Di dashboard project, klik **New**
2. Pilih **Database** → **PostgreSQL**
3. Tunggu database selesai dibuat

### STEP 5: Set Environment Variables

Klik service Laravel Anda → **Variables** → **New Variable**

Tambahkan variables berikut:

```
APP_NAME=Puskesmas Stock Management
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:GENERATE_NEW_KEY
APP_URL=${{RAILWAY_PUBLIC_DOMAIN}}

DB_CONNECTION=pgsql
DATABASE_URL=${{Postgres.DATABASE_URL}}

SESSION_DRIVER=database
CACHE_DRIVER=database
LOG_CHANNEL=stack
QUEUE_CONNECTION=sync
SESSION_SECURE_COOKIE=true
```

### STEP 6: Generate APP_KEY

Di terminal lokal, jalankan:
```bash
php artisan key:generate --show
```

Copy hasilnya (contoh: `base64:xxxxx...`) dan paste ke variable `APP_KEY` di Railway.

### STEP 7: Deploy!

Railway akan otomatis deploy setelah variables di-set.

---

## 🔐 Login Credentials

| Email | Password |
|-------|----------|
| admin@puskesmas.com | password123 |
| staff@puskesmas.com | password123 |

---

## 🌐 Akses Aplikasi

Setelah deploy berhasil:
1. Klik service Laravel
2. Klik tab **Settings**
3. Scroll ke **Domains**
4. Klik **Generate Domain**
5. Buka URL yang diberikan

---

## ⚠️ Troubleshooting

### Error: "No application encryption key"
- Set variable `APP_KEY` dengan hasil dari `php artisan key:generate --show`

### Error: "Database connection refused"
- Pastikan PostgreSQL database sudah dibuat
- Pastikan `DATABASE_URL` menggunakan reference `${{Postgres.DATABASE_URL}}`

### Error: "Migration failed"
- Buka Railway shell: Service → **Shell**
- Jalankan: `php artisan migrate --force`

---

## 📊 Monitoring

- Railway dashboard menampilkan:
  - CPU & Memory usage
  - Logs real-time
  - Deployment history

---

## 💰 Biaya

Railway memberikan **$5 free credits/bulan** untuk akun baru.
Cukup untuk aplikasi kecil-menengah.

---

## 🔄 Update Aplikasi

Setiap kali push ke GitHub, Railway akan otomatis re-deploy:

```bash
git add .
git commit -m "Update fitur"
git push origin main
```

Railway akan detect perubahan dan deploy otomatis!
