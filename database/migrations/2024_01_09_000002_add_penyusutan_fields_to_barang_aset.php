<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barang_aset', function (Blueprint $table) {
            // Data perolehan aset
            $table->year('tahun_perolehan')->nullable()->after('tanggal_masuk');
            $table->string('sumber_dana', 100)->nullable()->after('tahun_perolehan');
            $table->string('nomor_register', 50)->nullable()->after('sumber_dana');
            
            // Data penyusutan
            $table->integer('umur_manfaat_override')->nullable()->after('harga_perolehan');
            $table->decimal('nilai_residu', 15, 2)->default(0)->after('umur_manfaat_override');
            
            // Mutasi aset
            $table->decimal('nilai_penambahan', 15, 2)->default(0)->after('nilai_residu');
            $table->decimal('nilai_pengurangan', 15, 2)->default(0)->after('nilai_penambahan');
            $table->decimal('nilai_koreksi', 15, 2)->default(0)->after('nilai_pengurangan');
            
            // Status aset
            $table->enum('status_aset', ['aktif', 'tidak_aktif', 'dihapuskan', 'dipindahkan'])->default('aktif')->after('nilai_koreksi');
            $table->date('tanggal_penghapusan')->nullable()->after('status_aset');
            $table->text('alasan_penghapusan')->nullable()->after('tanggal_penghapusan');
            
            // Indexes
            $table->index('tahun_perolehan');
            $table->index('status_aset');
        });
    }

    public function down(): void
    {
        Schema::table('barang_aset', function (Blueprint $table) {
            $table->dropIndex(['tahun_perolehan']);
            $table->dropIndex(['status_aset']);
            $table->dropColumn([
                'tahun_perolehan', 'sumber_dana', 'nomor_register',
                'umur_manfaat_override', 'nilai_residu',
                'nilai_penambahan', 'nilai_pengurangan', 'nilai_koreksi',
                'status_aset', 'tanggal_penghapusan', 'alasan_penghapusan'
            ]);
        });
    }
};
