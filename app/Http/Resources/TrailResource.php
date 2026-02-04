<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Trail */
class TrailResource extends JsonResource
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
            'description' => $this->description,
            'short_description' => $this->short_description,
            'difficulty' => $this->difficulty->value,
            'distance_km' => $this->distance_km,
            'duration_hours' => $this->duration_hours,
            'elevation_gain_m' => $this->elevation_gain_m,
            'max_altitude_m' => $this->max_altitude_m,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'location_name' => $this->location_name,
            'county' => $this->county,
            'county_name' => $this->county_name,
            'route_a_name' => $this->route_a_name,
            'route_a_description' => $this->route_a_description,
            'route_a_latitude' => $this->route_a_latitude,
            'route_a_longitude' => $this->route_a_longitude,
            'route_b_enabled' => $this->route_b_enabled,
            'route_b_name' => $this->route_b_name,
            'route_b_description' => $this->route_b_description,
            'route_b_latitude' => $this->route_b_latitude,
            'route_b_longitude' => $this->route_b_longitude,
            'featured_image' => new MediaResource($this->whenLoaded('featuredImage')),
            'video_url' => $this->video_url,
            'status' => $this->status->value,
            'published_at' => $this->published_at,
            'amenities' => AmenityResource::collection($this->whenLoaded('amenities')),
            'images' => TrailImageResource::collection($this->whenLoaded('images')),
            'gpx_files' => TrailGpxResource::collection($this->whenLoaded('gpxFiles')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'updater' => new UserResource($this->whenLoaded('updater')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
