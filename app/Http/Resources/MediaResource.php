<?php

namespace App\Http\Resources;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Media */
class MediaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'original_filename' => $this->original_filename,
            'url' => $this->getUrl(),
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'type' => $this->type->value,
            'width' => $this->width,
            'height' => $this->height,
            'duration' => $this->duration,
            'alt_text' => $this->alt_text,
            'variants' => $this->buildVariantUrls(),
            'uploaded_by' => new UserResource($this->whenLoaded('uploadedBy')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * @return array<string, string>|null
     */
    private function buildVariantUrls(): ?array
    {
        if (! $this->variants) {
            return null;
        }

        $urls = [];
        foreach ($this->variants as $variant => $path) {
            $urls[$variant] = $this->resource->getVariantUrl($variant);
        }

        return $urls;
    }
}
