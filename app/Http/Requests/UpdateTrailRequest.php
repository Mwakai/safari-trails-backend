<?php

namespace App\Http\Requests;

use App\Enums\DurationType;
use App\Enums\TrailDifficulty;
use App\Enums\TrailImageType;
use App\Enums\TrailStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTrailRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('trails', 'slug')->ignore($this->route('trail'))],
            'description' => ['sometimes', 'required', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'difficulty' => ['sometimes', 'required', Rule::enum(TrailDifficulty::class)],
            'distance_km' => ['sometimes', 'required', 'numeric', 'min:0', 'max:9999.99'],
            'duration_type' => ['sometimes', 'required', Rule::enum(DurationType::class)],
            'duration_min' => ['sometimes', 'required', 'numeric', 'min:0.1', 'max:999.9'],
            'duration_max' => ['nullable', 'numeric', 'min:0.1', 'max:999.9'],
            'elevation_gain_m' => ['nullable', 'integer', 'min:0'],
            'max_altitude_m' => ['nullable', 'integer', 'min:0'],
            'is_year_round' => ['nullable', 'boolean'],
            'season_notes' => ['nullable', 'string', 'max:1000'],
            'best_months' => ['nullable', 'array'],
            'best_months.*' => ['integer', 'min:1', 'max:12'],
            'requires_guide' => ['nullable', 'boolean'],
            'requires_permit' => ['nullable', 'boolean'],
            'permit_info' => ['nullable', 'string', 'max:1000'],
            'accommodation_types' => ['nullable', 'array'],
            'accommodation_types.*' => ['string', Rule::in(['camping', 'huts', 'bandas', 'hotels'])],
            'latitude' => ['sometimes', 'required', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'required', 'numeric', 'between:-180,180'],
            'location_name' => ['sometimes', 'required', 'string', 'max:255'],
            'region_id' => ['sometimes', 'required', 'integer', 'exists:regions,id'],
            'route_a_name' => ['nullable', 'string', 'max:255'],
            'route_a_description' => ['nullable', 'string'],
            'route_a_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'route_a_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'route_b_enabled' => ['nullable', 'boolean'],
            'route_b_name' => ['nullable', 'string', 'max:255'],
            'route_b_description' => ['nullable', 'string'],
            'route_b_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'route_b_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'status' => ['sometimes', Rule::enum(TrailStatus::class)],
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
            'itinerary_days' => ['nullable', 'array'],
            'itinerary_days.*.day_number' => ['required', 'integer', 'min:1', 'max:255'],
            'itinerary_days.*.title' => ['required', 'string', 'max:255'],
            'itinerary_days.*.description' => ['nullable', 'string'],
            'itinerary_days.*.distance_km' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'itinerary_days.*.elevation_gain_m' => ['nullable', 'integer', 'min:0'],
            'itinerary_days.*.start_point' => ['nullable', 'string', 'max:255'],
            'itinerary_days.*.end_point' => ['nullable', 'string', 'max:255'],
            'itinerary_days.*.accommodation' => ['nullable', 'string', 'max:255'],
            'itinerary_days.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->sometimes('duration_max', 'gte:duration_min', function ($input) {
            return $input->duration_min !== null;
        });

        $validator->sometimes('best_months', 'required|array|min:1', function ($input) {
            return $input->is_year_round === false || $input->is_year_round === '0' || $input->is_year_round === 0;
        });

        $validator->sometimes('permit_info', 'required|string', function ($input) {
            return $input->requires_permit === true || $input->requires_permit === '1' || $input->requires_permit === 1;
        });

        $validator->after(function (\Illuminate\Validation\Validator $validator) {
            $days = $this->input('itinerary_days', []);

            if (is_array($days) && count($days) > 0) {
                $dayNumbers = array_column($days, 'day_number');
                $duplicates = array_diff_assoc($dayNumbers, array_unique($dayNumbers));

                if (! empty($duplicates)) {
                    $validator->errors()->add('itinerary_days', 'Duplicate day numbers are not allowed');
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.max' => 'Trail name must not exceed 255 characters',
            'slug.unique' => 'This slug is already in use',
            'short_description.max' => 'Short description must not exceed 500 characters',
            'distance_km.numeric' => 'Distance must be a number',
            'duration_min.numeric' => 'Minimum duration must be a number',
            'duration_max.gte' => 'Maximum duration must be greater than or equal to minimum duration',
            'best_months.required' => 'Best months are required when trail is not year-round',
            'best_months.*.min' => 'Month must be between 1 and 12',
            'best_months.*.max' => 'Month must be between 1 and 12',
            'permit_info.required' => 'Permit information is required when permit is required',
            'accommodation_types.*.in' => 'Accommodation type must be one of: camping, huts, bandas, hotels',
            'latitude.between' => 'Latitude must be between -90 and 90',
            'longitude.between' => 'Longitude must be between -180 and 180',
            'location_name.max' => 'Location name must not exceed 255 characters',
            'region_id.integer' => 'Region must be a valid ID',
            'region_id.exists' => 'Selected region does not exist',
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
            'itinerary_days.*.day_number.required' => 'Day number is required for each itinerary day',
            'itinerary_days.*.day_number.integer' => 'Day number must be an integer',
            'itinerary_days.*.day_number.min' => 'Day number must be at least 1',
            'itinerary_days.*.title.required' => 'Title is required for each itinerary day',
            'itinerary_days.*.title.max' => 'Itinerary day title must not exceed 255 characters',
            'itinerary_days.*.distance_km.numeric' => 'Itinerary day distance must be a number',
            'itinerary_days.*.elevation_gain_m.integer' => 'Itinerary day elevation gain must be an integer',
        ];
    }
}
