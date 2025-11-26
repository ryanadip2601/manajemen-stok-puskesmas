<?php

/**
 * Vercel Serverless Entry Point
 */

define('LARAVEL_START', microtime(true));

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Register Composer autoloader
    require __DIR__ . '/../vendor/autoload.php';

    // Bootstrap Laravel
    $app = require_once __DIR__ . '/../bootstrap/app.php';

    // Ensure storage directories exist
    $storagePaths = [
        '/tmp/views',
        '/tmp/cache',
        '/tmp/sessions',
        '/tmp/framework/views',
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
