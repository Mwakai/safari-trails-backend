<?php

namespace App\Models;

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
        'duration_hours',
        'elevation_gain_m',
        'max_altitude_m',
        'latitude',
        'longitude',
        'location_name',
        'county',
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
            'distance_km' => 'decimal:2',
            'duration_hours' => 'decimal:1',
            'elevation_gain_m' => 'integer',
            'max_altitude_m' => 'integer',
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
     * @return Attribute<string|null, never>
     */
    protected function countyName(): Attribute
    {
        return Attribute::get(fn (): ?string => config("counties.all.{$this->county}"));
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
}
