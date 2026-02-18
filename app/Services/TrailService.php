<?php

namespace App\Services;

use App\Enums\TrailStatus;
use App\Models\Amenity;
use App\Models\Trail;
use App\Models\TrailGpx;
use App\Models\TrailImage;
use App\Models\TrailItineraryDay;
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
                'duration_type' => $data['duration_type'] ?? 'hours',
                'duration_min' => $data['duration_min'],
                'duration_max' => $data['duration_max'] ?? null,
                'elevation_gain_m' => $data['elevation_gain_m'] ?? null,
                'max_altitude_m' => $data['max_altitude_m'] ?? null,
                'is_year_round' => $data['is_year_round'] ?? true,
                'season_notes' => $data['season_notes'] ?? null,
                'requires_guide' => $data['requires_guide'] ?? false,
                'requires_permit' => $data['requires_permit'] ?? false,
                'permit_info' => $data['permit_info'] ?? null,
                'accommodation_types' => $data['accommodation_types'] ?? null,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'location_name' => $data['location_name'],
                'region_id' => $data['region_id'],
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

            if (array_key_exists('best_months', $data)) {
                $trail->setBestMonths($data['best_months'] ?? []);
            }

            $this->syncItineraryDays($trail, $data['itinerary_days'] ?? []);

            $this->syncCampingAmenity($trail, $data['accommodation_types'] ?? null, $data['amenity_ids'] ?? []);

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
            $trailFields = collect($data)->except(['amenity_ids', 'images', 'gpx_files', 'best_months', 'itinerary_days'])->toArray();
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

            if (array_key_exists('best_months', $data)) {
                $trail->setBestMonths($data['best_months'] ?? []);
            }

            if (array_key_exists('itinerary_days', $data)) {
                $this->syncItineraryDays($trail, $data['itinerary_days'] ?? []);
            }

            if (array_key_exists('accommodation_types', $data) || array_key_exists('amenity_ids', $data)) {
                $this->syncCampingAmenity(
                    $trail,
                    $trail->accommodation_types,
                    $data['amenity_ids'] ?? [],
                );
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

    /**
     * Auto-sync Camping amenity based on accommodation_types.
     *
     * @param  array<string>|null  $accommodationTypes
     * @param  array<int>  $explicitAmenityIds
     */
    private function syncCampingAmenity(Trail $trail, ?array $accommodationTypes, array $explicitAmenityIds = []): void
    {
        $campingAmenity = Amenity::where('slug', 'camping')->first();

        if (! $campingAmenity) {
            return;
        }

        $hasCampingAccommodation = is_array($accommodationTypes) && in_array('camping', $accommodationTypes);
        $hasExplicitCamping = in_array($campingAmenity->id, $explicitAmenityIds);

        if ($hasCampingAccommodation && ! $hasExplicitCamping) {
            $trail->amenities()->syncWithoutDetaching([$campingAmenity->id]);
        } elseif (! $hasCampingAccommodation && ! $hasExplicitCamping) {
            $trail->amenities()->detach($campingAmenity->id);
        }
    }

    /**
     * Sync itinerary days using delete-and-reinsert pattern.
     *
     * @param  array<int, array{day_number: int, title: string, description?: string|null, distance_km?: float|null, elevation_gain_m?: int|null, start_point?: string|null, end_point?: string|null, accommodation?: string|null, sort_order?: int}>  $days
     */
    private function syncItineraryDays(Trail $trail, array $days): void
    {
        $trail->itineraryDays()->delete();

        foreach ($days as $index => $dayData) {
            TrailItineraryDay::create([
                'trail_id' => $trail->id,
                'day_number' => $dayData['day_number'],
                'title' => $dayData['title'],
                'description' => $dayData['description'] ?? null,
                'distance_km' => $dayData['distance_km'] ?? null,
                'elevation_gain_m' => $dayData['elevation_gain_m'] ?? null,
                'start_point' => $dayData['start_point'] ?? null,
                'end_point' => $dayData['end_point'] ?? null,
                'accommodation' => $dayData['accommodation'] ?? null,
                'sort_order' => $dayData['sort_order'] ?? $index,
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
