<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$id = 2;
try {
    $fileRow = DB::selectOne('SELECT fotografia as archivo_evidencia FROM "Usuario" WHERE id_usuario = ?', [$id]);
    
    if (!$fileRow) {
        throw new Exception("Row not found");
    }
    
    $content = $fileRow->archivo_evidencia;
    if (is_resource($content)) {
        $content = stream_get_contents($content);
    }
    
    echo "Size: " . strlen($content) . "\n";
    
    // Simulate serveFile headers/response
    $headers = [
        'Content-Type' => 'image/jpeg',
    ];
    
    // This part might fail if it's too large or something
    $response = response($content, 200, $headers);
    echo "Response created successfully\n";
    
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
