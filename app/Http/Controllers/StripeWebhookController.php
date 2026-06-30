<?php

namespace App\Http\Controllers;

use App\Models\MessagePayment;
use App\Notifications\MessageReceivedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('services.stripe.webhook_secret')
            );
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            DB::transaction(function () use ($session) {
                $payment = MessagePayment::where(
                    'stripe_checkout_session_id',
                    $session->id
                )->lockForUpdate()->first();

                if (! $payment || $payment->status === 'paid') {
                    return;
                }

                $payment->update([
                    'status' => 'paid',
                    'stripe_payment_intent_id' => $session->payment_intent,
                    'paid_at' => now(),
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'checkout_session' => $session->id,
                        'creator_payout' => $payment->metadata['creator_payout'] ?? null,
                    ]),
                ]);

                $message = $payment->message;
                $message->update([
                    'status' => 'safe',
                ]);

                $message->link->user->notify(new MessageReceivedNotification($message));
            });
        }

        return response()->json(['received' => true]);
    }

    public function cancel(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return redirect('/');
        }

        $payment = MessagePayment::where('stripe_checkout_session_id', $sessionId)
            ->with('message.link')
            ->first();

        if ($payment && $payment->status === 'pending') {
            DB::transaction(function () use ($payment) {
                $payment->update([
                    'status' => 'cancelled',
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'cancelled_at' => now()->toIso8601String(),
                    ]),
                ]);

                $payment->message->update([
                    'status' => 'payment_cancelled',
                ]);
            });
        }

        $redirectUrl = optional($payment->message->link)->slug
            ? route('links.message', $payment->message->link->slug).'?payment=cancel'
            : '/';

        return redirect($redirectUrl);
    }
}
