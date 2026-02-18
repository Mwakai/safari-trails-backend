<?php

namespace App\Observers;

use App\Models\Trail;
use Illuminate\Support\Facades\Cache;

class TrailObserver
{
    /**
     * Handle the Trail "created" event.
     */
    public function created(Trail $trail): void
    {
        $this->clearTrailCaches();
    }

    /**
     * Handle the Trail "updated" event.
     */
    public function updated(Trail $trail): void
    {
        Cache::forget("trail.{$trail->id}");
        Cache::forget("trail.slug.{$trail->slug}");
        Cache::forget("trail.related.{$trail->id}");
        $this->clearTrailCaches();
    }

    /**
     * Handle the Trail "deleted" event.
     */
    public function deleted(Trail $trail): void
    {
        Cache::forget("trail.{$trail->id}");
        Cache::forget("trail.slug.{$trail->slug}");
        Cache::forget("trail.related.{$trail->id}");
        $this->clearTrailCaches();
    }

    /**
     * Handle the Trail "restored" event.
     */
    public function restored(Trail $trail): void
    {
        $this->clearTrailCaches();
    }

    private function clearTrailCaches(): void
    {
        Cache::forget('trails.public');
        Cache::forget('trails.regions');
        Cache::forget('trails.difficulties');
        Cache::forget('trails.filter_options');
        Cache::forget('trails.map.all');
    }
}
