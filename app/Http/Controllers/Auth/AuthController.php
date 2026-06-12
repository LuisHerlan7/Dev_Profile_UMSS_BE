<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                'unique:users,name',
            ],
            'email' => ['required', 'email', 'max:50', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:50', 'confirmed'],
        ], [
            'name.unique' => 'Ya existe un usuario registrado con ese nombre. Por favor, elige otro nombre o agrega caracteres adicionales (ej: segundo apellido, inicial).',
            'name.max' => 'El nombre no puede superar los 50 caracteres.',
            'email.unique' => 'Ese correo ya está registrado.',
            'email.max' => 'El correo no puede superar los 50 caracteres.',
            'password.max' => 'La contraseña no puede superar los 50 caracteres.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => 'desarrollador',
            'password' => Hash::make($validated['password']),
            'preferred_language' => 'es',
        ]);

        $this->ensureCvProfile($user);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Usuario registrado correctamente.',
            'token' => $token,
            'user' => $this->buildUserPayload($user),
            'dashboard' => $this->buildDashboardPayload($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'max:50'],
        ]);

        /** @var User|null $user */
        $user = User::where('email', $credentials['email'])->first();

        $isValidPassword = $user
            && (
                Hash::check($credentials['password'], $user->password)
                || hash_equals((string) $user->password, $credentials['password'])
            );

        if (! $isValidPassword) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales no son validas.'],
            ]);
        }

        if ($user && hash_equals((string) $user->password, $credentials['password'])) {
            $user->forceFill([
                'password' => Hash::make($credentials['password']),
            ])->save();
        }

        if (! $this->canLogin($user)) {
            throw ValidationException::withMessages([
                'email' => ['Solo administradores y desarrolladores pueden iniciar sesion.'],
            ]);
        }

        $this->ensureCvProfile($user);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesion exitoso.',
            'token' => $token,
            'user' => $this->buildUserPayload($user),
            'dashboard' => $this->buildDashboardPayload($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'user' => $this->buildUserPayload($user),
            'dashboard' => $this->buildDashboardPayload($user),
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'user' => $this->buildUserPayload($user),
            'dashboard' => $this->buildDashboardPayload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Sesion cerrada correctamente.',
        ]);
    }

    public function updateLanguage(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'language' => ['required', 'in:es,en'],
        ]);

        DB::transaction(function () use ($user, $data): void {
            $user->forceFill([
                'preferred_language' => $data['language'],
            ])->save();

            if ($user->role === 'desarrollador') {
                DB::update(
                    'UPDATE "Portafolio"
                     SET idioma_principal = ?, fecha_actualizacion = ?
                     WHERE id_usuario = (
                         SELECT id_usuario FROM "Usuario" WHERE correo = ? LIMIT 1
                     )',
                    [$data['language'], now(), $user->email]
                );
            }
        });

        return response()->json([
            'message' => 'Idioma actualizado correctamente.',
            'language' => $data['language'],
        ]);
    }

    /**
     * @return array<string, int|string|null>
     */
    private function buildUserPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'avatar' => $user->avatar,
            'provider' => $user->provider,
            'preferred_language' => $user->preferred_language ?: 'es',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDashboardPayload(User $user): array
    {
        $role = $user->role;
        $isAdmin = in_array($role, ['admin', 'administrador'], true);
        $isDeveloper = $role === 'desarrollador';

        return [
            'type' => $isAdmin ? 'admin' : ($isDeveloper ? 'desarrollador' : 'visitante'),
            'route' => $isAdmin ? '/admin' : ($isDeveloper ? '/desarrollador' : '/visitante'),
            'title' => $isAdmin
                ? 'Panel del administrador'
                : ($isDeveloper ? 'Panel del desarrollador' : 'Panel del visitante'),
            'subtitle' => $isAdmin
                ? 'Gestiona usuarios, reportes y configuraciones de la plataforma.'
                : ($isDeveloper
                    ? 'Gestiona tu perfil profesional, proyectos y habilidades.'
                    : 'Explora perfiles y descubre talento de la comunidad UMSS.'),
            'profile_role_label' => $this->resolveRoleLabel($user->role),
            'profile_badge' => $isAdmin
                ? 'administrador'
                : ($isDeveloper ? 'perfil activo' : 'explorador activo'),
            'welcome_title' => $isAdmin
                ? 'Todo bajo control desde tu panel.'
                : ($isDeveloper
                    ? 'Tu espacio profesional ya esta listo.'
                    : 'Encuentra perfiles y oportunidades en un solo lugar.'),
            'welcome_message' => $isAdmin
                ? 'Monitorea reportes, cuentas y actividad del sistema.'
                : ($isDeveloper
                    ? 'Revisa tu progreso, fortalece tu portafolio y manten visible tu experiencia.'
                    : 'Filtra portafolios, revisa habilidades y conecta con desarrolladores verificados.'),
            'sections' => $isAdmin
                ? [
                    ['id' => 'dashboard', 'label' => 'Resumen del Sistema'],
                    ['id' => 'users', 'label' => 'Gestion de Usuarios'],
                    ['id' => 'moderation', 'label' => 'Moderacion de Contenido'],
                    ['id' => 'analytics', 'label' => 'Analiticas del Sistema'],
                    ['id' => 'settings', 'label' => 'Configuracion'],
                    ['id' => 'security', 'label' => 'Auditoria de Seguridad'],
                ]
                : ($isDeveloper
                    ? [
                        ['id' => 'overview', 'label' => 'Informacion General'],
                        ['id' => 'projects', 'label' => 'Proyectos'],
                        ['id' => 'evidence', 'label' => 'Evidencias'],
                        ['id' => 'skills', 'label' => 'Habilidades'],
                        ['id' => 'experience', 'label' => 'Experiencia'],
                        ['id' => 'settings', 'label' => 'Configuracion'],
                    ]
                    : [
                        ['id' => 'explore', 'label' => 'Explorar Portafolios'],
                        ['id' => 'filters', 'label' => 'Filtros'],
                        ['id' => 'connections', 'label' => 'Descubrimiento'],
                    ]),
        ];
    }

    private function ensureCvProfile(User $user): void
    {
        $exists = DB::table('Usuario')
            ->where('correo', $user->email)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('Usuario')->insert([
            'nombre_completo' => $user->name,
            'correo' => $user->email,
            'correo_contacto' => $user->email,
            'contraseña_hash' => (string) ($user->password ?? ''),
            'estado_perfil' => 'activo',
            'visibilidad_perfil' => 'publico',
            'fecha_creacion' => now(),
            'fecha_actualizacion' => now(),
        ]);
    }

    private function canLogin(User $user): bool
    {
        return in_array($user->role, ['desarrollador', 'admin', 'administrador'], true);
    }

    private function resolveRoleLabel(?string $role): string
    {
        return match ($role) {
            'desarrollador' => 'Desarrollador',
            'visitante' => 'Visitante',
            'admin', 'administrador' => 'Administrador',
            default => 'Usuario',
        };
    }
}
