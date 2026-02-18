<?php

namespace App\Http\Controllers;

use App\Enums\TrailDifficulty;
use App\Enums\TrailStatus;
use App\Filters\PublicTrailFilter;
use App\Http\Requests\ListPublicTrailsRequest;
use App\Http\Requests\MapMarkersRequest;
use App\Http\Resources\MapMarkerResource;
use App\Http\Resources\PublicTrailListResource;
use App\Http\Resources\TrailResource;
use App\Models\Amenity;
use App\Models\Trail;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PublicTrailController extends Controller
{
    use ApiResponses;

    public function index(ListPublicTrailsRequest $request, PublicTrailFilter $filters): JsonResponse
    {
        $query = Trail::query()
            ->with(['featuredImage', 'amenities', 'region:id,name,slug'])
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
            'region',
            'amenities',
            'images.media',
            'gpxFiles.media',
            'bestMonths',
            'itineraryDays',
        ]);

        return $this->ok('Trail retrieved', [
            'trail' => new TrailResource($trail),
        ]);
    }

    public function mapMarkers(MapMarkersRequest $request, PublicTrailFilter $filters): JsonResponse
    {
        $hasFilters = ! empty(array_filter($request->query(), fn ($v) => $v !== null && $v !== ''));

        if (! $hasFilters) {
            $trails = Cache::remember('trails.map.all', 600, function () {
                return Trail::query()
                    ->select(['id', 'slug', 'name', 'latitude', 'longitude', 'difficulty', 'distance_km', 'duration_type', 'duration_min', 'duration_max', 'elevation_gain_m', 'region_id', 'featured_image_id'])
                    ->with(['featuredImage:id,disk,path,variants', 'region:id,name,slug'])
                    ->where('status', TrailStatus::Published)
                    ->limit(500)
                    ->get();
            });
        } else {
            $query = Trail::query()
                ->select(['id', 'slug', 'name', 'latitude', 'longitude', 'difficulty', 'distance_km', 'duration_type', 'duration_min', 'duration_max', 'elevation_gain_m', 'region_id', 'featured_image_id'])
                ->with(['featuredImage:id,disk,path,variants', 'region:id,name,slug'])
                ->where('status', TrailStatus::Published)
                ->filter($filters)
                ->limit(500);

            $filters->applyTrailSorting($query);

            $trails = $query->get();
        }

        return $this->ok('Map markers retrieved', [
            'trails' => MapMarkerResource::collection($trails),
            'meta' => [
                'total' => $trails->count(),
                'bounds_applied' => $request->has('bounds'),
            ],
        ]);
    }

    public function related(string $slug): JsonResponse
    {
        $trail = Trail::query()
            ->where('slug', $slug)
            ->where('status', TrailStatus::Published)
            ->firstOrFail();

        $trails = Cache::remember("trail.related.{$trail->id}", 1800, function () use ($trail) {
            $adjacentDifficulties = $this->getAdjacentDifficulties($trail->difficulty);

            return Trail::query()
                ->select(['id', 'slug', 'name', 'latitude', 'longitude', 'difficulty', 'distance_km', 'duration_type', 'duration_min', 'duration_max', 'elevation_gain_m', 'region_id', 'featured_image_id'])
                ->with(['featuredImage:id,disk,path,variants', 'region:id,name,slug'])
                ->where('status', TrailStatus::Published)
                ->where('id', '!=', $trail->id)
                ->where(function ($query) use ($trail, $adjacentDifficulties) {
                    $query->where('region_id', $trail->region_id)
                        ->orWhereIn('difficulty', $adjacentDifficulties);
                })
                ->orderByRaw('CASE WHEN region_id = ? THEN 0 ELSE 1 END', [$trail->region_id])
                ->limit(6)
                ->get();
        });

        return $this->ok('Related trails retrieved', [
            'trails' => MapMarkerResource::collection($trails),
        ]);
    }

    public function filters(): JsonResponse
    {
        $data = Cache::remember('trails.filter_options', 3600, function () {
            $publishedScope = fn ($query) => $query->where('status', TrailStatus::Published);

            $regions = \App\Models\Region::query()
                ->active()
                ->ordered()
                ->withCount(['trails' => $publishedScope])
                ->get()
                ->filter(fn ($region) => $region->trails_count > 0)
                ->values()
                ->map(fn ($region) => [
                    'slug' => $region->slug,
                    'name' => $region->name,
                    'trails_count' => $region->trails_count,
                ]);

            $difficulties = Trail::query()
                ->where('status', TrailStatus::Published)
                ->selectRaw('difficulty, count(*) as trails_count')
                ->groupBy('difficulty')
                ->orderByDesc('trails_count')
                ->get()
                ->map(fn ($row) => [
                    'value' => $row->difficulty->value,
                    'label' => $row->difficulty->name,
                    'trails_count' => (int) $row->trails_count,
                ]);

            $amenities = Amenity::query()
                ->where('is_active', true)
                ->withCount(['trails' => $publishedScope])
                ->get()
                ->filter(fn ($amenity) => $amenity->trails_count > 0)
                ->sortByDesc('trails_count')
                ->values()
                ->map(fn ($amenity) => [
                    'id' => $amenity->id,
                    'name' => $amenity->name,
                    'slug' => $amenity->slug,
                    'trails_count' => $amenity->trails_count,
                ]);

            $durationTypes = collect(\App\Enums\DurationType::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => ucfirst($case->value),
            ])->values()->all();

            return [
                'regions' => $regions,
                'difficulties' => $difficulties,
                'amenities' => $amenities,
                'duration_types' => $durationTypes,
            ];
        });

        return $this->ok('Filter options retrieved', $data);
    }

    /**
     * @return array<string>
     */
    private function getAdjacentDifficulties(TrailDifficulty $difficulty): array
    {
        $ordered = [
            TrailDifficulty::Easy,
            TrailDifficulty::Moderate,
            TrailDifficulty::Difficult,
            TrailDifficulty::Expert,
        ];

        $index = array_search($difficulty, $ordered);
        $adjacent = [];

        if ($index > 0) {
            $adjacent[] = $ordered[$index - 1]->value;
        }
        $adjacent[] = $difficulty->value;
        if ($index < count($ordered) - 1) {
            $adjacent[] = $ordered[$index + 1]->value;
        }

        return $adjacent;
    }
}
