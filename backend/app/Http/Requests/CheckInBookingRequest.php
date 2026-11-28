<?php

namespace App\Http\Requests;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class CheckInBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attendee_count' => ['required', 'integer', 'min:1', 'max:50'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'attendee_count.required' => 'Jumlah peserta wajib diisi.',
            'attendee_count.integer' => 'Jumlah peserta harus berupa angka bulat.',
            'attendee_count.min' => 'Jumlah peserta minimal 1 orang.',
            'attendee_count.max' => 'Jumlah peserta maksimal 50 orang.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var Booking|null $booking */
            $booking = $this->route('booking');
            if (! $booking) {
                return;
            }

            if ($booking->status !== 'confirmed') {
                $validator->errors()->add(
                    'booking',
                    'Check-in hanya dapat dilakukan untuk booking berstatus confirmed.'
                );

                return;
            }

            $date = Carbon::parse($booking->booking_date)->toDateString();
            $windowStart = Carbon::parse($date . ' ' . substr($booking->start_time, 0, 5))->subMinutes(30);
            $windowEnd = Carbon::parse($date . ' ' . substr($booking->end_time, 0, 5));
            $now = now();

            if ($now->lt($windowStart) || $now->gte($windowEnd)) {
                $validator->errors()->add(
                    'booking',
                    'Check-in hanya dapat dilakukan dalam rentang 30 menit sebelum jam mulai hingga sebelum jam selesai.'
                );
            }
        });
    }
}
