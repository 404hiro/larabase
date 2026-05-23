<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageReply extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'message_id',
        'body',
    ];

    /**
     * Get the message that owns the reply.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
