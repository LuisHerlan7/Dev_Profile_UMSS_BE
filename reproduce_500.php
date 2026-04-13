<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Simulate getAvatar for id = 2 (the test user)
$id = 2;
$fileRow = DB::selectOne('SELECT fotografia as archivo_evidencia FROM "Usuario" WHERE id_usuario = ?', [$id]);

if (!$fileRow) {
    echo "No row found for user $id\n";
    exit;
}

if (!$fileRow->archivo_evidencia) {
    echo "No fotografia found for user $id\n";
    // Check if it's really null or something else
    var_dump($fileRow->archivo_evidencia);
    exit;
}

echo "Found fotografia. Length/Type information:\n";
$content = $fileRow->archivo_evidencia;
echo "Type: " . gettype($content) . "\n";

if (is_resource($content)) {
    echo "Content is a resource. Extracting...\n";
    $content = stream_get_contents($content);
}

echo "Content string length: " . strlen($content) . "\n";
if (strlen($content) > 10) {
    echo "First 10 chars: " . bin2hex(substr($content, 0, 10)) . "\n";
    echo "Raw prefix check (\\x): " . (strpos($content, '\x') === 0 ? "YES" : "NO") . "\n";
}

// Target the logic in serveFile
try {
    if (strpos($content, '\x') === 0) {
        echo "Attempting hex2bin...\n";
        $hex = substr($content, 2);
        if (strlen($hex) % 2 !== 0) {
            echo "ERROR: Hex string has ODD length: " . strlen($hex) . "\n";
        }
        $decoded = hex2bin($hex);
        echo "Success! Decoded length: " . strlen($decoded) . "\n";
    } else {
        echo "No \\x prefix found. Using raw content.\n";
    }
} catch (Throwable $e) {
    echo "EXCEPTION caught: " . $e->getMessage() . "\n";
}
