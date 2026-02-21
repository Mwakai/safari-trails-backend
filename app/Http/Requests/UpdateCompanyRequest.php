<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
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
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('companies', 'slug')->ignore($this->company)],
            'description' => ['nullable', 'string'],
            'logo_id' => ['nullable', 'integer', 'exists:media,id'],
            'cover_image_id' => ['nullable', 'integer', 'exists:media,id'],
            'website' => ['nullable', 'string', 'max:500', 'url'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'is_verified' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.max' => 'Company name must not exceed 255 characters',
            'slug.unique' => 'This slug is already in use',
            'website.url' => 'Website must be a valid URL',
            'email.email' => 'Email must be a valid email address',
            'logo_id.exists' => 'Selected logo does not exist',
            'cover_image_id.exists' => 'Selected cover image does not exist',
        ];
    }
}
