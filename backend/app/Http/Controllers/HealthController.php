<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->periksaDatabase(),
            'cache' => $this->periksaCache(),
        ];

        $sehat = collect($checks)->every(fn (array $c) => $c['status'] === 'ok');

        return response()->json([
            'status' => $sehat ? 'healthy' : 'unhealthy',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $sehat ? 200 : 503);
    }

    /**
     * Benar-benar menjalankan query ke database. Alasan di respons dibuat
     * generik supaya tidak membocorkan host, kredensial, atau SQL.
     */
    private function periksaDatabase(): array
    {
        try {
            DB::select('select 1');

            return ['status' => 'ok'];
        } catch (Throwable $e) {
            Log::error('database.gagal_terhubung', [
                'user_id' => null,
                'aksi' => 'health check',
                'kelas_galat' => get_class($e),
                'kode_galat' => $e->getCode(),
                'penyebab' => 'database tidak dapat diakses saat health check',
            ]);

            return [
                'status' => 'fail',
                'reason' => 'database tidak dapat diakses',
            ];
        }
    }

    /**
     * Menulis lalu membaca kunci sementara (TTL pendek) di cache.
     */
    private function periksaCache(): array
    {
        try {
            $kunci = 'health:'.Str::random(12);
            $nilai = Str::random(8);

            Cache::put($kunci, $nilai, 10);
            $terbaca = Cache::get($kunci);
            Cache::forget($kunci);

            if ($terbaca !== $nilai) {
                throw new \RuntimeException('nilai cache tidak cocok');
            }

            return ['status' => 'ok'];
        } catch (Throwable $e) {
            Log::error('cache.gagal_diakses', [
                'user_id' => null,
                'aksi' => 'health check',
                'kelas_galat' => get_class($e),
                'kode_galat' => $e->getCode(),
                'penyebab' => 'cache tidak dapat ditulis atau dibaca saat health check',
            ]);

            return [
                'status' => 'fail',
                'reason' => 'cache tidak dapat diakses',
            ];
        }
    }
}