<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAmenityRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:amenities,slug'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Amenity name is required',
            'name.max' => 'Amenity name must not exceed 255 characters',
            'slug.required' => 'Slug is required',
            'slug.max' => 'Slug must not exceed 255 characters',
            'slug.unique' => 'This slug is already in use',
            'is_active.boolean' => 'Active status must be true or false',
        ];
    }
}
