<?php

use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

test('notifications are paginated ten at a time', function () {
    $user = User::factory()->create();

    foreach (range(1, 11) as $index) {
        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test',
            'data' => [
                'title' => "Notification {$index}",
                'body' => "Notification body {$index}",
                'url' => '/dashboard',
            ],
            'read_at' => now(),
            'created_at' => now()->subMinutes($index),
            'updated_at' => now()->subMinutes($index),
        ]);
    }

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/Notifications')
            ->has('notifications.data', 10)
            ->where('notifications.per_page', 10)
            ->where('notifications.current_page', 1)
            ->where('notifications.last_page', 2)
        );

    $this->actingAs($user)
        ->get(route('notifications.index', ['page' => 2]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/Notifications')
            ->has('notifications.data', 1)
            ->where('notifications.current_page', 2)
        );
});

test('notifications page renders pagination controls', function () {
    $notificationsPage = file_get_contents(resource_path('js/pages/dashboard/Notifications.vue'));

    expect($notificationsPage)
        ->toContain('notificationsData.links')
        ->toContain('preserve-scroll')
        ->toContain('v-html="link.label"');
});
