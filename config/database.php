<?php

use Illuminate\Support\Str;

// Check for Railway PostgreSQL variables first
$pgHost = env('PGHOST', env('DB_HOST', '127.0.0.1'));
$pgPort = env('PGPORT', env('DB_PORT', '5432'));
$pgDatabase = env('PGDATABASE', env('DB_DATABASE', 'railway'));
$pgUsername = env('PGUSER', env('DB_USERNAME', 'postgres'));
$pgPassword = env('PGPASSWORD', env('DB_PASSWORD', ''));

// Parse DATABASE_URL untuk Railway/Heroku (fallback)
$databaseUrl = env('DATABASE_URL');

if (!empty($databaseUrl)) {
    // Remove potential prefix issues
    $url = str_replace('postgresql://', 'postgres://', $databaseUrl);
    $parsed = parse_url($url);
    
    if ($parsed !== false && isset($parsed['host'])) {
        $pgHost = $parsed['host'];
        $pgPort = (string)($parsed['port'] ?? '5432');
        $pgDatabase = isset($parsed['path']) ? ltrim($parsed['path'], '/') : $pgDatabase;
        $pgUsername = $parsed['user'] ?? $pgUsername;
        $pgPassword = isset($parsed['pass']) ? urldecode($parsed['pass']) : $pgPassword;
    }
}

// Determine default connection - use pgsql if any PG variable is set
$hasPgConfig = !empty(env('PGHOST')) || !empty(env('DATABASE_URL'));
$defaultConnection = env('DB_CONNECTION', $hasPgConfig ? 'pgsql' : 'sqlite');

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
