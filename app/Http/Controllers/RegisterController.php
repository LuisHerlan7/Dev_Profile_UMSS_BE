<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            User::validationRules(),
            User::validationMessages()
        );

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $hashedPassword = Hash::make($request->password);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $hashedPassword,
            'role' => 'desarrollador',
        ]);

        $exists = DB::table('Usuario')->where('correo', $user->email)->exists();
        if (! $exists) {
            DB::table('Usuario')->insert([
                'nombre_completo' => $user->name,
                'correo' => $user->email,
                'contraseña_hash' => $hashedPassword,
                'estado_perfil' => 'activo',
                'visibilidad_perfil' => 'publico',
                'nivel_experiencia' => 'junior',
                'fecha_creacion' => now(),
                'fecha_actualizacion' => now(),
            ]);
        }

        return response()->json(['message' => 'Usuario registrado correctamente.', 'user' => $user], 201);
    }
}
