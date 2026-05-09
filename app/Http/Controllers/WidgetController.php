<?php

namespace App\Http\Controllers;

use App\Actions\Widgets\FetchOgpAction;
use App\Http\Requests\Widgets\FetchOgpRequest;
use App\Http\Requests\Widgets\SyncWidgetsRequest;
use App\Http\Requests\Widgets\UploadWidgetImageRequest;
use App\Models\Link;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Throwable;

class WidgetController extends Controller
{
    /**
     * Fetch OGP information from a given URL.
     */
    public function fetchOgp(FetchOgpRequest $request, FetchOgpAction $fetchOgpAction): JsonResponse
    {
        try {
            $result = $fetchOgpAction->execute($request->validated('url'));

            return response()->json($result);
        } catch (Throwable $e) {
            return response()->json([
                'error' => 'URLから情報を取得できませんでした',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync widgets for the given link.
     */
    public function sync(SyncWidgetsRequest $request, Link $link, \App\Actions\Widgets\SyncWidgetsAction $syncWidgetsAction): RedirectResponse
    {
        $syncWidgetsAction->execute($link, $request->validated('widgets'));

        return back()->with('success', '保存しました');
    }

    /**
     * Upload an image for a widget.
     */
    public function uploadImage(UploadWidgetImageRequest $request): JsonResponse
    {
        $path = $request->file('image')->store('widgets', 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($path),
        ]);
    }
}
