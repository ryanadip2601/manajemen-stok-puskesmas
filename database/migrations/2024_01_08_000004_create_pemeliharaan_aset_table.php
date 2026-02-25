<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemeliharaan_aset', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_aset_id')->constrained('barang_aset')->onDelete('cascade');
            $table->foreignId('ruangan_id')->constrained('ruangan')->onDelete('restrict');
            $table->date('tanggal_pemeliharaan');
            $table->enum('jenis_pemeliharaan', ['rutinitas', 'perbaikan', 'penggantian'])->default('perbaikan');
            $table->text('deskripsi_masalah');
            $table->text('tindakan_perbaikan')->nullable();
            $table->enum('status', ['menunggu', 'proses', 'selesai', 'rusak'])->default('menunggu');
            $table->string('teknisi', 100);
            $table->decimal('biaya', 15, 2)->nullable();
            $table->string('lampiran_foto')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('tanggal_pemeliharaan');
            $table->index('status');
            $table->index('teknisi');
            $table->index(['barang_aset_id', 'status']);
            $table->index(['ruangan_id', 'tanggal_pemeliharaan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemeliharaan_aset');
    }
};
