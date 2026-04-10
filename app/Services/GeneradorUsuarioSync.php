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
        $row = DB::selectOne('SELECT id_usuario FROM "Usuario" WHERE correo = ?', [$user->email]);
        if ($row !== null) {
            return (int) $row->id_usuario;
        }

        $inserted = DB::selectOne(
            'INSERT INTO "Usuario" (nombre_completo, correo, contraseña_hash, fecha_creacion)
             VALUES (?, ?, ?, CURRENT_TIMESTAMP)
             RETURNING id_usuario',
            [$user->name, $user->email, $user->getAttributes()['password']]
        );

        return (int) $inserted->id_usuario;
    }
}
