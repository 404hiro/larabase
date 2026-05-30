<?php

use App\Models\Link;
use App\Models\Message;
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
        ->component('links/Link')
        ->where('link.slug', 'maessun')
        ->where('link.display_name', 'Maessun')
    );
});

test('empty link add controls fit the responsive widget area', function () {
    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));

    expect($linkPage)
        ->toContain('max-w-[374px]')
        ->toContain('min-[1025px]:px-0')
        ->toContain('min-[1025px]:max-w-[1198px] min-[1025px]:flex-row')
        ->toContain('min-[1025px]:justify-between')
        ->toContain('const isPreviewLayoutSwitching = ref(false)')
        ->toContain('const disableLayoutTransitionsBriefly = () => {')
        ->toContain('watch([activePreviewMode, isSmallViewport], () => {')
        ->toContain("isPreviewLayoutSwitching ? 'link-page--instant-layout' : ''")
        ->toContain('.link-page--instant-layout .vgl-item')
        ->toContain('transition-duration: 0ms !important')
        ->toContain("activePreviewMode === 'desktop'\n                            ? 'pb-24 min-[1025px]:mx-0 min-[1025px]:w-[736px] min-[1025px]:max-w-none min-[1025px]:shrink-0'\n                            : 'mt-2 pb-24'")
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
        ->toContain('const addMapWidget = (position: WidgetPosition | null = null)')
        ->toContain("type: 'map'")
        ->toContain('const w = position ? position.w : (pendingWidgetSize.value?.w ?? 2)')
        ->toContain('const h = position ? position.h : (pendingWidgetSize.value?.h ?? 4)')
        ->toContain("widget.type !== 'link'")
        ->toContain("option.key !== 'inline'")
        ->toContain("address: '東京都港区芝公園4丁目2-8'")
        ->toContain('lat: 35.6585805')
        ->toContain('lng: 139.7454329')
        ->toContain('@add-map="addMapWidget"')
        ->toContain('const addMediaWidget = async (')
        ->toContain('ref="emptyMediaInput"');
});

test('map widgets use leaflet and replace the toolbar message shortcut', function () {
    $toolbar = file_get_contents(resource_path('js/components/links/LinkToolbar.vue'));
    $content = file_get_contents(resource_path('js/components/links/LinkWidgetContent.vue'));
    $controls = file_get_contents(resource_path('js/components/links/LinkWidgetControls.vue'));
    $css = file_get_contents(resource_path('css/app.css'));

    expect($toolbar)
        ->toContain('MapPinned')
        ->toContain('addMap: []')
        ->toContain("emit('addMap')")
        ->toContain('マップ')
        ->not->toContain('MessageCircleHeart')
        ->not->toContain('ファンレター');

    expect($content)
        ->toContain("await import('leaflet')")
        ->toContain("widget.type === 'map'")
        ->toContain('L.map(mapRef.value')
        ->toContain("'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png'")
        ->toContain('&copy; OpenStreetMap contributors &copy; CARTO')
        ->toContain('grid-link-map-marker')
        ->toContain('grid-link-map-marker__pulse')
        ->toContain('grid-link-map-marker__dot')
        ->toContain('grid-link-map-center-marker')
        ->toContain('props.isMapMoving')
        ->toContain('leafletMap.value.setZoom(mapZoom.value, { animate: false })')
        ->toContain('updateMapCenterFromLeaflet')
        ->toContain("'update-map-center'")
        ->toContain('const recenterMap = () => {')
        ->toContain('window.requestAnimationFrame')
        ->toContain('window.setTimeout(applyCenter, 160)')
        ->toContain('invalidateSize({ pan: false })')
        ->toContain('animate: false')
        ->toContain('mapLayoutKey.value')
        ->toContain('const mapTitle = computed(() => {')
        ->toContain('rawTitle === undefined || rawTitle === null')
        ->toContain(':aria-label="mapAriaLabel"')
        ->toContain('grid-link-google-map')
        ->toContain('v-if="isEditing"')
        ->toContain(':value="mapTitle"')
        ->toContain('placeholder="Add a title..."')
        ->toContain('widget-text-input max-w-full cursor-text resize-none whitespace-pre-wrap break-words rounded-xl border border-white/60 bg-white/55 px-3 py-2 font-semibold text-gray-800 shadow-sm backdrop-blur-md')
        ->toContain('@input="updateTitle"')
        ->toContain('inline-block w-fit max-w-full whitespace-normal break-words rounded-xl border border-white/60 bg-white/55 px-3 py-2 font-semibold text-gray-800 shadow-sm backdrop-blur-md')
        ->toContain('東京タワー')
        ->not->toContain('{{ mapAddress }}');

    expect($controls)
        ->toContain('Search')
        ->toContain('Move')
        ->toContain('Minus')
        ->toContain('Plus')
        ->toContain('toggleMapMove')
        ->toContain('closeMapMove')
        ->toContain('updateMapZoom')
        ->toContain('updateMapLocation')
        ->toContain('showMapSearch')
        ->toContain('mapSearchQuery')
        ->toContain('https://nominatim.openstreetmap.org/search')
        ->toContain('ロケーションを検索')
        ->toContain('マップを移動')
        ->toContain('aria-label="縮小"')
        ->toContain('aria-label="拡大"')
        ->toContain('ロケーションを入力')
        ->toContain('top-[calc(100%+0.125rem)]')
        ->not->toContain('top-[calc(100%+0.25rem)]')
        ->not->toContain('top-[calc(100%+0.5rem)]')
        ->toContain('z-[3300]')
        ->toContain('w-72')
        ->toContain('type="text"')
        ->not->toContain('type="search"')
        ->toContain('text-sm text-white')
        ->toContain('h-10 w-full')
        ->toContain('selectMapLocation')
        ->toContain('候補が見つかりません')
        ->toContain('z-[3200]')
        ->toContain('gap-1.5 rounded-2xl bg-black/80 p-1.5')
        ->toContain('size-8 cursor-pointer items-center justify-center rounded-lg')
        ->not->toContain('size-9 cursor-pointer items-center justify-center rounded-lg')
        ->not->toContain('z-[140]');

    expect($css)
        ->toContain("@import 'leaflet/dist/leaflet.css'")
        ->toContain('.grid-link-map-marker__dot')
        ->toContain('.grid-link-map-marker__pulse')
        ->toContain('.grid-link-google-map .leaflet-tile')
        ->toContain('filter: saturate(1.18) contrast(0.96) brightness(1.05)')
        ->toContain('@keyframes grid-link-map-marker-pulse')
        ->toContain('background: #3b82f6')
        ->toContain('background: rgb(59 130 246 / 0.22)');
});

test('link and message pages hide the top profile navigation', function () {
    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));
    $messagePage = file_get_contents(resource_path('js/pages/links/Message.vue'));

    expect($linkPage)
        ->not->toContain('LinkPageNavigation')
        ->not->toContain('active-tab="profile"')
        ->toContain('pt-8')
        ->toContain('pt-12 min-[1025px]:px-0 sm:px-8');

    expect($messagePage)
        ->not->toContain('LinkPageNavigation')
        ->not->toContain('active-tab="message"')
        ->toContain('px-5 pt-5')
        ->toContain('absolute inset-x-0 top-0 h-28');
});

test('link page shows visitor floating share actions', function () {
    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));

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
        ->toContain('class="flex h-9 cursor-pointer items-center justify-center gap-2 rounded-full')
        ->toContain('@click="copyProfileUrl"')
        ->toContain('<Copy v-else class="size-4" />')
        ->toContain('class="flex size-9 cursor-pointer items-center justify-center rounded-full')
        ->toContain('<MoreHorizontal class="size-5" />')
        ->toContain('@select="reportUser"')
        ->toContain('このユーザーを通報する')
        ->not->toContain('fixed bottom-20 left-4')
        ->toContain('<footer')
        ->toContain('Built with GridLink');
});

test('message page route renders message mock page', function () {
    $user = User::factory()->create();
    $link = Link::query()->create([
        'user_id' => $user->id,
        'slug' => 'maessun',
        'display_name' => 'Maessun',
        'bio' => 'GridLink profile',
    ]);

    $response = $this->get(route('links.message', $link));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('links/Message')
        ->where('link.slug', 'maessun')
        ->where('link.display_name', 'Maessun')
    );
});

test('message page displays real messages', function () {
    $routeFile = file_get_contents(base_path('routes/web.php'));
    $controller = file_get_contents(app_path('Http/Controllers/LinkController.php'));
    $messagePage = file_get_contents(resource_path('js/pages/links/Message.vue'));

    expect($routeFile)
        ->toContain("Route::get('/@{link:slug}/message'")
        ->toContain("->name('links.message')");

    expect($controller)
        ->toContain('public function message(Request $request, Link $link): Response')
        ->toContain("Inertia::render('links/Message'");

    expect($messagePage)
        ->not->toContain('LinkPageNavigation')
        ->not->toContain('active-tab="message"')
        ->toContain(':href="linkShow.url(link.slug)"')
        ->toContain('プロフィールを見る')
        ->toContain('const isOwner = computed')
        ->toContain("const activeTab = ref<'write' | 'read'>('write')")
        ->toContain('書く')
        ->toContain('読む')
        ->toContain('メッセージを送る')
        ->toContain('届いたメッセージ')
        ->toContain('supportOptions')
        ->toContain('v-if="isOwner"');
});

test('message page only displays messages that are public and read to visitors', function () {
    $owner = User::factory()->create();
    $link = Link::factory()->create(['user_id' => $owner->id]);

    Message::factory()->create([
        'link_id' => $link->id,
        'body' => 'Visible message',
        'is_public' => true,
        'is_read' => true,
        'read_at' => now(),
    ]);
    Message::factory()->create([
        'link_id' => $link->id,
        'body' => 'Unread public message',
        'is_public' => true,
        'is_read' => false,
    ]);
    Message::factory()->create([
        'link_id' => $link->id,
        'body' => 'Read private message',
        'is_public' => false,
        'is_read' => true,
        'read_at' => now(),
    ]);

    $this->get(route('links.message', $link))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('links/Message')
            ->has('messages', 1)
            ->where('messages.0.body', 'Visible message')
        );
});

test('owner viewing the message page marks messages as read', function () {
    $owner = User::factory()->create();
    $link = Link::factory()->create(['user_id' => $owner->id]);
    $message = Message::factory()->create([
        'link_id' => $link->id,
        'is_public' => true,
        'is_read' => false,
        'read_at' => null,
    ]);

    $this->actingAs($owner)
        ->get(route('links.message', $link))
        ->assertSuccessful();

    expect($message->fresh())
        ->is_read->toBeTrue()
        ->read_at->not->toBeNull();
});

test('link toolbar exposes media upload controls', function () {
    $toolbar = file_get_contents(resource_path('js/components/links/LinkToolbar.vue'));
    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));

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
    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));

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

test('link toolbar exposes map button after text controls', function () {
    $toolbar = file_get_contents(resource_path('js/components/links/LinkToolbar.vue'));
    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));
    $textPosition = strpos($toolbar, '<span class="font-semibold">テキスト</span>');
    $mapPosition = strpos($toolbar, '<span class="font-semibold">マップ</span>');
    $sectionPosition = strpos($toolbar, '<span class="font-semibold">セクション</span>');

    expect($toolbar)
        ->toContain('MapPinned')
        ->toContain('addMap: []')
        ->toContain("emit('addMap')")
        ->toContain('マップ')
        ->toContain('aria-label="マップを追加"');

    expect($textPosition)->toBeLessThan($mapPosition);
    expect($mapPosition)->toBeLessThan($sectionPosition);

    expect($linkPage)
        ->toContain('@add-map="addMapWidget"')
        ->toContain(':letter-url="`/@${props.link.slug}/message`"');
});

test('private link pages show a publish button', function () {
    $profile = file_get_contents(resource_path('js/components/links/LinkProfile.vue'));
    $toolbar = file_get_contents(resource_path('js/components/links/LinkToolbar.vue'));
    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));
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
        ->toContain('bg-black text-white hover:bg-neutral-800')
        ->toContain('bg-black px-3 text-sm font-bold text-white')
        ->not->toContain('bg-emerald-500')
        ->not->toContain('hover:bg-emerald-600')
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
        ->toContain('has_web_display: !isSmallViewport.value')
        ->toContain('const hasWebDisplay = computed(() => Boolean(props.link.has_web_display))')
        ->toContain('const shouldForceMobileForVisitor = computed')
        ->toContain('const activePreviewMode = computed')
        ->toContain('const shouldShowDesktopGrid = computed')
        ->toContain('const shouldShowMobileGrid = computed')
        ->toContain('v-if="shouldShowDesktopGrid"')
        ->toContain('v-if="shouldShowMobileGrid"')
        ->toContain('burstPublishConfetti')
        ->toContain('publish-confetti-piece');

    expect($request)
        ->toContain("'is_published' => ['nullable', 'boolean']")
        ->toContain("'has_web_display' => ['nullable', 'boolean']");

    expect($controller)
        ->toContain("\$linkData['is_published']")
        ->toContain("\$request->boolean('is_published')")
        ->toContain("\$request->boolean('has_web_display')")
        ->toContain("\$linkData['has_web_display'] = true");
});

test('link profile shows a message link button under the bio', function () {
    $profile = file_get_contents(resource_path('js/components/links/LinkProfile.vue'));
    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));
    $request = file_get_contents(app_path('Http/Requests/Links/UpdateLinkRequest.php'));
    $controller = file_get_contents(app_path('Http/Controllers/LinkController.php'));
    $model = file_get_contents(app_path('Models/Link.php'));
    $dropContactsMigration = file_get_contents(database_path('migrations/2026_05_14_000001_drop_link_contacts_table.php'));

    expect($profile)
        ->toContain("import { Link as InertiaLink } from '@inertiajs/vue3'")
        ->toContain('MessageCircleHeart')
        ->toContain('letterUrl: string')
        ->toContain('v-if="isEditing"')
        ->toContain('disabled')
        ->toContain('cursor-not-allowed')
        ->toContain(':href="letterUrl"')
        ->toContain('grid h-12 w-full max-w-[374px]')
        ->toContain('rounded-full border border-gray-300 bg-white')
        ->toContain('rounded-full bg-white text-black')
        ->toContain('grid-cols-[40px_1fr_40px]')
        ->toContain('メッセージ')
        ->toContain('<MessageCircleHeart class="size-5" />')
        ->not->toContain('contactSlots')
        ->not->toContain('isContactModalOpen')
        ->not->toContain('linkServicesConfig')
        ->not->toContain('URLまたはメールアドレスを入力してください。');

    expect($linkPage)
        ->toContain(':letter-url="`/@${props.link.slug}/message`"')
        ->not->toContain('contacts?: LinkContact[]')
        ->not->toContain('contacts: serializeContacts(),')
        ->not->toContain('v-model:contacts="editForm.contacts"');

    expect($request)
        ->not->toContain("'contacts' => ['nullable', 'array', 'max:4']")
        ->not->toContain('FILTER_VALIDATE_EMAIL');

    expect($controller)
        ->toContain("\$link->load(['widgets', 'title'])")
        ->not->toContain("\$link->load(['contacts', 'widgets', 'title'])")
        ->not->toContain('$link->contacts()->delete()');

    expect($model)
        ->not->toContain('public function contacts(): HasMany')
        ->not->toContain('LinkContact::class');

    expect($dropContactsMigration)
        ->toContain("Schema::dropIfExists('link_contacts')");
});

test('link profile uses responsive avatar sizing and fixed name and bio sizing', function () {
    $profile = file_get_contents(resource_path('js/components/links/LinkProfile.vue'));

    expect($profile)
        ->toContain('size-[120px]')
        ->toContain('min-[1025px]:size-[184px]')
        ->toContain('text-[30px] leading-tight font-bold tracking-tight')
        ->toContain("pageTheme === 'dark'")
        ->toContain('border-white/15 bg-white/10 text-white')
        ->toContain('focus:bg-white/15')
        ->toContain("pageTheme === 'dark'\n                            ? 'border-white/15 bg-white/10 text-white")
        ->toContain('border-gray-200 bg-gray-100/70 text-gray-700')
        ->toContain('text-[16px]')
        ->not->toContain('min-[1025px]:text-[20px]');
});

test('link web display flag defaults false and only turns on', function () {
    $user = User::factory()->create();
    $link = Link::factory()->create([
        'user_id' => $user->id,
        'slug' => 'maessun',
        'has_web_display' => false,
    ]);

    expect($link->fresh()->has_web_display)->toBeFalse();

    $this->actingAs($user)->put(route('links.update', $link), [
        'display_name' => 'Maessun',
        'bio' => 'Updated bio',
        'has_web_display' => true,
    ])->assertRedirect();

    expect($link->fresh()->has_web_display)->toBeTrue();

    $this->actingAs($user)->put(route('links.update', $link), [
        'display_name' => 'Maessun',
        'bio' => 'Updated bio',
        'has_web_display' => false,
    ])->assertRedirect();

    // Verification: it should STAY true once turned on (as per the test requirement)
    expect($link->fresh()->has_web_display)->toBeTrue();
});

test('link page style settings are saved to theme config', function () {
    $user = User::factory()->create();
    $link = Link::factory()->create([
        'user_id' => $user->id,
        'slug' => 'styled-link',
        'theme_config' => [
            'theme' => 'light',
            'widget_style' => 'rounded',
        ],
    ]);

    $this->actingAs($user)->put(route('links.update', $link), [
        'display_name' => 'Styled Link',
        'bio' => 'Updated bio',
        'theme_config' => [
            'theme' => 'dark',
            'widget_style' => 'sharp',
        ],
    ])->assertRedirect();

    expect($link->fresh()->theme_config)->toMatchArray([
        'theme' => 'dark',
        'widget_style' => 'sharp',
    ]);

    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));
    $toolbar = file_get_contents(resource_path('js/components/links/LinkToolbar.vue'));
    $content = file_get_contents(resource_path('js/components/links/LinkWidgetContent.vue'));
    $profile = file_get_contents(resource_path('js/components/links/LinkProfile.vue'));
    $request = file_get_contents(app_path('Http/Requests/Links/UpdateLinkRequest.php'));
    $controller = file_get_contents(app_path('Http/Controllers/LinkController.php'));

    expect($linkPage)
        ->toContain('props.link.theme_config?.theme')
        ->toContain('props.link.theme_config?.widget_style')
        ->toContain('const LINK_PAGE_THEME_BACKGROUNDS = {')
        ->toContain("dark: '#111111'")
        ->toContain('const applyDocumentBackgroundColor = () => {')
        ->toContain('document.documentElement.style.backgroundColor = pageBackgroundColor.value')
        ->toContain('document.body.style.backgroundColor = pageBackgroundColor.value')
        ->toContain('watch(pageBackgroundColor, () => {')
        ->toContain('const pageTheme = ref')
        ->toContain('const widgetStyle = ref')
        ->toContain('theme_config: {')
        ->toContain('theme: pageTheme.value')
        ->toContain('widget_style: widgetStyle.value')
        ->toContain(':page-theme="pageTheme"')
        ->toContain(':widget-style="widgetStyle"')
        ->toContain('@update:page-theme="pageTheme = $event"')
        ->toContain('@update:widget-style="widgetStyle = $event"')
        ->toContain(':widget-corner-class=')
        ->toContain('pageThemeClasses')
        ->toContain('widgetCornerClass');

    expect($toolbar)
        ->toContain('Palette')
        ->not->toContain('Settings')
        ->toContain('スタイル変更')
        ->toContain('w-[min(calc(100vw-2rem),400px)]')
        ->toContain('text-base font-black')
        ->toContain('text-xs font-semibold')
        ->toContain('テーマ')
        ->toContain('ライト')
        ->toContain('ダーク')
        ->toContain('ウィジェットスタイル')
        ->toContain('シェイプ')
        ->toContain('ソフト')
        ->toContain('角丸')
        ->toContain("{ value: 'sharp', label: 'シェイプ', class: 'rounded-none' }")
        ->toContain("{ value: 'soft', label: 'ソフト', class: 'rounded-md' }")
        ->toContain("{ value: 'rounded', label: '角丸', class: 'rounded-full' }")
        ->toContain("'update:pageTheme'")
        ->toContain("'update:widgetStyle'")
        ->toContain('showStylePanel')
        ->toContain('stylePanelRef')
        ->toContain('data-style-toggle-button')
        ->toContain("targetElement?.closest('[data-style-toggle-button]')")
        ->not->toContain('styleButtonRef')
        ->toContain('closeStylePanelOnOutsideClick')
        ->toContain("window.addEventListener('pointerdown', closeStylePanelOnOutsideClick)")
        ->toContain("window.removeEventListener('pointerdown', closeStylePanelOnOutsideClick)");

    expect($content)
        ->toContain('widgetCornerClass?: string')
        ->toContain("pageTheme?: 'light' | 'dark'")
        ->toContain("props.widgetCornerClass ?? 'rounded-2xl'")
        ->toContain(':class="[cardFrameClasses, cardClipClasses, widgetCornerClass]"')
        ->toContain("props.widget.type === 'section' && !props.isEditing")
        ->toContain('border border-gray-200 bg-gray-100/70')
        ->toContain('border border-white/15 bg-white/10')
        ->toContain('text-white placeholder:text-white/45 hover:bg-white/10 focus:border-white/20 focus:bg-white/10')
        ->toContain('text-gray-950 placeholder:text-gray-400 hover:bg-gray-100 focus:border-gray-200 focus:bg-white')
        ->toContain("pageTheme === 'dark' ? 'text-white' : 'text-gray-800'");

    expect($profile)
        ->toContain("pageTheme?: 'light' | 'dark'")
        ->toContain("pageTheme === 'dark'");

    expect($request)
        ->toContain("'theme_config' => ['nullable', 'array']")
        ->toContain("'theme_config.theme' => ['nullable', Rule::in(['light', 'dark'])]")
        ->toContain("'theme_config.widget_style' => ['nullable', Rule::in(['sharp', 'soft', 'rounded'])]");

    expect($controller)
        ->toContain('$linkData[\'theme_config\']')
        ->toContain("'widget_style'");
});

test('text widgets support alignment controls and background colors', function () {
    $controls = file_get_contents(resource_path('js/components/links/LinkWidgetControls.vue'));
    $content = file_get_contents(resource_path('js/components/links/LinkWidgetContent.vue'));
    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));

    expect($controls)
        ->toContain("widget.type === 'text'")
        ->toContain('updateBackgroundColor')
        ->toContain('updateTextAlign')
        ->toContain('updateVerticalAlign')
        ->toContain('AlignVerticalJustifyStart')
        ->toContain('AlignVerticalJustifyEnd')
        ->toContain('bottom-[-5rem]')
        ->toContain('flex h-11 translate-x-1/2')
        ->toContain("mode === 'desktop' ? 'bottom-1' : 'bottom-[-4.25rem]'")
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
    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));

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
    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));
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

test('map widget controls add a leaflet map without the old modal flow', function () {
    $toolbar = file_get_contents(resource_path('js/components/links/LinkToolbar.vue'));
    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));
    $content = file_get_contents(resource_path('js/components/links/LinkWidgetContent.vue'));

    expect($toolbar)
        ->toContain('addMap')
        ->toContain('マップ');

    expect($linkPage)
        ->not->toContain('showAddMapModal')
        ->toContain("type: 'map'")
        ->toContain('temp_map_')
        ->toContain('const updateMapWidgetLocation = (')
        ->toContain('const mobileMapEditorWidget = ref')
        ->toContain('const openMobileMapEditor = (widget: any) => {')
        ->toContain('const mobileMapEditorPreviewStyle = computed(() => {')
        ->toContain('const shouldShowMobileMapLocationDropdown = computed(() => {')
        ->toContain('const updateMobileMapSearchQuery = (event: Event) => {')
        ->toContain('const selectMobileMapLocation = (result: {')
        ->toContain('const updateMobileMapTitle = (event: Event) => {')
        ->toContain('const updateMobileMapZoom = (delta: number) => {')
        ->toContain('マップを編集')
        ->toContain('mobileMapSearchQuery')
        ->toContain('ロケーション')
        ->toContain('placeholder="ロケーションを入力"')
        ->toContain('absolute top-[calc(100%+0.5rem)] right-0 left-0 z-[500] max-h-56 overflow-y-auto')
        ->toContain('マップ')
        ->toContain('mobileMapEditorPreviewStyle')
        ->toContain('bottom-3 left-3 z-[410]')
        ->toContain('updateMobileMapZoom(-1)')
        ->toContain('updateMobileMapZoom(1)')
        ->toContain('タイトル')
        ->toContain('placeholder="タイトルを入力"')
        ->toContain(':is-map-moving="true"')
        ->toContain('@update-map-location=')
        ->toContain('widget.settings.address = location.address')
        ->toContain('widget.settings.lat = location.lat')
        ->toContain('widget.settings.lng = location.lng')
        ->toContain('const activeMapMovingWidgetId = ref')
        ->toContain('const toggleMapMove = (')
        ->toContain('const closeMapMove = () => {')
        ->toContain('const updateMapWidgetCenter = (')
        ->toContain('const updateMapWidgetZoom = (')
        ->toContain('@toggle-map-move=')
        ->toContain('@update-map-center=')
        ->toContain('@update-map-zoom=')
        ->toContain('fixed inset-0 z-[2000] bg-white/70')
        ->toContain('@click="closeMapMove"')
        ->toContain('@close-map-move="closeMapMove"')
        ->toContain('!activeMapMovingWidgetId')
        ->toContain('!z-[3000]')
        ->not->toContain('hover:scale-[1.015]')
        ->toContain('z-[1000]')
        ->toContain('z-[1100]')
        ->toContain('z-[4000] cursor-grab')
        ->not->toContain('openMapModalWithPos');

    expect($content)
        ->toContain("widget.type === 'map'")
        ->toContain("await import('leaflet')")
        ->not->toContain('staticmap.openstreetmap.de');
});

test('clicking outside widgets clears active crop mode', function () {
    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));

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

test('github link widgets show commit glass instead of og images', function () {
    $content = file_get_contents(resource_path('js/components/links/LinkWidgetContent.vue'));

    expect($content)
        ->toContain("host !== 'github.com'")
        ->toContain('const githubProfile = computed(() => {')
        ->toContain('handle: `@${username}`')
        ->toContain('const githubCommitCells = computed(() => {')
        ->toContain('const githubCommitCellClasses = (level: number) => [')
        ->toContain('const githubCommitGlassClasses = computed(() => [')
        ->toContain('v-else-if="githubProfile"')
        ->toContain('v-for="(level, index) in githubCommitCells"')
        ->toContain(':class="githubCommitCellClasses(level)"')
        ->toContain(':class="githubCommitGlassClasses"')
        ->toContain('bg-[#F7F7F8]')
        ->toContain('bg-[#216E39]')
        ->not->toContain('{{ githubProfile.handle }}')
        ->not->toContain('<Github class="size-6" />');
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
        ->toContain('class="cursor-pointer group relative flex-1 overflow-hidden"')
        ->toContain('class="cursor-pointer group relative w-full shrink-0 overflow-hidden"')
        ->toContain(':class="widgetCornerClass"')
        ->not->toContain('rounded-2xl bg-gray-100');
});

test('image widgets show an editable hover title field', function () {
    $content = file_get_contents(resource_path('js/components/links/LinkWidgetContent.vue'));

    expect($content)
        ->toContain('placeholder="Add a title..."')
        ->toContain('group-hover:opacity-100')
        ->toContain('border border-white/60 bg-white/55')
        ->toContain('shadow-sm backdrop-blur-md')
        ->toContain('w-fit max-w-full whitespace-normal break-words')
        ->toContain('@input="updateTitle"')
        ->toContain('v-if="isEditing"');
});

test('image widgets expose link and crop controls', function () {
    $controls = file_get_contents(resource_path('js/components/links/LinkWidgetControls.vue'));
    $content = file_get_contents(resource_path('js/components/links/LinkWidgetContent.vue'));
    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));

    expect($controls)
        ->toContain("widget.type === 'image'")
        ->toContain("emit('editLink')")
        ->toContain("emit('toggleCrop')")
        ->toContain('トリミング')
        ->toContain('完了');

    expect($content)
        ->toContain('objectPosition')
        ->toContain("emit('update-crop'")
        ->toContain('ref="imageRef"');

    expect($linkPage)
        ->toContain('bg-gray-200/75')
        ->toContain('画像リンクを設定')
        ->toContain('updateWidgetLink(item.widget)')
        ->toContain('toggleImageCrop(item.i)')
        ->toContain('updateImageWidgetCrop(')
        ->toContain('item.widget');
});

test('grid resize handles are hidden because resizing is controlled by the widget toolbar', function () {
    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));

    expect($linkPage)
        ->toContain(':is-resizable="false"')
        ->toContain('@resize=')
        ->toContain('resizeWidget(');
});

test('mobile widget editing uses tap operation controls and a bottom resize toolbar', function () {
    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));
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
        ->toContain('border-2 border-black')
        ->toContain('z-[4000] cursor-grab')
        ->toContain('absolute inset-0 z-[700]')
        ->toContain('flex size-10 translate-x-1/2')
        ->toContain('cursor-grab touch-none')
        ->toContain('<Move class="size-5" />')
        ->toContain('<Pencil class="size-4" />')
        ->toContain('@resize-mobile-widget="resizeActiveMobileWidget"')
        ->toContain('@complete-mobile-widget-operation="completeMobileWidgetOperation"');

    expect($toolbar)
        ->toContain('mobileWidgetOperationActive?: boolean')
        ->toContain('fixed bottom-6 left-1/2 z-[9005]')
        ->toContain('mobileSizeOptions?: any[]')
        ->toContain('activeMobileWidget?: any | null')
        ->toContain('resizeMobileWidget')
        ->toContain('completeMobileWidgetOperation')
        ->toContain('v-if="!mobileWidgetOperationActive"')
        ->toContain('<Palette class="size-5" stroke-width="2.2" />')
        ->toContain('aria-label="スタイル変更"')
        ->toContain("'min-w-16 gap-1.5 bg-black px-3 text-sm font-bold text-white")
        ->toContain('<span v-if="isEditing">保存</span>')
        ->toContain('hidden h-11 items-center gap-1.5 rounded-2xl')
        ->toContain('class="flex size-8 cursor-pointer items-center justify-center')
        ->toContain('rounded-lg transition-colors"')
        ->toContain('class="flex h-8 min-w-12 cursor-pointer items-center justify-center rounded-lg bg-white px-4 text-xs font-bold whitespace-nowrap text-black transition-transform active:scale-95"')
        ->toContain('v-for="option in mobileSizeOptions"')
        ->toContain("emit('resizeMobileWidget', option.size)")
        ->toContain("emit('completeMobileWidgetOperation')")
        ->toContain('@click.stop')
        ->toContain('完了');

    expect(substr_count($linkPage, ':drag-allow-from='))
        ->toBe(1);
});

test('mobile widget move handle allows grid drag events to bubble', function () {
    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));
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
    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));

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
    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));
    $widgetContent = file_get_contents(resource_path('js/components/links/LinkWidgetContent.vue'));

    expect($linkPage)
        ->toContain("import { usePreferredReducedMotion, useWindowSize } from '@vueuse/core';")
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
    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));

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
    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));

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
    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));
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
        ->toContain("shape.value === '1x1'\n          ? 'h-[48px]'")
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
    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));

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
    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));

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
    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));
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
        ->toContain('initMobileCropper')
        ->toContain('destroyMobileCropper')
        ->toContain('new Cropper')
        ->toContain('dragMode: \'move\'')
        ->toContain('crop()')
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
    $linkPage = file_get_contents(resource_path('js/pages/links/Link.vue'));
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
        ->toContain('const RectangleHalfThin = (props: any) =>')
        ->toContain("key: 'section-compact'")
        ->toContain('icon: RectangleHalfThin')
        ->toContain("key: 'section-wide'")
        ->toContain('icon: RectangleThin')
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

test('link creation allows an empty title and bio', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('links.store'), [
        'slug' => 'maessun',
        'display_name' => 'Maessun',
        'bio' => '',
    ]);

    $link = Link::where('slug', 'maessun')->firstOrFail();
    $response->assertRedirect(route('links.show', ['link' => $link->slug, 'edit' => 1]));
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

    $link = Link::where('slug', 'maessun')->firstOrFail();
    $response->assertRedirect(route('links.show', ['link' => $link->slug, 'edit' => 1]));
    $this->assertDatabaseHas('links', [
        'slug' => 'maessun',
        'title_id' => $title->id,
    ]);
});

test('link creation walkthrough exposes title selection', function () {
    $walkthroughPage = file_get_contents(resource_path('js/pages/walkthrough/Index.vue'));

    expect($walkthroughPage)
        ->toContain('titleOptions')
        ->toContain('v-model="form.title_id"')
        ->toContain('肩書きを選択')
        ->toContain('form.errors.title_id');
});

test('walkthrough creation opens edit mode with profile values ready to edit', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('walkthrough.store'), [
        'slug' => 'walkthrough-profile',
        'display_name' => 'Walkthrough Name',
        'bio' => 'Walkthrough bio',
    ]);

    $link = Link::where('slug', 'walkthrough-profile')->firstOrFail();

    $response->assertRedirect(route('links.show', ['link' => $link->slug, 'edit' => 1]));
    $this->assertDatabaseHas('links', [
        'slug' => 'walkthrough-profile',
        'display_name' => 'Walkthrough Name',
        'bio' => 'Walkthrough bio',
    ]);

    $this->actingAs($user)
        ->get(route('links.show', ['link' => $link->slug, 'edit' => 1]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('links/Link')
            ->where('is_editing', true)
            ->where('link.display_name', 'Walkthrough Name')
            ->where('link.bio', 'Walkthrough bio')
        );

    $profile = file_get_contents(resource_path('js/components/links/LinkProfile.vue'));

    expect($profile)
        ->toContain("import { nextTick, onMounted, ref, watch } from 'vue'")
        ->toContain('onMounted(() => {')
        ->toContain('nextTick(() => syncEditors())');
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
