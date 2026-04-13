<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class GeneradorUsuarioSync
{
    /**
     * Asegura una fila en la tabla "Usuario" (esquema Generador CV) alineada con users.email.
     *
     * @return int id_usuario
     */
    public function ensureForLaravelUser(User $user): int
    {
        $id = DB::table('Usuario')
            ->where('correo', $user->email)
            ->value('id_usuario');

        if ($id !== null) {
            return (int) $id;
        }

        return (int) DB::table('Usuario')->insertGetId([
            'nombre_completo' => $user->name,
            'correo' => $user->email,
            'contraseña_hash' => $user->password,
            'estado_perfil' => 'activo',
            'visibilidad_perfil' => 'publico',
        ], 'id_usuario');
    }
}
