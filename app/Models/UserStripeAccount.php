<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStripeAccount extends Model
{
    /** @use HasFactory<\Database\Factories\UserStripeAccountFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'stripe_account_id',
        'details_submitted',
        'charges_enabled',
        'payouts_enabled',
        'onboarding_completed_at',
    ];

    /**
     * Get the user that owns the Stripe account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'details_submitted' => 'boolean',
            'charges_enabled' => 'boolean',
            'payouts_enabled' => 'boolean',
            'onboarding_completed_at' => 'datetime',
        ];
    }
}
