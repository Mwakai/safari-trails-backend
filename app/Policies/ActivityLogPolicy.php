<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ActivityLogPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): Response
    {
        return $user->hasPermission('activity_logs.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view activity logs.');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ActivityLog $activityLog): Response
    {
        return $user->hasPermission('activity_logs.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view activity logs.');
    }
}
