<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Region */
class RegionResource extends JsonResource
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
            'description' => $this->when($this->description !== null, $this->description),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'featured_image' => new MediaResource($this->whenLoaded('featuredImage')),
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
