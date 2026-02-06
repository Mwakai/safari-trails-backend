<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListUsersRequest extends FormRequest
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
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
            'created_after' => ['nullable', 'date'],
            'created_before' => ['nullable', 'date', 'after_or_equal:created_after'],
            'trashed' => ['nullable', 'string', 'in:with,only'],
            'sort' => ['nullable', 'string', 'in:created_at,updated_at,first_name,last_name,email'],
            'order' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'created_before.after_or_equal' => 'End date must be after or equal to start date',
            'sort.in' => 'Sort column must be one of: created_at, updated_at, first_name, last_name, email',
            'order.in' => 'Sort order must be asc or desc',
            'per_page.max' => 'Cannot request more than 100 items per page',
        ];
    }
}
