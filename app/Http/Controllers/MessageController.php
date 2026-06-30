<?php

namespace App\Http\Controllers;

use App\Http\Requests\Messages\StoreMessageRequest;
use App\Http\Requests\Messages\UpdateMessageRequest;
use App\Models\Link;
use App\Models\Message;
use App\Notifications\MessageReadNotification;
use App\Notifications\MessageReceivedNotification;
use App\Services\Stripe\CreateMessageCheckoutSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * Store a newly created message for a link.
     */
    public function store(StoreMessageRequest $request, Link $link): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $hasGift = $validated['has_gift'];

        if ($hasGift && empty($validated['gift_amount'])) {
            abort(422, '差し入れ金額を選択してください。');
        }

        $creator = $link->user()->with('stripeAccount')->firstOrFail();

        if ($hasGift && ! $creator->canReceivePayments()) {
            abort(422, 'このユーザーはまだ差し入れを受け取れません。');
        }

        $amount = $hasGift ? $validated['gift_amount'] : 0;

        $message = $link->messages()->create([
            'sender_user_id' => $user->id,
            'body' => $validated['body'],
            'amount' => $amount,
            'sender_mode' => $validated['sender_mode'],
            'sender_display_name' => $validated['sender_display_name'] ?? ($validated['sender_mode'] === 'named' ? $user->name : null),
            'status' => $hasGift ? 'pending_payment' : 'safe',
            'is_public' => false, // Always private by default for new system
            'is_read' => false,
            'metadata' => [
                'ip_hash' => hash('sha256', $request->ip()),
                'user_agent' => $request->userAgent(),
                'gift_label' => $validated['gift_label'] ?? null,
            ],
        ]);

        if (! $hasGift) {
            $link->user->notify(new MessageReceivedNotification($message));

            return back()->with('success', 'メッセージを送りました');
        }

        return app(CreateMessageCheckoutSession::class)->handle(
            message: $message,
            link: $link,
            creator: $creator,
            payer: $user
        );
    }

    /**
     * Update the specified message (for owner).
     */
    public function update(UpdateMessageRequest $request, Message $message): RedirectResponse
    {
        $link = $message->link;

        if ($request->user()->id !== $link->user_id) {
            abort(403);
        }

        $validated = $request->validated();

        if (isset($validated['is_public'])) {
            $message->is_public = $validated['is_public'];
            if ($message->is_public && ! $message->published_at) {
                $message->published_at = now();
            }
        }

        if (isset($validated['status'])) {
            $message->status = $validated['status'];
        }

        if (isset($validated['is_read'])) {
            $wasRead = $message->is_read;
            $message->is_read = $validated['is_read'];
            $message->read_at = $message->is_read ? ($message->read_at ?? now()) : null;

            if (! $wasRead && $message->is_read && $message->sender) {
                $message->sender->notify(new MessageReadNotification($message));
            }
        }

        $message->save();

        if (isset($validated['reply_body'])) {
            $message->reply()->updateOrCreate(
                ['message_id' => $message->id],
                ['body' => $validated['reply_body']]
            );
        }

        return back()->with('success', '更新しました');
    }

    /**
     * Delete a message (for owner).
     */
    public function destroy(Request $request, Message $message): RedirectResponse
    {
        if ($request->user()->id !== $message->link->user_id) {
            abort(403);
        }

        $message->delete();

        return back()->with('success', '削除しました');
    }
}
