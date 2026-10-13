<?php

use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

Route::get('session', [SessionController::class, 'show']);
Route::post('login', [SessionController::class, 'store'])->middleware('throttle:5,1');

Route::middleware('auth')->group(function () {
    Route::post('logout', [SessionController::class, 'destroy']);
});
