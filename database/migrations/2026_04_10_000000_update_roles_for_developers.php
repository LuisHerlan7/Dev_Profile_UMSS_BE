<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'visitante')
            ->orWhereNull('role')
            ->update(['role' => 'desarrollador']);

        DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'desarrollador'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'visitante'");
    }
};

