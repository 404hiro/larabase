<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessagePublication extends Model
{
    protected $fillable = [
        'message_id',
        'link_id',
        'reply_body',
        'image_url',
        'image_disk',
        'template',
        'visibility',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}
