<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListTrailsRequest extends FormRequest
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
            'status' => ['nullable', 'string'],
            'difficulty' => ['nullable', 'string'],
            'county' => ['nullable', 'string', 'max:255'],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
            'created_after' => ['nullable', 'date'],
            'created_before' => ['nullable', 'date', 'after_or_equal:created_after'],
            'trashed' => ['nullable', 'string', 'in:with,only'],
            'amenities' => ['nullable', 'string'],
            'amenities_any' => ['nullable', 'string'],
            'min_distance' => ['nullable', 'numeric', 'min:0'],
            'max_distance' => ['nullable', 'numeric', 'min:0'],
            'min_duration' => ['nullable', 'numeric', 'min:0'],
            'max_duration' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'string', 'in:created_at,updated_at,name,distance_km,duration_hours,difficulty,published_at'],
            'order' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    protected function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->sometimes('max_distance', 'gte:min_distance', function ($input) {
            return $input->min_distance !== null;
        });

        $validator->sometimes('max_duration', 'gte:min_duration', function ($input) {
            return $input->min_duration !== null;
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'created_before.after_or_equal' => 'End date must be after or equal to start date',
            'max_distance.gte' => 'Maximum distance must be greater than or equal to minimum distance',
            'max_duration.gte' => 'Maximum duration must be greater than or equal to minimum duration',
            'sort.in' => 'Sort column must be one of: created_at, updated_at, name, distance_km, duration_hours, difficulty, published_at',
            'order.in' => 'Sort order must be asc or desc',
            'per_page.max' => 'Cannot request more than 100 items per page',
        ];
    }
}
