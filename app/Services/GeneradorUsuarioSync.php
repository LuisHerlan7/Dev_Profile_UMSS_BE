<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class GeneradorUsuarioSync
{
    public function ensureForLaravelUser(User $user): int
    {
        $existing = DB::table('Usuario')->where('correo', $user->email)->first();

        if ($existing) {
            return (int) $existing->id_usuario;
        }

        return (int) DB::table('Usuario')->insertGetId([
            'nombre_completo' => $user->name,
            'correo' => $user->email,
            // The legacy schema requires this field to be NOT NULL.
            'contraseña_hash' => (string) ($user->password ?? ''),
            'estado_perfil' => 'activo',
            'visibilidad_perfil' => 'publico',
            'fecha_creacion' => now(),
            'fecha_actualizacion' => now(),
        ], 'id_usuario');
    }
}
