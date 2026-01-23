<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): Response
    {
        return $user->hasPermission('users.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view users.');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): Response
    {
        if (! $this->canManageUser($user, $model)) {
            return Response::deny('You do not have permission to view this user.');
        }

        return $user->hasPermission('users.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view users.');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): Response
    {
        return $user->hasPermission('users.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create users.');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): Response
    {
        if (! $this->canManageUser($user, $model)) {
            return Response::deny('You do not have permission to update this user.');
        }

        return $user->hasPermission('users.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update users.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): Response
    {
        if ($user->id === $model->id) {
            return Response::deny('You cannot delete your own account.');
        }

        if (! $this->canManageUser($user, $model)) {
            return Response::deny('You do not have permission to delete this user.');
        }

        return $user->hasPermission('users.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete users.');
    }

    /**
     * Determine whether the user can manage roles for the model.
     */
    public function manageRoles(User $user, User $model): Response
    {
        if (! $this->canManageUser($user, $model)) {
            return Response::deny('You do not have permission to manage this user\'s role.');
        }

        return $user->hasPermission('users.manage_roles')
            ? Response::allow()
            : Response::deny('You do not have permission to change user roles.');
    }

    /**
     * Determine whether the user can assign the super admin role.
     */
    public function assignSuperAdmin(User $user): Response
    {
        return $user->isSuperAdmin()
            ? Response::allow()
            : Response::deny('Only super admins can assign the super admin role.');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): Response
    {
        if (! $this->canManageUser($user, $model)) {
            return Response::deny('You do not have permission to restore this user.');
        }

        return $user->hasPermission('users.restore')
            ? Response::allow()
            : Response::deny('You do not have permission to restore users.');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): Response
    {
        if ($user->id === $model->id) {
            return Response::deny('You cannot permanently delete your own account.');
        }

        if (! $this->canManageUser($user, $model)) {
            return Response::deny('You do not have permission to permanently delete this user.');
        }

        return $user->hasPermission('users.force_delete')
            ? Response::allow()
            : Response::deny('You do not have permission to permanently delete users.');
    }

    /**
     * Check if the authenticated user can manage the target user.
     * Only super admins can manage other super admin users.
     */
    protected function canManageUser(User $authUser, User $targetUser): bool
    {
        if ($targetUser->isSuperAdmin() && ! $authUser->isSuperAdmin()) {
            return false;
        }

        return true;
    }
}
