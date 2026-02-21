<?php

namespace App\Models;

use App\Enums\GroupHikeStatus;
use App\Enums\TrailDifficulty;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupHike extends Model
{
    /** @use HasFactory<\Database\Factories\GroupHikeFactory> */
    use Filterable, HasFactory, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'short_description',
        'organizer_id',
        'company_id',
        'trail_id',
        'custom_location_name',
        'latitude',
        'longitude',
        'region_id',
        'meeting_point',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
        'max_participants',
        'registration_url',
        'registration_deadline',
        'registration_notes',
        'price',
        'price_currency',
        'price_notes',
        'contact_name',
        'contact_email',
        'contact_phone',
        'contact_whatsapp',
        'difficulty',
        'featured_image_id',
        'is_featured',
        'is_recurring',
        'recurring_notes',
        'status',
        'published_at',
        'cancelled_at',
        'cancellation_reason',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => GroupHikeStatus::class,
            'difficulty' => TrailDifficulty::class,
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'price' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_recurring' => 'boolean',
            'published_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'start_date' => 'date',
            'end_date' => 'date',
            'registration_deadline' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Trail, $this>
     */
    public function trail(): BelongsTo
    {
        return $this->belongsTo(Trail::class);
    }

    /**
     * @return BelongsTo<Region, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }

    /**
     * @return HasMany<GroupHikeImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(GroupHikeImage::class)->orderBy('sort_order');
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

    public function isPublished(): bool
    {
        return $this->status === GroupHikeStatus::Published;
    }

    public function isDraft(): bool
    {
        return $this->status === GroupHikeStatus::Draft;
    }

    public function isCancelled(): bool
    {
        return $this->status === GroupHikeStatus::Cancelled;
    }

    public function isCompleted(): bool
    {
        return $this->status === GroupHikeStatus::Completed;
    }

    public function isPast(): bool
    {
        $today = today();

        if ($this->end_date !== null) {
            return $this->end_date->lt($today);
        }

        return $this->start_date->lt($today);
    }

    /**
     * Effective difficulty: trail's difficulty if trail_id is set, otherwise own difficulty.
     */
    protected function effectiveDifficulty(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->trail_id && $this->relationLoaded('trail') && $this->trail) {
                    return $this->trail->difficulty;
                }

                return $this->difficulty;
            },
        );
    }

    /**
     * Whether this is a multi-day hike.
     */
    protected function isMultiDay(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->end_date !== null && ! $this->end_date->equalTo($this->start_date),
        );
    }

    /**
     * Whether this hike is free.
     */
    protected function isFree(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->price === null || (float) $this->price === 0.0,
        );
    }
}
