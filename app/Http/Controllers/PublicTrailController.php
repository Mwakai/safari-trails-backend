<?php

namespace App\Http\Controllers;

use App\Enums\TrailStatus;
use App\Filters\PublicTrailFilter;
use App\Http\Requests\ListPublicTrailsRequest;
use App\Http\Resources\PublicTrailListResource;
use App\Http\Resources\TrailResource;
use App\Models\Trail;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;

class PublicTrailController extends Controller
{
    use ApiResponses;

    public function index(ListPublicTrailsRequest $request, PublicTrailFilter $filters): JsonResponse
    {
        $query = Trail::query()
            ->with(['featuredImage', 'amenities'])
            ->where('status', TrailStatus::Published)
            ->filter($filters);

        $filters->applyTrailSorting($query);

        $trails = $query->paginate($filters->perPage());

        return $this->ok('Trails retrieved', [
            'trails' => PublicTrailListResource::collection($trails),
            'meta' => [
                'current_page' => $trails->currentPage(),
                'last_page' => $trails->lastPage(),
                'per_page' => $trails->perPage(),
                'total' => $trails->total(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $trail = Trail::query()
            ->where('slug', $slug)
            ->where('status', TrailStatus::Published)
            ->firstOrFail();

        $trail->load([
            'featuredImage',
            'amenities',
            'images.media',
            'gpxFiles.media',
        ]);

        return $this->ok('Trail retrieved', [
            'trail' => new TrailResource($trail),
        ]);
    }
}
