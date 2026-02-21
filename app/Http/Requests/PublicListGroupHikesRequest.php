<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicListGroupHikesRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string'],
            'difficulty' => ['nullable', 'string'],
            'company' => ['nullable', 'string'],
            'trail' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'is_free' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'string'],
            'order' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
