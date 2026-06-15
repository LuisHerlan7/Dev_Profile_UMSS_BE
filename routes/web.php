<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterController;

Route::get('/{any}', function () {
    return view('index');
})->where('any', '.*');

Route::post('/register', [RegisterController::class, 'register']);