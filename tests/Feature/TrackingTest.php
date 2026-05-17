<?php

use App\Models\Link;
use App\Models\LinkViewDailyStat;
use App\Models\User;
use App\Models\Widget;
use App\Models\WidgetClickDailyStat;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->link = Link::factory()->create([
        'user_id' => $this->user->id,
        'slug' => 'testlink',
        'is_published' => true,
    ]);
    $this->widget = Widget::factory()->create([
        'link_id' => $this->link->id,
        'type' => 'link',
        'content' => 'https://google.com',
    ]);
});

it('increments view count for guest access', function () {
    expect(LinkViewDailyStat::count())->toBe(0);

    $this->get('/@testlink')->assertSuccessful();

    expect(LinkViewDailyStat::count())->toBe(1);
    expect(LinkViewDailyStat::first()->view_count)->toBe(1);

    // Second access on same day
    $this->get('/@testlink')->assertSuccessful();
    expect(LinkViewDailyStat::first()->view_count)->toBe(2);
});

it('does not increment view count for owner access', function () {
    $this->actingAs($this->user)
        ->get('/@testlink')
        ->assertSuccessful();

    expect(LinkViewDailyStat::count())->toBe(0);
});

it('does not increment view count for bot access', function () {
    $this->get('/@testlink', [
        'User-Agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)',
    ])->assertSuccessful();

    expect(LinkViewDailyStat::count())->toBe(0);
});

it('increments widget click count and redirects', function () {
    expect(WidgetClickDailyStat::count())->toBe(0);

    $this->get("/@testlink/widgets/{$this->widget->id}/click")
        ->assertRedirect('https://google.com');

    expect(WidgetClickDailyStat::count())->toBe(1);
    expect(WidgetClickDailyStat::first()->click_count)->toBe(1);

    // Second click
    $this->get("/@testlink/widgets/{$this->widget->id}/click")
        ->assertRedirect('https://google.com');
    expect(WidgetClickDailyStat::first()->click_count)->toBe(2);
});

it('does not increment widget click count for owner access', function () {
    $this->actingAs($this->user)
        ->get("/@testlink/widgets/{$this->widget->id}/click")
        ->assertRedirect('https://google.com');

    expect(WidgetClickDailyStat::count())->toBe(0);
});

it('shows aggregate stats in dashboard', function () {
    // Create some historical stats
    LinkViewDailyStat::create([
        'link_id' => $this->link->id,
        'date' => now()->toDateString(),
        'view_count' => 10,
    ]);

    WidgetClickDailyStat::create([
        'link_id' => $this->link->id,
        'widget_id' => $this->widget->id,
        'date' => now()->toDateString(),
        'click_count' => 5,
    ]);

    // Create a stat out of 30 days range
    LinkViewDailyStat::create([
        'link_id' => $this->link->id,
        'date' => now()->subDays(31)->toDateString(),
        'view_count' => 100,
    ]);

    $this->actingAs($this->user)
        ->get('/dashboard/links')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('totalAccessesLast30Days', 10)
            ->where('totalClicksLast30Days', 5)
        );
});
