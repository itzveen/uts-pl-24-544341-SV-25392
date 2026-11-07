<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_returns_200_when_database_and_cache_ok(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertJson([
                'status' => 'healthy',
                'checks' => [
                    'database' => ['status' => 'ok'],
                    'cache' => ['status' => 'ok'],
                ],
            ]);
    }

    public function test_health_returns_503_when_database_fails(): void
    {
        DB::shouldReceive('select')->andThrow(new RuntimeException('db mati'));

        $this->getJson('/health')
            ->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
                'checks' => [
                    'database' => [
                        'status' => 'fail',
                        'reason' => 'database tidak dapat diakses',
                    ],
                ],
            ]);
    }
}