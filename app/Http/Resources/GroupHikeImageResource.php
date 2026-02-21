<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupHikeImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'media_id' => $this->media_id,
            'caption' => $this->caption,
            'sort_order' => $this->sort_order,
            'url' => $this->whenLoaded('media', fn () => $this->media?->getUrl()),
            'thumbnail' => $this->whenLoaded('media', fn () => $this->media?->getVariantUrl('thumbnail')),
            'medium' => $this->whenLoaded('media', fn () => $this->media?->getVariantUrl('medium')),
            'created_at' => $this->created_at,
        ];
    }
}
