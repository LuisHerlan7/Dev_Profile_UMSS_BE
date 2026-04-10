<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'firstName' => 'required|string|max:100',
            'lastName' => 'nullable|string|max:100',
            'maternalLastName' => 'nullable|string|max:100',
            'role' => 'nullable|string|max:150',
            'bio' => 'nullable|string',
        ]);

        $fullName = trim($data['firstName'] . ' ' . ($data['lastName'] ?? '') . ' ' . ($data['maternalLastName'] ?? ''));

        DB::table('Usuario')->where('id_usuario', $idUsuario)->update([
            'nombre_completo' => $fullName,
            'profesion' => $data['role'] ?? null,
            'biografia' => $data['bio'] ?? null,
            'fecha_actualizacion' => now(),
        ]);

        return response()->json(['message' => 'Perfil actualizado exitosamente.']);
    }
}
