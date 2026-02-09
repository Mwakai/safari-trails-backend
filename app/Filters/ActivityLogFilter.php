<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class ActivityLogFilter extends QueryFilter
{
    /** @var array<string> */
    protected array $sortableColumns = [
        'created_at',
        'log_name',
        'event',
    ];

    public function search(Builder $query, string $value): void
    {
        $this->applySearch($query, $value, ['log_name', 'event']);
    }

    public function log_name(Builder $query, string $value): void
    {
        $this->applyCommaSeparated($query, $value, 'log_name');
    }

    public function event(Builder $query, string $value): void
    {
        $this->applyCommaSeparated($query, $value, 'event');
    }

    public function causer_id(Builder $query, string $value): void
    {
        $query->where('causer_id', (int) $value);
    }

    public function subject_type(Builder $query, string $value): void
    {
        $query->where('subject_type', $value);
    }

    public function subject_id(Builder $query, string $value): void
    {
        $query->where('subject_id', (int) $value);
    }

    public function created_after(Builder $query, string $value): void
    {
        $this->applyDateRange($query, $value, 'created_at', '>=');
    }

    public function created_before(Builder $query, string $value): void
    {
        $this->applyDateRange($query, $value, 'created_at', '<=');
    }

    public function sort(Builder $query, string $value): void
    {
        // No-op: handled by applyActivityLogSorting
    }

    public function order(Builder $query, string $value): void
    {
        // No-op: handled by applyActivityLogSorting
    }

    public function applyActivityLogSorting(Builder $query): void
    {
        $column = $this->request->input('sort', 'created_at');
        $direction = $this->request->input('order', 'desc');

        $this->applySorting($query, $column, $direction, $this->sortableColumns);

        if (! in_array($column, $this->sortableColumns, true)) {
            $query->latest();
        }
    }
}
