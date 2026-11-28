<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingCheckIn extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'checked_in_by',
        'attendee_count',
        'checked_in_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'attendee_count' => 'integer',
            'checked_in_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }
}
