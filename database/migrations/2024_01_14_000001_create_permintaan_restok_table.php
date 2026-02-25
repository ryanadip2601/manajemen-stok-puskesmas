<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permintaan_restok', function (Blueprint $table) {
            $table->id();
            $table->string('kode_restok', 20)->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->integer('jumlah_diminta');
            $table->enum('prioritas', ['rendah', 'sedang', 'tinggi'])->default('sedang');
            $table->text('keterangan')->nullable();
            $table->date('tanggal_permintaan');
            $table->enum('status', ['pending', 'disetujui', 'ditolak', 'tersedia', 'dibaca'])->default('pending');
            $table->text('catatan_admin')->nullable();
            $table->text('alasan_penolakan')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['item_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_restok');
    }
};
