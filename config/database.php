<?php

use Illuminate\Support\Str;

// Parse DATABASE_URL untuk Railway/Heroku
$databaseUrl = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? getenv('DATABASE_URL') ?? env('DATABASE_URL');

$pgHost = '127.0.0.1';
$pgPort = '5432';
$pgDatabase = 'railway';
$pgUsername = 'postgres';
$pgPassword = '';

if (!empty($databaseUrl)) {
    $parsed = parse_url($databaseUrl);
    if ($parsed !== false) {
        $pgHost = $parsed['host'] ?? $pgHost;
        $pgPort = (string)($parsed['port'] ?? $pgPort);
        $pgDatabase = isset($parsed['path']) ? ltrim($parsed['path'], '/') : $pgDatabase;
        $pgUsername = $parsed['user'] ?? $pgUsername;
        $pgPassword = isset($parsed['pass']) ? urldecode($parsed['pass']) : $pgPassword;
    }
}

// Fallback ke env variables jika DATABASE_URL tidak ada
if (empty($databaseUrl)) {
    $pgHost = env('DB_HOST', $pgHost);
    $pgPort = env('DB_PORT', $pgPort);
    $pgDatabase = env('DB_DATABASE', $pgDatabase);
    $pgUsername = env('DB_USERNAME', $pgUsername);
    $pgPassword = env('DB_PASSWORD', $pgPassword);
}

// Determine default connection
$defaultConnection = $_ENV['DB_CONNECTION'] ?? $_SERVER['DB_CONNECTION'] ?? getenv('DB_CONNECTION') ?? env('DB_CONNECTION', 'sqlite');

return [
    'default' => $defaultConnection,

    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => database_path('database.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],

        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'puskesmas_stock'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'host' => $pgHost,
            'port' => $pgPort,
            'database' => $pgDatabase,
            'username' => $pgUsername,
            'password' => $pgPassword,
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],
    ],

    'migrations' => 'migrations',
];
