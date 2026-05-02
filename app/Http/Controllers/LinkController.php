<?php

namespace App\Http\Controllers;

use App\Models\Link;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
            ->withCount('widgets')
            ->get()
            ->map(fn (Link $link): array => [
                'id' => $link->id,
                'slug' => $link->slug,
                'display_name' => $link->display_name,
                'bio' => $link->bio,
                'avatar_url' => $link->avatar_url,
                'is_published' => $link->is_published,
                'widgets_count' => (int) $link->widgets_count,
                'updated_at' => $link->updated_at?->toISOString(),
            ]);

        return Inertia::render('Links/Index', [
            'links' => $links,
            'userName' => $request->user()->name,
        ]);
    }

    /**
     * Store a newly created link page.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => [
                'required',
                'string',
                'max:80',
                'regex:/^[A-Za-z0-9._~-]+$/',
                Rule::notIn([
                    'admin',
                    'confirm-password',
                    'dashboard',
                    'email-verification',
                    'forgot-password',
                    'login',
                    'logout',
                    'register',
                    'reset-password',
                    'settings',
                    'two-factor-challenge',
                    'verify-email',
                ]),
                Rule::unique('links', 'slug'),
            ],
            'display_name' => ['required', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:280'],
        ]);

        $link = $request->user()->links()->create($validated);

        return redirect()->route('links.show', $link);
    }

    /**
     * Display a published link page.
     */
    public function show(Link $link): Response
    {
        return Inertia::render('Link', [
            'link' => $link->load('widgets'),
        ]);
    }

    /**
     * Display a mock support page for the link.
     */
    public function support(Link $link): Response
    {
        return Inertia::render('LinkSupport', [
            'link' => $link,
        ]);
    }

    /**
     * Update the specified link.
     */
    public function update(Request $request, Link $link): RedirectResponse
    {
        if ($request->user()->id !== $link->user_id) {
            abort(403);
        }

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:280'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
            'is_published' => ['nullable', 'boolean'],
            'remove_avatar' => ['nullable', 'boolean'],
        ]);

        $linkData = [
            'display_name' => $validated['display_name'],
            'bio' => $validated['bio'] ?? null,
        ];

        if ($request->has('is_published')) {
            $linkData['is_published'] = $request->boolean('is_published');
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
}
