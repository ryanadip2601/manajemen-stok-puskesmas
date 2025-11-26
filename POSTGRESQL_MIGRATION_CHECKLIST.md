# ✅ PostgreSQL Migration Checklist

## 📋 Pre-Migration Checklist

- [ ] Backup MySQL database
- [ ] Install PostgreSQL locally for testing
- [ ] Install PHP pgsql extension
- [ ] Test PostgreSQL connection
- [ ] Review migration files

---

## 🔄 Migration Steps

### 1. Local Development Setup

#### Install PostgreSQL
**Ubuntu/Debian:**
```bash
sudo apt-get update
sudo apt-get install postgresql postgresql-contrib
sudo systemctl start postgresql
sudo systemctl enable postgresql
```

**macOS:**
```bash
brew install postgresql@15
brew services start postgresql@15
```

**Windows:**
Download installer: https://www.postgresql.org/download/windows/

#### Install PHP Extensions
```bash
# Ubuntu/Debian
sudo apt-get install php-pgsql php-mbstring php-xml

# macOS
brew install php-pgsql

# Windows: Edit php.ini
extension=pdo_pgsql
extension=pgsql
```

#### Create Database
```bash
# Login ke PostgreSQL
sudo -u postgres psql

# Create database
CREATE DATABASE puskesmas_stok;

# Create user (optional)
CREATE USER puskesmas_user WITH PASSWORD 'your_password';
GRANT ALL PRIVILEGES ON DATABASE puskesmas_stok TO puskesmas_user;

# Exit
\q
```

---

### 2. Update Configuration Files

#### Update config/database.php
- [ ] ✅ Added pgsql connection configuration
- [ ] ✅ Changed default to 'pgsql'
- [ ] ✅ Added sslmode configuration

#### Update .env
- [ ] ✅ Changed DB_CONNECTION to pgsql
- [ ] ✅ Changed DB_PORT to 5432
- [ ] ✅ Updated DB_USERNAME
- [ ] ✅ Updated DB_PASSWORD
- [ ] ✅ Added DB_SSLMODE

Example:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=puskesmas_stok
DB_USERNAME=postgres
DB_PASSWORD=
DB_SSLMODE=prefer
```

---

### 3. Update Controllers

#### Files to Update:
- [ ] ✅ app/Http/Controllers/CategoryController.php
  - Changed `LIKE` to `ILIKE` (line 15)
  
- [ ] ✅ app/Http/Controllers/ItemController.php
  - Changed `LIKE` to `ILIKE` (line 17, 18)

#### What Changed:
```php
// Before (MySQL)
->where('name', 'like', "%{$search}%")

// After (PostgreSQL)
->where('name', 'ILIKE', "%{$search}%")
```

**Why:** PostgreSQL's `LIKE` is case-sensitive. Use `ILIKE` for case-insensitive search.

---

### 4. Test Local Migration

#### Run Migrations
```bash
php artisan migrate:fresh
```

Expected output:
```
Dropped all tables successfully.
Migration table created successfully.
Migrating: 2024_01_01_000000_create_users_table
Migrated:  2024_01_01_000000_create_users_table (XX.XXms)
Migrating: 2024_01_01_000001_create_categories_table
Migrated:  2024_01_01_000001_create_categories_table (XX.XXms)
...
```

- [ ] ✅ All migrations ran successfully
- [ ] ✅ No errors

#### Seed Database
```bash
php artisan db:seed
```

- [ ] ✅ Seeding completed
- [ ] ✅ Users created
- [ ] ✅ Categories created
- [ ] ✅ Units created

#### Test Connection
```bash
php artisan db:show
```

Expected output:
```
PostgreSQL ......... 15.x
Database ........... puskesmas_stok
Host ............... 127.0.0.1
Port ............... 5432
Username ........... postgres
```

- [ ] ✅ Connection successful

#### Run Test Script
```bash
php test-postgres-connection.php
```

- [ ] ✅ All tests passed

---

### 5. Test Application Locally

#### Start Server
```bash
php artisan serve
```

#### Test Web Interface
- [ ] ✅ Login page loads
- [ ] ✅ Can login with admin@puskesmas.com
- [ ] ✅ Dashboard shows statistics
- [ ] ✅ Categories CRUD works
- [ ] ✅ Items CRUD works
- [ ] ✅ Stock In CRUD works
- [ ] ✅ Stock Out CRUD works
- [ ] ✅ Search functionality works (case-insensitive)
- [ ] ✅ Pagination works
- [ ] ✅ Stock auto-update works

#### Test API
```bash
# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@puskesmas.com","password":"password123"}'
```

- [ ] ✅ API login works
- [ ] ✅ Token received

```bash
# Get Dashboard (use token from login)
curl -X GET http://localhost:8000/api/dashboard \
  -H "Authorization: Bearer YOUR_TOKEN"
```

- [ ] ✅ Dashboard API works
- [ ] ✅ Statistics returned correctly

---

### 6. Vercel Setup

#### Create Vercel Account
- [ ] ✅ Signed up at https://vercel.com
- [ ] ✅ Connected GitHub account

#### Create Vercel Postgres Database
- [ ] ✅ Created new project in Vercel
- [ ] ✅ Created Postgres database via Storage tab
- [ ] ✅ Database region selected
- [ ] ✅ Database linked to project

#### Get Connection Credentials
- [ ] ✅ Copied POSTGRES_URL
- [ ] ✅ Copied all environment variables

Example values:
```env
POSTGRES_URL=postgres://default:xxx@xxx.postgres.vercel-storage.com:5432/verceldb
POSTGRES_PRISMA_URL=postgres://default:xxx@xxx.postgres.vercel-storage.com:5432/verceldb?pgbouncer=true
POSTGRES_URL_NON_POOLING=postgres://default:xxx@xxx.postgres.vercel-storage.com:5432/verceldb
```

---

### 7. Configure Vercel Environment

#### Add Environment Variables
Go to Vercel Dashboard → Project → Settings → Environment Variables

Add the following:

**App Configuration:**
- [ ] ✅ APP_NAME
- [ ] ✅ APP_ENV=production
- [ ] ✅ APP_KEY (generate with: `php artisan key:generate --show`)
- [ ] ✅ APP_DEBUG=false
- [ ] ✅ APP_URL=https://your-app.vercel.app

**Database:**
- [ ] ✅ DB_CONNECTION=pgsql
- [ ] ✅ DATABASE_URL=${POSTGRES_URL}

**Cache & Session:**
- [ ] ✅ CACHE_DRIVER=array
- [ ] ✅ SESSION_DRIVER=cookie
- [ ] ✅ SESSION_SECURE_COOKIE=true
- [ ] ✅ LOG_CHANNEL=stderr

**Sanctum:**
- [ ] ✅ SANCTUM_STATEFUL_DOMAINS=your-app.vercel.app

**All variables set for:**
- [ ] ✅ Production
- [ ] ✅ Preview
- [ ] ✅ Development

---

### 8. Run Production Migrations

#### Connect to Production Database
```bash
# Set DATABASE_URL from Vercel
export DATABASE_URL="postgres://default:xxx@xxx.postgres.vercel-storage.com:5432/verceldb?sslmode=require"

# Or edit .env
DATABASE_URL="postgres://default:xxx@xxx.postgres.vercel-storage.com:5432/verceldb?sslmode=require"
```

#### Test Connection
```bash
php test-postgres-connection.php
```

- [ ] ✅ Connection to Vercel Postgres successful

#### Run Migrations
```bash
php artisan migrate --force
```

- [ ] ✅ All migrations completed
- [ ] ✅ No errors

#### Seed Production Database
```bash
php artisan db:seed --force
```

- [ ] ✅ Seeding completed
- [ ] ✅ Admin user created
- [ ] ✅ Initial data created

---

### 9. Deploy to Vercel

#### Prepare Files
- [ ] ✅ vercel.json created
- [ ] ✅ .vercelignore created
- [ ] ✅ api/index.php created
- [ ] ✅ .env.vercel created

#### Commit Changes
```bash
git add .
git commit -m "Migrate to PostgreSQL for Vercel deployment"
git push origin main
```

- [ ] ✅ Changes committed
- [ ] ✅ Pushed to GitHub

#### Deploy
**Method 1: Auto-deploy via Git**
- [ ] ✅ Vercel detected push
- [ ] ✅ Build started
- [ ] ✅ Build completed
- [ ] ✅ Deployment successful

**Method 2: Manual via CLI**
```bash
vercel --prod
```

- [ ] ✅ Deployment completed
- [ ] ✅ Got deployment URL

---

### 10. Verify Production Deployment

#### Test Database Connection
Access: `https://your-app.vercel.app/test-db`

- [ ] ✅ Returns success status
- [ ] ✅ Shows all tables
- [ ] ✅ Driver is 'pgsql'

#### Test Web Interface
- [ ] ✅ Home redirects to login
- [ ] ✅ Login page loads correctly
- [ ] ✅ Can login with credentials
- [ ] ✅ Dashboard loads with statistics
- [ ] ✅ All menu items accessible
- [ ] ✅ CRUD operations work
- [ ] ✅ Search works (case-insensitive)
- [ ] ✅ Stock auto-update works
- [ ] ✅ No JavaScript errors in console
- [ ] ✅ No PHP errors in logs

#### Test API Endpoints
```bash
# Login
curl -X POST https://your-app.vercel.app/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@puskesmas.com","password":"password123"}'
```

- [ ] ✅ API login works
- [ ] ✅ Token received

```bash
# Dashboard
curl -X GET https://your-app.vercel.app/api/dashboard \
  -H "Authorization: Bearer TOKEN"
```

- [ ] ✅ Dashboard API works

```bash
# Get Items
curl -X GET https://your-app.vercel.app/api/items \
  -H "Authorization: Bearer TOKEN"
```

- [ ] ✅ Items API works

```bash
# Create Category
curl -X POST https://your-app.vercel.app/api/categories \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test Category","description":"Test"}'
```

- [ ] ✅ Create works

#### Check Logs
```bash
vercel logs your-app.vercel.app --follow
```

- [ ] ✅ No critical errors
- [ ] ✅ Database queries executing
- [ ] ✅ No connection errors

---

### 11. Performance Testing

#### Load Time
- [ ] ✅ Home page loads < 3s
- [ ] ✅ API responses < 1s
- [ ] ✅ Dashboard loads < 2s

#### Database Performance
- [ ] ✅ Query execution time acceptable
- [ ] ✅ No timeout errors
- [ ] ✅ Connection pooling working

#### Cold Start
- [ ] ✅ First request after idle < 5s
- [ ] ✅ Subsequent requests fast

---

### 12. Security Verification

- [ ] ✅ APP_DEBUG=false in production
- [ ] ✅ SSL enabled (https)
- [ ] ✅ Database SSL mode set to 'require'
- [ ] ✅ CSRF protection enabled
- [ ] ✅ Sanctum authentication working
- [ ] ✅ No sensitive data in logs
- [ ] ✅ Environment variables not exposed
- [ ] ✅ .env not in repository

---

### 13. Backup & Monitoring

#### Backup
- [ ] ✅ Created initial backup
- [ ] ✅ Tested restore process
- [ ] ✅ Scheduled regular backups

```bash
# Backup command
pg_dump "postgres://default:xxx@xxx.postgres.vercel-storage.com:5432/verceldb?sslmode=require" > backup-$(date +%Y%m%d).sql
```

#### Monitoring
- [ ] ✅ Enabled Vercel Analytics
- [ ] ✅ Setup error tracking (Sentry/Bugsnag)
- [ ] ✅ Monitor database size
- [ ] ✅ Monitor API usage

---

### 14. Documentation

- [ ] ✅ Updated README.md
- [ ] ✅ Created MYSQL_TO_POSTGRES_MIGRATION.md
- [ ] ✅ Created VERCEL_DEPLOYMENT_GUIDE.md
- [ ] ✅ Created test-postgres-connection.php
- [ ] ✅ Updated API_DOCUMENTATION.md
- [ ] ✅ Team informed about new deployment

---

### 15. Post-Migration Tasks

- [ ] ✅ MySQL database backed up
- [ ] ✅ MySQL server can be decommissioned (after verification period)
- [ ] ✅ DNS updated (if custom domain)
- [ ] ✅ Old deployment archived
- [ ] ✅ Monitoring set up
- [ ] ✅ Team trained on new environment

---

## 🎯 Success Criteria

All checkboxes above should be checked before considering migration complete.

### Critical Checks:
1. ✅ Application loads without errors
2. ✅ All CRUD operations work
3. ✅ Search functionality works correctly
4. ✅ API authentication works
5. ✅ Stock auto-update works
6. ✅ No data loss
7. ✅ Performance acceptable
8. ✅ Security measures in place

---

## 📞 Rollback Plan

If issues occur:

1. **Revert to MySQL:**
   ```bash
   # Update .env
   DB_CONNECTION=mysql
   
   # Restart application
   php artisan config:clear
   ```

2. **Restore from Backup:**
   ```bash
   # MySQL
   mysql -u root -p puskesmas_stok < backup.sql
   
   # PostgreSQL
   psql DATABASE_URL < backup.sql
   ```

3. **Emergency Contact:**
   - Vercel Support: https://vercel.com/support
   - Laravel Discord: https://discord.gg/laravel

---

## ✅ Migration Complete!

Date completed: __________________

Completed by: __________________

Verification by: __________________

**Status: Production Ready** ✨

---

**Next maintenance:**
- Weekly: Check logs and performance
- Monthly: Backup verification
- Quarterly: Security audit
