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
        User::create([
            'name' => 'Admin Puskesmas',
            'email' => 'admin@puskesmas.com',
            'password' => bcrypt('password123'),
            'role' => 'admin'
        ]);

        // Create Staff User
        User::create([
            'name' => 'Staff Gudang',
            'email' => 'staff@puskesmas.com',
            'password' => bcrypt('password123'),
            'role' => 'staff'
        ]);

        // Create Units
        Unit::create(['name' => 'Box', 'symbol' => 'Box']);
        Unit::create(['name' => 'Pcs', 'symbol' => 'Pcs']);
        Unit::create(['name' => 'Strip', 'symbol' => 'Strip']);
        Unit::create(['name' => 'Botol', 'symbol' => 'Btl']);
        Unit::create(['name' => 'Tube', 'symbol' => 'Tube']);
        Unit::create(['name' => 'Sachet', 'symbol' => 'Sct']);

        // Create Categories
        Category::create([
            'name' => 'Obat-obatan',
            'description' => 'Kategori untuk obat-obatan umum'
        ]);

        Category::create([
            'name' => 'Alat Kesehatan',
            'description' => 'Kategori untuk alat kesehatan medis'
        ]);

        Category::create([
            'name' => 'Vitamin & Suplemen',
            'description' => 'Kategori untuk vitamin dan suplemen kesehatan'
        ]);

        Category::create([
            'name' => 'Alat Tulis',
            'description' => 'Kategori untuk alat tulis kantor'
        ]);
    }
}
