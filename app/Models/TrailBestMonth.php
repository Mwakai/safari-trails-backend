<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrailBestMonth extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = null;

    /** @var list<string> */
    protected $fillable = [
        'trail_id',
        'month',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'created_at' => 'datetime',
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
