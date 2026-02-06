<?php

namespace App\Filters;

use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Builder;

class UserFilter extends QueryFilter
{
    /** @var array<string> */
    protected array $sortableColumns = [
        'created_at',
        'updated_at',
        'first_name',
        'last_name',
        'email',
    ];

    public function search(Builder $query, string $value): void
    {
        $this->applySearch($query, $value, ['first_name', 'last_name', 'email']);
    }

    public function status(Builder $query, string $value): void
    {
        $allowed = array_column(UserStatus::cases(), 'value');
        $this->applyCommaSeparated($query, $value, 'status', $allowed);
    }

    public function role_id(Builder $query, string $value): void
    {
        $query->where('role_id', (int) $value);
    }

    public function company_id(Builder $query, string $value): void
    {
        $query->where('company_id', (int) $value);
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

    public function sort(Builder $query, string $value): void
    {
        // No-op: handled by applyUserSorting
    }

    public function order(Builder $query, string $value): void
    {
        // No-op: handled by applyUserSorting
    }

    public function applyUserSorting(Builder $query): void
    {
        $column = $this->request->input('sort', 'created_at');
        $direction = $this->request->input('order', 'desc');

        $this->applySorting($query, $column, $direction, $this->sortableColumns);

        if (! in_array($column, $this->sortableColumns, true)) {
            $query->latest();
        }
    }
}
