<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add last_login_at to users table
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('is_approved');
            $table->timestamp('last_activity_at')->nullable()->after('last_login_at');
            $table->string('last_ip_address')->nullable()->after('last_activity_at');
        });

        // Create login histories table
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('action', ['login', 'logout'])->default('login');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device')->nullable();
            $table->string('browser')->nullable();
            $table->string('platform')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_histories');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_login_at', 'last_activity_at', 'last_ip_address']);
        });
    }
};
