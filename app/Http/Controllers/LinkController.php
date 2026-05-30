<?php

namespace App\Http\Controllers;

use App\Http\Requests\Links\StoreLinkRequest;
use App\Http\Requests\Links\UpdateLinkRequest;
use App\Models\Link;
use App\Models\Title;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class LinkController extends Controller
{
    /**
     * Display the current user's links dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $links = $user
            ->links()
            ->with('title')
            ->withCount('widgets')
            ->orderByDesc('updated_at')
            ->get();

        $linkIds = $links->pluck('id');

        $totalAccessesLast30Days = \App\Models\LinkViewDailyStat::query()
            ->whereIn('link_id', $linkIds)
            ->where('date', '>=', now()->subDays(29)->toDateString())
            ->sum('view_count');

        $totalClicksLast30Days = \App\Models\WidgetClickDailyStat::query()
            ->whereIn('link_id', $linkIds)
            ->where('date', '>=', now()->subDays(29)->toDateString())
            ->sum('click_count');

        return Inertia::render('dashboard/Links/Index', [
            'links' => $links->map(fn (Link $link): array => [
                'id' => $link->id,
                'slug' => $link->slug,
                'display_name' => $link->display_name,
                'title' => $link->title?->only(['id', 'name']),
                'bio' => $link->bio,
                'avatar_url' => $link->avatar_url,
                'is_published' => $link->is_published,
                'widgets_count' => (int) $link->widgets_count,
                'updated_at' => $link->updated_at?->toIso8601String(),
            ]),
            'linksCount' => $links->count(),
            'totalAccessesLast30Days' => (int) $totalAccessesLast30Days,
            'totalClicksLast30Days' => (int) $totalClicksLast30Days,
        ]);
    }

    /**
     * Store a newly created link page.
     */
    public function store(StoreLinkRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['bio'] = filled($validated['bio'] ?? null) ? $validated['bio'] : null;
        $validated['title_id'] = $validated['title_id'] ?? null;

        $link = $request->user()->links()->create($validated);

        return redirect()->route('links.show', ['link' => $link->slug, 'edit' => 1]);
    }

    /**
     * Display a link management page for the current user.
     */
    public function manage(Request $request, Link $link): Response
    {
        abort_unless($request->user()->id === $link->user_id, 403);

        $link->load(['title']);

        return Inertia::render('dashboard/Links/Show', [
            'link' => [
                'id' => $link->id,
                'slug' => $link->slug,
                'display_name' => $link->display_name,
                'title' => $link->title?->only(['id', 'name']),
                'bio' => $link->bio,
                'avatar_url' => $link->avatar_url,
                'is_published' => $link->is_published,
                'has_web_display' => $link->has_web_display,
                'updated_at' => $link->updated_at?->toISOString(),
            ],
            'titleOptions' => $this->titleOptions(),
        ]);
    }

    /**
     * Display a published link page.
     */
    public function show(Request $request, Link $link): Response
    {
        if (! $this->shouldExclude($request, $link)) {
            \Illuminate\Support\Facades\DB::statement('
                INSERT INTO link_view_daily_stats (
                    link_id,
                    date,
                    view_count,
                    created_at,
                    updated_at
                )
                VALUES (?, CURRENT_DATE, 1, NOW(), NOW())
                ON CONFLICT (link_id, date)
                DO UPDATE SET
                    view_count = link_view_daily_stats.view_count + 1,
                    updated_at = NOW()
            ', [$link->id]);
        }

        return Inertia::render('links/Link', [
            'link' => $link->load(['widgets', 'title']),
            'is_editing' => $request->boolean('edit') && $request->user()?->id === $link->user_id,
        ]);
    }

    /**
     * Determine if the request should be excluded from tracking.
     */
    private function shouldExclude(Request $request, Link $link): bool
    {
        // Exclude owner
        if ($request->user()?->id === $link->user_id) {
            return true;
        }

        // Exclude bots
        $userAgent = $request->userAgent();
        if (empty($userAgent)) {
            return true;
        }

        $bots = [
            'bot', 'crawler', 'spider', 'slurp', 'search', 'fetch', 'mediapartners',
            'lighthouse', 'google', 'bing', 'yandex', 'baidu', 'duckduckbot',
        ];

        foreach ($bots as $bot) {
            if (str_contains(strtolower($userAgent), $bot)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Display the message page for the link.
     */
    public function message(Request $request, Link $link): Response
    {
        $isOwner = $request->user()?->id === $link->user_id;

        $query = $link->messages()->with('reply')->latest();

        if ($isOwner) {
            $link->messages()
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
        }

        $query
            ->where('is_public', true)
            ->where('is_read', true);

        $messages = $query->get();

        return Inertia::render('links/Message', [
            'link' => $link,
            'messages' => \App\Http\Resources\MessageResource::collection($messages),
        ]);
    }

    /**
     * Update the specified link.
     */
    public function update(UpdateLinkRequest $request, Link $link): RedirectResponse
    {
        $validated = $request->validated();

        $linkData = [
            'display_name' => $validated['display_name'],
            'title_id' => $validated['title_id'] ?? null,
            'bio' => filled($validated['bio'] ?? null) ? $validated['bio'] : null,
        ];

        if ($request->has('theme_config')) {
            $currentThemeConfig = $link->theme_config ?? [];
            $incomingThemeConfig = $validated['theme_config'] ?? [];

            $linkData['theme_config'] = array_merge($currentThemeConfig, [
                'theme' => $incomingThemeConfig['theme'] ?? $currentThemeConfig['theme'] ?? 'light',
                'widget_style' => $incomingThemeConfig['widget_style'] ?? $currentThemeConfig['widget_style'] ?? 'rounded',
            ]);
        }

        if ($request->has('is_published')) {
            $linkData['is_published'] = $request->boolean('is_published');
        }

        if ($request->boolean('has_web_display')) {
            $linkData['has_web_display'] = true;
        }

        if ($request->boolean('remove_avatar')) {
            $this->deleteStoredAvatar($link);
            $linkData['avatar_url'] = null;
        }

        if ($request->hasFile('avatar')) {
            $this->deleteStoredAvatar($link);

            $path = $request->file('avatar')->store('link-avatars', 'public');
            $linkData['avatar_url'] = Storage::disk('public')->url($path);
        }

        if ($request->has('message_one_liner')) {
            $settings = $link->message_settings ?? [];
            $settings['one_liner'] = $request->input('message_one_liner');
            $linkData['message_settings'] = $settings;
        }

        $link->update($linkData);

        return back()->with('success', '保存しました');
    }

    /**
     * Remove the specified link.
     */
    public function destroy(Request $request, Link $link): RedirectResponse
    {
        abort_unless($request->user()->id === $link->user_id, 403);

        $this->deleteStoredAvatar($link);
        $link->delete();

        return redirect()->route('links.index')->with('success', 'リンクを削除しました');
    }

    private function deleteStoredAvatar(Link $link): void
    {
        if (! $link->avatar_url) {
            return;
        }

        $path = parse_url($link->avatar_url, PHP_URL_PATH);
        $storagePrefix = '/storage/';

        if (! is_string($path) || ! str_starts_with($path, $storagePrefix)) {
            return;
        }

        Storage::disk('public')->delete(substr($path, strlen($storagePrefix)));
    }

    /**
     * @return Collection<int, array{id: int, name: string}>
     */
    private function titleOptions(): Collection
    {
        return Title::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(fn (Title $title): array => [
                'id' => $title->id,
                'name' => $title->name,
            ]);
    }
}
