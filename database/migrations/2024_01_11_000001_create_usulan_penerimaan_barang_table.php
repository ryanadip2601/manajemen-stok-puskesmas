<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usulan_penerimaan_barang', function (Blueprint $table) {
            $table->id();
            $table->string('kode_usulan', 50)->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('ruangan_id')->constrained('ruangan')->onDelete('restrict');
            $table->foreignId('kategori_aset_id')->nullable()->constrained('kategori_aset')->onDelete('set null');
            
            // Data Barang
            $table->string('nama_barang', 255);
            $table->string('merk', 100)->nullable();
            $table->text('spesifikasi')->nullable();
            $table->integer('jumlah')->default(1);
            $table->enum('kondisi_barang', ['baru', 'bekas_baik', 'bekas_rusak_ringan'])->default('baru');
            $table->enum('kelengkapan', ['lengkap', 'tidak_lengkap'])->default('lengkap');
            $table->decimal('estimasi_harga', 15, 2)->nullable();
            $table->string('sumber_barang', 100)->nullable();
            
            // Foto Barang (multiple photos stored as JSON)
            $table->json('foto_barang')->nullable();
            
            // Tanggal & Status
            $table->date('tanggal_usulan');
            $table->date('tanggal_penerimaan_fisik')->nullable();
            $table->enum('status', ['pending', 'diproses', 'disetujui', 'ditolak', 'revisi'])->default('pending');
            $table->enum('prioritas', ['rendah', 'sedang', 'tinggi'])->default('sedang');
            
            // Catatan
            $table->text('keterangan_user')->nullable();
            $table->text('catatan_admin')->nullable();
            $table->text('alasan_penolakan')->nullable();
            $table->text('permintaan_revisi')->nullable();
            
            // Verifikasi Admin
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            
            // Jika disetujui, link ke barang_aset yang dibuat
            $table->foreignId('barang_aset_id')->nullable()->constrained('barang_aset')->onDelete('set null');
            
            $table->timestamps();
            
            // Indexes
            $table->index('kode_usulan');
            $table->index('status');
            $table->index('tanggal_usulan');
            $table->index(['user_id', 'status']);
            $table->index(['ruangan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usulan_penerimaan_barang');
    }
};
