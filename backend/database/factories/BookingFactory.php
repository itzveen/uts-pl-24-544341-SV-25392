<?php

namespace Database\Factories;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->numberBetween(8, 20);

        return [
            'customer_name' => fake()->name(),
            'facility' => fake()->randomElement(Booking::FACILITIES),
            'booking_date' => now()->addDays(fake()->numberBetween(1, 30))->format('Y-m-d'),
            'start_time' => sprintf('%02d:00', $start),
            'end_time' => sprintf('%02d:00', $start + 1),
            'status' => 'pending',
            'notes' => null,
        ];
    }
}