<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- USERS TABLE ---\n";
$users = DB::table('users')->get();
foreach ($users as $u) {
    echo "ID: {$u->id} | Name: {$u->name} | Email: {$u->email} | Role: {$u->role}\n";
}

echo "\n--- USUARIO TABLE ---\n";
$usuarios = DB::table('Usuario')->get();
foreach ($usuarios as $u) {
    echo "ID_USU: {$u->id_usuario} | Email: {$u->correo} | Name: {$u->nombre_completo}\n";
}
