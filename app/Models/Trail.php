<?php

namespace App\Models;

use App\Enums\DurationType;
use App\Enums\TrailDifficulty;
use App\Enums\TrailImageType;
use App\Enums\TrailStatus;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trail extends Model
{
    /** @use HasFactory<\Database\Factories\TrailFactory> */
    use Filterable, HasFactory;

    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'difficulty',
        'distance_km',
        'duration_type',
        'duration_min',
        'duration_max',
        'elevation_gain_m',
        'max_altitude_m',
        'is_year_round',
        'season_notes',
        'requires_guide',
        'requires_permit',
        'permit_info',
        'accommodation_types',
        'latitude',
        'longitude',
        'location_name',
        'region_id',
        'route_a_name',
        'route_a_description',
        'route_a_latitude',
        'route_a_longitude',
        'route_b_enabled',
        'route_b_name',
        'route_b_description',
        'route_b_latitude',
        'route_b_longitude',
        'featured_image_id',
        'video_url',
        'status',
        'published_at',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'difficulty' => TrailDifficulty::class,
            'status' => TrailStatus::class,
            'duration_type' => DurationType::class,
            'distance_km' => 'decimal:2',
            'duration_min' => 'decimal:1',
            'duration_max' => 'decimal:1',
            'elevation_gain_m' => 'integer',
            'max_altitude_m' => 'integer',
            'is_year_round' => 'boolean',
            'requires_guide' => 'boolean',
            'requires_permit' => 'boolean',
            'accommodation_types' => 'array',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'route_a_latitude' => 'decimal:8',
            'route_a_longitude' => 'decimal:8',
            'route_b_enabled' => 'boolean',
            'route_b_latitude' => 'decimal:8',
            'route_b_longitude' => 'decimal:8',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }

    /**
     * @return BelongsToMany<Amenity, $this>
     */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'trail_amenity');
    }

    /**
     * @return HasMany<TrailImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(TrailImage::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<TrailImage, $this>
     */
    public function galleryImages(): HasMany
    {
        return $this->hasMany(TrailImage::class)
            ->where('type', TrailImageType::Gallery)
            ->orderBy('sort_order');
    }

    /**
     * @return HasMany<TrailImage, $this>
     */
    public function routeAImages(): HasMany
    {
        return $this->hasMany(TrailImage::class)
            ->where('type', TrailImageType::RouteA)
            ->orderBy('sort_order');
    }

    /**
     * @return HasMany<TrailImage, $this>
     */
    public function routeBImages(): HasMany
    {
        return $this->hasMany(TrailImage::class)
            ->where('type', TrailImageType::RouteB)
            ->orderBy('sort_order');
    }

    /**
     * @return HasMany<TrailGpx, $this>
     */
    public function gpxFiles(): HasMany
    {
        return $this->hasMany(TrailGpx::class)->orderBy('sort_order');
    }

    /**
     * @return BelongsTo<Region, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * @return HasMany<TrailBestMonth, $this>
     */
    public function bestMonths(): HasMany
    {
        return $this->hasMany(TrailBestMonth::class)->orderBy('month');
    }

    /**
     * @return HasMany<TrailItineraryDay, $this>
     */
    public function itineraryDays(): HasMany
    {
        return $this->hasMany(TrailItineraryDay::class)->orderBy('day_number');
    }

    /**
     * Whether this is a multi-day trail (derived from duration_type).
     */
    protected function isMultiDay(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->duration_type === DurationType::Days,
        );
    }

    /**
     * Human-readable duration display (e.g., "2-3 hours", "5 days").
     */
    protected function durationDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->duration_min === null) {
                    return null;
                }

                $type = $this->duration_type?->value ?? 'hours';
                $min = rtrim(rtrim(number_format((float) $this->duration_min, 1), '0'), '.');
                $max = $this->duration_max ? rtrim(rtrim(number_format((float) $this->duration_max, 1), '0'), '.') : null;

                if ($max && (float) $this->duration_max !== (float) $this->duration_min) {
                    return "{$min}-{$max} {$type}";
                }

                return "{$min} {$type}";
            },
        );
    }

    /**
     * Formatted display of best hiking months (e.g., "Jan-Mar, Jul-Oct").
     */
    protected function bestMonthsDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->is_year_round) {
                    return 'Year-round';
                }

                $months = $this->getBestMonthsArray();

                if (empty($months)) {
                    return 'Year-round';
                }

                return $this->formatMonthRanges($months);
            },
        );
    }

    /**
     * Rating for the current month: 'best', 'okay', or 'avoid'.
     */
    protected function currentMonthRating(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->is_year_round) {
                    return 'best';
                }

                $currentMonth = (int) now()->format('n');

                if ($this->isGoodMonth($currentMonth)) {
                    return 'best';
                }

                // Check if adjacent months are good (makes current "okay")
                $prevMonth = $currentMonth === 1 ? 12 : $currentMonth - 1;
                $nextMonth = $currentMonth === 12 ? 1 : $currentMonth + 1;

                if ($this->isGoodMonth($prevMonth) || $this->isGoodMonth($nextMonth)) {
                    return 'okay';
                }

                return 'avoid';
            },
        );
    }

    /**
     * Contextual recommendation message based on current month.
     */
    protected function seasonRecommendation(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->is_year_round) {
                    return 'Great for hiking year-round';
                }

                $rating = $this->current_month_rating;
                $display = $this->best_months_display;

                return match ($rating) {
                    'best' => 'Now is a great time to hike this trail',
                    'okay' => "Conditions may vary. Best months: {$display}",
                    default => "Not recommended now. Best months: {$display}",
                };
            },
        );
    }

    /**
     * @return array<int>
     */
    public function getBestMonthsArray(): array
    {
        if ($this->relationLoaded('bestMonths')) {
            return $this->bestMonths->pluck('month')->sort()->values()->all();
        }

        return $this->bestMonths()->pluck('month')->sort()->values()->all();
    }

    /**
     * @param  array<int>  $months
     */
    public function setBestMonths(array $months): void
    {
        $this->bestMonths()->delete();

        $months = array_unique(array_filter($months, fn ($m) => $m >= 1 && $m <= 12));
        sort($months);

        foreach ($months as $month) {
            TrailBestMonth::create([
                'trail_id' => $this->id,
                'month' => $month,
                'created_at' => now(),
            ]);
        }

        $this->unsetRelation('bestMonths');
    }

    public function isGoodMonth(int $month): bool
    {
        if ($this->is_year_round) {
            return true;
        }

        return in_array($month, $this->getBestMonthsArray());
    }

    public function isGoodNow(): bool
    {
        return $this->isGoodMonth((int) now()->format('n'));
    }

    public function isPublished(): bool
    {
        return $this->status === TrailStatus::Published;
    }

    public function isDraft(): bool
    {
        return $this->status === TrailStatus::Draft;
    }

    public function isArchived(): bool
    {
        return $this->status === TrailStatus::Archived;
    }

    /**
     * Format month numbers into human-readable ranges.
     *
     * @param  array<int>  $months
     */
    private function formatMonthRanges(array $months): string
    {
        $names = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
        ];

        sort($months);
        $ranges = [];
        $start = $months[0];
        $prev = $months[0];

        for ($i = 1; $i < count($months); $i++) {
            if ($months[$i] === $prev + 1) {
                $prev = $months[$i];
            } else {
                $ranges[] = $start === $prev
                    ? $names[$start]
                    : $names[$start].'-'.$names[$prev];
                $start = $months[$i];
                $prev = $months[$i];
            }
        }

        $ranges[] = $start === $prev
            ? $names[$start]
            : $names[$start].'-'.$names[$prev];

        return implode(', ', $ranges);
    }
}
