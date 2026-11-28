<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingCheckInController;
use App\Http\Controllers\Api\BookingController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/facilities', [BookingController::class, 'facilities']);
    Route::post('/bookings/{booking}/check-in', [BookingCheckInController::class, 'store']);
    Route::apiResource('bookings', BookingController::class);
});