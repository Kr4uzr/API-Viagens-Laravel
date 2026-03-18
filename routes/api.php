<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TravelOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:api')->group(function () {
    Route::apiResource('travel-orders', TravelOrderController::class)->only(['index', 'store', 'show']);
    Route::patch('travel-orders/{travel_order}', [TravelOrderController::class, 'updateDetails']);
    Route::patch('travel-orders/{travel_order}/status', [TravelOrderController::class, 'updateStatus'])
        ->name('travel-orders.update-status');
});
