<?php

$databaseUrl = env('DATABASE_URL');
$pgConnection = [
    'driver' => 'pgsql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'railway'),
    'username' => env('DB_USERNAME', 'postgres'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8',
    'prefix' => '',
    'prefix_indexes' => true,
    'search_path' => 'public',
    'sslmode' => 'prefer',
];

// Parse DATABASE_URL jika tersedia (Railway)
if ($databaseUrl) {
    $parsed = parse_url($databaseUrl);
    $pgConnection['host'] = $parsed['host'] ?? $pgConnection['host'];
    $pgConnection['port'] = $parsed['port'] ?? $pgConnection['port'];
    $pgConnection['database'] = ltrim($parsed['path'] ?? '/railway', '/');
    $pgConnection['username'] = $parsed['user'] ?? $pgConnection['username'];
    $pgConnection['password'] = $parsed['pass'] ?? $pgConnection['password'];
}

return [
    'default' => env('DB_CONNECTION', 'sqlite'),

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

        'pgsql' => $pgConnection,
    ],

    'migrations' => 'migrations',
];
