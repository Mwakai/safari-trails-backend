<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CompanyPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->hasAnyPermission(['group_hikes.view', 'group_hikes.view_all', 'companies.view'])
            ? Response::allow()
            : Response::deny('You do not have permission to view companies.');
    }

    public function view(User $user, Company $company): Response
    {
        return $user->hasAnyPermission(['group_hikes.view', 'group_hikes.view_all', 'companies.view'])
            ? Response::allow()
            : Response::deny('You do not have permission to view this company.');
    }

    public function create(User $user): Response
    {
        return $user->hasPermission('companies.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create companies.');
    }

    public function update(User $user, Company $company): Response
    {
        return $user->hasPermission('companies.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update companies.');
    }

    public function delete(User $user, Company $company): Response
    {
        return $user->hasPermission('companies.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete companies.');
    }
}
