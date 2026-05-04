<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE "Usuario" ADD COLUMN IF NOT EXISTS correo_contacto VARCHAR(100)');
        DB::statement('ALTER TABLE "Usuario" ADD COLUMN IF NOT EXISTS titulos_jerarquia_json TEXT');
        DB::statement('ALTER TABLE "Usuario" ADD COLUMN IF NOT EXISTS roles_jerarquia_json TEXT');
        DB::statement('ALTER TABLE "Usuario" ADD COLUMN IF NOT EXISTS telefono_verificacion_estado VARCHAR(20) DEFAULT \'sin_verificar\'');
        DB::statement('ALTER TABLE "Usuario" ADD COLUMN IF NOT EXISTS telefono_verificado_at TIMESTAMP NULL');

        DB::statement('UPDATE "Usuario" SET correo_contacto = correo WHERE correo_contacto IS NULL OR BTRIM(correo_contacto) = \'\'');

        DB::statement(<<<'SQL'
DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conrelid = '"Usuario"'::regclass
          AND conname = 'Usuario_visibilidad_perfil_check'
    ) THEN
        ALTER TABLE "Usuario" DROP CONSTRAINT "Usuario_visibilidad_perfil_check";
    END IF;
END $$;
SQL);

        DB::statement(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conrelid = '"Usuario"'::regclass
          AND conname = 'usuario_visibilidad_perfil_check_v2'
    ) THEN
        ALTER TABLE "Usuario"
        ADD CONSTRAINT usuario_visibilidad_perfil_check_v2
        CHECK (visibilidad_perfil IN ('publico', 'privado', 'personalizado'));
    END IF;
END $$;
SQL);

        DB::statement('ALTER TABLE "Configuracion_Visibilidad" ADD COLUMN IF NOT EXISTS modo_visibilidad VARCHAR(20) DEFAULT \'publico\'');
        DB::statement('ALTER TABLE "Configuracion_Visibilidad" ADD COLUMN IF NOT EXISTS mostrar_informacion_general BOOLEAN DEFAULT TRUE');
        DB::statement('ALTER TABLE "Configuracion_Visibilidad" ADD COLUMN IF NOT EXISTS mostrar_contacto BOOLEAN DEFAULT TRUE');
        DB::statement('ALTER TABLE "Configuracion_Visibilidad" ADD COLUMN IF NOT EXISTS mostrar_correo BOOLEAN DEFAULT TRUE');
        DB::statement('ALTER TABLE "Configuracion_Visibilidad" ADD COLUMN IF NOT EXISTS mostrar_telefono BOOLEAN DEFAULT FALSE');

        DB::statement(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conrelid = '"Configuracion_Visibilidad"'::regclass
          AND conname = 'configuracion_visibilidad_modo_check'
    ) THEN
        ALTER TABLE "Configuracion_Visibilidad"
        ADD CONSTRAINT configuracion_visibilidad_modo_check
        CHECK (modo_visibilidad IN ('publico', 'privado', 'personalizado'));
    END IF;
END $$;
SQL);

        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS "Habilidad_Vinculo" (
    id_vinculo INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_habilidad INT NOT NULL,
    tipo_referencia VARCHAR(20) NOT NULL
        CHECK (tipo_referencia IN ('proyecto', 'experiencia', 'formacion')),
    id_proyecto INT NULL,
    id_experiencia INT NULL,
    id_formacion INT NULL,
    etiqueta_referencia VARCHAR(200) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_habilidad) REFERENCES "Habilidad"(id_habilidad) ON DELETE CASCADE,
    FOREIGN KEY (id_proyecto) REFERENCES "Proyecto"(id_proyecto) ON DELETE CASCADE,
    FOREIGN KEY (id_experiencia) REFERENCES "Experiencia_Laboral"(id_experiencia) ON DELETE CASCADE,
    FOREIGN KEY (id_formacion) REFERENCES "Formacion_Academica"(id_formacion) ON DELETE CASCADE
);
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS "Habilidad_Vinculo"');
        DB::statement('ALTER TABLE "Configuracion_Visibilidad" DROP COLUMN IF EXISTS mostrar_telefono');
        DB::statement('ALTER TABLE "Configuracion_Visibilidad" DROP COLUMN IF EXISTS mostrar_correo');
        DB::statement('ALTER TABLE "Configuracion_Visibilidad" DROP COLUMN IF EXISTS mostrar_contacto');
        DB::statement('ALTER TABLE "Configuracion_Visibilidad" DROP COLUMN IF EXISTS mostrar_informacion_general');
        DB::statement('ALTER TABLE "Configuracion_Visibilidad" DROP COLUMN IF EXISTS modo_visibilidad');
        DB::statement('ALTER TABLE "Usuario" DROP COLUMN IF EXISTS telefono_verificado_at');
        DB::statement('ALTER TABLE "Usuario" DROP COLUMN IF EXISTS telefono_verificacion_estado');
        DB::statement('ALTER TABLE "Usuario" DROP COLUMN IF EXISTS roles_jerarquia_json');
        DB::statement('ALTER TABLE "Usuario" DROP COLUMN IF EXISTS titulos_jerarquia_json');
        DB::statement('ALTER TABLE "Usuario" DROP COLUMN IF EXISTS correo_contacto');
    }
};
