<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\TrailGpx */
class TrailGpxResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'media' => new MediaResource($this->whenLoaded('media')),
            'name' => $this->name,
            'sort_order' => $this->sort_order,
        ];
    }
}
