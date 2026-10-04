<script setup lang="ts">
import LinkAddModal from '@/components/LinkAddModal.vue';
import ProfileWidget from '@/components/ProfileWidget.vue';
import WidgetControls from '@/components/WidgetControls.vue';
import {
    apiBaseUrl,
    apiGet,
    apiJSON,
    apiUrl,
    type CurrentUser,
    type GridLink,
    type LinkWidget,
} from '@/lib/api';
import { usePreferredReducedMotion } from '@vueuse/core';
import { GridItem, GridLayout } from 'grid-layout-plus';
import {
    Check,
    Copy,
    Crop,
    Flag,
    Image as ImageIcon,
    LayoutGrid,
    Link as LinkIcon,
    MapPin,
    MessageCircleHeart,
    Monitor,
    Moon,
    MoreHorizontal,
    Move,
    Palette,
    PartyPopper,
    Pencil,
    Plus,
    Save,
    Smartphone,
    Sun,
    Trash2,
    Type,
    X,
} from 'lucide-vue-next';
import { useMotionValue, useSpring } from 'motion-v';
import {
    computed,
    nextTick,
    onMounted,
    onUnmounted,
    reactive,
    ref,
    watch,
} from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const link = ref<GridLink | null>(null);
const user = ref<CurrentUser | null>(null);
const isOwner = ref(false);
const isEditing = ref(false);
const isLoading = ref(true);
const error = ref('');
const previewMode = ref<'desktop' | 'mobile'>('desktop');
const isPreviewLayoutSwitching = ref(false);
const copied = ref(false);
const saving = ref(false);
const hasUnsavedChanges = ref(false);
const sensitiveTarget = ref<LinkWidget | null>(null);
const showAddLinkModal = ref(false);
const linkTargetWidget = ref<LinkWidget | null>(null);
const activeWidgetId = ref<string | number | null>(null);
const draggingWidgetId = ref<string | number | null>(null);
const draggingWidgetMode = ref<'desktop' | 'mobile' | null>(null);
const suppressWidgetClickUntil = ref(0);
const croppingWidgetId = ref<string | number | null>(null);
const activeMapMovingWidgetId = ref<string | number | null>(null);
const lockedControlsWidgetId = ref<string | number | null>(null);
const editName = ref('');
const editBio = ref('');
const pageTheme = ref<'light' | 'dark'>('light');
const widgetStyle = ref<'sharp' | 'soft' | 'rounded'>('rounded');
const viewportSmall = ref(false);
const mediaInput = ref<HTMLInputElement | null>(null);
const avatarInput = ref<HTMLInputElement | null>(null);
const widgetThumbnailInput = ref<HTMLInputElement | null>(null);
const thumbnailTargetWidget = ref<LinkWidget | null>(null);
const nameEditor = ref<HTMLElement | null>(null);
const bioEditor = ref<HTMLElement | null>(null);
const isNameFocused = ref(false);
const isBioFocused = ref(false);
const mobileLinkEditorWidget = ref<LinkWidget | null>(null);
const mobileImageEditorWidget = ref<LinkWidget | null>(null);
const mobileMapEditorWidget = ref<LinkWidget | null>(null);
const mobileTextEditorWidget = ref<LinkWidget | null>(null);
const mobileSectionEditorWidget = ref<LinkWidget | null>(null);
const mobileTextEditorMode = ref<'add' | 'edit'>('edit');
const mobileSectionEditorMode = ref<'add' | 'edit'>('edit');
const showMobileAddLinkSheet = ref(false);
const showStylePanel = ref(false);
const stylePanelRef = ref<HTMLElement | null>(null);
const mobileAddLinkUrl = ref('');
const mobileAddLinkSensitive = ref(false);
const mobileAddLinkError = ref('');
const mobileSectionEditorError = ref('');
const mobileImageInput = ref<HTMLInputElement | null>(null);
const showPublishConfetti = ref(false);
const newlyAddedWidgetIds = ref<Set<string>>(new Set());
const desktopLayout = ref<any[]>([]);
const mobileLayout = ref<any[]>([]);
const hoveredWidgetId = ref<string | number | null>(null);
const maxWidgets = 50;
const maxTitleLength = 100;
const maxTextLength = 4500;
const maxUploadBytes = 5 * 1024 * 1024;
const prefersReducedMotion = usePreferredReducedMotion();
let dragSettleTimeout: number | null = null;
let suppressWidgetClickTimeout: number | null = null;
let previewLayoutSwitchTimeout: number | null = null;
const dragPointerState = ref<{
    widgetId: string | number;
    mode: 'desktop' | 'mobile';
    startX: number;
    startY: number;
    lastX: number;
    lastY: number;
    isDragging: boolean;
} | null>(null);
const dragVisualState = ref<{
    widgetId: string | number;
    mode: 'desktop' | 'mobile';
    boxShadow: string;
} | null>(null);
const motionValues = {
    x: useMotionValue(0),
    y: useMotionValue(0),
    rotate: useMotionValue(0),
    scale: useMotionValue(1),
    shadow: useMotionValue(0),
};
const springValues = {
    x: useSpring(motionValues.x, { stiffness: 420, damping: 32 }),
    y: useSpring(motionValues.y, { stiffness: 420, damping: 32 }),
    rotate: useSpring(motionValues.rotate, { stiffness: 520, damping: 36 }),
    scale: useSpring(motionValues.scale, { stiffness: 420, damping: 28 }),
    shadow: useSpring(motionValues.shadow, { stiffness: 420, damping: 34 }),
};
const dragSpringValues = reactive({
    x: 0,
    y: 0,
    rotate: 0,
    scale: 1,
    shadow: 0,
});

springValues.x.on('change', (value) => {
    dragSpringValues.x = value;
    if (!prefersReducedMotion.value) {
        const velocity = springValues.x.getVelocity();
        const rotateTarget = Math.max(Math.min(velocity * 0.006, 15), -15);
        motionValues.rotate.set(rotateTarget);
    }
});
springValues.y.on('change', (value) => (dragSpringValues.y = value));
springValues.rotate.on('change', (value) => (dragSpringValues.rotate = value));
springValues.scale.on('change', (value) => (dragSpringValues.scale = value));
springValues.shadow.on('change', (value) => (dragSpringValues.shadow = value));

const dragSpring = {
    values: dragSpringValues,
    set(targets: Partial<typeof dragSpringValues>) {
        if (prefersReducedMotion.value) {
            Object.assign(dragSpringValues, targets);
            for (const key in targets) {
                const value = targets[key as keyof typeof dragSpringValues];
                if (value !== undefined) {
                    motionValues[key as keyof typeof motionValues].set(value);
                    springValues[key as keyof typeof springValues].set(value);
                }
            }
            return;
        }
        for (const [key, target] of Object.entries(targets)) {
            if (target !== undefined && key !== 'rotate') {
                motionValues[key as keyof typeof motionValues].set(
                    target as number,
                );
            }
        }
    },
};

const slug = computed(() => String(route.params.slug || ''));
const hasWebDisplay = computed(() => Boolean(link.value?.has_web_display));
const forceMobileForVisitor = computed(
    () => !isOwner.value && !hasWebDisplay.value,
);
const activeMode = computed<'desktop' | 'mobile'>(() =>
    forceMobileForVisitor.value || viewportSmall.value
        ? 'mobile'
        : previewMode.value,
);
const pageThemeClasses = computed(() =>
    pageTheme.value === 'dark'
        ? 'bg-[#111111] text-white'
        : 'bg-white text-gray-950',
);
const cornerClass = computed(() =>
    widgetStyle.value === 'sharp'
        ? 'rounded-none'
        : widgetStyle.value === 'soft'
          ? 'rounded-xl'
          : 'rounded-2xl',
);
const displayInitial = computed(() =>
    (editName.value.trim().charAt(0) || 'G').toUpperCase(),
);
const profileUrl = computed(() => `${window.location.origin}/@${slug.value}`);
const widgets = computed<LinkWidget[]>(() =>
    Array.isArray(link.value?.widgets) ? link.value.widgets : [],
);

const desktopWidgets = computed(() => widgets.value);
const mobileWidgets = computed(() => widgets.value);
const activeWidget = computed(
    () =>
        widgets.value.find(
            (widget) => String(widget.id) === String(activeWidgetId.value),
        ) || null,
);
const activeMobileLayoutItem = computed(() => {
    if (
        !viewportSmall.value ||
        !isEditing.value ||
        activeWidgetId.value === null
    ) {
        return null;
    }

    return (
        mobileLayout.value.find(
            (item) => String(item.i) === String(activeWidgetId.value),
        ) || null
    );
});
const activeMobileWidget = computed(
    () => activeMobileLayoutItem.value?.widget || null,
);
const mobileWidgetOperationActive = computed(() =>
    Boolean(activeMobileLayoutItem.value),
);
const activeWidgetLabel = computed(() => {
    if (!activeWidget.value) return '';
    if (activeWidget.value.type === 'link') return 'リンク';
    if (activeWidget.value.type === 'image') return '画像';
    if (activeWidget.value.type === 'text') return 'テキスト';
    if (activeWidget.value.type === 'map') return '地図';
    if (activeWidget.value.type === 'section') return 'セクション';
    return 'ウィジェット';
});

const updateLayoutsFromWidgets = () => {
    desktopLayout.value = widgets.value.map((widget) => ({
        i: String(widget.id),
        x: Number(widget.x ?? 0),
        y: Number(widget.y ?? 0),
        w: Number(widget.w ?? 1),
        h: Number(widget.h ?? 2),
        widget,
    }));
    mobileLayout.value = widgets.value.map((widget) => ({
        i: String(widget.id),
        x: Number(widget.x_mobile ?? 0),
        y: Number(widget.y_mobile ?? 0),
        w: Number(widget.w_mobile ?? 1),
        h: Number(widget.h_mobile ?? 2),
        widget,
    }));
};

const syncWidgetsFromLayout = (mode: 'desktop' | 'mobile') => {
    const layout =
        mode === 'desktop' ? desktopLayout.value : mobileLayout.value;
    for (const item of layout) {
        const widget = widgets.value.find(
            (candidate) => String(candidate.id) === String(item.i),
        );
        if (!widget) continue;
        if (mode === 'desktop') {
            widget.x = item.x;
            widget.y = item.y;
            widget.w = item.w;
            widget.h = item.h;
        } else {
            widget.x_mobile = item.x;
            widget.y_mobile = item.y;
            widget.w_mobile = item.w;
            widget.h_mobile = item.h;
        }
    }
};

const syncWidgetsFromDesktop = () => syncWidgetsFromLayout('desktop');
const syncWidgetsFromMobile = () => syncWidgetsFromLayout('mobile');

const mobileGridRowHeight = computed(() => {
    if (typeof window === 'undefined') return 84.5;
    const contentWidth = Math.min(window.innerWidth - 40, 374);
    const gridWidth = contentWidth + 24;
    const colWidth = (gridWidth - 3 * 12) / 2;
    return colWidth * (84.5 / 181);
});

const desktopPlaceholderItems = computed(() => {
    if (widgets.value.length >= 3) return [];
    const sourceWidgets = widgets.value.map((widget) => ({ ...widget }));
    const placeholders = [
        {
            i: 'placeholder-media',
            type: 'media',
            label: 'メディアを追加する',
            icon: ImageIcon,
            w: 2,
            h: 4,
        },
        {
            i: 'placeholder-link',
            type: 'link',
            label: 'リンクを追加する',
            icon: LinkIcon,
            w: 1,
            h: 4,
        },
        {
            i: 'placeholder-text',
            type: 'text',
            label: 'テキストを追加する',
            icon: Type,
            w: 1,
            h: 2,
        },
    ];

    return placeholders
        .filter((placeholder) => {
            const widgetType =
                placeholder.type === 'media' ? 'image' : placeholder.type;
            return !widgets.value.some((widget) => widget.type === widgetType);
        })
        .map((placeholder) => {
            const position = findNextGridPosition(
                sourceWidgets,
                4,
                placeholder.w,
                placeholder.h,
                {
                    x: 'x',
                    y: 'y',
                    w: 'w',
                    h: 'h',
                },
            );
            const placedWidget = {
                x: position.x,
                y: position.y,
                w: placeholder.w,
                h: placeholder.h,
            };
            sourceWidgets.push(placedWidget as LinkWidget);
            return { ...placeholder, ...placedWidget };
        });
});

const mobilePlaceholderItems = computed(() => {
    if (widgets.value.length >= 3) return [];
    if (widgets.value.some((widget) => widget.type === 'link')) return [];
    const position = findNextGridPosition(widgets.value, 2, 1, 4, {
        x: 'x_mobile',
        y: 'y_mobile',
        w: 'w_mobile',
        h: 'h_mobile',
    });

    return [
        {
            i: 'placeholder-mobile-link',
            type: 'link',
            label: 'リンクを追加する',
            icon: LinkIcon,
            x: position.x,
            y: position.y,
            w: 1,
            h: 4,
        },
    ];
});

const mobileWidgetPreviewStyle = (widget: LinkWidget | null) => {
    if (!widget) {
        return { height: '250px', width: '250px' };
    }
    const w = Math.max(1, Number(widget.w_mobile ?? 2));
    const h = Math.max(1, Number(widget.h_mobile ?? 4));
    const cellWidth = (Math.min(window.innerWidth - 40, 374) + 24 - 36) / 2;
    const width = Math.min(306, cellWidth * w + 12 * Math.max(0, w - 1));
    const height = Math.min(
        420,
        mobileGridRowHeight.value * h + 12 * Math.max(0, h - 1),
    );

    return {
        width: `${Math.max(140, width)}px`,
        height: `${Math.max(84, height)}px`,
    };
};

const sizeOptions = computed(() => [
    { key: 'inline', label: 'インライン', size: { w: 2, h: 1 } },
    { key: 'small', label: '1x1', size: { w: 1, h: 2 } },
    { key: 'wide', label: '2x1', size: { w: 2, h: 2 } },
    { key: 'tall', label: '1x2', size: { w: 1, h: 4 } },
    { key: 'large', label: '2x2', size: { w: 2, h: 4 } },
]);
const sectionSizeOptions = {
    desktop: [
        { key: 'section-compact', label: '1x2', size: { w: 2, h: 1 } },
        { key: 'section-wide', label: '1x4', size: { w: 4, h: 1 } },
    ],
    mobile: [
        { key: 'section-compact', label: '1x2', size: { w: 1, h: 1 } },
        { key: 'section-wide', label: '1x4', size: { w: 2, h: 1 } },
    ],
};
const widgetSizeOptions = (widget: LinkWidget, mode: 'desktop' | 'mobile') => {
    if (widget.type === 'section') {
        return sectionSizeOptions[mode];
    }
    if (widget.type !== 'link') {
        return sizeOptions.value.filter((option) => option.key !== 'inline');
    }
    return sizeOptions.value;
};
const themeOptions = [
    { value: 'light', label: 'ライト', icon: Sun },
    { value: 'dark', label: 'ダーク', icon: Moon },
] as const;
const widgetStyleOptions = [
    { value: 'sharp', label: 'シェイプ', class: 'rounded-none' },
    { value: 'soft', label: 'ソフト', class: 'rounded-md' },
    { value: 'rounded', label: '角丸', class: 'rounded-full' },
] as const;

const findNextGridPosition = (
    sourceWidgets: LinkWidget[],
    columns: number,
    width: number,
    height: number,
    keys: {
        x: 'x' | 'x_mobile';
        y: 'y' | 'y_mobile';
        w: 'w' | 'w_mobile';
        h: 'h' | 'h_mobile';
    },
) => {
    const maxX = Math.max(columns - width, 0);
    const maxY = sourceWidgets.reduce((max, widget) => {
        return Math.max(
            max,
            Number(widget[keys.y] ?? 0) + Number(widget[keys.h] ?? 1),
        );
    }, 0);

    for (let y = 0; y < maxY + 1000; y += 1) {
        for (let x = 0; x <= maxX; x += 1) {
            const overlaps = sourceWidgets.some((widget) => {
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

    return { x: 0, y: maxY };
};

const markWidgetAsNewlyAdded = (widget: LinkWidget) => {
    const widgetId = String(widget.id);
    newlyAddedWidgetIds.value = new Set([
        ...newlyAddedWidgetIds.value,
        widgetId,
    ]);

    window.setTimeout(() => {
        const nextWidgetIds = new Set(newlyAddedWidgetIds.value);
        nextWidgetIds.delete(widgetId);
        newlyAddedWidgetIds.value = nextWidgetIds;
    }, 600);
};

const markDirty = () => {
    if (isEditing.value) {
        hasUnsavedChanges.value = true;
    }
};

const addWidget = (widget: Omit<LinkWidget, 'id'>) => {
    if (!link.value) return;
    const currentWidgets = Array.isArray(link.value.widgets)
        ? link.value.widgets
        : [];
    if (currentWidgets.length >= maxWidgets) {
        window.alert(`ウィジェットは${maxWidgets}個まで追加できます。`);
        return;
    }
    const newWidget = {
        ...widget,
        id: `temp_${Date.now()}_${Math.random().toString(36).slice(2)}`,
    };
    link.value.widgets = [...currentWidgets, newWidget];
    updateLayoutsFromWidgets();
    markWidgetAsNewlyAdded(newWidget);
    markDirty();
};

const addPreparedWidget = (widget: LinkWidget) => {
    if (!link.value) return false;
    const currentWidgets = Array.isArray(link.value.widgets)
        ? link.value.widgets
        : [];
    if (currentWidgets.length >= maxWidgets) {
        window.alert(`ウィジェットは${maxWidgets}個まで追加できます。`);
        return false;
    }
    link.value.widgets = [...currentWidgets, widget];
    updateLayoutsFromWidgets();
    markWidgetAsNewlyAdded(widget);
    markDirty();
    return true;
};

const widgetPositions = (
    desktopSize: { w: number; h: number },
    mobileSize: { w: number; h: number },
) => {
    const currentWidgets = widgets.value;
    const desktop = findNextGridPosition(
        currentWidgets,
        4,
        desktopSize.w,
        desktopSize.h,
        {
            x: 'x',
            y: 'y',
            w: 'w',
            h: 'h',
        },
    );
    const mobile = findNextGridPosition(
        currentWidgets,
        2,
        mobileSize.w,
        mobileSize.h,
        {
            x: 'x_mobile',
            y: 'y_mobile',
            w: 'w_mobile',
            h: 'h_mobile',
        },
    );

    return {
        x: desktop.x,
        y: desktop.y,
        w: desktopSize.w,
        h: desktopSize.h,
        x_mobile: mobile.x,
        y_mobile: mobile.y,
        w_mobile: mobileSize.w,
        h_mobile: mobileSize.h,
    };
};

const openAddLinkModal = () => {
    if (viewportSmall.value && isEditing.value) {
        mobileAddLinkUrl.value = '';
        mobileAddLinkSensitive.value = false;
        mobileAddLinkError.value = '';
        showMobileAddLinkSheet.value = true;
        return;
    }
    linkTargetWidget.value = null;
    showAddLinkModal.value = true;
};

const closeAddLinkModal = () => {
    showAddLinkModal.value = false;
    linkTargetWidget.value = null;
};

const absolutizeUrl = (value: string) => {
    try {
        return new URL(value, window.location.origin).toString();
    } catch {
        return value;
    }
};

const fetchOGP = async (url: string) => {
    try {
        return await apiJSON<{
            title?: string;
            thumbnail_url?: string | null;
            url?: string;
        }>('/fetch-ogp', 'POST', { url });
    } catch {
        return null;
    }
};

const addLinkWidget = async (url: string, isSensitiveValue = false) => {
    const targetWidget = linkTargetWidget.value;
    showAddLinkModal.value = false;
    const normalized = url.trim();
    if (targetWidget) {
        targetWidget.content = normalized || null;
        targetWidget.settings = {
            ...(targetWidget.settings || {}),
            sensitive: Boolean(normalized && isSensitiveValue),
        };
        linkTargetWidget.value = null;
        markDirty();
        return;
    }
    if (!normalized) return;
    let parsed: URL;
    try {
        parsed = new URL(normalized);
    } catch {
        window.alert('有効なURLを入力してください。');
        return;
    }
    const ogp = await fetchOGP(parsed.toString());
    const title = String(
        ogp?.title || parsed.hostname.replace(/^www\./, ''),
    ).slice(0, maxTitleLength);
    const thumbnailUrl = ogp?.thumbnail_url
        ? absolutizeUrl(ogp.thumbnail_url)
        : null;

    addWidget({
        type: 'link',
        content: parsed.toString(),
        thumbnail_url: thumbnailUrl,
        ...widgetPositions({ w: 1, h: 4 }, { w: 1, h: 4 }),
        settings: {
            title,
            aspectClass: 'aspect-video',
            sensitive: isSensitiveValue,
        },
    });
};

const addTextWidget = () => {
    const widget = {
        id: `temp_${Date.now()}_${Math.random().toString(36).slice(2)}`,
        type: 'text',
        content: null,
        thumbnail_url: null,
        ...widgetPositions({ w: 1, h: 2 }, { w: 1, h: 2 }),
        settings: {
            title:
                viewportSmall.value && isEditing.value ? '' : 'テキストを入力',
            bgColor: '#FFFFFF',
            textAlign: 'left',
            verticalAlign: 'center',
        },
    } as LinkWidget;

    if (viewportSmall.value && isEditing.value) {
        closeMobileEditors();
        mobileTextEditorMode.value = 'add';
        mobileTextEditorWidget.value = widget;
        return;
    }

    addPreparedWidget(widget);
};

const addSectionWidget = () => {
    const widget = {
        id: `temp_${Date.now()}_${Math.random().toString(36).slice(2)}`,
        type: 'section',
        content: viewportSmall.value && isEditing.value ? '' : 'セクション',
        thumbnail_url: null,
        ...widgetPositions({ w: 4, h: 1 }, { w: 2, h: 1 }),
        settings: {
            title: '',
        },
    } as LinkWidget;

    if (viewportSmall.value && isEditing.value) {
        closeMobileEditors();
        mobileSectionEditorMode.value = 'add';
        mobileSectionEditorError.value = '';
        mobileSectionEditorWidget.value = widget;
        return;
    }

    addPreparedWidget(widget);
};

const addMapWidget = () => {
    addWidget({
        type: 'map',
        content: null,
        thumbnail_url: null,
        ...widgetPositions({ w: 2, h: 4 }, { w: 2, h: 4 }),
        settings: {
            title: '東京タワー',
            address: '東京都港区芝公園4丁目2-8',
            lat: 35.6585805,
            lng: 139.7454329,
            zoom: 15,
        },
    });
};

const openMediaPicker = () => {
    mediaInput.value?.click();
};

const addMediaWidget = async (event: Event) => {
    if (!link.value) return;
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    input.value = '';
    if (!file) return;

    const compressed = await compressImageFile(file, 'card');
    if (!compressed) return;
    const formData = new FormData();
    formData.append('image', compressed);
    const response = await fetch(`${apiBaseUrl}/widgets/upload-image`, {
        method: 'POST',
        credentials: 'include',
        body: formData,
    });
    if (!response.ok) {
        window.alert('画像のアップロードに失敗しました。');
        return;
    }
    const data = (await response.json()) as { url: string };
    addWidget({
        type: 'image',
        content: null,
        thumbnail_url: data.url,
        ...widgetPositions({ w: 2, h: 4 }, { w: 2, h: 4 }),
        settings: {
            title: '',
            cropX: 50,
            cropY: 50,
        },
    });
};

const browserSupportsWebp = () => {
    const canvas = document.createElement('canvas');
    canvas.width = 1;
    canvas.height = 1;
    return canvas.toDataURL('image/webp').startsWith('data:image/webp');
};

const outputMimeType = (file: File) =>
    file.type === 'image/jpeg' || !browserSupportsWebp()
        ? 'image/jpeg'
        : 'image/webp';

const outputFileName = (fileName: string, mimeType: string) => {
    const extension = mimeType === 'image/jpeg' ? 'jpg' : 'webp';
    return `${fileName.replace(/\.[^.]+$/, '')}.${extension}`;
};

const isAnimatedImageFile = (file: File) => {
    const fileName = file.name.toLowerCase();
    return (
        file.type === 'image/gif' ||
        file.type === 'image/apng' ||
        fileName.endsWith('.gif') ||
        fileName.endsWith('.apng')
    );
};

const compressImageFile = (file: File, preset: 'avatar' | 'card' = 'card') =>
    new Promise<File | null>((resolve) => {
        if (file.size >= maxUploadBytes) {
            window.alert('画像は5MB未満のファイルを選択してください。');
            resolve(null);
            return;
        }
        if (!file.type.startsWith('image/') || isAnimatedImageFile(file)) {
            resolve(file);
            return;
        }
        const maxSize = preset === 'avatar' ? 512 : 1600;
        const mimeType = outputMimeType(file);
        const image = new Image();
        const objectUrl = URL.createObjectURL(file);
        image.onload = () => {
            URL.revokeObjectURL(objectUrl);
            const scale = Math.min(
                1,
                maxSize / Math.max(image.width, image.height),
            );
            const canvas = document.createElement('canvas');
            canvas.width = Math.max(1, Math.round(image.width * scale));
            canvas.height = Math.max(1, Math.round(image.height * scale));
            const context = canvas.getContext('2d');
            if (!context) {
                resolve(file);
                return;
            }
            context.drawImage(image, 0, 0, canvas.width, canvas.height);
            canvas.toBlob(
                (blob) => {
                    if (!blob) {
                        resolve(file);
                        return;
                    }
                    if (blob.size >= maxUploadBytes) {
                        window.alert('画像は5MB未満に圧縮できませんでした。');
                        resolve(null);
                        return;
                    }
                    resolve(
                        new File([blob], outputFileName(file.name, mimeType), {
                            type: mimeType,
                            lastModified: Date.now(),
                        }),
                    );
                },
                mimeType,
                0.8,
            );
        };
        image.onerror = () => {
            URL.revokeObjectURL(objectUrl);
            resolve(file);
        };
        image.src = objectUrl;
    });

const uploadImageFile = async (file: File) => {
    const formData = new FormData();
    formData.append('image', file);
    const response = await fetch(`${apiBaseUrl}/widgets/upload-image`, {
        method: 'POST',
        credentials: 'include',
        body: formData,
    });
    if (!response.ok) {
        throw new Error('upload failed');
    }
    return (await response.json()) as { url: string };
};

const openWidgetThumbnailPicker = (widget: LinkWidget) => {
    thumbnailTargetWidget.value = widget;
    widgetThumbnailInput.value?.click();
};

const updateWidgetThumbnail = async (event: Event) => {
    const targetWidget = thumbnailTargetWidget.value;
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    input.value = '';
    thumbnailTargetWidget.value = null;
    if (!targetWidget || !file) return;
    try {
        const compressed = await compressImageFile(file, 'card');
        if (!compressed) return;
        const data = await uploadImageFile(compressed);
        targetWidget.thumbnail_url = data.url;
        markDirty();
    } catch {
        window.alert('画像の差し替えに失敗しました。');
    }
};

const uploadLinkWidgetImage = async (widget: LinkWidget, file: File) => {
    try {
        const compressed = await compressImageFile(file, 'card');
        if (!compressed) return;
        const data = await uploadImageFile(compressed);
        widget.thumbnail_url = data.url;
        markDirty();
    } catch {
        window.alert('画像の差し替えに失敗しました。');
    }
};

const removeLinkWidgetImage = (widget: LinkWidget) => {
    widget.thumbnail_url = null;
    markDirty();
};

const updateAvatar = async (event: Event) => {
    if (!link.value) return;
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    input.value = '';
    if (!file) return;
    try {
        const compressed = await compressImageFile(file, 'avatar');
        if (!compressed) return;
        const data = await uploadImageFile(compressed);
        link.value.avatar_url = data.url;
        markDirty();
    } catch {
        window.alert('プロフィール画像のアップロードに失敗しました。');
    }
};

const deleteAvatar = () => {
    if (!link.value) return;
    link.value.avatar_url = null;
    markDirty();
};

const deleteWidget = (widget: LinkWidget) => {
    if (!link.value) return;
    link.value.widgets = link.value.widgets.filter(
        (item) => String(item.id) !== String(widget.id),
    );
    activeWidgetId.value = null;
    croppingWidgetId.value = null;
    updateLayoutsFromWidgets();
    markDirty();
};

const ensureSettings = (widget: LinkWidget) => {
    widget.settings = widget.settings || {};
    return widget.settings;
};

const updateWidgetTitle = (widget: LinkWidget, title: string) => {
    const limitedTitle = title.slice(
        0,
        widget.type === 'link' ? maxTitleLength : maxTextLength,
    );
    if (widget.type === 'section') {
        widget.content = limitedTitle;
        markDirty();
        return;
    }
    ensureSettings(widget).title = limitedTitle;
    markDirty();
};

const updateEditableName = (event: Event) => {
    editName.value = ((event.target as HTMLElement).innerText || '').slice(
        0,
        80,
    );
    markDirty();
};

const updateEditableBio = (event: Event) => {
    editBio.value = ((event.target as HTMLElement).innerText || '').slice(
        0,
        600,
    );
    markDirty();
};

const syncProfileEditorText = (
    editor: HTMLElement | null,
    value: string,
    isFocused: boolean,
) => {
    if (!editor || isFocused) return;
    if (editor.innerText !== value) {
        editor.innerText = value;
    }
};

const syncProfileEditors = () => {
    syncProfileEditorText(
        nameEditor.value,
        editName.value,
        isNameFocused.value,
    );
    syncProfileEditorText(bioEditor.value, editBio.value, isBioFocused.value);
};

const pasteProfilePlainText = (event: ClipboardEvent, singleLine = false) => {
    event.preventDefault();
    const text = event.clipboardData?.getData('text/plain') ?? '';
    document.execCommand(
        'insertText',
        false,
        singleLine ? text.replace(/[\r\n]+/g, ' ') : text,
    );
    if (singleLine) {
        updateEditableName(event);
    } else {
        updateEditableBio(event);
    }
};

const limitProfileBeforeInput = (
    event: InputEvent,
    maxLength: number,
    singleLine = false,
) => {
    if (singleLine && event.inputType === 'insertLineBreak') {
        event.preventDefault();
        return;
    }
    if (!event.data) return;
    const target = event.target as HTMLElement;
    const selectedLength = window.getSelection()?.toString().length ?? 0;
    if (
        target.innerText.length - selectedLength + event.data.length >
        maxLength
    ) {
        event.preventDefault();
    }
};

const updateTextWidgetBackgroundColor = (widget: LinkWidget, color: string) => {
    ensureSettings(widget).bgColor = color;
    markDirty();
};

const updateTextWidgetAlign = (
    widget: LinkWidget,
    align: 'left' | 'center' | 'right',
) => {
    ensureSettings(widget).textAlign = align;
    markDirty();
};

const updateTextWidgetVerticalAlign = (
    widget: LinkWidget,
    align: 'start' | 'center' | 'end',
) => {
    ensureSettings(widget).verticalAlign = align;
    markDirty();
};

const updateImageWidgetCrop = (
    widget: LinkWidget,
    crop: { x: number; y: number },
) => {
    const settings = ensureSettings(widget);
    settings.cropX = crop.x;
    settings.cropY = crop.y;
    markDirty();
};

const updateWidgetSensitive = (
    widget: LinkWidget,
    isSensitiveValue: boolean,
) => {
    ensureSettings(widget).sensitive = isSensitiveValue;
    markDirty();
};

const updateWidgetEmbedMode = (
    widget: LinkWidget,
    mode: 'link' | 'link_embed' | 'embed',
) => {
    ensureSettings(widget).youtubeMode = mode;
    markDirty();
};

const updateMapWidgetLocation = (
    widget: LinkWidget,
    location: {
        title: string;
        address: string;
        lat: number;
        lng: number;
        zoom: number;
    },
) => {
    const settings = ensureSettings(widget);
    settings.title = location.title;
    settings.address = location.address;
    settings.lat = location.lat;
    settings.lng = location.lng;
    settings.zoom = location.zoom;
    markDirty();
};

const updateMapWidgetZoom = (widget: LinkWidget, delta: number) => {
    const settings = ensureSettings(widget);
    const currentZoom = Number(settings.zoom ?? 15);
    settings.zoom = Math.min(19, Math.max(3, currentZoom + delta));
    markDirty();
};

const updateMapWidgetCenter = (
    widget: LinkWidget,
    center: { lat: number; lng: number; zoom: number },
) => {
    const settings = ensureSettings(widget);
    settings.lat = center.lat;
    settings.lng = center.lng;
    settings.zoom = center.zoom;
    markDirty();
};

const openWidgetLinkSettings = (widget: LinkWidget) => {
    linkTargetWidget.value = widget;
    showAddLinkModal.value = true;
};

const closeMobileEditors = () => {
    mobileLinkEditorWidget.value = null;
    mobileImageEditorWidget.value = null;
    mobileMapEditorWidget.value = null;
    mobileTextEditorWidget.value = null;
    mobileSectionEditorWidget.value = null;
    mobileTextEditorMode.value = 'edit';
    mobileSectionEditorMode.value = 'edit';
    mobileSectionEditorError.value = '';
    croppingWidgetId.value = null;
    activeMapMovingWidgetId.value = null;
};

const closeMobileAddLinkSheet = () => {
    showMobileAddLinkSheet.value = false;
    mobileAddLinkUrl.value = '';
    mobileAddLinkSensitive.value = false;
    mobileAddLinkError.value = '';
};

const submitMobileAddLink = async () => {
    const url = mobileAddLinkUrl.value.trim();
    if (!url) {
        mobileAddLinkError.value = 'URLを入力してください';
        return;
    }
    try {
        new URL(url);
    } catch {
        mobileAddLinkError.value = '有効なURLを入力してください';
        return;
    }
    closeMobileAddLinkSheet();
    await addLinkWidget(url, mobileAddLinkSensitive.value);
};

const editMobileWidget = (widget: LinkWidget) => {
    if (!viewportSmall.value) return;
    closeMobileEditors();
    ensureSettings(widget);
    activeWidgetId.value = widget.id;
    if (widget.type === 'link') {
        mobileLinkEditorWidget.value = widget;
        return;
    }
    if (widget.type === 'image') {
        mobileImageEditorWidget.value = widget;
        return;
    }
    if (widget.type === 'map') {
        mobileMapEditorWidget.value = widget;
        return;
    }
    if (widget.type === 'text') {
        mobileTextEditorMode.value = 'edit';
        mobileTextEditorWidget.value = widget;
        return;
    }
    if (widget.type === 'section') {
        mobileSectionEditorMode.value = 'edit';
        mobileSectionEditorWidget.value = widget;
    }
};

const updateMobileLinkTitle = (event: Event) => {
    if (!mobileLinkEditorWidget.value) return;
    updateWidgetTitle(
        mobileLinkEditorWidget.value,
        (event.target as HTMLInputElement).value,
    );
};

const chooseMobileLinkImage = () => {
    if (!mobileLinkEditorWidget.value) return;
    openWidgetThumbnailPicker(mobileLinkEditorWidget.value);
};

const removeMobileLinkImage = () => {
    if (!mobileLinkEditorWidget.value) return;
    mobileLinkEditorWidget.value.thumbnail_url = null;
    markDirty();
};

const updateMobileLinkSensitive = () => {
    if (!mobileLinkEditorWidget.value) return;
    updateWidgetSensitive(
        mobileLinkEditorWidget.value,
        !Boolean(mobileLinkEditorWidget.value.settings?.sensitive),
    );
};

const chooseMobileImage = () => {
    mobileImageInput.value?.click();
};

const updateMobileImage = async (event: Event) => {
    const widget = mobileImageEditorWidget.value;
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    input.value = '';
    if (!widget || !file) return;
    try {
        const compressed = await compressImageFile(file, 'card');
        if (!compressed) return;
        const data = await uploadImageFile(compressed);
        widget.thumbnail_url = data.url;
        ensureSettings(widget).cropX = 50;
        ensureSettings(widget).cropY = 50;
        markDirty();
    } catch {
        window.alert('画像の差し替えに失敗しました。');
    }
};

const updateMobileImageCaption = (event: Event) => {
    if (!mobileImageEditorWidget.value) return;
    updateWidgetTitle(
        mobileImageEditorWidget.value,
        (event.target as HTMLInputElement).value,
    );
};

const updateMobileImageLink = (event: Event) => {
    if (!mobileImageEditorWidget.value) return;
    const value = (event.target as HTMLInputElement).value.trim();
    mobileImageEditorWidget.value.content = value || null;
    if (!value) {
        ensureSettings(mobileImageEditorWidget.value).sensitive = false;
    }
    markDirty();
};

const updateMobileImageSensitive = () => {
    if (!mobileImageEditorWidget.value?.content) return;
    updateWidgetSensitive(
        mobileImageEditorWidget.value,
        !Boolean(mobileImageEditorWidget.value.settings?.sensitive),
    );
};

const updateMobileMapTitle = (event: Event) => {
    if (!mobileMapEditorWidget.value) return;
    updateWidgetTitle(
        mobileMapEditorWidget.value,
        (event.target as HTMLInputElement).value,
    );
};

const updateMobileTextContent = (event: Event) => {
    if (!mobileTextEditorWidget.value) return;
    updateWidgetTitle(
        mobileTextEditorWidget.value,
        ((event.target as HTMLElement).innerText || '').slice(0, maxTextLength),
    );
};

const updateMobileTextLink = (event: Event) => {
    if (!mobileTextEditorWidget.value) return;
    const value = (event.target as HTMLInputElement).value.trim();
    mobileTextEditorWidget.value.content = value || null;
    if (!value) {
        ensureSettings(mobileTextEditorWidget.value).sensitive = false;
    }
    markDirty();
};

const updateMobileTextSensitive = () => {
    if (!mobileTextEditorWidget.value?.content) return;
    updateWidgetSensitive(
        mobileTextEditorWidget.value,
        !Boolean(mobileTextEditorWidget.value.settings?.sensitive),
    );
};

const updateMobileSectionTitle = (event: Event) => {
    if (!mobileSectionEditorWidget.value) return;
    mobileSectionEditorError.value = '';
    updateWidgetTitle(
        mobileSectionEditorWidget.value,
        (event.target as HTMLInputElement).value,
    );
};

const completeMobileTextEditor = () => {
    if (!mobileTextEditorWidget.value) return;
    if (mobileTextEditorMode.value === 'add') {
        addPreparedWidget(mobileTextEditorWidget.value);
    }
    closeMobileEditors();
};

const closeMobileTextEditor = () => {
    closeMobileEditors();
};

const completeMobileSectionEditor = () => {
    const widget = mobileSectionEditorWidget.value;
    if (!widget) return;
    const title = String(widget.content ?? widget.settings?.title ?? '').trim();
    if (!title) {
        mobileSectionEditorError.value = 'セクションを入力してください';
        return;
    }
    widget.content = title;
    if (mobileSectionEditorMode.value === 'add') {
        addPreparedWidget(widget);
    }
    closeMobileEditors();
};

const closeMobileSectionEditor = () => {
    closeMobileEditors();
};

const resizeWidget = async (
    widget: LinkWidget,
    mode: 'desktop' | 'mobile',
    size: { w: number; h: number },
) => {
    const wasCropping = croppingWidgetId.value !== null;
    croppingWidgetId.value = null;
    activeMapMovingWidgetId.value = null;
    if (wasCropping) {
        await nextTick();
    }

    const layout =
        mode === 'desktop' ? desktopLayout.value : mobileLayout.value;
    const item = layout.find(
        (candidate) => String(candidate.i) === String(widget.id),
    );
    const columns = mode === 'desktop' ? 4 : 2;
    const width = Math.min(size.w, columns);

    if (item) {
        if (item.x + width > columns) {
            item.x = Math.max(columns - width, 0);
        }
        item.w = width;
        item.h = size.h;
    }

    const pushCollidingItems = (target: any) => {
        for (const other of layout) {
            if (other.i === target.i) continue;
            const overlaps =
                target.x < other.x + other.w &&
                target.x + target.w > other.x &&
                target.y < other.y + other.h &&
                target.y + target.h > other.y;
            if (overlaps) {
                other.y = target.y + target.h;
                pushCollidingItems(other);
            }
        }
    };

    if (item) {
        pushCollidingItems(item);
        layout.sort((a, b) => {
            if (a.i === item.i) return -1;
            if (b.i === item.i) return 1;
            return a.y === b.y ? a.x - b.x : a.y - b.y;
        });
    }

    if (mode === 'desktop') {
        desktopLayout.value = [...layout];
        nextTick(() => syncWidgetsFromDesktop());
    } else {
        mobileLayout.value = [...layout];
        nextTick(() => syncWidgetsFromMobile());
    }
    markDirty();
};

const closeMobileWidgetSheet = () => {
    activeWidgetId.value = null;
    croppingWidgetId.value = null;
    activeMapMovingWidgetId.value = null;
    lockedControlsWidgetId.value = null;
};

const toggleMapMove = (widget: LinkWidget) => {
    const widgetId = String(widget.id);
    activeMapMovingWidgetId.value =
        String(activeMapMovingWidgetId.value) === widgetId ? null : widgetId;
    croppingWidgetId.value = null;
    markDirty();
};

const closeMapMove = (widget: LinkWidget) => {
    if (String(activeMapMovingWidgetId.value) === String(widget.id)) {
        activeMapMovingWidgetId.value = null;
    }
};

const setControlsLock = (widget: LinkWidget, isOpen: boolean) => {
    lockedControlsWidgetId.value = isOpen ? String(widget.id) : null;
};

const updateDraggedWidgetVisual = (
    mode: 'desktop' | 'mobile',
    widgetId: string | number,
    horizontalOffset: number,
    deltaX = 0,
) => {
    const isReducedMotion = prefersReducedMotion.value;
    const clampedOffset = Math.max(Math.min(horizontalOffset, 48), -48);
    const rotateTarget = Math.max(Math.min(deltaX * 1.2, 25), -25);

    dragSpring.set({
        x: isReducedMotion ? clampedOffset * 0.012 : clampedOffset * 0.022,
        y: isReducedMotion ? -2 : -5,
        rotate: isReducedMotion ? rotateTarget * 0.3 : rotateTarget,
        scale: isReducedMotion ? 1.008 : 1.015,
        shadow: isReducedMotion ? 0.08 : 0.1,
    });

    dragVisualState.value = {
        widgetId,
        mode,
        boxShadow: '',
    };
};

const draggedWidgetStyle = (
    mode: 'desktop' | 'mobile',
    widgetId: string | number,
) => {
    const state = dragVisualState.value;
    if (
        !state ||
        state.mode !== mode ||
        String(state.widgetId) !== String(widgetId)
    ) {
        return undefined;
    }

    return {
        transform: `translate3d(${dragSpring.values.x}px, ${dragSpring.values.y}px, 0) rotate(${dragSpring.values.rotate}deg) scale(${dragSpring.values.scale})`,
        boxShadow: `0px ${dragSpring.values.y * -2.4}px ${dragSpring.values.y * -4.8}px rgba(15, 23, 42, ${dragSpring.values.shadow})`,
    };
};

const scheduleDraggedWidgetSettle = (
    mode: 'desktop' | 'mobile',
    widgetId: string | number,
) => {
    if (dragSettleTimeout !== null) {
        window.clearTimeout(dragSettleTimeout);
    }
    dragSettleTimeout = window.setTimeout(() => {
        if (
            String(draggingWidgetId.value) === String(widgetId) &&
            draggingWidgetMode.value === mode
        ) {
            updateDraggedWidgetVisual(mode, widgetId, 0);
        }
        dragSettleTimeout = null;
    }, 90);
};

const beginDraggedWidget = (
    mode: 'desktop' | 'mobile',
    widgetId: string | number,
) => {
    draggingWidgetId.value = widgetId;
    draggingWidgetMode.value = mode;
    dragSpring.set({
        x: 0,
        y: 0,
        rotate: 0,
        scale: 1,
        shadow: 0,
    });
    document.body.classList.add('is-dragging');
    updateDraggedWidgetVisual(mode, widgetId, 0);
    scheduleDraggedWidgetSettle(mode, widgetId);
};

const finishDraggedWidget = (
    widgetId: string | number,
    mode: 'desktop' | 'mobile',
) => {
    document.body.classList.remove('is-dragging');
    if (
        dragVisualState.value?.mode === mode &&
        String(dragVisualState.value.widgetId) === String(widgetId)
    ) {
        dragVisualState.value = null;
    }
    if (dragSettleTimeout !== null) {
        window.clearTimeout(dragSettleTimeout);
        dragSettleTimeout = null;
    }
    draggingWidgetId.value = null;
    draggingWidgetMode.value = null;
    suppressWidgetClickUntil.value = Date.now() + 300;
    if (suppressWidgetClickTimeout !== null) {
        window.clearTimeout(suppressWidgetClickTimeout);
    }
    suppressWidgetClickTimeout = window.setTimeout(() => {
        suppressWidgetClickUntil.value = 0;
        suppressWidgetClickTimeout = null;
    }, 300);
};

const startWidgetDragPointer = (
    widgetId: string | number,
    mode: 'desktop' | 'mobile',
    event: PointerEvent,
) => {
    if (
        !isEditing.value ||
        croppingWidgetId.value !== null ||
        activeMapMovingWidgetId.value !== null
    )
        return;
    dragPointerState.value = {
        widgetId,
        mode,
        startX: event.clientX,
        startY: event.clientY,
        lastX: event.clientX,
        lastY: event.clientY,
        isDragging: false,
    };
};

const handleWindowPointerMove = (event: PointerEvent) => {
    const pointerState = dragPointerState.value;
    if (!pointerState) return;

    const totalDeltaX = event.clientX - pointerState.startX;
    const totalDeltaY = event.clientY - pointerState.startY;
    const movedEnough = Math.abs(totalDeltaX) > 3 || Math.abs(totalDeltaY) > 3;
    if (movedEnough && !pointerState.isDragging) {
        pointerState.isDragging = true;
        beginDraggedWidget(pointerState.mode, pointerState.widgetId);
    }
    if (!pointerState.isDragging) {
        pointerState.lastX = event.clientX;
        pointerState.lastY = event.clientY;
        return;
    }

    updateDraggedWidgetVisual(
        pointerState.mode,
        pointerState.widgetId,
        totalDeltaX,
        event.clientX - pointerState.lastX,
    );
    scheduleDraggedWidgetSettle(pointerState.mode, pointerState.widgetId);
    pointerState.lastX = event.clientX;
    pointerState.lastY = event.clientY;
};

const handleWindowPointerUp = () => {
    const pointerState = dragPointerState.value;
    if (!pointerState) return;
    if (pointerState.isDragging) {
        finishDraggedWidget(pointerState.widgetId, pointerState.mode);
    }
    dragPointerState.value = null;
};

const shouldSuppressWidgetClick = () =>
    Date.now() < suppressWidgetClickUntil.value;

const syncWidgets = async () => {
    if (!link.value) return;
    const currentWidgets = Array.isArray(link.value.widgets)
        ? link.value.widgets
        : [];
    const response = await apiJSON<{ widgets?: LinkWidget[] }>(
        `/links/${link.value.slug}/widgets/sync`,
        'POST',
        {
            widgets: currentWidgets.map((widget) => ({
                id:
                    typeof widget.id === 'number' ||
                    /^\d+$/.test(String(widget.id))
                        ? Number(widget.id)
                        : undefined,
                type: widget.type,
                content: widget.content,
                thumbnail_url: widget.thumbnail_url,
                x: Number(widget.x ?? 0),
                y: Number(widget.y ?? 0),
                w: Number(widget.w ?? 1),
                h: Number(widget.h ?? 1),
                x_mobile: Number(widget.x_mobile ?? 0),
                y_mobile: Number(widget.y_mobile ?? 0),
                w_mobile: Number(widget.w_mobile ?? 1),
                h_mobile: Number(widget.h_mobile ?? 1),
                settings: widget.settings,
            })),
        },
    );
    if (Array.isArray(response.widgets)) {
        link.value.widgets = currentWidgets.map((widget, index) => ({
            ...widget,
            id: response.widgets?.[index]?.id ?? widget.id,
        }));
        updateLayoutsFromWidgets();
    }
};

const updateViewport = () => {
    viewportSmall.value = window.matchMedia('(max-width: 1024px)').matches;
};

const handleBeforeUnload = (event: BeforeUnloadEvent) => {
    if (!isEditing.value || !hasUnsavedChanges.value || saving.value) return;
    event.preventDefault();
    event.returnValue = '';
};

const closeStylePanelOnOutsideClick = (event: PointerEvent) => {
    if (!showStylePanel.value) return;
    const target = event.target as Element | null;
    if (
        target &&
        (stylePanelRef.value?.contains(target) ||
            target.closest('[data-style-toggle-button]'))
    ) {
        return;
    }
    showStylePanel.value = false;
};

const disableLayoutTransitionsBriefly = () => {
    isPreviewLayoutSwitching.value = true;

    if (previewLayoutSwitchTimeout !== null) {
        window.clearTimeout(previewLayoutSwitchTimeout);
    }

    nextTick(() => {
        requestAnimationFrame(() => {
            previewLayoutSwitchTimeout = window.setTimeout(() => {
                isPreviewLayoutSwitching.value = false;
                previewLayoutSwitchTimeout = null;
            }, 120);
        });
    });
};

const loadProfile = async () => {
    isLoading.value = true;
    error.value = '';
    try {
        const [profile, current] = await Promise.all([
            apiGet<{ link: GridLink; is_owner: boolean; is_editing: boolean }>(
                `/api/links/${slug.value}`,
            ),
            apiGet<{ user: CurrentUser | null }>('/api/me'),
        ]);
        link.value = {
            ...profile.link,
            widgets: Array.isArray(profile.link.widgets)
                ? profile.link.widgets
                : [],
        };
        user.value = current.user;
        isOwner.value = profile.is_owner;
        isEditing.value = profile.is_editing;
        editName.value = profile.link.display_name;
        editBio.value = profile.link.bio || '';
        pageTheme.value =
            profile.link.theme_config?.theme === 'dark' ? 'dark' : 'light';
        widgetStyle.value = ['sharp', 'soft', 'rounded'].includes(
            String(profile.link.theme_config?.widget_style),
        )
            ? (profile.link.theme_config?.widget_style as
                  | 'sharp'
                  | 'soft'
                  | 'rounded')
            : 'rounded';
        updateLayoutsFromWidgets();
        hasUnsavedChanges.value = false;
        document.title = profile.link.display_name;
    } catch {
        error.value = 'ページを読み込めませんでした';
    } finally {
        isLoading.value = false;
    }
};

const copyProfileUrl = async () => {
    if (navigator.share && viewportSmall.value) {
        try {
            await navigator.share({
                title: editName.value,
                url: profileUrl.value,
            });
            return;
        } catch {
            // The native share sheet was dismissed; fall back to clipboard below.
        }
    }
    await navigator.clipboard.writeText(profileUrl.value);
    copied.value = true;
    window.setTimeout(() => {
        copied.value = false;
    }, 2400);
};

const widgetHref = (widget: LinkWidget) => {
    if (
        !link.value ||
        !widget.content ||
        widget.type === 'section' ||
        widget.type === 'map'
    ) {
        return '';
    }
    return apiUrl(`/@${link.value.slug}/widgets/${widget.id}/click`);
};

const isSensitive = (widget: LinkWidget) => Boolean(widget.settings?.sensitive);
const openWidget = (event: MouseEvent, widget: LinkWidget) => {
    if (
        isEditing.value ||
        !widget.content ||
        widget.type === 'section' ||
        widget.type === 'map'
    ) {
        event.preventDefault();
        return;
    }
    if (isSensitive(widget)) {
        event.preventDefault();
        sensitiveTarget.value = widget;
    }
};

const continueSensitive = () => {
    if (!sensitiveTarget.value) return;
    window.open(
        widgetHref(sensitiveTarget.value),
        '_blank',
        'noopener,noreferrer',
    );
    sensitiveTarget.value = null;
};

const publishProfile = async () => {
    if (!link.value) return;
    link.value.is_published = true;
    await saveProfile(false);
    showPublishConfetti.value = true;
    window.setTimeout(() => {
        showPublishConfetti.value = false;
    }, 1700);
};

const saveProfile = async (exitEditing = true) => {
    if (!link.value) return;
    disableLayoutTransitionsBriefly();
    syncWidgetsFromDesktop();
    syncWidgetsFromMobile();
    saving.value = true;
    const displayName = editName.value.trim() || link.value.display_name;
    try {
        await apiJSON(`/links/${link.value.slug}`, 'PUT', {
            display_name: displayName,
            bio: editBio.value,
            avatar_url: link.value.avatar_url,
            delete_avatar: !link.value.avatar_url,
            theme_config: {
                theme: pageTheme.value,
                widget_style: widgetStyle.value,
            },
            is_published: link.value.is_published,
            has_web_display: !viewportSmall.value,
        });
        await syncWidgets();
        link.value.display_name = displayName;
        link.value.bio = editBio.value || null;
        link.value.theme_config = {
            theme: pageTheme.value,
            widget_style: widgetStyle.value,
        };
        link.value.has_web_display = !viewportSmall.value;
        hasUnsavedChanges.value = false;
        if (exitEditing) {
            isEditing.value = false;
        }
    } finally {
        saving.value = false;
    }
};

watch(pageTheme, (theme) => {
    document.documentElement.style.backgroundColor =
        theme === 'dark' ? '#111111' : '#ffffff';
    document.body.style.backgroundColor =
        theme === 'dark' ? '#111111' : '#ffffff';
});

watch(previewMode, () => {
    disableLayoutTransitionsBriefly();
    markDirty();
});

watch(viewportSmall, () => {
    disableLayoutTransitionsBriefly();
    if (!viewportSmall.value) {
        closeMobileAddLinkSheet();
        closeMobileEditors();
    }
});

watch(isEditing, () => {
    disableLayoutTransitionsBriefly();
});

watch(activeWidgetId, () => {
    if (viewportSmall.value && isEditing.value) {
        disableLayoutTransitionsBriefly();
    }
});

watch([pageTheme, widgetStyle], () => {
    markDirty();
});

watch(isEditing, (editing) => {
    if (!editing) {
        showStylePanel.value = false;
    }
});

watch(
    () => [editName.value, editBio.value, isEditing.value],
    () => {
        if (isEditing.value) {
            nextTick(syncProfileEditors);
        }
    },
    { immediate: true },
);

onMounted(() => {
    updateViewport();
    window.addEventListener('resize', updateViewport);
    window.addEventListener('pointermove', handleWindowPointerMove);
    window.addEventListener('pointerup', handleWindowPointerUp);
    window.addEventListener('pointercancel', handleWindowPointerUp);
    window.addEventListener('beforeunload', handleBeforeUnload);
    window.addEventListener('pointerdown', closeStylePanelOnOutsideClick);
    loadProfile();
});

onUnmounted(() => {
    window.removeEventListener('resize', updateViewport);
    window.removeEventListener('pointermove', handleWindowPointerMove);
    window.removeEventListener('pointerup', handleWindowPointerUp);
    window.removeEventListener('pointercancel', handleWindowPointerUp);
    window.removeEventListener('beforeunload', handleBeforeUnload);
    window.removeEventListener('pointerdown', closeStylePanelOnOutsideClick);
    document.body.classList.remove('is-dragging');
    if (dragSettleTimeout !== null) {
        window.clearTimeout(dragSettleTimeout);
    }
    if (suppressWidgetClickTimeout !== null) {
        window.clearTimeout(suppressWidgetClickTimeout);
    }
    if (previewLayoutSwitchTimeout !== null) {
        window.clearTimeout(previewLayoutSwitchTimeout);
    }
    document.documentElement.style.backgroundColor = '';
    document.body.style.backgroundColor = '';
});
</script>

<template>
    <main
        class="link-page min-h-screen transition-colors duration-300"
        :class="[
            pageThemeClasses,
            isEditing ? 'link-page--editing' : '',
            isPreviewLayoutSwitching ? 'link-page--instant-switch' : '',
        ]"
    >
        <div v-if="isLoading" class="grid min-h-screen place-items-center">
            <div class="grid gap-4 text-center">
                <LayoutGrid class="mx-auto size-9 animate-pulse" />
                <p class="text-sm font-bold text-gray-400">読み込み中...</p>
            </div>
        </div>

        <div
            v-else-if="error"
            class="grid min-h-screen place-items-center px-6 text-center"
        >
            <div>
                <h1 class="text-2xl font-black">{{ error }}</h1>
                <RouterLink
                    to="/"
                    class="mt-6 inline-flex rounded-full bg-black px-5 py-3 text-sm font-bold text-white"
                >
                    トップへ
                </RouterLink>
            </div>
        </div>

        <template v-else-if="link">
            <div
                v-if="!isOwner"
                class="fixed inset-x-0 bottom-4 z-[9005] flex justify-center px-4"
                aria-label="プロフィールアクション"
            >
                <div
                    class="flex h-11 items-center gap-2 rounded-full border border-neutral-200 bg-white/95 p-1 shadow-[0_18px_50px_rgba(0,0,0,0.16)] backdrop-blur-md"
                >
                    <div class="relative">
                        <div
                            v-if="copied"
                            class="absolute bottom-full left-1/2 mb-2 -translate-x-1/2 rounded-lg bg-black px-3 py-1.5 text-xs font-bold whitespace-nowrap text-white shadow-lg"
                        >
                            URLをコピーしました
                        </div>
                        <button
                            type="button"
                            class="flex h-9 cursor-pointer items-center justify-center gap-2 rounded-full bg-black px-5 text-sm font-bold text-white transition-colors hover:bg-neutral-800"
                            aria-label="シェア"
                            @click="copyProfileUrl"
                        >
                            <Check v-if="copied" class="size-4 text-white" />
                            <Copy v-else class="size-4" />
                            シェア
                        </button>
                    </div>
                    <button
                        v-if="!viewportSmall"
                        type="button"
                        class="flex size-9 cursor-pointer items-center justify-center rounded-full text-gray-700 transition-colors hover:bg-gray-100 max-[1024px]:hidden"
                        aria-label="通報"
                        title="通報"
                    >
                        <MoreHorizontal class="size-5" />
                    </button>
                </div>
            </div>

            <div
                v-if="isOwner"
                class="fixed inset-x-0 bottom-4 z-[9005] flex justify-center px-4"
            >
                <div
                    v-if="isEditing && showStylePanel"
                    ref="stylePanelRef"
                    class="absolute bottom-[calc(100%+0.75rem)] left-1/2 w-[min(calc(100vw-2rem),400px)] -translate-x-1/2 rounded-2xl border border-gray-200 bg-white p-4 text-gray-950 shadow-[0_18px_55px_rgba(15,23,42,0.18)]"
                >
                    <div class="flex flex-col gap-4">
                        <div class="grid gap-3 border-b border-gray-200 pb-4">
                            <div>
                                <h2 class="text-base font-black">テーマ</h2>
                                <p
                                    class="mt-1 text-xs font-semibold text-gray-500"
                                >
                                    好みの見た目を選択します。
                                </p>
                            </div>
                            <div
                                class="grid grid-cols-2 rounded-2xl border border-gray-200 bg-gray-50 p-1"
                            >
                                <button
                                    v-for="option in themeOptions"
                                    :key="option.value"
                                    type="button"
                                    class="flex h-9 cursor-pointer items-center justify-center gap-2 rounded-xl px-3 text-xs font-black transition-colors"
                                    :class="
                                        pageTheme === option.value
                                            ? 'bg-white text-gray-950 shadow-sm ring-1 ring-gray-200'
                                            : 'text-gray-500 hover:text-gray-900'
                                    "
                                    @click="pageTheme = option.value"
                                >
                                    <component
                                        :is="option.icon"
                                        class="size-4"
                                    />
                                    {{ option.label }}
                                </button>
                            </div>
                        </div>
                        <div class="grid gap-3">
                            <div>
                                <h2 class="text-base font-black">
                                    ウィジェットスタイル
                                </h2>
                                <p
                                    class="mt-1 text-xs font-semibold text-gray-500"
                                >
                                    ウィジェットの角丸を選択します。
                                </p>
                            </div>
                            <div
                                class="grid grid-cols-3 rounded-2xl border border-gray-200 bg-gray-50 p-1"
                            >
                                <button
                                    v-for="option in widgetStyleOptions"
                                    :key="option.value"
                                    type="button"
                                    class="flex h-9 cursor-pointer items-center justify-center gap-1.5 px-2 text-xs font-black transition-colors"
                                    :class="[
                                        option.class,
                                        widgetStyle === option.value
                                            ? 'bg-white text-gray-950 shadow-sm ring-1 ring-gray-200'
                                            : 'text-gray-500 hover:text-gray-900',
                                    ]"
                                    @click="widgetStyle = option.value"
                                >
                                    <span
                                        class="block size-4 shrink-0 border-2"
                                        :class="[
                                            option.class,
                                            widgetStyle === option.value
                                                ? 'border-gray-950'
                                                : 'border-gray-400',
                                        ]"
                                    ></span>
                                    {{ option.label }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    v-if="!mobileWidgetOperationActive"
                    class="flex max-w-[calc(100vw-2rem)] items-center gap-1 overflow-x-auto rounded-full border border-neutral-200 bg-white/95 p-1 shadow-[0_18px_50px_rgba(0,0,0,0.16)] backdrop-blur-md"
                >
                    <button
                        v-if="!viewportSmall"
                        type="button"
                        class="flex size-9 cursor-pointer items-center justify-center rounded-full text-gray-700 transition-colors hover:bg-gray-100 max-[1024px]:hidden"
                        :class="
                            previewMode === 'desktop'
                                ? 'bg-black text-white hover:bg-black'
                                : ''
                        "
                        aria-label="PC表示"
                        title="PC表示"
                        @click="previewMode = 'desktop'"
                    >
                        <Monitor class="size-5" />
                    </button>
                    <button
                        v-if="!viewportSmall"
                        type="button"
                        class="flex size-9 cursor-pointer items-center justify-center rounded-full text-gray-700 transition-colors hover:bg-gray-100 max-[1024px]:hidden"
                        :class="
                            previewMode === 'mobile'
                                ? 'bg-black text-white hover:bg-black'
                                : ''
                        "
                        aria-label="SP表示"
                        title="SP表示"
                        @click="previewMode = 'mobile'"
                    >
                        <Smartphone class="size-5" />
                    </button>
                    <button
                        v-if="isEditing"
                        type="button"
                        data-style-toggle-button
                        class="flex size-9 cursor-pointer items-center justify-center rounded-full text-gray-700 transition-colors hover:bg-gray-100"
                        :class="
                            showStylePanel
                                ? 'bg-black text-white hover:bg-black'
                                : ''
                        "
                        title="スタイル変更"
                        aria-label="スタイル変更"
                        @click.stop="showStylePanel = !showStylePanel"
                    >
                        <Palette class="size-5" />
                    </button>
                    <template v-if="isEditing">
                        <span class="mx-1 h-6 w-px shrink-0 bg-gray-200"></span>
                        <button
                            type="button"
                            class="flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-full text-gray-700 transition-colors hover:bg-gray-100"
                            title="リンクを追加"
                            aria-label="リンクを追加"
                            @click="openAddLinkModal"
                        >
                            <LinkIcon class="size-4" />
                        </button>
                        <button
                            type="button"
                            class="flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-full text-gray-700 transition-colors hover:bg-gray-100"
                            title="メディアを追加"
                            aria-label="メディアを追加"
                            @click="openMediaPicker"
                        >
                            <ImageIcon class="size-4" />
                        </button>
                        <button
                            type="button"
                            class="flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-full text-gray-700 transition-colors hover:bg-gray-100"
                            title="テキストを追加"
                            aria-label="テキストを追加"
                            @click="addTextWidget"
                        >
                            <Type class="size-4" />
                        </button>
                        <button
                            type="button"
                            class="flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-full text-gray-700 transition-colors hover:bg-gray-100"
                            title="地図を追加"
                            aria-label="地図を追加"
                            @click="addMapWidget"
                        >
                            <MapPin class="size-4" />
                        </button>
                        <button
                            type="button"
                            class="flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-full text-gray-700 transition-colors hover:bg-gray-100"
                            title="セクションを追加"
                            aria-label="セクションを追加"
                            @click="addSectionWidget"
                        >
                            <Plus class="size-4" />
                        </button>
                    </template>
                    <button
                        v-if="
                            !isEditing &&
                            !link.is_published &&
                            widgets.length > 0
                        "
                        type="button"
                        class="flex h-9 cursor-pointer items-center gap-2 rounded-full bg-black px-5 text-sm font-bold text-white transition-colors hover:bg-neutral-800 disabled:opacity-60"
                        :disabled="saving"
                        @click="publishProfile"
                    >
                        <PartyPopper class="size-4" />
                        公開
                    </button>
                    <div
                        v-if="!isEditing && link.is_published"
                        class="relative"
                    >
                        <div
                            v-if="copied"
                            class="absolute bottom-full left-1/2 mb-2 -translate-x-1/2 rounded-lg bg-black px-3 py-1.5 text-xs font-bold whitespace-nowrap text-white shadow-lg"
                        >
                            URLをコピーしました
                        </div>
                        <button
                            type="button"
                            class="flex h-9 cursor-pointer items-center gap-2 rounded-full bg-black px-5 text-sm font-bold text-white transition-colors hover:bg-neutral-800"
                            aria-label="シェア"
                            @click="copyProfileUrl"
                        >
                            <Check v-if="copied" class="size-4 text-white" />
                            <Copy v-else class="size-4" />
                            シェア
                        </button>
                    </div>
                    <button
                        type="button"
                        class="flex h-9 cursor-pointer items-center gap-2 rounded-full px-5 text-sm font-bold transition-colors disabled:opacity-60"
                        :class="
                            isEditing
                                ? 'bg-black text-white hover:bg-neutral-800'
                                : 'text-gray-900 hover:bg-gray-100'
                        "
                        :disabled="saving"
                        @click="isEditing ? saveProfile() : (isEditing = true)"
                    >
                        <Save v-if="isEditing" class="size-4" />
                        <Pencil v-else class="size-4" />
                        {{ isEditing ? (saving ? '保存中' : '保存') : '編集' }}
                    </button>
                </div>
            </div>
            <input
                ref="mediaInput"
                type="file"
                accept="image/*,.apng"
                class="hidden"
                @change="addMediaWidget"
            />
            <input
                ref="avatarInput"
                type="file"
                accept="image/*"
                class="hidden"
                @change="updateAvatar"
            />
            <input
                ref="widgetThumbnailInput"
                type="file"
                accept="image/*"
                class="hidden"
                @change="updateWidgetThumbnail"
            />

            <div
                class="transition-all duration-300"
                :class="
                    activeMode === 'mobile'
                        ? 'min-h-screen px-5 pt-8'
                        : 'min-h-screen px-5 pt-12 min-[1025px]:px-0 sm:px-8'
                "
            >
                <div
                    id="profile"
                    class="relative mx-auto w-full max-w-[1198px] transition-all duration-300"
                    :class="
                        activeMode === 'mobile'
                            ? 'flex w-full max-w-[374px] flex-col gap-4 pb-32'
                            : 'flex w-full flex-col gap-y-8 pb-32 min-[1025px]:max-w-[1198px] min-[1025px]:flex-row min-[1025px]:justify-between min-[1025px]:gap-x-4 min-[1025px]:gap-y-0'
                    "
                >
                    <aside
                        class="mx-auto w-full transition-all duration-300"
                        :class="
                            activeMode === 'mobile'
                                ? 'max-w-[374px] flex-shrink-0'
                                : 'max-w-[374px] min-[1025px]:mx-0 min-[1025px]:w-[280px] min-[1025px]:min-w-[200px] min-[1025px]:flex-shrink'
                        "
                    >
                        <div
                            :class="
                                activeMode === 'desktop'
                                    ? 'min-[1025px]:sticky min-[1025px]:top-16 min-[1025px]:-mx-4 min-[1025px]:max-h-[calc(100vh-64px)] min-[1025px]:overflow-y-auto min-[1025px]:px-4'
                                    : ''
                            "
                        >
                            <div class="text-left">
                                <div class="mt-4 mb-4 flex items-start gap-8">
                                    <div
                                        class="group relative shrink-0"
                                        :class="
                                            activeMode === 'mobile'
                                                ? 'size-[120px]'
                                                : 'size-[120px] min-[1025px]:size-[184px]'
                                        "
                                    >
                                        <button
                                            type="button"
                                            class="relative flex size-full items-center justify-center overflow-hidden rounded-full border-4 text-[32px] font-bold shadow-sm transition-colors"
                                            :class="[
                                                isEditing
                                                    ? 'cursor-pointer hover:border-gray-400'
                                                    : '',
                                                pageTheme === 'dark'
                                                    ? 'border-white/20 bg-white/10 text-white'
                                                    : 'border-gray-300 bg-gray-100 text-gray-700',
                                                activeMode === 'mobile'
                                                    ? 'text-[32px]'
                                                    : 'min-[1025px]:text-[44px]',
                                            ]"
                                            @click="
                                                isEditing
                                                    ? avatarInput?.click()
                                                    : undefined
                                            "
                                        >
                                            <img
                                                v-if="link.avatar_url"
                                                :src="apiUrl(link.avatar_url)"
                                                :alt="editName"
                                                class="size-full object-cover"
                                                draggable="false"
                                            />
                                            <ImageIcon
                                                v-else-if="isEditing"
                                                class="size-12 text-gray-400"
                                            />
                                            <span v-else>{{
                                                displayInitial
                                            }}</span>
                                            <span
                                                v-if="isEditing"
                                                class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/0 text-white opacity-0 transition-all duration-150 group-hover:bg-black/20 group-hover:opacity-100 max-[1024px]:bg-black/20 max-[1024px]:opacity-100"
                                            >
                                                <ImageIcon class="size-9" />
                                            </span>
                                        </button>
                                        <button
                                            v-if="isEditing && link.avatar_url"
                                            type="button"
                                            aria-label="プロフィール画像を削除"
                                            class="absolute -top-2.5 -left-2.5 z-20 flex size-8 cursor-pointer items-center justify-center rounded-xl bg-red-600 text-white opacity-0 shadow-lg transition-opacity duration-150 group-hover:opacity-100 hover:bg-red-700 max-[1024px]:opacity-100"
                                            title="プロフィール画像を削除"
                                            @click.stop="deleteAvatar"
                                        >
                                            <Trash2 class="size-4" />
                                        </button>
                                    </div>
                                </div>

                                <div
                                    class="mb-2 flex flex-col items-start gap-2"
                                    :class="
                                        activeMode === 'desktop'
                                            ? 'lg:flex-row lg:items-baseline lg:gap-3'
                                            : ''
                                    "
                                >
                                    <h1
                                        v-if="isEditing"
                                        ref="nameEditor"
                                        contenteditable="true"
                                        role="textbox"
                                        aria-label="表示名"
                                        data-placeholder="名前を入力"
                                        spellcheck="false"
                                        class="editor-placeholder w-full rounded-xl border-2 px-3 py-1 text-[30px] leading-tight font-bold tracking-tight outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20"
                                        :class="
                                            pageTheme === 'dark'
                                                ? 'border-white/15 bg-white/10 text-white hover:border-white/25 hover:bg-white/15'
                                                : 'border-gray-200 bg-gray-100/70 text-gray-950 hover:border-gray-300 hover:bg-gray-100'
                                        "
                                        @beforeinput="
                                            limitProfileBeforeInput(
                                                $event as InputEvent,
                                                80,
                                                true,
                                            )
                                        "
                                        @keydown.enter.prevent
                                        @input="updateEditableName"
                                        @paste="
                                            pasteProfilePlainText($event, true)
                                        "
                                        @focus="isNameFocused = true"
                                        @blur="
                                            isNameFocused = false;
                                            editName =
                                                editName.trim() ||
                                                link.display_name;
                                            syncProfileEditors();
                                        "
                                    ></h1>
                                    <h1
                                        v-else
                                        class="w-full border-2 border-transparent px-3 py-1 text-[30px] leading-tight font-bold tracking-tight break-words"
                                    >
                                        {{ editName }}
                                    </h1>
                                </div>

                                <div
                                    v-if="isEditing"
                                    ref="bioEditor"
                                    contenteditable="true"
                                    role="textbox"
                                    aria-label="BIO"
                                    data-placeholder="自己紹介を入力"
                                    aria-multiline="true"
                                    class="editor-placeholder min-h-[132px] w-full max-w-[374px] overflow-hidden rounded-xl border-2 px-3 py-2 text-[16px] leading-relaxed whitespace-pre-wrap outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20"
                                    :class="[
                                        activeMode === 'desktop'
                                            ? 'lg:max-w-xl'
                                            : '',
                                        pageTheme === 'dark'
                                            ? 'border-white/15 bg-white/10 text-white hover:border-white/25 hover:bg-white/15'
                                            : 'border-gray-200 bg-gray-100/70 text-gray-700 hover:border-gray-300 hover:bg-gray-100',
                                    ]"
                                    @beforeinput="
                                        limitProfileBeforeInput(
                                            $event as InputEvent,
                                            600,
                                        )
                                    "
                                    @input="updateEditableBio"
                                    @paste="pasteProfilePlainText($event)"
                                    @focus="isBioFocused = true"
                                    @blur="
                                        isBioFocused = false;
                                        syncProfileEditors();
                                    "
                                ></div>
                                <p
                                    v-else
                                    class="w-full max-w-[374px] border-2 border-transparent px-3 py-2 text-[16px] leading-relaxed break-words whitespace-pre-wrap"
                                    :class="[
                                        activeMode === 'desktop'
                                            ? 'lg:line-clamp-[15] lg:max-w-xl'
                                            : '',
                                        pageTheme === 'dark'
                                            ? 'text-white/70'
                                            : 'text-gray-700',
                                    ]"
                                >
                                    {{ editBio }}
                                </p>

                                <div class="mt-3">
                                    <RouterLink
                                        :to="`/@${link.slug}/message`"
                                        class="grid h-12 w-full max-w-[374px] grid-cols-[40px_1fr_40px] items-center rounded-full border px-1.5 transition-colors"
                                        :class="[
                                            activeMode === 'desktop'
                                                ? 'lg:max-w-xl'
                                                : '',
                                            isEditing
                                                ? pageTheme === 'dark'
                                                    ? 'pointer-events-none cursor-not-allowed border-white/10 bg-white/5 text-white/35 opacity-60'
                                                    : 'pointer-events-none cursor-not-allowed border-gray-200 bg-gray-100 text-gray-400 opacity-60'
                                                : pageTheme === 'dark'
                                                  ? 'border-white/15 bg-white/10 text-white hover:border-white/25 hover:bg-white/15'
                                                  : 'border-gray-300 bg-white text-gray-950 hover:border-gray-400 hover:bg-gray-50',
                                        ]"
                                        :aria-disabled="isEditing"
                                        :tabindex="isEditing ? -1 : 0"
                                        @click="
                                            isEditing
                                                ? $event.preventDefault()
                                                : undefined
                                        "
                                    >
                                        <span
                                            class="flex size-9 items-center justify-center rounded-full bg-white text-black"
                                            :class="
                                                isEditing ? 'opacity-60' : ''
                                            "
                                        >
                                            <MessageCircleHeart
                                                class="size-5"
                                            />
                                        </span>
                                        <span
                                            class="text-center text-sm font-bold"
                                            >メッセージ</span
                                        >
                                        <span aria-hidden="true"></span>
                                    </RouterLink>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <section
                        id="grid"
                        class="relative mx-auto w-full max-w-[374px] pt-0 transition-all duration-300"
                        :class="
                            activeMode === 'desktop'
                                ? 'pb-24 min-[1025px]:mx-0 min-[1025px]:w-[736px] min-[1025px]:max-w-none min-[1025px]:shrink-0'
                                : 'mt-2 pb-24'
                        "
                    >
                        <div
                            v-if="croppingWidgetId !== null"
                            class="fixed inset-0 z-[100] hidden bg-black/40 backdrop-blur-sm transition-opacity duration-300 min-[1025px]:block"
                            @click="croppingWidgetId = null"
                        ></div>
                        <GridLayout
                            v-if="activeMode === 'desktop'"
                            :key="`desktop-${viewportSmall}-${previewMode}`"
                            v-model:layout="desktopLayout"
                            :col-num="4"
                            :row-height="81.5"
                            :margin="[12, 12]"
                            :is-draggable="
                                isEditing &&
                                croppingWidgetId === null &&
                                activeMapMovingWidgetId === null
                            "
                            :is-resizable="false"
                            :vertical-compact="true"
                            :use-css-transforms="true"
                            @layout-updated="syncWidgetsFromDesktop"
                            class="-m-3 w-[calc(100%+24px)]"
                            :class="
                                isPreviewLayoutSwitching
                                    ? 'link-page--instant-layout'
                                    : ''
                            "
                        >
                            <GridItem
                                v-for="placeholder in isEditing
                                    ? desktopPlaceholderItems
                                    : []"
                                :key="placeholder.i"
                                :x="placeholder.x"
                                :y="placeholder.y"
                                :w="placeholder.w"
                                :h="placeholder.h"
                                :i="placeholder.i"
                                :static="true"
                                class="z-[1]"
                            >
                                <button
                                    type="button"
                                    class="relative flex h-full w-full cursor-pointer flex-col items-center justify-center rounded-[32px] border-2 border-dashed border-gray-200 text-slate-400 transition-colors hover:bg-gray-50"
                                    @click="
                                        placeholder.type === 'media'
                                            ? openMediaPicker()
                                            : placeholder.type === 'link'
                                              ? openAddLinkModal()
                                              : addTextWidget()
                                    "
                                >
                                    <Plus
                                        class="absolute top-4 right-4 size-4 text-gray-300"
                                    />
                                    <component
                                        :is="placeholder.icon"
                                        class="mb-2 size-6"
                                    />
                                    <span class="text-xs font-semibold">{{
                                        placeholder.label
                                    }}</span>
                                </button>
                            </GridItem>
                            <GridItem
                                v-for="item in desktopLayout"
                                :key="`desktop-${item.i}`"
                                :x="item.x"
                                :y="item.y"
                                :w="item.w"
                                :h="item.h"
                                :i="item.i"
                                :drag-ignore-from="'.widget-text-input, a, input, textarea'"
                                @mouseenter="
                                    draggingWidgetId === null
                                        ? (hoveredWidgetId = item.i)
                                        : null
                                "
                                @mouseleave="hoveredWidgetId = null"
                                class="group"
                                :class="
                                    croppingWidgetId === item.i
                                        ? 'is-cropping z-[200]'
                                        : String(activeMapMovingWidgetId) ===
                                            String(item.i)
                                          ? '!z-[3000]'
                                          : draggingWidgetId === item.i
                                            ? 'z-[1100]'
                                            : hoveredWidgetId === item.i ||
                                                lockedControlsWidgetId ===
                                                    item.i
                                              ? 'z-[1000]'
                                              : 'z-10'
                                "
                            >
                                <div
                                    class="h-full w-full will-change-transform"
                                    :class="cornerClass"
                                    :style="
                                        draggedWidgetStyle('desktop', item.i)
                                    "
                                >
                                    <component
                                        :is="
                                            item.widget.content &&
                                            item.widget.type !== 'section' &&
                                            item.widget.type !== 'map' &&
                                            !isEditing
                                                ? 'a'
                                                : 'div'
                                        "
                                        :href="widgetHref(item.widget)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="relative block h-full w-full"
                                        :class="[
                                            cornerClass,
                                            newlyAddedWidgetIds.has(
                                                String(item.widget.id),
                                            )
                                                ? 'widget-bounce-enter'
                                                : '',
                                            draggingWidgetId === item.i
                                                ? ''
                                                : 'transition-[transform,box-shadow] duration-200 ease-out',
                                            item.widget.content &&
                                            item.widget.type !== 'section' &&
                                            item.widget.type !== 'map' &&
                                            !isEditing
                                                ? 'cursor-pointer hover:-translate-y-0.5 hover:shadow-lg'
                                                : 'cursor-default',
                                            isEditing &&
                                            croppingWidgetId === null &&
                                            activeMapMovingWidgetId === null
                                                ? draggingWidgetId === item.i
                                                    ? 'cursor-grabbing'
                                                    : 'cursor-grab active:cursor-grabbing'
                                                : '',
                                            String(activeMapMovingWidgetId) ===
                                            String(item.i)
                                                ? 'ring-2 ring-black'
                                                : '',
                                        ]"
                                        @click="
                                            shouldSuppressWidgetClick()
                                                ? $event.preventDefault()
                                                : (openWidget(
                                                      $event,
                                                      item.widget,
                                                  ),
                                                  (activeWidgetId = item.i))
                                        "
                                        @pointerdown="
                                            startWidgetDragPointer(
                                                item.i,
                                                'desktop',
                                                $event,
                                            )
                                        "
                                    >
                                        <WidgetControls
                                            v-if="
                                                isEditing &&
                                                draggingWidgetId !== item.i &&
                                                (hoveredWidgetId === item.i ||
                                                    croppingWidgetId ===
                                                        item.i ||
                                                    String(
                                                        activeMapMovingWidgetId,
                                                    ) === String(item.i) ||
                                                    lockedControlsWidgetId ===
                                                        item.i)
                                            "
                                            :widget="item.widget"
                                            mode="desktop"
                                            :is-map-moving="
                                                String(
                                                    activeMapMovingWidgetId,
                                                ) === String(item.i)
                                            "
                                            :size-options="
                                                widgetSizeOptions(
                                                    item.widget,
                                                    'desktop',
                                                )
                                            "
                                            :is-cropping="
                                                croppingWidgetId === item.i
                                            "
                                            @delete="deleteWidget(item.widget)"
                                            @resize="
                                                resizeWidget(
                                                    item.widget,
                                                    'desktop',
                                                    $event,
                                                )
                                            "
                                            @edit-link="
                                                openWidgetLinkSettings(
                                                    item.widget,
                                                )
                                            "
                                            @toggle-crop="
                                                croppingWidgetId =
                                                    croppingWidgetId === item.i
                                                        ? null
                                                        : item.i
                                            "
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
                                            @update-youtube-mode="
                                                updateWidgetEmbedMode(
                                                    item.widget,
                                                    $event,
                                                )
                                            "
                                            @update-map-location="
                                                updateMapWidgetLocation(
                                                    item.widget,
                                                    $event,
                                                )
                                            "
                                            @update-map-zoom="
                                                updateMapWidgetZoom(
                                                    item.widget,
                                                    $event,
                                                )
                                            "
                                            @toggle-map-move="
                                                toggleMapMove(item.widget)
                                            "
                                            @close-map-move="
                                                closeMapMove(item.widget)
                                            "
                                            @lock-open="
                                                setControlsLock(
                                                    item.widget,
                                                    $event,
                                                )
                                            "
                                        />
                                        <ProfileWidget
                                            :widget="item.widget"
                                            mode="desktop"
                                            :page-theme="pageTheme"
                                            :corner-class="cornerClass"
                                            :is-editing="isEditing"
                                            :hide-image-link-icon="isEditing"
                                            :is-cropping="
                                                croppingWidgetId === item.i
                                            "
                                            :is-map-moving="
                                                String(
                                                    activeMapMovingWidgetId,
                                                ) === String(item.i)
                                            "
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
                                            @update-map-center="
                                                updateMapWidgetCenter(
                                                    item.widget,
                                                    $event,
                                                )
                                            "
                                            @upload-image="
                                                uploadLinkWidgetImage(
                                                    item.widget,
                                                    $event,
                                                )
                                            "
                                            @remove-image="
                                                removeLinkWidgetImage(
                                                    item.widget,
                                                )
                                            "
                                        />
                                    </component>
                                </div>
                            </GridItem>
                        </GridLayout>

                        <GridLayout
                            v-else
                            :key="`mobile-${viewportSmall}-${previewMode}`"
                            v-model:layout="mobileLayout"
                            :col-num="2"
                            :row-height="mobileGridRowHeight"
                            :margin="[12, 12]"
                            :is-draggable="
                                isEditing &&
                                croppingWidgetId === null &&
                                activeMapMovingWidgetId === null
                            "
                            :is-resizable="false"
                            :vertical-compact="true"
                            :use-css-transforms="true"
                            @layout-updated="syncWidgetsFromMobile"
                            class="-m-3 w-[calc(100%+24px)]"
                            :class="
                                isPreviewLayoutSwitching
                                    ? 'link-page--instant-layout'
                                    : ''
                            "
                        >
                            <GridItem
                                v-for="placeholder in isEditing
                                    ? mobilePlaceholderItems
                                    : []"
                                :key="placeholder.i"
                                :x="placeholder.x"
                                :y="placeholder.y"
                                :w="placeholder.w"
                                :h="placeholder.h"
                                :i="placeholder.i"
                                :static="true"
                                class="z-[1]"
                            >
                                <button
                                    type="button"
                                    class="relative flex h-full w-full cursor-pointer flex-col items-center justify-center rounded-[32px] border-2 border-dashed border-gray-200 text-slate-400 transition-colors hover:bg-gray-50"
                                    @click="openAddLinkModal"
                                >
                                    <Plus
                                        class="absolute top-4 right-4 size-4 text-gray-300"
                                    />
                                    <component
                                        :is="placeholder.icon"
                                        class="mb-2 size-6"
                                    />
                                    <span class="text-xs font-semibold">{{
                                        placeholder.label
                                    }}</span>
                                </button>
                            </GridItem>
                            <GridItem
                                v-for="item in mobileLayout"
                                :key="`mobile-${item.i}`"
                                :x="item.x"
                                :y="item.y"
                                :w="item.w"
                                :h="item.h"
                                :i="item.i"
                                :drag-allow-from="
                                    viewportSmall
                                        ? '.mobile-widget-move-handle'
                                        : undefined
                                "
                                :drag-ignore-from="'.mobile-widget-ignore-drag, .widget-text-input--focused, a, input, textarea'"
                                @mouseenter="
                                    draggingWidgetId === null
                                        ? (hoveredWidgetId = item.i)
                                        : null
                                "
                                @mouseleave="hoveredWidgetId = null"
                                class="group"
                                :class="[
                                    croppingWidgetId === item.i
                                        ? 'is-cropping z-[200]'
                                        : String(activeMapMovingWidgetId) ===
                                            String(item.i)
                                          ? '!z-[3000]'
                                          : draggingWidgetId === item.i
                                            ? 'z-[1100] cursor-grabbing'
                                            : viewportSmall &&
                                                activeWidgetId === item.i
                                              ? 'z-[4000] cursor-grab'
                                              : hoveredWidgetId === item.i ||
                                                  lockedControlsWidgetId ===
                                                      item.i
                                                ? 'z-[1000] cursor-grab'
                                                : 'z-10',
                                    isEditing && activeWidgetId === item.i
                                        ? 'cursor-default'
                                        : '',
                                    isEditing && !viewportSmall
                                        ? draggingWidgetId === item.i
                                            ? '!cursor-grabbing'
                                            : '!cursor-grab active:!cursor-grabbing'
                                        : '',
                                ]"
                            >
                                <div
                                    class="h-full w-full will-change-transform"
                                    :class="cornerClass"
                                    :style="
                                        draggedWidgetStyle('mobile', item.i)
                                    "
                                >
                                    <component
                                        :is="
                                            item.widget.content &&
                                            item.widget.type !== 'section' &&
                                            item.widget.type !== 'map' &&
                                            !isEditing
                                                ? 'a'
                                                : 'div'
                                        "
                                        :href="widgetHref(item.widget)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="relative block h-full w-full overflow-visible transition-[transform,box-shadow] duration-200 ease-out"
                                        :class="[
                                            cornerClass,
                                            newlyAddedWidgetIds.has(
                                                String(item.widget.id),
                                            )
                                                ? 'widget-bounce-enter'
                                                : '',
                                            draggingWidgetId === item.i
                                                ? ''
                                                : 'transition-[transform,box-shadow] duration-200 ease-out',
                                            item.widget.content &&
                                            item.widget.type !== 'section' &&
                                            item.widget.type !== 'map' &&
                                            !isEditing
                                                ? 'cursor-pointer active:scale-[0.99]'
                                                : 'cursor-default',
                                            activeWidgetId === item.i &&
                                            isEditing &&
                                            viewportSmall
                                                ? 'border-2 border-black'
                                                : '',
                                            draggingWidgetId === item.i
                                                ? 'z-50'
                                                : '',
                                            String(activeMapMovingWidgetId) ===
                                            String(item.i)
                                                ? 'ring-2 ring-black'
                                                : '',
                                        ]"
                                        @click="
                                            shouldSuppressWidgetClick()
                                                ? $event.preventDefault()
                                                : (openWidget(
                                                      $event,
                                                      item.widget,
                                                  ),
                                                  (activeWidgetId = item.i))
                                        "
                                        @pointerdown="
                                            !viewportSmall
                                                ? startWidgetDragPointer(
                                                      item.i,
                                                      'mobile',
                                                      $event,
                                                  )
                                                : null
                                        "
                                    >
                                        <div
                                            v-if="
                                                isEditing &&
                                                viewportSmall &&
                                                activeWidgetId === item.i &&
                                                croppingWidgetId !== item.i
                                            "
                                            class="pointer-events-none absolute inset-0 z-[700]"
                                        >
                                            <button
                                                type="button"
                                                aria-label="ウィジェットを削除"
                                                class="mobile-widget-ignore-drag pointer-events-auto absolute -top-2.5 -left-2.5 flex size-8 cursor-pointer items-center justify-center rounded-xl bg-red-600 text-white shadow-lg transition-transform active:scale-95"
                                                @click.stop="
                                                    deleteWidget(item.widget)
                                                "
                                                @pointerdown.stop
                                                @touchstart.stop
                                            >
                                                <Trash2 class="size-4" />
                                            </button>
                                            <button
                                                type="button"
                                                aria-label="ウィジェットを編集"
                                                class="mobile-widget-ignore-drag pointer-events-auto absolute -top-2.5 -right-2.5 flex size-8 cursor-pointer items-center justify-center rounded-xl bg-black text-white shadow-lg transition-transform active:scale-95"
                                                @click.stop="
                                                    editMobileWidget(
                                                        item.widget,
                                                    )
                                                "
                                                @pointerdown.stop
                                                @touchstart.stop
                                            >
                                                <Pencil class="size-4" />
                                            </button>
                                            <button
                                                type="button"
                                                aria-label="ウィジェットを移動"
                                                class="mobile-widget-move-handle pointer-events-auto absolute right-1/2 -bottom-5 flex size-10 translate-x-1/2 cursor-grab touch-none items-center justify-center rounded-full bg-black text-white shadow-lg active:cursor-grabbing"
                                                @click.stop.prevent
                                                @pointerdown="
                                                    startWidgetDragPointer(
                                                        item.i,
                                                        'mobile',
                                                        $event,
                                                    )
                                                "
                                            >
                                                <Move class="size-5" />
                                            </button>
                                        </div>
                                        <WidgetControls
                                            v-if="
                                                isEditing &&
                                                !viewportSmall &&
                                                draggingWidgetId !== item.i &&
                                                (hoveredWidgetId === item.i ||
                                                    croppingWidgetId ===
                                                        item.i ||
                                                    String(
                                                        activeMapMovingWidgetId,
                                                    ) === String(item.i) ||
                                                    lockedControlsWidgetId ===
                                                        item.i)
                                            "
                                            :widget="item.widget"
                                            mode="mobile"
                                            :is-map-moving="
                                                String(
                                                    activeMapMovingWidgetId,
                                                ) === String(item.i)
                                            "
                                            :size-options="
                                                widgetSizeOptions(
                                                    item.widget,
                                                    'mobile',
                                                )
                                            "
                                            :is-cropping="
                                                croppingWidgetId === item.i
                                            "
                                            @delete="deleteWidget(item.widget)"
                                            @resize="
                                                resizeWidget(
                                                    item.widget,
                                                    'mobile',
                                                    $event,
                                                )
                                            "
                                            @edit-link="
                                                openWidgetLinkSettings(
                                                    item.widget,
                                                )
                                            "
                                            @toggle-crop="
                                                croppingWidgetId =
                                                    croppingWidgetId === item.i
                                                        ? null
                                                        : item.i
                                            "
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
                                            @update-youtube-mode="
                                                updateWidgetEmbedMode(
                                                    item.widget,
                                                    $event,
                                                )
                                            "
                                            @update-map-location="
                                                updateMapWidgetLocation(
                                                    item.widget,
                                                    $event,
                                                )
                                            "
                                            @update-map-zoom="
                                                updateMapWidgetZoom(
                                                    item.widget,
                                                    $event,
                                                )
                                            "
                                            @toggle-map-move="
                                                toggleMapMove(item.widget)
                                            "
                                            @close-map-move="
                                                closeMapMove(item.widget)
                                            "
                                            @lock-open="
                                                setControlsLock(
                                                    item.widget,
                                                    $event,
                                                )
                                            "
                                        />
                                        <ProfileWidget
                                            :widget="item.widget"
                                            mode="mobile"
                                            :page-theme="pageTheme"
                                            :corner-class="cornerClass"
                                            :is-editing="
                                                isEditing && !viewportSmall
                                            "
                                            :hide-image-link-icon="isEditing"
                                            :is-cropping="
                                                croppingWidgetId === item.i
                                            "
                                            :is-map-moving="
                                                String(
                                                    activeMapMovingWidgetId,
                                                ) === String(item.i)
                                            "
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
                                            @update-map-center="
                                                updateMapWidgetCenter(
                                                    item.widget,
                                                    $event,
                                                )
                                            "
                                            @upload-image="
                                                uploadLinkWidgetImage(
                                                    item.widget,
                                                    $event,
                                                )
                                            "
                                            @remove-image="
                                                removeLinkWidgetImage(
                                                    item.widget,
                                                )
                                            "
                                        />
                                    </component>
                                </div>
                            </GridItem>
                        </GridLayout>
                    </section>
                </div>
            </div>

            <div
                v-if="
                    mobileWidgetOperationActive &&
                    activeMobileWidget &&
                    !mobileLinkEditorWidget &&
                    !mobileImageEditorWidget &&
                    !mobileMapEditorWidget &&
                    !mobileTextEditorWidget &&
                    !mobileSectionEditorWidget
                "
                class="fixed bottom-6 left-1/2 z-[9005] flex -translate-x-1/2 items-center justify-center"
                @click.stop
            >
                <div
                    class="flex h-11 items-center gap-1.5 rounded-2xl border border-gray-700 bg-[#292929] p-1.5 text-white shadow-[0_12px_30px_rgb(15,23,42,0.22)]"
                >
                    <button
                        v-for="option in widgetSizeOptions(
                            activeMobileWidget,
                            'mobile',
                        )"
                        :key="option.key"
                        type="button"
                        :aria-label="option.label"
                        class="flex size-8 cursor-pointer items-center justify-center rounded-lg transition-colors"
                        :class="
                            Number(activeMobileWidget.w_mobile) ===
                                option.size.w &&
                            Number(activeMobileWidget.h_mobile) ===
                                option.size.h
                                ? 'bg-white text-gray-950 shadow-sm'
                                : 'text-white/70 hover:bg-white/10 hover:text-white'
                        "
                        :title="option.label"
                        @click.stop="
                            resizeWidget(
                                activeMobileWidget,
                                'mobile',
                                option.size,
                            )
                        "
                    >
                        <span
                            class="block border-2 transition-colors"
                            :class="[
                                option.key === 'inline'
                                    ? 'h-2 w-5 rounded-[4px]'
                                    : option.key === 'small'
                                      ? 'size-3.5 rounded-[4px]'
                                      : option.key === 'wide'
                                        ? 'h-3 w-5 rounded-[5px]'
                                        : option.key === 'tall'
                                          ? 'h-5 w-3 rounded-[5px]'
                                          : 'size-[18px] rounded-[4px]',
                                Number(activeMobileWidget.w_mobile) ===
                                    option.size.w &&
                                Number(activeMobileWidget.h_mobile) ===
                                    option.size.h
                                    ? 'border-gray-950'
                                    : 'border-white/70',
                            ]"
                        ></span>
                    </button>
                    <div class="mx-1 h-7 w-px bg-white/20"></div>
                    <button
                        type="button"
                        class="flex h-8 min-w-12 cursor-pointer items-center justify-center rounded-lg bg-white px-4 text-xs font-bold whitespace-nowrap text-black transition-transform active:scale-95"
                        @click.stop="closeMobileWidgetSheet"
                    >
                        完了
                    </button>
                </div>
            </div>

            <footer
                v-if="!isOwner"
                class="px-5 pt-2 pb-24 text-center text-xs font-semibold text-gray-400"
            >
                <RouterLink to="/" class="transition-colors hover:text-gray-700"
                    >Built with GridLink</RouterLink
                >
            </footer>

            <div
                v-if="sensitiveTarget"
                class="fixed inset-0 z-[80] grid place-items-center bg-black/40 px-5 backdrop-blur-sm"
            >
                <section
                    class="w-full max-w-sm rounded-2xl bg-white p-6 text-gray-950 shadow-2xl"
                >
                    <Flag class="mb-4 size-7 text-red-500" />
                    <h2 class="text-xl font-black">外部リンクを開きます</h2>
                    <p class="mt-3 text-sm leading-6 font-medium text-gray-500">
                        このリンクは確認が必要な設定になっています。内容を確認してから移動してください。
                    </p>
                    <div class="mt-6 flex gap-3">
                        <button
                            class="h-11 flex-1 rounded-full bg-gray-100 text-sm font-bold text-gray-800"
                            @click="sensitiveTarget = null"
                        >
                            キャンセル
                        </button>
                        <button
                            class="h-11 flex-1 rounded-full bg-black text-sm font-bold text-white"
                            @click="continueSensitive"
                        >
                            開く
                        </button>
                    </div>
                </section>
            </div>

            <div
                v-if="showPublishConfetti"
                class="pointer-events-none fixed inset-0 z-[120] overflow-hidden"
            >
                <span
                    v-for="index in 34"
                    :key="index"
                    class="confetti-piece absolute top-0 block size-2 rounded-sm"
                    :style="{
                        left: `${(index * 29) % 100}%`,
                        backgroundColor: [
                            '#2563eb',
                            '#f43f5e',
                            '#22c55e',
                            '#f59e0b',
                            '#111827',
                        ][index % 5],
                        animationDelay: `${(index % 7) * 0.05}s`,
                        transform: `rotate(${index * 17}deg)`,
                    }"
                ></span>
            </div>

            <Transition name="mobile-sheet">
                <section
                    v-if="viewportSmall && showMobileAddLinkSheet"
                    class="fixed inset-x-0 bottom-0 z-[9999] max-h-[92vh] overflow-hidden rounded-t-3xl border border-gray-200 bg-white text-gray-950 shadow-2xl"
                    @click.stop
                    @pointerdown.stop
                    @touchstart.stop
                >
                    <header
                        class="sticky top-0 z-10 border-b border-gray-100 bg-inherit"
                    >
                        <div
                            class="flex h-16 items-center justify-between px-5"
                        >
                            <button
                                type="button"
                                class="flex size-8 cursor-pointer items-center justify-center rounded-full text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-950"
                                aria-label="キャンセル"
                                title="キャンセル"
                                @click="closeMobileAddLinkSheet"
                            >
                                <X class="size-5" />
                            </button>
                            <h2 class="text-base font-bold">リンクを追加</h2>
                            <button
                                type="button"
                                class="cursor-pointer rounded-full bg-black px-3 py-1.5 text-xs font-bold text-white shadow-sm transition-colors hover:bg-black"
                                @click="submitMobileAddLink"
                            >
                                追加
                            </button>
                        </div>
                    </header>
                    <form
                        class="flex max-h-[calc(92vh-64px)] flex-col gap-5 overflow-y-auto px-5 pt-2 pb-6"
                        @submit.prevent="submitMobileAddLink"
                    >
                        <label class="grid gap-2">
                            <span class="text-sm font-bold">URL</span>
                            <input
                                v-model="mobileAddLinkUrl"
                                type="url"
                                class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-base font-semibold text-gray-900 transition-colors outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/15"
                                placeholder="https://..."
                                @input="mobileAddLinkError = ''"
                            />
                            <span
                                v-if="mobileAddLinkError"
                                class="text-xs font-semibold text-red-600"
                                >{{ mobileAddLinkError }}</span
                            >
                        </label>
                        <label
                            class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-3"
                        >
                            <span class="text-sm font-bold"
                                >開く前に確認を表示</span
                            >
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="mobileAddLinkSensitive"
                                aria-label="開く前に確認を表示"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 focus:outline-none"
                                :class="
                                    mobileAddLinkSensitive
                                        ? 'bg-blue-600'
                                        : 'bg-gray-300'
                                "
                                @click.prevent.stop="
                                    mobileAddLinkSensitive =
                                        !mobileAddLinkSensitive
                                "
                            >
                                <span
                                    class="pointer-events-none inline-block size-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                    :class="
                                        mobileAddLinkSensitive
                                            ? 'translate-x-5'
                                            : 'translate-x-0'
                                    "
                                ></span>
                            </button>
                        </label>
                    </form>
                </section>
            </Transition>

            <Transition name="mobile-sheet">
                <section
                    v-if="viewportSmall && mobileLinkEditorWidget"
                    class="fixed inset-x-0 bottom-0 z-[9999] max-h-[92vh] overflow-hidden rounded-t-3xl border border-gray-200 bg-white text-gray-950 shadow-2xl"
                    :class="
                        pageTheme === 'dark'
                            ? 'border-zinc-800 bg-zinc-950 text-white'
                            : ''
                    "
                    @click.stop
                    @pointerdown.stop
                    @touchstart.stop
                >
                    <header
                        class="sticky top-0 z-10 border-b border-gray-100 bg-inherit"
                    >
                        <div
                            class="flex h-16 items-center justify-between px-5"
                        >
                            <div class="size-8"></div>
                            <h2 class="text-base font-bold">リンクを編集</h2>
                            <button
                                type="button"
                                class="cursor-pointer rounded-full bg-black px-3 py-1.5 text-xs font-bold text-white shadow-sm transition-colors hover:bg-black"
                                @click="closeMobileEditors"
                            >
                                完了
                            </button>
                        </div>
                    </header>
                    <div
                        class="flex max-h-[calc(92vh-64px)] flex-col gap-5 overflow-y-auto px-5 pt-2 pb-6"
                    >
                        <label class="grid gap-2">
                            <span class="text-sm font-bold">タイトル</span>
                            <input
                                :value="
                                    mobileLinkEditorWidget.settings?.title ?? ''
                                "
                                type="text"
                                :maxlength="maxTitleLength"
                                class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-base font-semibold text-gray-900 transition-colors outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/15"
                                placeholder="タイトルを入力"
                                @input="updateMobileLinkTitle"
                            />
                        </label>
                        <div class="grid gap-2">
                            <span class="text-sm font-bold">画像</span>
                            <div class="relative">
                                <button
                                    type="button"
                                    class="relative flex h-48 w-full cursor-pointer items-center justify-center overflow-hidden rounded-3xl border border-gray-200 bg-gray-50 text-gray-400 transition-transform active:scale-[0.99]"
                                    @click="chooseMobileLinkImage"
                                >
                                    <img
                                        v-if="
                                            mobileLinkEditorWidget.thumbnail_url
                                        "
                                        :src="
                                            mobileLinkEditorWidget.thumbnail_url
                                        "
                                        alt=""
                                        class="h-full w-full object-cover"
                                        draggable="false"
                                    />
                                    <ImageIcon v-else class="size-8" />
                                </button>
                                <button
                                    v-if="mobileLinkEditorWidget.thumbnail_url"
                                    type="button"
                                    aria-label="画像を削除"
                                    class="absolute top-3 right-3 flex size-10 cursor-pointer items-center justify-center rounded-xl bg-red-600 text-white shadow-sm transition-colors hover:bg-red-700"
                                    @click.stop="removeMobileLinkImage"
                                >
                                    <Trash2 class="size-4" />
                                </button>
                            </div>
                        </div>
                        <label
                            class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-3"
                        >
                            <span class="text-sm font-bold"
                                >開く前に確認を表示</span
                            >
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="
                                    Boolean(
                                        mobileLinkEditorWidget.settings
                                            ?.sensitive,
                                    )
                                "
                                aria-label="開く前に確認を表示"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 focus:outline-none"
                                :class="
                                    mobileLinkEditorWidget.settings?.sensitive
                                        ? 'bg-blue-600'
                                        : 'bg-gray-300'
                                "
                                @click.prevent.stop="updateMobileLinkSensitive"
                            >
                                <span
                                    class="pointer-events-none inline-block size-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                    :class="
                                        mobileLinkEditorWidget.settings
                                            ?.sensitive
                                            ? 'translate-x-5'
                                            : 'translate-x-0'
                                    "
                                ></span>
                            </button>
                        </label>
                    </div>
                </section>
            </Transition>

            <Transition name="mobile-sheet">
                <section
                    v-if="viewportSmall && mobileImageEditorWidget"
                    class="fixed inset-x-0 bottom-0 z-[9999] max-h-[92vh] overflow-hidden rounded-t-3xl border border-gray-200 bg-white text-gray-950 shadow-2xl"
                    :class="
                        pageTheme === 'dark'
                            ? 'border-zinc-800 bg-zinc-950 text-white'
                            : ''
                    "
                    @click.stop
                    @pointerdown.stop
                    @touchstart.stop
                >
                    <header
                        class="sticky top-0 z-10 border-b border-gray-100 bg-inherit"
                    >
                        <div
                            class="flex h-16 items-center justify-between px-5"
                        >
                            <div class="size-8"></div>
                            <h2 class="text-base font-bold">メディアを編集</h2>
                            <button
                                type="button"
                                class="cursor-pointer rounded-full bg-black px-3 py-1.5 text-xs font-bold text-white shadow-sm transition-colors hover:bg-black"
                                @click="closeMobileEditors"
                            >
                                完了
                            </button>
                        </div>
                    </header>
                    <div
                        class="flex max-h-[calc(92vh-64px)] flex-col gap-5 overflow-y-auto px-5 pt-2 pb-6"
                    >
                        <div class="grid gap-2">
                            <span class="text-sm font-bold">メディア</span>
                            <div
                                class="relative flex min-h-[360px] items-center justify-center rounded-3xl bg-gray-100 p-8"
                            >
                                <div
                                    class="relative overflow-hidden rounded-2xl"
                                    :style="
                                        mobileWidgetPreviewStyle(
                                            mobileImageEditorWidget,
                                        )
                                    "
                                >
                                    <ProfileWidget
                                        :widget="mobileImageEditorWidget"
                                        mode="mobile"
                                        :page-theme="pageTheme"
                                        :corner-class="cornerClass"
                                        :is-editing="true"
                                        :hide-image-link-icon="true"
                                        :is-cropping="
                                            croppingWidgetId ===
                                            mobileImageEditorWidget.id
                                        "
                                        @update-title="
                                            updateWidgetTitle(
                                                mobileImageEditorWidget,
                                                $event,
                                            )
                                        "
                                        @update-crop="
                                            updateImageWidgetCrop(
                                                mobileImageEditorWidget,
                                                $event,
                                            )
                                        "
                                    />
                                </div>
                                <button
                                    type="button"
                                    aria-label="クロップを調整"
                                    class="absolute right-8 bottom-8 flex size-10 cursor-pointer items-center justify-center rounded-xl shadow-md transition-colors"
                                    :class="
                                        croppingWidgetId ===
                                        mobileImageEditorWidget.id
                                            ? 'bg-white text-black'
                                            : 'bg-black text-white'
                                    "
                                    @click.stop="
                                        croppingWidgetId =
                                            croppingWidgetId ===
                                            mobileImageEditorWidget.id
                                                ? null
                                                : mobileImageEditorWidget.id
                                    "
                                >
                                    <Crop class="size-5" />
                                </button>
                                <button
                                    type="button"
                                    aria-label="画像を変更"
                                    :disabled="
                                        croppingWidgetId ===
                                        mobileImageEditorWidget.id
                                    "
                                    class="absolute bottom-8 left-8 flex h-10 cursor-pointer items-center justify-center rounded-xl bg-white px-4 text-sm font-bold text-black shadow-md transition-transform active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                                    @click.stop="chooseMobileImage"
                                >
                                    変更
                                </button>
                                <input
                                    ref="mobileImageInput"
                                    type="file"
                                    accept="image/*,.apng"
                                    class="hidden"
                                    @change="updateMobileImage"
                                />
                            </div>
                        </div>
                        <label class="grid gap-2">
                            <span class="text-sm font-bold">キャプション</span>
                            <input
                                :value="
                                    mobileImageEditorWidget.settings?.title ??
                                    ''
                                "
                                type="text"
                                :maxlength="maxTextLength"
                                class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-base font-semibold text-gray-900 transition-colors outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/15"
                                placeholder="キャプションを入力"
                                @input="updateMobileImageCaption"
                            />
                        </label>
                        <label class="grid gap-2">
                            <span class="text-sm font-bold">リンク</span>
                            <input
                                :value="mobileImageEditorWidget.content ?? ''"
                                type="url"
                                class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-base font-semibold text-gray-900 transition-colors outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/15"
                                placeholder="https://..."
                                @input="updateMobileImageLink"
                            />
                        </label>
                        <label
                            class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-3"
                            :class="
                                mobileImageEditorWidget.content
                                    ? ''
                                    : 'opacity-50'
                            "
                        >
                            <span class="text-sm font-bold"
                                >開く前に確認を表示</span
                            >
                            <button
                                type="button"
                                role="switch"
                                :disabled="!mobileImageEditorWidget.content"
                                :aria-checked="
                                    Boolean(
                                        mobileImageEditorWidget.content &&
                                        mobileImageEditorWidget.settings
                                            ?.sensitive,
                                    )
                                "
                                aria-label="開く前に確認を表示"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed"
                                :class="
                                    mobileImageEditorWidget.content &&
                                    mobileImageEditorWidget.settings?.sensitive
                                        ? 'bg-blue-600'
                                        : 'bg-gray-300'
                                "
                                @click.prevent.stop="updateMobileImageSensitive"
                            >
                                <span
                                    class="pointer-events-none inline-block size-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                    :class="
                                        mobileImageEditorWidget.content &&
                                        mobileImageEditorWidget.settings
                                            ?.sensitive
                                            ? 'translate-x-5'
                                            : 'translate-x-0'
                                    "
                                ></span>
                            </button>
                        </label>
                    </div>
                </section>
            </Transition>

            <Transition name="mobile-sheet">
                <section
                    v-if="viewportSmall && mobileMapEditorWidget"
                    class="fixed inset-x-0 bottom-0 z-[9999] max-h-[92vh] overflow-hidden rounded-t-3xl border border-gray-200 bg-white text-gray-950 shadow-2xl"
                    @click.stop
                    @pointerdown.stop
                    @touchstart.stop
                >
                    <header
                        class="sticky top-0 z-10 border-b border-gray-100 bg-inherit"
                    >
                        <div
                            class="flex h-16 items-center justify-between px-5"
                        >
                            <div class="size-8"></div>
                            <h2 class="text-base font-bold">地図を編集</h2>
                            <button
                                type="button"
                                class="cursor-pointer rounded-full bg-black px-3 py-1.5 text-xs font-bold text-white shadow-sm transition-colors hover:bg-black"
                                @click="closeMobileEditors"
                            >
                                完了
                            </button>
                        </div>
                    </header>
                    <div
                        class="flex max-h-[calc(92vh-64px)] flex-col gap-5 overflow-y-auto px-5 pt-2 pb-6"
                    >
                        <div
                            class="relative mx-auto overflow-hidden rounded-2xl"
                            :style="
                                mobileWidgetPreviewStyle(mobileMapEditorWidget)
                            "
                        >
                            <ProfileWidget
                                :widget="mobileMapEditorWidget"
                                mode="mobile"
                                :page-theme="pageTheme"
                                :corner-class="cornerClass"
                                :is-editing="true"
                                :hide-image-link-icon="true"
                                :is-map-moving="
                                    String(activeMapMovingWidgetId) ===
                                    String(mobileMapEditorWidget.id)
                                "
                                @update-title="
                                    updateWidgetTitle(
                                        mobileMapEditorWidget,
                                        $event,
                                    )
                                "
                                @update-map-center="
                                    updateMapWidgetCenter(
                                        mobileMapEditorWidget,
                                        $event,
                                    )
                                "
                            />
                        </div>
                        <div class="flex justify-center gap-2">
                            <button
                                type="button"
                                class="flex size-10 cursor-pointer items-center justify-center rounded-xl bg-gray-100 font-bold"
                                @click="
                                    updateMapWidgetZoom(
                                        mobileMapEditorWidget,
                                        -1,
                                    )
                                "
                            >
                                -
                            </button>
                            <button
                                type="button"
                                class="flex h-10 cursor-pointer items-center justify-center rounded-xl bg-black px-4 text-sm font-bold text-white"
                                @click="toggleMapMove(mobileMapEditorWidget)"
                            >
                                移動
                            </button>
                            <button
                                type="button"
                                class="flex size-10 cursor-pointer items-center justify-center rounded-xl bg-gray-100 font-bold"
                                @click="
                                    updateMapWidgetZoom(
                                        mobileMapEditorWidget,
                                        1,
                                    )
                                "
                            >
                                +
                            </button>
                        </div>
                        <label class="grid gap-2">
                            <span class="text-sm font-bold">タイトル</span>
                            <input
                                :value="
                                    mobileMapEditorWidget.settings?.title ?? ''
                                "
                                type="text"
                                class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-base font-semibold text-gray-900 transition-colors outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/15"
                                placeholder="タイトルを入力"
                                @input="updateMobileMapTitle"
                            />
                        </label>
                    </div>
                </section>
            </Transition>

            <Transition name="mobile-sheet">
                <section
                    v-if="viewportSmall && mobileTextEditorWidget"
                    class="fixed inset-x-0 bottom-0 z-[9999] max-h-[92vh] overflow-hidden rounded-t-3xl border border-gray-200 bg-white text-gray-950 shadow-2xl"
                    @click.stop
                    @pointerdown.stop
                    @touchstart.stop
                >
                    <header
                        class="sticky top-0 z-10 border-b border-gray-100 bg-inherit"
                    >
                        <div
                            class="flex h-16 items-center justify-between px-5"
                        >
                            <button
                                v-if="mobileTextEditorMode === 'add'"
                                type="button"
                                class="flex size-8 cursor-pointer items-center justify-center rounded-full text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-950"
                                aria-label="キャンセル"
                                title="キャンセル"
                                @click="closeMobileTextEditor"
                            >
                                <X class="size-5" />
                            </button>
                            <div v-else class="size-8"></div>
                            <h2 class="text-base font-bold">
                                {{
                                    mobileTextEditorMode === 'add'
                                        ? 'テキストを追加'
                                        : 'テキストを編集'
                                }}
                            </h2>
                            <button
                                type="button"
                                class="cursor-pointer rounded-full bg-black px-3 py-1.5 text-xs font-bold text-white shadow-sm transition-colors hover:bg-black"
                                @click="completeMobileTextEditor"
                            >
                                {{
                                    mobileTextEditorMode === 'add'
                                        ? '追加'
                                        : '完了'
                                }}
                            </button>
                        </div>
                    </header>
                    <div
                        class="flex max-h-[calc(92vh-64px)] flex-col gap-5 overflow-y-auto px-5 pt-2 pb-6"
                    >
                        <div
                            class="relative mx-auto overflow-hidden rounded-2xl"
                            :style="
                                mobileWidgetPreviewStyle(mobileTextEditorWidget)
                            "
                        >
                            <ProfileWidget
                                :widget="mobileTextEditorWidget"
                                mode="mobile"
                                :page-theme="pageTheme"
                                :corner-class="cornerClass"
                                :is-editing="true"
                                :hide-image-link-icon="true"
                                @update-title="
                                    updateWidgetTitle(
                                        mobileTextEditorWidget,
                                        $event,
                                    )
                                "
                            />
                        </div>
                        <label class="grid gap-2">
                            <span class="text-sm font-bold">URL</span>
                            <input
                                :value="mobileTextEditorWidget.content ?? ''"
                                type="url"
                                class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-base font-semibold text-gray-900 transition-colors outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/15"
                                placeholder="https://..."
                                @input="updateMobileTextLink"
                            />
                        </label>
                        <label
                            class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-3"
                            :class="
                                mobileTextEditorWidget.content
                                    ? ''
                                    : 'opacity-50'
                            "
                        >
                            <span class="text-sm font-bold"
                                >開く前に確認を表示</span
                            >
                            <button
                                type="button"
                                role="switch"
                                :disabled="!mobileTextEditorWidget.content"
                                :aria-checked="
                                    Boolean(
                                        mobileTextEditorWidget.content &&
                                        mobileTextEditorWidget.settings
                                            ?.sensitive,
                                    )
                                "
                                aria-label="開く前に確認を表示"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed"
                                :class="
                                    mobileTextEditorWidget.content &&
                                    mobileTextEditorWidget.settings?.sensitive
                                        ? 'bg-blue-600'
                                        : 'bg-gray-300'
                                "
                                @click.prevent.stop="updateMobileTextSensitive"
                            >
                                <span
                                    class="pointer-events-none inline-block size-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                    :class="
                                        mobileTextEditorWidget.content &&
                                        mobileTextEditorWidget.settings
                                            ?.sensitive
                                            ? 'translate-x-5'
                                            : 'translate-x-0'
                                    "
                                ></span>
                            </button>
                        </label>
                    </div>
                </section>
            </Transition>

            <Transition name="mobile-sheet">
                <section
                    v-if="viewportSmall && mobileSectionEditorWidget"
                    class="fixed inset-x-0 bottom-0 z-[9999] max-h-[92vh] overflow-hidden rounded-t-3xl border border-gray-200 bg-white text-gray-950 shadow-2xl"
                    @click.stop
                    @pointerdown.stop
                    @touchstart.stop
                >
                    <header
                        class="sticky top-0 z-10 border-b border-gray-100 bg-inherit"
                    >
                        <div
                            class="flex h-16 items-center justify-between px-5"
                        >
                            <button
                                v-if="mobileSectionEditorMode === 'add'"
                                type="button"
                                class="flex size-8 cursor-pointer items-center justify-center rounded-full text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-950"
                                aria-label="キャンセル"
                                title="キャンセル"
                                @click="closeMobileSectionEditor"
                            >
                                <X class="size-5" />
                            </button>
                            <div v-else class="size-8"></div>
                            <h2 class="text-base font-bold">
                                {{
                                    mobileSectionEditorMode === 'add'
                                        ? 'セクションを追加'
                                        : 'セクションを編集'
                                }}
                            </h2>
                            <button
                                type="button"
                                class="cursor-pointer rounded-full bg-black px-3 py-1.5 text-xs font-bold text-white shadow-sm transition-colors hover:bg-black"
                                @click="completeMobileSectionEditor"
                            >
                                {{
                                    mobileSectionEditorMode === 'add'
                                        ? '追加'
                                        : '完了'
                                }}
                            </button>
                        </div>
                    </header>
                    <div
                        class="flex max-h-[calc(92vh-64px)] flex-col gap-5 overflow-y-auto px-5 pt-2 pb-6"
                    >
                        <label class="grid gap-2">
                            <span class="text-sm font-bold">セクション</span>
                            <input
                                :value="
                                    mobileSectionEditorWidget.content ??
                                    mobileSectionEditorWidget.settings?.title ??
                                    ''
                                "
                                type="text"
                                class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-base font-semibold text-gray-900 transition-colors outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/15"
                                placeholder="セクションを入力"
                                @input="updateMobileSectionTitle"
                            />
                            <span
                                v-if="mobileSectionEditorError"
                                class="text-xs font-semibold text-red-600"
                                >{{ mobileSectionEditorError }}</span
                            >
                        </label>
                    </div>
                </section>
            </Transition>

            <LinkAddModal
                :show="showAddLinkModal"
                :initial-url="linkTargetWidget?.content"
                :initial-sensitive="
                    Boolean(linkTargetWidget?.settings?.sensitive)
                "
                :allow-empty="Boolean(linkTargetWidget)"
                :title="linkTargetWidget ? 'リンクを設定' : 'リンクを追加'"
                :submit-label="linkTargetWidget ? '設定' : '追加'"
                @close="closeAddLinkModal"
                @add="addLinkWidget"
            />
        </template>
    </main>
</template>

<style scoped>
@keyframes widgetBounceIn {
    0% {
        opacity: 0;
        transform: scale(0.94);
    }

    36% {
        opacity: 1;
        transform: scale(1.12);
    }

    62% {
        opacity: 1;
        transform: scale(0.97);
    }

    82% {
        opacity: 1;
        transform: scale(1.03);
    }

    100% {
        opacity: 1;
        transform: scale(1);
    }
}

.widget-bounce-enter {
    animation: widgetBounceIn 0.58s cubic-bezier(0.19, 1, 0.22, 1) both;
    transform-origin: center;
}

.mobile-sheet-enter-active,
.mobile-sheet-leave-active {
    transition:
        transform 0.28s cubic-bezier(0.19, 1, 0.22, 1),
        opacity 0.2s ease;
}

.mobile-sheet-enter-from,
.mobile-sheet-leave-to {
    opacity: 0;
    transform: translateY(100%);
}

@keyframes confettiFall {
    0% {
        opacity: 0;
        transform: translateY(-10vh) rotate(0deg);
    }

    12% {
        opacity: 1;
    }

    100% {
        opacity: 0;
        transform: translateY(110vh) rotate(760deg);
    }
}

.confetti-piece {
    animation: confettiFall 1.55s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.editor-placeholder:empty::before {
    display: block;
    color: #9ca3af;
    content: attr(data-placeholder);
    pointer-events: none;
}

:global(body.is-dragging *) {
    user-select: none !important;
    -webkit-user-select: none !important;
}

:global(body.is-dragging .vgl-item:not(.vgl-item--dragging)) {
    pointer-events: none !important;
}

:global(.link-page--editing .vgl-item.vgl-item--dragging) {
    z-index: 9999 !important;
    background-color: transparent !important;
}

:global(.link-page--editing .vgl-item:has(.link-widget-controls)) {
    z-index: 150 !important;
}

:global(.vgl-item.is-cropping) {
    z-index: 200 !important;
}

:global(.link-page:not(.link-page--editing) .vgl-item),
:global(.link-page:not(.link-page--editing) .vgl-item *) {
    cursor: default !important;
}

:global(.cursor-pointer),
:global(button:not(:disabled)),
:global([role='button']:not(:disabled)),
:global(.vgl-item a),
:global(.vgl-item button),
:global(.link-page:not(.link-page--editing) .vgl-item a),
:global(.link-page:not(.link-page--editing) .vgl-item a *) {
    cursor: pointer !important;
}

:global(.vgl-item--placeholder),
:global(.vue-grid-item.vue-grid-placeholder) {
    z-index: 2;
    border: 2px dashed rgb(156 163 175) !important;
    border-radius: 1rem !important;
    background: rgb(209 213 219 / 0.8) !important;
    transition-duration: 100ms;
    user-select: none;
}

:global(.vgl-item:not(.vgl-item--dragging):not(.vgl-item--resizing)) {
    transition:
        transform 0.2s ease,
        width 0.2s ease,
        height 0.2s ease !important;
}

:global(.link-page--instant-switch),
:global(.link-page--instant-switch *),
:global(.link-page--instant-layout),
:global(.link-page--instant-layout *) {
    transition: none !important;
    transition-duration: 0ms !important;
    animation-duration: 0ms !important;
}

:global(.link-page--instant-switch .vgl-item),
:global(.link-page--instant-layout .vgl-item) {
    transition: none !important;
}

@media (prefers-reduced-motion: reduce) {
    .widget-bounce-enter {
        animation-duration: 0.16s;
        animation-name: widgetFadeIn;
    }
}

@keyframes widgetFadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}
</style>
