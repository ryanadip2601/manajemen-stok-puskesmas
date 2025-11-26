# 🚀 Panduan Deployment ke Vercel dengan PostgreSQL

## 📋 Prerequisites

- [x] Akun Vercel (https://vercel.com)
- [x] GitHub/GitLab account
- [x] Repository Git untuk project ini
- [x] Aplikasi sudah ditest di local dengan PostgreSQL

---

## 🗄️ PART 1: Setup Vercel Postgres Database

### Step 1: Login ke Vercel
```bash
# Install Vercel CLI
npm install -g vercel

# Login
vercel login
```

### Step 2: Buat Project di Vercel Dashboard

1. Buka: https://vercel.com/dashboard
2. Klik **"Add New..."** → **"Project"**
3. Import repository Git Anda
4. Project name: `puskesmas-stok` (atau nama lain)

### Step 3: Create Vercel Postgres Database

#### Via Dashboard:
1. Buka project Anda di Vercel Dashboard
2. Tab **"Storage"**
3. Klik **"Create Database"**
4. Pilih **"Postgres"**
5. Database name: `puskesmas-stok-db`
6. Region: Pilih yang terdekat (misalnya: Singapore - sin1)
7. Klik **"Create"**

#### Via CLI (Alternative):
```bash
vercel storage create postgres puskesmas-stok-db --region sin1
```

### Step 4: Link Database ke Project

#### Via Dashboard:
1. Setelah database dibuat, akan muncul di tab Storage
2. Klik database tersebut
3. Tab **"Settings"**
4. Scroll ke **"Projects Connected"**
5. Klik **"Connect Project"**
6. Pilih project Anda

#### Via CLI:
```bash
vercel link
vercel env pull .env.local
```

### Step 5: Get Connection Credentials

Setelah database terhubung, Vercel otomatis menambahkan environment variables:

**Di Dashboard → Settings → Environment Variables:**

```env
POSTGRES_URL="postgres://default:xxxxx@xxx.postgres.vercel-storage.com:5432/verceldb"
POSTGRES_PRISMA_URL="postgres://default:xxxxx@xxx.postgres.vercel-storage.com:5432/verceldb?pgbouncer=true&connect_timeout=15"
POSTGRES_URL_NON_POOLING="postgres://default:xxxxx@xxx.postgres.vercel-storage.com:5432/verceldb"
POSTGRES_USER="default"
POSTGRES_HOST="xxx.postgres.vercel-storage.com"
POSTGRES_PASSWORD="xxxxx"
POSTGRES_DATABASE="verceldb"
```

---

## ⚙️ PART 2: Configure Environment Variables

### Step 1: Add Laravel Environment Variables

Di **Vercel Dashboard → Your Project → Settings → Environment Variables**, tambahkan:

#### Required Variables:
```env
APP_NAME="Manajemen Stok Puskesmas"
APP_ENV=production
APP_KEY=base64:GENERATE_THIS_FIRST
APP_DEBUG=false
APP_URL=https://your-app.vercel.app

DB_CONNECTION=pgsql
DATABASE_URL=${POSTGRES_URL}

LOG_CHANNEL=stderr
CACHE_DRIVER=array
SESSION_DRIVER=cookie
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=your-app.vercel.app
```

### Step 2: Generate APP_KEY

**Local:**
```bash
php artisan key:generate --show
```

Copy output (contoh: `base64:xxxxxxxxxxxxxx`) dan tambahkan ke Vercel Environment Variables.

**Atau via online:**
```bash
# Generate random base64 key
echo "base64:$(openssl rand -base64 32)"
```

### Step 3: Set Environment Scope

Untuk setiap variable, set scope:
- ✅ Production
- ✅ Preview
- ✅ Development

---

## 🏗️ PART 3: Prepare Project for Deployment

### Step 1: Install PHP PostgreSQL Extension (Local Testing)

**Ubuntu/Debian:**
```bash
sudo apt-get update
sudo apt-get install php-pgsql php-mbstring php-xml
```

**macOS:**
```bash
brew install php
brew install php-pgsql
```

**Windows:**
Edit `php.ini` dan uncomment:
```ini
extension=pdo_pgsql
extension=pgsql
```

Restart server:
```bash
# Restart Apache/Nginx
sudo service apache2 restart
# atau
sudo service nginx restart
```

### Step 2: Test Local Connection

Edit `.env`:
```env
DB_CONNECTION=pgsql
DATABASE_URL="postgres://default:xxx@xxx.postgres.vercel-storage.com:5432/verceldb?sslmode=require"
```

Test:
```bash
php artisan db:show
php artisan migrate:status
```

### Step 3: Run Migrations (IMPORTANT!)

**Via Local Machine:**
```bash
# Set DATABASE_URL dari Vercel
export DATABASE_URL="postgres://default:xxx@xxx.postgres.vercel-storage.com:5432/verceldb?sslmode=require"

# Run migrations
php artisan migrate --force

# Seed data (optional)
php artisan db:seed --force
```

**Via Vercel CLI:**
```bash
vercel env pull .env.production
php artisan migrate --env=production --force
```

⚠️ **PENTING:** Vercel serverless functions tidak bisa run migrations otomatis. Harus run manual dari local ke production database!

### Step 4: Optimize for Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

⚠️ **NOTE:** Di Vercel, cache files akan disimpan di `/tmp`, jadi cache akan reset setiap cold start.

---

## 📦 PART 4: Deploy to Vercel

### Method 1: Via Git Push (Recommended)

1. **Commit semua changes:**
```bash
git add .
git commit -m "Migrate to PostgreSQL for Vercel"
git push origin main
```

2. **Vercel auto-deploy:**
- Vercel akan detect push
- Build otomatis
- Deploy otomatis

3. **Check deployment:**
- Dashboard → Deployments
- Lihat logs untuk troubleshooting

### Method 2: Via Vercel CLI

```bash
# Build
vercel build --prod

# Deploy
vercel --prod
```

### Method 3: Manual via Dashboard

1. Dashboard → Project → Deployments
2. Klik **"Redeploy"**
3. Tunggu sampai selesai

---

## 🧪 PART 5: Testing & Verification

### Step 1: Test Database Connection

**Create test route** di `routes/web.php`:
```php
Route::get('/test-db', function () {
    try {
        $pdo = DB::connection()->getPdo();
        $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
        
        return response()->json([
            'status' => 'success',
            'database' => DB::connection()->getDatabaseName(),
            'tables' => $tables,
            'driver' => DB::connection()->getDriverName()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});
```

Access: `https://your-app.vercel.app/test-db`

### Step 2: Test API Endpoints

**Login:**
```bash
curl -X POST https://your-app.vercel.app/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@puskesmas.com",
    "password": "password123"
  }'
```

**Get Dashboard:**
```bash
curl -X GET https://your-app.vercel.app/api/dashboard \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Step 3: Test Web Interface

1. Open: `https://your-app.vercel.app`
2. Login dengan credentials
3. Test CRUD operations
4. Check search functionality

### Step 4: Monitor Logs

**Vercel Dashboard:**
- Project → Deployments → Latest
- Tab "Logs"

**Via CLI:**
```bash
vercel logs your-app.vercel.app --follow
```

---

## 🔍 PART 6: Troubleshooting

### Issue 1: "No application encryption key"
**Solution:**
```bash
# Generate key
php artisan key:generate --show

# Add to Vercel Environment Variables
APP_KEY=base64:xxxxxx
```

### Issue 2: "SQLSTATE[08006] Connection refused"
**Possible causes:**
1. `DATABASE_URL` tidak set
2. SSL mode salah
3. IP whitelist (Vercel Postgres biasanya tidak perlu)

**Solution:**
```bash
# Verify environment variables
vercel env ls

# Test connection dari local
psql "postgres://default:xxx@xxx.postgres.vercel-storage.com:5432/verceldb?sslmode=require"
```

### Issue 3: "Table doesn't exist"
**Solution:**
```bash
# Run migrations manual
php artisan migrate --env=production --force
```

### Issue 4: "419 CSRF Token Mismatch"
**Solution:**
Tambahkan domain ke `config/sanctum.php`:
```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'your-app.vercel.app')),
```

### Issue 5: Session tidak persist
**Solution:**
Update `.env.vercel`:
```env
SESSION_DRIVER=cookie
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

### Issue 6: Cold Start Slow
**Explanation:** Vercel serverless functions sleep jika tidak ada request. First request akan lambat.

**Solutions:**
1. Upgrade ke Vercel Pro (no cold start)
2. Keep-alive ping every 5 minutes
3. Optimize autoload: `composer install --optimize-autoloader --no-dev`

### Issue 7: Storage Write Error
**Explanation:** Vercel filesystem is read-only except `/tmp`

**Solution:**
Update `config/view.php`:
```php
'compiled' => env('VIEW_COMPILED_PATH', realpath(storage_path('framework/views'))),
```

Set di Vercel env:
```env
VIEW_COMPILED_PATH=/tmp/views
```

---

## 📊 PART 7: Database Management

### Backup Database

**Via CLI:**
```bash
# Install pg_dump
# Ubuntu/Debian
sudo apt-get install postgresql-client

# Backup
pg_dump "postgres://default:xxx@xxx.postgres.vercel-storage.com:5432/verceldb?sslmode=require" > backup.sql
```

**Via Vercel Dashboard:**
1. Storage → Your Database
2. Tab "Backups"
3. Download backup

### Restore Database

```bash
psql "postgres://default:xxx@xxx.postgres.vercel-storage.com:5432/verceldb?sslmode=require" < backup.sql
```

### View Database

**Via psql:**
```bash
psql "postgres://default:xxx@xxx.postgres.vercel-storage.com:5432/verceldb?sslmode=require"

# List tables
\dt

# Query
SELECT * FROM users;
```

**Via GUI Tools:**
- DBeaver: https://dbeaver.io/
- TablePlus: https://tableplus.com/
- pgAdmin: https://www.pgadmin.org/

Connection details dari Vercel environment variables.

---

## ⚡ PART 8: Performance Optimization

### 1. Enable OPcache

Create `php.ini` di root:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
```

### 2. Optimize Composer Autoload

```bash
composer install --optimize-autoloader --no-dev --no-scripts
```

### 3. Database Connection Pooling

Vercel Postgres sudah menggunakan PgBouncer untuk connection pooling.

Gunakan `POSTGRES_PRISMA_URL` untuk pooled connection:
```env
DATABASE_URL="${POSTGRES_PRISMA_URL}"
```

### 4. Cache Configuration

Update `.env`:
```env
CACHE_DRIVER=array
CONFIG_CACHE_PATH=/tmp/config.php
ROUTES_CACHE_PATH=/tmp/routes.php
```

### 5. Optimize Images & Assets

```bash
# Install Laravel Mix atau Vite
npm install
npm run production

# Atau gunakan CDN untuk assets
```

---

## 🔒 PART 9: Security Best Practices

### 1. Environment Variables Security
- ✅ Jangan commit `.env` ke Git
- ✅ Use Vercel Environment Variables
- ✅ Rotate `APP_KEY` regularly
- ✅ Use strong `POSTGRES_PASSWORD`

### 2. Database Security
- ✅ Enable SSL mode: `sslmode=require`
- ✅ Use least-privilege database user
- ✅ Regularly backup database
- ✅ Monitor database access logs

### 3. API Security
- ✅ Rate limiting di routes
- ✅ CORS configuration
- ✅ Sanctum token expiration
- ✅ Input validation & sanitization

### 4. Laravel Security
- ✅ `APP_DEBUG=false` di production
- ✅ CSRF protection enabled
- ✅ XSS protection
- ✅ SQL injection protection (use Eloquent)

---

## 📈 PART 10: Monitoring & Logging

### Vercel Analytics
Enable di Dashboard → Project → Analytics

### Custom Logging
```php
// Log to stderr (Vercel compatible)
Log::channel('stderr')->info('Database query executed', [
    'query' => $query,
    'time' => $time
]);
```

### Error Tracking
Integrate services:
- Sentry: https://sentry.io
- Bugsnag: https://www.bugsnag.com
- Rollbar: https://rollbar.com

```bash
composer require sentry/sentry-laravel
```

---

## ✅ Deployment Checklist

Before deploying to production:

- [ ] ✅ Migrations tested di local PostgreSQL
- [ ] ✅ All environment variables set di Vercel
- [ ] ✅ APP_KEY generated
- [ ] ✅ APP_DEBUG=false
- [ ] ✅ Database seeded dengan data awal
- [ ] ✅ CORS configured untuk domain
- [ ] ✅ SANCTUM_STATEFUL_DOMAINS set
- [ ] ✅ Test endpoints via Postman/curl
- [ ] ✅ Web interface tested
- [ ] ✅ Search functionality tested
- [ ] ✅ CRUD operations tested
- [ ] ✅ Database backup created
- [ ] ✅ Error logging configured
- [ ] ✅ Security headers set
- [ ] ✅ SSL certificate active

---

## 🎯 Quick Deployment Commands

```bash
# 1. Setup Vercel
vercel login
vercel link

# 2. Pull environment variables
vercel env pull .env.production

# 3. Run migrations (manual)
php artisan migrate --env=production --force
php artisan db:seed --env=production --force

# 4. Deploy
git add .
git commit -m "Deploy to Vercel with PostgreSQL"
git push origin main

# 5. Verify
vercel logs --follow
```

---

## 📞 Support & Resources

### Documentation:
- Vercel Postgres: https://vercel.com/docs/storage/vercel-postgres
- Vercel PHP: https://vercel.com/docs/runtimes/php
- Laravel Deployment: https://laravel.com/docs/deployment

### Community:
- Vercel Discord: https://vercel.com/discord
- Laravel Discord: https://discord.gg/laravel

### Help:
- Vercel Support: https://vercel.com/support
- Laravel Forums: https://laracasts.com/discuss

---

## 🎉 Deployment Complete!

Your application is now running on Vercel with PostgreSQL! 🚀

**Live URL:** https://your-app.vercel.app

**Next Steps:**
1. ✅ Setup custom domain (optional)
2. ✅ Configure CDN for assets
3. ✅ Setup monitoring & alerts
4. ✅ Schedule database backups
5. ✅ Performance testing & optimization

---

**Happy Deploying! 🎊**
