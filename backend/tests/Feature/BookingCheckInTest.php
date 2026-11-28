<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingCheckInTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    private function confirmedBooking(array $overrides = []): Booking
    {
        return Booking::factory()->create(array_merge([
            'booking_date' => '2026-12-01',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'status' => 'confirmed',
        ], $overrides));
    }

    public function test_check_in_requires_authentication(): void
    {
        $booking = $this->confirmedBooking();

        $this->postJson("/api/bookings/{$booking->id}/check-in", [
            'attendee_count' => 5,
        ])
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_check_in_succeeds_for_confirmed_booking_within_allowed_window(): void
    {
        $user = $this->signIn();
        $this->travelTo(Carbon::parse('2026-12-01 09:30:00'));

        $booking = $this->confirmedBooking();

        $this->postJson("/api/bookings/{$booking->id}/check-in", [
            'attendee_count' => 8,
            'notes' => 'Tim datang lengkap',
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.booking_id', $booking->id)
            ->assertJsonPath('data.checked_in_by', $user->id)
            ->assertJsonPath('data.attendee_count', 8)
            ->assertJsonPath('data.notes', 'Tim datang lengkap');

        $this->assertDatabaseHas('booking_check_ins', [
            'booking_id' => $booking->id,
            'checked_in_by' => $user->id,
            'attendee_count' => 8,
            'notes' => 'Tim datang lengkap',
        ]);
    }

    public function test_check_in_is_rejected_when_booking_status_is_not_confirmed(): void
    {
        $this->signIn();
        $this->travelTo(Carbon::parse('2026-12-01 10:15:00'));

        $pending = $this->confirmedBooking(['status' => 'pending']);
        $cancelled = $this->confirmedBooking(['status' => 'cancelled']);

        $this->postJson("/api/bookings/{$pending->id}/check-in", ['attendee_count' => 4])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['booking']);

        $this->postJson("/api/bookings/{$cancelled->id}/check-in", ['attendee_count' => 4])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['booking']);
    }

    public function test_check_in_is_rejected_outside_allowed_time_window(): void
    {
        $this->signIn();
        $booking = $this->confirmedBooking();

        // Terlalu awal: 31 menit sebelum start_time (09:29 < 09:30)
        $this->travelTo(Carbon::parse('2026-12-01 09:29:00'));
        $this->postJson("/api/bookings/{$booking->id}/check-in", ['attendee_count' => 4])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['booking']);

        // Sudah lewat: tepat pada end_time (12:00)
        $this->travelTo(Carbon::parse('2026-12-01 12:00:00'));
        $this->postJson("/api/bookings/{$booking->id}/check-in", ['attendee_count' => 4])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['booking']);

        // Tanggal berbeda
        $this->travelTo(Carbon::parse('2026-11-30 10:00:00'));
        $this->postJson("/api/bookings/{$booking->id}/check-in", ['attendee_count' => 4])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['booking']);
    }

    public function test_duplicate_check_in_is_rejected_with_409(): void
    {
        $this->signIn();
        $this->travelTo(Carbon::parse('2026-12-01 10:00:00'));

        $booking = $this->confirmedBooking();

        $this->postJson("/api/bookings/{$booking->id}/check-in", ['attendee_count' => 6])
            ->assertStatus(201);

        $this->postJson("/api/bookings/{$booking->id}/check-in", ['attendee_count' => 6])
            ->assertStatus(409)
            ->assertJson(['message' => 'Booking ini sudah melakukan check-in.']);
    }

    public function test_check_in_validates_attendee_count(): void
    {
        $this->signIn();
        $this->travelTo(Carbon::parse('2026-12-01 10:00:00'));

        $booking = $this->confirmedBooking();

        $this->postJson("/api/bookings/{$booking->id}/check-in", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['attendee_count']);

        $this->postJson("/api/bookings/{$booking->id}/check-in", ['attendee_count' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['attendee_count']);

        $this->postJson("/api/bookings/{$booking->id}/check-in", ['attendee_count' => 51])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['attendee_count']);
    }

    public function test_check_in_returns_404_for_missing_booking(): void
    {
        $this->signIn();

        $this->postJson('/api/bookings/999999/check-in', ['attendee_count' => 4])
            ->assertStatus(404);
    }
}
