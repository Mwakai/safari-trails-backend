<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo_id',
        'cover_image_id',
        'website',
        'email',
        'phone',
        'whatsapp',
        'instagram',
        'facebook',
        'is_verified',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function logo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'logo_id');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function coverImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_image_id');
    }

    /**
     * @return HasMany<GroupHike, $this>
     */
    public function groupHikes(): HasMany
    {
        return $this->hasMany(GroupHike::class);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }
}
