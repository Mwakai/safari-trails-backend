<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Trail */
class MapMarkerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'difficulty' => $this->difficulty->value,
            'difficulty_label' => $this->difficulty->name,
            'distance_km' => $this->distance_km,
            'duration_type' => $this->duration_type?->value,
            'duration_min' => $this->duration_min,
            'duration_max' => $this->duration_max,
            'is_multi_day' => $this->is_multi_day,
            'duration_display' => $this->duration_display,
            'elevation_gain_m' => $this->elevation_gain_m,
            'thumbnail_url' => $this->whenLoaded('featuredImage', fn () => $this->featuredImage?->getVariantUrl('thumbnail')),
            'region_slug' => $this->whenLoaded('region', fn () => $this->region->slug),
            'region_name' => $this->whenLoaded('region', fn () => $this->region->name),
        ];
    }
}
