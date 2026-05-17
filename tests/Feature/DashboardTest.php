<?php

use App\Models\Link;
use App\Models\Message;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

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

test('dashboard page layout matches specifications', function () {
    $dashboardPage = file_get_contents(resource_path('js/pages/dashboard/Index.vue'));
    $dashboardLayout = file_get_contents(resource_path('js/layouts/DashboardLayout.vue'));
    $messagesPage = file_get_contents(resource_path('js/pages/dashboard/messages/Mailbox.vue'));
    $inboxPage = file_get_contents(resource_path('js/pages/dashboard/messages/Inbox.vue'));
    $sentPage = file_get_contents(resource_path('js/pages/dashboard/messages/Sent.vue'));

    expect($dashboardPage)
        ->toContain('DashboardLayout')
        ->not->toContain('AppLayout')
        ->toContain('最近のメッセージ')
        ->toContain('公開中')
        ->toContain('返信')
        ->toContain('削除')
        ->toContain("form.post('/dashboard/links'");

    expect($messagesPage)
        ->toContain('メッセージ管理')
        ->toContain("url.searchParams.set('message'")
        ->toContain("url.searchParams.delete('message'")
        ->toContain('const selectedMessageId = ref<string | null>(getInitialSelectedMessageId())')
        ->toContain('message.sender.avatar_url')
        ->toContain('message.link.avatar_url')
        ->toContain('isInbox.value ? getSenderName(message) : message.link.display_name')
        ->toContain("isInbox.value && message.sender_mode === 'anonymous'")
        ->toContain('v-if="shouldShowListAvatar(message)"')
        ->toContain('!isAnonymousInboxMessage')
        ->toContain('isInbox.value && !message.is_read')
        ->toContain('useForm({ is_read: true }).patch')
        ->toContain('!message.is_read')
        ->toContain('未読')
        ->toContain('既読')
        ->toContain('プライベート')
        ->not->toContain('非公開希望')
        ->not->toContain('公開中')
        ->toContain('getSenderName(message)')
        ->toContain('formatDate(message.created_at)')
        ->not->toContain('getPreview(message.body)')
        ->not->toContain('返信済み');

    expect($inboxPage)
        ->toContain('Mailbox')
        ->toContain('mailbox="inbox"');

    expect($sentPage)
        ->toContain('Mailbox')
        ->toContain('mailbox="sent"');

    expect($dashboardLayout)
        ->toContain('dashboardLinks')
        ->toContain('isLinksOpen')
        ->toContain('isActiveLinksSection')
        ->toContain('isActiveLinksOverview')
        ->toContain('ダッシュボード')
        ->toContain('設定')
        ->toContain('リンク')
        ->toContain('メッセージ')
        ->toContain('href="/dashboard/messages/inbox"')
        ->toContain('href="/dashboard/messages/sent"')
        ->toContain('href="/dashboard/links"')
        ->toContain('bg-white text-black')
        ->toContain('{{ link.display_name }}')
        ->toContain('truncate')
        ->toContain('/dashboard/links/${link.id}');
});

test('dashboard includes messages for links owned by the user', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $ownedLink = Link::factory()->create([
        'user_id' => $owner->id,
        'display_name' => 'Owned Profile',
    ]);
    $otherLink = Link::factory()->create([
        'user_id' => $otherUser->id,
        'display_name' => 'Other Profile',
    ]);

    Message::factory()->create([
        'link_id' => $ownedLink->id,
        'body' => 'Hello owner',
        'sender_display_name' => 'Sender Name',
    ]);
    Message::factory()->create([
        'link_id' => $otherLink->id,
        'body' => 'Not yours',
    ]);

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/Index')
            ->where('messagesCount', 1)
            ->where('messages.0.body', 'Hello owner')
            ->where('messages.0.link.display_name', 'Owned Profile')
            ->missing('messages.1')
        );
});

test('dashboard layout receives user links for nested sidebar navigation', function () {
    $user = User::factory()->create();
    Link::factory()->create([
        'user_id' => $user->id,
        'display_name' => 'Main Link',
        'slug' => 'main-link',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboardLinks.0.display_name', 'Main Link')
            ->where('dashboardLinks.0.slug', 'main-link')
        );
});

test('links dashboard shows owned links and summary cards', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $ownedLink = Link::factory()->create([
        'user_id' => $user->id,
        'display_name' => 'Owned Link',
    ]);
    Link::factory()->create([
        'user_id' => $other->id,
        'display_name' => 'Other Link',
    ]);

    $this->actingAs($user)
        ->get(route('links.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/Links/Index')
            ->where('linksCount', 1)
            ->where('totalAccessesLast30Days', 0)
            ->where('totalClicksLast30Days', 0)
            ->where('links.0.id', $ownedLink->id)
            ->where('links.0.display_name', 'Owned Link')
            ->missing('links.1')
        );
});

test('owner can open a link management dashboard page', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $link = Link::factory()->create([
        'user_id' => $owner->id,
        'display_name' => 'Creator Page',
        'slug' => 'creator-page',
    ]);

    $this->actingAs($owner)
        ->get(route('dashboard.links.show', $link->id))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/Links/Show')
            ->where('link.display_name', 'Creator Page')
        );

    $this->actingAs($other)
        ->get(route('dashboard.links.show', $link->id))
        ->assertForbidden();
});

test('owner can open the message management dashboard page', function () {
    $owner = User::factory()->create();
    $link = Link::factory()->create([
        'user_id' => $owner->id,
        'display_name' => 'Creator Page',
    ]);
    $message = Message::factory()->create([
        'link_id' => $link->id,
        'body' => 'A focused message',
    ]);

    $this->actingAs($owner)
        ->get(route('dashboard.messages', ['mailbox' => 'inbox']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/messages/Inbox')
            ->where('mailbox', 'inbox')
            ->where('messages.0.id', $message->id)
            ->where('messages.0.body', 'A focused message')
            ->where('messages.0.is_read', false)
            ->where('messages.0.sender.id', $message->sender_user_id)
            ->where('messages.0.link.display_name', 'Creator Page')
        );

    expect($message->fresh()->is_read)->toBeFalse();
});

test('owner can open sent messages from the message dashboard page', function () {
    $sender = User::factory()->create();
    $owner = User::factory()->create();
    $link = Link::factory()->create([
        'user_id' => $owner->id,
        'display_name' => 'Recipient Page',
    ]);
    $sentMessage = Message::factory()->create([
        'link_id' => $link->id,
        'sender_user_id' => $sender->id,
        'body' => 'A sent message',
    ]);

    $this->actingAs($sender)
        ->get(route('dashboard.messages', ['mailbox' => 'sent']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/messages/Sent')
            ->where('mailbox', 'sent')
            ->where('messages.0.id', $sentMessage->id)
            ->where('messages.0.body', 'A sent message')
            ->where('messages.0.sender.id', $sender->id)
            ->where('messages.0.link.display_name', 'Recipient Page')
            ->where('messages.0.link.avatar_url', $link->avatar_url)
        );
});

test('message dashboard returns not found for inaccessible inbox message keys', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $otherLink = Link::factory()->create([
        'user_id' => $other->id,
    ]);
    $otherMessage = Message::factory()->create([
        'link_id' => $otherLink->id,
    ]);

    $this->actingAs($owner)
        ->get(route('dashboard.messages', [
            'mailbox' => 'inbox',
            'message' => $otherMessage->id,
        ]))
        ->assertNotFound();
});

test('message dashboard returns not found for inaccessible sent message keys', function () {
    $sender = User::factory()->create();
    $otherSender = User::factory()->create();
    $link = Link::factory()->create();
    $otherSentMessage = Message::factory()->create([
        'link_id' => $link->id,
        'sender_user_id' => $otherSender->id,
    ]);

    $this->actingAs($sender)
        ->get(route('dashboard.messages', [
            'mailbox' => 'sent',
            'message' => $otherSentMessage->id,
        ]))
        ->assertNotFound();
});
