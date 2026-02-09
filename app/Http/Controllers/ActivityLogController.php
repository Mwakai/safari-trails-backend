<?php

namespace App\Http\Controllers;

use App\Filters\ActivityLogFilter;
use App\Http\Requests\ListActivityLogsRequest;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ActivityLogController extends Controller
{
    use ApiResponses;

    public function index(ListActivityLogsRequest $request, ActivityLogFilter $filters): JsonResponse
    {
        $response = Gate::inspect('viewAny', ActivityLog::class);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $query = ActivityLog::query()
            ->with('causer')
            ->filter($filters);

        $filters->applyActivityLogSorting($query);

        $logs = $query->paginate($filters->perPage());

        return $this->ok('Activity logs retrieved', [
            'activity_logs' => ActivityLogResource::collection($logs),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    public function show(ActivityLog $activityLog): JsonResponse
    {
        $response = Gate::inspect('view', $activityLog);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $activityLog->load('causer');

        return $this->ok('Activity log retrieved', [
            'activity_log' => new ActivityLogResource($activityLog),
        ]);
    }
}
