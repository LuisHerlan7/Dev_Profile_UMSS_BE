<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Añadir columnas a Experiencia_Laboral
        DB::statement('ALTER TABLE "Experiencia_Laboral" ADD COLUMN IF NOT EXISTS archivo_evidencia BYTEA');
        DB::statement('ALTER TABLE "Experiencia_Laboral" ADD COLUMN IF NOT EXISTS nombre_archivo_evidencia VARCHAR(255)');
        DB::statement('ALTER TABLE "Experiencia_Laboral" ADD COLUMN IF NOT EXISTS mime_tipo_evidencia VARCHAR(50)');

        // Añadir columnas a Formacion_Academica
        DB::statement('ALTER TABLE "Formacion_Academica" ADD COLUMN IF NOT EXISTS archivo_evidencia BYTEA');
        DB::statement('ALTER TABLE "Formacion_Academica" ADD COLUMN IF NOT EXISTS nombre_archivo_evidencia VARCHAR(255)');
        DB::statement('ALTER TABLE "Formacion_Academica" ADD COLUMN IF NOT EXISTS mime_tipo_evidencia VARCHAR(50)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE "Experiencia_Laboral" DROP COLUMN IF EXISTS archivo_evidencia');
        DB::statement('ALTER TABLE "Experiencia_Laboral" DROP COLUMN IF EXISTS nombre_archivo_evidencia');
        DB::statement('ALTER TABLE "Experiencia_Laboral" DROP COLUMN IF EXISTS mime_tipo_evidencia');

        DB::statement('ALTER TABLE "Formacion_Academica" DROP COLUMN IF EXISTS archivo_evidencia');
        DB::statement('ALTER TABLE "Formacion_Academica" DROP COLUMN IF EXISTS nombre_archivo_evidencia');
        DB::statement('ALTER TABLE "Formacion_Academica" DROP COLUMN IF EXISTS mime_tipo_evidencia');
    }
};
