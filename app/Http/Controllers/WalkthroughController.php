<?php

namespace App\Http\Controllers;

use App\Http\Requests\Links\StoreLinkRequest;
use App\Models\Title;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WalkthroughController extends Controller
{
    /**
     * Display the walkthrough page.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('walkthrough/Index', [
            'titleOptions' => Title::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created link from walkthrough.
     */
    public function store(StoreLinkRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['bio'] = filled($validated['bio'] ?? null) ? $validated['bio'] : null;
        $validated['title_id'] = $validated['title_id'] ?? null;
        $validated['is_published'] = true;

        $link = $request->user()->links()->create($validated);

        // Add a default message widget
        $link->widgets()->create([
            'type' => 'message',
            'x' => 0,
            'y' => 0,
            'w' => 1,
            'h' => 2,
            'x_mobile' => 0,
            'y_mobile' => 0,
            'w_mobile' => 2,
            'h_mobile' => 4,
            'settings' => [
                'title' => 'メッセージを送る',
                'description' => '私へのファンレターやメッセージをお待ちしています！',
            ],
        ]);

        // Redirect to the link page with edit mode enabled
        return redirect()->route('links.show', ['link' => $link->slug, 'edit' => 1]);
    }
}
