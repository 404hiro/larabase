<?php

namespace App\Services\Stripe;

use App\Models\Link;
use App\Models\Message;
use App\Models\MessagePayment;
use App\Models\User;
use Inertia\Inertia;
use Stripe\StripeClient;

class CreateMessageCheckoutSession
{
    public function calculateFees(int $amount): array
    {
        $platformFee = (int) floor($amount * 0.10);
        $creatorPayout = max(0, $amount - $platformFee);

        return [
            'amount' => $amount,
            'platform_fee' => $platformFee,
            'creator_payout' => $creatorPayout,
        ];
    }

    public function handle(Message $message, Link $link, User $creator, User $payer)
    {
        $amount = $message->amount;
        $fees = $this->calculateFees($amount);
        $platformFee = $fees['platform_fee'];

        $payment = MessagePayment::create([
            'message_id' => $message->id,
            'payer_user_id' => $payer->id,
            'creator_user_id' => $creator->id,
            'amount' => $amount,
            'platform_fee' => $platformFee,
            'currency' => 'jpy',
            'status' => 'pending',
            'metadata' => [
                'creator_payout' => $fees['creator_payout'],
            ],
        ]);

        $stripe = new StripeClient(config('services.stripe.secret'));

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => 'jpy',
                    'product_data' => [
                        'name' => $message->metadata['gift_label'] ?? '差し入れ',
                    ],
                    'unit_amount' => $amount,
                ],
                'quantity' => 1,
            ]],
            'payment_intent_data' => [
                'application_fee_amount' => $platformFee,
                'transfer_data' => [
                    'destination' => $creator->stripeAccount->stripe_account_id,
                ],
            ],
            'success_url' => route('links.message', $link->slug).'?payment=success',
            'cancel_url' => route('stripe.checkout.cancel').'?session_id={CHECKOUT_SESSION_ID}',
            'metadata' => [
                'message_id' => (string) $message->id,
                'payment_id' => (string) $payment->id,
                'link_id' => (string) $link->id,
                'creator_user_id' => (string) $creator->id,
                'payer_user_id' => (string) $payer->id,
            ],
        ]);

        $payment->update([
            'stripe_checkout_session_id' => $session->id,
        ]);

        if (request()->header('X-Inertia')) {
            return Inertia::location($session->url);
        }

        return redirect()->away($session->url);
    }
}
