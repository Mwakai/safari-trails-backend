<?php

namespace App\Services;

use App\Enums\GroupHikeStatus;
use App\Models\GroupHike;
use App\Models\GroupHikeImage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GroupHikeService
{
    /**
     * Create a new group hike.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): GroupHike
    {
        return DB::transaction(function () use ($data, $user) {
            $slug = $data['slug'] ?? Str::slug($data['title']);
            $slug = $this->ensureUniqueSlug($slug);

            $organizerId = $user->hasPermission('group_hikes.view_all') && isset($data['organizer_id'])
                ? $data['organizer_id']
                : $user->id;

            $companyId = $data['company_id'] ?? ($user->company_id ?? null);

            $hike = GroupHike::create([
                'title' => $data['title'],
                'slug' => $slug,
                'description' => $data['description'],
                'short_description' => $data['short_description'] ?? null,
                'organizer_id' => $organizerId,
                'company_id' => $companyId,
                'trail_id' => $data['trail_id'] ?? null,
                'custom_location_name' => $data['custom_location_name'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'region_id' => $data['region_id'] ?? null,
                'meeting_point' => $data['meeting_point'] ?? null,
                'start_date' => $data['start_date'],
                'start_time' => $data['start_time'],
                'end_date' => $data['end_date'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'max_participants' => $data['max_participants'] ?? null,
                'registration_url' => $data['registration_url'] ?? null,
                'registration_deadline' => $data['registration_deadline'] ?? null,
                'registration_notes' => $data['registration_notes'] ?? null,
                'price' => $data['price'] ?? null,
                'price_currency' => $data['price_currency'] ?? 'KES',
                'price_notes' => $data['price_notes'] ?? null,
                'contact_name' => $data['contact_name'] ?? null,
                'contact_email' => $data['contact_email'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'contact_whatsapp' => $data['contact_whatsapp'] ?? null,
                'difficulty' => $data['difficulty'] ?? null,
                'featured_image_id' => $data['featured_image_id'] ?? null,
                'is_featured' => $data['is_featured'] ?? false,
                'is_recurring' => $data['is_recurring'] ?? false,
                'recurring_notes' => $data['recurring_notes'] ?? null,
                'status' => GroupHikeStatus::Draft,
                'created_by' => $user->id,
            ]);

            if (isset($data['images'])) {
                $this->syncGalleryImages($hike, $data['images']);
            }

            ActivityLogger::log(
                event: 'created',
                subject: $hike,
                causer: $user,
                logName: 'group_hikes',
            );

            return $hike;
        });
    }

    /**
     * Update an existing group hike.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(GroupHike $hike, array $data, User $user): GroupHike
    {
        return DB::transaction(function () use ($hike, $data, $user) {
            $fields = collect($data)->except(['images'])->toArray();
            $fields['updated_by'] = $user->id;

            if (isset($fields['slug']) && $fields['slug'] !== $hike->slug) {
                $fields['slug'] = $this->ensureUniqueSlug($fields['slug'], $hike->id);
            }

            $hike->update($fields);

            if (array_key_exists('images', $data)) {
                $this->syncGalleryImages($hike, $data['images'] ?? []);
            }

            ActivityLogger::log(
                event: 'updated',
                subject: $hike,
                causer: $user,
                logName: 'group_hikes',
            );

            return $hike;
        });
    }

    /**
     * Delete a group hike.
     */
    public function delete(GroupHike $hike, User $user): void
    {
        ActivityLogger::log(
            event: 'deleted',
            subject: $hike,
            causer: $user,
            logName: 'group_hikes',
        );

        $hike->delete();
    }

    /**
     * Publish a group hike.
     */
    public function publish(GroupHike $hike, User $user): GroupHike
    {
        $hike->update([
            'status' => GroupHikeStatus::Published,
            'published_at' => $hike->published_at ?? now(),
            'updated_by' => $user->id,
        ]);

        ActivityLogger::log(
            event: 'published',
            subject: $hike,
            causer: $user,
            logName: 'group_hikes',
        );

        return $hike;
    }

    /**
     * Unpublish a group hike (back to draft).
     */
    public function unpublish(GroupHike $hike, User $user): GroupHike
    {
        $hike->update([
            'status' => GroupHikeStatus::Draft,
            'published_at' => null,
            'updated_by' => $user->id,
        ]);

        ActivityLogger::log(
            event: 'unpublished',
            subject: $hike,
            causer: $user,
            logName: 'group_hikes',
        );

        return $hike;
    }

    /**
     * Cancel a group hike.
     */
    public function cancel(GroupHike $hike, string $reason, User $user): GroupHike
    {
        $hike->update([
            'status' => GroupHikeStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'updated_by' => $user->id,
        ]);

        ActivityLogger::log(
            event: 'cancelled',
            subject: $hike,
            causer: $user,
            properties: ['reason' => $reason],
            logName: 'group_hikes',
        );

        return $hike;
    }

    /**
     * Sync gallery images using delete-and-reinsert pattern.
     *
     * @param  array<int, array{media_id: int, caption?: string|null, sort_order?: int}>  $images
     */
    public function syncGalleryImages(GroupHike $hike, array $images): void
    {
        $hike->images()->delete();

        foreach ($images as $index => $imageData) {
            GroupHikeImage::create([
                'group_hike_id' => $hike->id,
                'media_id' => $imageData['media_id'],
                'caption' => $imageData['caption'] ?? null,
                'sort_order' => $imageData['sort_order'] ?? $index,
                'created_at' => now(),
            ]);
        }
    }

    private function ensureUniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $original = $slug;
        $counter = 1;

        while (GroupHike::query()
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
