<?php

namespace App\Actions\Widgets;

use Illuminate\Support\Facades\Http;
use Throwable;

class FetchOgpAction
{
    /**
     * Fetch OGP information from a given URL.
     *
     * @return array{title: string, thumbnail_url: ?string, url: string}
     *
     * @throws Throwable
     */
    public function execute(string $url): array
    {
        $title = parse_url($url, PHP_URL_HOST) ?? $url;
        $thumbnailUrl = null;

        $response = Http::timeout(3)->get($url);

        if ($response->successful()) {
            $html = $response->body();

            if (
                preg_match('/<meta[^>]*property=[\'"]og:title[\'"][^>]*content=[\'"]([^\'"]+)[\'"]/i', $html, $matches) ||
                preg_match('/<meta[^>]*content=[\'"]([^\'"]+)[\'"][^>]*property=[\'"]og:title[\'"]/i', $html, $matches)
            ) {
                $title = html_entity_decode($matches[1]);
            } elseif (preg_match('/<title>([^<]+)<\/title>/i', $html, $matches)) {
                $title = html_entity_decode($matches[1]);
            }

            if (
                preg_match('/<meta[^>]*property=[\'"]og:image[\'"][^>]*content=[\'"]([^\'"]+)[\'"]/i', $html, $matches) ||
                preg_match('/<meta[^>]*content=[\'"]([^\'"]+)[\'"][^>]*property=[\'"]og:image[\'"]/i', $html, $matches)
            ) {
                $thumbnailUrl = html_entity_decode($matches[1]);
            }
        }

        return [
            'title' => $title,
            'thumbnail_url' => $thumbnailUrl,
            'url' => $url,
        ];
    }
}
