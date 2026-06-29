<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE "Usuario" ADD COLUMN IF NOT EXISTS nivel_experiencia VARCHAR(20)');
        DB::statement("ALTER TABLE \"Usuario\" ADD CONSTRAINT check_nivel_experiencia CHECK (nivel_experiencia IN ('senior', 'semi-senior', 'junior'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE "Usuario" DROP CONSTRAINT IF EXISTS check_nivel_experiencia');
        DB::statement('ALTER TABLE "Usuario" DROP COLUMN IF EXISTS nivel_experiencia');
    }
};
