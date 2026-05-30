<script setup lang="ts">
import { type LinkService, linkServicesConfig } from '@/lib/linkServices';
import { compressImage } from '@/utils/imageCompression';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';
import type { Map as LeafletMap } from 'leaflet';
import {
    ArrowRight,
    Image,
    Link as LinkIcon,
    Trash2,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps<{
    widget: any;
    mode: 'desktop' | 'mobile';
    widgetCornerClass?: string;
    pageTheme?: 'light' | 'dark';
    isEditing: boolean;
    isActive: boolean;
    isCropping?: boolean;
    isMapMoving?: boolean;
}>();

const MAX_LINK_TITLE_LENGTH = 100;
const MAX_TEXT_WIDGET_LENGTH = 4500;

const emit = defineEmits<{
    activate: [];
    'update-title': [newTitle: string];
    'update-crop': [crop: { x: number; y: number }];
    'update-map-center': [center: { lat: number; lng: number; zoom: number }];
    'upload-image': [file: File];
    'remove-image': [];
}>();

const updateTitle = (event: Event) => {
    const maxLength =
        props.widget.type === 'link'
            ? MAX_LINK_TITLE_LENGTH
            : props.widget.type === 'text'
              ? MAX_TEXT_WIDGET_LENGTH
              : MAX_TEXT_WIDGET_LENGTH;

    emit(
        'update-title',
        (event.target as HTMLTextAreaElement).value.slice(0, maxLength),
    );
};

const updateTitleFromElement = (element: HTMLElement | null) => {
    const maxLength =
        props.widget.type === 'link'
            ? MAX_LINK_TITLE_LENGTH
            : props.widget.type === 'text'
              ? MAX_TEXT_WIDGET_LENGTH
              : MAX_TEXT_WIDGET_LENGTH;

    emit('update-title', (element?.innerText ?? '').slice(0, maxLength));
};

const activate = () => {
    emit('activate');
};

const settings = computed(() => props.widget.settings || {});

const youtubeVideoId = computed(() => {
    if (props.widget.type !== 'link' || !href.value) return null;
    try {
        const url = new URL(href.value);
        const host = url.hostname.replace(/^www\./, '');
        const pathParts = url.pathname.split('/').filter(Boolean);

        if (host === 'youtu.be') {
            return pathParts[0] || null;
        }

        if (host === 'youtube.com' || host.endsWith('.youtube.com')) {
            if (pathParts[0] === 'watch') {
                return url.searchParams.get('v');
            }
            if (['embed', 'shorts', 'live'].includes(pathParts[0] ?? '')) {
                return pathParts[1] || null;
            }
        }
        return null;
    } catch (e) {
        return null;
    }
});

const vimeoVideoId = computed(() => {
    if (props.widget.type !== 'link' || !href.value) return null;
    try {
        const url = new URL(href.value);
        const host = url.hostname.replace(/^www\./, '');
        const pathParts = url.pathname.split('/').filter(Boolean);

        if (host === 'vimeo.com' && pathParts[0]) {
            return pathParts[0];
        }
        return null;
    } catch (e) {
        return null;
    }
});

const tiktokVideoId = computed(() => {
    if (props.widget.type !== 'link' || !href.value) return null;
    try {
        const url = new URL(href.value);
        const host = url.hostname.replace(/^www\./, '');
        const pathParts = url.pathname.split('/').filter(Boolean);

        // Standard: tiktok.com/@user/video/ID
        if (host === 'tiktok.com' && pathParts[1] === 'video' && pathParts[2]) {
            return pathParts[2];
        }
        return null;
    } catch (e) {
        return null;
    }
});

const musicEmbedInfo = computed(() => {
    if (props.widget.type !== 'link' || !href.value) return null;
    try {
        const url = new URL(href.value);
        const host = url.hostname.replace(/^www\./, '');

        // YouTube Music
        if (host === 'music.youtube.com') {
            const videoId = url.searchParams.get('v');
            if (videoId) {
                return {
                    service: 'youtube-music',
                    url: `https://www.youtube.com/embed/${videoId}`,
                };
            }
        }

        // YouTube
        if (youtubeVideoId.value) {
            return {
                service: 'youtube',
                url: `https://www.youtube.com/embed/${youtubeVideoId.value}`,
            };
        }

        // Vimeo
        if (vimeoVideoId.value) {
            return {
                service: 'vimeo',
                url: `https://player.vimeo.com/video/${vimeoVideoId.value}`,
            };
        }

        // TikTok
        if (tiktokVideoId.value) {
            return {
                service: 'tiktok',
                url: `https://www.tiktok.com/embed/v2/${tiktokVideoId.value}`,
            };
        }

        return null;
    } catch (e) {
        return null;
    }
});

const embedMode = computed(() => {
    if (!musicEmbedInfo.value || shape.value === 'inline') return 'link';
    return settings.value.youtubeMode || 'link';
});

const title = computed(() => {
    if (props.widget.type === 'section') {
        return props.widget.content || settings.value.title || '';
    }

    return settings.value.title || '';
});
const hasTitle = computed(() => String(title.value).trim().length > 0);

const href = computed(() => props.widget.content || '');
const image = computed(() => props.widget.thumbnail_url || '');
const bgColor = computed(() => settings.value.bgColor || '#FFFFFF');
const textAlign = computed(() => settings.value.textAlign || 'left');
const verticalAlign = computed(() => settings.value.verticalAlign || 'center');
const mapLat = computed(() => Number(settings.value.lat ?? 35.6585805));
const mapLng = computed(() => Number(settings.value.lng ?? 139.7454329));
const mapZoom = computed(() => Number(settings.value.zoom ?? 15));
const mapTitle = computed(() => {
    const rawTitle = settings.value.title;

    return rawTitle === undefined || rawTitle === null
        ? '東京タワー'
        : String(rawTitle);
});
const mapAriaLabel = computed(
    () => mapTitle.value.trim() || settings.value.address || 'マップ',
);
const mapLayoutKey = computed(() => {
    if (props.mode === 'desktop') {
        return `${props.widget.w}:${props.widget.h}`;
    }

    return `${props.widget.w_mobile}:${props.widget.h_mobile}`;
});

const domain = computed(() => {
    if (!href.value) return '';
    try {
        const url = new URL(href.value);
        return url.hostname.replace(/^www\./, '');
    } catch (e) {
        return '';
    }
});

const githubProfile = computed(() => {
    if (props.widget.type !== 'link' || !href.value) return null;

    try {
        const url = new URL(href.value);
        const host = url.hostname.replace(/^www\./, '').toLowerCase();

        if (host !== 'github.com') {
            return null;
        }

        const username = url.pathname.split('/').filter(Boolean)[0] ?? 'github';

        return {
            username,
            handle: `@${username}`,
        };
    } catch (e) {
        return null;
    }
});

const githubCommitCellCount = computed(() => (shape.value === '2x2' ? 48 : 30));
const githubCommitGridClasses = computed(() =>
    shape.value === '2x2'
        ? 'grid grid-cols-8 gap-1.5'
        : 'grid grid-cols-6 gap-1.5',
);

const githubCommitCells = computed(() => {
    const seed = githubProfile.value?.username ?? 'github';
    const charTotal = Array.from(seed).reduce(
        (total, char) => total + char.charCodeAt(0),
        0,
    );

    return Array.from({ length: githubCommitCellCount.value }, (_, index) => {
        const value = (charTotal + index * 17 + (index % 5) * 11) % 9;
        const emptyLeadCells = shape.value === '2x2' ? 12 : 8;

        if (index < emptyLeadCells) {
            return 0;
        }

        return value > 6 ? 4 : value > 4 ? 3 : value > 2 ? 2 : 1;
    });
});

const githubCommitCellClasses = (level: number) => [
    'size-3.5 rounded-[5px] transition-colors sm:size-4',
    level === 4
        ? 'bg-[#216E39]'
        : level === 3
          ? 'bg-[#40C463]'
          : level === 2
            ? 'bg-[#9BE9A8]'
            : level === 1
              ? 'bg-[#D6F5D6]'
              : 'bg-[#EEF0F3]',
];

const githubCommitGlassClasses = computed(() => [
    'flex h-full w-full items-center justify-center bg-[#F7F7F8] p-4',
    props.mode === 'desktop' ? 'sm:p-5' : '',
]);

const faviconUrl = computed(() => {
    if (props.widget.favicon_url) return props.widget.favicon_url;
    if (!domain.value) return '';
    return `https://www.google.com/s2/favicons?domain=${domain.value}&sz=128`;
});

const shape = computed(() => {
    const w = props.mode === 'desktop' ? props.widget.w : props.widget.w_mobile;
    const h = props.mode === 'desktop' ? props.widget.h : props.widget.h_mobile;

    if (w === 1 && h === 2) return '1x1';
    if (w === 2 && h === 2) return '2x1';
    if (w === 1 && h === 4) return '1x2';
    if (w === 2 && h === 4) return '2x2';
    if (w === 2 && h === 1) return 'inline';

    return '1x1';
});

const faviconFailed = ref(false);

const handleFaviconError = () => {
    faviconFailed.value = true;
};

const linkService = computed<LinkService | null>(() => {
    if (!href.value) return null;
    try {
        const url = new URL(href.value);
        const host = url.hostname.replace(/^www\./, '');
        const pathParts = url.pathname.split('/').filter(Boolean);
        const rawAccount = pathParts.length > 0 ? pathParts[0] : '';
        const account = rawAccount ? `@${rawAccount.replace(/^@/, '')}` : '';
        const isHost = (domain: string) => {
            return host === domain || host.endsWith(`.${domain}`);
        };

        if (
            host === 'apps.apple.com' ||
            (host === 'itunes.apple.com' && pathParts.includes('app'))
        ) {
            return {
                name: 'App Store',
                account: '',
                color: 'bg-[#0A84FF] text-white hover:bg-[#006EDB]',
                backgroundColor: 'bg-[#eff6ff]',
                actionLabel: 'インストール',
                isAppStore: true,
            };
        }

        if (host === 'play.google.com' && pathParts.includes('apps')) {
            return {
                name: 'Play Store',
                account: '',
                color: 'bg-[#01875F] text-white hover:bg-[#006B4B]',
                backgroundColor: 'bg-[#effaf5]',
                actionLabel: 'インストール',
                isAppStore: true,
            };
        }

        if (host === 'play.google.com') {
            return {
                name: 'Google Music',
                account: '',
                color: 'bg-[#1A73E8] text-white hover:bg-[#1558b0]',
                backgroundColor: 'bg-[#eff6ff]',
                actionLabel: 'プレイ',
                isMusic: true,
            };
        }

        if (isHost('amazon.com') || isHost('amazon.co.jp')) {
            return {
                name: 'Amazon',
                account: '',
                color: 'bg-[#FF9900] text-gray-950 hover:bg-[#E68A00]',
                backgroundColor: 'bg-[#fff8ed]',
                actionLabel: '購入',
                isCommerce: true,
            };
        }

        if (isHost('rakuten.co.jp')) {
            return {
                name: 'Rakuten',
                account: '',
                color: 'bg-[#BF0000] text-white hover:bg-[#990000]',
                backgroundColor: 'bg-[#fff1f1]',
                actionLabel: '購入',
                isCommerce: true,
            };
        }

        if (isHost('shopify.com') || isHost('myshopify.com')) {
            return {
                name: 'Shopify',
                account: '',
                color: 'bg-[#7AB55C] text-white hover:bg-[#659A4C]',
                backgroundColor: 'bg-[#f3faed]',
                actionLabel: '購入',
                isCommerce: true,
            };
        }

        if (isHost('buymeacoffee.com')) {
            return {
                name: 'Buy Me a Coffee',
                account: '',
                color: 'bg-[#FFDD00] text-gray-950 hover:bg-[#E6C700]',
                backgroundColor: 'bg-[#fffbea]',
                actionLabel: 'サポート',
                isSupport: true,
            };
        }

        if (isHost('ko-fi.com')) {
            return {
                name: 'Ko-fi',
                account: '',
                color: 'bg-[#29ABE0] text-white hover:bg-[#168FBD]',
                backgroundColor: 'bg-[#effaff]',
                actionLabel: 'サポート',
                isSupport: true,
            };
        }

        if (isHost('patreon.com')) {
            return {
                name: 'Patreon',
                account: '',
                color: 'bg-[#FF424D] text-white hover:bg-[#E23640]',
                backgroundColor: 'bg-[#fff1f2]',
                actionLabel: 'サポート',
                isSupport: true,
            };
        }

        if (isHost('fantia.jp')) {
            return {
                name: 'Fantia',
                account: '',
                color: 'bg-[#FF7A00] text-white hover:bg-[#D96500]',
                backgroundColor: 'bg-[#fff6ed]',
                actionLabel: 'フォロー',
                isFanPlatform: true,
            };
        }

        if (isHost('myfans.jp')) {
            return {
                name: 'Myfans',
                account: '',
                color: 'bg-[#FF5C8A] text-white hover:bg-[#E34773]',
                backgroundColor: 'bg-[#fff1f6]',
                actionLabel: 'フォロー',
                isFanPlatform: true,
            };
        }

        if (isHost('onlyfans.com')) {
            return {
                name: 'OnlyFans',
                account: '',
                color: 'bg-[#00AFF0] text-white hover:bg-[#0096CE]',
                backgroundColor: 'bg-[#effaff]',
                actionLabel: 'フォロー',
                isFanPlatform: true,
            };
        }

        if (isHost('fanbox.cc')) {
            return {
                name: 'FANBOX',
                account: '',
                color: 'bg-[#00A1E9] text-white hover:bg-[#0086C2]',
                backgroundColor: 'bg-[#effaff]',
                actionLabel: 'フォロー',
                isFanPlatform: true,
            };
        }

        const matchedServiceConfig =
            linkServicesConfig[host] ??
            (isHost('youtube.com') ? linkServicesConfig['youtube.com'] : null);

        if (matchedServiceConfig) {
            const config = { ...matchedServiceConfig };

            // YouTube specific handling for videos vs channels
            if (isHost('youtube.com') || host === 'youtu.be') {
                if (youtubeVideoId.value) {
                    config.actionLabel = 'プレイ';
                    config.isMusic = true;
                } else {
                    config.actionLabel = 'フォロー';
                }
            }

            // TikTok specific handling for videos vs accounts
            if (host === 'tiktok.com') {
                if (tiktokVideoId.value) {
                    config.actionLabel = 'プレイ';
                    config.isMusic = true;
                } else {
                    config.actionLabel = 'フォロー';
                    config.isMusic = false;
                }
            }

            return {
                ...config,
                account: config.account ?? account,
            } as LinkService;
        }

        return null;
    } catch (e) {
        return null;
    }
});

const socialNetwork = computed(() => {
    return linkService.value?.isMusic ||
        linkService.value?.isCommerce ||
        linkService.value?.isAppStore ||
        linkService.value?.isSupport ||
        linkService.value?.isFanPlatform
        ? null
        : linkService.value;
});

const actionService = computed(() => {
    return linkService.value?.isMusic ||
        linkService.value?.isCommerce ||
        linkService.value?.isAppStore ||
        linkService.value?.isSupport ||
        linkService.value?.isFanPlatform
        ? linkService.value
        : null;
});

const actionPillClass =
    'inline-flex h-8 w-fit items-center justify-center rounded-full px-4 text-xs font-semibold leading-none transition-colors';

const socialActionPillClasses = computed(() => [
    actionPillClass,
    socialNetwork.value?.color ?? 'bg-black text-white',
]);

const actionServicePillClasses = computed(() => [
    actionPillClass,
    actionService.value?.color ?? 'bg-black text-white',
]);

const actionServiceLabel = computed(
    () => actionService.value?.actionLabel ?? '',
);

const ogpInput = ref<HTMLInputElement | null>(null);
const textEditor = ref<HTMLElement | null>(null);
const linkTitleEditor = ref<HTMLElement | null>(null);
const isTextEditorFocused = ref(false);
const isLinkTitleFocused = ref(false);

const chooseOgpImage = () => {
    if (!props.isEditing) return;
    activate();
    ogpInput.value?.click();
};

const handleOgpUpdate = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    if (!file) return;
    const compressedFile = await compressImage(file, { preset: 'card' });
    if (!compressedFile) return;

    emit('upload-image', compressedFile);
    input.value = '';
};

const cropPosition = computed(() => ({
    x: Number(settings.value.cropX ?? 50),
    y: Number(settings.value.cropY ?? 50),
}));

const imageStyle = computed(() => ({
    objectPosition: `${cropPosition.value.x}% ${cropPosition.value.y}%`,
}));

const textWidgetClasses = computed(() => {
    return [
        textColor.value,
        textAlign.value === 'center'
            ? 'text-center'
            : textAlign.value === 'right'
              ? 'text-right'
              : 'text-left',
        verticalAlign.value === 'start'
            ? 'justify-start'
            : verticalAlign.value === 'end'
              ? 'justify-end'
              : 'justify-center',
    ];
});

const textWidgetStyle = computed(() => ({
    backgroundColor: bgColor.value,
}));
const cardFrameClasses = computed(() => {
    if (props.widget.type === 'section' && !props.isEditing) {
        return '';
    }

    if (props.widget.type === 'section') {
        return props.pageTheme === 'dark'
            ? 'border border-white/15 bg-white/10'
            : 'border border-gray-200 bg-gray-100/70';
    }

    if (props.widget.type === 'image' || props.widget.type === 'text') {
        return 'border border-gray-200';
    }

    return 'border border-gray-200 bg-white';
});
const widgetCornerClass = computed(
    () => props.widgetCornerClass ?? 'rounded-2xl',
);
const cardClipClasses = computed(() =>
    props.isCropping ? 'overflow-visible' : 'overflow-hidden',
);
const cardFrameStyle = computed(() => {
    if (props.widget.type === 'text') {
        return {
            backgroundColor: bgColor.value,
        };
    }

    if (props.widget.type === 'image') {
        return {
            backgroundColor: 'transparent',
        };
    }

    return undefined;
});
const normalizedBgColor = computed(() => {
    const color = bgColor.value.trim();

    if (/^#[0-9a-f]{6}$/i.test(color)) {
        return color;
    }

    if (/^#[0-9a-f]{3}$/i.test(color)) {
        return `#${color
            .slice(1)
            .split('')
            .map((value) => value + value)
            .join('')}`;
    }

    return '#ffffff';
});
const textColor = computed(() => {
    const red = parseInt(normalizedBgColor.value.slice(1, 3), 16);
    const green = parseInt(normalizedBgColor.value.slice(3, 5), 16);
    const blue = parseInt(normalizedBgColor.value.slice(5, 7), 16);
    const luminance = (0.299 * red + 0.587 * green + 0.114 * blue) / 255;

    return luminance > 0.55 ? 'text-gray-800' : 'text-white';
});
const textEditorClasses = computed(() => [
    isTextEditorFocused.value ? 'widget-text-input--focused' : '',
    isTextEditorFocused.value
        ? 'cursor-text'
        : 'cursor-grab active:cursor-grabbing',
    textColor.value,
    hasTitle.value ? '' : 'is-empty',
    textAlign.value === 'center'
        ? 'text-center'
        : textAlign.value === 'right'
          ? 'text-right'
          : 'text-left',
]);
const linkTitleEditorClasses = computed(() => [
    isLinkTitleFocused.value ? 'widget-text-input--focused' : '',
    'block w-full overflow-auto whitespace-pre-wrap break-words rounded bg-gray-600/10 text-base leading-6 text-gray-800 outline-none focus:ring-2 focus:ring-blue-500',
    shape.value === 'inline'
        ? 'h-6'
        : shape.value === '1x1'
          ? 'h-[48px]'
          : props.mode === 'mobile'
            ? 'h-[48px]'
            : 'h-[72px]',
    isLinkTitleFocused.value
        ? 'cursor-text'
        : 'cursor-grab active:cursor-grabbing',
]);
const linkTitleDisplayClasses = computed(() => [
    'block whitespace-pre-wrap break-words text-base leading-6 text-gray-800',
    shape.value === 'inline'
        ? 'h-6 truncate whitespace-nowrap'
        : props.mode === 'mobile'
          ? 'h-[48px] line-clamp-2'
          : 'h-[72px] line-clamp-3',
]);
const linkDomainClasses =
    'whitespace-normal break-words text-base font-semibold text-gray-500';
const linkCardClasses = computed(() => {
    return linkService.value?.backgroundColor ?? 'bg-white';
});
const textEditorPanelClasses = computed(() => [
    'bg-gray-200/40',
    verticalAlign.value === 'start'
        ? 'justify-start'
        : verticalAlign.value === 'end'
          ? 'justify-end'
          : 'justify-center',
]);
const captionEditorStyle = computed(() => ({
    width: `min(${Math.max(title.value.length + 1, 12)}ch, 100%)`,
}));

const focusTextEditor = () => {
    if (!props.isEditing || props.widget.type !== 'text') return;

    activate();
    nextTick(() => {
        textEditor.value?.focus();
    });
};

const stopPointerWhenFocused = (event: Event, isFocused: boolean) => {
    if (isFocused) {
        event.stopPropagation();
    }
};

const syncTextEditor = () => {
    if (!textEditor.value || isTextEditorFocused.value) return;

    if (textEditor.value.innerText !== title.value) {
        textEditor.value.innerText = title.value;
    }
};

const updateTextEditor = () => {
    emit(
        'update-title',
        (textEditor.value?.innerText ?? '').slice(0, MAX_TEXT_WIDGET_LENGTH),
    );
};

const limitPlainTextBeforeInput = (event: Event, maxLength: number) => {
    const inputEvent = event as InputEvent;

    if (
        inputEvent.inputType.startsWith('delete') ||
        inputEvent.inputType === 'historyUndo' ||
        inputEvent.inputType === 'historyRedo'
    ) {
        return;
    }

    const target = event.target as HTMLElement;
    const selection = window.getSelection();
    const selectedLength = selection?.toString().length ?? 0;
    const currentLength = target.innerText.length;
    const incomingLength = inputEvent.data?.length ?? 0;

    if (currentLength - selectedLength + incomingLength > maxLength) {
        event.preventDefault();
    }
};

const pastePlainText = (event: ClipboardEvent, maxLength: number) => {
    event.preventDefault();

    const target = event.target as HTMLElement;
    const selection = window.getSelection();
    const selectedLength = selection?.toString().length ?? 0;
    const currentLength = target.innerText.length;
    const remainingLength = Math.max(
        0,
        maxLength - currentLength + selectedLength,
    );
    const pastedText = (event.clipboardData?.getData('text/plain') ?? '').slice(
        0,
        remainingLength,
    );

    document.execCommand('insertText', false, pastedText);
};

watch(
    () => [title.value, props.isEditing],
    () => nextTick(syncTextEditor),
    { immediate: true },
);

const syncLinkTitleEditor = () => {
    if (!linkTitleEditor.value || isLinkTitleFocused.value) return;

    const fallbackTitle = socialNetwork.value?.account ?? '';
    const editorTitle = title.value || fallbackTitle;

    if (linkTitleEditor.value.innerText !== editorTitle) {
        linkTitleEditor.value.innerText = editorTitle;
    }
};

const updateLinkTitleEditor = () => {
    updateTitleFromElement(linkTitleEditor.value);
};

const pastePlainLinkTitle = (event: ClipboardEvent) => {
    pastePlainText(event, MAX_LINK_TITLE_LENGTH);
    updateLinkTitleEditor();
};

watch(
    () => [
        title.value,
        props.isEditing,
        props.isActive,
        shape.value,
        socialNetwork.value?.account,
        embedMode.value,
    ],
    () => nextTick(syncLinkTitleEditor),
    { immediate: true },
);

const imageRef = ref<HTMLImageElement | null>(null);
const cropper = ref<Cropper | null>(null);
const isInitializingCropper = ref(false);
const mapRef = ref<HTMLElement | null>(null);
const leafletMap = ref<LeafletMap | null>(null);

const initCropper = () => {
    if (!imageRef.value) return;

    isInitializingCropper.value = true;

    cropper.value = new Cropper(imageRef.value, {
        viewMode: 1,
        dragMode: 'move',
        autoCropArea: 1,
        restore: false,
        guides: false,
        center: false,
        highlight: false,
        cropBoxMovable: false,
        cropBoxResizable: false,
        zoomable: false,
        toggleDragModeOnDblclick: false,
        modal: true, // Show the semi-transparent overlay outside the crop box
        ready() {
            if (!cropper.value) return;

            const containerData = cropper.value.getContainerData();
            const imageData = cropper.value.getImageData();
            const cropBoxData = {
                left: 0,
                top: 0,
                width: containerData.width,
                height: containerData.height,
            };

            cropper.value.setCropBoxData(cropBoxData);

            // Auto-fit logic: Fill the container with the image
            const scale = Math.max(
                containerData.width / imageData.naturalWidth,
                containerData.height / imageData.naturalHeight,
            );

            cropper.value.setCanvasData({
                width: imageData.naturalWidth * scale,
                height: imageData.naturalHeight * scale,
            });

            // Set initial position based on existing cropX/Y
            const canvasData = cropper.value.getCanvasData();
            const xPercent = Number(settings.value.cropX ?? 50);
            const yPercent = Number(settings.value.cropY ?? 50);

            const xRange = containerData.width - canvasData.width;
            const yRange = containerData.height - canvasData.height;

            cropper.value.setCanvasData({
                left: xRange * (xPercent / 100),
                top: yRange * (yPercent / 100),
            });

            nextTick(() => {
                cropper.value?.setCropBoxData(cropBoxData);
                isInitializingCropper.value = false;
            });
        },
        crop() {
            if (isInitializingCropper.value) return;

            const canvasData = cropper.value?.getCanvasData();
            const containerData = cropper.value?.getContainerData();

            if (canvasData && containerData) {
                const xRange = containerData.width - canvasData.width;
                const yRange = containerData.height - canvasData.height;

                // Avoid division by zero
                const x =
                    Math.abs(xRange) < 0.1
                        ? 50
                        : (canvasData.left / xRange) * 100;
                const y =
                    Math.abs(yRange) < 0.1
                        ? 50
                        : (canvasData.top / yRange) * 100;

                emit('update-crop', {
                    x: Math.min(100, Math.max(0, x)),
                    y: Math.min(100, Math.max(0, y)),
                });
            }
        },
    });
};

const destroyCropper = () => {
    if (cropper.value) {
        cropper.value.destroy();
        cropper.value = null;
    }

    isInitializingCropper.value = false;
};

const initializeMap = async () => {
    if (props.widget.type !== 'map' || !mapRef.value || leafletMap.value) {
        return;
    }

    const L = await import('leaflet');
    leafletMap.value = L.map(mapRef.value, {
        attributionControl: false,
        zoomControl: false,
        dragging: Boolean(props.isMapMoving),
        scrollWheelZoom: false,
        doubleClickZoom: Boolean(props.isMapMoving),
        boxZoom: false,
        keyboard: false,
        tap: Boolean(props.isMapMoving),
        touchZoom: Boolean(props.isMapMoving),
    }).setView([mapLat.value, mapLng.value], mapZoom.value);

    L.tileLayer(
        'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
        {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
        },
    ).addTo(leafletMap.value);

    leafletMap.value.on('moveend', updateMapCenterFromLeaflet);

    recenterMap();
};

const recenterMap = () => {
    const applyCenter = () => {
        if (!leafletMap.value || props.widget.type !== 'map') {
            return;
        }

        leafletMap.value.invalidateSize({ pan: false });
        leafletMap.value.setView([mapLat.value, mapLng.value], mapZoom.value, {
            animate: false,
        });
    };

    nextTick(() => {
        window.requestAnimationFrame(() => {
            applyCenter();
            window.setTimeout(applyCenter, 160);
        });
    });
};

const syncMapView = () => {
    if (!leafletMap.value || props.widget.type !== 'map') {
        return;
    }

    if (props.isMapMoving) {
        leafletMap.value.dragging.enable();
        leafletMap.value.doubleClickZoom.enable();
        leafletMap.value.touchZoom.enable();
        if (leafletMap.value.getZoom() !== mapZoom.value) {
            leafletMap.value.setZoom(mapZoom.value, { animate: false });
        }
    } else {
        leafletMap.value.dragging.disable();
        leafletMap.value.doubleClickZoom.disable();
        leafletMap.value.touchZoom.disable();
        recenterMap();
    }
};

const updateMapCenterFromLeaflet = () => {
    if (!leafletMap.value || !props.isMapMoving) {
        return;
    }

    const center = leafletMap.value.getCenter();

    emit('update-map-center', {
        lat: center.lat,
        lng: center.lng,
        zoom: leafletMap.value.getZoom(),
    });
};

const destroyMap = () => {
    if (leafletMap.value) {
        leafletMap.value.off('moveend', updateMapCenterFromLeaflet);
        leafletMap.value.remove();
        leafletMap.value = null;
    }
};

watch(
    () => props.isCropping,
    (isCropping) => {
        if (isCropping) {
            nextTick(() => initCropper());
        } else {
            destroyCropper();
        }
    },
);

watch(
    () => [
        props.widget.type,
        props.isEditing,
        mapLat.value,
        mapLng.value,
        mapZoom.value,
        mapLayoutKey.value,
        props.isMapMoving,
        props.mode,
    ],
    () => {
        if (props.widget.type === 'map') {
            nextTick(() => {
                initializeMap();
                syncMapView();
            });
        }
    },
);

onMounted(() => {
    if (props.widget.type === 'map') {
        nextTick(initializeMap);
    }
});

onUnmounted(() => {
    destroyCropper();
    destroyMap();
});
</script>

<template>
    <div
        class="relative h-full w-full"
        :class="[cardFrameClasses, cardClipClasses, widgetCornerClass]"
        :style="cardFrameStyle"
    >
        <div
            v-if="widget.type === 'section'"
            class="flex h-full min-h-[48px] items-center px-3"
        >
            <textarea
                v-if="isEditing"
                :value="title"
                rows="1"
                maxlength="4500"
                placeholder="セクションを入力"
                class="widget-text-input w-full cursor-text resize-none rounded-xl border border-transparent bg-transparent px-3 py-2 leading-tight font-bold transition-colors duration-150 focus:outline-none"
                :class="[
                    mode === 'desktop' ? 'text-xl' : 'text-lg',
                    pageTheme === 'dark'
                        ? 'text-white placeholder:text-white/45 hover:bg-white/10 focus:border-white/20 focus:bg-white/10'
                        : 'text-gray-950 placeholder:text-gray-400 hover:bg-gray-100 focus:border-gray-200 focus:bg-white',
                ]"
                @input="updateTitle"
                @focus="activate"
                @click.stop
                @pointerdown.stop
                @mousedown.stop
                @touchstart.stop
            ></textarea>
            <p
                v-else
                class="truncate px-3 py-2 font-bold"
                :class="[
                    mode === 'desktop' ? 'text-xl' : 'text-lg',
                    pageTheme === 'dark' ? 'text-white' : 'text-gray-800',
                ]"
            >
                {{ title }}
            </p>
        </div>

        <div
            v-else-if="widget.type === 'text'"
            class="flex h-full w-full flex-col p-4 transition-colors duration-150"
            :class="textWidgetClasses"
            :style="textWidgetStyle"
            @click="focusTextEditor"
        >
            <div
                v-if="isEditing"
                class="flex min-h-0 flex-1 flex-col rounded-xl p-3 transition-colors duration-150"
                :class="textEditorPanelClasses"
                @click.stop="focusTextEditor"
            >
                <div
                    ref="textEditor"
                    contenteditable="true"
                    role="textbox"
                    aria-multiline="true"
                    data-placeholder="テキストを入力..."
                    class="text-widget-editor max-h-full w-full overflow-auto border border-transparent bg-transparent text-lg leading-snug font-semibold break-words whitespace-pre-wrap transition-colors duration-150 outline-none"
                    :class="textEditorClasses"
                    @beforeinput="
                        limitPlainTextBeforeInput(
                            $event,
                            MAX_TEXT_WIDGET_LENGTH,
                        )
                    "
                    @input="updateTextEditor"
                    @paste="
                        pastePlainText($event, MAX_TEXT_WIDGET_LENGTH);
                        updateTextEditor();
                    "
                    @focus="
                        activate();
                        isTextEditorFocused = true;
                    "
                    @blur="isTextEditorFocused = false"
                    @click.stop
                    @pointerdown="
                        stopPointerWhenFocused($event, isTextEditorFocused)
                    "
                    @mousedown="
                        stopPointerWhenFocused($event, isTextEditorFocused)
                    "
                    @touchstart="
                        stopPointerWhenFocused($event, isTextEditorFocused)
                    "
                ></div>
            </div>
            <p
                v-else-if="title"
                class="p-3 text-lg leading-snug font-semibold"
                :class="
                    shape === 'inline'
                        ? 'truncate'
                        : 'break-words whitespace-pre-wrap'
                "
            >
                {{ title }}
            </p>
        </div>

        <div
            v-else-if="widget.type === 'image'"
            class="relative h-full w-full"
            :class="[
                isCropping
                    ? 'is-cropping-active overflow-visible'
                    : 'overflow-hidden',
            ]"
        >
            <img
                ref="imageRef"
                :src="image"
                :alt="title"
                :style="!isCropping ? imageStyle : undefined"
                class="relative z-10 h-full w-full object-cover transition-[filter,transform] duration-150"
                :class="widgetCornerClass"
                draggable="false"
            />
            <div
                v-if="isCropping"
                class="pointer-events-none absolute inset-0 z-20"
                :class="widgetCornerClass"
            ></div>
            <div
                v-if="isEditing"
                class="absolute inset-x-0 bottom-0 z-30 p-3 transition-opacity duration-150 focus-within:opacity-100"
                :class="
                    title ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'
                "
            >
                <textarea
                    :value="title"
                    placeholder="Add a title..."
                    rows="1"
                    maxlength="4500"
                    class="widget-text-input [field-sizing:content] max-w-full cursor-text resize-none rounded-xl border border-white/60 bg-white/55 px-3 py-2 font-semibold break-words whitespace-pre-wrap text-gray-800 shadow-sm backdrop-blur-md placeholder:text-gray-800 focus:ring-2 focus:ring-white/80 focus:outline-none"
                    :class="mode === 'desktop' ? 'text-sm' : 'text-xs'"
                    :style="captionEditorStyle"
                    @keydown.enter.prevent
                    @input="updateTitle"
                    @focus="activate"
                    @click.stop
                    @pointerdown.stop
                    @mousedown.stop
                    @touchstart.stop
                ></textarea>
            </div>
            <div v-else-if="title" class="absolute inset-x-0 bottom-0 z-30 p-3">
                <span
                    class="inline-block w-fit max-w-full rounded-xl border border-white/60 bg-white/55 px-3 py-2 font-semibold break-words whitespace-normal text-gray-800 shadow-sm backdrop-blur-md"
                    :class="mode === 'desktop' ? 'text-sm' : 'text-xs'"
                >
                    {{ title }}
                </span>
            </div>
        </div>

        <div
            v-else-if="widget.type === 'map'"
            class="grid-link-google-map relative h-full w-full overflow-hidden bg-[#f1eee8]"
            :class="widgetCornerClass"
            :aria-label="mapAriaLabel"
        >
            <div ref="mapRef" class="h-full w-full"></div>
            <div
                class="grid-link-map-center-marker pointer-events-none absolute top-1/2 left-1/2 z-[402] -translate-x-1/2 -translate-y-1/2"
                aria-hidden="true"
            >
                <span class="grid-link-map-marker__pulse"></span>
                <span class="grid-link-map-marker__dot"></span>
            </div>
            <div
                v-if="isEditing"
                class="absolute inset-x-0 bottom-0 z-[403] p-3 transition-opacity duration-150 focus-within:opacity-100"
                :class="
                    mapTitle
                        ? 'opacity-100'
                        : 'opacity-0 group-hover:opacity-100'
                "
            >
                <textarea
                    :value="mapTitle"
                    placeholder="Add a title..."
                    rows="1"
                    maxlength="4500"
                    class="widget-text-input [field-sizing:content] max-w-full cursor-text resize-none rounded-xl border border-white/60 bg-white/55 px-3 py-2 font-semibold break-words whitespace-pre-wrap text-gray-800 shadow-sm backdrop-blur-md placeholder:text-gray-800 focus:ring-2 focus:ring-white/80 focus:outline-none"
                    :class="mode === 'desktop' ? 'text-sm' : 'text-xs'"
                    :style="captionEditorStyle"
                    @keydown.enter.prevent
                    @input="updateTitle"
                    @focus="activate"
                    @click.stop
                    @pointerdown.stop
                    @mousedown.stop
                    @touchstart.stop
                ></textarea>
            </div>
            <div
                v-else-if="mapTitle"
                class="absolute inset-x-0 bottom-0 z-[403] p-3"
            >
                <span
                    class="inline-block w-fit max-w-full rounded-xl border border-white/60 bg-white/55 px-3 py-2 font-semibold break-words whitespace-normal text-gray-800 shadow-sm backdrop-blur-md"
                    :class="mode === 'desktop' ? 'text-sm' : 'text-xs'"
                >
                    {{ mapTitle }}
                </span>
            </div>
        </div>

        <div
            v-else-if="widget.type === 'social'"
            class="flex h-full flex-col p-4"
        >
            <img
                :src="image"
                :alt="widget.subtitle"
                class="mb-3 size-8"
                draggable="false"
            />
            <span class="block truncate text-base text-gray-800">
                {{ title }}
            </span>
            <div class="flex-1" />
            <span :class="[actionPillClass, 'bg-[#0095f6] text-white']">
                フォロー
            </span>
        </div>

        <div
            v-else-if="widget.type === 'link'"
            class="h-full w-full"
            :class="linkCardClasses"
        >
            <!-- Embed Mode -->
            <div
                v-if="embedMode === 'embed' && musicEmbedInfo"
                class="h-full w-full overflow-hidden bg-black"
                :class="widgetCornerClass"
            >
                <iframe
                    :src="musicEmbedInfo.url"
                    :title="`${musicEmbedInfo.service} player`"
                    frameborder="0"
                    allow="
                        accelerometer;
                        autoplay;
                        clipboard-write;
                        encrypted-media;
                        gyroscope;
                        picture-in-picture;
                        web-share;
                    "
                    allowfullscreen
                    class="h-full w-full"
                    :class="{ 'pointer-events-none': isEditing }"
                ></iframe>
            </div>

            <!-- Inline Layout -->
            <div
                v-else-if="shape === 'inline'"
                class="flex h-full items-center px-5"
            >
                <img
                    v-if="faviconUrl && !faviconFailed"
                    :src="faviconUrl"
                    @error="handleFaviconError"
                    alt=""
                    class="mr-3 size-8 shrink-0 rounded-lg object-cover"
                    draggable="false"
                />
                <div
                    v-else
                    class="mr-3 flex size-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-400"
                >
                    <LinkIcon class="size-4" />
                </div>
                <div
                    v-if="isEditing"
                    ref="linkTitleEditor"
                    contenteditable="true"
                    role="textbox"
                    aria-label="リンクタイトル"
                    :class="[linkTitleEditorClasses, 'flex-1']"
                    @beforeinput="
                        limitPlainTextBeforeInput($event, MAX_LINK_TITLE_LENGTH)
                    "
                    @keydown.enter.prevent
                    @input="updateLinkTitleEditor"
                    @paste="pastePlainLinkTitle"
                    @focus="
                        activate();
                        isLinkTitleFocused = true;
                    "
                    @blur="isLinkTitleFocused = false"
                    @click.stop
                    @pointerdown="
                        stopPointerWhenFocused($event, isLinkTitleFocused)
                    "
                    @mousedown="
                        stopPointerWhenFocused($event, isLinkTitleFocused)
                    "
                    @touchstart="
                        stopPointerWhenFocused($event, isLinkTitleFocused)
                    "
                ></div>
                <p
                    v-else
                    class="flex-1 truncate text-lg font-bold text-gray-800"
                >
                    {{ title || socialNetwork?.account }}
                </p>
            </div>

            <!-- 1x1 Layout -->
            <div v-else-if="shape === '1x1'" class="flex h-full flex-col p-5">
                <img
                    v-if="faviconUrl && !faviconFailed"
                    :src="faviconUrl"
                    @error="handleFaviconError"
                    alt=""
                    class="mb-3 size-8 rounded-lg object-cover"
                    draggable="false"
                />
                <div
                    v-else
                    class="mb-3 flex size-8 items-center justify-center rounded-lg bg-gray-100 text-gray-400"
                >
                    <LinkIcon class="size-4" />
                </div>
                <div
                    v-if="isEditing"
                    ref="linkTitleEditor"
                    contenteditable="true"
                    role="textbox"
                    aria-label="リンクタイトル"
                    :class="linkTitleEditorClasses"
                    @beforeinput="
                        limitPlainTextBeforeInput($event, MAX_LINK_TITLE_LENGTH)
                    "
                    @keydown.enter.prevent
                    @input="updateLinkTitleEditor"
                    @paste="pastePlainLinkTitle"
                    @focus="
                        activate();
                        isLinkTitleFocused = true;
                    "
                    @blur="isLinkTitleFocused = false"
                    @click.stop
                    @pointerdown="
                        stopPointerWhenFocused($event, isLinkTitleFocused)
                    "
                    @mousedown="
                        stopPointerWhenFocused($event, isLinkTitleFocused)
                    "
                    @touchstart="
                        stopPointerWhenFocused($event, isLinkTitleFocused)
                    "
                ></div>
                <p v-else :class="linkTitleDisplayClasses">
                    {{ title || socialNetwork?.account }}
                </p>

                <div class="flex-1"></div>

                <template v-if="socialNetwork && socialNetwork.account">
                    <span :class="socialActionPillClasses"> フォロー</span>
                </template>
                <template v-else-if="actionService">
                    <span :class="actionServicePillClasses">
                        {{ actionServiceLabel }}
                    </span>
                </template>
                <template v-else>
                    <p :class="linkDomainClasses">{{ domain }}</p>
                </template>
            </div>

            <!-- 2x1 Layout (Horizontal) -->
            <div v-else-if="shape === '2x1'" class="flex h-full gap-4 p-4">
                <div class="flex min-w-0 flex-1 flex-col py-1 pl-1">
                    <div class="mb-3">
                        <div class="relative w-fit shrink-0">
                            <img
                                v-if="faviconUrl && !faviconFailed"
                                :src="faviconUrl"
                                @error="handleFaviconError"
                                alt=""
                                class="size-8 shrink-0 rounded-lg object-cover"
                                draggable="false"
                            />
                            <div
                                v-else
                                class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-400"
                            >
                                <LinkIcon class="size-4" />
                            </div>
                        </div>
                    </div>
                    <div
                        v-if="isEditing"
                        ref="linkTitleEditor"
                        contenteditable="true"
                        role="textbox"
                        aria-label="リンクタイトル"
                        :class="linkTitleEditorClasses"
                        @beforeinput="
                            limitPlainTextBeforeInput(
                                $event,
                                MAX_LINK_TITLE_LENGTH,
                            )
                        "
                        @keydown.enter.prevent
                        @input="updateLinkTitleEditor"
                        @paste="pastePlainLinkTitle"
                        @focus="
                            activate();
                            isLinkTitleFocused = true;
                        "
                        @blur="isLinkTitleFocused = false"
                        @click.stop
                        @pointerdown="
                            stopPointerWhenFocused($event, isLinkTitleFocused)
                        "
                        @mousedown="
                            stopPointerWhenFocused($event, isLinkTitleFocused)
                        "
                        @touchstart="
                            stopPointerWhenFocused($event, isLinkTitleFocused)
                        "
                    ></div>
                    <p v-else :class="linkTitleDisplayClasses">
                        {{ title || socialNetwork?.account }}
                    </p>
                    <template v-if="socialNetwork && socialNetwork.account">
                        <span :class="[socialActionPillClasses, 'mt-2']">
                            フォロー</span
                        >
                    </template>
                    <template v-else-if="actionService">
                        <span :class="[actionServicePillClasses, 'mt-2']">
                            {{ actionServiceLabel }}
                        </span>
                    </template>
                    <div class="flex-1"></div>
                    <template v-if="!socialNetwork && !actionService">
                        <p :class="['mt-auto', linkDomainClasses]">
                            {{ domain }}
                        </p>
                    </template>
                </div>
                <div
                    v-if="embedMode === 'link_embed' && musicEmbedInfo"
                    class="relative flex-1 overflow-hidden"
                    :class="widgetCornerClass"
                >
                    <iframe
                        :src="musicEmbedInfo.url"
                        :title="`${musicEmbedInfo.service} player`"
                        frameborder="0"
                        allow="
                            accelerometer;
                            autoplay;
                            clipboard-write;
                            encrypted-media;
                            gyroscope;
                            picture-in-picture;
                            web-share;
                        "
                        allowfullscreen
                        class="relative z-10 h-full w-full"
                        :class="{ 'pointer-events-none': isEditing }"
                    ></iframe>
                </div>
                <div
                    v-else-if="githubProfile"
                    class="flex-1 overflow-hidden"
                    :class="widgetCornerClass"
                >
                    <div :class="githubCommitGlassClasses">
                        <div class="grid grid-cols-6 gap-1.5">
                            <span
                                v-for="(level, index) in githubCommitCells"
                                :key="index"
                                :class="githubCommitCellClasses(level)"
                            ></span>
                        </div>
                    </div>
                </div>
                <button
                    v-else-if="isEditing"
                    @click.stop="chooseOgpImage"
                    class="group relative flex-1 cursor-pointer overflow-hidden"
                    :class="widgetCornerClass"
                >
                    <img
                        v-if="image"
                        :src="image"
                        class="h-full w-full object-cover"
                        draggable="false"
                    />
                    <span
                        class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/0 text-white opacity-0 transition-all duration-150 group-hover:bg-black/20 group-hover:opacity-100"
                    >
                        <Image class="size-8" />
                    </span>
                    <button
                        v-if="image"
                        @click.stop="emit('remove-image')"
                        class="absolute top-2 right-2 flex size-8 cursor-pointer items-center justify-center rounded-xl bg-red-600 text-white opacity-0 transition-opacity duration-150 group-hover:opacity-100 hover:bg-red-700"
                    >
                        <Trash2 class="size-4" />
                    </button>
                </button>
                <div
                    v-else-if="image"
                    class="flex-1 overflow-hidden"
                    :class="widgetCornerClass"
                >
                    <img
                        :src="image"
                        class="h-full w-full object-cover"
                        draggable="false"
                    />
                </div>
            </div>

            <!-- 1x2 Layout (Vertical) -->
            <div
                v-else-if="shape === '1x2'"
                class="flex h-full flex-col gap-4 p-4"
            >
                <div class="flex min-h-0 flex-1 flex-col px-1 pt-1">
                    <div class="mb-3">
                        <div class="relative w-fit shrink-0">
                            <img
                                v-if="faviconUrl && !faviconFailed"
                                :src="faviconUrl"
                                @error="handleFaviconError"
                                alt=""
                                class="size-8 shrink-0 rounded-lg object-cover"
                                draggable="false"
                            />
                            <div
                                v-else
                                class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-400"
                            >
                                <LinkIcon class="size-4" />
                            </div>
                        </div>
                    </div>
                    <div
                        v-if="isEditing"
                        ref="linkTitleEditor"
                        contenteditable="true"
                        role="textbox"
                        aria-label="リンクタイトル"
                        :class="linkTitleEditorClasses"
                        @beforeinput="
                            limitPlainTextBeforeInput(
                                $event,
                                MAX_LINK_TITLE_LENGTH,
                            )
                        "
                        @keydown.enter.prevent
                        @input="updateLinkTitleEditor"
                        @paste="pastePlainLinkTitle"
                        @focus="
                            activate();
                            isLinkTitleFocused = true;
                        "
                        @blur="isLinkTitleFocused = false"
                        @click.stop
                        @pointerdown="
                            stopPointerWhenFocused($event, isLinkTitleFocused)
                        "
                        @mousedown="
                            stopPointerWhenFocused($event, isLinkTitleFocused)
                        "
                        @touchstart="
                            stopPointerWhenFocused($event, isLinkTitleFocused)
                        "
                    ></div>
                    <p v-else :class="linkTitleDisplayClasses">
                        {{ title || socialNetwork?.account }}
                    </p>
                    <template v-if="socialNetwork && socialNetwork.account">
                        <span :class="[socialActionPillClasses, 'mt-2']">
                            フォロー</span
                        >
                    </template>
                    <template v-else-if="actionService">
                        <span :class="[actionServicePillClasses, 'mt-2']">
                            {{ actionServiceLabel }}
                        </span>
                    </template>
                    <div class="flex-1"></div>
                    <template v-if="!socialNetwork && !actionService">
                        <p :class="linkDomainClasses">{{ domain }}</p>
                    </template>
                </div>
                <div
                    v-if="embedMode === 'link_embed' && musicEmbedInfo"
                    class="relative flex-1 overflow-hidden"
                    :class="widgetCornerClass"
                >
                    <iframe
                        :src="musicEmbedInfo.url"
                        :title="`${musicEmbedInfo.service} player`"
                        frameborder="0"
                        allow="
                            accelerometer;
                            autoplay;
                            clipboard-write;
                            encrypted-media;
                            gyroscope;
                            picture-in-picture;
                            web-share;
                        "
                        allowfullscreen
                        class="relative z-10 h-full w-full"
                        :class="{ 'pointer-events-none': isEditing }"
                    ></iframe>
                </div>
                <div
                    v-else-if="githubProfile"
                    class="flex-1 overflow-hidden"
                    :class="widgetCornerClass"
                >
                    <div :class="githubCommitGlassClasses">
                        <div class="grid grid-cols-6 gap-1.5">
                            <span
                                v-for="(level, index) in githubCommitCells"
                                :key="index"
                                :class="githubCommitCellClasses(level)"
                            ></span>
                        </div>
                    </div>
                </div>
                <button
                    v-else-if="isEditing"
                    @click.stop="chooseOgpImage"
                    class="group relative flex-1 cursor-pointer overflow-hidden"
                    :class="widgetCornerClass"
                >
                    <img
                        v-if="image"
                        :src="image"
                        class="h-full w-full object-cover"
                        draggable="false"
                    />
                    <span
                        class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/0 text-white opacity-0 transition-all duration-150 group-hover:bg-black/20 group-hover:opacity-100"
                    >
                        <Image class="size-8" />
                    </span>
                    <button
                        v-if="image"
                        @click.stop="emit('remove-image')"
                        class="absolute top-2 right-2 flex size-8 cursor-pointer items-center justify-center rounded-xl bg-red-600 text-white opacity-0 transition-opacity duration-150 group-hover:opacity-100 hover:bg-red-700"
                    >
                        <Trash2 class="size-4" />
                    </button>
                </button>
                <div
                    v-else-if="image"
                    class="flex-1 overflow-hidden"
                    :class="widgetCornerClass"
                >
                    <img
                        :src="image"
                        class="h-full w-full object-cover"
                        draggable="false"
                    />
                </div>
            </div>

            <!-- 2x2 Layout (Large Square) -->
            <div
                v-else-if="shape === '2x2'"
                class="flex h-full flex-col justify-between p-4"
            >
                <div class="flex min-h-0 flex-1 flex-col px-1 pt-1 pb-3">
                    <div class="mb-3 flex items-start justify-between">
                        <div class="relative w-fit shrink-0">
                            <img
                                v-if="faviconUrl && !faviconFailed"
                                :src="faviconUrl"
                                @error="handleFaviconError"
                                alt=""
                                class="size-8 shrink-0 rounded-lg object-cover"
                                draggable="false"
                            />
                            <div
                                v-else
                                class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-400"
                            >
                                <LinkIcon class="size-4" />
                            </div>
                        </div>

                        <!-- Top Right Action Button -->
                        <template v-if="socialNetwork && socialNetwork.account">
                            <span :class="socialActionPillClasses">
                                フォロー</span
                            >
                        </template>
                        <template v-else-if="actionService">
                            <span :class="actionServicePillClasses">
                                {{ actionServiceLabel }}
                            </span>
                        </template>
                    </div>

                    <template v-if="socialNetwork && socialNetwork.account">
                        <div
                            v-if="isEditing"
                            ref="linkTitleEditor"
                            contenteditable="true"
                            role="textbox"
                            aria-label="リンクタイトル"
                            :class="linkTitleEditorClasses"
                            @beforeinput="
                                limitPlainTextBeforeInput(
                                    $event,
                                    MAX_LINK_TITLE_LENGTH,
                                )
                            "
                            @keydown.enter.prevent
                            @input="updateLinkTitleEditor"
                            @paste="pastePlainLinkTitle"
                            @focus="
                                activate();
                                isLinkTitleFocused = true;
                            "
                            @blur="isLinkTitleFocused = false"
                            @click.stop
                            @pointerdown="
                                stopPointerWhenFocused(
                                    $event,
                                    isLinkTitleFocused,
                                )
                            "
                            @mousedown="
                                stopPointerWhenFocused(
                                    $event,
                                    isLinkTitleFocused,
                                )
                            "
                            @touchstart="
                                stopPointerWhenFocused(
                                    $event,
                                    isLinkTitleFocused,
                                )
                            "
                        ></div>
                        <p v-else :class="linkTitleDisplayClasses">
                            {{ title || socialNetwork.account }}
                        </p>
                    </template>
                    <template v-else-if="actionService">
                        <div
                            v-if="isEditing"
                            ref="linkTitleEditor"
                            contenteditable="true"
                            role="textbox"
                            aria-label="リンクタイトル"
                            :class="linkTitleEditorClasses"
                            @beforeinput="
                                limitPlainTextBeforeInput(
                                    $event,
                                    MAX_LINK_TITLE_LENGTH,
                                )
                            "
                            @keydown.enter.prevent
                            @input="updateLinkTitleEditor"
                            @paste="pastePlainLinkTitle"
                            @focus="
                                activate();
                                isLinkTitleFocused = true;
                            "
                            @blur="isLinkTitleFocused = false"
                            @click.stop
                            @pointerdown="
                                stopPointerWhenFocused(
                                    $event,
                                    isLinkTitleFocused,
                                )
                            "
                            @mousedown="
                                stopPointerWhenFocused(
                                    $event,
                                    isLinkTitleFocused,
                                )
                            "
                            @touchstart="
                                stopPointerWhenFocused(
                                    $event,
                                    isLinkTitleFocused,
                                )
                            "
                        ></div>
                        <p v-else :class="linkTitleDisplayClasses">
                            {{ title }}
                        </p>
                    </template>
                    <template v-else>
                        <div
                            v-if="isEditing"
                            ref="linkTitleEditor"
                            contenteditable="true"
                            role="textbox"
                            aria-label="リンクタイトル"
                            :class="linkTitleEditorClasses"
                            @beforeinput="
                                limitPlainTextBeforeInput(
                                    $event,
                                    MAX_LINK_TITLE_LENGTH,
                                )
                            "
                            @keydown.enter.prevent
                            @input="updateLinkTitleEditor"
                            @paste="pastePlainLinkTitle"
                            @focus="
                                activate();
                                isLinkTitleFocused = true;
                            "
                            @blur="isLinkTitleFocused = false"
                            @click.stop
                            @pointerdown="
                                stopPointerWhenFocused(
                                    $event,
                                    isLinkTitleFocused,
                                )
                            "
                            @mousedown="
                                stopPointerWhenFocused(
                                    $event,
                                    isLinkTitleFocused,
                                )
                            "
                            @touchstart="
                                stopPointerWhenFocused(
                                    $event,
                                    isLinkTitleFocused,
                                )
                            "
                        ></div>
                        <p v-else :class="linkTitleDisplayClasses">
                            {{ title }}
                        </p>
                        <div class="flex-1"></div>
                        <p :class="['mt-2 text-lg', linkDomainClasses]">
                            {{ domain }}
                        </p>
                    </template>
                </div>
                <div
                    v-if="embedMode === 'link_embed' && musicEmbedInfo"
                    class="relative w-full shrink-0 overflow-hidden"
                    :class="widgetCornerClass"
                    style="aspect-ratio: 1.91 / 1"
                >
                    <iframe
                        :src="musicEmbedInfo.url"
                        :title="`${musicEmbedInfo.service} player`"
                        frameborder="0"
                        allow="
                            accelerometer;
                            autoplay;
                            clipboard-write;
                            encrypted-media;
                            gyroscope;
                            picture-in-picture;
                            web-share;
                        "
                        allowfullscreen
                        class="relative z-10 h-full w-full"
                        :class="{ 'pointer-events-none': isEditing }"
                    ></iframe>
                </div>
                <div
                    v-else-if="githubProfile"
                    class="w-full shrink-0 overflow-hidden"
                    :class="widgetCornerClass"
                    style="aspect-ratio: 1.91 / 1"
                >
                    <div :class="githubCommitGlassClasses">
                        <div class="grid grid-cols-6 gap-1.5">
                            <span
                                v-for="(level, index) in githubCommitCells"
                                :key="index"
                                :class="githubCommitCellClasses(level)"
                            ></span>
                        </div>
                    </div>
                </div>
                <button
                    v-else-if="isEditing"
                    @click.stop="chooseOgpImage"
                    class="group relative w-full shrink-0 cursor-pointer overflow-hidden"
                    :class="widgetCornerClass"
                    style="aspect-ratio: 1.91 / 1"
                >
                    <img
                        v-if="image"
                        :src="image"
                        class="h-full w-full object-cover"
                        draggable="false"
                    />
                    <span
                        class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/0 text-white opacity-0 transition-all duration-150 group-hover:bg-black/20 group-hover:opacity-100"
                    >
                        <Image class="size-8" />
                    </span>
                    <button
                        v-if="image"
                        @click.stop="emit('remove-image')"
                        class="absolute top-2 right-2 flex size-8 cursor-pointer items-center justify-center rounded-xl bg-red-600 text-white opacity-0 transition-opacity duration-150 group-hover:opacity-100 hover:bg-red-700"
                    >
                        <Trash2 class="size-4" />
                    </button>
                </button>
                <div
                    v-else-if="image"
                    class="w-full shrink-0 overflow-hidden"
                    :class="widgetCornerClass"
                    style="aspect-ratio: 1.91 / 1"
                >
                    <img
                        :src="image"
                        class="h-full w-full object-cover"
                        draggable="false"
                    />
                </div>
            </div>
            <input
                ref="ogpInput"
                type="file"
                accept="image/*,.apng"
                class="hidden"
                @change="handleOgpUpdate"
            />
        </div>

        <div
            v-else
            class="flex h-full w-full items-center justify-center p-4 text-center text-lg font-semibold whitespace-pre-line text-gray-700"
            :class="[widget.bg, mode === 'desktop' ? 'leading-relaxed' : '']"
        >
            <span v-if="widget.id === 'article-note' && mode === 'desktop'">
                article
                <ArrowRight class="inline size-6" />
            </span>
            <span v-else>{{ title }}</span>
        </div>
    </div>
</template>

<style>
.text-widget-editor.is-empty::before {
    content: attr(data-placeholder);
    color: rgb(107 114 128 / 0.72);
    pointer-events: none;
}

/* Cropper customization */
.is-cropping-active .cropper-container {
    background-color: transparent !important;
    overflow: visible !important;
}

.is-cropping-active .cropper-wrap-box {
    overflow: visible !important;
}

.is-cropping-active .cropper-bg {
    background-image: none !important;
}

.is-cropping-active .cropper-canvas {
    outline: 1px solid #000 !important; /* Border for the entire image */
}

.is-cropping-active .cropper-view-box {
    outline: 3px solid #000 !important;
    outline-color: #000 !important;
    border-radius: 1rem;
    box-shadow: 0 0 0 1000px rgba(229, 231, 235, 0.45); /* Manual overlay if modal is not enough */
}

.is-cropping-active .cropper-face {
    background-color: transparent !important;
}

.is-cropping-active .cropper-modal {
    background-color: rgba(229, 231, 235, 0.45) !important;
    opacity: 1 !important;
}

.is-cropping-active .cropper-move {
    cursor: grab !important;
}

.is-cropping-active .cropper-move:active,
.is-cropping-active:active .cropper-move {
    cursor: grabbing !important;
}
</style>
