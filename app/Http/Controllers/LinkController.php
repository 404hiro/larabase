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
     * Display the current user's link management page.
     */
    public function index(Request $request): Response
    {
        $links = $request->user()
            ->links()
            ->with('title')
            ->withCount('widgets')
            ->get();

        return Inertia::render('dashboard/Links/Index', [
            'links' => \App\Http\Resources\LinkResource::collection($links),
            'titleOptions' => $this->titleOptions(),
            'userName' => $request->user()->name,
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

        return redirect()->route('links.show', $link);
    }

    /**
     * Display a published link page.
     */
    public function show(Link $link): Response
    {
        return Inertia::render('links/Link', [
            'link' => $link->load(['widgets', 'title']),
        ]);
    }

    /**
     * Display a mock fan letter page for the link.
     */
    public function letter(Link $link): Response
    {
        return Inertia::render('links/Message', [
            'link' => $link,
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
            'bio' => filled($validated['bio'] ?? null) ? $validated['bio'] : null,
        ];

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

        $link->update($linkData);

        return back()->with('success', '保存しました');
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
