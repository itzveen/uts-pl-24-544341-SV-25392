<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckInBookingRequest;
use App\Http\Resources\BookingCheckInResource;
use App\Models\Booking;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class BookingCheckInController extends Controller
{
    public function store(CheckInBookingRequest $request, Booking $booking): JsonResponse
    {
        if ($booking->checkIn()->exists()) {
            return response()->json([
                'message' => 'Booking ini sudah melakukan check-in.',
            ], 409);
        }

        try {
            $checkIn = $booking->checkIn()->create([
                'checked_in_by' => $request->user()->id,
                'attendee_count' => $request->validated('attendee_count'),
                'checked_in_at' => now(),
                'notes' => $request->validated('notes'),
            ]);
        } catch (UniqueConstraintViolationException) {
            return response()->json([
                'message' => 'Booking ini sudah melakukan check-in.',
            ], 409);
        }

        Log::info('booking.check_in', [
            'user_id' => $request->user()->id,
            'booking_id' => $booking->id,
            'attendee_count' => $checkIn->attendee_count,
        ]);

        return (new BookingCheckInResource($checkIn))
            ->response()
            ->setStatusCode(201);
    }
}
