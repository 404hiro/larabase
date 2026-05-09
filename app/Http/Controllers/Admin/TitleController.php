<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Title;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TitleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/titles/Index', [
            'titles' => Title::query()
                ->withCount('links')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (Title $title): array => [
                    'id' => $title->id,
                    'name' => $title->name,
                    'sort_order' => $title->sort_order,
                    'is_active' => $title->is_active,
                    'links_count' => $title->links_count,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Title::query()->create($this->validateTitle($request));

        return back()->with('success', '職業を追加しました');
    }

    public function update(Request $request, Title $title): RedirectResponse
    {
        $title->update($this->validateTitle($request, $title));

        return back()->with('success', '職業を更新しました');
    }

    public function destroy(Title $title): RedirectResponse
    {
        $title->delete();

        return back()->with('success', '職業を削除しました');
    }

    /**
     * @return array{name: string, sort_order: int, is_active: bool}
     */
    private function validateTitle(Request $request, ?Title $title = null): array
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('titles', 'name')->ignore($title?->id),
            ],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        return [
            'name' => $validated['name'],
            'sort_order' => (int) $validated['sort_order'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];
    }
}
