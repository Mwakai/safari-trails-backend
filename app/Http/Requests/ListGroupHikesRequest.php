<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListGroupHikesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->user()?->hasPermission('group_hikes.view_all')) {
            $this->request->remove('organizer_id');
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string'],
            'organizer_id' => ['nullable', 'integer'],
            'company_id' => ['nullable', 'integer'],
            'trail_id' => ['nullable', 'integer'],
            'region_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'is_featured' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'string'],
            'order' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
