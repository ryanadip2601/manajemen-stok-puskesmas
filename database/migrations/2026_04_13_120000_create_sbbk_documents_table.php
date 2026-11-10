<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sbbk_documents', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_sbbk', 100)->unique();
            $table->date('tanggal_sbbk');
            $table->string('unit_peminta', 150);
            $table->string('perihal', 255)->nullable();
            $table->text('keterangan')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_mime', 100);
            $table->unsignedBigInteger('file_size');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('tanggal_sbbk');
            $table->index('uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sbbk_documents');
    }
};
