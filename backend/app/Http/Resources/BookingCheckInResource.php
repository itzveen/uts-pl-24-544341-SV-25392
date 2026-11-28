<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingCheckInResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'checked_in_by' => $this->checked_in_by,
            'attendee_count' => $this->attendee_count,
            'checked_in_at' => $this->checked_in_at?->toISOString(),
            'notes' => $this->notes,
        ];
    }
}
