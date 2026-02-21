<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelGroupHikeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cancellation_reason.required' => 'Cancellation reason is required',
            'cancellation_reason.max' => 'Cancellation reason must not exceed 500 characters',
        ];
    }
}
