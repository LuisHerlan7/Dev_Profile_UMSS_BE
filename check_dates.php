<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('Evidencia_Digital')->get();
foreach ($rows as $r) {
    echo "ID: {$r->id_evidencia} | Fecha: " . ($r->fecha_carga ?? 'NULL') . " | URL: " . ($r->url_enlace ?? 'NULL') . "\n";
}
