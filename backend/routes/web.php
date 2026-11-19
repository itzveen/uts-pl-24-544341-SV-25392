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

// Endpoint metrik dalam format teks Prometheus untuk memantau request per detik.
Route::get('/metrics', function () {
    $file = storage_path('framework/cache/prometheus_metrics.json');
    $data = [];

    if (is_file($file)) {
        $fp = @fopen($file, 'r');
        if ($fp && flock($fp, LOCK_SH)) {
            $data = json_decode(stream_get_contents($fp), true) ?: [];
            flock($fp, LOCK_UN);
        }
        if ($fp) {
            fclose($fp);
        }
    }

    $lines = [
        '# HELP booking_http_requests_total Total permintaan HTTP yang masuk ke aplikasi BookingAPI.',
        '# TYPE booking_http_requests_total counter',
    ];

    if (empty($data)) {
        $lines[] = 'booking_http_requests_total{method="GET",path="/metrics",status="200"} 1';
    } else {
        foreach ($data as $key => $count) {
            [$method, $path, $status] = array_pad(explode('|', $key, 3), 3, 'unknown');
            $safePath = addslashes($path);
            $lines[] = sprintf(
                'booking_http_requests_total{method="%s",path="%s",status="%s"} %d',
                $method,
                $safePath,
                $status,
                (int) $count
            );
        }
    }

    return response(implode("\n", $lines)."\n", 200, [
        'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
    ]);
})->withoutMiddleware([
    StartSession::class,
    ShareErrorsFromSession::class,
    AddQueuedCookiesToResponse::class,
    EncryptCookies::class,
    PreventRequestForgery::class,
]);