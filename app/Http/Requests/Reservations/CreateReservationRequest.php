<?php

declare(strict_types=1);

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reservations.create');
    }

    public function rules(): array
    {
        return [
            'space_id' => [
                'required',
                'integer',
                Rule::exists('spaces', 'id')->where('status', 'available'),
            ],
            'start_datetime' => [
                'required',
                'date',
                'after:now',
            ],
            'end_datetime' => [
                'required',
                'date',
                'after:start_datetime',
                function ($attribute, $value, $fail) {
                    $start = $this->input('start_datetime');
                    $duration = \Carbon\Carbon::parse($start)->diffInHours($value);
                    $maxHours = config('sigabe.reservations.max_hours_per_booking');

                    if ($duration > $maxHours) {
                        $fail("La reserva no puede exceder {$maxHours} horas.");
                    }
                },
            ],
            'purpose' => [
                'required',
                'string',
                'max:500',
            ],
            'attendees_count' => [
                'required',
                'integer',
                'min:1',
                'max:200',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'purpose' => strip_tags($this->purpose ?? ''),
        ]);
    }
}
