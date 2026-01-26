<?php

namespace App\Policies;

use App\Models\Amenity;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AmenityPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): Response
    {
        return $user->hasPermission('amenities.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view amenities.');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Amenity $amenity): Response
    {
        return $user->hasPermission('amenities.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view amenities.');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): Response
    {
        return $user->hasPermission('amenities.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create amenities.');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Amenity $amenity): Response
    {
        return $user->hasPermission('amenities.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update amenities.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Amenity $amenity): Response
    {
        return $user->hasPermission('amenities.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete amenities.');
    }
}
