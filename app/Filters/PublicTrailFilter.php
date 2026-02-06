<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class PublicTrailFilter extends TrailFilter
{
    public function status(Builder $query, string $value): void
    {
        // No-op: public trails are always published
    }

    public function created_by(Builder $query, string $value): void
    {
        // No-op: not available publicly
    }

    public function trashed(Builder $query, string $value): void
    {
        // No-op: not available publicly
    }

    public function bounds(Builder $query, string $value): void
    {
        $parts = array_map('trim', explode(',', $value));

        if (count($parts) !== 4) {
            return;
        }

        [$swLat, $swLng, $neLat, $neLng] = array_map('floatval', $parts);

        $query->whereBetween('latitude', [$swLat, $neLat])
            ->whereBetween('longitude', [$swLng, $neLng]);
    }

    public function near_lat(Builder $query, string $value): void
    {
        $nearLng = $this->request->input('near_lng');

        if (! is_numeric($value) || ! is_numeric($nearLng)) {
            return;
        }

        $lat = (float) $value;
        $lng = (float) $nearLng;
        $radius = min((float) $this->request->input('radius', 25), 200);

        $query->selectRaw(
            '*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
            [$lat, $lng, $lat]
        )->having('distance', '<=', $radius)
            ->orderBy('distance');
    }

    public function near_lng(Builder $query, string $value): void
    {
        // No-op: handled by near_lat
    }

    public function radius(Builder $query, string $value): void
    {
        // No-op: handled by near_lat
    }
}
