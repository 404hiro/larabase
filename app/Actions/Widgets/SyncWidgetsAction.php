<?php

namespace App\Actions\Widgets;

use App\Models\Link;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SyncWidgetsAction
{
    /**
     * Sync widgets for the given link.
     *
     * @param  array<int, array<string, mixed>>  $widgetsData
     *
     * @throws ValidationException
     */
    public function execute(Link $link, array $widgetsData): void
    {
        $this->validateCustomRules($widgetsData);

        $this->deleteRemovedFiles($link, $widgetsData);

        // Delete existing widgets and create new ones
        $link->widgets()->delete();

        foreach ($widgetsData as $widget) {
            $link->widgets()->create([
                'type' => $widget['type'],
                'content' => $widget['content'] ?? null,
                'thumbnail_url' => $widget['thumbnail_url'] ?? null,
                'x' => $widget['x'],
                'y' => $widget['y'],
                'w' => $widget['w'],
                'h' => $widget['h'],
                'x_mobile' => $widget['x_mobile'],
                'y_mobile' => $widget['y_mobile'],
                'w_mobile' => $widget['w_mobile'],
                'h_mobile' => $widget['h_mobile'],
                'settings' => $widget['settings'] ?? null,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $widgetsData
     *
     * @throws ValidationException
     */
    private function validateCustomRules(array $widgetsData): void
    {
        foreach ($widgetsData as $widget) {
            $title = $widget['settings']['title'] ?? null;

            if (
                $widget['type'] === 'link' &&
                is_string($title) &&
                mb_strlen($title) > 100
            ) {
                throw ValidationException::withMessages([
                    'widgets' => 'リンクウィジェットのタイトルは100文字以内で入力してください。',
                ]);
            }

            if (
                $widget['type'] === 'text' &&
                is_string($title) &&
                mb_strlen($title) > 4500
            ) {
                throw ValidationException::withMessages([
                    'widgets' => 'テキストウィジェットは4500文字以内で入力してください。',
                ]);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $widgetsData
     */
    private function deleteRemovedFiles(Link $link, array $widgetsData): void
    {
        $oldFiles = $link->widgets()
            ->get()
            ->flatMap(function ($widget) {
                $urls = [];
                if ($widget->type === 'image' && ! empty($widget->content)) {
                    $urls[] = $widget->content;
                }
                if (! empty($widget->thumbnail_url)) {
                    $urls[] = $widget->thumbnail_url;
                }

                return $urls;
            })
            ->filter()
            ->values()
            ->toArray();

        $newFiles = collect($widgetsData)
            ->flatMap(function ($widget) {
                $urls = [];
                if (($widget['type'] ?? '') === 'image' && ! empty($widget['content'])) {
                    $widgetContent = $widget['content'];
                    if (is_string($widgetContent)) {
                        $urls[] = $widgetContent;
                    }
                }
                if (! empty($widget['thumbnail_url'])) {
                    $urls[] = $widget['thumbnail_url'];
                }

                return $urls;
            })
            ->filter()
            ->values()
            ->toArray();

        $filesToDelete = array_diff($oldFiles, $newFiles);

        foreach ($filesToDelete as $fileUrl) {
            $path = parse_url($fileUrl, PHP_URL_PATH);
            $storagePrefix = '/storage/';

            if (is_string($path) && str_starts_with($path, $storagePrefix)) {
                Storage::disk('public')->delete(substr($path, strlen($storagePrefix)));
            }
        }
    }
}
