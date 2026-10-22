<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => 'Admin',
                'password' => env('ADMIN_PASSWORD', 'password'),
            ]
        );

        if (Booking::count() === 0) {
            Booking::create([
                'customer_name' => 'Rizky Pratama',
                'facility' => 'futsal',
                'booking_date' => now()->addDays(2)->format('Y-m-d'),
                'start_time' => '19:00',
                'end_time' => '21:00',
                'status' => 'confirmed',
                'notes' => 'Sparring antar kelas',
            ]);

            Booking::create([
                'customer_name' => 'Band Kampus',
                'facility' => 'studio_musik',
                'booking_date' => now()->addDays(3)->format('Y-m-d'),
                'start_time' => '15:00',
                'end_time' => '17:00',
                'status' => 'pending',
                'notes' => 'Latihan untuk tampil di acara dies natalis',
            ]);

            Booking::create([
                'customer_name' => 'Sinta Dewi',
                'facility' => 'studio_foto',
                'booking_date' => now()->addDays(5)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '10:00',
                'status' => 'pending',
                'notes' => null,
            ]);
        }
    }
}