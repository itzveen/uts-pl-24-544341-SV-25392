<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    public const FACILITIES = ['futsal', 'badminton', 'basket', 'studio_musik', 'studio_foto'];

    public const STATUSES = ['pending', 'confirmed', 'cancelled'];

    protected $fillable = [
        'customer_name',
        'facility',
        'booking_date',
        'start_time',
        'end_time',
        'status',
        'notes',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];
}