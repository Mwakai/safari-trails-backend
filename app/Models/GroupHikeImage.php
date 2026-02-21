<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupHikeImage extends Model
{
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'group_hike_id',
        'media_id',
        'caption',
        'sort_order',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<GroupHike, $this>
     */
    public function groupHike(): BelongsTo
    {
        return $this->belongsTo(GroupHike::class);
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
