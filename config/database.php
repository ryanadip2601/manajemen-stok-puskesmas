<?php

use Illuminate\Support\Str;

// Default PostgreSQL settings
$pgHost = env('DB_HOST', '127.0.0.1');
$pgPort = env('DB_PORT', '5432');
$pgDatabase = env('DB_DATABASE', 'railway');
$pgUsername = env('DB_USERNAME', 'postgres');
$pgPassword = env('DB_PASSWORD', '');

// Parse DATABASE_URL untuk Railway/Heroku
$databaseUrl = env('DATABASE_URL');

if (!empty($databaseUrl) && filter_var($databaseUrl, FILTER_VALIDATE_URL)) {
    $parsed = parse_url($databaseUrl);
    if ($parsed !== false && isset($parsed['host'])) {
        $pgHost = $parsed['host'];
        $pgPort = (string)($parsed['port'] ?? '5432');
        $pgDatabase = isset($parsed['path']) ? ltrim($parsed['path'], '/') : $pgDatabase;
        $pgUsername = $parsed['user'] ?? $pgUsername;
        $pgPassword = isset($parsed['pass']) ? urldecode($parsed['pass']) : $pgPassword;
    }
}

// Determine default connection
$defaultConnection = env('DB_CONNECTION', 'sqlite');

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
