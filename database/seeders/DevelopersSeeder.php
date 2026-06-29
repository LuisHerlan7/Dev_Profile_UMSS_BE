<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DevelopersSeeder extends Seeder
{
    private const TOTAL_DEVELOPERS = 48;

    public function run(): void
    {
        $technologies = [
            'JavaScript' => ['framework', 'Frontend and backend language'],
            'TypeScript' => ['lenguaje_programacion', 'Typed JavaScript language'],
            'React' => ['framework', 'Frontend UI framework'],
            'Vue.js' => ['framework', 'Frontend UI framework'],
            'Angular' => ['framework', 'Frontend UI framework'],
            'Node.js' => ['framework', 'Backend runtime'],
            'Laravel' => ['framework', 'PHP web framework'],
            'Django' => ['framework', 'Python web framework'],
            'Spring Boot' => ['framework', 'Java backend framework'],
            'Flutter' => ['framework', 'Cross-platform mobile framework'],
            'Kotlin' => ['lenguaje_programacion', 'Android and backend language'],
            'Python' => ['lenguaje_programacion', 'General purpose language'],
            'Java' => ['lenguaje_programacion', 'Backend and enterprise language'],
            'C#' => ['lenguaje_programacion', 'Microsoft ecosystem language'],
            'PostgreSQL' => ['base_datos', 'Relational database'],
            'MySQL' => ['base_datos', 'Relational database'],
            'MongoDB' => ['base_datos', 'Document database'],
            'Firebase' => ['herramienta_desarrollo', 'Mobile and web backend platform'],
            'AWS' => ['herramienta_desarrollo', 'Cloud platform'],
            'Docker' => ['herramienta_desarrollo', 'Containerization tool'],
            'Kubernetes' => ['herramienta_desarrollo', 'Container orchestration'],
            'Git' => ['herramienta_desarrollo', 'Version control'],
            'GraphQL' => ['otro', 'API query language'],
            'REST API' => ['otro', 'API architecture style'],
        ];

        foreach ($technologies as $name => [$category, $description]) {
            DB::table('Tecnologia')->updateOrInsert(
                ['nombre_tecnologia' => $name],
                ['categoria' => $category, 'descripcion' => $description]
            );
        }

        $profiles = [
            'Frontend' => ['React', 'Vue.js', 'Angular', 'TypeScript', 'JavaScript', 'Git'],
            'Backend' => ['Laravel', 'Node.js', 'Django', 'Spring Boot', 'PostgreSQL', 'REST API'],
            'Fullstack' => ['React', 'Laravel', 'Node.js', 'PostgreSQL', 'TypeScript', 'REST API'],
            'Mobile' => ['Flutter', 'Kotlin', 'Firebase', 'REST API', 'Git'],
            'DevOps' => ['Docker', 'Kubernetes', 'AWS', 'Git', 'PostgreSQL'],
            'Data Science' => ['Python', 'PostgreSQL', 'MongoDB', 'AWS', 'Git'],
        ];

        $levels = [
            'Junior' => ['years' => [0, 2], 'projects' => [1, 3], 'skills' => [3, 5], 'jobs' => [0, 1]],
            'Semi-Senior' => ['years' => [3, 5], 'projects' => [3, 5], 'skills' => [5, 7], 'jobs' => [1, 3]],
            'Senior' => ['years' => [6, 12], 'projects' => [5, 8], 'skills' => [6, 9], 'jobs' => [2, 4]],
        ];

        $firstNames = ['Juan', 'Carlos', 'Miguel', 'Luis', 'Fernando', 'David', 'Alejandro', 'Ricardo', 'Francisco', 'Jorge', 'Patricia', 'Maria', 'Sandra', 'Laura', 'Ana', 'Sofia', 'Elena', 'Carmen', 'Rosa', 'Teresa', 'Valeria', 'Diego', 'Camila', 'Andres'];
        $lastNames = ['Garcia', 'Martinez', 'Rodriguez', 'Gonzalez', 'Flores', 'Lopez', 'Hernandez', 'Perez', 'Sanchez', 'Ramirez', 'Cruz', 'Morales', 'Gutierrez', 'Romero', 'Vargas', 'Castillo', 'Medina', 'Reyes'];
        $softSkills = ['Comunicacion efectiva', 'Trabajo en equipo', 'Liderazgo tecnico', 'Resolucion de problemas', 'Mentoria', 'Gestion de tiempo'];
        $profileNames = array_keys($profiles);
        $levelNames = array_keys($levels);

        for ($i = 1; $i <= self::TOTAL_DEVELOPERS; $i++) {
            $firstName = $firstNames[($i - 1) % count($firstNames)];
            $lastName = $lastNames[(int) floor(($i - 1) / count($firstNames)) % count($lastNames)];
            $profileType = $profileNames[($i - 1) % count($profileNames)];
            $experienceLevel = $levelNames[(int) floor(($i - 1) / count($profileNames)) % count($levelNames)];
            $years = $this->numberInRange($levels[$experienceLevel]['years'], $i);
            $email = sprintf('seed.dev%03d@est.umss.edu', $i);
            $name = "{$firstName} {$lastName}";

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('developer123'),
                    'role' => 'desarrollador',
                ]
            );

            $usuarioId = $this->ensureUsuario($user->email, $name, $profileType, $experienceLevel, $years);
            $portafolioId = $this->ensurePortafolio($usuarioId, $name, $profileType, $i);

            $this->resetGeneratedProfileData($usuarioId, $portafolioId);
            $this->seedVisibility($usuarioId);
            $this->seedSkills($usuarioId, $profiles[$profileType], $softSkills, $levels[$experienceLevel]['skills'], $years, $i);
            $this->seedExperience($usuarioId, $profileType, $experienceLevel, $levels[$experienceLevel]['jobs'], $years, $i);
            $this->seedEducation($usuarioId, $experienceLevel, $i);
            $this->seedProjects($usuarioId, $portafolioId, $profileType, $experienceLevel, $profiles[$profileType], $levels[$experienceLevel]['projects'], $i);
            $this->seedNetworks($usuarioId, $name, $i);
        }

        $this->command?->info(self::TOTAL_DEVELOPERS.' developer profiles seeded for filter testing.');
    }

    private function ensureUsuario(string $email, string $name, string $profileType, string $experienceLevel, int $years): int
    {
        DB::table('Usuario')->updateOrInsert(
            ['correo' => $email],
            [
                'nombre_completo' => $name,
                'contraseña_hash' => Hash::make('developer123'),
                'estado_perfil' => 'activo',
                'visibilidad_perfil' => 'publico',
                'profesion' => "{$profileType} Developer {$experienceLevel}",
                'biografia' => "Desarrollador {$experienceLevel} orientado a {$profileType} con {$years} anos de experiencia.",
                'telefono' => '+591 7'.random_int(1000000, 9999999),
                'correo_contacto' => $email,
                'fecha_actualizacion' => now(),
            ]
        );

        return (int) DB::table('Usuario')->where('correo', $email)->value('id_usuario');
    }

    private function ensurePortafolio(int $usuarioId, string $name, string $profileType, int $index): int
    {
        $slug = Str::slug("{$name}-{$profileType}-{$index}");
        $url = rtrim((string) env('FRONTEND_URL', 'http://127.0.0.1:4200'), '/').'/portafolio/'.$slug;

        DB::table('Portafolio')->updateOrInsert(
            ['id_usuario' => $usuarioId],
            [
                'titulo_portafolio' => "Portafolio {$profileType} - {$name}",
                'descripcion_general' => "Perfil publico de prueba para filtros de desarrolladores {$profileType}.",
                'url_publica' => $url,
                'tema_color' => $this->themeColor($index),
                'idioma_principal' => 'es',
                'estado' => 'publicado',
                'fecha_actualizacion' => now(),
            ]
        );

        return (int) DB::table('Portafolio')->where('id_usuario', $usuarioId)->value('id_portafolio');
    }

    private function resetGeneratedProfileData(int $usuarioId, int $portafolioId): void
    {
        DB::table('Proyecto')->where('id_portafolio', $portafolioId)->delete();
        DB::table('Habilidad')->where('id_usuario', $usuarioId)->delete();
        DB::table('Experiencia_Laboral')->where('id_usuario', $usuarioId)->delete();
        DB::table('Formacion_Academica')->where('id_usuario', $usuarioId)->delete();
        DB::table('Red_Profesional')->where('id_usuario', $usuarioId)->delete();
        DB::table('Configuracion_Visibilidad')->where('id_usuario', $usuarioId)->delete();
    }

    private function seedVisibility(int $usuarioId): void
    {
        DB::table('Configuracion_Visibilidad')->insert([
            'id_usuario' => $usuarioId,
            'mostrar_proyectos' => true,
            'mostrar_habilidades' => true,
            'mostrar_experiencia' => true,
            'mostrar_formacion' => true,
            'mostrar_redes_sociales' => true,
            'permitir_descargas' => true,
            'permitir_comentarios' => false,
            'modo_visibilidad' => 'publico',
            'mostrar_informacion_general' => true,
            'mostrar_contacto' => true,
            'mostrar_correo' => true,
            'mostrar_telefono' => false,
            'fecha_actualizacion' => now(),
        ]);
    }

    private function seedSkills(int $usuarioId, array $technicalSkills, array $softSkills, array $skillRange, int $years, int $seed): void
    {
        $skillCount = $this->numberInRange($skillRange, $seed);
        $selectedTechnical = array_slice($this->rotate($technicalSkills, $seed), 0, min($skillCount, count($technicalSkills)));

        foreach ($selectedTechnical as $offset => $skillName) {
            DB::table('Habilidad')->insert([
                'id_usuario' => $usuarioId,
                'nombre_habilidad' => $skillName,
                'tipo_habilidad' => 'tecnica',
                'descripcion' => "Experiencia practica desarrollando soluciones con {$skillName}.",
                'nivel_dominio' => $this->skillLevel($years, $offset),
                'porcentaje_dominio' => min(100, 35 + ($years * 7) + ($offset * 3)),
                'anos_experiencia' => max(1, min($years, $years - $offset + 1)),
                'fecha_adquisicion' => now()->subYears(max(1, $years))->toDateString(),
                'estado' => 'activo',
            ]);
        }

        foreach (array_slice($this->rotate($softSkills, $seed), 0, 2) as $skillName) {
            DB::table('Habilidad')->insert([
                'id_usuario' => $usuarioId,
                'nombre_habilidad' => $skillName,
                'tipo_habilidad' => 'blanda',
                'descripcion' => "Habilidad aplicada en equipos academicos y profesionales.",
                'nivel_dominio' => $years >= 6 ? 'avanzado' : 'intermedio',
                'porcentaje_dominio' => $years >= 6 ? 85 : 65,
                'anos_experiencia' => max(1, $years),
                'fecha_adquisicion' => now()->subYears(max(1, $years))->toDateString(),
                'estado' => 'activo',
            ]);
        }
    }

    private function seedExperience(int $usuarioId, string $profileType, string $experienceLevel, array $jobRange, int $years, int $seed): void
    {
        $companies = ['TuringSoft', 'Andes Digital', 'Cochabamba Labs', 'Qhatu Tech', 'Kuntur Cloud', 'Data UMSS'];
        $jobCount = $this->numberInRange($jobRange, $seed);

        for ($i = 0; $i < $jobCount; $i++) {
            $startYearsAgo = max(1, $years - $i * 2);
            DB::table('Experiencia_Laboral')->insert([
                'id_usuario' => $usuarioId,
                'titulo_puesto' => "{$experienceLevel} {$profileType} Developer",
                'nombre_empresa' => $companies[($seed + $i) % count($companies)],
                'descripcion_puesto' => "Desarrollo de productos {$profileType}, colaboracion con equipos y entrega de funcionalidades verificables.",
                'fecha_inicio' => now()->subYears($startYearsAgo)->startOfMonth()->toDateString(),
                'fecha_fin' => $i === 0 && $experienceLevel !== 'Junior' ? null : now()->subYears(max(0, $startYearsAgo - 1))->endOfMonth()->toDateString(),
                'es_trabajo_actual' => $i === 0 && $experienceLevel !== 'Junior',
                'ubicacion' => 'Cochabamba, Bolivia',
                'tipo_contrato' => ['tiempo_completo', 'medio_tiempo', 'freelance', 'temporal'][($seed + $i) % 4],
                'visibilidad' => 'publico',
            ]);
        }
    }

    private function seedEducation(int $usuarioId, string $experienceLevel, int $seed): void
    {
        DB::table('Formacion_Academica')->insert([
            'id_usuario' => $usuarioId,
            'institucion' => 'Universidad Mayor de San Simon',
            'nivel_estudio' => 'licenciatura',
            'carrera_especialidad' => 'Ingenieria Informatica',
            'fecha_inicio' => now()->subYears(5 + ($seed % 3))->toDateString(),
            'fecha_fin' => $experienceLevel === 'Junior' ? null : now()->subYears(1 + ($seed % 2))->toDateString(),
            'actualmente_estudiante' => $experienceLevel === 'Junior',
            'descripcion' => 'Formacion academica generada para pruebas de perfil y busqueda.',
            'visibilidad' => 'publico',
        ]);
    }

    private function seedProjects(int $usuarioId, int $portafolioId, string $profileType, string $experienceLevel, array $skills, array $projectRange, int $seed): void
    {
        $projectCount = $this->numberInRange($projectRange, $seed);
        $statuses = ['verificado', 'en_revision', 'rechazado'];

        for ($i = 1; $i <= $projectCount; $i++) {
            $status = $statuses[($seed + $i) % count($statuses)];
            $projectId = DB::table('Proyecto')->insertGetId([
                'id_portafolio' => $portafolioId,
                'nombre_proyecto' => "{$profileType} {$this->projectName($seed + $i)} {$i}",
                'descripcion_proyecto' => "Proyecto {$experienceLevel} para demostrar capacidades {$profileType}.",
                'descripcion_tecnica' => 'Arquitectura, implementacion, pruebas y despliegue documentados.',
                'fecha_inicio' => now()->subMonths(18 + $i)->toDateString(),
                'fecha_fin' => now()->subMonths($i)->toDateString(),
                'enlace_repositorio' => 'https://github.com/umss/'.Str::slug("{$profileType}-seed-{$seed}-{$i}"),
                'enlace_proyecto_activo' => 'https://demo.umss.edu/'.Str::slug("{$profileType}-seed-{$seed}-{$i}"),
                'estado_proyecto' => ['completado', 'en_desarrollo', 'pausado'][($seed + $i) % 3],
                'rol_desarrollador' => "{$profileType} Developer",
                'visibilidad' => 'publico',
                'estado_revision' => $status,
                'fecha_creacion' => now()->subDays($seed + $i),
            ], 'id_proyecto');

            foreach (array_slice($this->rotate($skills, $i), 0, min(4, count($skills))) as $skillName) {
                $techId = (int) DB::table('Tecnologia')->where('nombre_tecnologia', $skillName)->value('id_tecnologia');
                DB::table('Tecnologia_Proyecto')->insert([
                    'id_proyecto' => $projectId,
                    'id_tecnologia' => $techId,
                    'nivel_utilizacion' => ['basico', 'intermedio', 'avanzado'][($seed + $i) % 3],
                ]);
            }

            DB::table('Evidencia_Digital')->insert([
                'id_proyecto' => $projectId,
                'id_usuario' => $usuarioId,
                'tipo_evidencia' => ['imagen', 'documento', 'video', 'enlace'][($seed + $i) % 4],
                'titulo' => "Evidencia {$profileType} {$i}",
                'descripcion' => 'Evidencia de prueba asociada al proyecto generado.',
                'url_enlace' => rtrim((string) env('APP_URL', 'http://127.0.0.1:9200'), '/').'/storage/evidences/seed-'.$seed.'-'.$i.'.pdf',
                'nombre_archivo' => 'seed-'.$seed.'-'.$i.'.pdf',
                'tipo_mime' => 'application/pdf',
                'tamaño_archivo' => 512000 + ($seed * 1000),
                'fecha_carga' => now()->subDays($i),
                'visibilidad' => 'publico',
                'estado_revision' => $status,
            ]);
        }
    }

    private function seedNetworks(int $usuarioId, string $name, int $seed): void
    {
        $slug = Str::slug($name.'-'.$seed);

        foreach (['GitHub' => 'https://github.com/', 'LinkedIn' => 'https://linkedin.com/in/'] as $network => $baseUrl) {
            DB::table('Red_Profesional')->insert([
                'id_usuario' => $usuarioId,
                'nombre_red' => $network,
                'enlace_perfil' => $baseUrl.$slug,
                'usuario_red' => $slug,
                'verificado' => $network === 'GitHub',
                'fecha_agregado' => now(),
            ]);
        }
    }

    private function numberInRange(array $range, int $seed): int
    {
        [$min, $max] = $range;

        return $min + ($seed % (($max - $min) + 1));
    }

    private function rotate(array $items, int $offset): array
    {
        $offset %= count($items);

        return array_merge(array_slice($items, $offset), array_slice($items, 0, $offset));
    }

    private function skillLevel(int $years, int $offset): string
    {
        if ($years >= 8 && $offset < 2) {
            return 'experto';
        }

        if ($years >= 4) {
            return 'avanzado';
        }

        return $offset === 0 ? 'intermedio' : 'basico';
    }

    private function themeColor(int $index): string
    {
        $colors = ['#2563eb', '#059669', '#dc2626', '#7c3aed', '#0891b2', '#ca8a04'];

        return $colors[($index - 1) % count($colors)];
    }

    private function projectName(int $seed): string
    {
        $names = ['Dashboard', 'API', 'Portal', 'Marketplace', 'Monitor', 'Planner', 'Analytics', 'Automation'];

        return $names[$seed % count($names)];
    }
}
