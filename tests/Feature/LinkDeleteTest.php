<?php

use App\Models\Link;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Support\Facades\Storage;

test('user can delete their own link', function () {
    $user = User::factory()->create();
    $link = Link::factory()->create([
        'user_id' => $user->id,
        'slug' => 'delete-me',
    ]);

    Widget::factory()->create(['link_id' => $link->id]);

    $response = $this->actingAs($user)
        ->delete(route('dashboard.links.destroy', $link->id));

    $response->assertRedirect(route('links.index'));
    $response->assertSessionHas('success', 'リンクを削除しました');

    $this->assertDatabaseMissing('links', ['id' => $link->id]);
    $this->assertDatabaseMissing('widgets', ['link_id' => $link->id]);
});

test('user cannot delete someone else\'s link', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $link = Link::factory()->create([
        'user_id' => $owner->id,
        'slug' => 'not-yours',
    ]);

    $response = $this->actingAs($otherUser)
        ->delete(route('dashboard.links.destroy', $link->id));

    $response->assertForbidden();
    $this->assertDatabaseHas('links', ['id' => $link->id]);
});

test('deleting a link removes its avatar from storage', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $avatarPath = 'link-avatars/test-avatar.png';
    Storage::disk('public')->put($avatarPath, 'dummy content');

    $link = Link::factory()->create([
        'user_id' => $user->id,
        'avatar_url' => Storage::disk('public')->url($avatarPath),
    ]);

    Storage::disk('public')->assertExists($avatarPath);

    $this->actingAs($user)
        ->delete(route('dashboard.links.destroy', $link->id))
        ->assertRedirect();

    Storage::disk('public')->assertMissing($avatarPath);
});
