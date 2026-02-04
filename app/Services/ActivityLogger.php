<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /**
     * Log an activity event.
     *
     * @param  array<string, mixed>  $properties
     */
    public static function log(
        string $event,
        ?Model $subject = null,
        ?Model $causer = null,
        array $properties = [],
        string $logName = 'default',
    ): ActivityLog {
        return ActivityLog::create([
            'log_name' => $logName,
            'event' => $event,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'causer_type' => $causer?->getMorphClass(),
            'causer_id' => $causer?->getKey(),
            'properties' => $properties ?: null,
            'created_at' => now(),
        ]);
    }
}
