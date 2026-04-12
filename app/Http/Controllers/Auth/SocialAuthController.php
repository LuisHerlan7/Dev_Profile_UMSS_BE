<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;

class SocialAuthController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function callback(string $provider): JsonResponse|RedirectResponse
    {
        $socialUser = Socialite::driver($provider)->stateless()->user();

        $email = $socialUser->getEmail();
        $providerId = (string) $socialUser->getId();

        if (! $email) {
            return response()->json([
                'message' => 'No se pudo obtener el correo del proveedor social.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var User $user */
        $user = User::query()
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->orWhere('email', $email)
            ->first() ?? new User();

        $user->fill([
            'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: $email,
            'email' => $email,
            'provider' => $provider,
            'provider_id' => $providerId,
            'avatar' => $socialUser->getAvatar(),
        ]);

        if (! $user->exists || ! $user->password) {
            $user->password = Str::password(40);
        }

        if (! $user->role) {
            $user->role = 'desarrollador';
        }

        $user->save();

        $this->ensureCvProfile($user);

        $token = $user->createToken($provider.'_oauth_token')->plainTextToken;
        $frontendUrl = rtrim((string) config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');
        $redirectUrl = $frontendUrl.'/auth/callback?token='.urlencode($token);

        return redirect()->away($redirectUrl);
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
            'contraseña_hash' => $user->password,
            'estado_perfil' => 'activo',
            'visibilidad_perfil' => 'publico',
        ]);
    }
}
