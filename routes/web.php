<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\StockOutController;
use App\Http\Controllers\StockOutRequestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserMonitorController;
use App\Http\Controllers\RuanganController;
use App\Http\Controllers\KategoriAsetController;
use App\Http\Controllers\BarangAsetController;
use App\Http\Controllers\PemeliharaanAsetController;
use App\Http\Controllers\PenyusutanController;
use App\Http\Controllers\UsulanPenerimaanController;
use App\Http\Controllers\UsulanPermintaanController;
use App\Http\Controllers\PermintaanRestokController;
use App\Http\Controllers\PermintaanBarangController;
use App\Http\Controllers\LaporanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// ================== ROUTE SEMENTARA UNTUK SETUP (HAPUS SETELAH SELESAI!) ==================
Route::get('/run-setup/{key}', function ($key) {
    // Ganti 'rahasia123' dengan kata sandi Anda sendiri
    if ($key !== 'rahasia123') {
        abort(404);
    }
    
    $output = [];
    
    // 1. Jalankan migrate
    \Artisan::call('migrate', ['--force' => true]);
    $output[] = '✅ Migration: ' . \Artisan::output();
    
    // 2. Clear config cache
    \Artisan::call('config:clear');
    $output[] = '✅ Config Clear: ' . \Artisan::output();
    
    // 3. Clear route cache
    \Artisan::call('route:clear');
    $output[] = '✅ Route Clear: ' . \Artisan::output();
    
    // 4. Clear view cache
    \Artisan::call('view:clear');
    $output[] = '✅ View Clear: ' . \Artisan::output();
    
    // 5. Create storage link (jika belum ada)
    try {
        \Artisan::call('storage:link');
        $output[] = '✅ Storage Link: ' . \Artisan::output();
    } catch (\Throwable $e) {
        $output[] = '⚠️ Storage Link: Skipped - ' . $e->getMessage();
    }
    
    return '<html><head><title>Setup Complete</title></head><body style="font-family:monospace;padding:20px;background:#1a1a2e;color:#0f0;">
        <h1>🚀 Setup Berhasil!</h1>
        <pre>' . implode("\n", $output) . '</pre>
        <hr>
        <p style="color:red;font-weight:bold;">⚠️ PENTING: Hapus route ini dari routes/web.php setelah selesai!</p>
        <p><a href="/" style="color:#0ff;">← Kembali ke Website</a></p>
    </body></html>';
});
// ================== AKHIR ROUTE SEMENTARA ==================

// Guest Routes (Login, Register, Forgot Password)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
    
    // Forgot Password via Email
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.send');
    Route::get('/reset-password', [AuthController::class, 'showResetPassword'])->name('password.reset.form');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Profile & Change Password - Semua user
    Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [AuthController::class, 'changePassword'])->name('profile.password');
    
    // Dashboard - Semua user bisa akses
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Items - Semua user bisa lihat daftar
    Route::get('/items', [ItemController::class, 'index'])->name('items.index');

    // ==================== ADMIN ONLY ROUTES ====================
    Route::middleware('admin')->group(function () {
        // User Approval Management
        Route::get('/users/pending', [AuthController::class, 'pendingUsers'])->name('users.pending');
        Route::post('/users/{id}/approve', [AuthController::class, 'approveUser'])->name('users.approve');
        Route::post('/users/{id}/reject', [AuthController::class, 'rejectUser'])->name('users.reject');

        // User Management & History
        Route::get('/users', [UserMonitorController::class, 'index'])->name('users.index');
        Route::get('/users/login-history', [UserMonitorController::class, 'loginHistory'])->name('users.login-history');
        Route::delete('/users/login-history/clear', [UserMonitorController::class, 'clearLoginHistory'])->name('users.login-history.clear');
        Route::get('/users/activity-logs', [UserMonitorController::class, 'activityLogs'])->name('users.activity-logs');
        Route::delete('/users/activity-logs/clear', [UserMonitorController::class, 'clearActivityLogs'])->name('users.activity-logs.clear');
        Route::get('/users/{id}', [UserMonitorController::class, 'show'])->name('users.show');
        Route::get('/users/{id}/edit-role', [UserMonitorController::class, 'editRole'])->name('users.edit-role');
        Route::put('/users/{id}/role', [UserMonitorController::class, 'updateRole'])->name('users.update-role');
        Route::delete('/users/{id}', [UserMonitorController::class, 'destroy'])->name('users.destroy');

        // Reports - Hanya Admin
        Route::get('/reports', [LaporanController::class, 'index'])->name('reports.index');
        Route::get('/reports/monthly', [ReportController::class, 'monthly'])->name('reports.monthly');
        Route::get('/reports/monthly/export', [ReportController::class, 'exportMonthlyCSV'])->name('reports.monthly.export');
        Route::get('/reports/yearly', [ReportController::class, 'yearly'])->name('reports.yearly');
        Route::get('/reports/yearly/export', [ReportController::class, 'exportYearlyCSV'])->name('reports.yearly.export');
        Route::get('/reports/barang-masuk', [LaporanController::class, 'barangMasuk'])->name('reports.barang-masuk');
        Route::get('/reports/barang-masuk/export', [LaporanController::class, 'exportBarangMasuk'])->name('reports.barang-masuk.export');
        Route::get('/reports/barang-keluar', [LaporanController::class, 'barangKeluar'])->name('reports.barang-keluar');
        Route::get('/reports/barang-keluar/export', [LaporanController::class, 'exportBarangKeluar'])->name('reports.barang-keluar.export');
        Route::get('/reports/pengambilan-barang', [LaporanController::class, 'pengambilanBarang'])->name('reports.pengambilan-barang');
        Route::get('/reports/pengambilan-barang/export', [LaporanController::class, 'exportPengambilanBarang'])->name('reports.pengambilan-barang.export');
        Route::get('/reports/permintaan-bhp', [LaporanController::class, 'permintaanBhp'])->name('reports.permintaan-bhp');
        Route::get('/reports/permintaan-bhp/export', [LaporanController::class, 'exportPermintaanBhp'])->name('reports.permintaan-bhp.export');
        Route::get('/reports/usulan-aset', [LaporanController::class, 'usulanAset'])->name('reports.usulan-aset');
        Route::get('/reports/usulan-aset/export', [LaporanController::class, 'exportUsulanAset'])->name('reports.usulan-aset.export');
        Route::get('/reports/pemeliharaan', [LaporanController::class, 'pemeliharaan'])->name('reports.pemeliharaan');
        Route::get('/reports/pemeliharaan/export', [LaporanController::class, 'exportPemeliharaan'])->name('reports.pemeliharaan.export');
    });

    // ==================== WAREHOUSE ROUTES ====================
    // Admin dan Admin Gudang bisa mengakses
    Route::middleware('warehouse')->group(function () {
        // Categories - Full CRUD
        Route::resource('categories', CategoryController::class);
        
        // Items - CRUD (Create, Edit, Update, Delete)
        Route::get('/items/clear-filter', [ItemController::class, 'clearFilter'])->name('items.clear-filter');
        Route::get('/items/import', [ItemController::class, 'showImport'])->name('items.import');
        Route::post('/items/import', [ItemController::class, 'import'])->name('items.import.store');
        Route::get('/items/create', [ItemController::class, 'create'])->name('items.create');
        Route::post('/items', [ItemController::class, 'store'])->name('items.store');
        Route::get('/items/{item}', [ItemController::class, 'show'])->name('items.show');
        Route::get('/items/{item}/edit', [ItemController::class, 'edit'])->name('items.edit');
        Route::put('/items/{item}', [ItemController::class, 'update'])->name('items.update');
        Route::delete('/items/{item}', [ItemController::class, 'destroy'])->name('items.destroy');
        
        // Stock In - Full CRUD (Barang Masuk)
        Route::get('/stock-in/clear-filter', [StockInController::class, 'clearFilter'])->name('stock-in.clear-filter');
        Route::post('/stock-in/batch', [StockInController::class, 'storeBatch'])->name('stock-in.store-batch');
        Route::resource('stock-in', StockInController::class);
        
        // Stock Out - Full CRUD untuk Admin & Admin Gudang
        Route::get('/stock-out/clear-filter', [StockOutController::class, 'clearFilter'])->name('stock-out.clear-filter');
        Route::post('/stock-out/batch', [StockOutController::class, 'storeBatch'])->name('stock-out.store-batch');
        Route::get('/stock-out', [StockOutController::class, 'index'])->name('stock-out.index');
        Route::get('/stock-out/create', [StockOutController::class, 'create'])->name('stock-out.create');
        Route::post('/stock-out', [StockOutController::class, 'store'])->name('stock-out.store');
        Route::get('/stock-out/{stock_out}', [StockOutController::class, 'show'])->name('stock-out.show');
        Route::get('/stock-out/{stock_out}/edit', [StockOutController::class, 'edit'])->name('stock-out.edit');
        Route::put('/stock-out/{stock_out}', [StockOutController::class, 'update'])->name('stock-out.update');
        Route::delete('/stock-out/{stock_out}', [StockOutController::class, 'destroy'])->name('stock-out.destroy');
        
        // Scan Barcode
        Route::get('/scan', [ItemController::class, 'scanBarcode'])->name('items.scan');
        Route::post('/scan/search', [ItemController::class, 'searchByBarcode'])->name('items.scan.search');

        // ==================== CHECKLIST PERMINTAAN PENGAMBILAN ====================
        Route::get('/checklist', [StockOutRequestController::class, 'checklistIndex'])->name('checklist.index');
        Route::post('/checklist/{id}/approve', [StockOutRequestController::class, 'approve'])->name('checklist.approve');
        Route::post('/checklist/{id}/reject', [StockOutRequestController::class, 'reject'])->name('checklist.reject');
        Route::post('/checklist/{id}/complete', [StockOutRequestController::class, 'complete'])->name('checklist.complete');
        Route::post('/checklist/{id}/undo', [StockOutRequestController::class, 'undoApproval'])->name('checklist.undo');
        
        // API untuk notifikasi permintaan baru
        Route::get('/api/pending-requests', [StockOutRequestController::class, 'getPendingCount'])->name('api.pending-requests');

        // ==================== MODUL ASET PER RUANGAN ====================
        // Ruangan
        Route::resource('ruangan', RuanganController::class);
        
        // Kategori Aset
        Route::resource('kategori-aset', KategoriAsetController::class)->except(['show']);
        
        // Barang Aset
        Route::get('/barang-aset/export', [BarangAsetController::class, 'exportExcel'])->name('barang-aset.export');
        Route::get('/barang-aset/generate-kode', [BarangAsetController::class, 'generateKode'])->name('barang-aset.generate-kode');
        Route::get('/barang-aset/clear-filter', [BarangAsetController::class, 'clearFilter'])->name('barang-aset.clear-filter');
        Route::resource('barang-aset', BarangAsetController::class);

        // ==================== MODUL PEMELIHARAAN ASET ====================
        Route::get('/pemeliharaan/clear-filter', [PemeliharaanAsetController::class, 'clearFilter'])->name('pemeliharaan.clear-filter');
        Route::get('/pemeliharaan/export', [PemeliharaanAsetController::class, 'exportExcel'])->name('pemeliharaan.export');
        Route::get('/pemeliharaan/ruangan/{ruangan}', [PemeliharaanAsetController::class, 'riwayatRuangan'])->name('pemeliharaan.riwayat-ruangan');
        Route::get('/pemeliharaan/aset/{aset}', [PemeliharaanAsetController::class, 'riwayatAset'])->name('pemeliharaan.riwayat-aset');
        Route::get('/pemeliharaan/get-aset/{ruangan}', [PemeliharaanAsetController::class, 'getAsetByRuangan'])->name('pemeliharaan.get-aset');
        Route::post('/pemeliharaan/{id}/status', [PemeliharaanAsetController::class, 'updateStatus'])->name('pemeliharaan.update-status');
        Route::resource('pemeliharaan', PemeliharaanAsetController::class);

        // ==================== MODUL PENYUSUTAN ASET ====================
        Route::get('/penyusutan', [PenyusutanController::class, 'index'])->name('penyusutan.index');
        Route::post('/penyusutan/proses', [PenyusutanController::class, 'prosesTahunan'])->name('penyusutan.proses');
        Route::get('/penyusutan/aset/{id}', [PenyusutanController::class, 'hitungAset'])->name('penyusutan.aset');
        Route::get('/penyusutan/kartu/{id}', [PenyusutanController::class, 'kartuAset'])->name('penyusutan.kartu');
        Route::get('/penyusutan/laporan/golongan', [PenyusutanController::class, 'laporanGolongan'])->name('penyusutan.laporan-golongan');
        Route::get('/penyusutan/laporan/tahun-perolehan', [PenyusutanController::class, 'laporanTahunPerolehan'])->name('penyusutan.laporan-tahun-perolehan');
        Route::get('/penyusutan/laporan/detail', [PenyusutanController::class, 'laporanDetail'])->name('penyusutan.laporan-detail');
        Route::get('/penyusutan/export', [PenyusutanController::class, 'exportCSV'])->name('penyusutan.export');
        
        // API Penyusutan
        Route::get('/api/penyusutan/aset/{id}', [PenyusutanController::class, 'apiHitungAset'])->name('api.penyusutan.aset');
        Route::get('/api/penyusutan/summary', [PenyusutanController::class, 'apiSummary'])->name('api.penyusutan.summary');
    });

    // ==================== STAFF ROUTES ====================
    // Pegawai - Permintaan Pengambilan Barang
    Route::get('/pengambilan-barang', [StockOutRequestController::class, 'staffIndex'])->name('stock-request.staff.index');
    Route::get('/pengambilan-barang/ajukan', [StockOutRequestController::class, 'staffCreate'])->name('stock-request.staff.create');
    Route::post('/pengambilan-barang', [StockOutRequestController::class, 'staffStore'])->name('stock-request.staff.store');
    Route::post('/pengambilan-barang/{id}/cancel', [StockOutRequestController::class, 'staffCancel'])->name('stock-request.staff.cancel');

    // ==================== STAFF - BARANG ASET & PEMELIHARAAN ====================
    // Staff bisa melihat daftar aset (read-only) dan mengajukan pemeliharaan
    Route::middleware('staff.aset')->group(function () {
        // Barang Aset - View Only untuk Staff
        Route::get('/staff/barang-aset', [BarangAsetController::class, 'index'])->name('staff.barang-aset.index');
        Route::get('/staff/barang-aset/{id}', [BarangAsetController::class, 'show'])->name('staff.barang-aset.show');
        
        // Pemeliharaan - Staff bisa lihat dan ajukan
        Route::get('/staff/pemeliharaan', [PemeliharaanAsetController::class, 'index'])->name('staff.pemeliharaan.index');
        Route::get('/staff/pemeliharaan/create', [PemeliharaanAsetController::class, 'create'])->name('staff.pemeliharaan.create');
        Route::get('/staff/pemeliharaan/get-aset/{ruangan}', [PemeliharaanAsetController::class, 'getAsetByRuangan'])->name('staff.pemeliharaan.get-aset');
        Route::post('/staff/pemeliharaan', [PemeliharaanAsetController::class, 'store'])->name('staff.pemeliharaan.store');
        Route::get('/staff/pemeliharaan/{id}', [PemeliharaanAsetController::class, 'show'])->name('staff.pemeliharaan.show');
    });

    // ==================== USULAN PENERIMAAN BARANG ====================
    // Semua user yang login bisa mengajukan usulan
    Route::get('/usulan-penerimaan', [UsulanPenerimaanController::class, 'index'])->name('usulan-penerimaan.index');
    Route::get('/usulan-penerimaan/create', [UsulanPenerimaanController::class, 'create'])->name('usulan-penerimaan.create');
    Route::post('/usulan-penerimaan', [UsulanPenerimaanController::class, 'store'])->name('usulan-penerimaan.store');
    Route::get('/usulan-penerimaan/{id}', [UsulanPenerimaanController::class, 'show'])->name('usulan-penerimaan.show');
    Route::get('/usulan-penerimaan/{id}/edit', [UsulanPenerimaanController::class, 'edit'])->name('usulan-penerimaan.edit');
    Route::put('/usulan-penerimaan/{id}', [UsulanPenerimaanController::class, 'update'])->name('usulan-penerimaan.update');
    Route::delete('/usulan-penerimaan/{id}/cancel', [UsulanPenerimaanController::class, 'cancel'])->name('usulan-penerimaan.cancel');
    Route::get('/usulan-penerimaan-export', [UsulanPenerimaanController::class, 'exportUserCSV'])->name('usulan-penerimaan.export');

    // Admin routes for managing usulan penerimaan
    Route::middleware('warehouse')->group(function () {
        Route::get('/admin/usulan-penerimaan', [UsulanPenerimaanController::class, 'adminIndex'])->name('usulan-penerimaan.admin.index');
        Route::get('/admin/usulan-penerimaan/export', [UsulanPenerimaanController::class, 'exportCSV'])->name('usulan-penerimaan.admin.export');
        Route::get('/admin/usulan-penerimaan/{id}', [UsulanPenerimaanController::class, 'adminShow'])->name('usulan-penerimaan.admin.show');
        Route::post('/admin/usulan-penerimaan/{id}/proses', [UsulanPenerimaanController::class, 'proses'])->name('usulan-penerimaan.admin.proses');
        Route::post('/admin/usulan-penerimaan/{id}/approve', [UsulanPenerimaanController::class, 'approve'])->name('usulan-penerimaan.admin.approve');
        Route::post('/admin/usulan-penerimaan/{id}/reject', [UsulanPenerimaanController::class, 'reject'])->name('usulan-penerimaan.admin.reject');
        Route::post('/admin/usulan-penerimaan/{id}/revision', [UsulanPenerimaanController::class, 'requestRevision'])->name('usulan-penerimaan.admin.revision');
        Route::delete('/admin/usulan-penerimaan/{id}', [UsulanPenerimaanController::class, 'adminDestroy'])->name('usulan-penerimaan.admin.destroy');
        Route::get('/api/usulan-penerimaan/pending', [UsulanPenerimaanController::class, 'getPendingCount'])->name('api.usulan-penerimaan.pending');
    });

    // ==================== USULAN PERMINTAAN BARANG HABIS PAKAI (LAMA - TIDAK DIPAKAI) ====================
    // Route lama dipertahankan sementara untuk backward compatibility, redirect ke route baru
    // Route::get('/usulan-permintaan-barang', ...);
    // Route::get('/permintaan-restok', ...);

    // ==================== PERMINTAAN BARANG (GABUNGAN BARU) ====================
    // Semua user yang login bisa mengajukan permintaan barang (baru maupun restok)
    Route::get('/permintaan-barang', [PermintaanBarangController::class, 'index'])->name('permintaan-barang.index');
    Route::get('/permintaan-barang/create', [PermintaanBarangController::class, 'create'])->name('permintaan-barang.create');
    Route::post('/permintaan-barang', [PermintaanBarangController::class, 'store'])->name('permintaan-barang.store');
    Route::get('/permintaan-barang/{id}', [PermintaanBarangController::class, 'show'])->name('permintaan-barang.show');
    Route::get('/permintaan-barang/{id}/edit', [PermintaanBarangController::class, 'edit'])->name('permintaan-barang.edit');
    Route::put('/permintaan-barang/{id}', [PermintaanBarangController::class, 'update'])->name('permintaan-barang.update');
    Route::delete('/permintaan-barang/{id}/cancel', [PermintaanBarangController::class, 'cancel'])->name('permintaan-barang.cancel');

    // API untuk pencarian barang (AJAX)
    Route::get('/api/permintaan-barang/search-items', [PermintaanBarangController::class, 'searchItems'])->name('api.permintaan-barang.search-items');

    // API untuk popup notifikasi restok
    Route::get('/api/permintaan-barang/notifications', [PermintaanBarangController::class, 'getPopupNotifications'])->name('api.permintaan-barang.notifications');
    Route::post('/api/permintaan-barang/mark-read', [PermintaanBarangController::class, 'markAsRead'])->name('api.permintaan-barang.mark-read');

    // Admin routes for managing permintaan barang
    Route::middleware('warehouse')->group(function () {
        Route::get('/admin/permintaan-barang', [PermintaanBarangController::class, 'adminIndex'])->name('permintaan-barang.admin.index');
        Route::get('/admin/permintaan-barang/{id}', [PermintaanBarangController::class, 'adminShow'])->name('permintaan-barang.admin.show');
        Route::post('/admin/permintaan-barang/{id}/proses', [PermintaanBarangController::class, 'proses'])->name('permintaan-barang.admin.proses');
        Route::post('/admin/permintaan-barang/{id}/approve', [PermintaanBarangController::class, 'approve'])->name('permintaan-barang.admin.approve');
        Route::post('/admin/permintaan-barang/{id}/reject', [PermintaanBarangController::class, 'reject'])->name('permintaan-barang.admin.reject');
        Route::post('/admin/permintaan-barang/{id}/revision', [PermintaanBarangController::class, 'requestRevision'])->name('permintaan-barang.admin.revision');
        Route::get('/api/permintaan-barang/pending', [PermintaanBarangController::class, 'getPendingCount'])->name('api.permintaan-barang.pending');
    });
});
