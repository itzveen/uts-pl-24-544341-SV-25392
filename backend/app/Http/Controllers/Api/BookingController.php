<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

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

        Log::info('booking.dibuat', $this->konteks($request, 'membuat booking', $booking));

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

        Log::info('booking.diubah', $this->konteks($request, 'mengubah booking', $booking));

        return new BookingResource($booking);
    }

    public function destroy(Request $request, Booking $booking): JsonResponse
    {
        $konteks = $this->konteks($request, 'menghapus booking', $booking);

        $booking->delete();

        Log::info('booking.dihapus', $konteks);

        return response()->json(['message' => 'Booking berhasil dihapus.']);
    }

    public function facilities(): JsonResponse
    {
        return response()->json([
            'data' => Booking::FACILITIES,
            'statuses' => Booking::STATUSES,
        ]);
    }

    /**
     * Konteks log: siapa (user_id), mengerjakan apa (aksi), dan data booking
     * non-pribadi saja. customer_name dan notes sengaja TIDAK dicatat.
     */
    private function konteks(Request $request, string $aksi, Booking $booking): array
    {
        return [
            'user_id' => $request->user()?->id,
            'aksi' => $aksi,
            'booking_id' => $booking->id,
            'facility' => $booking->facility,
            'booking_date' => Carbon::parse($booking->booking_date)->toDateString(),
        ];
    }
}