<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Widget extends Model
{
    /** @use HasFactory<\Database\Factories\WidgetFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'link_id',
        'type',
        'content',
        'thumbnail_url',
        'x',
        'y',
        'w',
        'h',
        'x_mobile',
        'y_mobile',
        'w_mobile',
        'h_mobile',
        'settings',
    ];

    /**
     * Get the link page that owns the widget.
     */
    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }
}
