<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Services\GeneradorUsuarioSync;
use Illuminate\Http\JsonResponse;

class DeveloperSettingsController extends Controller
{
    public function __construct(
        private readonly GeneradorUsuarioSync $generadorUsuarioSync
    ) {}

    public function updateAvatar(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'desarrollador') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $idUsuario = $this->generadorUsuarioSync->ensureForLaravelUser($user);

        if ($request->hasFile('avatar')) {
            $request->validate([
                'avatar' => 'required|image|max:5120',
            ]);
            $file = $request->file('avatar');
            $archivoBytes = file_get_contents($file->getRealPath());
            
            DB::table('Usuario')->where('id_usuario', $idUsuario)->update([
                'fotografia' => DB::raw("decode('".base64_encode($archivoBytes)."', 'base64')"),
                'fecha_actualizacion' => now(),
            ]);
        } elseif ($request->input('remove_avatar')) {
            DB::table('Usuario')->where('id_usuario', $idUsuario)->update([
                'fotografia' => null,
                'fecha_actualizacion' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Avatar actualizado exitosamente.',
            'url' => '/api/developer/files/avatar/' . $idUsuario . '?t=' . time()
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $idUsuario = $this->generadorUsuarioSync->ensureForLaravelUser($user);

        $data = $request->validate([
            'firstName'        => 'required|string|max:100',
            'lastName'         => 'nullable|string|max:100',
            'maternalLastName' => 'nullable|string|max:100',
            'role'             => 'nullable|string|max:150',
            'bio'              => 'nullable|string',
        ]);

        $fullName = trim($data['firstName'] . ' ' . ($data['lastName'] ?? '') . ' ' . ($data['maternalLastName'] ?? ''));

        DB::table('Usuario')->where('id_usuario', $idUsuario)->update([
            'nombre_completo'    => $fullName,
            'profesion'          => $data['role'] ?? null,
            'biografia'          => $data['bio'] ?? null,
            'fecha_actualizacion'=> now(),
        ]);

        return response()->json(['message' => 'Perfil actualizado exitosamente.']);
    }

    public function updateSocialLinks(Request $request): JsonResponse
    {
        $user = $request->user();
        $idUsuario = $this->generadorUsuarioSync->ensureForLaravelUser($user);

        $data = $request->validate([
            'github'   => 'nullable|string|max:255',
            'linkedin' => 'nullable|string|max:255',
            'website'  => 'nullable|string|max:255',
            'phone'    => 'nullable|string|max:50',
        ]);

        // Update phone on Usuario table
        DB::table('Usuario')->where('id_usuario', $idUsuario)->update([
            'telefono'           => $data['phone'] ?? null,
            'fecha_actualizacion'=> now(),
        ]);

        // Upsert each social network
        $redes = [
            'github'   => $data['github']   ?? null,
            'linkedin' => $data['linkedin'] ?? null,
            'website'  => $data['website']  ?? null,
        ];

        foreach ($redes as $nombre => $enlace) {
            $existing = DB::table('Red_Profesional')
                ->where('id_usuario', $idUsuario)
                ->whereRaw('LOWER(nombre_red) = ?', [strtolower($nombre)])
                ->first();

            if ($enlace !== null && $enlace !== '') {
                if ($existing) {
                    DB::table('Red_Profesional')->where('id_red', $existing->id_red)->update([
                        'enlace_perfil'  => $enlace,
                        'fecha_agregado' => now(),
                    ]);
                } else {
                    DB::table('Red_Profesional')->insert([
                        'id_usuario'     => $idUsuario,
                        'nombre_red'     => $nombre,
                        'enlace_perfil'  => $enlace,
                        'fecha_agregado' => now(),
                    ]);
                }
            } else {
                // Remove row if value was cleared
                if ($existing) {
                    DB::table('Red_Profesional')->where('id_red', $existing->id_red)->delete();
                }
            }
        }

        return response()->json(['message' => 'Redes sociales actualizadas.']);
    }

    public function verifyPassword(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate(['current_password' => 'required|string']);
        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'Contraseña incorrecta.'], 422);
        }
        return response()->json(['message' => 'OK']);
    }

    public function updateEmail(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->email = $data['email'];
        $user->save();

        return response()->json(['message' => 'Correo actualizado.']);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8',
        ]);

        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'La contraseña actual no es correcta.'], 422);
        }

        $user->password = Hash::make($data['new_password']);
        $user->save();

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }

    public function syncHighlights(Request $request): JsonResponse
    {
        $user = $request->user();
        $idUsuario = $this->generadorUsuarioSync->ensureForLaravelUser($user);

        $data = $request->validate([
            'projects'   => 'nullable|array',
            'skills'     => 'nullable|array',
            'trajectory' => 'nullable|array',
        ]);

        $payload = [
            'projects'   => $data['projects']   ?? [],
            'skills'     => $data['skills']     ?? [],
            'trajectory' => $data['trajectory'] ?? [],
        ];

        DB::table('Usuario')->where('id_usuario', $idUsuario)->update([
            'highlights_json'     => json_encode($payload),
            'fecha_actualizacion' => now(),
        ]);

        return response()->json(['message' => 'Destacados de visibilidad actualizados.']);
    }
}
