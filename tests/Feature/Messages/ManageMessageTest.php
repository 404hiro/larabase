<?php

use App\Models\Link;
use App\Models\Message;
use App\Models\User;

it('allows the owner to publish a message', function () {
    $owner = User::factory()->create();
    $link = Link::factory()->create(['user_id' => $owner->id]);
    $message = Message::factory()->create(['link_id' => $link->id, 'is_public' => false]);

    $this->actingAs($owner)
        ->patch(route('messages.update', $message->id), [
            'is_public' => true,
        ])
        ->assertRedirect();

    expect($message->fresh()->is_public)->toBeTrue();
    expect($message->fresh()->published_at)->not->toBeNull();
});

it('allows the owner to reply to a message', function () {
    $owner = User::factory()->create();
    $link = Link::factory()->create(['user_id' => $owner->id]);
    $message = Message::factory()->create(['link_id' => $link->id]);

    $this->actingAs($owner)
        ->patch(route('messages.update', $message->id), [
            'reply_body' => 'Thank you for your message!',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('message_replies', [
        'message_id' => $message->id,
        'body' => 'Thank you for your message!',
    ]);
});

it('allows the owner to mark a message as read', function () {
    $owner = User::factory()->create();
    $link = Link::factory()->create(['user_id' => $owner->id]);
    $message = Message::factory()->create([
        'link_id' => $link->id,
        'is_read' => false,
        'read_at' => null,
    ]);

    $this->actingAs($owner)
        ->patch(route('messages.update', $message->id), [
            'is_read' => true,
        ])
        ->assertRedirect();

    expect($message->fresh())
        ->is_read->toBeTrue()
        ->read_at->not->toBeNull();
});

it('prevents non-owners from managing messages', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $link = Link::factory()->create(['user_id' => $owner->id]);
    $message = Message::factory()->create(['link_id' => $link->id]);

    $this->actingAs($other)
        ->patch(route('messages.update', $message->id), [
            'is_public' => true,
        ])
        ->assertForbidden();
});

it('allows the owner to delete a message', function () {
    $owner = User::factory()->create();
    $link = Link::factory()->create(['user_id' => $owner->id]);
    $message = Message::factory()->create(['link_id' => $link->id]);

    $this->actingAs($owner)
        ->delete(route('messages.destroy', $message->id))
        ->assertRedirect();

    $this->assertSoftDeleted('messages', ['id' => $message->id]);
});
