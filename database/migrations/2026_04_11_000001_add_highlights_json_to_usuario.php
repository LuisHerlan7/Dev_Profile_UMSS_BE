<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE "Usuario" ADD COLUMN IF NOT EXISTS highlights_json TEXT DEFAULT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE "Usuario" DROP COLUMN IF EXISTS highlights_json');
    }
};
