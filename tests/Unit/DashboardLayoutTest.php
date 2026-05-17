<?php

test('dashboard sidebar links use consistent styling and truncation', function () {
    $dashboardLayout = file_get_contents(dirname(__DIR__, 2).'/resources/js/layouts/DashboardLayout.vue');

    expect($dashboardLayout)
        ->toContain('isLinksOpen')
        ->toContain('toggleLinksSection')
        ->toContain('isActiveLinksOverview')
        ->toContain('href="/dashboard/links"')
        ->toContain('aria-expanded')
        ->toContain('dashboard-links-accordion-sub')
        ->toContain('Overview')
        ->toContain('bg-white text-black')
        ->toContain('text-white/65 hover:bg-white/10 hover:text-white')
        ->toContain('truncate')
        ->toContain('> {{ link.display_name }}');
});

test('dashboard sidebar overview links to dashboard links index', function () {
    $appSidebarDashboard = file_get_contents(dirname(__DIR__, 2).'/resources/js/components/AppSidebarDashboard.vue');

    expect($appSidebarDashboard)
        ->toContain("href: '/dashboard/links'")
        ->not->toContain("href: '/links'");
});
