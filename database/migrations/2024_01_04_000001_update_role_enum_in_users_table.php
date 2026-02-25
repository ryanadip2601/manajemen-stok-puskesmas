<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // For SQLite, we need to recreate the column
        // First, add a temporary column
        Schema::table('users', function (Blueprint $table) {
            $table->string('role_new')->default('staff')->after('role');
        });

        // Copy data
        DB::statement("UPDATE users SET role_new = role");

        // Drop old column and rename new one
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('role_new', 'role');
        });
    }

    public function down(): void
    {
        // Revert back if needed
    }
};
