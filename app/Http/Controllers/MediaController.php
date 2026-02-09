<?php

namespace App\Http\Controllers;

use App\Filters\MediaFilter;
use App\Http\Requests\ListMediaRequest;
use App\Http\Requests\StoreMediaRequest;
use App\Http\Requests\UpdateMediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Services\ActivityLogger;
use App\Services\MediaService;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class MediaController extends Controller
{
    use ApiResponses;

    public function __construct(private MediaService $mediaService) {}

    public function index(ListMediaRequest $request, MediaFilter $filters): JsonResponse
    {
        $response = Gate::inspect('viewAny', Media::class);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $query = Media::query()
            ->with('uploadedBy')
            ->filter($filters);

        $filters->applyMediaSorting($query);

        $media = $query->paginate($filters->perPage(20));

        return $this->ok('Media retrieved', [
            'media' => MediaResource::collection($media),
            'meta' => [
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage(),
                'per_page' => $media->perPage(),
                'total' => $media->total(),
            ],
        ]);
    }

    public function store(StoreMediaRequest $request): JsonResponse
    {
        $response = Gate::inspect('create', Media::class);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $media = $this->mediaService->upload(
            $request->file('file'),
            $request->user()->id,
        );

        if ($request->filled('alt_text')) {
            $media->update(['alt_text' => $request->input('alt_text')]);
            $media->refresh();
        }

        $media->load('uploadedBy');

        ActivityLogger::log(
            event: 'uploaded',
            subject: $media,
            causer: $request->user(),
            logName: 'media',
        );

        return $this->success('Media uploaded successfully', [
            'media' => new MediaResource($media),
        ], 201);
    }

    public function show(Media $media): JsonResponse
    {
        $response = Gate::inspect('view', $media);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $media->load('uploadedBy');

        return $this->ok('Media retrieved', [
            'media' => new MediaResource($media),
        ]);
    }

    public function update(UpdateMediaRequest $request, Media $media): JsonResponse
    {
        $response = Gate::inspect('update', $media);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $media->update($request->validated());

        ActivityLogger::log(
            event: 'updated',
            subject: $media,
            causer: $request->user(),
            logName: 'media',
        );

        return $this->ok('Media updated successfully', [
            'media' => new MediaResource($media),
        ]);
    }

    public function destroy(Media $media): JsonResponse
    {
        $response = Gate::inspect('delete', $media);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        ActivityLogger::log(
            event: 'deleted',
            subject: $media,
            causer: auth()->user(),
            logName: 'media',
        );

        $this->mediaService->deleteMedia($media);

        return $this->ok('Media deleted successfully');
    }
}
