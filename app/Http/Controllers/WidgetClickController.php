<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\Widget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WidgetClickController extends Controller
{
    /**
     * Track the widget click and redirect to the target URL.
     */
    public function click(Request $request, Link $link, Widget $widget): RedirectResponse
    {
        // 1. Verify the widget belongs to the link
        abort_unless($widget->link_id === $link->id, 404);

        // 2. Determine target URL
        $targetUrl = $widget->content;
        abort_unless(filled($targetUrl), 404);

        // 3. Track click if not owner and not a bot
        if (! $this->shouldExclude($request, $link)) {
            DB::statement('
                INSERT INTO widget_click_daily_stats (
                    link_id,
                    widget_id,
                    date,
                    click_count,
                    created_at,
                    updated_at
                )
                VALUES (?, ?, CURRENT_DATE, 1, NOW(), NOW())
                ON CONFLICT (widget_id, date)
                DO UPDATE SET
                    click_count = widget_click_daily_stats.click_count + 1,
                    updated_at = NOW()
            ', [
                $link->id,
                $widget->id,
            ]);
        }

        return redirect()->away($targetUrl);
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
}
