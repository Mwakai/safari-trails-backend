<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\GroupHike
 */
class PublicGroupHikeResource extends JsonResource
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
            'description' => $this->description,
            'short_description' => $this->short_description,
            'company' => new CompanyResource($this->whenLoaded('company')),
            'trail' => new TrailResource($this->whenLoaded('trail')),
            'custom_location_name' => $this->custom_location_name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'region' => new RegionResource($this->whenLoaded('region')),
            'meeting_point' => $this->meeting_point,
            'start_date' => $this->start_date?->toDateString(),
            'start_time' => $this->start_time,
            'end_date' => $this->end_date?->toDateString(),
            'end_time' => $this->end_time,
            'is_multi_day' => $this->is_multi_day,
            'max_participants' => $this->max_participants,
            'spots_remaining' => null,
            'registration_url' => $this->registration_url,
            'registration_deadline' => $this->registration_deadline?->toDateString(),
            'registration_notes' => $this->registration_notes,
            'price' => $this->price,
            'price_currency' => $this->price_currency,
            'price_notes' => $this->price_notes,
            'is_free' => $this->is_free,
            'contact_name' => $this->contact_name,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'contact_whatsapp' => $this->contact_whatsapp,
            'difficulty' => $this->effective_difficulty?->value,
            'featured_image' => new MediaResource($this->whenLoaded('featuredImage')),
            'images' => GroupHikeImageResource::collection($this->whenLoaded('images')),
            'is_featured' => $this->is_featured,
            'is_recurring' => $this->is_recurring,
            'recurring_notes' => $this->recurring_notes,
            'status' => $this->status->value,
            'is_past' => $this->isPast(),
            'published_at' => $this->published_at,
            'cancellation_reason' => $this->cancellation_reason,
            'created_at' => $this->created_at,
        ];
    }
}
