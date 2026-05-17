<?php

namespace App\Http\Controllers;

use App\Http\Requests\Messages\StoreMessageRequest;
use App\Http\Requests\Messages\UpdateMessageRequest;
use App\Models\Link;
use App\Models\Message;
use App\Notifications\MessageReadNotification;
use App\Notifications\MessageReceivedNotification;
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

        $message = $link->messages()->create([
            'sender_user_id' => $user->id,
            'body' => $validated['body'],
            'amount' => $validated['amount'] ?? 0,
            'sender_mode' => $validated['sender_mode'],
            'sender_display_name' => $validated['sender_mode'] === 'named' ? $user->name : null,
            'status' => 'safe', // Defaulting to safe for MVP
            'is_public' => $request->boolean('is_public'),
            'published_at' => $request->boolean('is_public') ? now() : null,
            'metadata' => [
                'ip_hash' => hash('sha256', $request->ip()),
                'user_agent' => $request->userAgent(),
            ],
        ]);

        $link->user->notify(new MessageReceivedNotification($message));

        return back()->with('success', 'メッセージを送りました');
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
