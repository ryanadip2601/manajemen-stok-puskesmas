<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Database Connection
    |--------------------------------------------------------------------------
    | Untuk Hostinger: mysql
    | Untuk Development Lokal: sqlite
    */
    'default' => env('DB_CONNECTION', 'sqlite'),

    'connections' => [
        /*
        |--------------------------------------------------------------------------
        | SQLite (Development Lokal)
        |--------------------------------------------------------------------------
        */
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => database_path('database.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | MySQL (Hostinger Production)
        |--------------------------------------------------------------------------
        */
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'simbar_db'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],
    ],

    'migrations' => 'migrations',
];
