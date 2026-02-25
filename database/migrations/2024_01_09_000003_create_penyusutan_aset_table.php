<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penyusutan_aset', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_aset_id')->constrained('barang_aset')->onDelete('cascade');
            
            // Periode penyusutan
            $table->year('tahun');
            $table->tinyInteger('bulan')->nullable(); // null = tahunan
            $table->date('tanggal_hitung');
            
            // Nilai dasar perhitungan
            $table->decimal('nilai_perolehan', 15, 2);
            $table->decimal('nilai_residu', 15, 2)->default(0);
            $table->integer('umur_manfaat'); // dalam tahun
            
            // Hasil perhitungan penyusutan
            $table->decimal('penyusutan_per_tahun', 15, 2);
            $table->decimal('penyusutan_per_bulan', 15, 2);
            $table->decimal('penyusutan_periode', 15, 2); // penyusutan untuk periode ini
            
            // Akumulasi
            $table->decimal('akumulasi_penyusutan_awal', 15, 2)->default(0);
            $table->decimal('akumulasi_penyusutan_akhir', 15, 2);
            
            // Nilai buku
            $table->decimal('nilai_buku_awal', 15, 2);
            $table->decimal('nilai_buku_akhir', 15, 2);
            
            // Mutasi dalam periode
            $table->decimal('mutasi_penambahan', 15, 2)->default(0);
            $table->decimal('mutasi_pengurangan', 15, 2)->default(0);
            $table->decimal('mutasi_koreksi', 15, 2)->default(0);
            
            // Metadata
            $table->string('metode_penyusutan', 50)->default('garis_lurus');
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Unique constraint untuk mencegah duplikasi
            $table->unique(['barang_aset_id', 'tahun', 'bulan'], 'unique_penyusutan_periode');
            
            // Indexes
            $table->index('tahun');
            $table->index(['tahun', 'bulan']);
            $table->index('tanggal_hitung');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penyusutan_aset');
    }
};
