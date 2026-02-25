<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barang_aset', function (Blueprint $table) {
            $table->string('no_sertifikat', 255)->nullable()->after('nomor_register')->comment('No. Sertifikat/No. Pabrik/No. Chasis/No. Mesin');
            $table->string('asal_perolehan', 255)->nullable()->after('no_sertifikat')->comment('Asal/Cara Perolehan Barang');
            $table->string('ukuran_konstruksi', 255)->nullable()->after('asal_perolehan')->comment('Ukuran Barang/Konstruksi');
        });
    }

    public function down(): void
    {
        Schema::table('barang_aset', function (Blueprint $table) {
            $table->dropColumn(['no_sertifikat', 'asal_perolehan', 'ukuran_konstruksi']);
        });
    }
};
