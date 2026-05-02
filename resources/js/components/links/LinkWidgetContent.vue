<script setup lang="ts">
import { ArrowRight, Link as LinkIcon, Image, Play } from 'lucide-vue-next';
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';

const props = defineProps<{
    widget: any;
    mode: 'desktop' | 'mobile';
    isEditing: boolean;
    isActive: boolean;
    isCropping?: boolean;
}>();

const emit = defineEmits<{
    activate: [];
    'update-title': [newTitle: string];
    'update-crop': [crop: { x: number; y: number }];
    'upload-image': [file: File];
    'remove-image': [];
}>();

const updateTitle = (event: Event) => {
    emit('update-title', (event.target as HTMLTextAreaElement).value);
};

const updateTitleFromElement = (element: HTMLElement | null) => {
    emit('update-title', element?.innerText ?? '');
};

const activate = () => {
    emit('activate');
};

const settings = computed(() => props.widget.settings || {});

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

const domain = computed(() => {
    if (!href.value) return '';
    try {
        const url = new URL(href.value);
        return url.hostname.replace(/^www\./, '');
    } catch (e) {
        return '';
    }
});

const faviconUrl = computed(() => {
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

    return '1x1';
});

const faviconFailed = ref(false);

const handleFaviconError = () => {
    faviconFailed.value = true;
};

type LinkService = {
    name: string;
    account: string;
    color: string;
    backgroundColor: string;
    actionLabel?: string;
    isAppStore?: boolean;
    isCommerce?: boolean;
    isFanPlatform?: boolean;
    isMusic?: boolean;
    isSupport?: boolean;
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

        const services: Record<
            string,
            Omit<LinkService, 'account'> & { account?: string }
        > = {
            'x.com': {
                name: 'X',
                color: 'bg-black text-white hover:bg-gray-800',
                backgroundColor: 'bg-gray-50',
            },
            'twitter.com': {
                name: 'X',
                color: 'bg-black text-white hover:bg-gray-800',
                backgroundColor: 'bg-gray-50',
            },
            'instagram.com': {
                name: 'Instagram',
                color: 'bg-gradient-to-r from-[#833ab4] via-[#fd1d1d] to-[#fcb045] text-white hover:opacity-90',
                backgroundColor: 'bg-[#fff1f6]',
            },
            'threads.net': {
                name: 'Threads',
                color: 'bg-black text-white hover:bg-gray-800',
                backgroundColor: 'bg-gray-50',
            },
            'youtube.com': {
                name: 'YouTube',
                color: 'bg-[#FF0000] text-white hover:bg-[#CC0000]',
                backgroundColor: 'bg-[#fff1f1]',
            },
            'music.youtube.com': {
                name: 'YouTube Music',
                color: 'bg-[#FF0000] text-white hover:bg-[#CC0000]',
                backgroundColor: 'bg-[#fff1f1]',
                isMusic: true,
            },
            'tiktok.com': {
                name: 'TikTok',
                color: 'bg-black text-white hover:bg-gray-800',
                backgroundColor: 'bg-gray-50',
            },
            'facebook.com': {
                name: 'Facebook',
                color: 'bg-[#1877F2] text-white hover:bg-[#0C63D4]',
                backgroundColor: 'bg-[#eff6ff]',
            },
            'tumblr.com': {
                name: 'Tumblr',
                color: 'bg-[#36465D] text-white hover:bg-[#253142]',
                backgroundColor: 'bg-[#f1f5f9]',
            },
            'pixiv.net': {
                name: 'Pixiv',
                color: 'bg-[#0096FA] text-white hover:bg-[#007AD1]',
                backgroundColor: 'bg-[#eff8ff]',
            },
            'snapchat.com': {
                name: 'Snapchat',
                color: 'bg-[#FFFC00] text-black hover:bg-[#E6E200]',
                backgroundColor: 'bg-[#fffde7]',
            },
            'github.com': {
                name: 'GitHub',
                color: 'bg-[#24292F] text-white hover:bg-[#000000]',
                backgroundColor: 'bg-gray-50',
            },
            'linkedin.com': {
                name: 'LinkedIn',
                color: 'bg-[#0A66C2] text-white hover:bg-[#004182]',
                backgroundColor: 'bg-[#eff6ff]',
            },
            'twitch.tv': {
                name: 'Twitch',
                color: 'bg-[#9146FF] text-white hover:bg-[#772CE8]',
                backgroundColor: 'bg-[#f5f0ff]',
            },
            'medium.com': {
                name: 'Medium',
                color: 'bg-black text-white hover:bg-gray-800',
                backgroundColor: 'bg-gray-50',
            },
            'dribbble.com': {
                name: 'Dribbble',
                color: 'bg-[#ea4c89] text-white hover:bg-[#e0357a]',
                backgroundColor: 'bg-[#fff1f6]',
            },
            'figma.com': {
                name: 'Figma',
                color: 'bg-[#F24E1E] text-white hover:bg-[#E14013]',
                backgroundColor: 'bg-[#fff4ef]',
            },
            'note.com': {
                name: 'Note',
                color: 'bg-[#2CB696] text-white hover:bg-[#239B7F]',
                backgroundColor: 'bg-[#effcf8]',
            },
            'music.apple.com': {
                name: 'Apple Music',
                color: 'bg-[#FA243C] text-white hover:bg-[#dd1830]',
                backgroundColor: 'bg-[#fff1f3]',
                actionLabel: 'Play',
                isMusic: true,
            },
            'itunes.apple.com': {
                name: 'Apple Music',
                color: 'bg-[#FA243C] text-white hover:bg-[#dd1830]',
                backgroundColor: 'bg-[#fff1f3]',
                actionLabel: 'Play',
                isMusic: true,
            },
            'open.spotify.com': {
                name: 'Spotify',
                color: 'bg-[#1DB954] text-white hover:bg-[#169c46]',
                backgroundColor: 'bg-[#effaf3]',
                actionLabel: 'Play',
                isMusic: true,
            },
            'spotify.com': {
                name: 'Spotify',
                color: 'bg-[#1DB954] text-white hover:bg-[#169c46]',
                backgroundColor: 'bg-[#effaf3]',
                actionLabel: 'Play',
                isMusic: true,
            },
            'music.google.com': {
                name: 'Google Music',
                color: 'bg-[#1A73E8] text-white hover:bg-[#1558b0]',
                backgroundColor: 'bg-[#eff6ff]',
                actionLabel: 'Play',
                isMusic: true,
            },
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
                actionLabel: 'Play',
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

        if (services[host]) {
            return {
                ...services[host],
                account: services[host].account ?? account,
            };
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

const handleOgpUpdate = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    if (!file) return;
    emit('upload-image', file);
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
    textColor.value,
    hasTitle.value ? '' : 'is-empty',
    textAlign.value === 'center'
        ? 'text-center'
        : textAlign.value === 'right'
          ? 'text-right'
          : 'text-left',
]);
const linkTitleEditorClasses =
    'block min-h-6 max-h-20 w-full overflow-auto whitespace-pre-wrap break-words rounded bg-gray-100/70 text-base leading-6 text-gray-800 outline-none focus:ring-2 focus:ring-blue-500';
const linkTitleDisplayClasses =
    'block min-h-6 whitespace-pre-wrap break-words text-base leading-6 text-gray-800';
const linkDomainClasses =
    'whitespace-normal break-words text-base font-semibold text-gray-500';
const linkCardClasses = computed(() => {
    return linkService.value?.backgroundColor ?? 'bg-white';
});
const textEditorPanelClasses = computed(() => [
    isTextEditorFocused.value ? 'bg-gray-200/40' : 'bg-transparent',
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

const syncTextEditor = () => {
    if (!textEditor.value || isTextEditorFocused.value) return;

    if (textEditor.value.innerText !== title.value) {
        textEditor.value.innerText = title.value;
    }
};

const updateTextEditor = () => {
    emit('update-title', textEditor.value?.innerText ?? '');
};

const pastePlainText = (event: ClipboardEvent) => {
    event.preventDefault();

    const pastedText = event.clipboardData?.getData('text/plain') ?? '';
    document.execCommand('insertText', false, pastedText);
    updateTextEditor();
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
    event.preventDefault();

    const pastedText = event.clipboardData?.getData('text/plain') ?? '';
    document.execCommand('insertText', false, pastedText);
    updateLinkTitleEditor();
};

watch(
    () => [
        title.value,
        props.isEditing,
        props.isActive,
        socialNetwork.value?.account,
    ],
    () => nextTick(syncLinkTitleEditor),
    { immediate: true },
);

const isDraggingCrop = ref(false);
const lastCropPointer = ref<{ x: number; y: number } | null>(null);
let cropAnimationFrame: number | null = null;
let pendingCrop: { x: number; y: number } | null = null;

const clampCrop = (value: number) => Math.min(100, Math.max(0, value));

const stopCropDrag = () => {
    isDraggingCrop.value = false;
    lastCropPointer.value = null;
    if (cropAnimationFrame) {
        cancelAnimationFrame(cropAnimationFrame);
        cropAnimationFrame = null;
    }
    if (pendingCrop) {
        emit('update-crop', pendingCrop);
        pendingCrop = null;
    }
    window.removeEventListener('mousemove', dragCrop);
    window.removeEventListener('mouseup', stopCropDrag);
};

const dragCrop = (event: MouseEvent) => {
    if (!isDraggingCrop.value || !lastCropPointer.value) return;

    const deltaX = event.clientX - lastCropPointer.value.x;
    const deltaY = event.clientY - lastCropPointer.value.y;

    const currentCrop = pendingCrop ?? cropPosition.value;
    pendingCrop = {
        x: clampCrop(currentCrop.x - deltaX * 0.35),
        y: clampCrop(currentCrop.y - deltaY * 0.35),
    };

    if (!cropAnimationFrame) {
        cropAnimationFrame = requestAnimationFrame(() => {
            cropAnimationFrame = null;

            if (pendingCrop) {
                emit('update-crop', pendingCrop);
                pendingCrop = null;
            }
        });
    }

    lastCropPointer.value = { x: event.clientX, y: event.clientY };
};

const startCropDrag = (event: MouseEvent) => {
    if (!props.isEditing || !props.isCropping) return;

    event.preventDefault();
    event.stopPropagation();
    activate();
    isDraggingCrop.value = true;
    lastCropPointer.value = { x: event.clientX, y: event.clientY };
    window.addEventListener('mousemove', dragCrop);
    window.addEventListener('mouseup', stopCropDrag);
};

onUnmounted(() => {
    stopCropDrag();
});
</script>

<template>
    <div
        class="relative h-full w-full overflow-hidden"
        :class="
            widget.type === 'section' && !isEditing
                ? ''
                : 'rounded-2xl border border-gray-200 bg-white'
        "
    >
        <div
            v-if="widget.type === 'section'"
            class="flex h-full min-h-[48px] items-center px-3"
        >
            <textarea
                v-if="isEditing"
                :value="title"
                rows="1"
                placeholder="セクションを入力"
                class="w-full resize-none rounded-xl border border-transparent bg-transparent px-3 py-2 leading-tight font-bold text-gray-800 transition-colors duration-150 placeholder:text-gray-400 hover:bg-gray-100/70 focus:border-gray-200 focus:bg-gray-100/70 focus:outline-none"
                :class="[
                    mode === 'desktop' ? 'text-xl' : 'text-lg',
                    hasTitle ? '' : 'bg-gray-100/60',
                ]"
                @input="updateTitle"
                @focus="activate"
                @click.stop
            ></textarea>
            <p
                v-else
                class="truncate font-bold text-gray-800"
                :class="mode === 'desktop' ? 'text-xl' : 'text-lg'"
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
                @mousedown.stop
            >
                <div
                    ref="textEditor"
                    contenteditable="true"
                    role="textbox"
                    aria-multiline="true"
                    data-placeholder="テキストを入力..."
                    class="text-widget-editor max-h-full w-full overflow-auto whitespace-pre-wrap break-words border border-transparent bg-transparent text-lg font-semibold leading-snug outline-none transition-colors duration-150"
                    :class="textEditorClasses"
                    @input="updateTextEditor"
                    @paste="pastePlainText"
                    @focus="
                        activate();
                        isTextEditorFocused = true;
                    "
                    @blur="isTextEditorFocused = false"
                    @click.stop
                    @mousedown.stop
                ></div>
            </div>
            <p
                v-else-if="title"
                class="whitespace-pre-wrap break-words text-lg font-semibold leading-snug"
            >
                {{ title }}
            </p>
        </div>

        <div
            v-else-if="widget.type === 'image'"
            class="relative h-full w-full overflow-hidden"
        >
            <img
                :src="image"
                :alt="title"
                :style="imageStyle"
                class="relative z-10 h-full w-full rounded-2xl object-cover transition-[filter,transform] duration-150"
                :class="[
                    isCropping
                        ? 'cursor-grab brightness-90 active:cursor-grabbing'
                        : '',
                    isDraggingCrop ? 'scale-[1.04]' : '',
                ]"
                draggable="false"
                @mousedown="startCropDrag"
            />
            <div
                v-if="isCropping"
                class="pointer-events-none absolute inset-0 z-20 rounded-2xl ring-2 ring-white/80 ring-inset"
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
                    class="max-w-full resize-none whitespace-pre-wrap break-words rounded-xl bg-white/80 px-3 py-2 font-semibold text-gray-800 backdrop-blur-sm [field-sizing:content] placeholder:text-gray-800 focus:ring-2 focus:ring-white/80 focus:outline-none"
                    :class="mode === 'desktop' ? 'text-sm' : 'text-xs'"
                    :style="captionEditorStyle"
                    @keydown.enter.prevent
                    @input="updateTitle"
                    @focus="activate"
                    @click.stop
                    @mousedown.stop
                    @touchstart.stop
                ></textarea>
            </div>
            <div
                v-else-if="title"
                class="absolute inset-x-0 bottom-0 z-30 p-3"
            >
                <span
                    class="inline-block w-fit max-w-full whitespace-normal break-words rounded-xl bg-white/80 px-3 py-2 font-semibold text-gray-800 backdrop-blur-sm"
                    :class="mode === 'desktop' ? 'text-sm' : 'text-xs'"
                >
                    {{ title }}
                </span>
            </div>
        </div>

        <div v-else-if="widget.type === 'social'" class="flex h-full flex-col p-4">
            <img :src="image" :alt="widget.subtitle" class="mb-3 size-8" draggable="false" />
            <span class="block truncate text-base text-gray-800">
                {{ title }}
            </span>
            <div class="flex-1" />
            <span class="w-fit rounded-full bg-[#0095f6] px-4 py-1.5 text-xs font-semibold text-white">
                Follow
            </span>
        </div>

        <div
            v-else-if="widget.type === 'link'"
            class="h-full w-full"
            :class="linkCardClasses"
        >
            <!-- 1x1 Layout -->
            <div v-if="shape === '1x1'" class="flex h-full flex-col p-5">
                <img v-if="faviconUrl && !faviconFailed" :src="faviconUrl" @error="handleFaviconError" alt=""
                    class="mb-3 size-10 rounded-[10px] object-cover" draggable="false" />
                <div v-else
                    class="mb-3 flex size-10 items-center justify-center rounded-[10px] bg-gray-100 text-gray-400">
                    <LinkIcon class="size-5" />
                </div>
                <div
                    v-if="isEditing"
                    ref="linkTitleEditor"
                    contenteditable="true"
                    role="textbox"
                    aria-label="リンクタイトル"
                    :class="linkTitleEditorClasses"
                    @input="updateLinkTitleEditor"
                    @paste="pastePlainLinkTitle"
                    @focus="
                        activate();
                        isLinkTitleFocused = true;
                    "
                    @blur="isLinkTitleFocused = false"
                    @click.stop
                    @mousedown.stop
                ></div>
                <p v-else :class="linkTitleDisplayClasses">{{ title || socialNetwork?.account }}</p>

                <div class="flex-1"></div>

                <template v-if="socialNetwork && socialNetwork.account">
                    <a :href="href" target="_blank" rel="noopener noreferrer"
                        :class="['w-fit rounded-full  px-4 py-1.5 text-xs font-semibold  cursor-pointer  transition-colors'.replace('  ', ' ').trim(), socialNetwork.color]"
                        @click.stop>
                        Follow
                    </a>
                </template>
                <template v-else-if="actionService">
                    <span
                        :class="['inline-flex w-fit items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-semibold transition-colors', actionService.color]"
                    >
                        <Play
                            v-if="actionService.isMusic"
                            class="size-3 fill-current"
                        />
                        {{ actionService.actionLabel }}
                    </span>
                </template>
                <template v-else>
                    <p :class="linkDomainClasses">{{ domain }}</p>
                </template>
            </div>

            <!-- 2x1 Layout (Horizontal) -->
            <div v-else-if="shape === '2x1'" class="flex h-full p-4 gap-4">
                <div class="flex min-w-0 flex-1 flex-col py-1 pl-1">
                    <img v-if="faviconUrl && !faviconFailed" :src="faviconUrl" @error="handleFaviconError" alt=""
                        class="mb-3 size-10 rounded-[10px] object-cover" draggable="false" />
                    <div v-else
                        class="mb-3 flex size-10 items-center justify-center rounded-[10px] bg-gray-100 text-gray-400">
                        <LinkIcon class="size-5" />
                    </div>
                    <div
                        v-if="isEditing"
                        ref="linkTitleEditor"
                        contenteditable="true"
                        role="textbox"
                        aria-label="リンクタイトル"
                        :class="linkTitleEditorClasses"
                        @input="updateLinkTitleEditor"
                        @paste="pastePlainLinkTitle"
                        @focus="
                            activate();
                            isLinkTitleFocused = true;
                        "
                        @blur="isLinkTitleFocused = false"
                        @click.stop
                        @mousedown.stop
                    ></div>
                    <p v-else :class="linkTitleDisplayClasses">{{ title || socialNetwork?.account }}</p>
                    <div class="flex-1"></div>
                    <template v-if="socialNetwork && socialNetwork.account">
                        <a :href="href" target="_blank" rel="noopener noreferrer"
                            :class="['mt-auto w-fit rounded-full  px-4 py-1.5 text-xs font-semibold  cursor-pointer  transition-colors'.replace('  ', ' ').trim(), socialNetwork.color]"
                            @click.stop>
                            Follow
                        </a>
                    </template>
                    <template v-else-if="actionService">
                        <span
                            :class="['mt-auto inline-flex w-fit items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-semibold transition-colors', actionService.color]"
                        >
                            <Play
                                v-if="actionService.isMusic"
                                class="size-3 fill-current"
                            />
                            {{ actionService.actionLabel }}
                        </span>
                    </template>
                    <template v-else>
                        <p :class="['mt-auto', linkDomainClasses]">{{ domain }}</p>
                    </template>
                </div>
                <button v-if="isEditing" @click.stop="chooseOgpImage"
                    class="cursor-pointer group relative flex-1 overflow-hidden rounded-2xl">
                    <img v-if="image" :src="image" class="h-full w-full object-cover" draggable="false" />
                    <span
                        class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/0 text-white opacity-0 transition-all duration-150 group-hover:bg-black/20 group-hover:opacity-100">
                        <Image class="size-8" />
                    </span>
                    <button v-if="image" @click.stop="emit('remove-image')"
                        class="absolute top-2 right-2 flex size-8 cursor-pointer items-center justify-center rounded-xl bg-black/90 text-white opacity-0 transition-opacity duration-150 hover:bg-black group-hover:opacity-100">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </button>
                <div v-else-if="image" class="flex-1 overflow-hidden rounded-2xl">
                    <img :src="image" class="h-full w-full object-cover" draggable="false" />
                </div>
            </div>

            <!-- 1x2 Layout (Vertical) -->
            <div v-else-if="shape === '1x2'" class="flex h-full flex-col p-4 gap-4">
                <div class="flex min-h-0 flex-1 flex-col pt-1 px-1">
                    <img v-if="faviconUrl && !faviconFailed" :src="faviconUrl" @error="handleFaviconError" alt=""
                        class="mb-3 size-10 rounded-[10px] object-cover" draggable="false" />
                    <div v-else
                        class="mb-3 flex size-10 items-center justify-center rounded-[10px] bg-gray-100 text-gray-400">
                        <LinkIcon class="size-5" />
                    </div>
                    <div
                        v-if="isEditing"
                        ref="linkTitleEditor"
                        contenteditable="true"
                        role="textbox"
                        aria-label="リンクタイトル"
                        :class="linkTitleEditorClasses"
                        @input="updateLinkTitleEditor"
                        @paste="pastePlainLinkTitle"
                        @focus="
                            activate();
                            isLinkTitleFocused = true;
                        "
                        @blur="isLinkTitleFocused = false"
                        @click.stop
                        @mousedown.stop
                    ></div>
                    <p v-else :class="linkTitleDisplayClasses">{{ title || socialNetwork?.account }}</p>
                    <div class="flex-1"></div>
                    <template v-if="socialNetwork && socialNetwork.account">
                        <a :href="href" target="_blank" rel="noopener noreferrer"
                            :class="['w-fit rounded-full  px-4 py-1.5 text-xs font-semibold  cursor-pointer  transition-colors'.replace('  ', ' ').trim(), socialNetwork.color]"
                            @click.stop>
                            Follow
                        </a>
                    </template>
                    <template v-else-if="actionService">
                        <span
                            :class="['inline-flex w-fit items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-semibold transition-colors', actionService.color]"
                        >
                            <Play
                                v-if="actionService.isMusic"
                                class="size-3 fill-current"
                            />
                            {{ actionService.actionLabel }}
                        </span>
                    </template>
                    <template v-else>
                        <p :class="linkDomainClasses">{{ domain }}</p>
                    </template>
                </div>
                <button v-if="isEditing" @click.stop="chooseOgpImage"
                    class="cursor-pointer group relative flex-1 overflow-hidden rounded-2xl">
                    <img v-if="image" :src="image" class="h-full w-full object-cover" draggable="false" />
                    <span
                        class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/0 text-white opacity-0 transition-all duration-150 group-hover:bg-black/20 group-hover:opacity-100">
                        <Image class="size-8" />
                    </span>
                    <button v-if="image" @click.stop="emit('remove-image')"
                        class="absolute top-2 right-2 flex size-8 cursor-pointer items-center justify-center rounded-xl bg-black/90 text-white opacity-0 transition-opacity duration-150 hover:bg-black group-hover:opacity-100">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </button>
                <div v-else-if="image" class="flex-1 overflow-hidden rounded-2xl">
                    <img :src="image" class="h-full w-full object-cover" draggable="false" />
                </div>
            </div>

            <!-- 2x2 Layout (Large Square) -->
            <div v-else-if="shape === '2x2'" class="flex h-full flex-col p-4 justify-between">
                <div class="flex min-h-0 flex-1 flex-col px-1 pt-1 pb-3">
                    <img v-if="faviconUrl && !faviconFailed" :src="faviconUrl" @error="handleFaviconError" alt=""
                        class="mb-3 size-10 rounded-[10px] object-cover shrink-0"
                        draggable="false" />
                    <div v-else
                        class="mb-3 flex size-10 items-center justify-center rounded-[10px] bg-gray-100 text-gray-400 shrink-0">
                        <LinkIcon class="size-5" />
                    </div>
                    <template v-if="socialNetwork && socialNetwork.account">
                        <div
                            v-if="isEditing"
                            ref="linkTitleEditor"
                            contenteditable="true"
                            role="textbox"
                            aria-label="リンクタイトル"
                            :class="linkTitleEditorClasses"
                            @input="updateLinkTitleEditor"
                            @paste="pastePlainLinkTitle"
                            @focus="
                                activate();
                                isLinkTitleFocused = true;
                            "
                            @blur="isLinkTitleFocused = false"
                            @click.stop
                            @mousedown.stop
                        ></div>
                        <p v-else :class="linkTitleDisplayClasses">{{
                            title || socialNetwork.account }}</p>
                        <div class="flex-1"></div>
                        <a :href="href" target="_blank" rel="noopener noreferrer"
                            :class="['mt-2 w-fit rounded-full  px-4 py-1.5 text-sm font-semibold  cursor-pointer  transition-colors inline-block'.replace('  ', ' ').trim(), socialNetwork.color]"
                            @click.stop>
                            Follow
                        </a>
                    </template>
                    <template v-else-if="actionService">
                        <div
                            v-if="isEditing"
                            ref="linkTitleEditor"
                            contenteditable="true"
                            role="textbox"
                            aria-label="リンクタイトル"
                            :class="linkTitleEditorClasses"
                            @input="updateLinkTitleEditor"
                            @paste="pastePlainLinkTitle"
                            @focus="
                                activate();
                                isLinkTitleFocused = true;
                            "
                            @blur="isLinkTitleFocused = false"
                            @click.stop
                            @mousedown.stop
                        ></div>
                        <p v-else :class="linkTitleDisplayClasses">{{
                            title }}</p>
                        <div class="flex-1"></div>
                        <span
                            :class="['mt-2 inline-flex w-fit items-center gap-1.5 rounded-full px-4 py-1.5 text-sm font-semibold transition-colors', actionService.color]"
                        >
                            <Play
                                v-if="actionService.isMusic"
                                class="size-3.5 fill-current"
                            />
                            {{ actionService.actionLabel }}
                        </span>
                    </template>
                    <template v-else>
                        <div
                            v-if="isEditing"
                            ref="linkTitleEditor"
                            contenteditable="true"
                            role="textbox"
                            aria-label="リンクタイトル"
                            :class="linkTitleEditorClasses"
                            @input="updateLinkTitleEditor"
                            @paste="pastePlainLinkTitle"
                            @focus="
                                activate();
                                isLinkTitleFocused = true;
                            "
                            @blur="isLinkTitleFocused = false"
                            @click.stop
                            @mousedown.stop
                        ></div>
                        <p v-else :class="linkTitleDisplayClasses">{{
                            title }}</p>
                        <div class="flex-1"></div>
                        <p :class="['mt-2 text-lg', linkDomainClasses]">{{ domain }}</p>
                    </template>
                </div>
                <button v-if="isEditing" @click.stop="chooseOgpImage"
                    class="cursor-pointer group relative w-full shrink-0 overflow-hidden rounded-2xl"
                    style="aspect-ratio: 1.91 / 1;">
                    <img v-if="image" :src="image" class="h-full w-full object-cover" draggable="false" />
                    <span
                        class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/0 text-white opacity-0 transition-all duration-150 group-hover:bg-black/20 group-hover:opacity-100">
                        <Image class="size-8" />
                    </span>
                    <button v-if="image" @click.stop="emit('remove-image')"
                        class="absolute top-2 right-2 flex size-8 cursor-pointer items-center justify-center rounded-xl bg-black/90 text-white opacity-0 transition-opacity duration-150 hover:bg-black group-hover:opacity-100">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </button>
                <div v-else-if="image" class="w-full shrink-0 overflow-hidden rounded-2xl"
                    style="aspect-ratio: 1.91 / 1;">
                    <img :src="image" class="h-full w-full object-cover" draggable="false" />
                </div>
            </div>
            <input ref="ogpInput" type="file" accept="image/*" class="hidden" @change="handleOgpUpdate" />
        </div>

        <div v-else
            class="flex h-full w-full items-center justify-center p-4 text-center text-lg font-semibold whitespace-pre-line text-gray-700"
            :class="[widget.bg, mode === 'desktop' ? 'leading-relaxed' : '']">
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
</style>
