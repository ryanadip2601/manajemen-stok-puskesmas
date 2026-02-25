<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Ruangan;
use App\Models\KategoriAset;
use App\Models\BarangAset;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding database...');

        // ============================================
        // USERS
        // ============================================
        $this->command->info('Creating users...');

        // Create Super Admin
        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'phone' => '081234567890',
                'role' => 'admin',
                'is_approved' => true,
                'approved_at' => now(),
            ]
        );

        // Create Admin Gudang
        User::updateOrCreate(
            ['email' => 'gudang@admin.com'],
            [
                'name' => 'Admin Gudang',
                'password' => Hash::make('password'),
                'phone' => '081234567891',
                'role' => 'admin_gudang',
                'is_approved' => true,
                'approved_at' => now(),
            ]
        );

        // Create Staff/Pegawai
        User::updateOrCreate(
            ['email' => 'staff@admin.com'],
            [
                'name' => 'Pegawai',
                'password' => Hash::make('password'),
                'phone' => '081234567892',
                'role' => 'staff',
                'is_approved' => true,
                'approved_at' => now(),
            ]
        );

        // ============================================
        // UNITS (Satuan Barang)
        // ============================================
        $this->command->info('Creating units...');

        $units = [
            ['name' => 'Box', 'symbol' => 'Box'],
            ['name' => 'Pcs', 'symbol' => 'Pcs'],
            ['name' => 'Strip', 'symbol' => 'Strip'],
            ['name' => 'Botol', 'symbol' => 'Btl'],
            ['name' => 'Tube', 'symbol' => 'Tube'],
            ['name' => 'Sachet', 'symbol' => 'Sct'],
            ['name' => 'Pak', 'symbol' => 'Pak'],
            ['name' => 'Lusin', 'symbol' => 'Lsn'],
            ['name' => 'Rim', 'symbol' => 'Rim'],
            ['name' => 'Unit', 'symbol' => 'Unit'],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(['name' => $unit['name']], $unit);
        }

        // ============================================
        // CATEGORIES (Kategori Barang)
        // ============================================
        $this->command->info('Creating categories...');

        $categories = [
            ['name' => 'Obat-obatan', 'description' => 'Kategori untuk obat-obatan umum'],
            ['name' => 'Alat Kesehatan', 'description' => 'Kategori untuk alat kesehatan medis'],
            ['name' => 'Vitamin & Suplemen', 'description' => 'Kategori untuk vitamin dan suplemen kesehatan'],
            ['name' => 'Alat Tulis Kantor', 'description' => 'Kategori untuk alat tulis kantor'],
            ['name' => 'Bahan Habis Pakai', 'description' => 'Kategori untuk bahan habis pakai medis'],
            ['name' => 'Alat Laboratorium', 'description' => 'Kategori untuk alat laboratorium'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name']], $category);
        }

        // ============================================
        // RUANGAN (untuk Modul Aset)
        // ============================================
        $this->command->info('Creating ruangan...');

        $ruangan = [
            ['nama_ruangan' => 'Ruang Pendaftaran', 'lokasi' => 'Lantai 1', 'keterangan' => 'Ruang pendaftaran pasien'],
            ['nama_ruangan' => 'Ruang Pemeriksaan Umum', 'lokasi' => 'Lantai 1', 'keterangan' => 'Ruang pemeriksaan dokter umum'],
            ['nama_ruangan' => 'Ruang Gigi', 'lokasi' => 'Lantai 1', 'keterangan' => 'Ruang perawatan gigi'],
            ['nama_ruangan' => 'Ruang KIA/KB', 'lokasi' => 'Lantai 1', 'keterangan' => 'Ruang Kesehatan Ibu dan Anak'],
            ['nama_ruangan' => 'Laboratorium', 'lokasi' => 'Lantai 1', 'keterangan' => 'Ruang laboratorium'],
            ['nama_ruangan' => 'Gudang Obat', 'lokasi' => 'Lantai 1', 'keterangan' => 'Penyimpanan obat-obatan'],
            ['nama_ruangan' => 'Ruang Kepala Puskesmas', 'lokasi' => 'Lantai 2', 'keterangan' => 'Kantor kepala puskesmas'],
            ['nama_ruangan' => 'Ruang Administrasi', 'lokasi' => 'Lantai 2', 'keterangan' => 'Ruang administrasi umum'],
        ];

        foreach ($ruangan as $r) {
            Ruangan::firstOrCreate(['nama_ruangan' => $r['nama_ruangan']], $r);
        }

        // ============================================
        // KATEGORI ASET
        // ============================================
        $this->command->info('Creating kategori aset...');

        $kategoriAset = [
            ['nama_kategori' => 'Mebel & Furniture', 'kode_golongan' => 'KA-001', 'golongan' => 'peralatan_mesin', 'deskripsi' => 'Meja, kursi, lemari, dll', 'umur_manfaat' => 8],
            ['nama_kategori' => 'Alat Medis', 'kode_golongan' => 'KA-002', 'golongan' => 'peralatan_mesin', 'deskripsi' => 'Peralatan medis dan kesehatan', 'umur_manfaat' => 5],
            ['nama_kategori' => 'Elektronik', 'kode_golongan' => 'KA-003', 'golongan' => 'peralatan_mesin', 'deskripsi' => 'Komputer, printer, AC, dll', 'umur_manfaat' => 4],
            ['nama_kategori' => 'Kendaraan', 'kode_golongan' => 'KA-004', 'golongan' => 'peralatan_mesin', 'deskripsi' => 'Ambulans, motor, mobil dinas', 'umur_manfaat' => 8],
            ['nama_kategori' => 'Peralatan Laboratorium', 'kode_golongan' => 'KA-005', 'golongan' => 'peralatan_mesin', 'deskripsi' => 'Alat-alat laboratorium', 'umur_manfaat' => 5],
        ];

        foreach ($kategoriAset as $ka) {
            KategoriAset::firstOrCreate(['kode_golongan' => $ka['kode_golongan']], $ka);
        }

        // ============================================
        // BARANG ASET (Sample Data)
        // ============================================
        $this->command->info('Creating sample barang aset...');

        // Get kategori and ruangan IDs
        $kategoriMebel = KategoriAset::where('nama_kategori', 'Mebel & Furniture')->first();
        $kategoriMedis = KategoriAset::where('nama_kategori', 'Alat Medis')->first();
        $kategoriElektronik = KategoriAset::where('nama_kategori', 'Elektronik')->first();
        $kategoriLab = KategoriAset::where('nama_kategori', 'Peralatan Laboratorium')->first();

        $ruanganPendaftaran = Ruangan::where('nama_ruangan', 'Ruang Pendaftaran')->first();
        $ruanganPemeriksaan = Ruangan::where('nama_ruangan', 'Ruang Pemeriksaan Umum')->first();
        $ruanganLab = Ruangan::where('nama_ruangan', 'Laboratorium')->first();
        $ruanganGigi = Ruangan::where('nama_ruangan', 'Ruang Gigi')->first();
        $ruanganAdmin = Ruangan::where('nama_ruangan', 'Ruang Administrasi')->first();

        if ($kategoriMebel && $kategoriMedis && $kategoriElektronik && $kategoriLab) {
            $barangAset = [
                // Ruang Pendaftaran
                ['kode_aset' => 'AST-2024-0001', 'nama_aset' => 'Meja Pendaftaran', 'kategori_aset_id' => $kategoriMebel->id, 'ruangan_id' => $ruanganPendaftaran->id, 'kondisi' => 'baik', 'tanggal_masuk' => '2024-01-15', 'harga_perolehan' => 1500000, 'merk' => 'Olympic'],
                ['kode_aset' => 'AST-2024-0002', 'nama_aset' => 'Kursi Tunggu 3 Seater', 'kategori_aset_id' => $kategoriMebel->id, 'ruangan_id' => $ruanganPendaftaran->id, 'kondisi' => 'baik', 'tanggal_masuk' => '2024-01-15', 'harga_perolehan' => 2500000, 'merk' => 'Chitose', 'jumlah' => 4],
                ['kode_aset' => 'AST-2024-0003', 'nama_aset' => 'Komputer Desktop', 'kategori_aset_id' => $kategoriElektronik->id, 'ruangan_id' => $ruanganPendaftaran->id, 'kondisi' => 'baik', 'tanggal_masuk' => '2024-02-01', 'harga_perolehan' => 8000000, 'merk' => 'Lenovo'],
                
                // Ruang Pemeriksaan Umum
                ['kode_aset' => 'AST-2024-0004', 'nama_aset' => 'Tempat Tidur Periksa', 'kategori_aset_id' => $kategoriMedis->id, 'ruangan_id' => $ruanganPemeriksaan->id, 'kondisi' => 'baik', 'tanggal_masuk' => '2024-01-10', 'harga_perolehan' => 3500000, 'merk' => 'GEA'],
                ['kode_aset' => 'AST-2024-0005', 'nama_aset' => 'Tensimeter Digital', 'kategori_aset_id' => $kategoriMedis->id, 'ruangan_id' => $ruanganPemeriksaan->id, 'kondisi' => 'baik', 'tanggal_masuk' => '2024-01-10', 'harga_perolehan' => 850000, 'merk' => 'Omron'],
                ['kode_aset' => 'AST-2024-0006', 'nama_aset' => 'Stetoskop', 'kategori_aset_id' => $kategoriMedis->id, 'ruangan_id' => $ruanganPemeriksaan->id, 'kondisi' => 'rusak_ringan', 'tanggal_masuk' => '2023-06-15', 'harga_perolehan' => 350000, 'merk' => 'Littmann'],
                
                // Laboratorium
                ['kode_aset' => 'AST-2024-0007', 'nama_aset' => 'Mikroskop Binokuler', 'kategori_aset_id' => $kategoriLab->id, 'ruangan_id' => $ruanganLab->id, 'kondisi' => 'baik', 'tanggal_masuk' => '2024-03-01', 'harga_perolehan' => 15000000, 'merk' => 'Olympus'],
                ['kode_aset' => 'AST-2024-0008', 'nama_aset' => 'Centrifuge', 'kategori_aset_id' => $kategoriLab->id, 'ruangan_id' => $ruanganLab->id, 'kondisi' => 'baik', 'tanggal_masuk' => '2024-03-01', 'harga_perolehan' => 8500000, 'merk' => 'Hettich'],
                ['kode_aset' => 'AST-2024-0009', 'nama_aset' => 'Hematology Analyzer', 'kategori_aset_id' => $kategoriLab->id, 'ruangan_id' => $ruanganLab->id, 'kondisi' => 'rusak_ringan', 'tanggal_masuk' => '2023-01-20', 'harga_perolehan' => 75000000, 'merk' => 'Sysmex'],
                ['kode_aset' => 'AST-2024-0010', 'nama_aset' => 'Lemari Penyimpanan Reagent', 'kategori_aset_id' => $kategoriMebel->id, 'ruangan_id' => $ruanganLab->id, 'kondisi' => 'baik', 'tanggal_masuk' => '2024-01-15', 'harga_perolehan' => 4500000, 'merk' => 'Krisbow'],
                
                // Ruang Gigi
                ['kode_aset' => 'AST-2024-0011', 'nama_aset' => 'Dental Unit', 'kategori_aset_id' => $kategoriMedis->id, 'ruangan_id' => $ruanganGigi->id, 'kondisi' => 'baik', 'tanggal_masuk' => '2023-08-01', 'harga_perolehan' => 45000000, 'merk' => 'Sirona'],
                ['kode_aset' => 'AST-2024-0012', 'nama_aset' => 'Kompresor Dental', 'kategori_aset_id' => $kategoriMedis->id, 'ruangan_id' => $ruanganGigi->id, 'kondisi' => 'baik', 'tanggal_masuk' => '2023-08-01', 'harga_perolehan' => 12000000, 'merk' => 'Cattani'],
                
                // Ruang Administrasi
                ['kode_aset' => 'AST-2024-0013', 'nama_aset' => 'Laptop', 'kategori_aset_id' => $kategoriElektronik->id, 'ruangan_id' => $ruanganAdmin->id, 'kondisi' => 'baik', 'tanggal_masuk' => '2024-04-01', 'harga_perolehan' => 12000000, 'merk' => 'HP ProBook'],
                ['kode_aset' => 'AST-2024-0014', 'nama_aset' => 'Printer LaserJet', 'kategori_aset_id' => $kategoriElektronik->id, 'ruangan_id' => $ruanganAdmin->id, 'kondisi' => 'baik', 'tanggal_masuk' => '2024-04-01', 'harga_perolehan' => 4500000, 'merk' => 'HP'],
                ['kode_aset' => 'AST-2024-0015', 'nama_aset' => 'Lemari Arsip', 'kategori_aset_id' => $kategoriMebel->id, 'ruangan_id' => $ruanganAdmin->id, 'kondisi' => 'baik', 'tanggal_masuk' => '2024-01-10', 'harga_perolehan' => 3500000, 'merk' => 'Brother'],
            ];

            foreach ($barangAset as $ba) {
                BarangAset::firstOrCreate(['kode_aset' => $ba['kode_aset']], array_merge($ba, ['jumlah' => $ba['jumlah'] ?? 1]));
            }
        }

        $this->command->info('Database seeding completed!');
        $this->command->newLine();
        $this->command->info('===========================================');
        $this->command->info('Default Login Credentials:');
        $this->command->info('===========================================');
        $this->command->info('Admin     : admin@admin.com / password');
        $this->command->info('Gudang    : gudang@admin.com / password');
        $this->command->info('Staff     : staff@admin.com / password');
        $this->command->info('===========================================');
    }
}
