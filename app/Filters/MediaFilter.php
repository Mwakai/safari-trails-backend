<?php

namespace App\Filters;

use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Builder;

class MediaFilter extends QueryFilter
{
    /** @var array<string> */
    protected array $sortableColumns = [
        'created_at',
        'updated_at',
        'original_filename',
        'size',
        'type',
    ];

    public function search(Builder $query, string $value): void
    {
        $this->applySearch($query, $value, ['original_filename', 'alt_text']);
    }

    public function type(Builder $query, string $value): void
    {
        $allowed = array_column(MediaType::cases(), 'value');
        $this->applyCommaSeparated($query, $value, 'type', $allowed);
    }

    public function uploaded_by(Builder $query, string $value): void
    {
        $query->where('uploaded_by', (int) $value);
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

    public function sort(Builder $query, string $value): void
    {
        // No-op: handled by applyMediaSorting
    }

    public function order(Builder $query, string $value): void
    {
        // No-op: handled by applyMediaSorting
    }

    public function applyMediaSorting(Builder $query): void
    {
        $column = $this->request->input('sort', 'created_at');
        $direction = $this->request->input('order', 'desc');

        $this->applySorting($query, $column, $direction, $this->sortableColumns);

        if (! in_array($column, $this->sortableColumns, true)) {
            $query->latest();
        }
    }
}
