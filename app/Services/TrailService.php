<?php

namespace App\Services;

use App\Enums\TrailStatus;
use App\Models\Trail;
use App\Models\TrailGpx;
use App\Models\TrailImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TrailService
{
    /**
     * Create a trail with all related data.
     *
     * @param  array<string, mixed>  $data
     */
    public function createTrail(array $data, int $userId): Trail
    {
        return DB::transaction(function () use ($data, $userId) {
            $slug = $data['slug'] ?? Str::slug($data['name']);
            $slug = $this->ensureUniqueSlug($slug);

            $trail = Trail::create([
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'],
                'short_description' => $data['short_description'] ?? null,
                'difficulty' => $data['difficulty'],
                'distance_km' => $data['distance_km'],
                'duration_hours' => $data['duration_hours'],
                'elevation_gain_m' => $data['elevation_gain_m'] ?? null,
                'max_altitude_m' => $data['max_altitude_m'] ?? null,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'location_name' => $data['location_name'],
                'county' => $data['county'],
                'route_a_name' => $data['route_a_name'] ?? null,
                'route_a_description' => $data['route_a_description'] ?? null,
                'route_a_latitude' => $data['route_a_latitude'] ?? null,
                'route_a_longitude' => $data['route_a_longitude'] ?? null,
                'route_b_enabled' => $data['route_b_enabled'] ?? false,
                'route_b_name' => $data['route_b_name'] ?? null,
                'route_b_description' => $data['route_b_description'] ?? null,
                'route_b_latitude' => $data['route_b_latitude'] ?? null,
                'route_b_longitude' => $data['route_b_longitude'] ?? null,
                'featured_image_id' => $data['featured_image_id'] ?? null,
                'video_url' => $data['video_url'] ?? null,
                'status' => $data['status'] ?? TrailStatus::Draft,
                'published_at' => isset($data['status']) && $data['status'] === TrailStatus::Published->value
                    ? now()
                    : null,
                'created_by' => $userId,
            ]);

            $this->syncAmenities($trail, $data['amenity_ids'] ?? []);
            $this->syncImages($trail, $data['images'] ?? []);
            $this->syncGpxFiles($trail, $data['gpx_files'] ?? []);

            return $trail;
        });
    }

    /**
     * Update a trail with all related data.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTrail(Trail $trail, array $data, int $userId): Trail
    {
        return DB::transaction(function () use ($trail, $data, $userId) {
            $trailFields = collect($data)->except(['amenity_ids', 'images', 'gpx_files'])->toArray();
            $trailFields['updated_by'] = $userId;

            if (isset($trailFields['status'])
                && $trailFields['status'] === TrailStatus::Published->value
                && ! $trail->published_at
            ) {
                $trailFields['published_at'] = now();
            }

            $trail->update($trailFields);

            if (array_key_exists('amenity_ids', $data)) {
                $this->syncAmenities($trail, $data['amenity_ids'] ?? []);
            }

            if (array_key_exists('images', $data)) {
                $this->syncImages($trail, $data['images'] ?? []);
            }

            if (array_key_exists('gpx_files', $data)) {
                $this->syncGpxFiles($trail, $data['gpx_files'] ?? []);
            }

            return $trail;
        });
    }

    /**
     * Sync amenities using the sync method.
     *
     * @param  array<int>  $amenityIds
     */
    private function syncAmenities(Trail $trail, array $amenityIds): void
    {
        $trail->amenities()->sync($amenityIds);
    }

    /**
     * Sync trail images using delete-and-reinsert pattern.
     *
     * @param  array<int, array{media_id: int, type: string, caption?: string|null, sort_order?: int}>  $images
     */
    private function syncImages(Trail $trail, array $images): void
    {
        $trail->images()->delete();

        foreach ($images as $index => $imageData) {
            TrailImage::create([
                'trail_id' => $trail->id,
                'media_id' => $imageData['media_id'],
                'type' => $imageData['type'],
                'caption' => $imageData['caption'] ?? null,
                'sort_order' => $imageData['sort_order'] ?? $index,
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Sync GPX files using delete-and-reinsert pattern.
     *
     * @param  array<int, array{media_id: int, name: string, sort_order?: int}>  $gpxFiles
     */
    private function syncGpxFiles(Trail $trail, array $gpxFiles): void
    {
        $trail->gpxFiles()->delete();

        foreach ($gpxFiles as $index => $gpxData) {
            TrailGpx::create([
                'trail_id' => $trail->id,
                'media_id' => $gpxData['media_id'],
                'name' => $gpxData['name'],
                'sort_order' => $gpxData['sort_order'] ?? $index,
                'created_at' => now(),
            ]);
        }
    }

    private function ensureUniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $original = $slug;
        $counter = 1;

        while (Trail::query()
            ->where('slug', $slug)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists()
        ) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
