<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookingController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $bookings = Booking::query()
            ->when($request->query('facility'), fn ($q, $v) => $q->where('facility', $v))
            ->when($request->query('date'), fn ($q, $v) => $q->where('booking_date', $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->get();

        return BookingResource::collection($bookings);
    }

    public function store(BookingRequest $request): JsonResponse
    {
        $booking = Booking::create($request->validated());

        return (new BookingResource($booking))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Booking $booking): BookingResource
    {
        return new BookingResource($booking);
    }

    public function update(BookingRequest $request, Booking $booking): BookingResource
    {
        $booking->update($request->validated());

        return new BookingResource($booking);
    }

    public function destroy(Booking $booking): JsonResponse
    {
        $booking->delete();

        return response()->json(['message' => 'Booking berhasil dihapus.']);
    }

    public function facilities(): JsonResponse
    {
        return response()->json([
            'data' => Booking::FACILITIES,
            'statuses' => Booking::STATUSES,
        ]);
    }
}