<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\UserStripeAccount;
use App\Services\StripeConnectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class StripeConnectController extends Controller
{
    public function onboarding(Request $request, StripeConnectService $stripe): Response
    {
        $user = $request->user();
        $stripeAccount = $user->stripeAccount;

        if (! $stripeAccount) {
            $account = $stripe->createAccount();
            $stripeAccount = $user->stripeAccount()->create([
                'stripe_account_id' => $account->id,
                'details_submitted' => (bool) ($account->details_submitted ?? false),
                'charges_enabled' => (bool) ($account->charges_enabled ?? false),
                'payouts_enabled' => (bool) ($account->payouts_enabled ?? false),
            ]);
        }

        $onboardingUrl = $stripe->createOnboardingLink(
            stripeAccount: $stripeAccount,
            refreshUrl: route('stripe-connect.refresh'),
            returnUrl: route('stripe-connect.return'),
        );

        return Inertia::location($onboardingUrl);
    }

    public function refresh(Request $request, StripeConnectService $stripe): Response
    {
        return $this->onboarding($request, $stripe);
    }

    public function return(Request $request, StripeConnectService $stripe): RedirectResponse
    {
        $stripeAccount = $request->user()->stripeAccount;

        if ($stripeAccount instanceof UserStripeAccount) {
            $stripe->syncAccount($stripeAccount);
        }

        return redirect()->to(route('profile.edit', absolute: false).'#revenue');
    }
}
