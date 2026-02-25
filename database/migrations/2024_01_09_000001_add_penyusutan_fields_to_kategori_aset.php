<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kategori_aset', function (Blueprint $table) {
            // Golongan aset sesuai standar akuntansi pemerintah
            $table->string('kode_golongan', 20)->nullable()->after('nama_kategori');
            $table->enum('golongan', [
                'tanah',           // Tidak disusutkan
                'peralatan_mesin', // Umur 5 tahun
                'gedung_bangunan', // Umur 50 tahun
                'jalan_irigasi',   // Umur 10 tahun
                'aset_tetap_lainnya', // Umur 4 tahun
                'kdp'              // Konstruksi Dalam Pengerjaan - tidak disusutkan
            ])->default('peralatan_mesin')->after('kode_golongan');
            
            // Umur manfaat dalam tahun
            $table->integer('umur_manfaat')->default(5)->after('golongan');
            
            // Apakah kategori ini bisa disusutkan
            $table->boolean('dapat_disusutkan')->default(true)->after('umur_manfaat');
        });
    }

    public function down(): void
    {
        Schema::table('kategori_aset', function (Blueprint $table) {
            $table->dropColumn(['kode_golongan', 'golongan', 'umur_manfaat', 'dapat_disusutkan']);
        });
    }
};
