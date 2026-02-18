<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrailItineraryDay extends Model
{
    /** @use HasFactory<\Database\Factories\TrailItineraryDayFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'trail_id',
        'day_number',
        'title',
        'description',
        'distance_km',
        'elevation_gain_m',
        'start_point',
        'end_point',
        'accommodation',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_number' => 'integer',
            'distance_km' => 'decimal:2',
            'elevation_gain_m' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Trail, $this>
     */
    public function trail(): BelongsTo
    {
        return $this->belongsTo(Trail::class);
    }
}
