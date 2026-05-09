<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

it('can redirect to google', function () {
    $response = $this->get(route('auth.google'));

    $response->assertRedirect();
    $this->assertStringContainsString('accounts.google.com', $response->getTargetUrl());
});

it('can authenticate with google', function () {
    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->shouldReceive('getId')->andReturn('1234567890');
    $abstractUser->shouldReceive('getEmail')->andReturn('test@example.com');
    $abstractUser->shouldReceive('getName')->andReturn('Test User');
    $abstractUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');
    $abstractUser->id = '1234567890';
    $abstractUser->email = 'test@example.com';
    $abstractUser->name = 'Test User';
    $abstractUser->avatar = 'https://example.com/avatar.jpg';

    Socialite::shouldReceive('driver')->with('google')->andReturn(Mockery::mock(\Laravel\Socialite\Two\AbstractProvider::class, function ($mock) use ($abstractUser) {
        $mock->shouldReceive('user')->andReturn($abstractUser);
    }));

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();

    $user = User::where('email', 'test@example.com')->first();
    expect($user->google_id)->toBe('1234567890');
});

it('links existing user with google if email matches', function () {
    $user = User::factory()->create([
        'email' => 'existing@example.com',
        'google_id' => null,
    ]);

    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->shouldReceive('getId')->andReturn('google-id-123');
    $abstractUser->shouldReceive('getEmail')->andReturn('existing@example.com');
    $abstractUser->shouldReceive('getName')->andReturn('Existing User');
    $abstractUser->id = 'google-id-123';
    $abstractUser->email = 'existing@example.com';
    $abstractUser->name = 'Existing User';

    Socialite::shouldReceive('driver')->with('google')->andReturn(Mockery::mock(\Laravel\Socialite\Two\AbstractProvider::class, function ($mock) use ($abstractUser) {
        $mock->shouldReceive('user')->andReturn($abstractUser);
    }));

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);

    $user->refresh();
    expect($user->google_id)->toBe('google-id-123');
});
