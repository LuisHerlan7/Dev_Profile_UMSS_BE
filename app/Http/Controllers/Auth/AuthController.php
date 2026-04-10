<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:desarrollador,visitante'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => $validated['password'],
        ]);

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
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales no son validas.'],
            ]);
        }

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
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDashboardPayload(User $user): array
    {
        $isDeveloper = $user->role === 'desarrollador';

        return [
            'type' => $isDeveloper ? 'desarrollador' : 'visitante',
            'route' => $isDeveloper ? '/desarrollador' : '/visitante',
            'title' => $isDeveloper ? 'Panel del desarrollador' : 'Panel del visitante',
            'subtitle' => $isDeveloper
                ? 'Gestiona tu perfil profesional, proyectos y habilidades.'
                : 'Explora perfiles y descubre talento de la comunidad UMSS.',
            'profile_role_label' => $this->resolveRoleLabel($user->role),
            'profile_badge' => $isDeveloper ? 'perfil activo' : 'explorador activo',
            'welcome_title' => $isDeveloper
                ? 'Tu espacio profesional ya esta listo.'
                : 'Encuentra perfiles y oportunidades en un solo lugar.',
            'welcome_message' => $isDeveloper
                ? 'Revisa tu progreso, fortalece tu portafolio y manten visible tu experiencia.'
                : 'Filtra portafolios, revisa habilidades y conecta con desarrolladores verificados.',
            'sections' => $isDeveloper
                ? [
                    ['id' => 'overview', 'label' => 'Informacion General'],
                    ['id' => 'projects', 'label' => 'Proyectos'],
                    ['id' => 'skills', 'label' => 'Habilidades'],
                    ['id' => 'experience', 'label' => 'Experiencia'],
                    ['id' => 'settings', 'label' => 'Configuracion'],
                ]
                : [
                    ['id' => 'explore', 'label' => 'Explorar Portafolios'],
                    ['id' => 'filters', 'label' => 'Filtros'],
                    ['id' => 'connections', 'label' => 'Descubrimiento'],
                ],
        ];
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
