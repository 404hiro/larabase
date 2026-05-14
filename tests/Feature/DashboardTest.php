<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);
});

test('dashboard page links to the links management page', function () {
    $dashboardPage = file_get_contents(resource_path('js/pages/dashboard/Dashboard.vue'));

    expect($dashboardPage)
        ->toContain('href="/links"')
        ->toContain('リンクを管理');
});
