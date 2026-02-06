<?php

namespace App\Filters;

use App\Enums\TrailDifficulty;
use App\Enums\TrailStatus;
use Illuminate\Database\Eloquent\Builder;

class TrailFilter extends QueryFilter
{
    /** @var array<string> */
    protected array $sortableColumns = [
        'created_at',
        'updated_at',
        'name',
        'distance_km',
        'duration_hours',
        'difficulty',
        'published_at',
    ];

    public function search(Builder $query, string $value): void
    {
        $this->applySearch($query, $value, ['name', 'location_name', 'county']);
    }

    public function status(Builder $query, string $value): void
    {
        $allowed = array_column(TrailStatus::cases(), 'value');
        $this->applyCommaSeparated($query, $value, 'status', $allowed);
    }

    public function difficulty(Builder $query, string $value): void
    {
        $allowed = array_column(TrailDifficulty::cases(), 'value');
        $this->applyCommaSeparated($query, $value, 'difficulty', $allowed);
    }

    public function county(Builder $query, string $value): void
    {
        $this->applyCommaSeparated($query, $value, 'county');
    }

    public function created_by(Builder $query, string $value): void
    {
        $query->where('created_by', (int) $value);
    }

    public function created_after(Builder $query, string $value): void
    {
        $this->applyDateRange($query, $value, 'created_at', '>=');
    }

    public function created_before(Builder $query, string $value): void
    {
        $this->applyDateRange($query, $value, 'created_at', '<=');
    }

    public function trashed(Builder $query, string $value): void
    {
        $this->applyTrashed($query, $value);
    }

    public function amenities(Builder $query, string $value): void
    {
        $ids = array_filter(array_map('intval', explode(',', $value)));

        foreach ($ids as $id) {
            $query->whereHas('amenities', function (Builder $q) use ($id) {
                $q->where('amenities.id', $id);
            });
        }
    }

    public function amenities_any(Builder $query, string $value): void
    {
        $ids = array_filter(array_map('intval', explode(',', $value)));

        if (empty($ids)) {
            return;
        }

        $query->whereHas('amenities', function (Builder $q) use ($ids) {
            $q->whereIn('amenities.id', $ids);
        });
    }

    public function min_distance(Builder $query, string $value): void
    {
        if (is_numeric($value)) {
            $query->where('distance_km', '>=', (float) $value);
        }
    }

    public function max_distance(Builder $query, string $value): void
    {
        if (is_numeric($value)) {
            $query->where('distance_km', '<=', (float) $value);
        }
    }

    public function min_duration(Builder $query, string $value): void
    {
        if (is_numeric($value)) {
            $query->where('duration_hours', '>=', (float) $value);
        }
    }

    public function max_duration(Builder $query, string $value): void
    {
        if (is_numeric($value)) {
            $query->where('duration_hours', '<=', (float) $value);
        }
    }

    public function sort(Builder $query, string $value): void
    {
        // No-op: handled by applyTrailSorting
    }

    public function order(Builder $query, string $value): void
    {
        // No-op: handled by applyTrailSorting
    }

    public function applyTrailSorting(Builder $query): void
    {
        $column = $this->request->input('sort', 'created_at');
        $direction = $this->request->input('order', 'desc');

        $this->applySorting($query, $column, $direction, $this->sortableColumns);

        if (! in_array($column, $this->sortableColumns, true)) {
            $query->latest();
        }
    }
}
