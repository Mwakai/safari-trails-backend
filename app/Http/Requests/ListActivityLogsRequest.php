<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListActivityLogsRequest extends FormRequest
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
            'log_name' => ['nullable', 'string'],
            'event' => ['nullable', 'string'],
            'causer_id' => ['nullable', 'integer'],
            'subject_type' => ['nullable', 'string'],
            'subject_id' => ['nullable', 'integer'],
            'created_after' => ['nullable', 'date'],
            'created_before' => ['nullable', 'date', 'after_or_equal:created_after'],
            'sort' => ['nullable', 'string', 'in:created_at,log_name,event'],
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
            'sort.in' => 'Sort column must be one of: created_at, log_name, event',
            'order.in' => 'Sort order must be asc or desc',
            'per_page.max' => 'Cannot request more than 100 items per page',
        ];
    }
}
