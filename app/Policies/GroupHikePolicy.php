<?php

namespace App\Policies;

use App\Models\GroupHike;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GroupHikePolicy
{
    public function viewAny(User $user): Response
    {
        return $user->hasAnyPermission(['group_hikes.view', 'group_hikes.view_all'])
            ? Response::allow()
            : Response::deny('You do not have permission to view group hikes.');
    }

    public function view(User $user, GroupHike $groupHike): Response
    {
        if ($user->hasPermission('group_hikes.view_all')) {
            return Response::allow();
        }

        if ($user->hasPermission('group_hikes.view')) {
            if ($groupHike->organizer_id === $user->id) {
                return Response::allow();
            }
            if ($user->company_id && $groupHike->company_id === $user->company_id) {
                return Response::allow();
            }
        }

        return Response::deny('You do not have permission to view this group hike.');
    }

    public function create(User $user): Response
    {
        return $user->hasPermission('group_hikes.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create group hikes.');
    }

    public function update(User $user, GroupHike $groupHike): Response
    {
        if ($groupHike->isCancelled() || $groupHike->isCompleted()) {
            return Response::deny('Cannot update a cancelled or completed hike.');
        }

        if ($user->hasPermission('group_hikes.update_all')) {
            return Response::allow();
        }

        if ($user->hasPermission('group_hikes.update')) {
            if ($groupHike->organizer_id === $user->id) {
                return Response::allow();
            }
            if ($user->company_id && $groupHike->company_id === $user->company_id) {
                return Response::allow();
            }
        }

        return Response::deny('You do not have permission to update this group hike.');
    }

    public function delete(User $user, GroupHike $groupHike): Response
    {
        if ($user->hasPermission('group_hikes.delete_all')) {
            return Response::allow();
        }

        if ($user->hasPermission('group_hikes.delete')) {
            $isOwn = $groupHike->organizer_id === $user->id
                || ($user->company_id && $groupHike->company_id === $user->company_id);

            if ($isOwn && $groupHike->isDraft()) {
                return Response::allow();
            }
        }

        return Response::deny('You do not have permission to delete this group hike.');
    }

    public function publish(User $user, GroupHike $groupHike): Response
    {
        if ($user->hasPermission('group_hikes.update_all')) {
            return Response::allow();
        }

        if ($user->hasPermission('group_hikes.update')) {
            if ($groupHike->organizer_id === $user->id) {
                return Response::allow();
            }
            if ($user->company_id && $groupHike->company_id === $user->company_id) {
                return Response::allow();
            }
        }

        return Response::deny('You do not have permission to publish this group hike.');
    }

    public function cancel(User $user, GroupHike $groupHike): Response
    {
        if (! $groupHike->isPublished()) {
            return Response::deny('Only published hikes can be cancelled.');
        }

        if ($user->hasPermission('group_hikes.update_all')) {
            return Response::allow();
        }

        if ($user->hasPermission('group_hikes.update')) {
            if ($groupHike->organizer_id === $user->id) {
                return Response::allow();
            }
            if ($user->company_id && $groupHike->company_id === $user->company_id) {
                return Response::allow();
            }
        }

        return Response::deny('You do not have permission to cancel this group hike.');
    }
}
