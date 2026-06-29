<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admins = [
            ['email' => 'root@gmail.com', 'name' => 'root'],
            ['email' => 'parche@gmail.com', 'name' => 'Parche'],
            ['email' => 'herlan@gmail.com', 'name' => 'Herlan'],
            ['email' => 'kevin@gmail.com', 'name' => 'Kevin'],
            ['email' => 'nioxi@gmail.com', 'name' => 'Nioxi'],
            ['email' => 'andy@gmail.com', 'name' => 'Andy'],
            ['email' => 'mikory@gmail.com', 'name' => 'Mikory'],
        ];

        foreach ($admins as $admin) {
            $user = User::updateOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'],
                    'password' => Hash::make('admin123'),
                    'role' => 'admin',
                ]
            );

            $this->ensureUsuarioProfile($user);
        }

        $developer = User::updateOrCreate(
            ['email' => '696969696@est.ums.edu'],
            [
                'name' => 'Adrian',
                'password' => Hash::make('holaHerlan'),
                'role' => 'desarrollador',
            ]
        );

        $developerUsuarioId = $this->ensureUsuarioProfile($developer);
        $portafolioId = $this->ensurePortafolio($developerUsuarioId, $developer->name);

        $projectDefinitions = [
            [
                'name' => 'Sistema de Inventarios UMSS',
                'description' => 'Plataforma web para la gestión de inventarios institucionales.',
                'role' => 'Fullstack Developer',
                'repo_url' => 'https://github.com/umss/inventarios',
                'live_url' => 'https://inventarios.umss.edu',
                'status' => 'verificado',
                'evidences' => [
                    ['title' => 'Capturas UI', 'type' => 'imagen', 'status' => 'verificado'],
                    ['title' => 'Informe PDF', 'type' => 'documento', 'status' => 'verificado'],
                ],
                'tech' => ['React', 'Laravel', 'PostgreSQL'],
            ],
            [
                'name' => 'App Movil Becas',
                'description' => 'Aplicacion movil para seguimiento de becas universitarias.',
                'role' => 'Mobile Developer',
                'repo_url' => 'https://github.com/umss/becas-app',
                'live_url' => 'https://demo.umss.edu/becas',
                'status' => 'en_revision',
                'evidences' => [
                    ['title' => 'Video Demo', 'type' => 'video', 'status' => 'en_revision'],
                ],
                'tech' => ['Flutter', 'Firebase'],
            ],
            [
                'name' => 'Portal de Practicas',
                'description' => 'Sistema de practicas y vinculacion con empresas.',
                'role' => 'Backend Developer',
                'repo_url' => 'https://github.com/umss/practicas',
                'live_url' => 'https://practicas.umss.edu',
                'status' => 'rechazado',
                'evidences' => [
                    ['title' => 'Contrato firmado', 'type' => 'documento', 'status' => 'rechazado'],
                    ['title' => 'Captura de API', 'type' => 'imagen', 'status' => 'rechazado'],
                ],
                'tech' => ['Node.js', 'PostgreSQL'],
            ],
        ];

        foreach ($projectDefinitions as $definition) {
            $projectRecord = DB::table('Proyecto')
                ->where('id_portafolio', $portafolioId)
                ->where('nombre_proyecto', $definition['name'])
                ->first();

            $projectId = $projectRecord?->id_proyecto ?? DB::table('Proyecto')->insertGetId([
                'id_portafolio' => $portafolioId,
                'nombre_proyecto' => $definition['name'],
                'descripcion_proyecto' => $definition['description'],
                'rol_desarrollador' => $definition['role'],
                'enlace_repositorio' => $definition['repo_url'],
                'enlace_proyecto_activo' => $definition['live_url'],
                'visibilidad' => 'publico',
                'estado_revision' => $definition['status'],
                'fecha_creacion' => now(),
            ], 'id_proyecto');

            if ($projectRecord) {
                DB::table('Proyecto')
                    ->where('id_proyecto', $projectId)
                    ->update([
                        'descripcion_proyecto' => $definition['description'],
                        'rol_desarrollador' => $definition['role'],
                        'enlace_repositorio' => $definition['repo_url'],
                        'enlace_proyecto_activo' => $definition['live_url'],
                        'estado_revision' => $definition['status'],
                    ]);
            }

            foreach ($definition['tech'] as $techName) {
                $tech = DB::table('Tecnologia')->where('nombre_tecnologia', $techName)->first();
                $techId = $tech?->id_tecnologia ?? DB::table('Tecnologia')->insertGetId([
                    'nombre_tecnologia' => $techName,
                    'categoria' => 'otro',
                    'descripcion' => 'Tecnologia registrada en el seed.',
                ], 'id_tecnologia');

                DB::table('Tecnologia_Proyecto')->updateOrInsert(
                    [
                        'id_proyecto' => $projectId,
                        'id_tecnologia' => $techId,
                    ],
                    ['nivel_utilizacion' => 'intermedio']
                );
            }

            foreach ($definition['evidences'] as $index => $evidence) {
                $fileName = Str::slug($evidence['title']).'-'.($index + 1).'.pdf';
                $url = rtrim((string) env('APP_URL', 'http://127.0.0.1:9200'), '/').'/storage/evidences/'.$fileName;

                DB::table('Evidencia_Digital')->updateOrInsert(
                    [
                        'id_proyecto' => $projectId,
                        'titulo' => $evidence['title'],
                    ],
                    [
                        'id_usuario' => $developerUsuarioId,
                        'tipo_evidencia' => $evidence['type'],
                        'descripcion' => 'Evidencia cargada para demostracion.',
                        'url_enlace' => $url,
                        'nombre_archivo' => $fileName,
                        'tipo_mime' => $evidence['type'] === 'video' ? 'video/mp4' : 'application/pdf',
                        'tamaño_archivo' => 1048576,
                        'fecha_carga' => now(),
                        'visibilidad' => 'publico',
                        'estado_revision' => $evidence['status'],
                    ]
                );
            }
        }

        $this->seedSecurityEvents($developerUsuarioId);
        $this->call(DevelopersSeeder::class);
    }

    private function ensureUsuarioProfile(User $user): int
    {
        $existing = DB::table('Usuario')->where('correo', $user->email)->first();

        if ($existing) {
            return (int) $existing->id_usuario;
        }

        return (int) DB::table('Usuario')->insertGetId([
            'nombre_completo' => $user->name,
            'correo' => $user->email,
            'contraseña_hash' => $user->password,
            'estado_perfil' => 'activo',
            'visibilidad_perfil' => 'publico',
            'fecha_creacion' => now(),
        ], 'id_usuario');
    }

    private function ensurePortafolio(int $usuarioId, string $userName): int
    {
        $existing = DB::table('Portafolio')->where('id_usuario', $usuarioId)->first();

        if ($existing) {
            return (int) $existing->id_portafolio;
        }

        $slug = Str::slug($userName.'-'.$usuarioId, '-');
        $url = rtrim((string) env('FRONTEND_URL', 'http://127.0.0.1:4200'), '/').'/portafolio/'.$slug;

        return (int) DB::table('Portafolio')->insertGetId([
            'id_usuario' => $usuarioId,
            'titulo_portafolio' => 'Portafolio de '.$userName,
            'descripcion_general' => 'Portafolio generado para demostracion.',
            'url_publica' => $url,
            'estado' => 'publicado',
            'fecha_creacion' => now(),
        ], 'id_portafolio');
    }

    private function seedSecurityEvents(int $usuarioId): void
    {
        $events = [
            ['title' => 'Registro de actividad', 'desc' => 'Actividad administrativa registrada por el sistema.'],
            ['title' => 'Actualizacion de perfil', 'desc' => 'El usuario actualizo informacion de su cuenta.'],
            ['title' => 'Revision de contenido', 'desc' => 'Contenido evaluable fue revisado desde el panel administrativo.'],
            ['title' => 'Cambio de configuracion', 'desc' => 'Se actualizo una preferencia del sistema.'],
        ];

        foreach ($events as $offset => $event) {
            DB::table('Actividad_Sistema')->insert([
                'id_usuario' => $usuarioId,
                'tipo_actividad' => $event['title'],
                'descripcion' => $event['desc'],
                'tabla_afectada' => 'Usuario',
                'id_registro_afectado' => $usuarioId,
                'fecha_actividad' => Carbon::now()->subHours($offset + 1),
                'direccion_ip' => '192.168.1.'.(10 + $offset),
            ]);
        }
    }
}
