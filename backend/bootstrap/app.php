<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API ini tidak punya halaman login (route bernama "login"),
        // jadi tamu tanpa token tidak boleh diarahkan ke mana pun.
        $middleware->redirectGuestsTo(fn (Request $request) => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        // Log error untuk galat database. ->stop() mencegah log bawaan Laravel,
        // karena log bawaan memuat pesan SQL dan bindings (bisa berisi data pribadi).
        $exceptions->report(function (QueryException $e) {
            // Kode driver MySQL untuk koneksi gagal: 2002/2003 (tidak terjangkau),
            // 2006/2013 (server hilang). Pesan galat sengaja tidak dicatat.
            $kodeDriver = $e->errorInfo[1] ?? null;
            $gagalTerhubung = in_array($kodeDriver, [2002, 2003, 2006, 2013], true);

            $request = request();

            Log::error($gagalTerhubung ? 'database.gagal_terhubung' : 'database.query_gagal', [
                // hasUser() tidak melakukan query, jadi aman saat database mati.
                'user_id' => Auth::guard('sanctum')->hasUser() ? Auth::guard('sanctum')->id() : null,
                'aksi' => $request->method().' '.($request->route()?->uri() ?? $request->path()),
                'kelas_galat' => get_class($e),
                'kode_galat' => $e->getCode(),
                'kode_driver' => $kodeDriver,
                'penyebab' => $gagalTerhubung
                    ? 'koneksi ke database gagal'
                    : 'query ke database gagal',
            ]);
        })->stop();
    })->create();