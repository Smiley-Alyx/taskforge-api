<?php

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'register']);
    Route::post('login', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'me']);
        Route::post('logout', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'logout']);
    });
});
