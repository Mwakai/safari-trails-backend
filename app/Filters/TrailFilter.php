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
        'duration_min',
        'difficulty',
        'published_at',
    ];

    public function search(Builder $query, string $value): void
    {
        $query->where(function (Builder $q) use ($value) {
            $q->where('name', 'like', "%{$value}%")
                ->orWhere('location_name', 'like', "%{$value}%")
                ->orWhereHas('region', function (Builder $rq) use ($value) {
                    $rq->where('name', 'like', "%{$value}%");
                });
        });
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

    public function region(Builder $query, string $value): void
    {
        $slugs = array_filter(array_map('trim', explode(',', $value)));

        if (empty($slugs)) {
            return;
        }

        $query->whereHas('region', function (Builder $q) use ($slugs) {
            if (count($slugs) === 1) {
                $q->where('slug', $slugs[0]);
            } else {
                $q->whereIn('slug', $slugs);
            }
        });
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
            $hours = (float) $value;

            $query->where(function (Builder $q) use ($hours) {
                $q->where(function (Builder $q2) use ($hours) {
                    $q2->where('duration_type', 'hours')
                        ->where('duration_min', '>=', $hours);
                })->orWhere(function (Builder $q2) use ($hours) {
                    $q2->where('duration_type', 'days')
                        ->where('duration_min', '>=', $hours / 8);
                });
            });
        }
    }

    public function max_duration(Builder $query, string $value): void
    {
        if (is_numeric($value)) {
            $hours = (float) $value;

            $query->where(function (Builder $q) use ($hours) {
                $q->where(function (Builder $q2) use ($hours) {
                    $q2->where('duration_type', 'hours')
                        ->where(function (Builder $q3) use ($hours) {
                            $q3->whereNotNull('duration_max')
                                ->where('duration_max', '<=', $hours);
                        })->orWhere(function (Builder $q3) use ($hours) {
                            $q3->where('duration_type', 'hours')
                                ->whereNull('duration_max')
                                ->where('duration_min', '<=', $hours);
                        });
                })->orWhere(function (Builder $q2) use ($hours) {
                    $q2->where('duration_type', 'days')
                        ->where(function (Builder $q3) use ($hours) {
                            $q3->whereNotNull('duration_max')
                                ->where('duration_max', '<=', $hours / 8);
                        })->orWhere(function (Builder $q3) use ($hours) {
                            $q3->where('duration_type', 'days')
                                ->whereNull('duration_max')
                                ->where('duration_min', '<=', $hours / 8);
                        });
                });
            });
        }
    }

    public function duration_type(Builder $query, string $value): void
    {
        if (in_array($value, ['hours', 'days'])) {
            $query->where('duration_type', $value);
        }
    }

    public function is_multi_day(Builder $query, string $value): void
    {
        if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
            $query->where('duration_type', 'days');
        } else {
            $query->where('duration_type', 'hours');
        }
    }

    public function requires_guide(Builder $query, string $value): void
    {
        $query->where('requires_guide', filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }

    public function requires_permit(Builder $query, string $value): void
    {
        $query->where('requires_permit', filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }

    public function accommodation(Builder $query, string $value): void
    {
        $types = array_filter(array_map('trim', explode(',', $value)));

        foreach ($types as $type) {
            $query->whereJsonContains('accommodation_types', $type);
        }
    }

    public function best_month(Builder $query, string $value): void
    {
        $month = (int) $value;

        if ($month < 1 || $month > 12) {
            return;
        }

        $query->where(function (Builder $q) use ($month) {
            $q->where('is_year_round', true)
                ->orWhereHas('bestMonths', function (Builder $q2) use ($month) {
                    $q2->where('month', $month);
                });
        });
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
