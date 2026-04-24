<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE "Habilidad" ADD COLUMN IF NOT EXISTS porcentaje_dominio INT');

        DB::statement(<<<'SQL'
            UPDATE "Habilidad"
            SET porcentaje_dominio = CASE
                WHEN nivel_dominio = 'basico' THEN 25
                WHEN nivel_dominio = 'intermedio' THEN 50
                WHEN nivel_dominio = 'avanzado' THEN 75
                WHEN nivel_dominio = 'experto' THEN 100
                ELSE 50
            END
            WHERE porcentaje_dominio IS NULL
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE "Habilidad" DROP COLUMN IF EXISTS porcentaje_dominio');
    }
};
