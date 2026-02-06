<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAmenityRequest;
use App\Http\Requests\UpdateAmenityRequest;
use App\Http\Resources\AmenityResource;
use App\Models\Amenity;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AmenityController extends Controller
{
    use ApiResponses;

    public function index(): JsonResponse
    {
        $response = Gate::inspect('viewAny', Amenity::class);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $amenities = Amenity::orderBy('name')->get();

        return $this->ok('Amenities retrieved', [
            'amenities' => AmenityResource::collection($amenities),
        ]);
    }

    public function store(StoreAmenityRequest $request): JsonResponse
    {
        $response = Gate::inspect('create', Amenity::class);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $data = $request->validated();

        $amenity = Amenity::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'is_active' => $data['is_active'] ?? true,
            'created_by' => $request->user()->id,
        ]);

        return $this->success('Amenity created successfully', [
            'amenity' => new AmenityResource($amenity),
        ], 201);
    }

    public function show(Amenity $amenity): JsonResponse
    {
        $response = Gate::inspect('view', $amenity);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        return $this->ok('Amenity retrieved', [
            'amenity' => new AmenityResource($amenity),
        ]);
    }

    public function update(UpdateAmenityRequest $request, Amenity $amenity): JsonResponse
    {
        $response = Gate::inspect('update', $amenity);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $amenity->update($request->validated());

        return $this->ok('Amenity updated successfully', [
            'amenity' => new AmenityResource($amenity),
        ]);
    }

    public function destroy(Amenity $amenity): JsonResponse
    {
        $response = Gate::inspect('delete', $amenity);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $amenity->delete();

        return $this->ok('Amenity deleted successfully');
    }
}
