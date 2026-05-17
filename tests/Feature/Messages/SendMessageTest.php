<?php

use App\Models\Link;
use App\Models\User;

it('can send a message to a link', function () {
    $sender = User::factory()->create();
    $owner = User::factory()->create();
    $link = Link::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($sender)
        ->post(route('messages.store', $link->slug), [
            'body' => 'Hello there!',
            'sender_mode' => 'named',
            'amount' => 500,
            'is_public' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('messages', [
        'link_id' => $link->id,
        'sender_user_id' => $sender->id,
        'body' => 'Hello there!',
        'amount' => 500,
        'sender_mode' => 'named',
        'sender_display_name' => $sender->name,
        'is_public' => true,
        'is_read' => false,
    ]);
});

it('can send an anonymous message', function () {
    $sender = User::factory()->create();
    $owner = User::factory()->create();
    $link = Link::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($sender)
        ->post(route('messages.store', $link->slug), [
            'body' => 'Anonymous secret!',
            'sender_mode' => 'anonymous',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('messages', [
        'link_id' => $link->id,
        'sender_user_id' => $sender->id,
        'body' => 'Anonymous secret!',
        'sender_mode' => 'anonymous',
        'sender_display_name' => null,
    ]);
});

it('requires login to send a message', function () {
    $link = Link::factory()->create();

    $this->post(route('messages.store', $link->slug), [
        'body' => 'Guest message',
        'sender_mode' => 'anonymous',
    ])
        ->assertRedirect(route('login'));
});
