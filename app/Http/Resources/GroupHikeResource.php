<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/**
 * @mixin \App\Models\GroupHike
 */
class GroupHikeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'organizer_id' => $this->organizer_id,
            'organizer' => new UserResource($this->whenLoaded('organizer')),
            'company_id' => $this->company_id,
            'company' => new CompanyResource($this->whenLoaded('company')),
            'trail_id' => $this->trail_id,
            'trail' => new TrailResource($this->whenLoaded('trail')),
            'custom_location_name' => $this->custom_location_name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'region_id' => $this->region_id,
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
            'featured_image_id' => $this->featured_image_id,
            'images' => GroupHikeImageResource::collection($this->whenLoaded('images')),
            'is_featured' => $this->is_featured,
            'is_recurring' => $this->is_recurring,
            'recurring_notes' => $this->recurring_notes,
            'status' => $this->status->value,
            'is_past' => $this->isPast(),
            'published_at' => $this->published_at,
            'cancelled_at' => $this->cancelled_at,
            'cancellation_reason' => $this->cancellation_reason,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'updater' => new UserResource($this->whenLoaded('updater')),
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'can_edit' => $user ? Gate::inspect('update', $this->resource)->allowed() : false,
            'can_delete' => $user ? Gate::inspect('delete', $this->resource)->allowed() : false,
            'can_publish' => $user ? Gate::inspect('publish', $this->resource)->allowed() : false,
            'can_cancel' => $user ? Gate::inspect('cancel', $this->resource)->allowed() : false,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
