<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WidgetClickDailyStat extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'link_id',
        'widget_id',
        'date',
        'click_count',
    ];

    /**
     * Get the link that owns the stat.
     */
    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    /**
     * Get the widget that owns the stat.
     */
    public function widget(): BelongsTo
    {
        return $this->belongsTo(Widget::class);
    }
}
