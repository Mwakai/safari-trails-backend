<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Trail */
class TrailListResource extends JsonResource
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
            'duration_hours' => $this->duration_hours,
            'location_name' => $this->location_name,
            'county' => $this->county,
            'county_name' => $this->county_name,
            'featured_image' => new MediaResource($this->whenLoaded('featuredImage')),
            'status' => $this->status->value,
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
        ];
    }
}
