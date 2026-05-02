<script setup lang="ts">
import LinkAddModal from '@/components/links/LinkAddModal.vue';
import LinkPageNavigation from '@/components/links/LinkPageNavigation.vue';
import LinkProfile from '@/components/links/LinkProfile.vue';
import LinkToolbar from '@/components/links/LinkToolbar.vue';
import LinkWidgetContent from '@/components/links/LinkWidgetContent.vue';
import LinkWidgetControls from '@/components/links/LinkWidgetControls.vue';
import { Toaster } from '@/components/ui/toast';
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { GridItem, GridLayout } from 'grid-layout-plus';
import {
    Image as ImageIcon,
    LayoutGrid,
    Link as LinkIcon,
    Music,
    Plus,
    RectangleHorizontal,
    RectangleVertical,
    Square,
} from 'lucide-vue-next';
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';

const props = defineProps<{
    link: {
        id: number;
        user_id: number;
        slug: string;
        display_name: string;
        bio?: string | null;
        avatar_url?: string | null;
        is_published?: boolean;
        widgets?: any[];
    };
}>();

const page = usePage();
const isOwner = computed(() => {
    return (
        page.props.auth?.user && page.props.auth.user.id === props.link.user_id
    );
});

const previewMode = ref<'desktop' | 'mobile'>('desktop');
const isEditing = ref(false);

const editForm = ref({
    display_name: props.link.display_name,
    bio:
        props.link.bio ||
        [
            "Photographe d'architecture contemporaine, j’explore les mutations urbaines",
            '',
            "Mon projet ELEVATION redécouvre l'architecture pour offrir un nouveau regard sur notre quotidien.",
            '',
            'contact : hello@grid.link',
        ].join('\n'),
});
const avatarFile = ref<File | null>(null);
const avatarPreviewUrl = ref<string | null>(null);
const avatarRemoved = ref(false);

const localWidgets = ref<any[]>([]);
const desktopLayout = ref<any[]>([]);
const mobileLayout = ref<any[]>([]);
const pageScrollContainer = ref<HTMLElement | null>(null);

const draggingWidgetId = ref<string | number | null>(null);
const croppingWidgetId = ref<string | number | null>(null);

const updateLayoutsFromWidgets = () => {
    desktopLayout.value = localWidgets.value.map((w) => ({
        i: String(w.id),
        x: Number(w.x ?? 0),
        y: Number(w.y ?? 0),
        w: Number(w.w ?? 1),
        h: Number(w.h ?? 2),
        widget: w,
    }));
    mobileLayout.value = localWidgets.value.map((w) => ({
        i: String(w.id),
        x: Number(w.x_mobile ?? 0),
        y: Number(w.y_mobile ?? 0),
        w: Number(w.w_mobile ?? 1),
        h: Number(w.h_mobile ?? 2),
        widget: w,
    }));
};

const scrollToWidgetBottom = () => {
    nextTick(() => {
        requestAnimationFrame(() => {
            window.scrollTo({
                top: document.documentElement.scrollHeight,
                behavior: 'smooth',
            });
        });
    });
};

const syncWidgetsFromDesktop = () => {
    desktopLayout.value.forEach((item) => {
        const widget = localWidgets.value.find((w) => String(w.id) === item.i);
        if (widget) {
            widget.x = item.x;
            widget.y = item.y;
            widget.w = item.w;
            widget.h = item.h;
        }
    });
};

const syncWidgetsFromMobile = () => {
    mobileLayout.value.forEach((item) => {
        const widget = localWidgets.value.find((w) => String(w.id) === item.i);
        if (widget) {
            widget.x_mobile = item.x;
            widget.y_mobile = item.y;
            widget.w_mobile = item.w;
            widget.h_mobile = item.h;
        }
    });
};

type LayoutKeys = {
    x: 'x' | 'x_mobile';
    y: 'y' | 'y_mobile';
    w: 'w' | 'w_mobile';
    h: 'h' | 'h_mobile';
};

const desktopKeys = { x: 'x', y: 'y', w: 'w', h: 'h' } as const;
const mobileKeys = {
    x: 'x_mobile',
    y: 'y_mobile',
    w: 'w_mobile',
    h: 'h_mobile',
} as const;
const sectionSizeOptions = {
    desktop: [
        {
            key: 'section-compact',
            icon: Square,
            label: '1/2幅',
            size: { w: 2, h: 1 },
        },
        {
            key: 'section-wide',
            icon: RectangleHorizontal,
            label: '全幅',
            size: { w: 4, h: 1 },
        },
    ],
    mobile: [
        {
            key: 'section-compact',
            icon: Square,
            label: '1/2幅',
            size: { w: 1, h: 1 },
        },
        {
            key: 'section-wide',
            icon: RectangleHorizontal,
            label: '全幅',
            size: { w: 2, h: 1 },
        },
    ],
};
const widgetSizeOptionList = [
    { key: 'small', icon: Square, label: '1x1', size: { w: 1, h: 2 } },
    {
        key: 'wide',
        icon: RectangleHorizontal,
        label: '2x1',
        size: { w: 2, h: 2 },
    },
    {
        key: 'tall',
        icon: RectangleVertical,
        label: '1x2',
        size: { w: 1, h: 4 },
    },
    { key: 'large', icon: LayoutGrid, label: '2x2', size: { w: 2, h: 4 } },
];

const findNextGridPosition = (
    widgets: any[],
    columns: number,
    width: number,
    height: number,
    keys: LayoutKeys,
) => {
    const maxX = Math.max(columns - width, 0);

    for (let y = 0; y < 1000; y += 1) {
        for (let x = 0; x <= maxX; x += 1) {
            const overlaps = widgets.some((widget) => {
                const widgetX = Number(widget[keys.x] ?? 0);
                const widgetY = Number(widget[keys.y] ?? 0);
                const widgetW = Number(widget[keys.w] ?? 1);
                const widgetH = Number(widget[keys.h] ?? 1);

                return (
                    x < widgetX + widgetW &&
                    x + width > widgetX &&
                    y < widgetY + widgetH &&
                    y + height > widgetY
                );
            });

            if (!overlaps) {
                return { x, y };
            }
        }
    }

    return { x: 0, y: widgets.length };
};

const normalizeWidgetLayout = (widgets: any[]) => {
    return widgets.map((widget) => {
        const normalized = { ...widget };

        // Ensure widgets have the new h:2 base for squares if they were h:1
        if (normalized.type !== 'section') {
            if (Number(normalized.h ?? 0) === 1) {
                normalized.h = 2;
                normalized.y = Number(normalized.y ?? 0) * 2;
            }
            if (Number(normalized.h_mobile ?? 0) === 1) {
                normalized.h_mobile = 2;
                normalized.y_mobile = Number(normalized.y_mobile ?? 0) * 2;
            }
        } else {
            // Sections should be h:1 (half height)
            normalized.h = 1;
            normalized.h_mobile = 1;
        }

        return normalized;
    });
};

const resizeWidget = (
    widgetId: string | number,
    mode: 'desktop' | 'mobile',
    size: { w: number; h: number },
) => {
    const layout =
        mode === 'desktop' ? desktopLayout.value : mobileLayout.value;
    const item = layout.find((i) => i.i === String(widgetId));
    const columns = mode === 'desktop' ? 4 : 2;
    const width = Math.min(size.w, columns);

    if (item) {
        if (item.x + width > columns) {
            item.x = 0;
            let maxY = 0;
            for (const i of layout) {
                if (i.y + i.h > maxY) maxY = i.y + i.h;
            }
            item.y = maxY;
        }
        item.w = width;
        item.h = size.h;
    }

    if (mode === 'desktop') {
        desktopLayout.value = [...desktopLayout.value];
        nextTick(() => syncWidgetsFromDesktop());
    } else {
        mobileLayout.value = [...mobileLayout.value];
        nextTick(() => syncWidgetsFromMobile());
    }
};

const widgetSizeOptions = (widget: any, mode: 'desktop' | 'mobile') => {
    if (widget.type === 'section') {
        return sectionSizeOptions[mode];
    }

    return widgetSizeOptionList;
};

onUnmounted(() => {
    if (avatarPreviewUrl.value) {
        URL.revokeObjectURL(avatarPreviewUrl.value);
    }
});

watch(
    () => props.link.widgets,
    (newVal) => {
        if (!isEditing.value) {
            localWidgets.value = normalizeWidgetLayout(
                JSON.parse(JSON.stringify(newVal || [])),
            );
            updateLayoutsFromWidgets();
        }
    },
    { immediate: true },
);

const toggleEdit = () => {
    if (isEditing.value) {
        editForm.value.display_name = editForm.value.display_name.replace(
            /\s+/g,
            '',
        );
        // Save link data
        router.post(
            `/links/${props.link.slug}`,
            {
                _method: 'put',
                display_name: editForm.value.display_name,
                bio: editForm.value.bio,
                avatar: avatarFile.value,
                remove_avatar: avatarRemoved.value,
            },
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    if (avatarPreviewUrl.value) {
                        URL.revokeObjectURL(avatarPreviewUrl.value);
                        avatarPreviewUrl.value = null;
                    }
                    avatarFile.value = null;
                    avatarRemoved.value = false;
                    // Sync widgets data
                    router.post(
                        `/links/${props.link.slug}/widgets/sync`,
                        { widgets: localWidgets.value },
                        {
                            preserveScroll: true,
                            onSuccess: () => {
                                isEditing.value = false;
                                activeWidgetId.value = null;
                            },
                        },
                    );
                },
            },
        );
    } else {
        isEditing.value = true;
    }
};

const displayInitial = computed(() => {
    return (editForm.value.display_name.trim().charAt(0) || 'G').toUpperCase();
});
const profileAvatarUrl = computed(() => {
    if (avatarPreviewUrl.value) {
        return avatarPreviewUrl.value;
    }

    if (avatarRemoved.value) {
        return null;
    }

    return props.link.avatar_url ?? null;
});
const showPrivateNotice = computed(() => {
    return (
        Boolean(isOwner.value) &&
        !props.link.is_published &&
        localWidgets.value.length > 0
    );
});

const updateAvatar = (file: File | null) => {
    if (!file) return;

    if (avatarPreviewUrl.value) {
        URL.revokeObjectURL(avatarPreviewUrl.value);
    }

    avatarFile.value = file;
    avatarRemoved.value = false;
    avatarPreviewUrl.value = URL.createObjectURL(file);
};

const removeAvatar = () => {
    if (avatarPreviewUrl.value) {
        URL.revokeObjectURL(avatarPreviewUrl.value);
    }

    avatarFile.value = null;
    avatarPreviewUrl.value = null;
    avatarRemoved.value = true;
};

const publishLink = () => {
    router.post(
        `/links/${props.link.slug}`,
        {
            _method: 'put',
            display_name: editForm.value.display_name.replace(/\s+/g, ''),
            bio: editForm.value.bio,
            is_published: true,
        },
        {
            preserveScroll: true,
        },
    );
};

const updateWidgetTitle = (widget: any, value: string) => {
    if (widget.type === 'section') {
        widget.content = value;
    } else {
        if (!widget.settings) widget.settings = {};
        widget.settings.title = value;
    }
    markDirty();
};

const uploadWidgetImage = async (widget: any, file: File) => {
    try {
        const formData = new FormData();
        formData.append('image', file);
        const response = await axios.post('/widgets/upload-image', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        widget.thumbnail_url = response.data.url;
        markDirty();
    } catch (error) {
        console.error('Failed to upload image:', error);
        alert('画像のアップロードに失敗しました。');
    }
};

const removeWidgetImage = (widget: any) => {
    widget.thumbnail_url = null;
    markDirty();
};

const updateWidgetLink = (widget: any) => {
    linkTargetWidget.value = widget;
    showAddLinkModal.value = true;
};

const toggleImageCrop = (widgetId: string | number) => {
    croppingWidgetId.value =
        croppingWidgetId.value === widgetId ? null : widgetId;
    activeWidgetId.value = widgetId;
};

const setControlsLock = (widgetId: string | number, isOpen: boolean) => {
    lockedControlsWidgetId.value = isOpen ? widgetId : null;
};

const updateImageWidgetCrop = (widget: any, crop: { x: number; y: number }) => {
    if (!widget.settings) widget.settings = {};

    widget.settings.cropX = crop.x;
    widget.settings.cropY = crop.y;
    markDirty();
};

const updateTextWidgetBackgroundColor = (widget: any, color: string) => {
    if (!widget.settings) widget.settings = {};

    widget.settings.bgColor = color;
    markDirty();
};

const updateTextWidgetAlign = (
    widget: any,
    align: 'left' | 'center' | 'right',
) => {
    if (!widget.settings) widget.settings = {};

    widget.settings.textAlign = align;
    markDirty();
};

const updateTextWidgetVerticalAlign = (
    widget: any,
    align: 'start' | 'center' | 'end',
) => {
    if (!widget.settings) widget.settings = {};

    widget.settings.verticalAlign = align;
    markDirty();
};

const updateWidgetSensitive = (widget: any, isSensitive: boolean) => {
    if (!widget.settings) widget.settings = {};

    widget.settings.sensitive = isSensitive;
    markDirty();
};

const addSectionWidget = () => {
    const desktopPosition = findNextGridPosition(localWidgets.value, 4, 4, 1, {
        x: 'x',
        y: 'y',
        w: 'w',
        h: 'h',
    });
    const mobilePosition = findNextGridPosition(localWidgets.value, 2, 2, 1, {
        x: 'x_mobile',
        y: 'y_mobile',
        w: 'w_mobile',
        h: 'h_mobile',
    });
    const newWidget = {
        id: `temp_section_${Date.now()}`,
        type: 'section',
        content: null,
        thumbnail_url: null,
        x: desktopPosition.x,
        y: desktopPosition.y,
        w: 4,
        h: 1,
        x_mobile: mobilePosition.x,
        y_mobile: mobilePosition.y,
        w_mobile: 2,
        h_mobile: 1,
        settings: {
            title: '',
        },
    };

    localWidgets.value.push(newWidget);

    updateLayoutsFromWidgets();
    scrollToWidgetBottom();
};

const showAddLinkModal = ref(false);
const linkTargetWidget = ref<any | null>(null);
const pendingWidgetPosition = ref<{
    x: number;
    y: number;
    w: number;
    h: number;
} | null>(null);
const openAddLinkModal = () => {
    linkTargetWidget.value = null;
    pendingWidgetPosition.value = null;
    showAddLinkModal.value = true;
};
const openModalWithPos = (x: number, y: number, w: number, h: number) => {
    linkTargetWidget.value = null;
    pendingWidgetPosition.value = { x, y, w, h };
    showAddLinkModal.value = true;
};

const closeAddLinkModal = () => {
    showAddLinkModal.value = false;
    linkTargetWidget.value = null;
    pendingWidgetPosition.value = null;
};

const linkModalTitle = computed(() => {
    if (!linkTargetWidget.value) {
        return 'リンクを追加';
    }

    if (linkTargetWidget.value.type === 'text') {
        return 'テキストリンクを設定';
    }

    if (linkTargetWidget.value.type === 'image') {
        return '画像リンクを設定';
    }

    return 'リンクを設定';
});

const activeWidgetId = ref<number | string | null>(null);
const hoveredWidgetId = ref<number | string | null>(null);
const lockedControlsWidgetId = ref<number | string | null>(null);
const sensitiveTargetWidget = ref<any | null>(null);

const markDirty = () => {};

const addMediaWidget = async (file: File) => {
    const isImageFile =
        file.type.startsWith('image/') ||
        /\.(apng|gif|png|jpe?g|webp|avif|svg)$/i.test(file.name);

    if (!isImageFile) {
        alert('画像ファイルを選択してください。');

        return;
    }

    try {
        const formData = new FormData();
        formData.append('image', file);

        const response = await axios.post('/widgets/upload-image', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        const desktopPosition = findNextGridPosition(
            localWidgets.value,
            4,
            1,
            2,
            { x: 'x', y: 'y', w: 'w', h: 'h' },
        );
        const mobilePosition = findNextGridPosition(
            localWidgets.value,
            2,
            1,
            2,
            { x: 'x_mobile', y: 'y_mobile', w: 'w_mobile', h: 'h_mobile' },
        );
        const newWidget = {
            id: `temp_media_${Date.now()}`,
            type: 'image',
            content: null,
            thumbnail_url: response.data.url,
            x: desktopPosition.x,
            y: desktopPosition.y,
            w: 1,
            h: 2,
            x_mobile: mobilePosition.x,
            y_mobile: mobilePosition.y,
            w_mobile: 1,
            h_mobile: 2,
            settings: {
                title: '',
                cropX: 50,
                cropY: 50,
            },
        };

        localWidgets.value.push(newWidget);

        updateLayoutsFromWidgets();
        scrollToWidgetBottom();
    } catch (error) {
        console.error('Failed to upload media:', error);
        alert('メディアのアップロードに失敗しました。');
    }
};

const addTextWidget = () => {
    const desktopPosition = findNextGridPosition(localWidgets.value, 4, 1, 2, {
        x: 'x',
        y: 'y',
        w: 'w',
        h: 'h',
    });
    const mobilePosition = findNextGridPosition(localWidgets.value, 2, 1, 2, {
        x: 'x_mobile',
        y: 'y_mobile',
        w: 'w_mobile',
        h: 'h_mobile',
    });
    const newWidget = {
        id: `temp_text_${Date.now()}`,
        type: 'text',
        content: null,
        thumbnail_url: null,
        x: desktopPosition.x,
        y: desktopPosition.y,
        w: 1,
        h: 2,
        x_mobile: mobilePosition.x,
        y_mobile: mobilePosition.y,
        w_mobile: 1,
        h_mobile: 2,
        settings: {
            title: '',
            bgColor: '#FFFFFF',
            textAlign: 'left',
            verticalAlign: 'center',
        },
    };

    localWidgets.value.push(newWidget);

    updateLayoutsFromWidgets();
    scrollToWidgetBottom();
};

const addLinkWidget = async (url: string, isSensitive = false) => {
    showAddLinkModal.value = false;

    if (linkTargetWidget.value) {
        linkTargetWidget.value.content = url || null;
        if (!linkTargetWidget.value.settings) {
            linkTargetWidget.value.settings = {};
        }
        linkTargetWidget.value.settings.sensitive = Boolean(url && isSensitive);
        linkTargetWidget.value = null;
        markDirty();

        return;
    }

    const pos = pendingWidgetPosition.value;
    const w = pos ? pos.w : 1;
    const h = pos ? pos.h : 2;

    try {
        const { data } = await axios.post('/fetch-ogp', { url });

        const desktopPosition = pos
            ? pos
            : findNextGridPosition(localWidgets.value, 4, w, h, {
                  x: 'x',
                  y: 'y',
                  w: 'w',
                  h: 'h',
              });
        const mobilePosition = pos
            ? pos
            : findNextGridPosition(localWidgets.value, 2, w, h, {
                  x: 'x_mobile',
                  y: 'y_mobile',
                  w: 'w_mobile',
                  h: 'h_mobile',
              });

        const newWidget = {
            id: `temp_${Date.now()}`,
            type: 'link',
            content: url,
            thumbnail_url: data.thumbnail_url,
            x: desktopPosition.x,
            y: desktopPosition.y,
            w: w,
            h: h,
            x_mobile: mobilePosition.x,
            y_mobile: mobilePosition.y,
            w_mobile: w,
            h_mobile: h,

            settings: {
                title: data.title,
                aspectClass: 'aspect-video',
                sensitive: isSensitive,
            },
        };

        localWidgets.value.push(newWidget);

        updateLayoutsFromWidgets();
        scrollToWidgetBottom();
    } catch (e) {
        console.error('Failed to fetch OGP', e);

        const desktopPosition = pos
            ? pos
            : findNextGridPosition(localWidgets.value, 4, w, h, {
                  x: 'x',
                  y: 'y',
                  w: 'w',
                  h: 'h',
              });
        const mobilePosition = pos
            ? pos
            : findNextGridPosition(localWidgets.value, 2, w, h, {
                  x: 'x_mobile',
                  y: 'y_mobile',
                  w: 'w_mobile',
                  h: 'h_mobile',
              });

        const newWidget = {
            id: `temp_${Date.now()}`,
            type: 'link',
            content: url,
            x: desktopPosition.x,
            y: desktopPosition.y,
            w: w,
            h: h,
            x_mobile: mobilePosition.x,
            y_mobile: mobilePosition.y,
            w_mobile: w,
            h_mobile: h,

            settings: {
                title: url,
                aspectClass: 'aspect-video',
                sensitive: isSensitive,
            },
        };
        localWidgets.value.push(newWidget);

        updateLayoutsFromWidgets();
        scrollToWidgetBottom();
    } finally {
        pendingWidgetPosition.value = null;
    }
};

const deleteWidget = (widgetId: number | string) => {
    localWidgets.value = localWidgets.value.filter(
        (widget) => String(widget.id) !== String(widgetId),
    );
    updateLayoutsFromWidgets();
    if (String(activeWidgetId.value) === String(widgetId)) {
        activeWidgetId.value = null;
    }
    if (String(croppingWidgetId.value) === String(widgetId)) {
        croppingWidgetId.value = null;
    }
};

const handlePageClick = (event: MouseEvent) => {
    const target = event.target as HTMLElement | null;

    if (!target?.closest('.vgl-item')) {
        activeWidgetId.value = null;
        croppingWidgetId.value = null;
    }
};

const isSensitiveWidget = (widget: any) => {
    return Boolean(widget.settings?.sensitive);
};

const openSensitiveWarning = (widget: any) => {
    if (!widget.content) return;

    sensitiveTargetWidget.value = widget;
};

const closeSensitiveWarning = () => {
    sensitiveTargetWidget.value = null;
};

const continueToSensitiveLink = () => {
    const url = sensitiveTargetWidget.value?.content;

    if (!url) return;

    window.open(url, '_blank', 'noopener,noreferrer');
    sensitiveTargetWidget.value = null;
};
</script>

<style>
.link-page--editing .vgl-item:not(.vgl-item--resizing) {
    cursor: grab !important;
}
.link-page--editing .vgl-item.vgl-item--dragging {
    cursor: grabbing !important;
    z-index: 100 !important;
}
.link-page--editing .vgl-item:has(.link-widget-controls) {
    z-index: 150 !important;
}
.link-page:not(.link-page--editing) .vgl-item,
.link-page:not(.link-page--editing) .vgl-item * {
    cursor: default !important;
}
.cursor-pointer,
.vgl-item a,
.vgl-item button,
.link-page:not(.link-page--editing) .vgl-item a,
.link-page:not(.link-page--editing) .vgl-item a * {
    cursor: pointer !important;
}
.vgl-item--placeholder,
.vue-grid-item.vue-grid-placeholder {
    background: rgb(209 213 219 / 0.8) !important;
    border-radius: 1rem !important;
    border: 2px dashed rgb(156 163 175) !important;
    transition-duration: 100ms;
    z-index: 2;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    -o-user-select: none;
    user-select: none;
}
.vgl-item:not(.vgl-item--dragging):not(.vgl-item--resizing) {
    transition:
        transform 0.2s ease,
        width 0.2s ease,
        height 0.2s ease !important;
}
</style>

<template>
    <Head :title="props.link.display_name">
        <link v-if="profileAvatarUrl" rel="icon" :href="profileAvatarUrl" />
        <link
            v-if="profileAvatarUrl"
            rel="apple-touch-icon"
            :href="profileAvatarUrl"
        />
    </Head>

    <main
        @click="handlePageClick"
        class="link-page text-gray-950 transition-colors duration-300"
        :class="[
            'min-h-screen bg-white',
            isEditing ? 'link-page--editing' : '',
        ]"
    >
        <LinkPageNavigation :slug="props.link.slug" active-tab="profile" />

        <a
            v-if="!isOwner"
            href="/"
            class="fixed bottom-4 left-4 z-30 text-sm text-gray-400 transition-colors hover:text-gray-700"
        >
            Built with GridLink
        </a>

        <!-- Floating Toolbar for Owner -->
        <LinkToolbar
            v-if="isOwner"
            :is-editing="isEditing"
            v-model:preview-mode="previewMode"
            :support-url="`/@${props.link.slug}/support`"
            @toggle-edit="toggleEdit"
            @add-link="openAddLinkModal"
            @add-media="addMediaWidget"
            @add-text="addTextWidget"
            @add-section="addSectionWidget"
        />

        <div
            v-if="croppingWidgetId"
            class="pointer-events-none fixed inset-0 z-[70] bg-gray-200/75"
        ></div>

        <div
            class="transition-all duration-300"
            :class="[
                previewMode === 'mobile'
                    ? 'min-h-screen px-5 pt-14'
                    : 'min-h-screen px-5 pt-14 min-[1025px]:pt-20 sm:px-8',
            ]"
        >
            <div
                id="profile"
                ref="pageScrollContainer"
                class="relative mx-auto w-full max-w-[1198px] transition-all duration-300"
                :class="[
                    previewMode === 'mobile'
                        ? 'flex w-full max-w-[374px] flex-col gap-4 pb-32'
                        : 'flex w-full flex-col pb-32 min-[1025px]:flex-row min-[1025px]:justify-center min-[1025px]:gap-8',
                ]"
            >
                <LinkProfile
                    v-model:display-name="editForm.display_name"
                    v-model:bio="editForm.bio"
                    :is-editing="isEditing"
                    :preview-mode="previewMode"
                    :avatar-url="profileAvatarUrl"
                    :display-initial="displayInitial"
                    :show-private-notice="showPrivateNotice"
                    @update:avatar="updateAvatar"
                    @remove:avatar="removeAvatar"
                    @publish="publishLink"
                />

                <section
                    id="grid"
                    class="relative mx-auto w-full max-w-[374px] pt-0 transition-all duration-300 min-[1025px]:mx-0 min-[1025px]:max-w-none"
                    :class="
                        previewMode === 'desktop'
                            ? 'pb-24 min-[1025px]:w-[864px] min-[1025px]:shrink-0'
                            : 'w-full pb-24'
                    "
                >
                    <div
                        v-if="localWidgets.length === 0 && isEditing"
                        class="absolute top-0 left-0 z-20 grid w-[864px] grid-cols-4 gap-4 px-2 pt-2 max-[1024px]:hidden"
                        style="grid-auto-rows: 92px"
                        :class="previewMode === 'desktop' ? 'block' : 'hidden'"
                    >
                        <button
                            @click="openModalWithPos(0, 0, 2, 2)"
                            class="relative col-span-2 row-span-2 flex h-full w-full cursor-pointer flex-col items-center justify-center rounded-[32px] border-2 border-dashed border-gray-200 text-slate-400 transition-colors hover:bg-gray-50"
                        >
                            <div class="absolute top-4 right-4 text-gray-300">
                                <Plus class="size-4" />
                            </div>
                            <LinkIcon class="mb-2 size-6" />
                            <span class="text-xs font-semibold">Add Link</span>
                        </button>
                        <button
                            @click="openModalWithPos(2, 0, 1, 2)"
                            class="relative col-span-1 row-span-2 flex h-full w-full flex-col items-center justify-center rounded-[32px] border-2 border-dashed border-gray-200 text-slate-400 transition-colors hover:bg-gray-50"
                        >
                            <div class="absolute top-4 right-4 text-gray-300">
                                <Plus class="size-4" />
                            </div>
                            <Music class="mb-2 size-6" />
                            <span class="text-xs font-semibold">Add Music</span>
                        </button>
                        <button
                            @click="openModalWithPos(0, 2, 2, 4)"
                            class="relative col-span-2 row-span-4 flex h-full w-full cursor-pointer flex-col items-center justify-center rounded-[32px] border-2 border-dashed border-gray-200 text-slate-400 transition-colors hover:bg-gray-50"
                        >
                            <div class="absolute top-4 right-4 text-gray-300">
                                <Plus class="size-4" />
                            </div>
                            <ImageIcon class="mb-2 size-6" />
                            <span class="text-xs font-semibold">Add Media</span>
                        </button>
                    </div>

                    <div
                        v-if="localWidgets.length === 0 && isEditing"
                        class="absolute top-0 left-1/2 z-20 grid w-full max-w-[356px] -translate-x-1/2 grid-cols-2 gap-4 px-2 pt-2"
                        style="grid-auto-rows: 73px"
                        :class="
                            previewMode === 'desktop'
                                ? 'hidden max-[1024px]:grid'
                                : 'grid'
                        "
                    >
                        <button
                            @click="openModalWithPos(0, 0, 2, 2)"
                            class="relative col-span-2 row-span-2 flex h-full w-full cursor-pointer flex-col items-center justify-center rounded-[32px] border-2 border-dashed border-gray-200 text-slate-400 transition-colors hover:bg-gray-50"
                        >
                            <div class="absolute top-4 right-4 text-gray-300">
                                <Plus class="size-4" />
                            </div>
                            <LinkIcon class="mb-2 size-6" />
                            <span class="text-xs font-semibold">Add Link</span>
                        </button>
                        <button
                            @click="openModalWithPos(0, 2, 1, 2)"
                            class="relative col-span-1 row-span-2 flex h-full w-full flex-col items-center justify-center rounded-[32px] border-2 border-dashed border-gray-200 text-slate-400 transition-colors hover:bg-gray-50"
                        >
                            <div class="absolute top-4 right-4 text-gray-300">
                                <Plus class="size-4" />
                            </div>
                            <Music class="mb-2 size-6" />
                            <span class="text-xs font-semibold">Add Music</span>
                        </button>
                        <button
                            @click="openModalWithPos(0, 4, 2, 4)"
                            class="relative col-span-2 row-span-4 flex h-full w-full cursor-pointer flex-col items-center justify-center rounded-[32px] border-2 border-dashed border-gray-200 text-slate-400 transition-colors hover:bg-gray-50"
                        >
                            <div class="absolute top-4 right-4 text-gray-300">
                                <Plus class="size-4" />
                            </div>
                            <ImageIcon class="mb-2 size-6" />
                            <span class="text-xs font-semibold">Add Media</span>
                        </button>
                    </div>
                    <!-- Desktop Grid -->
                    <GridLayout
                        v-model:layout="desktopLayout"
                        :col-num="4"
                        :row-height="99"
                        :margin="[8, 8]"
                        :is-draggable="isEditing && !croppingWidgetId"
                        :is-resizable="false"
                        :vertical-compact="true"
                        :use-css-transforms="true"
                        @layout-updated="syncWidgetsFromDesktop"
                        class="w-[864px] max-[1024px]:hidden"
                        :class="previewMode === 'desktop' ? 'block' : 'hidden'"
                    >
                        <GridItem
                            v-for="item in desktopLayout"
                            :key="`desktop-${item.i}`"
                            :x="item.x"
                            :y="item.y"
                            :w="item.w"
                            :h="item.h"
                            :i="item.i"
                            @drag-start="draggingWidgetId = item.i"
                            @drag-end="draggingWidgetId = null"
                            @mouseenter="hoveredWidgetId = item.i"
                            @mouseleave="hoveredWidgetId = null"
                            class="group"
                            :class="[
                                croppingWidgetId === item.i ? 'z-[90]' : '',
                                draggingWidgetId === item.i
                                    ? 'z-[130]'
                                    : hoveredWidgetId === item.i ||
                                        croppingWidgetId === item.i ||
                                        lockedControlsWidgetId === item.i
                                      ? 'z-[120]'
                                      : 'z-10',
                                isEditing && draggingWidgetId !== item.i
                                    ? 'cursor-grab'
                                    : '',
                                isEditing && draggingWidgetId === item.i
                                    ? 'cursor-grabbing'
                                    : '',
                            ]"
                        >
                            <div class="h-full w-full">
                                <component
                                    :is="
                                        item.widget.content &&
                                        item.widget.type !== 'section' &&
                                        !isSensitiveWidget(item.widget) &&
                                        !isEditing
                                            ? 'a'
                                            : 'div'
                                    "
                                    :href="
                                        item.widget.content &&
                                        item.widget.type !== 'section' &&
                                        !isSensitiveWidget(item.widget) &&
                                        !isEditing
                                            ? item.widget.content
                                            : undefined
                                    "
                                    :target="
                                        item.widget.content &&
                                        item.widget.type !== 'section' &&
                                        !isSensitiveWidget(item.widget) &&
                                        !isEditing
                                            ? '_blank'
                                            : undefined
                                    "
                                    rel="noopener noreferrer"
                                    @click="
                                        isEditing
                                            ? (activeWidgetId =
                                                  activeWidgetId === item.i
                                                      ? null
                                                      : item.i)
                                            : item.widget.content &&
                                                item.widget.type !==
                                                    'section' &&
                                                isSensitiveWidget(item.widget)
                                              ? openSensitiveWarning(
                                                    item.widget,
                                                )
                                              : null
                                    "
                                    class="relative block h-full w-full transition-transform duration-150 will-change-transform"
                                    :class="[
                                        item.widget.content &&
                                        item.widget.type !== 'section' &&
                                        !isEditing
                                            ? 'cursor-pointer'
                                            : '',
                                        item.widget.type === 'section' &&
                                        !isEditing
                                            ? ''
                                            : 'hover:scale-[1.015] active:scale-[0.99]',
                                        draggingWidgetId == item.i
                                            ? 'scale-[1.025]'
                                            : '',
                                    ]"
                                >
                                    <LinkWidgetControls
                                        v-if="
                                            isEditing &&
                                            (hoveredWidgetId === item.i ||
                                                croppingWidgetId === item.i ||
                                                lockedControlsWidgetId ===
                                                    item.i)
                                        "
                                        :widget="item.widget"
                                        mode="desktop"
                                        :size-options="
                                            widgetSizeOptions(
                                                item.widget,
                                                'desktop',
                                            )
                                        "
                                        :is-cropping="
                                            croppingWidgetId === item.i
                                        "
                                        @delete="deleteWidget(item.i)"
                                        @lock-open="
                                            setControlsLock(item.i, $event)
                                        "
                                        @edit-link="
                                            updateWidgetLink(item.widget)
                                        "
                                        @toggle-crop="toggleImageCrop(item.i)"
                                        @update-background-color="
                                            updateTextWidgetBackgroundColor(
                                                item.widget,
                                                $event,
                                            )
                                        "
                                        @update-text-align="
                                            updateTextWidgetAlign(
                                                item.widget,
                                                $event,
                                            )
                                        "
                                        @update-vertical-align="
                                            updateTextWidgetVerticalAlign(
                                                item.widget,
                                                $event,
                                            )
                                        "
                                        @update-sensitive="
                                            updateWidgetSensitive(
                                                item.widget,
                                                $event,
                                            )
                                        "
                                        @resize="
                                            resizeWidget(
                                                item.i,
                                                'desktop',
                                                $event,
                                            )
                                        "
                                    />

                                    <LinkWidgetContent
                                        :widget="item.widget"
                                        mode="desktop"
                                        :is-editing="isEditing"
                                        :is-active="activeWidgetId === item.i"
                                        :is-cropping="
                                            croppingWidgetId === item.i
                                        "
                                        :profile-image-url="profileAvatarUrl"
                                        @activate="activeWidgetId = item.i"
                                        @update-title="
                                            updateWidgetTitle(
                                                item.widget,
                                                $event,
                                            )
                                        "
                                        @update-crop="
                                            updateImageWidgetCrop(
                                                item.widget,
                                                $event,
                                            )
                                        "
                                        @upload-image="
                                            uploadWidgetImage(
                                                item.widget,
                                                $event,
                                            )
                                        "
                                        @remove-image="
                                            removeWidgetImage(item.widget)
                                        "
                                    />
                                </component>
                            </div>
                        </GridItem>
                    </GridLayout>

                    <!-- Mobile Grid -->
                    <GridLayout
                        v-model:layout="mobileLayout"
                        :col-num="2"
                        :row-height="79"
                        :margin="[8, 8]"
                        :is-draggable="isEditing && !croppingWidgetId"
                        :is-resizable="false"
                        :vertical-compact="true"
                        :use-css-transforms="true"
                        @layout-updated="syncWidgetsFromMobile"
                        class="mx-auto w-full max-w-[356px]"
                        :class="
                            previewMode === 'desktop'
                                ? 'hidden max-[1024px]:block'
                                : 'block'
                        "
                    >
                        <GridItem
                            v-for="item in mobileLayout"
                            :key="`mobile-${item.i}`"
                            :x="item.x"
                            :y="item.y"
                            :w="item.w"
                            :h="item.h"
                            :i="item.i"
                            @drag-start="draggingWidgetId = item.i"
                            @drag-end="draggingWidgetId = null"
                            @mouseenter="hoveredWidgetId = item.i"
                            @mouseleave="hoveredWidgetId = null"
                            class="group"
                            :class="[
                                croppingWidgetId === item.i ? 'z-[120]' : '',
                                draggingWidgetId === item.i
                                    ? 'z-[130]'
                                    : hoveredWidgetId === item.i ||
                                        croppingWidgetId === item.i ||
                                        lockedControlsWidgetId === item.i
                                      ? 'z-[120]'
                                      : 'z-10',
                                isEditing && draggingWidgetId !== item.i
                                    ? 'cursor-grab'
                                    : '',
                                isEditing && draggingWidgetId === item.i
                                    ? 'cursor-grabbing'
                                    : '',
                            ]"
                        >
                            <div class="h-full w-full">
                                <component
                                    :is="
                                        item.widget.content &&
                                        item.widget.type !== 'section' &&
                                        !isSensitiveWidget(item.widget) &&
                                        !isEditing
                                            ? 'a'
                                            : 'div'
                                    "
                                    :href="
                                        item.widget.content &&
                                        item.widget.type !== 'section' &&
                                        !isSensitiveWidget(item.widget) &&
                                        !isEditing
                                            ? item.widget.content
                                            : undefined
                                    "
                                    :target="
                                        item.widget.content &&
                                        item.widget.type !== 'section' &&
                                        !isSensitiveWidget(item.widget) &&
                                        !isEditing
                                            ? '_blank'
                                            : undefined
                                    "
                                    rel="noopener noreferrer"
                                    class="relative block h-full w-full overflow-visible"
                                    :class="[
                                        item.widget.content &&
                                        item.widget.type !== 'section' &&
                                        !isEditing
                                            ? 'cursor-pointer'
                                            : '',
                                        draggingWidgetId == item.i
                                            ? 'z-50'
                                            : '',
                                    ]"
                                    @click="
                                        isEditing
                                            ? (activeWidgetId =
                                                  activeWidgetId === item.i
                                                      ? null
                                                      : item.i)
                                            : item.widget.content &&
                                                item.widget.type !==
                                                    'section' &&
                                                isSensitiveWidget(item.widget)
                                              ? openSensitiveWarning(
                                                    item.widget,
                                                )
                                              : null
                                    "
                                >
                                    <LinkWidgetControls
                                        v-if="
                                            isEditing &&
                                            (hoveredWidgetId === item.i ||
                                                croppingWidgetId === item.i ||
                                                lockedControlsWidgetId ===
                                                    item.i)
                                        "
                                        :widget="item.widget"
                                        mode="mobile"
                                        :size-options="
                                            widgetSizeOptions(
                                                item.widget,
                                                'mobile',
                                            )
                                        "
                                        :is-cropping="
                                            croppingWidgetId === item.i
                                        "
                                        @delete="deleteWidget(item.i)"
                                        @lock-open="
                                            setControlsLock(item.i, $event)
                                        "
                                        @edit-link="
                                            updateWidgetLink(item.widget)
                                        "
                                        @toggle-crop="toggleImageCrop(item.i)"
                                        @update-background-color="
                                            updateTextWidgetBackgroundColor(
                                                item.widget,
                                                $event,
                                            )
                                        "
                                        @update-text-align="
                                            updateTextWidgetAlign(
                                                item.widget,
                                                $event,
                                            )
                                        "
                                        @update-vertical-align="
                                            updateTextWidgetVerticalAlign(
                                                item.widget,
                                                $event,
                                            )
                                        "
                                        @update-sensitive="
                                            updateWidgetSensitive(
                                                item.widget,
                                                $event,
                                            )
                                        "
                                        @resize="
                                            resizeWidget(
                                                item.i,
                                                'mobile',
                                                $event,
                                            )
                                        "
                                    />
                                    <LinkWidgetContent
                                        :widget="item.widget"
                                        mode="mobile"
                                        :is-editing="isEditing"
                                        :is-active="activeWidgetId === item.i"
                                        :is-cropping="
                                            croppingWidgetId === item.i
                                        "
                                        :profile-image-url="profileAvatarUrl"
                                        @activate="activeWidgetId = item.i"
                                        @update-title="
                                            updateWidgetTitle(
                                                item.widget,
                                                $event,
                                            )
                                        "
                                        @update-crop="
                                            updateImageWidgetCrop(
                                                item.widget,
                                                $event,
                                            )
                                        "
                                        @upload-image="
                                            uploadWidgetImage(
                                                item.widget,
                                                $event,
                                            )
                                        "
                                        @remove-image="
                                            removeWidgetImage(item.widget)
                                        "
                                    />
                                </component>
                            </div>
                        </GridItem>
                    </GridLayout>
                </section>
            </div>
        </div>

        <LinkAddModal
            :show="showAddLinkModal"
            :initial-url="linkTargetWidget?.content"
            :initial-sensitive="Boolean(linkTargetWidget?.settings?.sensitive)"
            :allow-empty="
                Boolean(linkTargetWidget && linkTargetWidget.type !== 'link')
            "
            :title="linkModalTitle"
            :submit-label="linkTargetWidget ? '設定' : '追加'"
            @close="closeAddLinkModal"
            @add="addLinkWidget"
        />

        <div
            v-if="sensitiveTargetWidget"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
        >
            <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-xl">
                <h3 class="text-xl font-bold text-gray-950">
                    センシティブなコンテンツ
                </h3>
                <p class="mt-3 text-sm leading-6 text-gray-600">
                    このリンクはセンシティブなコンテンツを含む可能性があります。内容を確認したうえでリンクを開いてください。
                </p>
                <div class="mt-6 flex gap-3">
                    <button
                        type="button"
                        class="flex-1 rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-50"
                        @click="closeSensitiveWarning"
                    >
                        キャンセル
                    </button>
                    <button
                        type="button"
                        class="flex-1 rounded-xl bg-[#292929] px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-black"
                        @click="continueToSensitiveLink"
                    >
                        リンクを開く
                    </button>
                </div>
            </div>
        </div>

        <Toaster />
    </main>
</template>
