<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MediaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): Response
    {
        return $user->hasPermission('media.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view media.');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Media $media): Response
    {
        return $user->hasPermission('media.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view media.');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): Response
    {
        return $user->hasPermission('media.create')
            ? Response::allow()
            : Response::deny('You do not have permission to upload media.');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Media $media): Response
    {
        return $user->hasPermission('media.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update media.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Media $media): Response
    {
        return $user->hasPermission('media.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete media.');
    }
}
