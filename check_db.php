<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$users = DB::select('SELECT id_usuario, correo, fotografia IS NOT NULL as has_photo FROM "Usuario"');
print_r($users);
