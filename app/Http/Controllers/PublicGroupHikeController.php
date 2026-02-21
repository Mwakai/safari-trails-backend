<?php

namespace App\Http\Controllers;

use App\Enums\GroupHikeStatus;
use App\Filters\PublicGroupHikeFilter;
use App\Http\Requests\PublicListGroupHikesRequest;
use App\Http\Resources\PublicGroupHikeListResource;
use App\Http\Resources\PublicGroupHikeResource;
use App\Models\Company;
use App\Models\GroupHike;
use App\Models\Trail;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;

class PublicGroupHikeController extends Controller
{
    use ApiResponses;

    public function index(PublicListGroupHikesRequest $request, PublicGroupHikeFilter $filters): JsonResponse
    {
        $query = GroupHike::query()
            ->with(['featuredImage', 'region', 'company'])
            ->where('status', GroupHikeStatus::Published)
            ->where('start_date', '>=', today())
            ->filter($filters);

        $filters->applyHikeSorting($query);

        $hikes = $query->paginate($filters->perPage());

        return $this->ok('Group hikes retrieved', [
            'group_hikes' => PublicGroupHikeListResource::collection($hikes),
            'meta' => [
                'current_page' => $hikes->currentPage(),
                'last_page' => $hikes->lastPage(),
                'per_page' => $hikes->perPage(),
                'total' => $hikes->total(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $hike = GroupHike::query()
            ->where('slug', $slug)
            ->where('status', GroupHikeStatus::Published)
            ->firstOrFail();

        $hike->load([
            'company.logo',
            'trail',
            'region',
            'featuredImage',
            'images.media',
        ]);

        return $this->ok('Group hike retrieved', [
            'group_hike' => new PublicGroupHikeResource($hike),
        ]);
    }

    public function featured(): JsonResponse
    {
        $hikes = GroupHike::query()
            ->with(['featuredImage', 'region', 'company'])
            ->where('status', GroupHikeStatus::Published)
            ->where('start_date', '>=', today())
            ->where('is_featured', true)
            ->orderBy('start_date')
            ->limit(6)
            ->get();

        return $this->ok('Featured group hikes retrieved', [
            'group_hikes' => PublicGroupHikeListResource::collection($hikes),
        ]);
    }

    public function thisWeek(): JsonResponse
    {
        $hikes = GroupHike::query()
            ->with(['featuredImage', 'region', 'company'])
            ->where('status', GroupHikeStatus::Published)
            ->whereBetween('start_date', [today(), today()->addDays(7)])
            ->orderBy('start_date')
            ->get();

        return $this->ok('This week\'s group hikes retrieved', [
            'group_hikes' => PublicGroupHikeListResource::collection($hikes),
        ]);
    }

    public function byCompany(string $companySlug): JsonResponse
    {
        $company = Company::where('slug', $companySlug)->where('is_active', true)->firstOrFail();

        $hikes = GroupHike::query()
            ->with(['featuredImage', 'region', 'company'])
            ->where('status', GroupHikeStatus::Published)
            ->where('company_id', $company->id)
            ->where('start_date', '>=', today())
            ->orderBy('start_date')
            ->get();

        return $this->ok('Group hikes by company retrieved', [
            'group_hikes' => PublicGroupHikeListResource::collection($hikes),
        ]);
    }

    public function byTrail(string $trailSlug): JsonResponse
    {
        $trail = Trail::where('slug', $trailSlug)->firstOrFail();

        $hikes = GroupHike::query()
            ->with(['featuredImage', 'region', 'company'])
            ->where('status', GroupHikeStatus::Published)
            ->where('trail_id', $trail->id)
            ->where('start_date', '>=', today())
            ->orderBy('start_date')
            ->get();

        return $this->ok('Group hikes by trail retrieved', [
            'group_hikes' => PublicGroupHikeListResource::collection($hikes),
        ]);
    }
}
