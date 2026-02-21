<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublishGroupHikeRequest extends FormRequest
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
        return [];
    }

    protected function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $v) {
            $hike = $this->route('groupHike');

            if (! $hike) {
                return;
            }

            if (! $hike->isDraft()) {
                $v->errors()->add('status', 'Only draft hikes can be published.');

                return;
            }

            if (! $hike->featured_image_id) {
                $v->errors()->add('featured_image_id', 'A featured image is required before publishing.');
            }

            if ($hike->start_date && $hike->start_date->lt(today())) {
                $v->errors()->add('start_date', 'Start date must be in the future to publish.');
            }

            if (! $hike->trail_id) {
                if (! $hike->custom_location_name) {
                    $v->errors()->add('custom_location_name', 'Custom location name is required to publish.');
                }
                if (! $hike->latitude) {
                    $v->errors()->add('latitude', 'Latitude is required to publish.');
                }
                if (! $hike->longitude) {
                    $v->errors()->add('longitude', 'Longitude is required to publish.');
                }
                if (! $hike->region_id) {
                    $v->errors()->add('region_id', 'Region is required to publish.');
                }
            }
        });
    }
}
