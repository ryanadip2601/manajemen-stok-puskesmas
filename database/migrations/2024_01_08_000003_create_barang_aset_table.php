<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang_aset', function (Blueprint $table) {
            $table->id();
            $table->string('kode_aset', 50)->unique();
            $table->string('nama_aset', 255);
            $table->foreignId('kategori_aset_id')->constrained('kategori_aset')->onDelete('restrict');
            $table->foreignId('ruangan_id')->constrained('ruangan')->onDelete('restrict');
            $table->enum('kondisi', ['baik', 'rusak_ringan', 'rusak_berat'])->default('baik');
            $table->date('tanggal_masuk');
            $table->integer('jumlah')->default(1);
            $table->decimal('harga_perolehan', 15, 2)->nullable();
            $table->string('merk', 100)->nullable();
            $table->string('spesifikasi', 255)->nullable();
            $table->string('foto')->nullable();
            $table->text('keterangan')->nullable();
            $table->boolean('butuh_perbaikan')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('kode_aset');
            $table->index('nama_aset');
            $table->index('kondisi');
            $table->index('tanggal_masuk');
            $table->index(['ruangan_id', 'kondisi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang_aset');
    }
};
