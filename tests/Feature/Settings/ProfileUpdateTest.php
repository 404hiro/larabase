<?php

use App\Models\User;
use App\Models\UserStripeAccount;
use App\Services\StripeConnectService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Stripe\Account;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile page includes the revenue settings section', function () {
    $user = User::factory()->create();
    UserStripeAccount::factory()->create([
        'user_id' => $user->id,
        'details_submitted' => true,
        'charges_enabled' => true,
        'payouts_enabled' => true,
        'onboarding_completed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Profile')
            ->where('stripeAccount.details_submitted', true)
            ->where('stripeAccount.charges_enabled', true)
            ->where('stripeAccount.payouts_enabled', true)
        );
});

test('settings page uses the settings url', function () {
    expect(route('profile.edit', absolute: false))->toBe('/settings');
});

test('settings page hides appearance settings and displays sections as cards', function () {
    $settingsProfile = file_get_contents(resource_path('js/pages/settings/Profile.vue'));
    $settingsRoutes = file_get_contents(base_path('routes/settings.php'));

    expect($settingsProfile)
        ->toContain('Card')
        ->toContain('プロフィール情報')
        ->toContain('収益設定')
        ->toContain('StripeConnection設定')
        ->toContain('stripeConnectOnboarding')
        ->toContain('DeleteUser')
        ->not->toContain('AppearanceTabs')
        ->not->toContain('外観設定');

    expect($settingsRoutes)
        ->toContain("Route::redirect('settings/appearance', '/settings')")
        ->not->toContain("name('appearance.edit')");
});

test('stripe onboarding creates a connected account and redirects to stripe', function () {
    $user = User::factory()->create();
    $stripe = Mockery::mock(StripeConnectService::class);

    $stripe->shouldReceive('createAccount')
        ->once()
        ->andReturn(Account::constructFrom([
            'id' => 'acct_test123',
            'details_submitted' => false,
            'charges_enabled' => false,
            'payouts_enabled' => false,
        ]));
    $stripe->shouldReceive('createOnboardingLink')
        ->once()
        ->andReturn('https://connect.stripe.test/onboarding');

    $this->app->instance(StripeConnectService::class, $stripe);

    $this->actingAs($user)
        ->post(route('stripe-connect.onboarding'))
        ->assertRedirect('https://connect.stripe.test/onboarding');

    $this->assertDatabaseHas('user_stripe_accounts', [
        'user_id' => $user->id,
        'stripe_account_id' => 'acct_test123',
        'charges_enabled' => false,
        'payouts_enabled' => false,
    ]);
});

test('stripe return syncs connect account status', function () {
    $stripeAccount = UserStripeAccount::factory()->create();
    $stripe = Mockery::mock(StripeConnectService::class);

    $stripe->shouldReceive('syncAccount')
        ->once()
        ->andReturnUsing(function (UserStripeAccount $account): UserStripeAccount {
            $account->update([
                'details_submitted' => true,
                'charges_enabled' => true,
                'payouts_enabled' => true,
                'onboarding_completed_at' => now(),
            ]);

            return $account;
        });

    $this->app->instance(StripeConnectService::class, $stripe);

    $this->actingAs($stripeAccount->user)
        ->get(route('stripe-connect.return'))
        ->assertRedirect('/settings#revenue');

    $stripeAccount->refresh();

    expect($stripeAccount->details_submitted)->toBeTrue();
    expect($stripeAccount->charges_enabled)->toBeTrue();
    expect($stripeAccount->payouts_enabled)->toBeTrue();
    expect($stripeAccount->onboarding_completed_at)->not->toBeNull();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('profile.update'), [
            'name' => 'Test User',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
});

test('google account identifier is unchanged when profile information is updated', function () {
    $user = User::factory()->create();
    $googleId = $user->google_id;

    $response = $this
        ->actingAs($user)
        ->post(route('profile.update'), [
            'name' => 'Test User',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->google_id)->toBe($googleId);
});

test('profile avatar can be removed', function () {
    Storage::fake('public');

    $path = UploadedFile::fake()->image('avatar.png')->store('avatars', 'public');
    $user = User::factory()->create([
        'avatar' => $path,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'remove_avatar' => true,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    Storage::disk('public')->assertMissing($path);
    expect($user->refresh()->avatar)->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

test('account deletion does not require a password for google-only users', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});
