<?php

namespace App\Http\Requests;

use App\Models\Booking;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class BookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $dateRules = ['required', 'date_format:Y-m-d'];

        // Booking baru tidak boleh di masa lalu; saat update tanggal lama tetap boleh.
        if ($this->isMethod('post')) {
            $dateRules[] = 'after_or_equal:today';
        }

        return [
            'customer_name' => ['required', 'string', 'min:3', 'max:100'],
            'facility' => ['required', Rule::in(Booking::FACILITIES)],
            'booking_date' => $dateRules,
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'status' => ['sometimes', Rule::in(Booking::STATUSES)],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'facility.in' => 'Fasilitas harus salah satu dari: ' . implode(', ', Booking::FACILITIES) . '.',
            'end_time.after' => 'Jam selesai harus lebih besar dari jam mulai.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var Booking|null $current */
            $current = $this->route('booking');
            $status = $this->input('status', $current?->status ?? 'pending');

            if ($status === 'cancelled') {
                return;
            }

            $conflict = Booking::query()
                ->where('facility', $this->input('facility'))
                ->where('booking_date', $this->input('booking_date'))
                ->where('status', '!=', 'cancelled')
                ->where('start_time', '<', $this->input('end_time'))
                ->where('end_time', '>', $this->input('start_time'))
                ->when($current, fn ($query) => $query->where('id', '!=', $current->id))
                ->exists();

            if ($conflict) {
                $validator->errors()->add(
                    'start_time',
                    'Jadwal bentrok dengan booking lain pada fasilitas dan tanggal yang sama.'
                );
            }
        });
    }

    /**
     * Catat validasi gagal sebagai warning. Hanya nama kolom dan nama aturan
     * yang dicatat, tidak ada nilai yang dikirim pengguna.
     */
    protected function failedValidation(Validator $validator): void
    {
        $gagal = $validator->failed();
        $aturan = [];

        foreach ($validator->errors()->keys() as $kolom) {
            // Error tanpa aturan bawaan hanya berasal dari pengecekan jadwal bentrok.
            $aturan[$kolom] = isset($gagal[$kolom])
                ? array_keys($gagal[$kolom])
                : ['JadwalBentrok'];
        }

        Log::warning('booking.validasi_gagal', [
            'user_id' => $this->user()?->id,
            'aksi' => $this->isMethod('post') ? 'membuat booking' : 'mengubah booking',
            'kolom_gagal' => array_keys($aturan),
            'aturan_gagal' => $aturan,
        ]);

        parent::failedValidation($validator);
    }
}