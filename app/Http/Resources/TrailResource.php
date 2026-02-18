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
            'duration_type' => $this->duration_type?->value,
            'duration_min' => $this->duration_min,
            'duration_max' => $this->duration_max,
            'is_multi_day' => $this->is_multi_day,
            'duration_display' => $this->duration_display,
            'elevation_gain_m' => $this->elevation_gain_m,
            'max_altitude_m' => $this->max_altitude_m,
            'is_year_round' => $this->is_year_round,
            'season_notes' => $this->season_notes,
            'best_months' => $this->whenLoaded('bestMonths', fn () => $this->getBestMonthsArray()),
            'best_months_display' => $this->whenLoaded('bestMonths', fn () => $this->best_months_display),
            'current_month_rating' => $this->whenLoaded('bestMonths', fn () => $this->current_month_rating),
            'season_recommendation' => $this->whenLoaded('bestMonths', fn () => $this->season_recommendation),
            'requires_guide' => $this->requires_guide,
            'requires_permit' => $this->requires_permit,
            'permit_info' => $this->permit_info,
            'accommodation_types' => $this->accommodation_types,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'location_name' => $this->location_name,
            'region_id' => $this->region_id,
            'region' => new RegionResource($this->whenLoaded('region')),
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
            'itinerary_days' => TrailItineraryDayResource::collection($this->whenLoaded('itineraryDays')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'updater' => new UserResource($this->whenLoaded('updater')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
