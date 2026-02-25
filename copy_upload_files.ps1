# Script untuk menyalin file yang perlu diupload ke Hostinger
# Jalankan: .\copy_upload_files.ps1

$source = "C:\Users\Ryana\Downloads\Apk Vibe Coding\website barang 1.1"
$dest = "C:\Users\Ryana\Downloads\Apk Vibe Coding\website barang 1.1\upload_to_hostinger"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  MENYALIN FILE UNTUK UPLOAD HOSTINGER" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# Bersihkan folder tujuan
if (Test-Path $dest) {
    Remove-Item -Recurse -Force $dest
}
New-Item -ItemType Directory -Path $dest | Out-Null

# === A. FILE YANG DIMODIFIKASI ===
Write-Host "`n[1/8] Menyalin Controllers..." -ForegroundColor Yellow

# Controllers yang dimodifikasi
$controllers = @(
    "AuthController.php",
    "CategoryController.php",
    "ItemController.php",
    "ReportController.php",
    "StockInController.php",
    "StockOutController.php"
)
New-Item -ItemType Directory -Path "$dest\app\Http\Controllers" -Force | Out-Null
foreach ($file in $controllers) {
    Copy-Item "$source\app\Http\Controllers\$file" "$dest\app\Http\Controllers\" -Force
}

# Controllers baru
$newControllers = @(
    "BarangAsetController.php",
    "KategoriAsetController.php",
    "PemeliharaanAsetController.php",
    "PenyusutanController.php",
    "RuanganController.php",
    "StockOutRequestController.php",
    "UserMonitorController.php",
    "UsulanPenerimaanController.php"
)
foreach ($file in $newControllers) {
    if (Test-Path "$source\app\Http\Controllers\$file") {
        Copy-Item "$source\app\Http\Controllers\$file" "$dest\app\Http\Controllers\" -Force
    }
}

Write-Host "[2/8] Menyalin Middleware..." -ForegroundColor Yellow
New-Item -ItemType Directory -Path "$dest\app\Http\Middleware" -Force | Out-Null
$middlewares = @(
    "StaffAsetAccess.php",
    "UpdateLastActivity.php",
    "WarehouseAccess.php"
)
foreach ($file in $middlewares) {
    if (Test-Path "$source\app\Http\Middleware\$file") {
        Copy-Item "$source\app\Http\Middleware\$file" "$dest\app\Http\Middleware\" -Force
    }
}

# Kernel.php
Copy-Item "$source\app\Http\Kernel.php" "$dest\app\Http\" -Force

Write-Host "[3/8] Menyalin Models..." -ForegroundColor Yellow
New-Item -ItemType Directory -Path "$dest\app\Models" -Force | Out-Null
# Semua models
Get-ChildItem "$source\app\Models\*.php" | ForEach-Object {
    Copy-Item $_.FullName "$dest\app\Models\" -Force
}

Write-Host "[4/8] Menyalin Services..." -ForegroundColor Yellow
if (Test-Path "$source\app\Services") {
    New-Item -ItemType Directory -Path "$dest\app\Services" -Force | Out-Null
    Get-ChildItem "$source\app\Services\*.php" | ForEach-Object {
        Copy-Item $_.FullName "$dest\app\Services\" -Force
    }
}

Write-Host "[5/8] Menyalin Config..." -ForegroundColor Yellow
New-Item -ItemType Directory -Path "$dest\config" -Force | Out-Null
Copy-Item "$source\config\database.php" "$dest\config\" -Force
Copy-Item "$source\config\mail.php" "$dest\config\" -Force

Write-Host "[6/8] Menyalin Migrations..." -ForegroundColor Yellow
New-Item -ItemType Directory -Path "$dest\database\migrations" -Force | Out-Null
Get-ChildItem "$source\database\migrations\*.php" | ForEach-Object {
    Copy-Item $_.FullName "$dest\database\migrations\" -Force
}

# Seeders
New-Item -ItemType Directory -Path "$dest\database\seeders" -Force | Out-Null
Copy-Item "$source\database\seeders\DatabaseSeeder.php" "$dest\database\seeders\" -Force

Write-Host "[7/8] Menyalin Views..." -ForegroundColor Yellow
New-Item -ItemType Directory -Path "$dest\resources\views" -Force | Out-Null

# Copy semua folder views
$viewFolders = @(
    "auth",
    "barang-aset",
    "categories",
    "dashboard",
    "emails",
    "items",
    "kategori-aset",
    "layouts",
    "pemeliharaan",
    "penyusutan",
    "reports",
    "ruangan",
    "stock_in",
    "stock_out",
    "stock_out_requests",
    "users",
    "usulan-penerimaan"
)
foreach ($folder in $viewFolders) {
    if (Test-Path "$source\resources\views\$folder") {
        Copy-Item "$source\resources\views\$folder" "$dest\resources\views\" -Recurse -Force
    }
}

Write-Host "[8/8] Menyalin Routes & Root Files..." -ForegroundColor Yellow
New-Item -ItemType Directory -Path "$dest\routes" -Force | Out-Null
Copy-Item "$source\routes\web.php" "$dest\routes\" -Force

New-Item -ItemType Directory -Path "$dest\public" -Force | Out-Null
if (Test-Path "$source\public\.htaccess") {
    Copy-Item "$source\public\.htaccess" "$dest\public\" -Force
}

# Root files
Copy-Item "$source\composer.json" "$dest\" -Force
Copy-Item "$source\composer.lock" "$dest\" -Force
if (Test-Path "$source\.htaccess") {
    Copy-Item "$source\.htaccess" "$dest\" -Force
}

# Buat file instruksi
$instructions = @"
====================================================
  FILE SIAP UPLOAD KE HOSTINGER
====================================================

CARA UPLOAD:
1. Buka File Manager di hPanel Hostinger
2. Navigasi ke public_html (atau folder aplikasi Anda)
3. Upload semua isi folder ini dengan struktur yang sama
4. TIMPA file yang sudah ada

SETELAH UPLOAD:
1. Jalankan migration untuk tabel baru:
   php artisan migrate --force

2. Clear cache:
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache

3. Jika ada error permission:
   chmod -R 775 storage
   chmod -R 775 bootstrap/cache

STRUKTUR FOLDER INI:
- app/Http/Controllers/  -> Controllers
- app/Http/Middleware/   -> Middleware baru
- app/Models/            -> Models
- app/Services/          -> Services
- config/                -> Config files
- database/migrations/   -> Migration files
- database/seeders/      -> Seeder files
- resources/views/       -> View files
- routes/                -> Route files
- public/                -> Public files
- composer.json          -> Composer config
- composer.lock          -> Composer lock

====================================================
"@

$instructions | Out-File "$dest\README_UPLOAD.txt" -Encoding UTF8

Write-Host "`n========================================" -ForegroundColor Green
Write-Host "  SELESAI!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host "`nFile telah disalin ke:" -ForegroundColor White
Write-Host "$dest" -ForegroundColor Cyan
Write-Host "`nTotal folder: " -NoNewline
(Get-ChildItem $dest -Directory -Recurse).Count
Write-Host "Total file: " -NoNewline
(Get-ChildItem $dest -File -Recurse).Count
Write-Host "`nSilakan upload isi folder tersebut ke Hostinger." -ForegroundColor Yellow
