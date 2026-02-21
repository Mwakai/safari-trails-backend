<?php

namespace App\Http\Controllers;

use App\Filters\GroupHikeFilter;
use App\Http\Requests\CancelGroupHikeRequest;
use App\Http\Requests\ListGroupHikesRequest;
use App\Http\Requests\PublishGroupHikeRequest;
use App\Http\Requests\StoreGroupHikeRequest;
use App\Http\Requests\UpdateGroupHikeRequest;
use App\Http\Resources\GroupHikeListResource;
use App\Http\Resources\GroupHikeResource;
use App\Models\GroupHike;
use App\Services\GroupHikeService;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GroupHikeController extends Controller
{
    use ApiResponses;

    public function __construct(private GroupHikeService $groupHikeService) {}

    public function index(ListGroupHikesRequest $request, GroupHikeFilter $filters): JsonResponse
    {
        $response = Gate::inspect('viewAny', GroupHike::class);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $query = GroupHike::query()->with(['featuredImage', 'region']);

        if (! $request->user()->hasPermission('group_hikes.view_all')) {
            $userId = $request->user()->id;
            $companyId = $request->user()->company_id;

            $query->where(function ($q) use ($userId, $companyId) {
                $q->where('organizer_id', $userId);
                if ($companyId) {
                    $q->orWhere('company_id', $companyId);
                }
            });
        }

        $query->filter($filters);
        $filters->applyHikeSorting($query);

        $hikes = $query->paginate($filters->perPage());

        return $this->ok('Group hikes retrieved', [
            'group_hikes' => GroupHikeListResource::collection($hikes),
            'meta' => [
                'current_page' => $hikes->currentPage(),
                'last_page' => $hikes->lastPage(),
                'per_page' => $hikes->perPage(),
                'total' => $hikes->total(),
            ],
        ]);
    }

    public function show(GroupHike $groupHike): JsonResponse
    {
        $response = Gate::inspect('view', $groupHike);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $groupHike->load([
            'organizer',
            'company.logo',
            'trail',
            'region',
            'featuredImage',
            'images.media',
            'creator',
            'updater',
        ]);

        return $this->ok('Group hike retrieved', [
            'group_hike' => new GroupHikeResource($groupHike),
        ]);
    }

    public function store(StoreGroupHikeRequest $request): JsonResponse
    {
        $response = Gate::inspect('create', GroupHike::class);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $hike = $this->groupHikeService->create($request->validated(), $request->user());

        $hike->load([
            'organizer',
            'company',
            'trail',
            'region',
            'featuredImage',
            'images.media',
            'creator',
        ]);

        return $this->success('Group hike created successfully', [
            'group_hike' => new GroupHikeResource($hike),
        ], 201);
    }

    public function update(UpdateGroupHikeRequest $request, GroupHike $groupHike): JsonResponse
    {
        $response = Gate::inspect('update', $groupHike);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $hike = $this->groupHikeService->update($groupHike, $request->validated(), $request->user());

        $hike->load([
            'organizer',
            'company',
            'trail',
            'region',
            'featuredImage',
            'images.media',
            'creator',
            'updater',
        ]);

        return $this->ok('Group hike updated successfully', [
            'group_hike' => new GroupHikeResource($hike),
        ]);
    }

    public function destroy(GroupHike $groupHike): JsonResponse
    {
        $response = Gate::inspect('delete', $groupHike);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $this->groupHikeService->delete($groupHike, auth()->user());

        return $this->ok('Group hike deleted successfully');
    }

    public function publish(PublishGroupHikeRequest $request, GroupHike $groupHike): JsonResponse
    {
        $response = Gate::inspect('publish', $groupHike);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $hike = $this->groupHikeService->publish($groupHike, $request->user());

        return $this->ok('Group hike published successfully', [
            'group_hike' => new GroupHikeResource($hike->load(['featuredImage', 'region'])),
        ]);
    }

    public function unpublish(Request $request, GroupHike $groupHike): JsonResponse
    {
        $response = Gate::inspect('publish', $groupHike);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $hike = $this->groupHikeService->unpublish($groupHike, $request->user());

        return $this->ok('Group hike unpublished successfully', [
            'group_hike' => new GroupHikeResource($hike->load(['featuredImage', 'region'])),
        ]);
    }

    public function cancel(CancelGroupHikeRequest $request, GroupHike $groupHike): JsonResponse
    {
        $response = Gate::inspect('cancel', $groupHike);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $hike = $this->groupHikeService->cancel(
            $groupHike,
            $request->validated('cancellation_reason'),
            $request->user(),
        );

        return $this->ok('Group hike cancelled successfully', [
            'group_hike' => new GroupHikeResource($hike->load(['featuredImage', 'region'])),
        ]);
    }

    public function galleryReorder(Request $request, GroupHike $groupHike): JsonResponse
    {
        $response = Gate::inspect('update', $groupHike);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $request->validate([
            'image_ids' => ['required', 'array'],
            'image_ids.*' => ['integer'],
        ]);

        $imageIds = $request->input('image_ids');

        foreach ($imageIds as $sortOrder => $imageId) {
            $groupHike->images()->where('id', $imageId)->update(['sort_order' => $sortOrder]);
        }

        return $this->ok('Gallery reordered successfully');
    }
}
