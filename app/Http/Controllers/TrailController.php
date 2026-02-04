<?php

namespace App\Http\Controllers;

use App\Enums\TrailStatus;
use App\Http\Requests\StoreTrailRequest;
use App\Http\Requests\UpdateTrailRequest;
use App\Http\Requests\UpdateTrailStatusRequest;
use App\Http\Resources\TrailListResource;
use App\Http\Resources\TrailResource;
use App\Models\Trail;
use App\Services\ActivityLogger;
use App\Services\TrailService;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class TrailController extends Controller
{
    use ApiResponses;

    public function __construct(private TrailService $trailService) {}

    public function index(Request $request): JsonResponse
    {
        $response = Gate::inspect('viewAny', Trail::class);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $query = Trail::query()->with('featuredImage')->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('location_name', 'like', "%{$search}%")
                    ->orWhere('county', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->input('difficulty'));
        }

        if ($request->filled('county')) {
            $query->where('county', $request->input('county'));
        }

        $trails = $query->paginate(15);

        return $this->ok('Trails retrieved', [
            'trails' => TrailListResource::collection($trails),
            'meta' => [
                'current_page' => $trails->currentPage(),
                'last_page' => $trails->lastPage(),
                'per_page' => $trails->perPage(),
                'total' => $trails->total(),
            ],
        ]);
    }

    public function store(StoreTrailRequest $request): JsonResponse
    {
        $response = Gate::inspect('create', Trail::class);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $trail = $this->trailService->createTrail(
            $request->validated(),
            $request->user()->id,
        );

        ActivityLogger::log(
            event: 'created',
            subject: $trail,
            causer: $request->user(),
            logName: 'trails',
        );

        $trail->load([
            'featuredImage',
            'amenities',
            'images.media',
            'gpxFiles.media',
            'creator',
        ]);

        return $this->success('Trail created successfully', [
            'trail' => new TrailResource($trail),
        ], 201);
    }

    public function show(Trail $trail): JsonResponse
    {
        $response = Gate::inspect('view', $trail);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $trail->load([
            'featuredImage',
            'amenities',
            'images.media',
            'gpxFiles.media',
            'creator',
            'updater',
        ]);

        return $this->ok('Trail retrieved', [
            'trail' => new TrailResource($trail),
        ]);
    }

    public function update(UpdateTrailRequest $request, Trail $trail): JsonResponse
    {
        $response = Gate::inspect('update', $trail);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $trail = $this->trailService->updateTrail(
            $trail,
            $request->validated(),
            $request->user()->id,
        );

        ActivityLogger::log(
            event: 'updated',
            subject: $trail,
            causer: $request->user(),
            logName: 'trails',
        );

        $trail->load([
            'featuredImage',
            'amenities',
            'images.media',
            'gpxFiles.media',
            'creator',
            'updater',
        ]);

        return $this->ok('Trail updated successfully', [
            'trail' => new TrailResource($trail),
        ]);
    }

    public function updateStatus(UpdateTrailStatusRequest $request, Trail $trail): JsonResponse
    {
        $response = Gate::inspect('updateStatus', $trail);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $data = $request->validated();
        $oldStatus = $trail->status;
        $newStatus = TrailStatus::from($data['status']);

        $trail->update([
            'status' => $newStatus,
            'published_at' => $newStatus === TrailStatus::Published && ! $trail->published_at
                ? now()
                : $trail->published_at,
            'updated_by' => $request->user()->id,
        ]);

        ActivityLogger::log(
            event: 'status_changed',
            subject: $trail,
            causer: $request->user(),
            properties: [
                'old_status' => $oldStatus->value,
                'new_status' => $newStatus->value,
            ],
            logName: 'trails',
        );

        return $this->ok('Trail status updated successfully', [
            'trail' => new TrailResource($trail->load('featuredImage')),
        ]);
    }

    public function destroy(Trail $trail): JsonResponse
    {
        $response = Gate::inspect('delete', $trail);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        ActivityLogger::log(
            event: 'deleted',
            subject: $trail,
            causer: auth()->user(),
            logName: 'trails',
        );

        $trail->delete();

        return $this->ok('Trail deleted successfully');
    }

    public function restore(int $id): JsonResponse
    {
        $trail = Trail::onlyTrashed()->findOrFail($id);

        $response = Gate::inspect('update', $trail);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $trail->restore();

        ActivityLogger::log(
            event: 'restored',
            subject: $trail,
            causer: auth()->user(),
            logName: 'trails',
        );

        return $this->ok('Trail restored successfully', [
            'trail' => new TrailResource($trail->load('featuredImage')),
        ]);
    }

    public function counties(): JsonResponse
    {
        $counties = Cache::rememberForever('trails.counties', fn () => config('counties'));

        return $this->ok('Counties retrieved', [
            'counties' => $counties,
        ]);
    }

    public function difficulties(): JsonResponse
    {
        $difficulties = Cache::rememberForever('trails.difficulties', fn () => collect(\App\Enums\TrailDifficulty::cases())->map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->name,
        ])->values()->all());

        return $this->ok('Difficulties retrieved', [
            'difficulties' => $difficulties,
        ]);
    }
}
