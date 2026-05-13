<?php

use App\Models\Link;
use App\Models\Title;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

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

test('empty link add controls fit the responsive widget area', function () {
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));

    expect($linkPage)
        ->toContain('max-w-[374px]')
        ->toContain('min-[1025px]:px-0')
        ->toContain('min-[1025px]:max-w-none min-[1025px]:flex-row')
        ->toContain("previewMode === 'desktop'\n                            ? 'pb-24 min-[1025px]:mx-0 min-[1025px]:w-[736px] min-[1025px]:max-w-none min-[1025px]:shrink-0'\n                            : 'mt-2 pb-24'")
        ->toContain('class="-m-3 w-[calc(100%+24px)]"')
        ->toContain(':col-num="2"')
        ->toContain(':row-height="81.5"')
        ->toContain(':row-height="84.5"')
        ->toContain('desktopPlaceholderItems')
        ->toContain('mobilePlaceholderItems')
        ->toContain('localWidgets.value.length >= 3')
        ->toContain("placeholder.type === 'media' ? 'image' : placeholder.type")
        ->toContain('widget.type === widgetType')
        ->toContain("widget.type === 'link'")
        ->toContain("'flex w-full max-w-[374px] flex-col gap-4 pb-32'")
        ->toContain(':static="true"')
        ->toContain('flex h-full w-full cursor-pointer flex-col items-center justify-center')
        ->toContain('リンクを追加する')
        ->toContain('メディアを追加する')
        ->toContain('テキストを追加する')
        ->toContain("i: 'placeholder-media'")
        ->toContain("i: 'placeholder-link'")
        ->toContain("i: 'placeholder-text'")
        ->toContain("i: 'placeholder-mobile-link'")
        ->not->toContain('isGridAreaEmpty')
        ->toContain('for (let y = 0; y < maxY + 1000; y += 1)')
        ->toContain('openMediaPickerFromEmptyState({')
        ->toContain('const toolbarWidgetSize = { w: 1, h: 2 } as const')
        ->toContain('pendingWidgetSize.value = toolbarWidgetSize')
        ->toContain('const addTextWidget = (position: WidgetPosition | null = null)')
        ->toContain('const addMediaWidget = async (')
        ->toContain('ref="emptyMediaInput"');
});

test('link page displays top profile navigation', function () {
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));
    $navigation = file_get_contents(resource_path('js/components/links/LinkPageNavigation.vue'));

    expect($linkPage)
        ->toContain('LinkPageNavigation')
        ->toContain(':slug="props.link.slug"')
        ->toContain('active-tab="profile"')
        ->toContain('pt-8')
        ->toContain('pt-12 min-[1025px]:px-0 sm:px-8');

    expect($navigation)
        ->toContain('aria-label="プロフィールナビゲーション"')
        ->toContain('h-9')
        ->toContain('z-[9000]')
        ->toContain('border-b border-gray-200 bg-white/95')
        ->not->toContain('shadow-sm')
        ->toContain('max-w-[374px]')
        ->toContain('min-[1025px]:max-w-[1198px]')
        ->toContain('@{{ slug }}')
        ->toContain('activeTab')
        ->toContain('プロフィール')
        ->toContain('UserRound')
        ->toContain('MessageCircleHeart')
        ->toContain('items-center gap-1.5')
        ->toContain('<UserRound class="size-4" />')
        ->toContain('<MessageCircleHeart class="size-4" />')
        ->toContain('hidden min-[1025px]:inline')
        ->toContain('font-bold text-black')
        ->toContain('after:bg-black')
        ->toContain('メッセージ')
        ->toContain(':href="`/@${slug}/letter`"')
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

test('link page shows visitor floating share actions', function () {
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));

    expect($linkPage)
        ->toContain('const copiedProfileUrl = ref(false)')
        ->toContain('const profileUrl = computed(() => {')
        ->toContain('const copyProfileUrl = async () => {')
        ->toContain('navigator.clipboard.writeText(profileUrl.value)')
        ->toContain('}, 2400)')
        ->toContain('v-if="!isOwner"')
        ->toContain('aria-label="プロフィールアクション"')
        ->toContain('class="flex h-11 items-center gap-2 rounded-full')
        ->toContain('URLをコピーしました')
        ->toContain('<Check')
        ->toContain('class="size-4 text-white"')
        ->toContain('class="flex h-9 items-center justify-center gap-2 rounded-full')
        ->toContain('@click="copyProfileUrl"')
        ->toContain('<Copy v-else class="size-4" />')
        ->toContain('class="flex size-9 items-center justify-center rounded-full')
        ->toContain('<MoreHorizontal class="size-5" />')
        ->toContain('@select="reportUser"')
        ->toContain('このユーザーを通報する')
        ->not->toContain('fixed bottom-20 left-4')
        ->toContain('<footer')
        ->toContain('Built with GridLink');
});

test('letter page route renders letter mock page', function () {
    $user = User::factory()->create();
    $link = Link::query()->create([
        'user_id' => $user->id,
        'slug' => 'maessun',
        'display_name' => 'Maessun',
        'bio' => 'GridLink profile',
    ]);

    $response = $this->get(route('links.letter', $link));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('LinkLetter')
        ->where('link.slug', 'maessun')
        ->where('link.display_name', 'Maessun')
    );
});

test('letter page displays mock support and fan messages', function () {
    $routeFile = file_get_contents(base_path('routes/web.php'));
    $controller = file_get_contents(app_path('Http/Controllers/LinkController.php'));
    $letterPage = file_get_contents(resource_path('js/pages/LinkLetter.vue'));

    expect($routeFile)
        ->toContain("Route::get('/@{link:slug}/letter'")
        ->toContain("->name('links.letter')");

    expect($controller)
        ->toContain('public function letter(Link $link): Response')
        ->toContain("Inertia::render('LinkLetter'");

    expect($letterPage)
        ->toContain('LinkPageNavigation')
        ->toContain('active-tab="letter"')
        ->toContain('bg-neutral-100')
        ->toContain('fanMessages')
        ->toContain('const isOwner = computed')

        ->toContain('page.props.auth?.user && page.props.auth.user.id === props.link.user_id')
        ->toContain("const activeTab = ref<'write' | 'read'>('write')")
        ->toContain('書く')
        ->toContain('読む')
        ->toContain("activeTab === 'write'")
        ->toContain("activeTab === 'read'")
        ->toContain('ファンレターを書く')
        ->toContain('レターを送る')
        ->toContain('ファンからのメッセージ')
        ->toContain('supportOptions')
        ->toContain('v-if="isOwner"')
        ->toContain('収益設定をすると支援が受け取れます。')
        ->toContain('収益設定')
        ->toContain('v-else class="mt-5 grid grid-cols-3 gap-2"')
        ->toContain(':disabled="isOwner"')
        ->toContain('disabled:cursor-not-allowed')
        ->toContain('fanMessages')
        ->toContain('Buymeacoffee')
        ->toContain('after:bg-black')
        ->toContain('rounded-xl bg-black')
        ->not->toContain('bg-violet')
        ->not->toContain('text-violet')
        ->not->toContain('border-violet')
        ->not->toContain('ring-violet')
        ->not->toContain('@{{ link.slug }}')
        ->not->toContain('{{ link.bio }}')
        ->not->toContain('v-if="link.bio"')
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

test('link toolbar exposes letter page button after section controls', function () {
    $toolbar = file_get_contents(resource_path('js/components/links/LinkToolbar.vue'));
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));

    expect($toolbar)
        ->toContain("import { Link as InertiaLink } from '@inertiajs/vue3'")
        ->toContain('MessageCircleHeart')
        ->toContain('letterUrl: string')
        ->toContain(':href="letterUrl"')
        ->toContain('ファンレター')
        ->toContain('aria-label="ファンレターページを開く"');

    expect($linkPage)
        ->toContain(':letter-url="`/@${props.link.slug}/letter`"');
});

test('private link pages show a publish button', function () {
    $profile = file_get_contents(resource_path('js/components/links/LinkProfile.vue'));
    $toolbar = file_get_contents(resource_path('js/components/links/LinkToolbar.vue'));
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));
    $controller = file_get_contents(app_path('Http/Controllers/LinkController.php'));
    $request = file_get_contents(app_path('Http/Requests/Links/UpdateLinkRequest.php'));

    expect($profile)
        ->not->toContain('プロフィールを公開する')
        ->not->toContain('公開する')
        ->not->toContain('publish: []')
        ->not->toContain('showPublicCopyButton')
        ->not->toContain('const handleShare = async () => {')
        ->not->toContain('const reportUser = () => {')
        ->not->toContain('<Share2')
        ->not->toContain('<MoreHorizontal')
        ->not->toContain('<Flag')
        ->toContain('border-gray-200 bg-gray-100/70')
        ->not->toContain('isPublished?: boolean');

    expect($toolbar)
        ->toContain('PartyPopper')
        ->toContain('isPublished?: boolean')
        ->toContain('isShareCopied?: boolean')
        ->toContain('hasWidgets?: boolean')
        ->toContain('publish: []')
        ->toContain('share: []')
        ->toContain('v-if="!isEditing && !isPublished && hasWidgets"')
        ->toContain('v-if="!isEditing && isPublished"')
        ->toContain("emit('publish')")
        ->toContain("emit('share')")
        ->toContain('URLをコピーしました')
        ->toContain('class="size-4 text-white"')
        ->toContain('rounded-xl bg-black px-4 text-sm text-white')
        ->toContain('rounded-xl bg-black px-3 text-sm font-bold text-white')
        ->toContain('<span>シェア</span>')
        ->toContain('<span>公開</span>')
        ->toContain('<span v-if="!isEditing">編集</span>')
        ->toContain('<Copy v-else class="size-4" />')
        ->not->toContain('bg-blue-600')
        ->not->toContain('hover:bg-blue-700')
        ->toContain('公開');

    expect($linkPage)
        ->not->toContain('localWidgets.value.length > 0')
        ->toContain('const isLinkPublished = ref(Boolean(props.link.is_published))')
        ->toContain(':is-published="isLinkPublished"')
        ->toContain(':is-share-copied="copiedProfileUrl"')
        ->toContain(':has-widgets="localWidgets.length > 0"')
        ->toContain('@share="copyProfileUrl"')
        ->not->toContain('<LinkProfile\n                    v-model:display-name="editForm.display_name"\n                    v-model:bio="editForm.bio"\n                    :is-editing="isEditing"\n                    :preview-mode="previewMode"\n                    :avatar-url="profileAvatarUrl"\n                    :display-initial="displayInitial"\n                    :is-published="isLinkPublished"')
        ->toContain('@publish="publishLink"')
        ->toContain('is_published: true')
        ->toContain('burstPublishConfetti')
        ->toContain('publish-confetti-piece');

    expect($request)->toContain("'is_published' => ['nullable', 'boolean']");

    expect($controller)
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
        ->toContain('flex h-11 translate-x-1/2')
        ->toContain("mode === 'desktop' ? 'bottom-2' : 'bottom-[-4.5rem]'")
        ->toContain('class="h-8 w-20 rounded-lg')
        ->toContain("widget.type === 'text' && !showContentLabels")
        ->not->toContain("widget.type === 'text' && !showColorPicker && !showContentLabels")
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
        ->toContain('Boolean(linkTargetWidget && linkTargetWidget.type !== \'link\')')
        ->toContain('linkTargetWidget.value.content =');

    expect($linkPage)
        ->toContain('updateWidgetLink(item.widget)')
        ->toContain('テキストリンクを設定')
        ->toContain(':initial-url="linkTargetWidget?.content"')
        ->toContain('Boolean(linkTargetWidget && linkTargetWidget.type !== \'link\')')
        ->toContain('linkTargetWidget.value.content =');
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
    $services = file_get_contents(resource_path('js/lib/linkServices.ts'));

    expect($services)
        ->toContain('music.apple.com')
        ->toContain('open.spotify.com')
        ->toContain('music.youtube.com')
        ->toContain('isMusic: true')
        ->toContain("actionLabel: 'プレイ'")
        ->toContain("'bg-[#fff1f3]'")
        ->toContain("'bg-[#effaf3]'");
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
        ->toContain('{{ actionServiceLabel }}');
});

test('link widget action labels render as unified button-like spans', function () {
    $content = file_get_contents(resource_path('js/components/links/LinkWidgetContent.vue'));

    expect($content)
        ->toContain('const actionPillClass')
        ->toContain('inline-flex')
        ->toContain('h-8')
        ->toContain('w-fit')
        ->toContain('items-center')
        ->toContain('justify-center')
        ->toContain('socialActionPillClasses')
        ->toContain('actionServicePillClasses')
        ->toContain('actionServiceLabel')
        ->not->toContain('<Play')
        ->not->toContain("actionLabel: 'Play'");
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

test('mobile widget editing uses tap operation controls and a bottom resize toolbar', function () {
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));
    $toolbar = file_get_contents(resource_path('js/components/links/LinkToolbar.vue'));

    expect($linkPage)
        ->toContain("window.matchMedia('(max-width: 1024px)')")
        ->toContain('activateMobileWidget')
        ->toContain('completeMobileWidgetOperation')
        ->toContain('activeMobileLayoutItem')
        ->toContain('activeMobileSizeOptions')
        ->toContain("activeMobileLayoutItem.widget.type !== 'section'")
        ->toContain("'.mobile-widget-move-handle'")
        ->toContain(':drag-allow-from=')
        ->toContain(':drag-ignore-from=')
        ->toContain('widget-text-input--focused, a, input, textarea')
        ->toContain('mobile-widget-ignore-drag, .widget-text-input--focused, a, input, textarea')
        ->not->toContain('[contenteditable=true]')
        ->not->toContain('mobile-widget-ignore-drag, a, button')
        ->toContain("activeWidgetId === item.i\n                                    ? 'cursor-default'")
        ->toContain('Math.max(columns - width, 0)')
        ->toContain('pushCollidingItems')
        ->not->toContain('item.y = maxY')
        ->toContain('isEditing && !isSmallViewport')
        ->toContain('cursor-grabbing')
        ->toContain('cursor-grab active:cursor-grabbing')
        ->toContain('mobile-widget-move-handle')
        ->toContain('ウィジェットを移動')
        ->toContain('ウィジェットを編集')
        ->toContain('ウィジェットを削除')
        ->toContain('bg-red-600')
        ->toContain('<Trash2 class="size-4" />')
        ->toContain('rounded-2xl border-2 border-black')
        ->toContain('flex size-10 translate-x-1/2')
        ->toContain('cursor-grab touch-none')
        ->toContain('<Move class="size-5" />')
        ->toContain('<Pencil class="size-4" />')
        ->toContain('@resize-mobile-widget="resizeActiveMobileWidget"')
        ->toContain('@complete-mobile-widget-operation="completeMobileWidgetOperation"');

    expect($toolbar)
        ->toContain('mobileWidgetOperationActive?: boolean')
        ->toContain('mobileSizeOptions?: any[]')
        ->toContain('activeMobileWidget?: any | null')
        ->toContain('resizeMobileWidget')
        ->toContain('completeMobileWidgetOperation')
        ->toContain('v-if="!mobileWidgetOperationActive"')
        ->toContain("'min-w-16 gap-1.5 bg-emerald-500 px-3 text-sm font-bold text-white")
        ->toContain('<span v-if="isEditing">保存</span>')
        ->toContain('hidden h-11 items-center gap-1.5 rounded-2xl')
        ->toContain('class="flex size-8 items-center justify-center')
        ->toContain('rounded-lg transition-colors"')
        ->toContain('class="flex h-8 min-w-12 items-center justify-center whitespace-nowrap rounded-lg bg-white px-4 text-xs font-bold text-black transition-transform active:scale-95"')
        ->toContain('v-for="option in mobileSizeOptions"')
        ->toContain("emit('resizeMobileWidget', option.size)")
        ->toContain("emit('completeMobileWidgetOperation')")
        ->toContain('@click.stop')
        ->toContain('完了');

    expect(substr_count($linkPage, ':drag-allow-from='))
        ->toBe(1);
});

test('mobile widget move handle allows grid drag events to bubble', function () {
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));
    $handleStart = strpos($linkPage, 'aria-label="ウィジェットを移動"');

    expect($handleStart)->not->toBeFalse();

    $handleMarkup = substr($linkPage, $handleStart, 700);

    expect($handleMarkup)
        ->toContain('mobile-widget-move-handle')
        ->toContain('@click.stop.prevent')
        ->toContain('@pointerdown=')
        ->toContain('startWidgetDragPointer(')
        ->not->toContain('@pointerdown.stop');
});

test('widget drag end suppresses the follow up click selection', function () {
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));

    expect($linkPage)
        ->toContain('const suppressWidgetClickUntil = ref(0)')
        ->toContain('const beginDraggedWidget = (')
        ->toContain('const finishDraggedWidget = (')
        ->toContain('const startWidgetDragPointer = (')
        ->toContain('const handleWindowPointerMove = (event: PointerEvent) => {')
        ->toContain('const handleWindowPointerUp = () => {')
        ->toContain('suppressWidgetClickUntil.value = Date.now() + 300')
        ->toContain('const shouldSuppressWidgetClick = ()')
        ->toContain('const isSuppressingWidgetInteractions = computed(() => {')
        ->toContain('const isWidgetInteractionDisabled = (widgetId: string | number) => {')
        ->toContain('const handleWidgetClick = (event: MouseEvent, item: any)')
        ->toContain("window.addEventListener('pointermove', handleWindowPointerMove)")
        ->toContain("window.addEventListener('pointerup', handleWindowPointerUp)")
        ->toContain("window.addEventListener('pointercancel', handleWindowPointerUp)")
        ->toContain('startWidgetDragPointer(')
        ->toContain('@click="handleWidgetClick($event, item)"')
        ->toContain('@activate="activateWidget(item.i)"')
        ->toContain("'pointer-events-none'");

    expect(substr_count($linkPage, '@click="handleWidgetClick($event, item)"'))
        ->toBe(2);
});

test('widget dragging uses motion wobble animations', function () {
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));
    $widgetContent = file_get_contents(resource_path('js/components/links/LinkWidgetContent.vue'));

    expect($linkPage)
        ->toContain("import { usePreferredReducedMotion } from '@vueuse/core';")
        ->toContain('const dragPointerState = ref<{')
        ->toContain('const dragVisualState = ref<{')
        ->toContain('const prefersReducedMotion = usePreferredReducedMotion()')
        ->toContain('const updateDraggedWidgetVisual = (')
        ->toContain('const scheduleDraggedWidgetSettle = (')
        ->toContain('const clampedOffset = Math.max(Math.min(horizontalOffset, 48), -48)')
        ->toContain('x: isReducedMotion ? clampedOffset * 0.012 : clampedOffset * 0.022')
        ->toContain('rotate: isReducedMotion ? rotateTarget * 0.3 : rotateTarget')
        ->toContain('return `link-widget-${mode}-${widgetId}`')
        ->toContain('updateDraggedWidgetVisual(mode, widgetId, 0)')
        ->toContain('scheduleDraggedWidgetSettle(mode, widgetId)')
        ->toContain('const horizontalOffset = event.clientX - pointerState.startX')
        ->toContain('getDraggedWidgetStyle(\'desktop\', item.i)')
        ->toContain('getDraggedWidgetStyle(\'mobile\', item.i)');

    expect($widgetContent)
        ->toContain('const cardFrameStyle = computed(() => {')
        ->toContain("backgroundColor: 'transparent'");
});

test('newly added widgets briefly bounce into place', function () {
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));

    expect($linkPage)
        ->toContain('const newlyAddedWidgetIds = ref<Set<string>>(new Set())')
        ->toContain('const markWidgetAsNewlyAdded = (widget: any) => {')
        ->toContain('const addLocalWidget = (widget: any) => {')
        ->toContain('markWidgetAsNewlyAdded(widget)')
        ->toContain('newlyAddedWidgetIds.has(item.i)')
        ->toContain("'widget-bounce-enter'")
        ->toContain('@keyframes widgetBounceIn')
        ->toContain('transform: scale(1.12);')
        ->toContain('@media (prefers-reduced-motion: reduce)');
});

test('toasts render above sheets and modals', function () {
    $toaster = file_get_contents(resource_path('js/components/ui/toast/Toaster.vue'));

    expect($toaster)
        ->toContain('z-[9999]')
        ->toContain("window.addEventListener('app-toast', handleToastEvent)")
        ->not->toContain('z-[100]');
});

test('editing warns before leaving with unsaved changes', function () {
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));

    expect($linkPage)
        ->toContain('hasUnsavedChanges')
        ->toContain('isSavingChanges')
        ->toContain('shouldConfirmUnsavedChanges')
        ->toContain('handleBeforeUnload')
        ->toContain("window.addEventListener('beforeunload', handleBeforeUnload)")
        ->toContain("window.removeEventListener('beforeunload', handleBeforeUnload)")
        ->toContain("router.on('before'")
        ->toContain('未保存の変更があります。ページを離れますか？')
        ->toContain('event.preventDefault()')
        ->toContain('event.returnValue')
        ->toContain('hasUnsavedChanges.value = false')
        ->toContain('入力エラーにより保存に失敗しました')
        ->toContain('showSaveValidationErrorToast')
        ->toContain('markDirty()');
});

test('widget editing enforces content limits in the interface', function () {
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));
    $content = file_get_contents(resource_path('js/components/links/LinkWidgetContent.vue'));
    $modal = file_get_contents(resource_path('js/components/links/LinkAddModal.vue'));

    expect($linkPage)
        ->toContain('const MAX_WIDGETS = 50')
        ->toContain('const MAX_URL_LENGTH = 2000')
        ->toContain('const MAX_LINK_TITLE_LENGTH = 100')
        ->toContain('const MAX_TEXT_WIDGET_LENGTH = 4500')
        ->toContain('canAddWidget')
        ->toContain('ウィジェットは50個まで追加できます')
        ->toContain('normalizedUrl.length > MAX_URL_LENGTH')
        ->toContain(':maxlength="MAX_URL_LENGTH"')
        ->toContain(':maxlength="MAX_LINK_TITLE_LENGTH"')
        ->toContain('@beforeinput=')
        ->toContain('limitPlainTextBeforeInput')
        ->toContain('pastePlainTextWithLimit');

    expect($content)
        ->toContain('const MAX_LINK_TITLE_LENGTH = 100')
        ->toContain('const MAX_TEXT_WIDGET_LENGTH = 4500')
        ->toContain('widget-text-input')
        ->toContain('widget-text-input--focused')
        ->toContain('stopPointerWhenFocused')
        ->toContain('shape.value')
        ->toContain("isLinkTitleFocused.value ? 'cursor-text' : 'cursor-grab active:cursor-grabbing'")
        ->not->toContain('cursor-move')
        ->toContain('cursor-text')
        ->toContain('maxlength="4500"')
        ->toContain('@beforeinput="limitPlainTextBeforeInput($event, MAX_LINK_TITLE_LENGTH)"')
        ->toContain('pastePlainText($event, MAX_TEXT_WIDGET_LENGTH)');

    expect($modal)
        ->toContain('const maxUrlLength = 2000')
        ->toContain(':maxlength="maxUrlLength"')
        ->toContain('normalizedUrl.length > maxUrlLength');
});

test('widget sync rejects invalid widget limits', function () {
    $user = User::factory()->create();
    $link = Link::query()->create([
        'user_id' => $user->id,
        'slug' => 'limit-check',
        'display_name' => 'Limit Check',
    ]);

    $widget = [
        'id' => null,
        'type' => 'link',
        'content' => 'https://example.com',
        'thumbnail_url' => null,
        'x' => 0,
        'y' => 0,
        'w' => 1,
        'h' => 2,
        'x_mobile' => 0,
        'y_mobile' => 0,
        'w_mobile' => 1,
        'h_mobile' => 2,
        'settings' => ['title' => str_repeat('a', 101)],
    ];

    $this
        ->actingAs($user)
        ->from(route('links.show', $link))
        ->post(route('widgets.sync', $link), ['widgets' => [$widget]])
        ->assertRedirect(route('links.show', $link))
        ->assertSessionHasErrors('widgets');

    $widget['settings']['title'] = 'Valid';
    $widget['content'] = str_repeat('a', 2001);

    $this
        ->actingAs($user)
        ->from(route('links.show', $link))
        ->post(route('widgets.sync', $link), ['widgets' => [$widget]])
        ->assertRedirect(route('links.show', $link))
        ->assertSessionHasErrors('widgets.0.content');

    $widgets = array_fill(0, 51, [
        ...$widget,
        'content' => 'https://example.com',
    ]);

    $this
        ->actingAs($user)
        ->from(route('links.show', $link))
        ->post(route('widgets.sync', $link), ['widgets' => $widgets])
        ->assertRedirect(route('links.show', $link))
        ->assertSessionHasErrors('widgets');
});

test('mobile toolbar link add uses a bottom sheet instead of the modal', function () {
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));

    expect($linkPage)
        ->toContain('openAddLinkFromToolbar')
        ->toContain('@add-link="openAddLinkFromToolbar"')
        ->toContain('showMobileAddLinkSheet')
        ->toContain('mobileAddLinkUrl')
        ->toContain('mobileAddLinkSensitive')
        ->toContain('submitMobileAddLink')
        ->toContain('if (isSmallViewport.value)')
        ->toContain('showAddLinkModal.value = true')
        ->toContain(':open="showMobileAddLinkSheet"')
        ->toContain('setMobileAddLinkSheetOpen')
        ->toContain('リンクを追加')
        ->toContain(':show-close="false"')
        ->toContain('sticky top-0 z-10 border-b border-gray-100 bg-white p-0')
        ->toContain('aria-label="キャンセル"')
        ->toContain('@click="closeMobileAddLinkSheet"')
        ->toContain('@click="submitMobileAddLink"')
        ->toContain('@input="mobileAddLinkError = \'\'"')
        ->toContain('有効なURLを入力してください')
        ->toContain('addLinkWidget(normalizedUrl, mobileAddLinkSensitive.value)')
        ->toContain('role="switch"');
});

test('mobile link widget edit button opens a bottom sheet editor', function () {
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));

    expect($linkPage)
        ->toContain("widget.type === 'link'")
        ->toContain('openMobileLinkEditor(widget)')
        ->toContain('mobileLinkEditorWidget')
        ->toContain('setMobileLinkEditorOpen')
        ->toContain('<Sheet')
        ->toContain('side="bottom"')
        ->toContain('overlay-class="z-[9999]"')
        ->toContain('overflow-hidden rounded-t-3xl')
        ->toContain('gap-0')
        ->toContain('p-0')
        ->toContain('max-h-[calc(92vh-64px)]')
        ->toContain('overflow-y-auto')
        ->toContain('リンクを編集')
        ->toContain('updateMobileLinkTitle')
        ->toContain('chooseMobileLinkImage')
        ->toContain('updateMobileLinkImage')
        ->toContain('removeMobileLinkImage')
        ->toContain('updateMobileLinkSensitive')
        ->toContain('センシティブ')
        ->toContain('h-48 w-full')
        ->toContain('@click="chooseMobileLinkImage"')
        ->toContain('absolute top-3 right-3')
        ->toContain('@click.stop="removeMobileLinkImage"')
        ->toContain('aria-label="画像を削除"')
        ->toContain('bg-red-600 text-white')
        ->toContain('<Trash2 class="size-4" />')
        ->toContain('role="switch"');
});

test('mobile image widget edit button opens a bottom sheet editor', function () {
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));
    $sheetContent = file_get_contents(resource_path('js/components/ui/sheet/SheetContent.vue'));

    expect($linkPage)
        ->toContain("widget.type === 'image'")
        ->toContain('openMobileImageEditor(widget)')
        ->toContain('mobileImageEditorWidget')
        ->toContain('setMobileImageEditorOpen')
        ->toContain('メディアを編集')
        ->toContain('mobileImageEditorPreviewStyle')
        ->toContain('mobileImageEditorCropStyle')
        ->toContain("height: '250px'")
        ->toContain("width: '250px'")
        ->toContain('メディア')
        ->not->toContain('画像を編集')
        ->toContain('chooseMobileImage')
        ->toContain('updateMobileImage')
        ->toContain('updateMobileImageCaption')
        ->toContain('updateMobileImageLink')
        ->toContain('updateMobileImageSensitive')
        ->toContain('isMobileImageCropping')
        ->toContain('startMobileImageCrop')
        ->toContain('dragMobileImageCrop')
        ->toContain('stopMobileImageCrop')
        ->toContain('event.preventDefault()')
        ->toContain('event.stopPropagation()')
        ->toContain('touch-none')
        ->toContain('bg-black text-white')
        ->toContain('bg-white text-black')
        ->toContain('クロップを調整')
        ->toContain('キャプション')
        ->toContain('リンク')
        ->toContain(':disabled="!mobileImageEditorWidget.content"')
        ->not->toContain('removeMobileImage');

    expect($sheetContent)
        ->toContain('overlayClass')
        ->toContain('absolute top-4 right-4 z-30')
        ->toContain('<SheetOverlay :class="overlayClass" />');
});

test('mobile profile avatar delete control is always visible and delete controls are red', function () {
    $profile = file_get_contents(resource_path('js/components/links/LinkProfile.vue'));
    $controls = file_get_contents(resource_path('js/components/links/LinkWidgetControls.vue'));
    $content = file_get_contents(resource_path('js/components/links/LinkWidgetContent.vue'));

    expect($profile)
        ->toContain("previewMode === 'mobile'")
        ->toContain('max-[1024px]:opacity-100')
        ->toContain('max-[1024px]:[&_*]:pointer-events-auto')
        ->toContain('group-hover:opacity-100');

    expect($controls)
        ->toContain('type="button"')
        ->toContain('@pointerdown.stop')
        ->toContain('bg-red-600')
        ->toContain('hover:bg-red-700')
        ->toContain('<Trash2 class="size-4" />');

    expect($content)
        ->toContain('bg-red-600 text-white')
        ->toContain('hover:bg-red-700');
});

test('mobile text and section widgets open bottom sheet editors', function () {
    $linkPage = file_get_contents(resource_path('js/pages/Link.vue'));
    $sheetHeader = file_get_contents(resource_path('js/components/ui/sheet/SheetHeader.vue'));

    expect($sheetHeader)
        ->toContain('sticky top-0 z-10');

    expect($linkPage)
        ->toContain("widget.type === 'text'")
        ->toContain('if (isSmallViewport.value)')
        ->toContain('activeWidgetId.value = widget.id')
        ->toContain('commitMobileAddedWidget')
        ->toContain("openMobileTextEditor(newWidget, 'add')")
        ->toContain('openMobileTextEditor(widget)')
        ->toContain('mobileTextEditorMode')
        ->toContain('completeMobileTextEditor')
        ->toContain('mobileTextEditorWidget')
        ->toContain('setMobileTextEditorOpen')
        ->toContain('updateMobileTextContent')
        ->toContain('mobileTextEditorInput')
        ->toContain('syncMobileTextEditor')
        ->toContain('isMobileTextEditorFocused')
        ->toContain('mobileTextEditorInputClasses')
        ->toContain('updateMobileTextBackgroundColorInput')
        ->toContain('updateMobileTextLink')
        ->toContain('updateMobileTextSensitive')
        ->toContain('mobileTextEditorPreviewStyle')
        ->toContain('mobileTextEditorBoxClasses')
        ->toContain('normalizedMobileTextBackgroundColor')
        ->toContain('mobileTextColorSwatches')
        ->toContain("height: '250px'")
        ->toContain("width: '250px'")
        ->toContain('const previewCellSize = 125')
        ->toContain('height: `${height * previewCellSize}px`')
        ->toContain('width: `${width * previewCellSize}px`')
        ->toContain('contenteditable="true"')
        ->toContain('role="textbox"')
        ->toContain('data-placeholder="テキストを入力"')
        ->toContain('mobile-text-editor')
        ->toContain('mobile-text-editor.is-empty::before')
        ->toContain('content: attr(data-placeholder)')
        ->not->toContain('v-text="')
        ->toContain('テキストを編集')
        ->toContain('テキストを追加')
        ->toContain('@click="closeMobileTextEditor"')
        ->toContain("v-if=\"mobileTextEditorMode === 'add'\"")
        ->toContain('{{')
        ->toContain("mobileTextEditorMode === 'add' ? '追加' : '完了'")
        ->toContain('}}')
        ->toContain('テキストを入力')
        ->toContain('スタイル')
        ->toContain('flex gap-2 overflow-x-auto')
        ->toContain('flex size-10 shrink-0')
        ->toContain('items-center text-center')
        ->toContain('justify-center')
        ->toContain('背景色')
        ->toContain('class="h-10 w-[90px] shrink-0')
        ->toContain('aria-label="カラーコード"')
        ->toContain('URL')
        ->toContain(':disabled="!mobileTextEditorWidget.content"')
        ->toContain('updateTextWidgetAlign')
        ->toContain('updateTextWidgetVerticalAlign')
        ->toContain('updateTextWidgetBackgroundColor')
        ->toContain('AlignVerticalJustifyCenter')
        ->toContain("widget.type === 'section'")
        ->toContain("openMobileSectionEditor(newWidget, 'add')")
        ->toContain('openMobileSectionEditor(widget)')
        ->toContain('mobileSectionEditorMode')
        ->toContain('mobileSectionEditorError')
        ->toContain('completeMobileSectionEditor')
        ->toContain('mobileSectionEditorWidget')
        ->toContain('setMobileSectionEditorOpen')
        ->toContain('updateMobileSectionTitle')
        ->toContain('セクションを編集')
        ->toContain('セクションを追加')
        ->toContain('@click="closeMobileSectionEditor"')
        ->toContain('セクションを入力してください')
        ->toContain("v-if=\"mobileSectionEditorMode === 'add'\"")
        ->toContain('{{')
        ->toContain("mobileSectionEditorMode === 'add'")
        ->toContain('? \'追加\'')
        ->toContain(": '完了'")
        ->toContain('}}')
        ->toContain('セクションを入力');
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
    $title = Title::query()->create([
        'name' => 'テスト職業',
        'sort_order' => 1,
        'is_active' => true,
    ]);
    Link::query()->create([
        'user_id' => $user->id,
        'slug' => 'maessun',
        'display_name' => 'Maessun',
        'title_id' => $title->id,
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
        ->where('links.0.title.name', 'テスト職業')
    );
});

test('link creation allows an empty title and bio', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('links.store'), [
        'slug' => 'maessun',
        'display_name' => 'Maessun',
        'bio' => '',
    ]);

    $response->assertRedirect(route('links.show', 'maessun'));
    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('links', [
        'slug' => 'maessun',
        'title_id' => null,
        'bio' => null,
    ]);
});

test('link creation stores the selected title', function () {
    $user = User::factory()->create();
    $title = Title::query()->create([
        'name' => '配信者',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->post(route('links.store'), [
        'slug' => 'maessun',
        'display_name' => 'Maessun',
        'title_id' => $title->id,
        'bio' => 'GridLink profile',
    ]);

    $response->assertRedirect(route('links.show', 'maessun'));
    $this->assertDatabaseHas('links', [
        'slug' => 'maessun',
        'title_id' => $title->id,
    ]);
});

test('link creation modals expose title selection', function () {
    $linksIndexPage = file_get_contents(resource_path('js/pages/Links/Index.vue'));
    $dashboardPage = file_get_contents(resource_path('js/pages/Dashboard.vue'));

    expect($linksIndexPage)
        ->toContain('titleOptions')
        ->toContain('v-model="form.title_id"')
        ->toContain('職業を選択しない')
        ->toContain('form.errors.title_id');

    expect($dashboardPage)
        ->toContain('titleOptions')
        ->toContain('v-model="form.title_id"')
        ->toContain('職業を選択しない')
        ->toContain('form.errors.title_id');
});

test('admin can manage title master records', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::query()->create(['name' => 'admin']));

    $response = $this->actingAs($admin)->get(route('admin.titles.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/titles/Index')
    );

    $this->actingAs($admin)->post(route('admin.titles.store'), [
        'name' => '漫画家',
        'sort_order' => 30,
        'is_active' => true,
    ])->assertRedirect();

    $title = Title::query()->where('name', '漫画家')->firstOrFail();

    $this->actingAs($admin)->put(route('admin.titles.update', $title), [
        'name' => 'コミック作家',
        'sort_order' => 31,
        'is_active' => false,
    ])->assertRedirect();

    $this->assertDatabaseHas('titles', [
        'id' => $title->id,
        'name' => 'コミック作家',
        'sort_order' => 31,
        'is_active' => false,
    ]);
});
