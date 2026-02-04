<?php

namespace App\Http\Requests;

use App\Enums\TrailDifficulty;
use App\Enums\TrailImageType;
use App\Enums\TrailStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrailRequest extends FormRequest
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
            'slug' => ['nullable', 'string', 'max:255', 'unique:trails,slug'],
            'description' => ['required', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'difficulty' => ['required', Rule::enum(TrailDifficulty::class)],
            'distance_km' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'duration_hours' => ['required', 'numeric', 'min:0', 'max:999.9'],
            'elevation_gain_m' => ['nullable', 'integer', 'min:0'],
            'max_altitude_m' => ['nullable', 'integer', 'min:0'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'location_name' => ['required', 'string', 'max:255'],
            'county' => ['required', 'string', 'max:100', Rule::in(array_keys(config('counties.all')))],
            'route_a_name' => ['nullable', 'string', 'max:255'],
            'route_a_description' => ['nullable', 'string'],
            'route_a_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'route_a_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'route_b_enabled' => ['nullable', 'boolean'],
            'route_b_name' => ['nullable', 'string', 'max:255'],
            'route_b_description' => ['nullable', 'string'],
            'route_b_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'route_b_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'status' => ['nullable', Rule::enum(TrailStatus::class)],
            'featured_image_id' => ['nullable', 'integer', 'exists:media,id'],
            'video_url' => ['nullable', 'string', 'max:500', 'url'],
            'amenity_ids' => ['nullable', 'array'],
            'amenity_ids.*' => ['integer', 'exists:amenities,id'],
            'images' => ['nullable', 'array'],
            'images.*.media_id' => ['required', 'integer', 'exists:media,id'],
            'images.*.type' => ['required', Rule::enum(TrailImageType::class)],
            'images.*.caption' => ['nullable', 'string', 'max:255'],
            'images.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'gpx_files' => ['nullable', 'array'],
            'gpx_files.*.media_id' => ['required', 'integer', 'exists:media,id'],
            'gpx_files.*.name' => ['required', 'string', 'max:255'],
            'gpx_files.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Trail name is required',
            'name.max' => 'Trail name must not exceed 255 characters',
            'slug.required' => 'Slug is required',
            'slug.unique' => 'This slug is already in use',
            'description.required' => 'Description is required',
            'short_description.max' => 'Short description must not exceed 500 characters',
            'difficulty.required' => 'Difficulty level is required',
            'difficulty.Illuminate\Validation\Rules\Enum' => 'Difficulty must be a valid difficulty level',
            'distance_km.required' => 'Distance is required',
            'distance_km.numeric' => 'Distance must be a number',
            'duration_hours.required' => 'Duration is required',
            'duration_hours.numeric' => 'Duration must be a number',
            'latitude.required' => 'Latitude is required',
            'latitude.between' => 'Latitude must be between -90 and 90',
            'longitude.required' => 'Longitude is required',
            'longitude.between' => 'Longitude must be between -180 and 180',
            'location_name.required' => 'Location name is required',
            'county.required' => 'County is required',
            'county.in' => 'County must be a valid Kenyan county',
            'featured_image_id.exists' => 'Selected featured image does not exist',
            'video_url.url' => 'Video URL must be a valid URL',
            'video_url.max' => 'Video URL must not exceed 500 characters',
            'amenity_ids.array' => 'Amenities must be an array',
            'amenity_ids.*.exists' => 'Selected amenity does not exist',
            'images.*.media_id.required' => 'Image media ID is required',
            'images.*.media_id.exists' => 'Selected image media does not exist',
            'images.*.type.required' => 'Image type is required',
            'gpx_files.*.media_id.required' => 'GPX file media ID is required',
            'gpx_files.*.media_id.exists' => 'Selected GPX media does not exist',
            'gpx_files.*.name.required' => 'GPX file name is required',
        ];
    }
}
