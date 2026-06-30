<?php

use App\Models\Link;
use App\Models\LinkViewDailyStat;
use App\Models\Message;
use App\Models\User;
use App\Models\Widget;
use App\Models\WidgetClickDailyStat;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    Link::factory()->create(['user_id' => $user->id]);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);
});

test('dashboard page layout matches specifications', function () {
    $dashboardPage = file_get_contents(resource_path('js/pages/dashboard/Index.vue'));
    $dashboardLayout = file_get_contents(resource_path('js/layouts/DashboardLayout.vue'));
    $bottomNav = file_get_contents(resource_path('js/components/DashboardBottomNav.vue'));
    $messagesPage = file_get_contents(resource_path('js/pages/dashboard/messages/Mailbox.vue'));

    expect($dashboardPage)
        ->toContain('DashboardLayout')
        ->not->toContain('AppLayout')
        ->toContain('リンク')
        ->toContain('メッセージ')
        ->toContain('閲覧数(30日)')
        ->toContain('クリック数(30日)')
        ->toContain('閲覧数の推移')
        ->toContain('クリック数の推移')
        ->toContain('viewChartData')
        ->toContain('clickChartData')
        ->toContain('grid grid-cols-4 gap-4')
        ->not->toContain('リンクを追加')
        ->not->toContain('最近のメッセージ')
        ->toContain('href="/dashboard/links"');

    expect($messagesPage)
        ->toContain('メッセージ管理')
        ->toContain("url.searchParams.set('message'")
        ->toContain("url.searchParams.delete('message'")
        ->toContain('const selectedMessageId = ref<string | null>(getInitialSelectedMessageId())')
        ->toContain('isInbox.value ? getSenderName(message) : message.link.display_name')
        ->toContain('isInbox.value && !message.is_read')
        ->toContain('useForm({ is_read: true }).patch')
        ->toContain('!message.is_read')
        ->toContain("switchMailbox('inbox')")
        ->toContain("switchMailbox('sent')")
        ->toContain('max-w-[250px]')
        ->toContain('md:grid-cols-[340px_minmax(0,1fr)]')
        ->toContain('md:static')
        ->toContain('md:hidden')
        ->toContain('さらに表示')
        ->toContain('loadMoreMessages')
        ->toContain('props.inboxLimit + 20')
        ->toContain('props.sentLimit + 20')
        ->toContain('受信箱')
        ->toContain('送信箱')
        ->toContain('プライベート')
        ->not->toContain('非公開希望')
        ->not->toContain('公開中')
        ->toContain('getSenderName(message)')
        ->toContain('formatDate(message.created_at)')
        ->not->toContain('getPreview(message.body)')
        ->not->toContain('返信済み');

    expect($dashboardLayout)
        ->toContain('dashboardLinks')
        ->toContain('isLinksOpen')
        ->toContain('isActiveLinksSection')
        ->toContain('isActiveLinksOverview')
        ->toContain('ダッシュボード')
        ->toContain('設定')
        ->toContain('リンク')
        ->toContain('メッセージ')
        ->toContain('href="/dashboard/messages"')
        ->toContain('min-[1025px]:translate-x-0')
        ->toContain('min-[1025px]:hidden')
        ->toContain('min-[1025px]:pb-0')
        ->toContain('href="/dashboard/links"')
        ->toContain('bg-white text-black')
        ->toContain('{{ link.display_name }}')
        ->toContain('truncate')
        ->toContain('/dashboard/links/${link.id}')
        ->not->toContain('検索...');

    expect($bottomNav)
        ->toContain('ダッシュボード')
        ->toContain('メッセージ')
        ->toContain('リンク')
        ->toContain("href: '/dashboard/links'")
        ->toContain('お知らせ')
        ->toContain('設定')
        ->toContain('min-[1025px]:hidden');
});

test('dashboard includes summary metrics and view chart for links owned by the user', function () {
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
    $widget = Widget::factory()->create(['link_id' => $ownedLink->id]);
    LinkViewDailyStat::create([
        'link_id' => $ownedLink->id,
        'date' => now()->toDateString(),
        'view_count' => 7,
    ]);
    LinkViewDailyStat::create([
        'link_id' => $otherLink->id,
        'date' => now()->toDateString(),
        'view_count' => 99,
    ]);
    WidgetClickDailyStat::create([
        'link_id' => $ownedLink->id,
        'widget_id' => $widget->id,
        'date' => now()->toDateString(),
        'click_count' => 3,
    ]);

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/Index')
            ->where('linksCount', 1)
            ->where('messagesCount', 1)
            ->where('totalViewsLast30Days', 7)
            ->where('totalClicksLast30Days', 3)
            ->has('viewChartData', 30)
            ->has('clickChartData', 30)
            ->where('viewChartData.29.views', 7)
            ->where('clickChartData.29.clicks', 3)
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
        ->get(route('dashboard.messages'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/messages/Mailbox')
            ->where('initialMailbox', 'inbox')
            ->where('inboxMessages.0.id', $message->id)
            ->where('inboxMessages.0.body', 'A focused message')
            ->where('inboxMessages.0.is_read', false)
            ->where('inboxMessages.0.sender.id', $message->sender_user_id)
            ->where('inboxMessages.0.link.display_name', 'Creator Page')
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
            ->component('dashboard/messages/Mailbox')
            ->where('initialMailbox', 'sent')
            ->where('sentMessages.0.id', $sentMessage->id)
            ->where('sentMessages.0.body', 'A sent message')
            ->where('sentMessages.0.sender.id', $sender->id)
            ->where('sentMessages.0.link.display_name', 'Recipient Page')
            ->where('sentMessages.0.link.avatar_url', $link->avatar_url)
        );
});

test('message dashboard loads messages in batches of twenty', function () {
    $owner = User::factory()->create();
    $link = Link::factory()->create([
        'user_id' => $owner->id,
    ]);

    Message::factory()
        ->count(21)
        ->sequence(fn ($sequence) => [
            'created_at' => now()->subMinutes($sequence->index),
        ])
        ->create([
            'link_id' => $link->id,
        ]);

    $this->actingAs($owner)
        ->get(route('dashboard.messages'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/messages/Mailbox')
            ->where('inboxLimit', 20)
            ->where('hasMoreInboxMessages', true)
            ->has('inboxMessages', 20)
        );

    $this->actingAs($owner)
        ->get(route('dashboard.messages', ['inboxLimit' => 40]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/messages/Mailbox')
            ->where('inboxLimit', 40)
            ->where('hasMoreInboxMessages', false)
            ->has('inboxMessages', 21)
        );
});

test('message dashboard expands the initial batch for a selected older message', function () {
    $owner = User::factory()->create();
    $link = Link::factory()->create([
        'user_id' => $owner->id,
    ]);

    $messages = Message::factory()
        ->count(25)
        ->sequence(fn ($sequence) => [
            'created_at' => now()->subMinutes($sequence->index),
        ])
        ->create([
            'link_id' => $link->id,
        ]);
    $oldestMessage = $messages->last();

    $this->actingAs($owner)
        ->get(route('dashboard.messages', ['message' => $oldestMessage->id]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/messages/Mailbox')
            ->where('inboxLimit', 25)
            ->where('inboxMessages.24.id', $oldestMessage->id)
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
