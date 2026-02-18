<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Trail */
class PublicTrailListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'difficulty' => $this->difficulty->value,
            'distance_km' => $this->distance_km,
            'duration_type' => $this->duration_type?->value,
            'duration_min' => $this->duration_min,
            'duration_max' => $this->duration_max,
            'is_multi_day' => $this->is_multi_day,
            'duration_display' => $this->duration_display,
            'elevation_gain_m' => $this->elevation_gain_m,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'location_name' => $this->location_name,
            'region_id' => $this->region_id,
            'region_name' => $this->whenLoaded('region', fn () => $this->region->name),
            'region_slug' => $this->whenLoaded('region', fn () => $this->region->slug),
            'featured_image' => new MediaResource($this->whenLoaded('featuredImage')),
            'amenities' => AmenityResource::collection($this->whenLoaded('amenities')),
            'published_at' => $this->published_at,
        ];
    }
}
