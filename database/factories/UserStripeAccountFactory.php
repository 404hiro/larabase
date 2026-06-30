<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserStripeAccount>
 */
class UserStripeAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'stripe_account_id' => 'acct_'.fake()->unique()->bothify('????????????????'),
            'details_submitted' => false,
            'charges_enabled' => false,
            'payouts_enabled' => false,
            'onboarding_completed_at' => null,
        ];
    }
}
