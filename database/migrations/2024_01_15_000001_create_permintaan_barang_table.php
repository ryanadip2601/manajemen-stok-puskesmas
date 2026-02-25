<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permintaan_barang', function (Blueprint $table) {
            $table->id();
            $table->string('kode_permintaan', 50)->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Tipe permintaan
            $table->enum('tipe', ['barang_baru', 'restok'])->default('barang_baru');

            // Item reference (diisi jika tipe=restok, nullable jika barang_baru)
            $table->foreignId('item_id')->nullable()->constrained('items')->onDelete('set null');

            // Nama barang (manual jika barang_baru, otomatis dari item jika restok)
            $table->string('nama_barang', 255);

            // Ruangan (opsional, terutama untuk barang_baru)
            $table->foreignId('ruangan_id')->nullable()->constrained('ruangan')->onDelete('set null');

            // Detail permintaan
            $table->integer('jumlah')->default(1);
            $table->enum('prioritas', ['rendah', 'sedang', 'tinggi'])->default('sedang');
            $table->text('keterangan')->nullable();

            // Tanggal & Status
            $table->date('tanggal_permintaan');
            $table->enum('status', [
                'pending',
                'diproses',
                'disetujui',
                'ditolak',
                'revisi',
                'tersedia',
                'dibaca'
            ])->default('pending');

            // Admin response
            $table->text('catatan_admin')->nullable();
            $table->text('alasan_penolakan')->nullable();
            $table->text('permintaan_revisi')->nullable();

            // Verifikasi
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();

            // Notifikasi (untuk restok: popup "Barang Sudah Tersedia!")
            $table->timestamp('notified_at')->nullable();

            // Item yang dibuat setelah approve barang_baru
            $table->unsignedBigInteger('created_item_id')->nullable();
            $table->foreign('created_item_id')->references('id')->on('items')->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index('kode_permintaan');
            $table->index('tipe');
            $table->index('status');
            $table->index('tanggal_permintaan');
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'tipe']);
            $table->index(['item_id', 'status']);
            $table->index(['ruangan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_barang');
    }
};
