<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): void
    {
        Sanctum::actingAs(User::factory()->create());
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Budi Santoso',
            'facility' => 'futsal',
            'booking_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'notes' => 'Latihan rutin',
        ], $overrides);
    }

    public function test_endpoints_require_token_and_return_401_json(): void
    {
        $this->getJson('/api/bookings')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);

        $this->postJson('/api/bookings', $this->payload())->assertStatus(401);
    }

    public function test_unauthenticated_request_without_accept_header_returns_401(): void
    {
        // Meniru klien seperti Postman/browser yang tidak mengirim header Accept: application/json
        $this->get('/api/bookings')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_can_create_booking_and_returns_201(): void
    {
        $this->signIn();

        $this->postJson('/api/bookings', $this->payload())
            ->assertStatus(201)
            ->assertJsonPath('data.facility', 'futsal')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.start_time', '10:00');

        $this->assertDatabaseHas('bookings', [
            'customer_name' => 'Budi Santoso',
            'facility' => 'futsal',
        ]);
    }

    public function test_create_with_invalid_data_returns_422(): void
    {
        $this->signIn();

        $this->postJson('/api/bookings', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'customer_name', 'facility', 'booking_date', 'start_time', 'end_time',
            ]);

        $this->postJson('/api/bookings', $this->payload(['facility' => 'renang']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['facility']);

        $this->postJson('/api/bookings', $this->payload(['start_time' => '14:00', 'end_time' => '13:00']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['end_time']);

        $this->postJson('/api/bookings', $this->payload(['booking_date' => now()->subDay()->format('Y-m-d')]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['booking_date']);
    }

    public function test_overlapping_booking_is_rejected_but_adjacent_one_is_allowed(): void
    {
        $this->signIn();

        $date = now()->addDay()->format('Y-m-d');

        Booking::factory()->create([
            'facility' => 'futsal',
            'booking_date' => $date,
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);

        // Bentrok: 11:00-13:00 beririsan dengan 10:00-12:00
        $this->postJson('/api/bookings', $this->payload([
            'booking_date' => $date,
            'start_time' => '11:00',
            'end_time' => '13:00',
        ]))->assertStatus(422)->assertJsonValidationErrors(['start_time']);

        // Bersebelahan: 12:00-13:00 boleh
        $this->postJson('/api/bookings', $this->payload([
            'booking_date' => $date,
            'start_time' => '12:00',
            'end_time' => '13:00',
        ]))->assertStatus(201);

        // Fasilitas lain di jam yang sama boleh
        $this->postJson('/api/bookings', $this->payload([
            'facility' => 'basket',
            'booking_date' => $date,
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]))->assertStatus(201);
    }

    public function test_can_list_and_show_bookings(): void
    {
        $this->signIn();

        $booking = Booking::factory()->create();
        Booking::factory()->count(2)->create();

        $this->getJson('/api/bookings')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');

        $this->getJson("/api/bookings/{$booking->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $booking->id);

        $this->getJson('/api/bookings/999999')->assertStatus(404);
    }

    public function test_can_update_booking(): void
    {
        $this->signIn();

        $booking = Booking::factory()->create([
            'booking_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);

        // Mengubah booking sendiri dengan jam yang sama tidak dianggap bentrok
        $this->putJson("/api/bookings/{$booking->id}", $this->payload([
            'booking_date' => $booking->booking_date,
            'customer_name' => 'Nama Baru',
            'status' => 'confirmed',
        ]))
            ->assertStatus(200)
            ->assertJsonPath('data.customer_name', 'Nama Baru')
            ->assertJsonPath('data.status', 'confirmed');
    }

    public function test_can_delete_booking(): void
    {
        $this->signIn();

        $booking = Booking::factory()->create();

        $this->deleteJson("/api/bookings/{$booking->id}")
            ->assertStatus(200)
            ->assertJson(['message' => 'Booking berhasil dihapus.']);

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }
}