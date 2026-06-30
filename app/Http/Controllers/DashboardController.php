<?php

namespace App\Http\Controllers;

use App\Models\LinkViewDailyStat;
use App\Models\Message;
use App\Models\Title;
use App\Models\WidgetClickDailyStat;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the user dashboard.
     */
    public function __invoke(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        if ($user->links()->count() === 0) {
            return redirect()->route('walkthrough.index');
        }

        $linkIds = $user->links()->pluck('id');
        $startDate = CarbonImmutable::today()->subDays(29);
        $endDate = CarbonImmutable::today();
        $dailyViews = LinkViewDailyStat::query()
            ->whereIn('link_id', $linkIds)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('date, SUM(view_count) as total_views')
            ->groupBy('date')
            ->pluck('total_views', 'date');
        $viewChartData = collect(CarbonPeriod::create($startDate, $endDate))
            ->map(fn (\Carbon\CarbonInterface $date): array => [
                'date' => $date->toDateString(),
                'label' => $date->format('n/j'),
                'views' => (int) ($dailyViews[$date->toDateString()] ?? 0),
            ])
            ->values();
        $totalViewsLast30Days = (int) $dailyViews->sum();
        $dailyClicks = WidgetClickDailyStat::query()
            ->whereIn('link_id', $linkIds)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('date, SUM(click_count) as total_clicks')
            ->groupBy('date')
            ->pluck('total_clicks', 'date');
        $clickChartData = collect(CarbonPeriod::create($startDate, $endDate))
            ->map(fn (\Carbon\CarbonInterface $date): array => [
                'date' => $date->toDateString(),
                'label' => $date->format('n/j'),
                'clicks' => (int) ($dailyClicks[$date->toDateString()] ?? 0),
            ])
            ->values();
        $totalClicksLast30Days = (int) $dailyClicks->sum();

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
            'totalViewsLast30Days' => $totalViewsLast30Days,
            'totalClicksLast30Days' => $totalClicksLast30Days,
            'viewChartData' => $viewChartData,
            'clickChartData' => $clickChartData,
        ]);
    }

    /**
     * Display the current user's message management page.
     */
    public function messages(Request $request): Response
    {
        $user = $request->user();
        $selectedMessageId = $request->query('message');
        $defaultMessageLimit = 20;
        $maxMessageLimit = 200;
        $inboxLimit = min(
            max($request->integer('inboxLimit', $defaultMessageLimit), $defaultMessageLimit),
            $maxMessageLimit,
        );
        $sentLimit = min(
            max($request->integer('sentLimit', $defaultMessageLimit), $defaultMessageLimit),
            $maxMessageLimit,
        );

        $inboxMessagesQuery = Message::query()
            ->with(['link:id,slug,display_name,user_id,avatar_url', 'reply', 'sender:id,name,avatar'])
            ->whereHas('link', function ($query) use ($user): void {
                $query->where('user_id', $user->id);
            });

        $sentMessagesQuery = Message::query()
            ->with(['link:id,slug,display_name,user_id,avatar_url', 'reply', 'sender:id,name,avatar'])
            ->where('sender_user_id', $user->id);

        $selectedInboxMessage = is_string($selectedMessageId)
            ? (clone $inboxMessagesQuery)->whereKey($selectedMessageId)->first()
            : null;
        $selectedSentMessage = is_string($selectedMessageId)
            ? (clone $sentMessagesQuery)->whereKey($selectedMessageId)->first()
            : null;

        if (
            is_string($selectedMessageId)
            && ! $selectedInboxMessage
            && ! $selectedSentMessage
        ) {
            abort(404);
        }

        $initialMailbox = $request->query('mailbox') === 'sent' ? 'sent' : 'inbox';

        if ($selectedInboxMessage) {
            $inboxLimit = max(
                $inboxLimit,
                (clone $inboxMessagesQuery)
                    ->where('created_at', '>', $selectedInboxMessage->created_at)
                    ->count() + 1,
            );
        }

        if ($selectedSentMessage) {
            $initialMailbox = 'sent';
            $sentLimit = max(
                $sentLimit,
                (clone $sentMessagesQuery)
                    ->where('created_at', '>', $selectedSentMessage->created_at)
                    ->count() + 1,
            );
        }

        $inboxMessages = (clone $inboxMessagesQuery)
            ->latest()
            ->limit($inboxLimit + 1)
            ->get();
        $sentMessages = (clone $sentMessagesQuery)
            ->latest()
            ->limit($sentLimit + 1)
            ->get();
        $hasMoreInboxMessages = $inboxMessages->count() > $inboxLimit;
        $hasMoreSentMessages = $sentMessages->count() > $sentLimit;

        return Inertia::render('dashboard/messages/Mailbox', [
            'initialMailbox' => $initialMailbox,
            'inboxLimit' => $inboxLimit,
            'sentLimit' => $sentLimit,
            'hasMoreInboxMessages' => $hasMoreInboxMessages,
            'hasMoreSentMessages' => $hasMoreSentMessages,
            'inboxMessages' => $inboxMessages->take($inboxLimit)->map(fn (Message $message): array => [
                'id' => $message->id,
                'body' => $message->body,
                'sender_mode' => $message->sender_mode,
                'sender_display_name' => $message->sender_display_name,
                'is_public' => $message->is_public,
                'is_read' => $message->is_read,
                'amount' => $message->amount,
                'created_at' => $message->created_at->toIso8601String(),
                'reply_body' => $message->reply?->body,
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
            'sentMessages' => $sentMessages->take($sentLimit)->map(fn (Message $message): array => [
                'id' => $message->id,
                'body' => $message->body,
                'sender_mode' => $message->sender_mode,
                'sender_display_name' => $message->sender_display_name,
                'is_public' => $message->is_public,
                'is_read' => $message->is_read,
                'amount' => $message->amount,
                'created_at' => $message->created_at->toIso8601String(),
                'reply_body' => $message->reply?->body,
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
