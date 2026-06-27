<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GeneradorUsuarioSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class DeveloperSettingsController extends Controller
{
    public function __construct(
        private readonly GeneradorUsuarioSync $generadorUsuarioSync
    ) {}

    public function updateAvatar(Request $request): JsonResponse
    {
        $idUsuario = $this->resolveDeveloperId($request);

        $data = $request->validate([
            'avatar' => ['nullable', 'image', 'max:5120'],
            'remove_avatar' => ['nullable', 'boolean'],
        ]);

        $removeAvatar = $data['remove_avatar'] ?? false;
        if ($removeAvatar === true || $removeAvatar === '1' || $removeAvatar === 'true') {
            DB::update(
                'UPDATE "Usuario" SET fotografia = NULL, fecha_actualizacion = ? WHERE id_usuario = ?',
                [now(), $idUsuario]
            );

            return response()->json([
                'message' => 'Avatar eliminado correctamente.',
                'url' => null,
            ]);
        }

        $file = $request->file('avatar');
        if (! $file) {
            throw ValidationException::withMessages([
                'avatar' => ['Debes seleccionar una imagen válida.'],
            ]);
        }

        DB::update(
            'UPDATE "Usuario" SET fotografia = decode(?, \'hex\'), fecha_actualizacion = ? WHERE id_usuario = ?',
            [bin2hex(file_get_contents($file->getRealPath())), now(), $idUsuario]
        );

        return response()->json([
            'message' => 'Avatar actualizado correctamente.',
            'url' => '/api/developer/files/avatar/' . $idUsuario . '?t=' . time(),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $idUsuario = $this->resolveDeveloperId($request);

        $data = $request->validate([
            'firstName' => ['required', 'string', 'max:80', 'regex:/^(?=.*\pL)[\pL\s]+$/u'],
            'lastName' => ['nullable', 'string', 'max:80', 'regex:/^(?=.*\pL)[\pL\s]+$/u'],
            'maternalLastName' => ['nullable', 'string', 'max:120', 'regex:/^(?=.*\pL)[\pL\s]+$/u'],
            'role' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:3000'],
            'contactEmail' => ['nullable', 'email', 'max:100'],
            'titleHierarchy' => ['nullable', 'array'],
            'titleHierarchy.*' => ['required', 'string', 'max:120'],
            'roleHierarchy' => ['nullable', 'array'],
            'roleHierarchy.*' => ['required', 'string', 'max:120'],
        ], [
            'firstName.regex' => 'El nombre solo puede contener letras y espacios.',
            'lastName.regex' => 'El apellido solo puede contener letras y espacios.',
            'maternalLastName.regex' => 'El apellido materno solo puede contener letras y espacios.',
        ]);

        $fullName = collect([
            $data['firstName'],
            $data['lastName'] ?? null,
            $data['maternalLastName'] ?? null,
        ])->filter(fn ($value) => filled($value))->implode(' ');

        DB::update(
            'UPDATE "Usuario"
             SET nombre_completo = ?,
                 profesion = ?,
                 biografia = ?,
                 correo_contacto = ?,
                 titulos_jerarquia_json = ?,
                 roles_jerarquia_json = ?,
                 fecha_actualizacion = ?
             WHERE id_usuario = ?',
            [
                $fullName,
                $data['role'] ?? null,
                $data['bio'] ?? null,
                $data['contactEmail'] ?? null,
                json_encode(array_values($data['titleHierarchy'] ?? []), JSON_UNESCAPED_UNICODE),
                json_encode(array_values($data['roleHierarchy'] ?? []), JSON_UNESCAPED_UNICODE),
                now(),
                $idUsuario,
            ]
        );

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
        ]);
    }

    public function updateSocialLinks(Request $request): JsonResponse
    {
        $idUsuario = $this->resolveDeveloperId($request);

        $data = $request->validate([
            'github' => ['nullable', 'url', 'max:255'],
            'linkedin' => ['nullable', 'url', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^[0-9]{8}$/'],
        ], [
            'phone.regex' => 'El telefono debe contener exactamente 8 digitos numericos.',
        ]);

        $existingUser = DB::selectOne('SELECT telefono FROM "Usuario" WHERE id_usuario = ?', [$idUsuario]);
        $phoneChanged = $existingUser && ($existingUser->telefono ?? null) !== ($data['phone'] ?? null);

        DB::update(
            'UPDATE "Usuario"
             SET telefono = ?,
                 telefono_verificacion_estado = ?,
                 telefono_verificado_at = ?,
                 fecha_actualizacion = ?
             WHERE id_usuario = ?',
            [
                $data['phone'] ?? null,
                $phoneChanged && filled($data['phone'] ?? null) ? 'sin_verificar' : (($existingUser->telefono ?? null) ? 'verificado' : 'sin_verificar'),
                $phoneChanged ? null : ($existingUser->telefono ? now() : null),
                now(),
                $idUsuario,
            ]
        );

        $this->upsertSocialLink($idUsuario, 'GitHub', $data['github'] ?? null);
        $this->upsertSocialLink($idUsuario, 'LinkedIn', $data['linkedin'] ?? null);
        $this->upsertSocialLink($idUsuario, 'Website', $data['website'] ?? null);

        return response()->json([
            'message' => $phoneChanged && filled($data['phone'] ?? null)
                ? 'Redes actualizadas. El nuevo teléfono quedó marcado como pendiente de verificación externa por WhatsApp.'
                : 'Redes actualizadas correctamente.',
            'phone_verification_status' => $phoneChanged && filled($data['phone'] ?? null) ? 'sin_verificar' : 'verificado',
        ]);
    }

    public function updateEmail(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $idUsuario = $this->resolveDeveloperId($request);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        $oldEmail = $user->email;
        $newEmail = $data['email'];

        DB::transaction(function () use ($user, $idUsuario, $oldEmail, $newEmail): void {
            $user->forceFill([
                'email' => $newEmail,
            ])->save();

            DB::update(
                'UPDATE "Usuario"
                 SET correo = ?,
                     correo_contacto = CASE
                         WHEN correo_contacto IS NULL OR correo_contacto = ? THEN ?
                         ELSE correo_contacto
                     END,
                     fecha_actualizacion = ?
                 WHERE id_usuario = ?',
                [$newEmail, $oldEmail, $newEmail, now(), $idUsuario]
            );
        });

        return response()->json([
            'message' => 'Correo actualizado correctamente.',
        ]);
    }

    public function verifyPassword(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        $isValidPassword = Hash::check($data['current_password'], $user->password)
            || hash_equals((string) $user->password, $data['current_password']);

        if (! $isValidPassword) {
            return response()->json([
                'message' => 'La contraseña actual no coincide.',
            ], 422);
        }

        return response()->json([
            'message' => 'Contraseña verificada.',
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $idUsuario = $this->resolveDeveloperId($request);

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'regex:/^\S+$/'],
        ]);

        $isValidPassword = Hash::check($data['current_password'], $user->password)
            || hash_equals((string) $user->password, $data['current_password']);

        if (! $isValidPassword) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual no coincide.'],
            ]);
        }

        $hashedPassword = Hash::make($data['new_password']);

        DB::transaction(function () use ($user, $idUsuario, $hashedPassword): void {
            $user->forceFill([
                'password' => $hashedPassword,
            ])->save();

            DB::update(
                'UPDATE "Usuario" SET contraseña_hash = ?, fecha_actualizacion = ? WHERE id_usuario = ?',
                [$hashedPassword, now(), $idUsuario]
            );
        });

        return response()->json([
            'message' => 'Contraseña actualizada correctamente.',
        ]);
    }

    public function syncHighlights(Request $request): JsonResponse
    {
        $idUsuario = $this->resolveDeveloperId($request);

        $data = $request->validate([
            'projects' => ['nullable', 'array', 'max:3'],
            'projects.*' => ['required', 'string', 'max:120'],
            'skills' => ['nullable', 'array', 'max:3'],
            'skills.*' => ['required', 'string', 'max:120'],
            'trajectory' => ['nullable', 'array', 'max:3'],
            'trajectory.*' => ['required', 'string', 'max:180'],
        ]);

        DB::update(
            'UPDATE "Usuario" SET highlights_json = ?, fecha_actualizacion = ? WHERE id_usuario = ?',
            [
                json_encode([
                    'projects' => array_values($data['projects'] ?? []),
                    'skills' => array_values($data['skills'] ?? []),
                    'trajectory' => array_values($data['trajectory'] ?? []),
                ], JSON_UNESCAPED_UNICODE),
                now(),
                $idUsuario,
            ]
        );

        return response()->json([
            'message' => 'Destacados actualizados correctamente.',
        ]);
    }

    public function updateVisibility(Request $request): JsonResponse
    {
        $idUsuario = $this->resolveDeveloperId($request);

        $data = $request->validate([
            'mode' => ['required', 'in:publico,privado,personalizado'],
            'showGeneral' => ['nullable', 'boolean'],
            'showProjects' => ['nullable', 'boolean'],
            'showSkills' => ['nullable', 'boolean'],
            'showExperience' => ['nullable', 'boolean'],
            'showFormation' => ['nullable', 'boolean'],
            'showSocialLinks' => ['nullable', 'boolean'],
            'showContact' => ['nullable', 'boolean'],
            'showEmail' => ['nullable', 'boolean'],
            'showPhone' => ['nullable', 'boolean'],
        ]);

        $config = $this->ensureVisibilityConfig($idUsuario);
        $mode = $data['mode'];

        $showGeneral = $mode === 'publico' ? true : ($mode === 'privado' ? false : (bool) ($data['showGeneral'] ?? true));
        $showProjects = $mode === 'publico' ? true : ($mode === 'privado' ? false : (bool) ($data['showProjects'] ?? true));
        $showSkills = $mode === 'publico' ? true : ($mode === 'privado' ? false : (bool) ($data['showSkills'] ?? true));
        $showExperience = $mode === 'publico' ? true : ($mode === 'privado' ? false : (bool) ($data['showExperience'] ?? true));
        $showFormation = $mode === 'publico' ? true : ($mode === 'privado' ? false : (bool) ($data['showFormation'] ?? true));
        $showSocialLinks = $mode === 'publico' ? true : ($mode === 'privado' ? false : (bool) ($data['showSocialLinks'] ?? true));
        $showContact = $mode === 'publico' ? true : ($mode === 'privado' ? false : (bool) ($data['showContact'] ?? true));
        $showEmail = $mode === 'publico' ? true : ($mode === 'privado' ? false : (bool) ($data['showEmail'] ?? true));
        $showPhone = $mode === 'publico' ? true : ($mode === 'privado' ? false : (bool) ($data['showPhone'] ?? false));

        DB::transaction(function () use (
            $idUsuario,
            $config,
            $mode,
            $showGeneral,
            $showProjects,
            $showSkills,
            $showExperience,
            $showFormation,
            $showSocialLinks,
            $showContact,
            $showEmail,
            $showPhone
        ): void {
            DB::update(
                'UPDATE "Usuario" SET visibilidad_perfil = ?, fecha_actualizacion = ? WHERE id_usuario = ?',
                [$mode, now(), $idUsuario]
            );

            DB::update(
                'UPDATE "Configuracion_Visibilidad"
                 SET modo_visibilidad = ?,
                     mostrar_informacion_general = ?,
                     mostrar_proyectos = ?,
                     mostrar_habilidades = ?,
                     mostrar_experiencia = ?,
                     mostrar_formacion = ?,
                     mostrar_redes_sociales = ?,
                     mostrar_contacto = ?,
                     mostrar_correo = ?,
                     mostrar_telefono = ?,
                     fecha_actualizacion = ?
                 WHERE id_config = ?',
                [
                    $mode,
                    $showGeneral,
                    $showProjects,
                    $showSkills,
                    $showExperience,
                    $showFormation,
                    $showSocialLinks,
                    $showContact,
                    $showEmail,
                    $showPhone,
                    now(),
                    $config->id_config,
                ]
            );
        });

        return response()->json([
            'message' => 'Configuración de visibilidad actualizada correctamente.',
        ]);
    }

    public function updateLanguage(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $idUsuario = $this->resolveDeveloperId($request);

        $data = $request->validate([
            'language' => ['required', 'in:es,en'],
        ]);

        DB::transaction(function () use ($user, $idUsuario, $data): void {
            $user->forceFill([
                'preferred_language' => $data['language'],
            ])->save();

            DB::update(
                'UPDATE "Portafolio"
                 SET idioma_principal = ?, fecha_actualizacion = ?
                 WHERE id_usuario = ?',
                [$data['language'], now(), $idUsuario]
            );
        });

        return response()->json([
            'message' => 'Idioma actualizado correctamente.',
            'language' => $data['language'],
        ]);
    }

    private function resolveDeveloperId(Request $request): int
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->role !== 'desarrollador') {
            abort(403, 'Solo desarrolladores pueden modificar esta configuración.');
        }

        return $this->generadorUsuarioSync->ensureForLaravelUser($user);
    }

    private function ensureVisibilityConfig(int $idUsuario): object
    {
        $config = DB::selectOne('SELECT * FROM "Configuracion_Visibilidad" WHERE id_usuario = ?', [$idUsuario]);

        if ($config) {
            return $config;
        }

        $inserted = DB::selectOne(
            'INSERT INTO "Configuracion_Visibilidad" (
                id_usuario,
                modo_visibilidad,
                mostrar_informacion_general,
                mostrar_proyectos,
                mostrar_habilidades,
                mostrar_experiencia,
                mostrar_formacion,
                mostrar_redes_sociales,
                mostrar_contacto,
                mostrar_correo,
                mostrar_telefono,
                permitir_descargas,
                permitir_comentarios,
                fecha_actualizacion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            RETURNING *',
            [
                $idUsuario,
                'publico',
                true,
                true,
                true,
                true,
                true,
                true,
                true,
                true,
                false,
                true,
                false,
                now(),
            ]
        );

        return $inserted;
    }

    private function upsertSocialLink(int $idUsuario, string $networkName, ?string $url): void
    {
        $existing = DB::selectOne(
            'SELECT id_red FROM "Red_Profesional" WHERE id_usuario = ? AND LOWER(nombre_red) = LOWER(?) LIMIT 1',
            [$idUsuario, $networkName]
        );

        if (! filled($url)) {
            if ($existing) {
                DB::delete('DELETE FROM "Red_Profesional" WHERE id_red = ?', [$existing->id_red]);
            }

            return;
        }

        if ($existing) {
            DB::update(
                'UPDATE "Red_Profesional"
                 SET enlace_perfil = ?, fecha_agregado = ?
                 WHERE id_red = ?',
                [$url, now(), $existing->id_red]
            );

            return;
        }

        DB::insert(
            'INSERT INTO "Red_Profesional" (id_usuario, nombre_red, enlace_perfil, fecha_agregado)
             VALUES (?, ?, ?, ?)',
            [$idUsuario, $networkName, $url, now()]
        );
    }
}
