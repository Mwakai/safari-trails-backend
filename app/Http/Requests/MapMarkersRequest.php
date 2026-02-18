<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MapMarkersRequest extends FormRequest
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
            'difficulty' => ['nullable', 'string'],
            'region' => ['nullable', 'string', 'max:255'],
            'amenities' => ['nullable', 'string'],
            'amenities_any' => ['nullable', 'string'],
            'min_distance' => ['nullable', 'numeric', 'min:0'],
            'max_distance' => ['nullable', 'numeric', 'min:0'],
            'min_duration' => ['nullable', 'numeric', 'min:0'],
            'max_duration' => ['nullable', 'numeric', 'min:0'],
            'duration_type' => ['nullable', 'string', 'in:hours,days'],
            'is_multi_day' => ['nullable', 'boolean'],
            'requires_guide' => ['nullable', 'boolean'],
            'requires_permit' => ['nullable', 'boolean'],
            'accommodation' => ['nullable', 'string'],
            'best_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'bounds' => ['nullable', 'string', 'regex:/^-?\d+\.?\d*,-?\d+\.?\d*,-?\d+\.?\d*,-?\d+\.?\d*$/'],
            'near_lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:near_lng'],
            'near_lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:near_lat'],
            'radius' => ['nullable', 'numeric', 'min:1', 'max:200'],
            'sort' => ['nullable', 'string', 'in:name,distance_km,duration_min,difficulty'],
            'order' => ['nullable', 'string', 'in:asc,desc'],
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
            'max_distance.gte' => 'Maximum distance must be greater than or equal to minimum distance',
            'max_duration.gte' => 'Maximum duration must be greater than or equal to minimum duration',
            'bounds.regex' => 'Bounds must be in format: sw_lat,sw_lng,ne_lat,ne_lng',
            'near_lat.required_with' => 'Latitude is required when longitude is provided',
            'near_lng.required_with' => 'Longitude is required when latitude is provided',
            'near_lat.between' => 'Latitude must be between -90 and 90',
            'near_lng.between' => 'Longitude must be between -180 and 180',
            'radius.max' => 'Radius cannot exceed 200 km',
            'sort.in' => 'Sort column must be one of: name, distance_km, duration_min, difficulty',
            'order.in' => 'Sort order must be asc or desc',
        ];
    }
}
