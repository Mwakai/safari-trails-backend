<?php

namespace App\Policies;

use App\Models\Trail;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TrailPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): Response
    {
        return $user->hasPermission('trails.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view trails.');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Trail $trail): Response
    {
        return $user->hasPermission('trails.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view trails.');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): Response
    {
        return $user->hasPermission('trails.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create trails.');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Trail $trail): Response
    {
        return $user->hasPermission('trails.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update trails.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Trail $trail): Response
    {
        return $user->hasPermission('trails.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete trails.');
    }

    /**
     * Determine whether the user can update the trail status.
     */
    public function updateStatus(User $user, Trail $trail): Response
    {
        return $user->hasPermission('trails.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update trail status.');
    }
}
