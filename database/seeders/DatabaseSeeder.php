<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use App\Models\Unit;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin User
        User::firstOrCreate(
            ['email' => 'admin@puskesmas.com'],
            [
                'name' => 'Admin Puskesmas',
                'password' => bcrypt('password123'),
                'role' => 'admin'
            ]
        );

        // Create Staff User
        User::firstOrCreate(
            ['email' => 'staff@puskesmas.com'],
            [
                'name' => 'Staff Gudang',
                'password' => bcrypt('password123'),
                'role' => 'staff'
            ]
        );

        // Create Units
        $units = [
            ['name' => 'Box', 'symbol' => 'Box'],
            ['name' => 'Pcs', 'symbol' => 'Pcs'],
            ['name' => 'Strip', 'symbol' => 'Strip'],
            ['name' => 'Botol', 'symbol' => 'Btl'],
            ['name' => 'Tube', 'symbol' => 'Tube'],
            ['name' => 'Sachet', 'symbol' => 'Sct'],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(['name' => $unit['name']], $unit);
        }

        // Create Categories
        $categories = [
            ['name' => 'Obat-obatan', 'description' => 'Kategori untuk obat-obatan umum'],
            ['name' => 'Alat Kesehatan', 'description' => 'Kategori untuk alat kesehatan medis'],
            ['name' => 'Vitamin & Suplemen', 'description' => 'Kategori untuk vitamin dan suplemen kesehatan'],
            ['name' => 'Alat Tulis', 'description' => 'Kategori untuk alat tulis kantor'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name']], $category);
        }
    }
}
