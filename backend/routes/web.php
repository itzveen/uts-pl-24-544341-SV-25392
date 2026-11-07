<?php

use App\Http\Controllers\HealthController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::get('/', function () {
    return view('welcome');
});

// Endpoint publik untuk healthcheck. Session, cookie, dan proteksi CSRF
// dimatikan agar pemeriksaan berkala tidak membuat berkas session baru
// di setiap request (CSRF butuh session, jadi harus ikut dimatikan).
Route::get('/health', HealthController::class)
    ->withoutMiddleware([
        StartSession::class,
        ShareErrorsFromSession::class,
        AddQueuedCookiesToResponse::class,
        EncryptCookies::class,
        PreventRequestForgery::class,
    ]);