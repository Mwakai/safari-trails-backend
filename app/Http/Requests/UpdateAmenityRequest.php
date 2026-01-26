<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAmenityRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('amenities', 'slug')->ignore($this->route('amenity'))],
            'icon' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.max' => 'Amenity name must not exceed 255 characters',
            'slug.max' => 'Slug must not exceed 255 characters',
            'slug.unique' => 'This slug is already in use',
            'icon.max' => 'Icon must not exceed 255 characters',
            'description.max' => 'Description must not exceed 1000 characters',
            'is_active.boolean' => 'Active status must be true or false',
        ];
    }
}
