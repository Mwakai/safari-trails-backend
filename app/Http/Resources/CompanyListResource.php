<?php

namespace App\Http\Resources;

use App\Enums\GroupHikeStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Company
 */
class CompanyListResource extends JsonResource
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
            'logo_thumbnail' => $this->whenLoaded('logo', fn () => $this->logo?->getVariantUrl('thumbnail')),
            'is_verified' => $this->is_verified,
            'is_active' => $this->is_active,
            'hike_count' => $this->groupHikes()->where('status', GroupHikeStatus::Published)->count(),
        ];
    }
}
