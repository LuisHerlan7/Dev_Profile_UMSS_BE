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
        $validator = Validator::make($request->all(), User::validationRules());

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'desarrollador',
        ]);

        $exists = DB::table('Usuario')->where('correo', $user->email)->exists();
        if (! $exists) {
            DB::table('Usuario')->insert([
                'nombre_completo' => $user->name,
                'correo' => $user->email,
                'contraseña_hash' => $user->password,
                'estado_perfil' => 'activo',
                'visibilidad_perfil' => 'publico',
            ]);
        }

        return response()->json(['message' => 'User registered successfully', 'user' => $user], 201);
    }
}
