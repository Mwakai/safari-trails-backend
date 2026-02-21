<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\GroupHike
 */
class PublicGroupHikeListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'company' => $this->whenLoaded('company', fn () => [
                'name' => $this->company?->name,
                'slug' => $this->company?->slug,
            ]),
            'custom_location_name' => $this->custom_location_name,
            'region' => new RegionResource($this->whenLoaded('region')),
            'start_date' => $this->start_date?->toDateString(),
            'start_time' => $this->start_time,
            'end_date' => $this->end_date?->toDateString(),
            'is_multi_day' => $this->is_multi_day,
            'price' => $this->price,
            'price_currency' => $this->price_currency,
            'is_free' => $this->is_free,
            'difficulty' => $this->effective_difficulty?->value,
            'featured_image_thumbnail' => $this->whenLoaded('featuredImage', fn () => $this->featuredImage?->getVariantUrl('thumbnail')),
            'featured_image_medium' => $this->whenLoaded('featuredImage', fn () => $this->featuredImage?->getVariantUrl('medium')),
            'is_featured' => $this->is_featured,
            'max_participants' => $this->max_participants,
            'spots_remaining' => null,
        ];
    }
}
