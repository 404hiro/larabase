<?php

namespace App\Services;

use App\Models\UserStripeAccount;
use Illuminate\Validation\ValidationException;
use Stripe\Account;
use Stripe\StripeClient;

class StripeConnectService
{
    public function __construct(private ?StripeClient $stripe = null) {}

    public function createAccount(): Account
    {
        return @$this->client()->accounts->create([
            'type' => 'express',
            'country' => config('services.stripe.connect_country', 'JP'),
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
        ]);
    }

    public function createOnboardingLink(
        UserStripeAccount $stripeAccount,
        string $refreshUrl,
        string $returnUrl,
    ): string {
        $accountLink = @$this->client()->accountLinks->create([
            'account' => $stripeAccount->stripe_account_id,
            'refresh_url' => $refreshUrl,
            'return_url' => $returnUrl,
            'type' => 'account_onboarding',
        ]);

        return $accountLink->url;
    }

    public function syncAccount(UserStripeAccount $stripeAccount): UserStripeAccount
    {
        $account = @$this->retrieveAccount($stripeAccount->stripe_account_id);
        $detailsSubmitted = (bool) ($account->details_submitted ?? false);
        $chargesEnabled = (bool) ($account->charges_enabled ?? false);
        $payoutsEnabled = (bool) ($account->payouts_enabled ?? false);

        $stripeAccount->fill([
            'details_submitted' => $detailsSubmitted,
            'charges_enabled' => $chargesEnabled,
            'payouts_enabled' => $payoutsEnabled,
            'onboarding_completed_at' => $detailsSubmitted && $chargesEnabled && $payoutsEnabled
                ? ($stripeAccount->onboarding_completed_at ?? now())
                : null,
        ])->save();

        return $stripeAccount;
    }

    protected function retrieveAccount(string $stripeAccountId): Account
    {
        return $this->client()->accounts->retrieve($stripeAccountId);
    }

    protected function client(): StripeClient
    {
        if ($this->stripe) {
            return $this->stripe;
        }

        $secret = config('services.stripe.secret');

        if (! is_string($secret) || $secret === '') {
            throw ValidationException::withMessages([
                'stripe' => 'Stripeのシークレットキーが設定されていません。',
            ]);
        }

        return $this->stripe = new StripeClient($secret);
    }
}
