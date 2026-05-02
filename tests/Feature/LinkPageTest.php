<?php

use App\Models\Link;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('published link page renders the link component', function () {
    $user = User::factory()->create();
    $link = Link::query()->create([
        'user_id' => $user->id,
        'slug' => 'maessun',
        'display_name' => 'Maessun',
        'bio' => 'GridLink profile',
    ]);

    $response = $this->get(route('links.show', $link));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Link')
        ->where('link.slug', 'maessun')
        ->where('link.display_name', 'Maessun')
    );
});

test('empty link add controls use the full visible button area', function () {
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));

    expect($linkPage)
        ->toContain('z-20 grid w-[864px] grid-cols-4')
        ->toContain('z-20 grid w-full max-w-[356px]')
        ->toContain('grid-auto-rows: 92px;')
        ->toContain('grid-auto-rows: 73px;')
        ->toContain('flex h-full w-full flex-col items-center justify-center')
        ->toContain('Add Link');
});

test('link page displays top profile navigation', function () {
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));
    $navigation = file_get_contents(resource_path('js/components/links/LinkPageNavigation.vue'));

    expect($linkPage)
        ->toContain('LinkPageNavigation')
        ->toContain(':slug="props.link.slug"')
        ->toContain('active-tab="profile"')
        ->toContain('pt-14')
        ->toContain('min-[1025px]:pt-20');

    expect($navigation)
        ->toContain('aria-label="プロフィールナビゲーション"')
        ->toContain('h-9')
        ->toContain('z-[9000]')
        ->not->toContain('shadow-sm')
        ->toContain('max-w-[374px]')
        ->toContain('min-[1025px]:max-w-[1198px]')
        ->toContain('@{{ slug }}')
        ->toContain('activeTab')
        ->toContain('プロフィール')
        ->toContain('UserRound')
        ->toContain('Heart')
        ->toContain('items-center gap-1.5')
        ->toContain('<UserRound class="size-4" />')
        ->toContain('<Heart class="size-4" />')
        ->toContain('hidden min-[1025px]:inline')
        ->toContain('font-bold text-black')
        ->toContain('after:bg-black')
        ->toContain('サポート')
        ->toContain(':href="`/@${slug}/support`"')
        ->toContain('isLoggedIn')
        ->toContain('aria-label="通知"')
        ->not->toContain('aria-label="検索"')
        ->toContain('aria-label="ユーザーメニュー"')
        ->toContain('UserMenuContent')
        ->toContain('DropdownMenuContent align="end" class="z-[9001] w-56"')
        ->toContain('getInitials(auth.user?.name)')
        ->toContain('href="/login"')
        ->toContain('Login');
});

test('support page route renders support mock page', function () {
    $user = User::factory()->create();
    $link = Link::query()->create([
        'user_id' => $user->id,
        'slug' => 'maessun',
        'display_name' => 'Maessun',
        'bio' => 'GridLink profile',
    ]);

    $response = $this->get(route('links.support', $link));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('LinkSupport')
        ->where('link.slug', 'maessun')
        ->where('link.display_name', 'Maessun')
    );
});

test('support page displays mock support and fan messages', function () {
    $routeFile = file_get_contents(base_path('routes/web.php'));
    $controller = file_get_contents(app_path('Http/Controllers/LinkController.php'));
    $supportPage = file_get_contents(resource_path('js/pages/LinkSupport.vue'));

    expect($routeFile)
        ->toContain("Route::get('/@{link:slug}/support'")
        ->toContain("->name('links.support')");

    expect($controller)
        ->toContain('public function support(Link $link): Response')
        ->toContain("Inertia::render('LinkSupport'");

    expect($supportPage)
        ->toContain('LinkPageNavigation')
        ->toContain('active-tab="support"')
        ->toContain('少額支援')
        ->toContain('サポートする')
        ->toContain('ファンからのメッセージ')
        ->toContain('supportOptions')
        ->toContain('fanMessages')
        ->toContain('Buymeacoffee')
        ->not->toContain('@submit');
});

test('link toolbar exposes media upload controls', function () {
    $toolbar = file_get_contents(resource_path('js/components/links/LinkToolbar.vue'));
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));

    expect($toolbar)
        ->toContain('addMedia: [file: File]')
        ->toContain('accept="image/*,.apng"')
        ->toContain('gap-2')
        ->toContain('メディア');

    expect($linkPage)
        ->toContain('@add-media="addMediaWidget"')
        ->toContain("type: 'image'")
        ->toContain('w: 1')
        ->toContain('h: 2');
});

test('link toolbar exposes text widget controls next to media', function () {
    $toolbar = file_get_contents(resource_path('js/components/links/LinkToolbar.vue'));
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));

    expect($toolbar)
        ->toContain('addText: []')
        ->toContain("emit('addText')")
        ->toContain('size-8')
        ->toContain('h-8')
        ->toContain('テキスト');

    expect($linkPage)
        ->toContain('@add-text="addTextWidget"')
        ->toContain("type: 'text'")
        ->toContain('temp_text_')
        ->toContain("title: ''")
        ->toContain("bgColor: '#FFFFFF'")
        ->toContain("textAlign: 'left'")
        ->toContain("verticalAlign: 'center'");
});

test('link toolbar exposes support page button after section controls', function () {
    $toolbar = file_get_contents(resource_path('js/components/links/LinkToolbar.vue'));
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));

    expect($toolbar)
        ->toContain("import { Link as InertiaLink } from '@inertiajs/vue3'")
        ->toContain('Heart')
        ->toContain('supportUrl: string')
        ->toContain(':href="supportUrl"')
        ->toContain('サポート')
        ->toContain('aria-label="サポートページを開く"');

    expect($linkPage)
        ->toContain(':support-url="`/@${props.link.slug}/support`"');
});

test('private link pages with widgets show a publish notice', function () {
    $profile = file_get_contents(resource_path('js/components/links/LinkProfile.vue'));
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));
    $controller = file_get_contents(app_path('Http/Controllers/LinkController.php'));

    expect($profile)
        ->toContain('showPrivateNotice')
        ->toContain('このページは非公開です')
        ->toContain('あなたにだけ表示されています')
        ->toContain('公開する')
        ->toContain('publish: []');

    expect($linkPage)
        ->toContain('showPrivateNotice')
        ->toContain('localWidgets.value.length > 0')
        ->toContain('!props.link.is_published')
        ->toContain('@publish="publishLink"')
        ->toContain('is_published: true');

    expect($controller)
        ->toContain("'is_published' => ['nullable', 'boolean']")
        ->toContain("\$linkData['is_published']")
        ->toContain("\$request->boolean('is_published')");
});

test('text widgets support alignment controls and background colors', function () {
    $controls = file_get_contents(resource_path('js/components/links/LinkWidgetControls.vue'));
    $content = file_get_contents(resource_path('js/components/links/LinkWidgetContent.vue'));
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));

    expect($controls)
        ->toContain("widget.type === 'text'")
        ->toContain('updateBackgroundColor')
        ->toContain('updateTextAlign')
        ->toContain('updateVerticalAlign')
        ->toContain('AlignVerticalJustifyStart')
        ->toContain('AlignVerticalJustifyEnd')
        ->toContain('bottom-[-5.25rem]')
        ->toContain("widget.type === 'text' && !showColorPicker && !showContentLabels")
        ->toContain('colorSwatches')
        ->toContain("'#FFFFFF'")
        ->toContain("'#BFDBFE'")
        ->toContain("'#FBCFE8'")
        ->toContain("'#BBF7D0'")
        ->toContain("'#1F2937'");

    expect($content)
        ->toContain("widget.type === 'text'")
        ->toContain('textWidgetClasses')
        ->toContain('textWidgetStyle')
        ->toContain('text-white')
        ->toContain('textarea');

    expect($linkPage)
        ->toContain('updateTextWidgetBackgroundColor')
        ->toContain('updateTextWidgetAlign')
        ->toContain('updateTextWidgetVerticalAlign')
        ->toContain('@update-background-color=');
});

test('text widgets expose link setting control', function () {
    $controls = file_get_contents(resource_path('js/components/links/LinkWidgetControls.vue'));
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));

    expect($controls)
        ->toContain("widget.type === 'text'")
        ->toContain('openLinkSettings')
        ->toContain('Boolean(widget.content)')
        ->toContain("props.widget.type === 'link' || Boolean(props.widget.content)")
        ->toContain('v-if="shouldShowContentLabelsButton"')
        ->toContain('リンクを設定');

    expect($linkPage)
        ->toContain('updateWidgetLink(item.widget)')
        ->toContain('テキストリンクを設定')
        ->toContain(':initial-url="linkTargetWidget?.content"')
        ->toContain(':allow-empty=')
        ->toContain("linkTargetWidget.type !== 'link'")
        ->toContain('linkTargetWidget.value.content = url || null');
});

test('text widget color controls keep code input without native color picker', function () {
    $controls = file_get_contents(resource_path('js/components/links/LinkWidgetControls.vue'));

    expect($controls)
        ->toContain('aria-label="カラーコード"')
        ->toContain('inputmode="text"')
        ->not->toContain('type="color"');
});

test('link widgets expose sensitive content labels', function () {
    $controls = file_get_contents(resource_path('js/components/links/LinkWidgetControls.vue'));
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));
    $modal = file_get_contents(resource_path('js/components/links/LinkAddModal.vue'));

    expect($controls)
        ->toContain('LockKeyhole')
        ->toContain("widget.type === 'link'")
        ->toContain('shouldShowContentLabelsButton')
        ->toContain('コンテンツ設定')
        ->toContain('センシティブ')
        ->toContain('role="switch"')
        ->toContain('toggleSensitive')
        ->toContain('showContentLabels');

    expect(substr_count($controls, '@click.prevent.stop="openLinkSettings"'))
        ->toBe(1);

    expect($linkPage)
        ->toContain('updateWidgetSensitive')
        ->toContain('isSensitiveWidget')
        ->toContain('openSensitiveWarning')
        ->toContain('センシティブなコンテンツ')
        ->toContain('このリンクはセンシティブなコンテンツを含む可能性があります。')
        ->toContain('continueToSensitiveLink')
        ->toContain(':initial-sensitive="Boolean(linkTargetWidget?.settings?.sensitive)"')
        ->toContain('@update-sensitive=');

    expect($modal)
        ->toContain('Plus, X')
        ->toContain('allowEmpty?: boolean')
        ->toContain('initialSensitive?: boolean')
        ->toContain('isSensitive.value = Boolean(props.initialSensitive)')
        ->toContain('add: [url: string, isSensitive: boolean]')
        ->toContain("emit('add', '', isSensitive.value)")
        ->toContain('aria-label="リンクをクリア"')
        ->toContain('role="switch"')
        ->toContain('emit(\'add\', normalizedUrl, isSensitive.value)');
});

test('map widget controls and rendering are removed', function () {
    $toolbar = file_get_contents(resource_path('js/components/links/LinkToolbar.vue'));
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));
    $content = file_get_contents(resource_path('js/components/links/LinkWidgetContent.vue'));

    expect($toolbar)
        ->not->toContain('addMap')
        ->not->toContain('マップ');

    expect($linkPage)
        ->not->toContain('showAddMapModal')
        ->not->toContain("type: 'map'")
        ->not->toContain('temp_map_')
        ->not->toContain('openMapModalWithPos');

    expect($content)
        ->not->toContain("widget.type === 'map'")
        ->not->toContain('staticmap.openstreetmap.de');
});

test('clicking outside widgets clears active crop mode', function () {
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));

    expect($linkPage)
        ->toContain('handlePageClick')
        ->toContain("target?.closest('.vgl-item')")
        ->toContain('croppingWidgetId.value = null')
        ->toContain('@click="handlePageClick"');
});

test('link widget text wraps instead of truncating', function () {
    $content = file_get_contents(resource_path('js/components/links/LinkWidgetContent.vue'));

    expect($content)
        ->toContain('linkTitleEditorClasses')
        ->toContain('whitespace-pre-wrap break-words')
        ->toContain('linkTitleDisplayClasses')
        ->not->toContain('h-6 w-full truncate')
        ->not->toContain('h-6 truncate text-base');
});

test('link widget titles remain editable for service links while editing', function () {
    $content = file_get_contents(resource_path('js/components/links/LinkWidgetContent.vue'));

    expect($content)
        ->toContain('ref="linkTitleEditor"')
        ->toContain('contenteditable="true"')
        ->toContain('actionService')
        ->toContain('socialNetwork')
        ->not->toContain('v-if="isEditing && isActive"');
});

test('link widgets show play buttons and brand tinted backgrounds for services', function () {
    $content = file_get_contents(resource_path('js/components/links/LinkWidgetContent.vue'));

    expect($content)
        ->toContain('music.apple.com')
        ->toContain('open.spotify.com')
        ->toContain('music.youtube.com')
        ->toContain('play.google.com')
        ->toContain('isMusic: true')
        ->toContain('actionService')
        ->toContain('linkCardClasses')
        ->toContain('backgroundColor')
        ->toContain("actionLabel: 'Play'")
        ->toContain('bg-[#fff1f3]')
        ->toContain('bg-[#effaf3]');
});

test('link widgets show purchase and install actions for commerce and app stores', function () {
    $content = file_get_contents(resource_path('js/components/links/LinkWidgetContent.vue'));

    expect($content)
        ->toContain("isHost('amazon.com')")
        ->toContain("isHost('amazon.co.jp')")
        ->toContain("isHost('rakuten.co.jp')")
        ->toContain("isHost('shopify.com')")
        ->toContain("isHost('myshopify.com')")
        ->toContain("host === 'apps.apple.com'")
        ->toContain("host === 'play.google.com' && pathParts.includes('apps')")
        ->toContain("actionLabel: '購入'")
        ->toContain("actionLabel: 'インストール'")
        ->toContain('isCommerce: true')
        ->toContain('isAppStore: true')
        ->toContain('bg-[#fff8ed]')
        ->toContain('bg-[#f3faed]')
        ->toContain('bg-[#effaf5]')
        ->toContain('{{ actionService.actionLabel }}');
});

test('link widgets show support and follow actions for creator platforms', function () {
    $content = file_get_contents(resource_path('js/components/links/LinkWidgetContent.vue'));

    expect($content)
        ->toContain("isHost('buymeacoffee.com')")
        ->toContain("isHost('ko-fi.com')")
        ->toContain("isHost('patreon.com')")
        ->toContain("isHost('fantia.jp')")
        ->toContain("isHost('myfans.jp')")
        ->toContain("isHost('onlyfans.com')")
        ->toContain("isHost('fanbox.cc')")
        ->toContain("actionLabel: 'サポート'")
        ->toContain("actionLabel: 'フォロー'")
        ->toContain('isSupport: true')
        ->toContain('isFanPlatform: true')
        ->toContain('bg-[#fffbea]')
        ->toContain('bg-[#effaff]')
        ->toContain('bg-[#fff1f2]')
        ->toContain('bg-[#fff6ed]');
});

test('link widget images render without placeholder backgrounds', function () {
    $content = file_get_contents(resource_path('js/components/links/LinkWidgetContent.vue'));

    expect($content)
        ->toContain('chooseOgpImage')
        ->toContain('class="cursor-pointer group relative flex-1 overflow-hidden rounded-2xl"')
        ->toContain('class="cursor-pointer group relative w-full shrink-0 overflow-hidden rounded-2xl"')
        ->not->toContain('rounded-2xl bg-gray-100');
});

test('image widgets show an editable hover title field', function () {
    $content = file_get_contents(resource_path('js/components/links/LinkWidgetContent.vue'));

    expect($content)
        ->toContain('placeholder="Add a title..."')
        ->toContain('group-hover:opacity-100')
        ->toContain('bg-white/80')
        ->toContain('w-fit max-w-full whitespace-normal break-words')
        ->toContain('@input="updateTitle"')
        ->toContain('v-if="isEditing"');
});

test('image widgets expose link and crop controls', function () {
    $controls = file_get_contents(resource_path('js/components/links/LinkWidgetControls.vue'));
    $content = file_get_contents(resource_path('js/components/links/LinkWidgetContent.vue'));
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));

    expect($controls)
        ->toContain("widget.type === 'image'")
        ->toContain("emit('editLink')")
        ->toContain("emit('toggleCrop')")
        ->toContain('クロップを調整');

    expect($content)
        ->toContain('objectPosition')
        ->toContain("emit('update-crop'")
        ->toContain('@mousedown="startCropDrag"');

    expect($linkPage)
        ->toContain('bg-gray-200/75')
        ->toContain('画像リンクを設定')
        ->toContain('updateWidgetLink(item.widget)')
        ->toContain('toggleImageCrop(item.i)')
        ->toContain('updateImageWidgetCrop(')
        ->toContain('item.widget');
});

test('grid resize handles are hidden because resizing is controlled by the widget toolbar', function () {
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));

    expect($linkPage)
        ->toContain(':is-resizable="false"')
        ->toContain('@resize=')
        ->toContain('resizeWidget(');
});

test('authenticated users can upload media widget images', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('media.png', 600, 600);

    $response = $this->actingAs($user)->postJson(route('widgets.upload-image'), [
        'image' => $file,
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('url', '/storage/widgets/'.$file->hashName());

    Storage::disk('public')->assertExists('widgets/'.$file->hashName());
});

test('media widget upload rejects non image files', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('notes.txt', 12, 'text/plain');

    $response = $this->actingAs($user)->postJson(route('widgets.upload-image'), [
        'image' => $file,
    ]);

    $response->assertUnprocessable();
});

test('guests are redirected from links index to login', function () {
    $response = $this->get(route('links.index'));

    $response->assertRedirect(route('login'));
});

test('links index renders the management page without links', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('links.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Links/Index')
        ->has('links', 0)
        ->where('userName', $user->name)
    );
});

test('links index renders only the authenticated users links', function () {
    $user = User::factory()->create();
    Link::query()->create([
        'user_id' => $user->id,
        'slug' => 'maessun',
        'display_name' => 'Maessun',
        'bio' => 'GridLink profile',
    ]);
    Link::query()->create([
        'user_id' => User::factory()->create()->id,
        'slug' => 'someone-else',
        'display_name' => 'Someone Else',
        'bio' => 'Other profile',
    ]);

    $response = $this->actingAs($user)->get(route('links.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Links/Index')
        ->has('links', 1)
        ->where('links.0.slug', 'maessun')
        ->where('links.0.display_name', 'Maessun')
    );
});
