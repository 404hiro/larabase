<?php

namespace App\Http\Controllers;

use App\Models\Link;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class WidgetController extends Controller
{
    public function fetchOgp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2000'],
        ]);

        $url = $validated['url'];
        $title = parse_url($url, PHP_URL_HOST) ?? $url;
        $thumbnailUrl = null;

        try {
            $response = Http::timeout(3)->get($url);
            if ($response->successful()) {
                $html = $response->body();

                if (preg_match('/<meta[^>]*property=[\'"]og:title[\'"][^>]*content=[\'"]([^\'"]+)[\'"]/i', $html, $matches) ||
                    preg_match('/<meta[^>]*content=[\'"]([^\'"]+)[\'"][^>]*property=[\'"]og:title[\'"]/i', $html, $matches)) {
                    $title = html_entity_decode($matches[1]);
                } elseif (preg_match('/<title>([^<]+)<\/title>/i', $html, $matches)) {
                    $title = html_entity_decode($matches[1]);
                }

                if (preg_match('/<meta[^>]*property=[\'"]og:image[\'"][^>]*content=[\'"]([^\'"]+)[\'"]/i', $html, $matches) ||
                    preg_match('/<meta[^>]*content=[\'"]([^\'"]+)[\'"][^>]*property=[\'"]og:image[\'"]/i', $html, $matches)) {
                    $thumbnailUrl = html_entity_decode($matches[1]);
                }
            }
        } catch (Throwable $e) {
            return response()->json([
                'error' => 'URLから情報を取得できませんでした',
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'title' => $title,
            'thumbnail_url' => $thumbnailUrl,
            'url' => $url,
        ]);
    }

    public function sync(Request $request, Link $link): RedirectResponse
    {
        if ($request->user()->id !== $link->user_id) {
            abort(403);
        }

        $validated = $request->validate([
            'widgets' => ['present', 'array', 'max:50'],
            'widgets.*.id' => ['nullable'],
            'widgets.*.type' => ['required', 'string'],
            'widgets.*.content' => ['nullable', 'string', 'max:2000'],
            'widgets.*.thumbnail_url' => ['nullable', 'string', 'max:2048'],
            'widgets.*.x' => ['required', 'integer'],
            'widgets.*.y' => ['required', 'integer'],
            'widgets.*.w' => ['required', 'integer'],
            'widgets.*.h' => ['required', 'integer'],
            'widgets.*.x_mobile' => ['required', 'integer'],
            'widgets.*.y_mobile' => ['required', 'integer'],
            'widgets.*.w_mobile' => ['required', 'integer'],
            'widgets.*.h_mobile' => ['required', 'integer'],
            'widgets.*.settings' => ['nullable', 'array'],
            'widgets.*.settings.title' => ['nullable', 'string', 'max:4500'],
            'widgets.*.settings.url' => ['nullable', 'string', 'max:2000'],
        ]);

        foreach ($validated['widgets'] as $widgetData) {
            $title = $widgetData['settings']['title'] ?? null;

            if (
                $widgetData['type'] === 'link' &&
                is_string($title) &&
                mb_strlen($title) > 100
            ) {
                throw ValidationException::withMessages([
                    'widgets' => 'リンクウィジェットのタイトルは100文字以内で入力してください。',
                ]);
            }

            if (
                $widgetData['type'] === 'text' &&
                is_string($title) &&
                mb_strlen($title) > 4500
            ) {
                throw ValidationException::withMessages([
                    'widgets' => 'テキストウィジェットは4500文字以内で入力してください。',
                ]);
            }
        }

        // Delete existing widgets
        $link->widgets()->delete();

        // Create new widgets
        foreach ($validated['widgets'] as $widgetData) {
            $link->widgets()->create([
                'type' => $widgetData['type'],
                'content' => $widgetData['content'] ?? null,
                'thumbnail_url' => $widgetData['thumbnail_url'] ?? null,
                'x' => $widgetData['x'],
                'y' => $widgetData['y'],
                'w' => $widgetData['w'],
                'h' => $widgetData['h'],
                'x_mobile' => $widgetData['x_mobile'],
                'y_mobile' => $widgetData['y_mobile'],
                'w_mobile' => $widgetData['w_mobile'],
                'h_mobile' => $widgetData['h_mobile'],
                'settings' => $widgetData['settings'] ?? null,
            ]);
        }

        return back()->with('success', '保存しました');
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png,image/gif,image/webp,image/apng',
                'max:5120',
            ],
        ]);

        $path = $request->file('image')->store('widgets', 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($path),
        ]);
    }
}
