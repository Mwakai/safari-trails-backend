<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class PublicGroupHikeFilter extends GroupHikeFilter
{
    public function status(Builder $query, string $value): void
    {
        // No-op: public always filters to published only
    }

    public function organizer_id(Builder $query, string $value): void
    {
        // No-op: organizer filter not exposed publicly
    }

    public function difficulty(Builder $query, string $value): void
    {
        $value = trim($value);

        if ($value !== '') {
            $query->where('difficulty', $value);
        }
    }

    public function min_price(Builder $query, string $value): void
    {
        if (is_numeric($value)) {
            $query->where('price', '>=', (float) $value);
        }
    }

    public function max_price(Builder $query, string $value): void
    {
        if (is_numeric($value)) {
            $query->where('price', '<=', (float) $value);
        }
    }

    public function is_free(Builder $query, string $value): void
    {
        if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
            $query->where(function (Builder $q) {
                $q->whereNull('price')->orWhere('price', 0);
            });
        }
    }

    public function company(Builder $query, string $value): void
    {
        $slug = trim($value);

        if ($slug !== '') {
            $query->whereHas('company', function (Builder $q) use ($slug) {
                $q->where('slug', $slug);
            });
        }
    }

    public function trail(Builder $query, string $value): void
    {
        $slug = trim($value);

        if ($slug !== '') {
            $query->whereHas('trail', function (Builder $q) use ($slug) {
                $q->where('slug', $slug);
            });
        }
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
}
