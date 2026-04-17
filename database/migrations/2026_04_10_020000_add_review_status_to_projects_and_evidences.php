<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
ALTER TABLE "Proyecto"
    ADD COLUMN IF NOT EXISTS estado_revision VARCHAR(20)
        DEFAULT 'en_revision'
        CHECK (estado_revision IN ('en_revision','verificado','rechazado'));
SQL);

        DB::statement(<<<'SQL'
UPDATE "Proyecto" SET estado_revision = 'en_revision' WHERE estado_revision IS NULL;
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE "Evidencia_Digital"
    ADD COLUMN IF NOT EXISTS estado_revision VARCHAR(20)
        DEFAULT 'en_revision'
        CHECK (estado_revision IN ('en_revision','verificado','rechazado'));
SQL);

        DB::statement(<<<'SQL'
UPDATE "Evidencia_Digital" SET estado_revision = 'en_revision' WHERE estado_revision IS NULL;
SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE "Evidencia_Digital" DROP COLUMN IF EXISTS estado_revision;');
        DB::statement('ALTER TABLE "Proyecto" DROP COLUMN IF EXISTS estado_revision;');
    }
};
