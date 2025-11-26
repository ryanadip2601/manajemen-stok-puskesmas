# 🔄 MySQL to PostgreSQL Migration Guide

## Perbedaan Utama MySQL vs PostgreSQL

### 1. **Auto Increment**
**MySQL:**
```php
$table->id(); // BIGINT UNSIGNED AUTO_INCREMENT
$table->bigIncrements('id');
```

**PostgreSQL:**
```php
$table->id(); // BIGSERIAL (sudah otomatis di Laravel)
$table->bigIncrements('id'); // BIGSERIAL
```

✅ **Laravel Eloquent otomatis handle ini**, tidak perlu perubahan syntax!

---

### 2. **ENUM Type**
**MySQL:**
```php
$table->enum('role', ['admin', 'staff']);
```

**PostgreSQL:**
Laravel akan otomatis convert ke:
```sql
-- Jadi VARCHAR dengan CHECK constraint
```

✅ **Laravel handle otomatis**, tapi untuk native PostgreSQL bisa gunakan:
```php
// Option 1: Tetap gunakan enum (Laravel convert otomatis)
$table->enum('role', ['admin', 'staff']);

// Option 2: Gunakan string dengan constraint
$table->string('role')->default('staff');
DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('admin', 'staff'))");
```

---

### 3. **Timestamp Default**
**MySQL:**
```php
$table->timestamp('created_at')->useCurrent();
```

**PostgreSQL:**
```php
$table->timestamp('created_at')->useCurrent(); // ✅ Same
// Atau
$table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
```

✅ **Tidak perlu perubahan!**

---

### 4. **Foreign Keys**
**MySQL:**
```php
$table->foreignId('user_id')->constrained()->onDelete('cascade');
```

**PostgreSQL:**
```php
$table->foreignId('user_id')->constrained()->onDelete('cascade'); // ✅ Same
```

✅ **Tidak perlu perubahan!**

---

### 5. **String vs Text**
**MySQL & PostgreSQL:**
- `VARCHAR(n)` → `$table->string('name', n)`
- `TEXT` → `$table->text('description')`

✅ **Sama, tidak perlu perubahan!**

---

### 6. **Boolean Type**
**MySQL:**
```php
$table->boolean('is_active'); // Jadi TINYINT(1)
```

**PostgreSQL:**
```php
$table->boolean('is_active'); // Jadi BOOLEAN native
```

✅ **Laravel handle otomatis!**

---

### 7. **Case Sensitivity**
**MySQL:**
- Default case-insensitive untuk string comparison
- `LIKE` case-insensitive

**PostgreSQL:**
- Default case-sensitive
- `LIKE` case-sensitive
- `ILIKE` case-insensitive

**Perubahan di Query:**
```php
// MySQL
User::where('name', 'LIKE', "%{$search}%")->get();

// PostgreSQL (case-insensitive)
User::where('name', 'ILIKE', "%{$search}%")->get();
```

---

### 8. **LIMIT & OFFSET**
**MySQL:**
```sql
SELECT * FROM users LIMIT 10 OFFSET 20;
```

**PostgreSQL:**
```sql
SELECT * FROM users LIMIT 10 OFFSET 20; -- ✅ Same
```

✅ **Eloquent handle otomatis dengan paginate()!**

---

### 9. **JSON Type**
**MySQL:**
```php
$table->json('data');
```

**PostgreSQL:**
```php
$table->json('data'); // Atau $table->jsonb('data') untuk binary JSON
```

✅ **PostgreSQL punya JSONB (binary) yang lebih cepat!**

---

### 10. **Unsigned Integers**
**MySQL:**
```php
$table->unsignedBigInteger('user_id');
```

**PostgreSQL:**
```php
$table->unsignedBigInteger('user_id'); // Laravel simulate dengan CHECK constraint
// Atau langsung
$table->bigInteger('user_id'); // PostgreSQL tidak ada UNSIGNED native
```

⚠️ **PostgreSQL tidak punya UNSIGNED, tapi Laravel handle dengan CHECK constraint.**

---

## 📋 Checklist Migration

### ✅ Tidak Perlu Diubah:
- [x] `$table->id()` / `bigIncrements()` → Auto convert ke BIGSERIAL
- [x] `$table->timestamps()` → Auto handle
- [x] `$table->foreignId()->constrained()`
- [x] `$table->string()`, `$table->text()`
- [x] `$table->integer()`, `$table->bigInteger()`
- [x] `$table->date()`, `$table->datetime()`
- [x] `$table->boolean()`
- [x] `$table->enum()` → Laravel convert otomatis

### ⚠️ Perlu Diubah:
- [ ] Query `LIKE` → Ubah ke `ILIKE` untuk case-insensitive search
- [ ] Custom raw SQL queries yang MySQL-specific
- [ ] `DB_CONNECTION=mysql` → `DB_CONNECTION=pgsql` di .env

---

## 🔧 Perubahan Konfigurasi

### 1. **Database Connection**
**config/database.php:**
```php
'default' => env('DB_CONNECTION', 'pgsql'), // Ubah dari mysql

'connections' => [
    'pgsql' => [
        'driver' => 'pgsql',
        'url' => env('DATABASE_URL'),
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '5432'),
        'database' => env('DB_DATABASE', 'forge'),
        'username' => env('DB_USERNAME', 'forge'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
        'search_path' => 'public',
        'sslmode' => 'prefer',
    ],
],
```

### 2. **.env untuk Local Development**
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=puskesmas_stok
DB_USERNAME=postgres
DB_PASSWORD=yourpassword
```

### 3. **.env untuk Vercel (Production)**
```env
DB_CONNECTION=pgsql
DATABASE_URL="${POSTGRES_URL}"
```

---

## 🚀 Vercel Postgres Connection

### Format Connection String:
```
postgres://user:password@host:port/database?sslmode=require
```

### Environment Variables Vercel:
```env
POSTGRES_URL="postgres://default:xxx@xxx.postgres.vercel-storage.com:5432/verceldb"
POSTGRES_PRISMA_URL="postgres://default:xxx@xxx.postgres.vercel-storage.com:5432/verceldb?pgbouncer=true"
POSTGRES_URL_NON_POOLING="postgres://default:xxx@xxx.postgres.vercel-storage.com:5432/verceldb"
POSTGRES_USER="default"
POSTGRES_HOST="xxx.postgres.vercel-storage.com"
POSTGRES_PASSWORD="xxx"
POSTGRES_DATABASE="verceldb"
```

Laravel akan menggunakan `DATABASE_URL` atau individual credentials.

---

## 🔍 Query Changes untuk PostgreSQL

### 1. **Case-Insensitive Search**
**Sebelum (MySQL):**
```php
Item::where('name', 'LIKE', "%{$search}%")->get();
```

**Sesudah (PostgreSQL):**
```php
Item::where('name', 'ILIKE', "%{$search}%")->get();
```

### 2. **Multiple Column Search**
**Sebelum:**
```php
Item::where('name', 'LIKE', "%{$search}%")
    ->orWhere('code', 'LIKE', "%{$search}%")
    ->get();
```

**Sesudah:**
```php
Item::where('name', 'ILIKE', "%{$search}%")
    ->orWhere('code', 'ILIKE', "%{$search}%")
    ->get();
```

### 3. **Boolean Comparison**
**MySQL & PostgreSQL (Same):**
```php
User::where('is_active', true)->get();
User::where('is_active', 1)->get(); // Works di keduanya
```

---

## ⚙️ PHP Extensions Required

### Install PostgreSQL Driver:
```bash
# Ubuntu/Debian
sudo apt-get install php-pgsql

# macOS (Homebrew)
brew install php-pgsql

# Windows
# Enable di php.ini:
extension=pdo_pgsql
extension=pgsql
```

### Cek Extension:
```bash
php -m | grep pgsql
```

---

## 🧪 Testing Connection

### Artisan Command:
```bash
php artisan migrate:status
php artisan db:show
```

### Test Script:
```php
// routes/web.php
Route::get('/test-db', function () {
    try {
        DB::connection()->getPDO();
        return "Database connection: " . DB::connection()->getDatabaseName();
    } catch (\Exception $e) {
        return "Could not connect to the database. Error: " . $e->getMessage();
    }
});
```

---

## 📊 Performance Tips

### 1. **Indexing**
PostgreSQL sangat benefit dari proper indexing:
```php
$table->index('column_name');
$table->index(['column1', 'column2']); // Composite index
```

### 2. **JSONB vs JSON**
Gunakan `jsonb` untuk performa lebih baik:
```php
$table->jsonb('metadata'); // Binary JSON, lebih cepat untuk query
```

### 3. **Connection Pooling**
Vercel Postgres menggunakan connection pooling (PgBouncer):
- Gunakan `POSTGRES_PRISMA_URL` untuk pooling
- Max connections limited

---

## ✅ Summary

| Feature | MySQL | PostgreSQL | Laravel Handle? |
|---------|-------|-----------|-----------------|
| Auto Increment | AUTO_INCREMENT | SERIAL/BIGSERIAL | ✅ Yes |
| ENUM | ENUM | VARCHAR + CHECK | ✅ Yes |
| Unsigned | UNSIGNED INT | CHECK constraint | ✅ Yes |
| Boolean | TINYINT(1) | BOOLEAN | ✅ Yes |
| Foreign Keys | FOREIGN KEY | FOREIGN KEY | ✅ Yes |
| Timestamps | TIMESTAMP | TIMESTAMP | ✅ Yes |
| LIKE search | LIKE (case-insensitive) | ILIKE | ⚠️ Manual change |
| JSON | JSON | JSON/JSONB | ✅ Yes |

**Kesimpulan:** Laravel Eloquent sudah handle 95% perbedaan! Yang perlu diubah manual hanya:
1. ✏️ `LIKE` → `ILIKE` di queries
2. ✏️ Config `DB_CONNECTION=pgsql`
3. ✏️ Install PHP pgsql extension
