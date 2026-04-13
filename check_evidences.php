<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$userEmail = 'test@test.com'; // Adjust if subagent confirmed another

$user = DB::table('users')->where('email', $userEmail)->first();
if (!$user) {
    echo "User $userEmail not found in 'users' table\n";
    exit;
}

$usuario = DB::table('Usuario')->where('correo', $userEmail)->first();
if (!$usuario) {
    echo "User $userEmail not found in 'Usuario' table\n";
    exit;
}

$count = DB::table('Evidencia_Digital')->where('id_usuario', $usuario->id_usuario)->count();
echo "ID_USUARIO: {$usuario->id_usuario}\n";
echo "EVIDENCIAS_COUNT: {$count}\n";

$rows = DB::table('Evidencia_Digital')
    ->leftJoin('Proyecto', 'Evidencia_Digital.id_proyecto', '=', 'Proyecto.id_proyecto')
    ->where('Evidencia_Digital.id_usuario', $usuario->id_usuario)
    ->select('Evidencia_Digital.*', 'Proyecto.nombre_proyecto')
    ->get();

foreach ($rows as $r) {
    echo " - ID: {$r->id_evidencia} | Title: {$r->titulo} | Project: {$r->nombre_proyecto} | Status: {$r->estado_revision}\n";
}
