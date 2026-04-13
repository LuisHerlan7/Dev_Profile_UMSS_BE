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
        DB::statement('ALTER TABLE "Experiencia_Laboral" ADD COLUMN IF NOT EXISTS archivo_evidencia BYTEA');
        DB::statement('ALTER TABLE "Experiencia_Laboral" ADD COLUMN IF NOT EXISTS nombre_archivo_evidencia VARCHAR(255)');
        DB::statement('ALTER TABLE "Experiencia_Laboral" ADD COLUMN IF NOT EXISTS mime_tipo_evidencia VARCHAR(50)');

        DB::statement('ALTER TABLE "Formacion_Academica" ADD COLUMN IF NOT EXISTS archivo_evidencia BYTEA');
        DB::statement('ALTER TABLE "Formacion_Academica" ADD COLUMN IF NOT EXISTS nombre_archivo_evidencia VARCHAR(255)');
        DB::statement('ALTER TABLE "Formacion_Academica" ADD COLUMN IF NOT EXISTS mime_tipo_evidencia VARCHAR(50)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('experience_and_formation_tables', function (Blueprint $table) {
            //
        });
    }
};
