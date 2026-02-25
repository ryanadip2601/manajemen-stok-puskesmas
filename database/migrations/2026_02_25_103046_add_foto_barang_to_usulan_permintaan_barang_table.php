<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('usulan_permintaan_barang', function (Blueprint $table) {
            $table->string('foto_barang')->nullable()->after('keterangan_user');
            $table->string('tipe_foto_barang')->nullable()->after('foto_barang'); // 'upload' atau 'url'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usulan_permintaan_barang', function (Blueprint $table) {
            $table->dropColumn(['foto_barang', 'tipe_foto_barang']);
        });
    }
};
