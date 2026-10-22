<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_credentials_returns_token(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ])
            ->assertStatus(200)
            ->assertJsonStructure(['message', 'token', 'token_type', 'user' => ['id', 'name', 'email']]);
    }

    public function test_login_with_wrong_password_returns_401(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'salah',
        ])
            ->assertStatus(401)
            ->assertJson(['message' => 'Email atau password salah.']);
    }

    public function test_login_without_fields_returns_422(): void
    {
        $this->postJson('/api/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_logout_revokes_the_token(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $token = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ])->json('token');

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertStatus(200)
            ->assertJson(['message' => 'Logout berhasil.']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}