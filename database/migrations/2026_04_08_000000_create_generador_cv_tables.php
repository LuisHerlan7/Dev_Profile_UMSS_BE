<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // NOTE:
        // - This migration creates the project tables that were previously defined in DatabaseSeeder.
        // - We intentionally do NOT run "CREATE DATABASE ...": Laravel should connect to an existing DB
        //   configured via .env (DB_DATABASE), and then create tables inside it.

        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS "Usuario" (
    id_usuario INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre_completo VARCHAR(150) NOT NULL,
    correo VARCHAR(100) UNIQUE NOT NULL,
    contraseña_hash VARCHAR(255) NOT NULL,
    fotografia BYTEA,
    profesion VARCHAR(100),
    biografia TEXT,
    telefono VARCHAR(20),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP,
    estado_perfil VARCHAR(20) DEFAULT 'activo'
        CHECK (estado_perfil IN ('activo','inactivo','suspendido')),
    visibilidad_perfil VARCHAR(20) DEFAULT 'publico'
        CHECK (visibilidad_perfil IN ('publico','privado'))
);
SQL);

        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS "Portafolio" (
    id_portafolio INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_usuario INT UNIQUE,
    titulo_portafolio VARCHAR(200) NOT NULL,
    descripcion_general TEXT,
    url_publica VARCHAR(255) UNIQUE NOT NULL,
    tema_color VARCHAR(7),
    idioma_principal VARCHAR(5) DEFAULT 'es',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP,
    estado VARCHAR(20) DEFAULT 'borrador'
        CHECK (estado IN ('borrador','publicado','archivado')),
    FOREIGN KEY (id_usuario) REFERENCES "Usuario"(id_usuario) ON DELETE CASCADE
);
SQL);

        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS "Configuracion_Visibilidad" (
    id_config INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_usuario INT UNIQUE,
    mostrar_proyectos BOOLEAN DEFAULT TRUE,
    mostrar_habilidades BOOLEAN DEFAULT TRUE,
    mostrar_experiencia BOOLEAN DEFAULT TRUE,
    mostrar_formacion BOOLEAN DEFAULT TRUE,
    mostrar_redes_sociales BOOLEAN DEFAULT TRUE,
    permitir_descargas BOOLEAN DEFAULT TRUE,
    permitir_comentarios BOOLEAN DEFAULT FALSE,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES "Usuario"(id_usuario) ON DELETE CASCADE
);
SQL);

        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS "Proyecto" (
    id_proyecto INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_portafolio INT,
    nombre_proyecto VARCHAR(150) NOT NULL,
    descripcion_proyecto TEXT NOT NULL,
    descripcion_tecnica TEXT,
    fecha_fin DATE,
    enlace_repositorio VARCHAR(255),
    enlace_proyecto_activo VARCHAR(255),
    estado_proyecto VARCHAR(20) DEFAULT 'completado'
        CHECK (estado_proyecto IN ('en_desarrollo','completado','pausado')),
    rol_desarrollador VARCHAR(100),
    fecha_inicio DATE,
    visibilidad VARCHAR(20) DEFAULT 'publico'
        CHECK (visibilidad IN ('publico','privado')),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_portafolio) REFERENCES "Portafolio"(id_portafolio) ON DELETE CASCADE
);
SQL);

        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS "Tecnologia" (
    id_tecnologia INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre_tecnologia VARCHAR(100) UNIQUE NOT NULL,
    categoria VARCHAR(30) NOT NULL
        CHECK (categoria IN ('lenguaje_programacion','framework','base_datos','herramienta_desarrollo','otro')),
    descripcion TEXT
);
SQL);

        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS "Tecnologia_Proyecto" (
    id_tech_proyecto INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_proyecto INT,
    id_tecnologia INT,
    nivel_utilizacion VARCHAR(20)
        CHECK (nivel_utilizacion IN ('basico','intermedio','avanzado')),
    FOREIGN KEY (id_proyecto) REFERENCES "Proyecto"(id_proyecto) ON DELETE CASCADE,
    FOREIGN KEY (id_tecnologia) REFERENCES "Tecnologia"(id_tecnologia) ON DELETE CASCADE
);
SQL);

        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS "Habilidad" (
    id_habilidad INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_usuario INT,
    nombre_habilidad VARCHAR(100) NOT NULL,
    tipo_habilidad VARCHAR(20)
        CHECK (tipo_habilidad IN ('tecnica','blanda')),
    descripcion TEXT,
    nivel_dominio VARCHAR(20)
        CHECK (nivel_dominio IN ('basico','intermedio','avanzado','experto')),
    anos_experiencia INT,
    fecha_adquisicion DATE,
    estado VARCHAR(20) DEFAULT 'activo'
        CHECK (estado IN ('activo','inactivo')),
    FOREIGN KEY (id_usuario) REFERENCES "Usuario"(id_usuario) ON DELETE CASCADE
);
SQL);

        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS "Experiencia_Laboral" (
    id_experiencia INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    titulo_puesto VARCHAR(150) NOT NULL,
    id_usuario INT,
    nombre_empresa VARCHAR(150) NOT NULL,
    descripcion_puesto TEXT,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE,
    es_trabajo_actual BOOLEAN DEFAULT FALSE,
    ubicacion VARCHAR(150),
    tipo_contrato VARCHAR(20)
        CHECK (tipo_contrato IN ('tiempo_completo','medio_tiempo','freelance','temporal')),
    visibilidad VARCHAR(20) DEFAULT 'publico'
        CHECK (visibilidad IN ('publico','privado')),
    FOREIGN KEY (id_usuario) REFERENCES "Usuario"(id_usuario) ON DELETE CASCADE
);
SQL);

        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS "Formacion_Academica" (
    id_formacion INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_usuario INT,
    institucion VARCHAR(150) NOT NULL,
    nivel_estudio VARCHAR(20)
        CHECK (nivel_estudio IN ('secundaria','diploma','licenciatura','maestria','doctorado','curso','certificado')),
    carrera_especialidad VARCHAR(150) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE,
    actualmente_estudiante BOOLEAN DEFAULT FALSE,
    descripcion TEXT,
    visibilidad VARCHAR(20) DEFAULT 'publico'
        CHECK (visibilidad IN ('publico','privado')),
    FOREIGN KEY (id_usuario) REFERENCES "Usuario"(id_usuario) ON DELETE CASCADE
);
SQL);

        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS "Red_Profesional" (
    id_red INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_usuario INT,
    nombre_red VARCHAR(100) NOT NULL,
    enlace_perfil VARCHAR(255) NOT NULL,
    usuario_red VARCHAR(100),
    verificado BOOLEAN DEFAULT FALSE,
    fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES "Usuario"(id_usuario) ON DELETE CASCADE
);
SQL);

        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS "Actividad_Sistema" (
    id_actividad INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_usuario INT,
    tipo_actividad VARCHAR(100) NOT NULL,
    descripcion TEXT,
    tabla_afectada VARCHAR(50),
    id_registro_afectado INT,
    fecha_actividad TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    direccion_ip VARCHAR(45),
    FOREIGN KEY (id_usuario) REFERENCES "Usuario"(id_usuario) ON DELETE CASCADE
);
SQL);

        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS "Evidencia_Digital" (
    id_evidencia INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_proyecto INT,
    id_usuario INT,
    tipo_evidencia VARCHAR(20)
        CHECK (tipo_evidencia IN ('imagen','documento','video','enlace')),
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    url_enlace VARCHAR(500),
    archivo BYTEA,
    nombre_archivo VARCHAR(255),
    tipo_mime VARCHAR(50),
    tamaño_archivo BIGINT,
    fecha_carga TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    visibilidad VARCHAR(20) DEFAULT 'publico'
        CHECK (visibilidad IN ('publico','privado')),
    FOREIGN KEY (id_proyecto) REFERENCES "Proyecto"(id_proyecto) ON DELETE SET NULL,
    FOREIGN KEY (id_usuario) REFERENCES "Usuario"(id_usuario) ON DELETE CASCADE
);
SQL);

        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS "Contacto_Visitante" (
    id_contacto INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_portafolio INT,
    nombre_visitante VARCHAR(150),
    correo_visitante VARCHAR(100),
    asunto VARCHAR(200) NOT NULL,
    mensaje TEXT NOT NULL,
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(20) DEFAULT 'no_leido'
        CHECK (estado IN ('no_leido','leido','respondido')),
    FOREIGN KEY (id_portafolio) REFERENCES "Portafolio"(id_portafolio) ON DELETE CASCADE
);
SQL);

        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS "Reporte" (
    id_reporte INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_portafolio INT,
    tipo_reporte VARCHAR(20)
        CHECK (tipo_reporte IN ('general','proyectos','habilidades','experiencia','academica')),
    nombre_reporte VARCHAR(200) NOT NULL,
    contenido BYTEA,
    formato VARCHAR(10)
        CHECK (formato IN ('pdf','docx','html')),
    fecha_generacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_descarga_ultima TIMESTAMP,
    total_descargas INT DEFAULT 0,
    FOREIGN KEY (id_portafolio) REFERENCES "Portafolio"(id_portafolio) ON DELETE CASCADE
);
SQL);

        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS "Idioma_Portafolio" (
    id_idioma_portafolio INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    codigo_idioma VARCHAR(5) NOT NULL,
    id_portafolio INT,
    idioma_nombre VARCHAR(50) NOT NULL,
    contenido_traducido BYTEA,
    fecha_traduccion TIMESTAMP,
    FOREIGN KEY (id_portafolio) REFERENCES "Portafolio"(id_portafolio) ON DELETE CASCADE
);
SQL);
    }

    public function down(): void
    {
        // Drop in reverse order to satisfy FK constraints.
        DB::statement('DROP TABLE IF EXISTS "Idioma_Portafolio";');
        DB::statement('DROP TABLE IF EXISTS "Reporte";');
        DB::statement('DROP TABLE IF EXISTS "Contacto_Visitante";');
        DB::statement('DROP TABLE IF EXISTS "Evidencia_Digital";');
        DB::statement('DROP TABLE IF EXISTS "Actividad_Sistema";');
        DB::statement('DROP TABLE IF EXISTS "Red_Profesional";');
        DB::statement('DROP TABLE IF EXISTS "Formacion_Academica";');
        DB::statement('DROP TABLE IF EXISTS "Experiencia_Laboral";');
        DB::statement('DROP TABLE IF EXISTS "Habilidad";');
        DB::statement('DROP TABLE IF EXISTS "Tecnologia_Proyecto";');
        DB::statement('DROP TABLE IF EXISTS "Tecnologia";');
        DB::statement('DROP TABLE IF EXISTS "Proyecto";');
        DB::statement('DROP TABLE IF EXISTS "Configuracion_Visibilidad";');
        DB::statement('DROP TABLE IF EXISTS "Portafolio";');
        DB::statement('DROP TABLE IF EXISTS "Usuario";');
    }
};

