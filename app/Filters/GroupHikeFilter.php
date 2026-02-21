<?php

namespace App\Filters;

use App\Enums\GroupHikeStatus;
use Illuminate\Database\Eloquent\Builder;

class GroupHikeFilter extends QueryFilter
{
    /** @var array<string> */
    protected array $sortableColumns = [
        'start_date',
        'created_at',
        'title',
        'price',
    ];

    public function search(Builder $query, string $value): void
    {
        $query->where(function (Builder $q) use ($value) {
            $q->where('title', 'like', "%{$value}%")
                ->orWhere('custom_location_name', 'like', "%{$value}%");
        });
    }

    public function status(Builder $query, string $value): void
    {
        $allowed = array_column(GroupHikeStatus::cases(), 'value');
        $this->applyCommaSeparated($query, $value, 'status', $allowed);
    }

    public function organizer_id(Builder $query, string $value): void
    {
        if (! auth()->user()?->hasPermission('group_hikes.view_all')) {
            return;
        }

        $query->where('organizer_id', (int) $value);
    }

    public function company_id(Builder $query, string $value): void
    {
        $query->where('company_id', (int) $value);
    }

    public function trail_id(Builder $query, string $value): void
    {
        $query->where('trail_id', (int) $value);
    }

    public function region_id(Builder $query, string $value): void
    {
        $query->where('region_id', (int) $value);
    }

    public function date_from(Builder $query, string $value): void
    {
        if (strtotime($value) !== false) {
            $query->whereDate('start_date', '>=', $value);
        }
    }

    public function date_to(Builder $query, string $value): void
    {
        if (strtotime($value) !== false) {
            $query->whereDate('start_date', '<=', $value);
        }
    }

    public function is_featured(Builder $query, string $value): void
    {
        $query->where('is_featured', filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }

    public function sort(Builder $query, string $value): void
    {
        // No-op: handled by applyHikeSorting
    }

    public function order(Builder $query, string $value): void
    {
        // No-op: handled by applyHikeSorting
    }

    public function applyHikeSorting(Builder $query): void
    {
        $column = $this->request->input('sort', 'start_date');
        $direction = $this->request->input('order', 'asc');

        $this->applySorting($query, $column, $direction, $this->sortableColumns);

        if (! in_array($column, $this->sortableColumns, true)) {
            $query->orderBy('start_date', 'asc');
        }
    }
}
