<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessagePayment extends Model
{
    protected $fillable = [
        'message_id',
        'payer_user_id',
        'creator_user_id',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'amount',
        'platform_fee',
        'currency',
        'status',
        'paid_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }
}
