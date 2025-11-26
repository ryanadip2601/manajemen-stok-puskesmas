# 🚀 PostgreSQL Migration - Quick Start Guide

Panduan cepat untuk migrasi dari MySQL ke PostgreSQL dalam 10 menit!

---

## ⚡ Quick Migration (Local Testing)

### 1. Install PostgreSQL
```bash
# Ubuntu/Debian
sudo apt-get install postgresql postgresql-contrib php-pgsql

# macOS
brew install postgresql@15 php-pgsql
brew services start postgresql@15

# Windows: Download from https://www.postgresql.org/download/windows/
```

### 2. Create Database
```bash
# Login
sudo -u postgres psql

# Create
CREATE DATABASE puskesmas_stok;
\q
```

### 3. Update .env
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=puskesmas_stok
DB_USERNAME=postgres
DB_PASSWORD=
```

### 4. Test Connection
```bash
php test-postgres-connection.php
```

### 5. Migrate & Seed
```bash
php artisan migrate:fresh
php artisan db:seed
```

### 6. Test Application
```bash
php artisan serve
# Open http://localhost:8000
# Login: admin@puskesmas.com / password123
```

✅ **Done! Application running on PostgreSQL locally.**

---

## 🌐 Quick Deployment to Vercel

### 1. Create Vercel Account
- Go to: https://vercel.com
- Sign up with GitHub

### 2. Install Vercel CLI
```bash
npm install -g vercel
vercel login
```

### 3. Create Database
```bash
# Via CLI
vercel storage create postgres puskesmas-stok-db --region sin1

# Or via Dashboard: Storage → Create Database → Postgres
```

### 4. Link Project
```bash
vercel link
vercel env pull .env.production
```

### 5. Add Environment Variables
Go to Vercel Dashboard → Settings → Environment Variables

**Required:**
```env
APP_KEY=base64:GENERATE_WITH_php_artisan_key:generate
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=pgsql
DATABASE_URL=${POSTGRES_URL}
SESSION_DRIVER=cookie
CACHE_DRIVER=array
```

### 6. Run Production Migrations
```bash
# Use Vercel database URL
export DATABASE_URL="paste_your_POSTGRES_URL_here"
php artisan migrate --force
php artisan db:seed --force
```

### 7. Deploy
```bash
git add .
git commit -m "Deploy to Vercel with PostgreSQL"
git push origin main
```

Vercel will auto-deploy!

### 8. Verify
Visit: https://your-app.vercel.app

✅ **Done! Application running on Vercel with PostgreSQL.**

---

## 🔍 What Changed?

### Files Modified:
1. ✅ `config/database.php` - Added pgsql connection
2. ✅ `app/Http/Controllers/CategoryController.php` - LIKE → ILIKE
3. ✅ `app/Http/Controllers/ItemController.php` - LIKE → ILIKE
4. ✅ `.env.example` - Updated defaults to pgsql

### Files Created:
1. ✅ `vercel.json` - Vercel deployment config
2. ✅ `api/index.php` - Vercel API handler
3. ✅ `.vercelignore` - Files to ignore
4. ✅ `.env.vercel` - Vercel environment template
5. ✅ `test-postgres-connection.php` - Connection tester
6. ✅ `MYSQL_TO_POSTGRES_MIGRATION.md` - Full documentation
7. ✅ `VERCEL_DEPLOYMENT_GUIDE.md` - Deployment guide
8. ✅ `POSTGRESQL_MIGRATION_CHECKLIST.md` - Complete checklist

### Code Changes:
**Before:**
```php
->where('name', 'like', "%{$search}%")
```

**After:**
```php
->where('name', 'ILIKE', "%{$search}%")
```

**Why:** PostgreSQL LIKE is case-sensitive, ILIKE is case-insensitive.

---

## 📚 Full Documentation

For detailed information, read:

1. **MYSQL_TO_POSTGRES_MIGRATION.md** - All differences explained
2. **VERCEL_DEPLOYMENT_GUIDE.md** - Step-by-step deployment
3. **POSTGRESQL_MIGRATION_CHECKLIST.md** - Complete checklist

---

## 🆘 Troubleshooting

### Issue: "pdo_pgsql extension not loaded"
```bash
# Install
sudo apt-get install php-pgsql
php -m | grep pgsql  # Verify
```

### Issue: "Connection refused"
```bash
# Check PostgreSQL is running
sudo systemctl status postgresql
sudo systemctl start postgresql
```

### Issue: "SQLSTATE[08006]"
- Check DATABASE_URL format
- Verify SSL mode: `?sslmode=require`
- Test with: `php test-postgres-connection.php`

### Issue: "Table doesn't exist"
```bash
# Run migrations
php artisan migrate --force
```

### Issue: "419 CSRF Error" on Vercel
Add to Vercel env:
```env
SANCTUM_STATEFUL_DOMAINS=your-app.vercel.app
SESSION_DRIVER=cookie
```

---

## ✅ Verification Checklist

After migration, verify:

- [ ] ✅ Login works
- [ ] ✅ Dashboard shows statistics
- [ ] ✅ CRUD operations work
- [ ] ✅ Search is case-insensitive
- [ ] ✅ Stock auto-updates
- [ ] ✅ API authentication works
- [ ] ✅ No errors in logs

---

## 🎯 Key Differences MySQL vs PostgreSQL

| Feature | MySQL | PostgreSQL |
|---------|-------|------------|
| Default Port | 3306 | 5432 |
| Auto Increment | AUTO_INCREMENT | SERIAL |
| Case-Sensitive Search | LIKE (no) | LIKE (yes), ILIKE (no) |
| Boolean | TINYINT(1) | BOOLEAN |
| JSON | JSON | JSON/JSONB |

**Good news:** Laravel handles most differences automatically! 🎉

---

## 💡 Pro Tips

### 1. Use Connection Pooling
```env
# Use Prisma URL for better performance
DATABASE_URL="${POSTGRES_PRISMA_URL}"
```

### 2. Monitor Database Size
```bash
# Via psql
SELECT pg_size_pretty(pg_database_size('verceldb'));
```

### 3. Backup Regularly
```bash
# Create backup
pg_dump "YOUR_DATABASE_URL" > backup.sql

# Restore
psql "YOUR_DATABASE_URL" < backup.sql
```

### 4. Optimize Queries
```php
// Use eager loading
Item::with(['category', 'unit'])->get();

// Use indexes (already in migrations)
$table->index('column_name');
```

---

## 📞 Need Help?

- **Documentation:** Read VERCEL_DEPLOYMENT_GUIDE.md
- **Test Connection:** Run `php test-postgres-connection.php`
- **Vercel Support:** https://vercel.com/support
- **Laravel Discord:** https://discord.gg/laravel

---

## 🎉 Success!

Your application is now running on PostgreSQL! 🚀

**Benefits:**
- ✅ Better data integrity
- ✅ Advanced features (JSONB, Arrays, Full-text search)
- ✅ Free hosting on Vercel
- ✅ Auto-scaling
- ✅ Global CDN
- ✅ SSL included

**Happy coding!** 💻
