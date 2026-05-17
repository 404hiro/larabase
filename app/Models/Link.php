<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Link extends Model
{
    /** @use HasFactory<\Database\Factories\LinkFactory> */
    use HasFactory, HasUuids;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'slug',
        'display_name',
        'title_id',
        'bio',
        'avatar_url',
        'theme_config',
        'is_published',
        'has_web_display',
        'is_accepting_messages',
        'message_settings',
    ];

    /**
     * Get the user that owns the link.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the selected title for the link page.
     */
    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }

    /**
     * Get the widgets placed on the link page.
     */
    public function widgets(): HasMany
    {
        return $this->hasMany(Widget::class)->orderBy('y')->orderBy('x');
    }

    /**
     * Get the messages received by the link.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get the public messages received by the link.
     */
    public function publicMessages(): HasMany
    {
        return $this->hasMany(Message::class)
            ->where('is_public', true)
            ->where('is_read', true);
    }

    /**
     * Get the publications related to the link.
     */


    /**
     * Get the blocks related to the link.
     */
    public function messageBlocks(): HasMany
    {
        return $this->hasMany(MessageBlock::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'theme_config' => 'array',
            'is_published' => 'boolean',
            'has_web_display' => 'boolean',
            'is_accepting_messages' => 'boolean',
            'message_settings' => 'array',
        ];
    }
}
