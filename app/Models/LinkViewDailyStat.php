<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkViewDailyStat extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'link_id',
        'date',
        'view_count',
    ];

    /**
     * Get the link that owns the stat.
     */
    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}
