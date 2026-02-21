<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\GroupHike
 */
class GroupHikeListResource extends JsonResource
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
            'start_date' => $this->start_date?->toDateString(),
            'start_time' => $this->start_time,
            'end_date' => $this->end_date?->toDateString(),
            'is_multi_day' => $this->is_multi_day,
            'custom_location_name' => $this->custom_location_name,
            'region' => new RegionResource($this->whenLoaded('region')),
            'difficulty' => $this->effective_difficulty?->value,
            'price' => $this->price,
            'price_currency' => $this->price_currency,
            'is_free' => $this->is_free,
            'featured_image_thumbnail' => $this->whenLoaded('featuredImage', fn () => $this->featuredImage?->getVariantUrl('thumbnail')),
            'organizer_id' => $this->organizer_id,
            'company_id' => $this->company_id,
            'trail_id' => $this->trail_id,
            'is_featured' => $this->is_featured,
            'status' => $this->status->value,
            'is_past' => $this->isPast(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
