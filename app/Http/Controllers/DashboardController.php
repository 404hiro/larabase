<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Title;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the user dashboard.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $messages = Message::query()
            ->with(['link:id,slug,display_name', 'publication', 'sender:id,name,avatar'])
            ->whereHas('link', function ($query) use ($user): void {
                $query->where('user_id', $user->id);
            })
            ->latest()
            ->limit(20)
            ->get();

        return Inertia::render('dashboard/Index', [
            'linksCount' => $user->links()->count(),
            'titleOptions' => Title::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'name']),
            'userName' => $user->name,
            'messagesCount' => Message::query()
                ->whereHas('link', function ($query) use ($user): void {
                    $query->where('user_id', $user->id);
                })
                ->count(),
            'unreadMessagesCount' => Message::query()
                ->where('is_read', false)
                ->whereHas('link', function ($query) use ($user): void {
                    $query->where('user_id', $user->id);
                })
                ->count(),
            'messages' => $messages->map(fn (Message $message): array => [
                'id' => $message->id,
                'body' => $message->body,
                'sender_mode' => $message->sender_mode,
                'sender_display_name' => $message->sender_display_name,
                'is_public' => $message->is_public,
                'is_read' => $message->is_read,
                'created_at' => $message->created_at->toIso8601String(),
                'reply_body' => $message->publication?->reply_body,
                'sender' => [
                    'id' => $message->sender->id,
                    'name' => $message->sender->name,
                    'avatar_url' => $message->sender->avatar_url,
                ],
                'link' => [
                    'slug' => $message->link->slug,
                    'display_name' => $message->link->display_name,
                ],
            ]),
        ]);
    }

    /**
     * Display the current user's message management page.
     */
    public function messages(Request $request, string $mailbox): Response
    {
        $user = $request->user();
        $selectedMessageId = $request->query('message');

        $mailboxMessagesQuery = Message::query()
            ->with(['link:id,slug,display_name,user_id,avatar_url', 'publication', 'sender:id,name,avatar'])
            ->when($mailbox === 'sent',
                fn ($query) => $query->where('sender_user_id', $user->id),
                fn ($query) => $query->whereHas('link', function ($query) use ($user): void {
                    $query->where('user_id', $user->id);
                }),
            );

        if ($selectedMessageId) {
            (clone $mailboxMessagesQuery)
                ->whereKey($selectedMessageId)
                ->firstOrFail();
        }

        $messages = $mailboxMessagesQuery
            ->latest()
            ->get();
        $component = $mailbox === 'sent'
            ? 'dashboard/messages/Sent'
            : 'dashboard/messages/Inbox';

        return Inertia::render($component, [
            'mailbox' => $mailbox,
            'messages' => $messages->map(fn (Message $message): array => [
                'id' => $message->id,
                'body' => $message->body,
                'sender_mode' => $message->sender_mode,
                'sender_display_name' => $message->sender_display_name,
                'is_public' => $message->is_public,
                'is_read' => $message->is_read,
                'created_at' => $message->created_at->toIso8601String(),
                'reply_body' => $message->publication?->reply_body,
                'sender' => [
                    'id' => $message->sender->id,
                    'name' => $message->sender->name,
                    'avatar_url' => $message->sender->avatar_url,
                ],
                'link' => [
                    'id' => $message->link->id,
                    'slug' => $message->link->slug,
                    'display_name' => $message->link->display_name,
                    'avatar_url' => $message->link->avatar_url,
                ],
            ]),
        ]);
    }
}
