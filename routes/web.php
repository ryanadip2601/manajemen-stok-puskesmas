<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\StockOutController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Guest Routes (Login, Register, Forgot Password)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
    
    // Forgot Password
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetCode'])->name('password.send');
    Route::get('/verify-code', [AuthController::class, 'showVerifyCode'])->name('password.verify');
    Route::post('/verify-code', [AuthController::class, 'verifyCode'])->name('password.verify.post');
    Route::get('/reset-password', [AuthController::class, 'showResetPassword'])->name('password.reset.form');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Profile & Change Password
    Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [AuthController::class, 'changePassword'])->name('profile.password');
    
    // Dashboard - Semua user bisa akses
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Admin Only Routes - HARUS SEBELUM route dengan parameter
    Route::middleware('admin')->group(function () {
        // Categories - Full CRUD
        Route::resource('categories', CategoryController::class);
        
        // Items - Create, Store (SEBELUM {item})
        Route::get('/items/create', [ItemController::class, 'create'])->name('items.create');
        Route::post('/items', [ItemController::class, 'store'])->name('items.store');
        Route::get('/items/{item}/edit', [ItemController::class, 'edit'])->name('items.edit');
        Route::put('/items/{item}', [ItemController::class, 'update'])->name('items.update');
        Route::delete('/items/{item}', [ItemController::class, 'destroy'])->name('items.destroy');
        
        // Stock In/Out - Full CRUD
        Route::resource('stock-in', StockInController::class);
        Route::resource('stock-out', StockOutController::class);
        
        // Scan Barcode - Admin only
        Route::get('/scan', [ItemController::class, 'scanBarcode'])->name('items.scan');
        Route::post('/scan/search', [ItemController::class, 'searchByBarcode'])->name('items.scan.search');

        // Reports
        Route::get('/reports/monthly', [ReportController::class, 'monthly'])->name('reports.monthly');
        Route::get('/reports/monthly/export', [ReportController::class, 'exportMonthlyCSV'])->name('reports.monthly.export');
        Route::get('/reports/yearly', [ReportController::class, 'yearly'])->name('reports.yearly');
        Route::get('/reports/yearly/export', [ReportController::class, 'exportYearlyCSV'])->name('reports.yearly.export');
    });
    
    // Items - Semua user bisa lihat (SETELAH route spesifik)
    Route::get('/items', [ItemController::class, 'index'])->name('items.index');
    Route::get('/items/{item}', [ItemController::class, 'show'])->name('items.show');
});
