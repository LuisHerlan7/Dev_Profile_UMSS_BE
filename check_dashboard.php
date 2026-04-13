<?php

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Http\Controllers\DeveloperDashboardController;
use Illuminate\Http\Request;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = User::where('email', 'test.user@umss.edu')->first();
if (!$user) {
    echo "User test.user@umss.edu not found\n";
    exit;
}

$request = Request::create('/api/developer/dashboard', 'GET');
$request->setUserResolver(function () use ($user) {
    return $user;
});

$controller = new DeveloperDashboardController($app->make(App\Services\GeneradorUsuarioSync::class));
$response = $controller->index($request);

echo "Dashboard Response:\n";
print_r(json_decode($response->getContent(), true));
