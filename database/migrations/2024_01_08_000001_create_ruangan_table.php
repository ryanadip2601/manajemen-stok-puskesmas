<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ruangan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_ruangan', 100);
            $table->string('lokasi', 255)->nullable();
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('nama_ruangan');
            $table->index('lokasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ruangan');
    }
};
