<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class QueryFilter
{
    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder
    {
        foreach ($this->request->query() as $name => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (method_exists($this, $name)) {
                $this->{$name}($query, $value);
            }
        }

        return $query;
    }

    protected function applySearch(Builder $query, string $value, array $columns): void
    {
        $query->where(function (Builder $q) use ($value, $columns) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', "%{$value}%");
            }
        });
    }

    /**
     * @param  array<string>  $allowed
     */
    protected function applyCommaSeparated(Builder $query, string $value, string $column, array $allowed = []): void
    {
        $values = array_filter(array_map('trim', explode(',', $value)));

        if ($allowed) {
            $values = array_intersect($values, $allowed);
        }

        if (empty($values)) {
            return;
        }

        if (count($values) === 1) {
            $query->where($column, reset($values));
        } else {
            $query->whereIn($column, $values);
        }
    }

    /**
     * @param  array<string>  $allowedColumns
     */
    protected function applySorting(Builder $query, string $column, string $direction = 'asc', array $allowedColumns = []): void
    {
        if ($allowedColumns && ! in_array($column, $allowedColumns, true)) {
            return;
        }

        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($column, $direction);
    }

    protected function applyDateRange(Builder $query, string $value, string $column, string $operator): void
    {
        if (strtotime($value) !== false) {
            $query->where($column, $operator, $value);
        }
    }

    protected function applyTrashed(Builder $query, string $value): void
    {
        if (! auth()->check() || ! auth()->user()->hasPermission('trails.delete')) {
            return;
        }

        match ($value) {
            'with' => $query->withTrashed(),
            'only' => $query->onlyTrashed(),
            default => null,
        };
    }

    public function perPage(int $default = 15): int
    {
        $perPage = (int) $this->request->input('per_page', $default);

        return min(max($perPage, 1), 100);
    }
}
