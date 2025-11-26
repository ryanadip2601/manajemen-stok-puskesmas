<?php

/**
 * Vercel Serverless Entry Point
 */

define('LARAVEL_START', microtime(true));

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure storage directories exist BEFORE loading Laravel
$storagePaths = [
    '/tmp/bootstrap/cache',
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
];

foreach ($storagePaths as $path) {
    if (!is_dir($path)) {
        @mkdir($path, 0755, true);
    }
}

// Create SQLite database if needed
$dbPath = '/tmp/database.sqlite';
if (!file_exists($dbPath)) {
    @touch($dbPath);
}

// Set environment variables for Laravel paths
$_ENV['APP_SERVICES_CACHE'] = '/tmp/bootstrap/cache/services.php';
$_ENV['APP_PACKAGES_CACHE'] = '/tmp/bootstrap/cache/packages.php';
$_ENV['APP_CONFIG_CACHE'] = '/tmp/bootstrap/cache/config.php';
$_ENV['APP_ROUTES_CACHE'] = '/tmp/bootstrap/cache/routes.php';
$_ENV['APP_EVENTS_CACHE'] = '/tmp/bootstrap/cache/events.php';

try {
    // Register Composer autoloader
    require __DIR__ . '/../vendor/autoload.php';

    // Bootstrap Laravel
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    
    // Override storage path
    $app->useStoragePath('/tmp/storage');

    // Handle The Request
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

    $response = $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    );

    $response->send();

    $kernel->terminate($request, $response);

} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => explode("\n", $e->getTraceAsString())
    ]);
}
