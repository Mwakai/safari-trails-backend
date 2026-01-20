<?php

namespace App\Http\Requests;

use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', Rule::enum(UserStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required',
            'first_name.max' => 'First name must not exceed 255 characters',
            'last_name.required' => 'Last name is required',
            'last_name.max' => 'Last name must not exceed 255 characters',
            'email.required' => 'Email is required',
            'email.email' => 'Email must be a valid email address',
            'email.unique' => 'Email is already registered',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'password.confirmed' => 'Password confirmation does not match',
            'role_id.required' => 'Role is required',
            'role_id.integer' => 'Role ID must be a valid integer',
            'role_id.exists' => 'Selected role does not exist',
            'company_id.integer' => 'Company ID must be a valid integer',
            'company_id.exists' => 'Selected company does not exist',
            'phone.max' => 'Phone number must not exceed 20 characters',
            'status.enum' => 'Status must be a valid status value',
        ];
    }
}
