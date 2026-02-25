<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usulan_permintaan_barang', function (Blueprint $table) {
            $table->id();
            $table->string('kode_permintaan', 50)->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('ruangan_id')->constrained('ruangan')->onDelete('restrict');

            // Data Barang yang Diminta
            $table->string('nama_barang', 255);
            $table->integer('jumlah')->default(1);
            $table->enum('prioritas', ['rendah', 'sedang', 'tinggi'])->default('sedang');
            $table->text('keterangan_user')->nullable();

            // Tanggal & Status
            $table->date('tanggal_permintaan');
            $table->enum('status', ['pending', 'diproses', 'disetujui', 'ditolak', 'revisi'])->default('pending');

            // Catatan Admin
            $table->text('catatan_admin')->nullable();
            $table->text('alasan_penolakan')->nullable();
            $table->text('permintaan_revisi')->nullable();

            // Verifikasi Admin
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();

            // Jika disetujui, link ke item yang dibuat
            $table->foreignId('item_id')->nullable()->constrained('items')->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index('kode_permintaan');
            $table->index('status');
            $table->index('tanggal_permintaan');
            $table->index(['user_id', 'status']);
            $table->index(['ruangan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usulan_permintaan_barang');
    }
};
