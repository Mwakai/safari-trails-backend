<?php

namespace App\Http\Requests;

use App\Enums\TrailDifficulty;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGroupHikeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->user()->hasPermission('group_hikes.view_all')) {
            $this->merge(['organizer_id' => $this->user()->id]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:255',
                Rule::unique('group_hikes', 'slug')->ignore($this->groupHike),
            ],
            'description' => ['sometimes', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'organizer_id' => ['nullable', 'integer', 'exists:users,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'trail_id' => ['nullable', 'integer', 'exists:trails,id'],
            'custom_location_name' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'meeting_point' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i,H:i:s'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'end_time' => ['nullable', 'date_format:H:i,H:i:s'],
            'max_participants' => ['nullable', 'integer', 'min:1'],
            'registration_url' => ['nullable', 'string', 'max:500', 'url'],
            'registration_deadline' => ['nullable', 'date'],
            'registration_notes' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'price_currency' => ['nullable', 'string', 'size:3'],
            'price_notes' => ['nullable', 'string', 'max:500'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_whatsapp' => ['nullable', 'string', 'max:50'],
            'difficulty' => ['nullable', Rule::enum(TrailDifficulty::class)],
            'featured_image_id' => ['nullable', 'integer', 'exists:media,id'],
            'is_featured' => ['nullable', 'boolean'],
            'is_recurring' => ['nullable', 'boolean'],
            'recurring_notes' => ['nullable', 'string', 'max:255'],
            'images' => ['nullable', 'array'],
            'images.*.media_id' => ['required', 'integer', 'exists:media,id'],
            'images.*.caption' => ['nullable', 'string', 'max:255'],
            'images.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $v) {
            $companyId = $this->input('company_id');
            $user = $this->user();

            if ($companyId && ! $user->hasPermission('group_hikes.view_all')) {
                if ((int) $companyId !== (int) $user->company_id) {
                    $v->errors()->add('company_id', 'You can only assign hikes to your own company.');
                }
            }
        });

        $validator->sometimes('recurring_notes', 'required|string|max:255', function ($input) {
            return $input->is_recurring === true || $input->is_recurring === '1' || $input->is_recurring === 1;
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.max' => 'Hike title must not exceed 255 characters',
            'slug.unique' => 'This slug is already in use',
            'end_date.after_or_equal' => 'End date must be on or after start date',
            'registration_url.url' => 'Registration URL must be a valid URL',
            'contact_email.email' => 'Contact email must be a valid email address',
            'featured_image_id.exists' => 'Selected featured image does not exist',
            'recurring_notes.required' => 'Recurring notes are required when hike is recurring',
        ];
    }
}
