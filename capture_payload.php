<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Http\Controllers\DeveloperDashboardController;
use App\Models\User;
use Illuminate\Support\Facades\DB;

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = User::where('role', 'desarrollador')->first();
if (!$user) die("No user\n");

$request = new \Illuminate\Http\Request();
$request->setUserResolver(fn() => $user);

$controller = app(DeveloperDashboardController::class);
$response = $controller->index($request);

file_put_contents('debug_payload.json', $response->getContent());
echo "Payload saved to debug_payload.json\n";
