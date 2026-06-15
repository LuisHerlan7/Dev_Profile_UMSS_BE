<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterController;

Route::get('/', function () {
    return view('index');
});

Route::post('/register', [RegisterController::class, 'register']);