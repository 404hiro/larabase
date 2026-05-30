<script setup lang="ts">
import LinkAddModal from '@/components/links/LinkAddModal.vue';
import LinkProfile from '@/components/links/LinkProfile.vue';
import LinkToolbar from '@/components/links/LinkToolbar.vue';
import LinkWidgetContent from '@/components/links/LinkWidgetContent.vue';
import LinkWidgetControls from '@/components/links/LinkWidgetControls.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Toaster } from '@/components/ui/toast';
import { click as widgetClick } from '@/routes/widgets';
import { compressImage } from '@/utils/imageCompression';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { usePreferredReducedMotion, useWindowSize } from '@vueuse/core';
import axios from 'axios';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';
import { GridItem, GridLayout } from 'grid-layout-plus';
import {
    AlignCenter,
    AlignLeft,
    AlignRight,
    AlignVerticalJustifyCenter,
    AlignVerticalJustifyEnd,
    AlignVerticalJustifyStart,
    Check,
    Copy,
    Crop,
    Flag,
    Image as ImageIcon,
    LayoutGrid,
    Link as LinkIcon,
    Minus,
    MoreHorizontal,
    Move,
    Pencil,
    Plus,
    RectangleHorizontal,
    RectangleVertical,
    Search,
    Square,
    Trash2,
    Type,
    X,
} from 'lucide-vue-next';
import { useMotionValue, useSpring } from 'motion-v';
import {
    computed,
    h,
    nextTick,
    onMounted,
    onUnmounted,
    reactive,
    ref,
    watch,
} from 'vue';

const RectangleThin = (props: any) =>
    h(
        'svg',
        {
            xmlns: 'http://www.w3.org/2000/svg',
            viewBox: '0 0 24 24',
            fill: 'none',
            class: props.class,
        },
        [
            h('path', {
                d: 'M19 10H5C3.89543 10 3 10.199 3 10.4444V13.5556C3 13.801 3.89543 14 5 14H19C20.1046 14 21 13.801 21 13.5556V10.4444C21 10.199 20.1046 10 19 10Z',
                stroke: 'currentColor',
                'stroke-width': '2',
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
            }),
        ],
    );

const RectangleHalfThin = (props: any) =>
    h(
        'svg',
        {
            xmlns: 'http://www.w3.org/2000/svg',
            viewBox: '0 0 24 24',
            fill: 'none',
            class: props.class,
        },
        [
            h('path', {
                d: 'M12 10H5C3.89543 10 3 10.199 3 10.4444V13.5556C3 13.801 3.89543 14 5 14H12C13.1046 14 14 13.801 14 13.5556V10.4444C14 10.199 13.1046 10 12 10Z',
                stroke: 'currentColor',
                'stroke-width': '2',
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
            }),
        ],
    );

const props = defineProps<{
    link: {
        id: number;
        user_id: number;
        slug: string;
        display_name: string;
        bio?: string | null;
        avatar_url?: string | null;
        is_published?: boolean;
        has_web_display?: boolean;
        theme_config?: {
            theme?: 'light' | 'dark' | null;
            widget_style?: 'sharp' | 'soft' | 'rounded' | null;
        } | null;
        widgets?: any[];
    };
    is_editing?: boolean;
}>();

const getWidgetHref = (widget: any) => {
    if (
        !widget.content ||
        widget.type === 'section' ||
        widget.type === 'map' ||
        isEditing.value
    ) {
        return undefined;
    }

    return widgetClick.url({
        link: props.link.slug,
        widget: widget.id,
    });
};

const MAX_WIDGETS = 50;
const MAX_URL_LENGTH = 2000;
const MAX_LINK_TITLE_LENGTH = 100;
const MAX_TEXT_WIDGET_LENGTH = 4500;

const page = usePage();
const isOwner = computed(() => {
    return (
        page.props.auth?.user && page.props.auth.user.id === props.link.user_id
    );
});

const previewMode = ref<'desktop' | 'mobile'>('desktop');
const isEditing = ref(isOwner.value && Boolean(props.is_editing));
const isLinkPublished = ref(Boolean(props.link.is_published));
const isPublishingLink = ref(false);
const copiedProfileUrl = ref(false);
const hasWebDisplay = computed(() => Boolean(props.link.has_web_display));
const shouldForceMobileForVisitor = computed(() => {
    return !isOwner.value && !hasWebDisplay.value;
});
const activePreviewMode = computed<'desktop' | 'mobile'>(() => {
    return shouldForceMobileForVisitor.value ? 'mobile' : previewMode.value;
});
const shouldShowDesktopGrid = computed(() => {
    return (
        !shouldForceMobileForVisitor.value &&
        !isSmallViewport.value &&
        activePreviewMode.value === 'desktop'
    );
});
const shouldShowMobileGrid = computed(() => {
    return (
        shouldForceMobileForVisitor.value ||
        isSmallViewport.value ||
        activePreviewMode.value === 'mobile'
    );
});
const LINK_PAGE_THEME_BACKGROUNDS = {
    light: '#ffffff',
    dark: '#111111',
} as const;
const pageTheme = ref<'light' | 'dark'>(
    props.link.theme_config?.theme === 'dark' ? 'dark' : 'light',
);
const widgetStyle = ref<'sharp' | 'soft' | 'rounded'>(
    ['sharp', 'soft', 'rounded'].includes(
        String(props.link.theme_config?.widget_style),
    )
        ? (props.link.theme_config?.widget_style as
              | 'sharp'
              | 'soft'
              | 'rounded')
        : 'rounded',
);
const widgetCornerClass = computed(() => {
    return widgetStyle.value === 'sharp'
        ? 'rounded-none'
        : widgetStyle.value === 'soft'
          ? 'rounded-xl'
          : 'rounded-2xl';
});
const pageThemeClasses = computed(() => {
    return pageTheme.value === 'dark'
        ? 'bg-[#111111] text-white'
        : 'bg-white text-gray-950';
});
const pageBackgroundColor = computed(() => {
    return LINK_PAGE_THEME_BACKGROUNDS[pageTheme.value];
});

let previousHtmlBackgroundColor = '';
let previousBodyBackgroundColor = '';

const applyDocumentBackgroundColor = () => {
    document.documentElement.style.backgroundColor = pageBackgroundColor.value;
    document.body.style.backgroundColor = pageBackgroundColor.value;
};

const editForm = ref({
    display_name: props.link.display_name,
    bio: props.link.bio ?? '',
});
const avatarFile = ref<File | null>(null);
const avatarPreviewUrl = ref<string | null>(null);
const avatarRemoved = ref(false);

const localWidgets = ref<any[]>([]);
const desktopLayout = ref<any[]>([]);
const mobileLayout = ref<any[]>([]);
type ConfettiPiece = {
    id: number;
    x: number;
    y: number;
    rotate: number;
    delay: number;
    duration: number;
    color: string;
    size: number;
};

const confettiPieces = ref<ConfettiPiece[]>([]);
let confettiTimeout: number | null = null;

const newlyAddedWidgetIds = ref<Set<string>>(new Set());
const markWidgetAsNewlyAdded = (widget: any) => {
    if (widget?.id === undefined || widget?.id === null) {
        return;
    }

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

const addLocalWidget = (widget: any) => {
    localWidgets.value.push(widget);
    markWidgetAsNewlyAdded(widget);
};

const pageScrollContainer = ref<HTMLElement | null>(null);

const draggingWidgetId = ref<string | number | null>(null);
const draggingWidgetMode = ref<'desktop' | 'mobile' | null>(null);
const suppressWidgetClickUntil = ref(0);
const croppingWidgetId = ref<string | number | null>(null);
const activeMapMovingWidgetId = ref<string | number | null>(null);
const isSmallViewport = ref(false);
const isPreviewLayoutSwitching = ref(false);
let suppressWidgetClickTimeout: number | null = null;
let dragSettleTimeout: number | null = null;
let previewLayoutSwitchTimeout: number | null = null;
const prefersReducedMotion = usePreferredReducedMotion();
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
    translateX: number;
    translateY: number;
    rotate: number;
    scale: number;
    boxShadow: string;
} | null>(null);

const isSuppressingWidgetInteractions = computed(() => {
    return (
        draggingWidgetId.value !== null ||
        suppressWidgetClickUntil.value > Date.now()
    );
});

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
    markDirty();
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
    markDirty();
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
            icon: RectangleHalfThin,
            label: '1x2',
            size: { w: 2, h: 1 },
        },
        {
            key: 'section-wide',
            icon: RectangleThin,
            label: '1x4',
            size: { w: 4, h: 1 },
        },
    ],
    mobile: [
        {
            key: 'section-compact',
            icon: RectangleHalfThin,
            label: '1x2',
            size: { w: 1, h: 1 },
        },
        {
            key: 'section-wide',
            icon: RectangleThin,
            label: '1x4',
            size: { w: 2, h: 1 },
        },
    ],
};
const widgetSizeOptionList = [
    {
        key: 'inline',
        icon: RectangleThin,
        label: 'インライン',
        size: { w: 2, h: 1 },
    },
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

    const maxY = widgets.reduce((max, widget) => {
        const widgetY = Number(widget[keys.y] ?? 0);
        const widgetH = Number(widget[keys.h] ?? 1);

        return Math.max(max, widgetY + widgetH);
    }, 0);

    for (let y = 0; y < maxY + 1000; y += 1) {
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

    return { x: 0, y: maxY };
};

const desktopPlaceholderItems = computed(() => {
    if (localWidgets.value.length >= 3) {
        return [];
    }

    const sourceWidgets = localWidgets.value.map((widget) => ({ ...widget }));
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

            return !localWidgets.value.some(
                (widget) => widget.type === widgetType,
            );
        })
        .map((placeholder) => {
            const position = findNextGridPosition(
                sourceWidgets,
                4,
                placeholder.w,
                placeholder.h,
                desktopKeys,
            );
            const placedWidget = {
                x: position.x,
                y: position.y,
                w: placeholder.w,
                h: placeholder.h,
            };

            sourceWidgets.push(placedWidget);

            return {
                ...placeholder,
                ...placedWidget,
            };
        });
});

const mobilePlaceholderItems = computed(() => {
    if (localWidgets.value.length >= 3) {
        return [];
    }

    if (localWidgets.value.some((widget) => widget.type === 'link')) {
        return [];
    }

    const position = findNextGridPosition(
        localWidgets.value,
        2,
        1,
        4,
        mobileKeys,
    );

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
            item.x = Math.max(columns - width, 0);
        }
        item.w = width;
        item.h = size.h;
    }

    const pushCollidingItems = (target: any) => {
        let moved = false;

        for (const other of layout) {
            if (other.i === target.i) {
                continue;
            }

            const overlaps =
                target.x < other.x + other.w &&
                target.x + target.w > other.x &&
                target.y < other.y + other.h &&
                target.y + target.h > other.y;

            if (overlaps) {
                other.y = target.y + target.h;
                moved = true;
                pushCollidingItems(other);
            }
        }

        return moved;
    };

    if (item) {
        pushCollidingItems(item);
        layout.sort((a, b) => {
            if (a.i === item.i) {
                return -1;
            }

            if (b.i === item.i) {
                return 1;
            }

            return a.y === b.y ? a.x - b.x : a.y - b.y;
        });
    }

    if (mode === 'desktop') {
        desktopLayout.value = [...desktopLayout.value];
        nextTick(() => syncWidgetsFromDesktop());
    } else {
        mobileLayout.value = [...mobileLayout.value];
        nextTick(() => syncWidgetsFromMobile());
    }

    markDirty();
};

const widgetSizeOptions = (widget: any, mode: 'desktop' | 'mobile') => {
    if (widget.type === 'section') {
        return sectionSizeOptions[mode];
    }

    if (widget.type !== 'link') {
        return widgetSizeOptionList.filter((option) => option.key !== 'inline');
    }

    return widgetSizeOptionList;
};

const activeMobileLayoutItem = computed(() => {
    if (
        !isSmallViewport.value ||
        !isEditing.value ||
        activeWidgetId.value === null
    ) {
        return null;
    }

    return (
        mobileLayout.value.find(
            (item) => String(item.i) === String(activeWidgetId.value),
        ) ?? null
    );
});

const activeMobileSizeOptions = computed(() => {
    if (!activeMobileLayoutItem.value) {
        return [];
    }

    return widgetSizeOptions(activeMobileLayoutItem.value.widget, 'mobile');
});

const activateMobileWidget = (widgetId: string | number) => {
    if (isSmallViewport.value && isEditing.value) {
        activeWidgetId.value = widgetId;
        croppingWidgetId.value = null;
    }
};

const completeMobileWidgetOperation = () => {
    activeWidgetId.value = null;
    croppingWidgetId.value = null;
    activeMapMovingWidgetId.value = null;
    lockedControlsWidgetId.value = null;
};

const editMobileWidget = (widget: any) => {
    if (widget.type === 'link') {
        openMobileLinkEditor(widget);

        return;
    }

    if (widget.type === 'image') {
        openMobileImageEditor(widget);

        return;
    }

    if (widget.type === 'map') {
        openMobileMapEditor(widget);

        return;
    }

    if (widget.type === 'text') {
        openMobileTextEditor(widget);

        return;
    }

    if (widget.type === 'section') {
        openMobileSectionEditor(widget);
    }
};

const resizeActiveMobileWidget = (size: { w: number; h: number }) => {
    if (!activeMobileLayoutItem.value) {
        return;
    }

    resizeWidget(activeMobileLayoutItem.value.i, 'mobile', size);
};

const updateViewportMode = () => {
    isSmallViewport.value = window.matchMedia('(max-width: 1024px)').matches;
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

onMounted(() => {
    previousHtmlBackgroundColor =
        document.documentElement.style.backgroundColor;
    previousBodyBackgroundColor = document.body.style.backgroundColor;
    applyDocumentBackgroundColor();
    updateViewportMode();
    window.addEventListener('resize', updateViewportMode);
    window.addEventListener('beforeunload', handleBeforeUnload);
    window.addEventListener('pointermove', handleWindowPointerMove);
    window.addEventListener('pointerup', handleWindowPointerUp);
    window.addEventListener('pointercancel', handleWindowPointerUp);
    removeInertiaBeforeListener.value = router.on('before', (event) => {
        if (!shouldConfirmUnsavedChanges()) {
            return;
        }

        if (!window.confirm('未保存の変更があります。ページを離れますか？')) {
            event.preventDefault();
        }
    });
});

onUnmounted(() => {
    document.documentElement.style.backgroundColor =
        previousHtmlBackgroundColor;
    document.body.style.backgroundColor = previousBodyBackgroundColor;

    if (avatarPreviewUrl.value) {
        URL.revokeObjectURL(avatarPreviewUrl.value);
    }

    if (suppressWidgetClickTimeout !== null) {
        window.clearTimeout(suppressWidgetClickTimeout);
    }

    if (dragSettleTimeout !== null) {
        window.clearTimeout(dragSettleTimeout);
    }

    if (confettiTimeout !== null) {
        window.clearTimeout(confettiTimeout);
    }

    if (previewLayoutSwitchTimeout !== null) {
        window.clearTimeout(previewLayoutSwitchTimeout);
    }

    if (mobileMapSearchTimeout !== null) {
        window.clearTimeout(mobileMapSearchTimeout);
    }

    mobileMapSearchAbortController?.abort();
    window.removeEventListener('resize', updateViewportMode);
    window.removeEventListener('beforeunload', handleBeforeUnload);
    window.removeEventListener('pointermove', handleWindowPointerMove);
    window.removeEventListener('pointerup', handleWindowPointerUp);
    window.removeEventListener('pointercancel', handleWindowPointerUp);
    removeInertiaBeforeListener.value?.();
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

watch(
    () => props.link.is_published,
    (isPublished) => {
        isLinkPublished.value = Boolean(isPublished);
    },
);

watch(
    () => [editForm.value.display_name, editForm.value.bio],
    () => markDirty(),
);

watch([pageTheme, widgetStyle], () => markDirty());

watch(pageBackgroundColor, () => {
    applyDocumentBackgroundColor();
});

watch([activePreviewMode, isSmallViewport], () => {
    disableLayoutTransitionsBriefly();
    updateLayoutsFromWidgets();
});

const toggleEdit = () => {
    if (isEditing.value) {
        editForm.value.display_name = editForm.value.display_name.replace(
            /\s+/g,
            '',
        );
        isSavingChanges.value = true;
        // Save link data
        router.post(
            `/links/${props.link.slug}`,
            {
                _method: 'put',
                display_name: editForm.value.display_name,
                bio: editForm.value.bio,
                has_web_display: !isSmallViewport.value,
                theme_config: {
                    theme: pageTheme.value,
                    widget_style: widgetStyle.value,
                },
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
                                hasUnsavedChanges.value = false;
                                isEditing.value = false;
                                activeWidgetId.value = null;
                                activeMapMovingWidgetId.value = null;
                                window.scrollTo({ top: 0, behavior: 'smooth' });
                            },
                            onError: () => {
                                showSaveValidationErrorToast();
                            },
                            onFinish: () => {
                                isSavingChanges.value = false;
                            },
                        },
                    );
                },
                onError: () => {
                    showSaveValidationErrorToast();
                    isSavingChanges.value = false;
                },
            },
        );
    } else {
        isEditing.value = true;
        hasUnsavedChanges.value = false;
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

const profileUrl = computed(() => {
    if (typeof window === 'undefined') {
        return `/@${props.link.slug}`;
    }

    return `${window.location.origin}/@${props.link.slug}`;
});

const copyProfileUrl = async () => {
    try {
        if (navigator.clipboard) {
            await navigator.clipboard.writeText(profileUrl.value);
        } else {
            const textArea = document.createElement('textarea');
            textArea.value = profileUrl.value;
            textArea.setAttribute('readonly', 'true');
            textArea.className = 'fixed -left-[9999px] top-0';
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }

        copiedProfileUrl.value = true;
        window.setTimeout(() => {
            copiedProfileUrl.value = false;
        }, 2400);
    } catch (error) {
        console.error('Failed to copy profile URL:', error);
    }
};

const reportUser = () => {
    alert('このユーザーを通報しました。ご協力ありがとうございます。');
};

const updateAvatar = (file: File | null) => {
    if (!file) return;

    if (avatarPreviewUrl.value) {
        URL.revokeObjectURL(avatarPreviewUrl.value);
    }

    avatarFile.value = file;
    avatarRemoved.value = false;
    avatarPreviewUrl.value = URL.createObjectURL(file);
    markDirty();
};

const removeAvatar = () => {
    if (avatarPreviewUrl.value) {
        URL.revokeObjectURL(avatarPreviewUrl.value);
    }

    avatarFile.value = null;
    avatarPreviewUrl.value = null;
    avatarRemoved.value = true;
    markDirty();
};

const burstPublishConfetti = () => {
    const colors = [
        '#10B981',
        '#34D399',
        '#F59E0B',
        '#F43F5E',
        '#60A5FA',
        '#A78BFA',
    ];

    confettiPieces.value = Array.from({ length: 34 }, (_, index) => {
        const angle = (Math.PI * 2 * index) / 34 + (Math.random() - 0.5) * 0.4;
        const distance = 80 + Math.random() * 180;

        return {
            id: Date.now() + index,
            x: Math.cos(angle) * distance,
            y: Math.sin(angle) * distance - 120 - Math.random() * 80,
            rotate: Math.random() * 720 - 360,
            delay: Math.random() * 80,
            duration: 720 + Math.random() * 420,
            color: colors[index % colors.length],
            size: 7 + Math.random() * 8,
        };
    });

    if (confettiTimeout !== null) {
        window.clearTimeout(confettiTimeout);
    }

    confettiTimeout = window.setTimeout(() => {
        confettiPieces.value = [];
        confettiTimeout = null;
    }, 1400);
};

const publishLink = () => {
    if (isPublishingLink.value || isLinkPublished.value) {
        return;
    }

    if (localWidgets.value.length === 0) {
        return;
    }

    isPublishingLink.value = true;

    router.post(
        `/links/${props.link.slug}`,
        {
            _method: 'put',
            display_name: editForm.value.display_name.replace(/\s+/g, ''),
            bio: editForm.value.bio,
            theme_config: {
                theme: pageTheme.value,
                widget_style: widgetStyle.value,
            },
            is_published: true,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isLinkPublished.value = true;
                burstPublishConfetti();
            },
            onFinish: () => {
                isPublishingLink.value = false;
            },
        },
    );
};

const updateWidgetTitle = (widget: any, value: string) => {
    const maxLength =
        widget.type === 'link'
            ? MAX_LINK_TITLE_LENGTH
            : widget.type === 'text'
              ? MAX_TEXT_WIDGET_LENGTH
              : MAX_TEXT_WIDGET_LENGTH;
    const limitedValue = trimToLength(value, maxLength);

    if (widget.type === 'section') {
        widget.content = limitedValue;
    } else {
        if (!widget.settings) widget.settings = {};
        widget.settings.title = limitedValue;
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
    activeMapMovingWidgetId.value = null;
    activeWidgetId.value = widgetId;
};

const toggleMapMove = (widgetId: string | number) => {
    activeMapMovingWidgetId.value =
        String(activeMapMovingWidgetId.value) === String(widgetId)
            ? null
            : widgetId;
    croppingWidgetId.value = null;
    activeWidgetId.value = widgetId;
};

const closeMapMove = () => {
    activeMapMovingWidgetId.value = null;
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

const updateWidgetYoutubeMode = (
    widget: any,
    mode: 'link' | 'link_embed' | 'embed',
) => {
    if (!widget.settings) {
        widget.settings = {};
    }
    widget.settings.youtubeMode = mode;
    markDirty();
};

const updateMapWidgetLocation = (
    widget: any,
    location: {
        title: string;
        address: string;
        lat: number;
        lng: number;
        zoom: number;
    },
) => {
    if (!widget.settings) {
        widget.settings = {};
    }

    widget.settings.title = location.title;
    widget.settings.address = location.address;
    widget.settings.lat = location.lat;
    widget.settings.lng = location.lng;
    widget.settings.zoom = location.zoom;
    markDirty();
};

const updateMapWidgetCenter = (
    widget: any,
    center: { lat: number; lng: number; zoom: number },
) => {
    if (!widget.settings) {
        widget.settings = {};
    }

    widget.settings.lat = center.lat;
    widget.settings.lng = center.lng;
    widget.settings.zoom = center.zoom;
    markDirty();
};

const updateMapWidgetZoom = (widget: any, delta: number) => {
    if (!widget.settings) {
        widget.settings = {};
    }

    const currentZoom = Number(widget.settings.zoom ?? 15);
    widget.settings.zoom = Math.min(19, Math.max(3, currentZoom + delta));
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
    if (!canAddWidget()) {
        return;
    }

    const isToolbarSizedSection = pendingWidgetSize.value !== null;
    const desktopWidth = isToolbarSizedSection ? 1 : 4;
    const desktopHeight = 1;
    const mobileWidth = isToolbarSizedSection ? 1 : 2;
    const mobileHeight = 1;
    const desktopPosition = findNextGridPosition(
        localWidgets.value,
        4,
        desktopWidth,
        desktopHeight,
        {
            x: 'x',
            y: 'y',
            w: 'w',
            h: 'h',
        },
    );
    const mobilePosition = findNextGridPosition(
        localWidgets.value,
        2,
        mobileWidth,
        mobileHeight,
        {
            x: 'x_mobile',
            y: 'y_mobile',
            w: 'w_mobile',
            h: 'h_mobile',
        },
    );
    const newWidget = {
        id: `temp_section_${Date.now()}`,
        type: 'section',
        content: null,
        thumbnail_url: null,
        x: desktopPosition.x,
        y: desktopPosition.y,
        w: desktopWidth,
        h: desktopHeight,
        x_mobile: mobilePosition.x,
        y_mobile: mobilePosition.y,
        w_mobile: mobileWidth,
        h_mobile: mobileHeight,
        settings: {
            title: '',
        },
    };

    if (isSmallViewport.value) {
        pendingWidgetSize.value = null;
        openMobileSectionEditor(newWidget, 'add');

        return;
    }

    addLocalWidget(newWidget);
    updateLayoutsFromWidgets();
    markDirty();
    scrollToWidgetBottom();
    pendingWidgetSize.value = null;
};

const showAddLinkModal = ref(false);
const showMobileAddLinkSheet = ref(false);
const mobileAddLinkUrl = ref('');
const mobileAddLinkError = ref('');
const mobileAddLinkSensitive = ref(false);
const emptyMediaInput = ref<HTMLInputElement | null>(null);
const linkTargetWidget = ref<any | null>(null);
type WidgetPosition = {
    x: number;
    y: number;
    w: number;
    h: number;
};
type WidgetSize = {
    w: number;
    h: number;
};
const pendingWidgetPosition = ref<WidgetPosition | null>(null);
const pendingWidgetSize = ref<WidgetSize | null>(null);
const toolbarWidgetSize = { w: 1, h: 2 } as const;
const openAddLinkModal = () => {
    linkTargetWidget.value = null;
    pendingWidgetPosition.value = null;
    pendingWidgetSize.value = null;
    showAddLinkModal.value = true;
};

const openAddLinkFromToolbar = () => {
    linkTargetWidget.value = null;
    pendingWidgetPosition.value = null;
    pendingWidgetSize.value = toolbarWidgetSize;

    if (isSmallViewport.value) {
        mobileAddLinkUrl.value = '';
        mobileAddLinkError.value = '';
        mobileAddLinkSensitive.value = false;
        showMobileAddLinkSheet.value = true;

        return;
    }

    showAddLinkModal.value = true;
};

const openMediaPickerFromEmptyState = (position: WidgetPosition) => {
    pendingWidgetPosition.value = position;
    pendingWidgetSize.value = null;
    emptyMediaInput.value?.click();
};

const handleEmptyMediaChange = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    if (file) {
        const compressedFile = await compressImage(file, { preset: 'card' });

        if (compressedFile) {
            await addMediaWidget(compressedFile, pendingWidgetPosition.value);
        }
    }

    input.value = '';
    pendingWidgetPosition.value = null;
    pendingWidgetSize.value = null;
};

const openModalWithPos = (x: number, y: number, w: number, h: number) => {
    linkTargetWidget.value = null;
    pendingWidgetPosition.value = { x, y, w, h };
    pendingWidgetSize.value = null;
    showAddLinkModal.value = true;
};

const closeAddLinkModal = () => {
    showAddLinkModal.value = false;
    linkTargetWidget.value = null;
    pendingWidgetPosition.value = null;
    pendingWidgetSize.value = null;
};

const closeMobileAddLinkSheet = () => {
    showMobileAddLinkSheet.value = false;
    mobileAddLinkUrl.value = '';
    mobileAddLinkError.value = '';
    mobileAddLinkSensitive.value = false;
};

const setMobileAddLinkSheetOpen = (open: boolean) => {
    if (!open) {
        closeMobileAddLinkSheet();
    }
};

const normalizeLinkUrl = (value: string) => {
    const trimmedUrl = value.trim();

    if (!trimmedUrl) {
        return '';
    }

    return /^[a-z][a-z\d+\-.]*:/i.test(trimmedUrl)
        ? trimmedUrl
        : `https://${trimmedUrl}`;
};

const isValidLinkUrl = (value: string) => {
    try {
        new URL(value);

        return true;
    } catch (_) {
        return false;
    }
};

const submitMobileAddLink = () => {
    mobileAddLinkError.value = '';

    const normalizedUrl = normalizeLinkUrl(mobileAddLinkUrl.value);

    if (
        !normalizedUrl ||
        normalizedUrl.length > MAX_URL_LENGTH ||
        !isValidLinkUrl(normalizedUrl)
    ) {
        mobileAddLinkError.value = '有効なURLを入力してください';

        return;
    }

    closeMobileAddLinkSheet();
    addLinkWidget(normalizedUrl, mobileAddLinkSensitive.value);
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
const mobileLinkEditorWidget = ref<any | null>(null);
const mobileLinkImageInput = ref<HTMLInputElement | null>(null);
const mobileImageEditorWidget = ref<any | null>(null);
const mobileImageInput = ref<HTMLInputElement | null>(null);
const isMobileImageCropping = ref(false);
const mobileImageCropPreview = ref<HTMLElement | null>(null);
const mobileImageRef = ref<HTMLImageElement | null>(null);
const mobileCropper = ref<Cropper | null>(null);
const isInitializingMobileCropper = ref(false);
const mobileMapEditorWidget = ref<any | null>(null);
const mobileMapSearchQuery = ref('');
const mobileMapSearchResults = ref<
    Array<{
        place_id: number;
        display_name: string;
        lat: string;
        lon: string;
        name?: string;
    }>
>([]);
const isSearchingMobileMap = ref(false);
const mobileMapSearchError = ref('');
const hasSearchedMobileMap = ref(false);
let mobileMapSearchTimeout: number | null = null;
let mobileMapSearchAbortController: AbortController | null = null;
const mobileTextEditorWidget = ref<any | null>(null);
const mobileTextEditorInput = ref<HTMLElement | null>(null);
const isMobileTextEditorFocused = ref(false);
const mobileTextEditorMode = ref<'add' | 'edit'>('edit');
const mobileSectionEditorWidget = ref<any | null>(null);
const mobileSectionEditorMode = ref<'add' | 'edit'>('edit');
const mobileSectionEditorError = ref('');
const hasUnsavedChanges = ref(false);
const isSavingChanges = ref(false);
const removeInertiaBeforeListener = ref<(() => void) | null>(null);

const showErrorToast = (description: string) => {
    window.dispatchEvent(
        new CustomEvent('app-toast', {
            detail: {
                variant: 'error',
                title: 'エラー',
                description,
            },
        }),
    );
};

const showSaveValidationErrorToast = () => {
    showErrorToast('入力エラーにより保存に失敗しました');
};

const canAddWidget = () => {
    if (localWidgets.value.length < MAX_WIDGETS) {
        return true;
    }

    showErrorToast('ウィジェットは50個まで追加できます');

    return false;
};

const trimToLength = (value: string, maxLength: number) => {
    return value.slice(0, maxLength);
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

const pastePlainTextWithLimit = (event: ClipboardEvent, maxLength: number) => {
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

const shouldConfirmUnsavedChanges = () => {
    return isEditing.value && hasUnsavedChanges.value && !isSavingChanges.value;
};

const markDirty = () => {
    if (isEditing.value) {
        hasUnsavedChanges.value = true;
    }
};

const handleBeforeUnload = (event: BeforeUnloadEvent) => {
    if (!shouldConfirmUnsavedChanges()) {
        return;
    }

    event.preventDefault();
    event.returnValue = '';
};

const ensureWidgetSettings = (widget: any) => {
    if (!widget.settings) {
        widget.settings = {};
    }
};

const commitMobileAddedWidget = (widget: any) => {
    if (!canAddWidget()) {
        return false;
    }

    if (
        !localWidgets.value.some(
            (localWidget) => String(localWidget.id) === String(widget.id),
        )
    ) {
        addLocalWidget(widget);
    }

    updateLayoutsFromWidgets();
    markDirty();
    activeWidgetId.value = widget.id;
    nextTick(scrollToWidgetBottom);

    return true;
};

const closeMobileLinkEditor = () => {
    mobileLinkEditorWidget.value = null;
};

const setMobileLinkEditorOpen = (open: boolean) => {
    if (!open) {
        closeMobileLinkEditor();
    }
};

const openMobileLinkEditor = (widget: any) => {
    ensureWidgetSettings(widget);
    mobileLinkEditorWidget.value = widget;
};

const updateMobileLinkTitle = (event: Event) => {
    if (!mobileLinkEditorWidget.value) {
        return;
    }

    ensureWidgetSettings(mobileLinkEditorWidget.value);
    mobileLinkEditorWidget.value.settings.title = (
        event.target as HTMLInputElement
    ).value.slice(0, MAX_LINK_TITLE_LENGTH);
    markDirty();
};

const chooseMobileLinkImage = () => {
    mobileLinkImageInput.value?.click();
};

const updateMobileLinkImage = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    const widget = mobileLinkEditorWidget.value;

    if (file && widget) {
        const compressedFile = await compressImage(file, { preset: 'card' });
        if (!compressedFile) return;

        await uploadWidgetImage(widget, compressedFile);
    }

    input.value = '';
};

const removeMobileLinkImage = () => {
    if (!mobileLinkEditorWidget.value) {
        return;
    }

    removeWidgetImage(mobileLinkEditorWidget.value);
};

const updateMobileLinkSensitive = () => {
    if (!mobileLinkEditorWidget.value) {
        return;
    }

    updateWidgetSensitive(
        mobileLinkEditorWidget.value,
        !Boolean(mobileLinkEditorWidget.value.settings?.sensitive),
    );
};

const closeMobileImageEditor = () => {
    mobileImageEditorWidget.value = null;
    isMobileImageCropping.value = false;
};

const setMobileImageEditorOpen = (open: boolean) => {
    if (!open) {
        closeMobileImageEditor();
    }
};

const openMobileImageEditor = (widget: any) => {
    ensureWidgetSettings(widget);
    widget.settings.cropX ??= 50;
    widget.settings.cropY ??= 50;
    mobileImageEditorWidget.value = widget;
};

const mobileImageEditorPreviewStyle = computed(() => {
    const widget = mobileImageEditorWidget.value;

    if (!widget) {
        return {
            height: '250px',
            width: '250px',
        };
    }

    const width = Number(widget.w_mobile ?? 1);
    const height = Number(widget.h_mobile ?? 2) / 2;
    const aspectRatio = width / height;

    if (aspectRatio >= 1) {
        return {
            height: `${250 / aspectRatio}px`,
            width: '250px',
        };
    }

    return {
        height: '250px',
        width: `${250 * aspectRatio}px`,
    };
});

const mobileImageEditorCropStyle = computed(() => ({
    objectPosition: `${Number(
        mobileImageEditorWidget.value?.settings?.cropX ?? 50,
    )}% ${Number(mobileImageEditorWidget.value?.settings?.cropY ?? 50)}%`,
}));

const chooseMobileImage = () => {
    mobileImageInput.value?.click();
};

const updateMobileImage = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    const widget = mobileImageEditorWidget.value;

    if (file && widget) {
        const compressedFile = await compressImage(file, { preset: 'card' });
        if (!compressedFile) return;

        await uploadWidgetImage(widget, compressedFile);
    }

    input.value = '';
};

const updateMobileImageCaption = (event: Event) => {
    if (!mobileImageEditorWidget.value) {
        return;
    }

    updateWidgetTitle(
        mobileImageEditorWidget.value,
        (event.target as HTMLInputElement).value,
    );
};

const updateMobileImageLink = (event: Event) => {
    if (!mobileImageEditorWidget.value) {
        return;
    }

    mobileImageEditorWidget.value.content = (
        event.target as HTMLInputElement
    ).value
        .trim()
        .slice(0, MAX_URL_LENGTH);

    if (
        !mobileImageEditorWidget.value.content &&
        mobileImageEditorWidget.value.settings
    ) {
        mobileImageEditorWidget.value.settings.sensitive = false;
    }

    markDirty();
};

const updateMobileImageSensitive = () => {
    if (!mobileImageEditorWidget.value?.content) {
        return;
    }

    updateWidgetSensitive(
        mobileImageEditorWidget.value,
        !Boolean(mobileImageEditorWidget.value.settings?.sensitive),
    );
};

const closeMobileMapEditor = () => {
    mobileMapEditorWidget.value = null;
    mobileMapSearchResults.value = [];
    mobileMapSearchError.value = '';
    isSearchingMobileMap.value = false;
    hasSearchedMobileMap.value = false;

    if (mobileMapSearchTimeout !== null) {
        window.clearTimeout(mobileMapSearchTimeout);
        mobileMapSearchTimeout = null;
    }

    mobileMapSearchAbortController?.abort();
    mobileMapSearchAbortController = null;
};

const setMobileMapEditorOpen = (open: boolean) => {
    if (!open) {
        closeMobileMapEditor();
    }
};

const openMobileMapEditor = (widget: any) => {
    ensureWidgetSettings(widget);
    widget.settings.title ??= '東京タワー';
    widget.settings.address ??= '東京都港区芝公園4丁目2-8';
    widget.settings.lat ??= 35.6585805;
    widget.settings.lng ??= 139.7454329;
    widget.settings.zoom ??= 15;
    mobileMapEditorWidget.value = widget;
    mobileMapSearchQuery.value = widget.settings.address;
    mobileMapSearchResults.value = [];
    mobileMapSearchError.value = '';
    hasSearchedMobileMap.value = false;
};

const { width: windowWidth } = useWindowSize();
const mobileGridDimensions = computed(() => {
    // Mobile grid settings (matching the GridLayout component)
    const colNum = 2;
    const rowHeight = 84.5;
    const margin = 12;

    // Calculate content width
    // Parent section has max-w-[374px]
    // Outer container has px-5 (20px each side)
    const padding = 40; // px-5 = 20px * 2
    const contentWidth = Math.min(windowWidth.value - padding, 374);
    const gridWidth = contentWidth + 24; // w-[calc(100%+24px)]

    const colWidth = (gridWidth - (colNum + 1) * margin) / colNum;

    return { colWidth, rowHeight, margin };
});

const mobileMapEditorPreviewStyle = computed(() => {
    const widget = mobileMapEditorWidget.value;

    if (!widget) {
        return {
            height: '250px',
            width: '250px',
        };
    }

    const w = Number(widget.w_mobile ?? 2);
    const h = Number(widget.h_mobile ?? 4);

    const { colWidth, rowHeight, margin } = mobileGridDimensions.value;

    // Calculate actual pixel dimensions on the grid
    const actualWidth = w * colWidth + (w - 1) * margin;
    const actualHeight = h * rowHeight + (h - 1) * margin;

    // Scale to fit a reasonable preview size (max dimension around 288px)
    const scale = Math.min(288 / actualWidth, 288 / actualHeight);

    return {
        width: `${actualWidth * scale}px`,
        height: `${actualHeight * scale}px`,
    };
});

const shouldShowMobileMapLocationDropdown = computed(() => {
    return (
        isSearchingMobileMap.value ||
        Boolean(mobileMapSearchError.value) ||
        mobileMapSearchResults.value.length > 0 ||
        (hasSearchedMobileMap.value &&
            mobileMapSearchQuery.value.trim().length >= 2 &&
            mobileMapSearchResults.value.length === 0)
    );
});

const extractMapLocationTitle = (displayName: string, name?: string) => {
    const title = (name || displayName.split(',')[0] || '').trim();

    return title || displayName;
};

const searchMobileMapLocations = async (query: string) => {
    const trimmedQuery = query.trim();

    mobileMapSearchAbortController?.abort();

    if (trimmedQuery.length < 2) {
        mobileMapSearchResults.value = [];
        mobileMapSearchError.value = '';
        isSearchingMobileMap.value = false;
        hasSearchedMobileMap.value = false;

        return;
    }

    mobileMapSearchAbortController = new AbortController();
    isSearchingMobileMap.value = true;
    mobileMapSearchError.value = '';
    hasSearchedMobileMap.value = false;

    try {
        const params = new URLSearchParams({
            q: trimmedQuery,
            format: 'jsonv2',
            limit: '5',
            addressdetails: '1',
            'accept-language': 'ja,en',
        });
        const response = await fetch(
            `https://nominatim.openstreetmap.org/search?${params.toString()}`,
            {
                signal: mobileMapSearchAbortController.signal,
                headers: {
                    Accept: 'application/json',
                },
            },
        );

        if (!response.ok) {
            throw new Error('Failed to search map locations.');
        }

        mobileMapSearchResults.value = await response.json();
    } catch (error) {
        if ((error as DOMException).name === 'AbortError') {
            return;
        }

        mobileMapSearchError.value = '候補を取得できませんでした';
        mobileMapSearchResults.value = [];
    } finally {
        isSearchingMobileMap.value = false;
        hasSearchedMobileMap.value = true;
    }
};

const updateMobileMapSearchQuery = (event: Event) => {
    mobileMapSearchQuery.value = (event.target as HTMLInputElement).value;
    hasSearchedMobileMap.value = false;

    if (mobileMapSearchTimeout !== null) {
        window.clearTimeout(mobileMapSearchTimeout);
    }

    mobileMapSearchTimeout = window.setTimeout(() => {
        searchMobileMapLocations(mobileMapSearchQuery.value);
    }, 300);
};

const selectMobileMapLocation = (result: {
    display_name: string;
    lat: string;
    lon: string;
    name?: string;
}) => {
    if (!mobileMapEditorWidget.value) {
        return;
    }

    updateMapWidgetLocation(mobileMapEditorWidget.value, {
        title: extractMapLocationTitle(result.display_name, result.name),
        address: result.display_name,
        lat: Number(result.lat),
        lng: Number(result.lon),
        zoom: 15,
    });

    mobileMapSearchQuery.value = result.display_name;
    mobileMapSearchResults.value = [];
};

const updateMobileMapTitle = (event: Event) => {
    if (!mobileMapEditorWidget.value) {
        return;
    }

    updateWidgetTitle(
        mobileMapEditorWidget.value,
        (event.target as HTMLInputElement).value,
    );
};

const updateMobileMapCenter = (center: {
    lat: number;
    lng: number;
    zoom: number;
}) => {
    if (!mobileMapEditorWidget.value) {
        return;
    }

    updateMapWidgetCenter(mobileMapEditorWidget.value, center);
};

const updateMobileMapZoom = (delta: number) => {
    if (!mobileMapEditorWidget.value) {
        return;
    }

    updateMapWidgetZoom(mobileMapEditorWidget.value, delta);
};

const updateMobileImageCrop = (crop: { x: number; y: number }) => {
    if (!mobileImageEditorWidget.value) {
        return;
    }

    updateImageWidgetCrop(mobileImageEditorWidget.value, crop);
};

const initMobileCropper = () => {
    if (!mobileImageRef.value || !mobileImageEditorWidget.value) return;

    isInitializingMobileCropper.value = true;

    mobileCropper.value = new Cropper(mobileImageRef.value, {
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
        modal: true,
        ready() {
            if (!mobileCropper.value) return;

            const containerData = mobileCropper.value.getContainerData();
            const imageData = mobileCropper.value.getImageData();
            const cropBoxData = {
                left: 0,
                top: 0,
                width: containerData.width,
                height: containerData.height,
            };

            mobileCropper.value.setCropBoxData(cropBoxData);

            const scale = Math.max(
                containerData.width / imageData.naturalWidth,
                containerData.height / imageData.naturalHeight,
            );

            mobileCropper.value.setCanvasData({
                width: imageData.naturalWidth * scale,
                height: imageData.naturalHeight * scale,
            });

            const canvasData = mobileCropper.value.getCanvasData();
            const xPercent = Number(
                mobileImageEditorWidget.value?.settings?.cropX ?? 50,
            );
            const yPercent = Number(
                mobileImageEditorWidget.value?.settings?.cropY ?? 50,
            );

            const xRange = containerData.width - canvasData.width;
            const yRange = containerData.height - canvasData.height;

            mobileCropper.value.setCanvasData({
                left: xRange * (xPercent / 100),
                top: yRange * (yPercent / 100),
            });

            nextTick(() => {
                mobileCropper.value?.setCropBoxData(cropBoxData);
                isInitializingMobileCropper.value = false;
            });
        },
        crop() {
            if (isInitializingMobileCropper.value) return;

            const canvasData = mobileCropper.value?.getCanvasData();
            const containerData = mobileCropper.value?.getContainerData();

            if (canvasData && containerData) {
                const xRange = containerData.width - canvasData.width;
                const yRange = containerData.height - canvasData.height;

                const x =
                    Math.abs(xRange) < 0.1
                        ? 50
                        : (canvasData.left / xRange) * 100;
                const y =
                    Math.abs(yRange) < 0.1
                        ? 50
                        : (canvasData.top / yRange) * 100;

                updateMobileImageCrop({
                    x: Math.min(100, Math.max(0, x)),
                    y: Math.min(100, Math.max(0, y)),
                });
            }
        },
    });
};

const destroyMobileCropper = () => {
    if (mobileCropper.value) {
        mobileCropper.value.destroy();
        mobileCropper.value = null;
    }
    isInitializingMobileCropper.value = false;
};

watch(isMobileImageCropping, (isCropping) => {
    if (isCropping) {
        nextTick(() => initMobileCropper());
    } else {
        destroyMobileCropper();
    }
});

const closeMobileTextEditor = () => {
    mobileTextEditorWidget.value = null;
    isMobileTextEditorFocused.value = false;
    mobileTextEditorMode.value = 'edit';
};

const setMobileTextEditorOpen = (open: boolean) => {
    if (!open) {
        closeMobileTextEditor();
    }
};

const openMobileTextEditor = (widget: any, mode: 'add' | 'edit' = 'edit') => {
    ensureWidgetSettings(widget);
    widget.settings.bgColor ??= '#FFFFFF';
    widget.settings.textAlign ??= 'left';
    widget.settings.verticalAlign ??= 'center';
    mobileTextEditorMode.value = mode;
    mobileTextEditorWidget.value = widget;
    nextTick(syncMobileTextEditor);
};

const completeMobileTextEditor = () => {
    if (mobileTextEditorMode.value === 'add' && mobileTextEditorWidget.value) {
        if (!commitMobileAddedWidget(mobileTextEditorWidget.value)) {
            return;
        }
    }

    closeMobileTextEditor();
};

const syncMobileTextEditor = () => {
    if (!mobileTextEditorInput.value || isMobileTextEditorFocused.value) {
        return;
    }

    const title = mobileTextEditorWidget.value?.settings?.title ?? '';

    if (mobileTextEditorInput.value.innerText !== title) {
        mobileTextEditorInput.value.innerText = title;
    }
};

watch(
    () => [
        mobileTextEditorWidget.value?.id,
        mobileTextEditorWidget.value?.settings?.title,
    ],
    () => nextTick(syncMobileTextEditor),
);

const updateMobileTextContent = (event: Event) => {
    if (!mobileTextEditorWidget.value) {
        return;
    }

    updateWidgetTitle(
        mobileTextEditorWidget.value,
        (event.target as HTMLElement).innerText,
    );
};

const updateMobileTextBackgroundColorInput = (event: Event) => {
    if (!mobileTextEditorWidget.value) {
        return;
    }

    const value = (event.target as HTMLInputElement).value.trim();
    const normalizedColor = /^#[0-9a-f]{3}$/i.test(value)
        ? `#${value
              .slice(1)
              .split('')
              .map((part) => part + part)
              .join('')}`
        : /^#[0-9a-f]{6}$/i.test(value)
          ? value
          : null;

    if (normalizedColor) {
        updateTextWidgetBackgroundColor(
            mobileTextEditorWidget.value,
            normalizedColor.toUpperCase(),
        );
    }
};

const updateMobileTextLink = (event: Event) => {
    if (!mobileTextEditorWidget.value) {
        return;
    }

    mobileTextEditorWidget.value.content = (
        event.target as HTMLInputElement
    ).value
        .trim()
        .slice(0, MAX_URL_LENGTH);

    if (
        !mobileTextEditorWidget.value.content &&
        mobileTextEditorWidget.value.settings
    ) {
        mobileTextEditorWidget.value.settings.sensitive = false;
    }

    markDirty();
};

const updateMobileTextSensitive = () => {
    if (!mobileTextEditorWidget.value?.content) {
        return;
    }

    updateWidgetSensitive(
        mobileTextEditorWidget.value,
        !Boolean(mobileTextEditorWidget.value.settings?.sensitive),
    );
};

const mobileTextEditorPreviewStyle = computed(() => {
    const widget = mobileTextEditorWidget.value;

    if (!widget) {
        return {
            height: '250px',
            width: '250px',
        };
    }

    const width = Number(widget.w_mobile ?? 1);
    const height = Number(widget.h_mobile ?? 2) / 2;
    const previewCellSize = 125;

    return {
        height: `${height * previewCellSize}px`,
        width: `${width * previewCellSize}px`,
    };
});

const normalizedMobileTextBackgroundColor = computed(() => {
    const color = String(
        mobileTextEditorWidget.value?.settings?.bgColor ?? '#FFFFFF',
    ).trim();

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

    return '#FFFFFF';
});

const mobileTextEditorTextClass = computed(() => {
    const color = normalizedMobileTextBackgroundColor.value;
    const red = parseInt(color.slice(1, 3), 16);
    const green = parseInt(color.slice(3, 5), 16);
    const blue = parseInt(color.slice(5, 7), 16);
    const luminance = (0.299 * red + 0.587 * green + 0.114 * blue) / 255;

    return luminance > 0.55 ? 'text-gray-800' : 'text-white';
});

const mobileTextEditorBoxClasses = computed(() => {
    const settings = mobileTextEditorWidget.value?.settings ?? {};

    return [
        mobileTextEditorTextClass.value,
        settings.textAlign === 'center'
            ? 'items-center text-center'
            : settings.textAlign === 'right'
              ? 'items-end text-right'
              : 'items-start text-left',
        settings.verticalAlign === 'start'
            ? 'justify-start'
            : settings.verticalAlign === 'end'
              ? 'justify-end'
              : 'justify-center',
    ];
});

const mobileTextEditorInputClasses = computed(() => {
    return [
        mobileTextEditorTextClass.value,
        mobileTextEditorWidget.value?.settings?.title ? '' : 'is-empty',
    ];
});

const mobileTextColorSwatches = [
    '#FFFFFF',
    '#FBCFE8',
    '#BFDBFE',
    '#BBF7D0',
    '#1F2937',
];

const closeMobileSectionEditor = () => {
    mobileSectionEditorWidget.value = null;
    mobileSectionEditorMode.value = 'edit';
    mobileSectionEditorError.value = '';
};

const setMobileSectionEditorOpen = (open: boolean) => {
    if (!open) {
        closeMobileSectionEditor();
    }
};

const openMobileSectionEditor = (
    widget: any,
    mode: 'add' | 'edit' = 'edit',
) => {
    ensureWidgetSettings(widget);
    mobileSectionEditorMode.value = mode;
    mobileSectionEditorError.value = '';
    mobileSectionEditorWidget.value = widget;
};

const completeMobileSectionEditor = () => {
    if (
        mobileSectionEditorMode.value === 'add' &&
        !String(
            mobileSectionEditorWidget.value?.content ??
                mobileSectionEditorWidget.value?.settings?.title ??
                '',
        ).trim()
    ) {
        mobileSectionEditorError.value = 'セクションを入力してください';

        return;
    }

    if (
        mobileSectionEditorMode.value === 'add' &&
        mobileSectionEditorWidget.value
    ) {
        if (!commitMobileAddedWidget(mobileSectionEditorWidget.value)) {
            return;
        }
    }

    closeMobileSectionEditor();
};

const updateMobileSectionTitle = (event: Event) => {
    if (!mobileSectionEditorWidget.value) {
        return;
    }

    mobileSectionEditorError.value = '';
    updateWidgetTitle(
        mobileSectionEditorWidget.value,
        (event.target as HTMLInputElement).value,
    );
};

const addMediaWidget = async (
    file: File,
    position: WidgetPosition | null = null,
) => {
    if (!canAddWidget()) {
        return;
    }

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

        const w = position ? position.w : (pendingWidgetSize.value?.w ?? 2);
        const h = position ? position.h : (pendingWidgetSize.value?.h ?? 4);
        const desktopPosition =
            position ??
            findNextGridPosition(localWidgets.value, 4, w, h, {
                x: 'x',
                y: 'y',
                w: 'w',
                h: 'h',
            });
        const mobilePosition =
            position ??
            findNextGridPosition(localWidgets.value, 2, w, h, {
                x: 'x_mobile',
                y: 'y_mobile',
                w: 'w_mobile',
                h: 'h_mobile',
            });
        const newWidget = {
            id: `temp_media_${Date.now()}`,
            type: 'image',
            content: null,
            thumbnail_url: response.data.url,
            x: desktopPosition.x,
            y: desktopPosition.y,
            w,
            h,
            x_mobile: mobilePosition.x,
            y_mobile: mobilePosition.y,
            w_mobile: w,
            h_mobile: h,
            settings: {
                title: '',
                cropX: 50,
                cropY: 50,
            },
        };

        addLocalWidget(newWidget);

        updateLayoutsFromWidgets();
        markDirty();
        scrollToWidgetBottom();
        pendingWidgetSize.value = null;
    } catch (error) {
        console.error('Failed to upload media:', error);
        alert('メディアのアップロードに失敗しました。');
    }
};

const addTextWidget = (position: WidgetPosition | null = null) => {
    if (!canAddWidget()) {
        return;
    }

    const w = position ? position.w : (pendingWidgetSize.value?.w ?? 1);
    const h = position ? position.h : (pendingWidgetSize.value?.h ?? 2);
    const desktopPosition =
        position ??
        findNextGridPosition(localWidgets.value, 4, w, h, {
            x: 'x',
            y: 'y',
            w: 'w',
            h: 'h',
        });
    const mobilePosition =
        position ??
        findNextGridPosition(localWidgets.value, 2, w, h, {
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
        w,
        h,
        x_mobile: mobilePosition.x,
        y_mobile: mobilePosition.y,
        w_mobile: w,
        h_mobile: h,
        settings: {
            title: '',
            bgColor: '#FFFFFF',
            textAlign: 'left',
            verticalAlign: 'center',
        },
    };

    if (isSmallViewport.value) {
        pendingWidgetSize.value = null;
        openMobileTextEditor(newWidget, 'add');

        return;
    }

    addLocalWidget(newWidget);
    updateLayoutsFromWidgets();
    markDirty();
    scrollToWidgetBottom();
    pendingWidgetSize.value = null;
};

const addMapWidget = (position: WidgetPosition | null = null) => {
    if (!canAddWidget()) {
        return;
    }

    const w = position ? position.w : (pendingWidgetSize.value?.w ?? 2);
    const h = position ? position.h : (pendingWidgetSize.value?.h ?? 4);
    const desktopPosition =
        position ??
        findNextGridPosition(localWidgets.value, 4, w, h, {
            x: 'x',
            y: 'y',
            w: 'w',
            h: 'h',
        });
    const mobilePosition =
        position ??
        findNextGridPosition(localWidgets.value, 2, w, h, {
            x: 'x_mobile',
            y: 'y_mobile',
            w: 'w_mobile',
            h: 'h_mobile',
        });
    const newWidget = {
        id: `temp_map_${Date.now()}`,
        type: 'map',
        content: null,
        thumbnail_url: null,
        x: desktopPosition.x,
        y: desktopPosition.y,
        w,
        h,
        x_mobile: mobilePosition.x,
        y_mobile: mobilePosition.y,
        w_mobile: w,
        h_mobile: h,
        settings: {
            title: '東京タワー',
            address: '東京都港区芝公園4丁目2-8',
            lat: 35.6585805,
            lng: 139.7454329,
            zoom: 15,
        },
    };

    addLocalWidget(newWidget);
    updateLayoutsFromWidgets();
    markDirty();
    scrollToWidgetBottom();
    pendingWidgetSize.value = null;
};

const addLinkWidget = async (url: string, isSensitive = false) => {
    showAddLinkModal.value = false;
    const limitedUrl = url.slice(0, MAX_URL_LENGTH);

    if (linkTargetWidget.value) {
        linkTargetWidget.value.content = limitedUrl || null;
        if (!linkTargetWidget.value.settings) {
            linkTargetWidget.value.settings = {};
        }
        linkTargetWidget.value.settings.sensitive = Boolean(
            limitedUrl && isSensitive,
        );
        linkTargetWidget.value = null;
        markDirty();

        return;
    }

    if (!canAddWidget()) {
        return;
    }

    const pos = pendingWidgetPosition.value;
    const w = pos ? pos.w : (pendingWidgetSize.value?.w ?? 1);
    const h = pos ? pos.h : (pendingWidgetSize.value?.h ?? 4);

    try {
        const { data } = await axios.post('/fetch-ogp', { url: limitedUrl });
        const xMatch = limitedUrl.match(
            /https?:\/\/(?:www\.)?(?:x|twitter)\.com\/([a-zA-Z0-9_]{1,15})(?:\/|\?|$)/i,
        );

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
            content: limitedUrl,
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
                title: xMatch
                    ? `@${xMatch[1]}`
                    : trimToLength(data.title ?? '', MAX_LINK_TITLE_LENGTH),
                aspectClass: 'aspect-video',
                sensitive: isSensitive,
            },
        };

        addLocalWidget(newWidget);

        updateLayoutsFromWidgets();
        markDirty();
        scrollToWidgetBottom();
    } catch (e) {
        console.error('Failed to fetch OGP', e);
        const xMatch = limitedUrl.match(
            /https?:\/\/(?:www\.)?(?:x|twitter)\.com\/([a-zA-Z0-9_]{1,15})(?:\/|\?|$)/i,
        );

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
            content: limitedUrl,
            x: desktopPosition.x,
            y: desktopPosition.y,
            w: w,
            h: h,
            x_mobile: mobilePosition.x,
            y_mobile: mobilePosition.y,
            w_mobile: w,
            h_mobile: h,

            settings: {
                title: xMatch
                    ? `@${xMatch[1]}`
                    : trimToLength(limitedUrl, MAX_LINK_TITLE_LENGTH),
                aspectClass: 'aspect-video',
                sensitive: isSensitive,
            },
        };
        addLocalWidget(newWidget);

        updateLayoutsFromWidgets();
        markDirty();
        scrollToWidgetBottom();
    } finally {
        pendingWidgetPosition.value = null;
        pendingWidgetSize.value = null;
    }
};

const deleteWidget = (widgetId: number | string) => {
    localWidgets.value = localWidgets.value.filter(
        (widget) => String(widget.id) !== String(widgetId),
    );
    updateLayoutsFromWidgets();
    markDirty();
    if (String(activeWidgetId.value) === String(widgetId)) {
        activeWidgetId.value = null;
    }
    if (String(croppingWidgetId.value) === String(widgetId)) {
        croppingWidgetId.value = null;
    }
    if (String(activeMapMovingWidgetId.value) === String(widgetId)) {
        activeMapMovingWidgetId.value = null;
    }
    if (String(mobileLinkEditorWidget.value?.id) === String(widgetId)) {
        mobileLinkEditorWidget.value = null;
    }
    if (String(mobileImageEditorWidget.value?.id) === String(widgetId)) {
        closeMobileImageEditor();
    }
    if (String(mobileTextEditorWidget.value?.id) === String(widgetId)) {
        mobileTextEditorWidget.value = null;
    }
    if (String(mobileSectionEditorWidget.value?.id) === String(widgetId)) {
        mobileSectionEditorWidget.value = null;
    }
};

const getWidgetMotionKey = (
    mode: 'desktop' | 'mobile',
    widgetId: string | number,
) => {
    return `link-widget-${mode}-${widgetId}`;
};

const motionValues = {
    x: useMotionValue(0),
    y: useMotionValue(0),
    rotate: useMotionValue(0),
    scale: useMotionValue(1),
    shadow: useMotionValue(0),
};

const springConfig = { stiffness: 400, damping: 30, mass: 0.5 };

const springValues = {
    x: useSpring(motionValues.x, springConfig),
    y: useSpring(motionValues.y, springConfig),
    rotate: useSpring(motionValues.rotate, springConfig),
    scale: useSpring(motionValues.scale, springConfig),
    shadow: useSpring(motionValues.shadow, springConfig),
};

const dragSpringValues = reactive({
    x: 0,
    y: 0,
    rotate: 0,
    scale: 1,
    shadow: 0,
});

springValues.x.on('change', (v) => {
    dragSpringValues.x = v;
    if (!prefersReducedMotion.value) {
        const velocity = springValues.x.getVelocity();
        // Use velocity to calculate tilt (-15 to 15 degrees)
        const rotateTarget = Math.max(Math.min(velocity * 0.006, 15), -15);
        motionValues.rotate.set(rotateTarget);
    }
});

springValues.y.on('change', (v) => (dragSpringValues.y = v));
springValues.rotate.on('change', (v) => (dragSpringValues.rotate = v));
springValues.scale.on('change', (v) => (dragSpringValues.scale = v));
springValues.shadow.on('change', (v) => (dragSpringValues.shadow = v));

const dragSpring = {
    values: dragSpringValues,
    set(targets: any) {
        if (prefersReducedMotion.value) {
            Object.assign(dragSpringValues, targets);
            for (const key in targets) {
                if (targets[key] !== undefined) {
                    motionValues[key as keyof typeof motionValues].set(
                        targets[key],
                    );
                    springValues[key as keyof typeof springValues].set(
                        targets[key],
                    );
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

const updateDraggedWidgetVisual = (
    mode: 'desktop' | 'mobile',
    widgetId: string | number,
    horizontalOffset: number,
    deltaX: number = 0,
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
        translateX: dragSpring.values.x,
        translateY: dragSpring.values.y,
        rotate: dragSpring.values.rotate,
        scale: dragSpring.values.scale,
        boxShadow: '',
    };
};

const getDraggedWidgetStyle = (
    mode: 'desktop' | 'mobile',
    widgetId: string | number,
) => {
    if (
        !dragVisualState.value ||
        dragVisualState.value.mode !== mode ||
        String(dragVisualState.value.widgetId) !== String(widgetId)
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
            String(draggingWidgetId.value) !== String(widgetId) ||
            draggingWidgetMode.value !== mode
        ) {
            dragSettleTimeout = null;

            return;
        }

        updateDraggedWidgetVisual(mode, widgetId, 0);
        dragSettleTimeout = null;
    }, 90);
};

const isWidgetInteractionDisabled = (widgetId: string | number) => {
    if (!isEditing.value || !isSuppressingWidgetInteractions.value) {
        return false;
    }

    return String(draggingWidgetId.value) !== String(widgetId);
};

const beginDraggedWidget = (
    mode: 'desktop' | 'mobile',
    widgetId: string | number,
) => {
    if (
        String(draggingWidgetId.value) === String(widgetId) &&
        draggingWidgetMode.value === mode
    ) {
        return;
    }

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
        dragVisualState.value &&
        dragVisualState.value.mode === mode &&
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
        croppingWidgetId.value ||
        activeMapMovingWidgetId.value
    ) {
        return;
    }

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

    if (!pointerState) {
        return;
    }

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

    const horizontalOffset = event.clientX - pointerState.startX;
    const deltaX = event.clientX - pointerState.lastX;

    updateDraggedWidgetVisual(
        pointerState.mode,
        pointerState.widgetId,
        horizontalOffset,
        deltaX,
    );
    scheduleDraggedWidgetSettle(pointerState.mode, pointerState.widgetId);

    pointerState.lastX = event.clientX;
    pointerState.lastY = event.clientY;
};

const handleWindowPointerUp = () => {
    if (!dragPointerState.value) {
        return;
    }

    if (dragPointerState.value.isDragging) {
        finishDraggedWidget(
            dragPointerState.value.widgetId,
            dragPointerState.value.mode,
        );
    }

    dragPointerState.value = null;
};

const shouldSuppressWidgetClick = () => {
    return Date.now() < suppressWidgetClickUntil.value;
};

const activateWidget = (widgetId: string | number) => {
    if (draggingWidgetId.value !== null || shouldSuppressWidgetClick()) {
        return;
    }

    activeWidgetId.value = widgetId;
};

const handleWidgetClick = (event: MouseEvent, item: any) => {
    if (draggingWidgetId.value !== null || shouldSuppressWidgetClick()) {
        event.preventDefault();
        event.stopPropagation();

        return;
    }

    if (isEditing.value) {
        activeWidgetId.value = activeWidgetId.value === item.i ? null : item.i;

        return;
    }

    if (
        item.widget.content &&
        item.widget.type !== 'section' &&
        isSensitiveWidget(item.widget)
    ) {
        openSensitiveWarning(item.widget);
    }
};

const handlePageClick = (event: MouseEvent) => {
    if (draggingWidgetId.value !== null || shouldSuppressWidgetClick()) {
        event.preventDefault();
        event.stopPropagation();

        return;
    }

    const target = event.target as HTMLElement | null;

    if (!target?.closest('.vgl-item')) {
        activeWidgetId.value = null;
        croppingWidgetId.value = null;
    }
};

const isSensitiveWidget = (widget: any) => {
    return Boolean(widget.settings?.sensitive);
};

const isYouTubeVideoUrl = (value: string | null | undefined) => {
    if (!value) {
        return false;
    }

    try {
        const url = new URL(value);
        const host = url.hostname.replace(/^www\./, '');
        const pathParts = url.pathname.split('/').filter(Boolean);
        const isYouTubeHost =
            host === 'youtube.com' ||
            host.endsWith('.youtube.com') ||
            host === 'youtu.be';

        if (!isYouTubeHost) {
            return false;
        }

        return Boolean(
            host === 'youtu.be'
                ? pathParts[0]
                : url.searchParams.get('v') ||
                      (['embed', 'shorts', 'live'].includes(
                          pathParts[0] ?? '',
                      ) &&
                          pathParts[1]),
        );
    } catch (e) {
        return false;
    }
};

const isYouTubeVideoWidget = (widget: any) => {
    return widget.type === 'link' && isYouTubeVideoUrl(widget.content);
};

const isYouTubeEmbedMode = (widget: any) => {
    return (
        isYouTubeVideoWidget(widget) && widget.settings?.youtubeMode === 'embed'
    );
};

const openSensitiveWarning = (widget: any) => {
    if (!widget.content) return;

    sensitiveTargetWidget.value = widget;
};

const closeSensitiveWarning = () => {
    sensitiveTargetWidget.value = null;
};

const continueToSensitiveLink = () => {
    const widget = sensitiveTargetWidget.value;

    if (!widget || !widget.content) return;

    const url = getWidgetHref(widget);

    if (!url) return;

    window.open(url, '_blank', 'noopener,noreferrer');
    sensitiveTargetWidget.value = null;
};
</script>

<style>
.link-page--editing .vgl-item.vgl-item--dragging {
    z-index: 9999 !important;
    background-color: transparent !important;
}

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

body.is-dragging * {
    user-select: none !important;
    -webkit-user-select: none !important;
}

body.is-dragging .vgl-item:not(.vgl-item--dragging) {
    pointer-events: none !important;
}

.link-page--editing .vgl-item:has(.link-widget-controls) {
    z-index: 150 !important;
}

.link-page--editing .vgl-item.is-cropping {
    z-index: 9003 !important;
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

.link-page--instant-layout,
.link-page--instant-layout *,
.link-page--instant-layout .vgl-item,
.link-page--instant-layout
    .vgl-item:not(.vgl-item--dragging):not(.vgl-item--resizing) {
    transition-duration: 0ms !important;
    transition-delay: 0ms !important;
    animation-duration: 0ms !important;
    animation-delay: 0ms !important;
}

.mobile-text-editor.is-empty::before {
    content: attr(data-placeholder);
    color: rgb(156 163 175);
    pointer-events: none;
}

.publish-confetti-piece {
    position: absolute;
    width: var(--confetti-size);
    height: calc(var(--confetti-size) * 0.58);
    border-radius: 2px;
    background: var(--confetti-color);
    animation: publishConfettiBurst var(--confetti-duration)
        cubic-bezier(0.16, 1, 0.3, 1) var(--confetti-delay) both;
    transform-origin: center;
}

@keyframes publishConfettiBurst {
    0% {
        opacity: 0;
        transform: translate3d(0, 0, 0) scale(0.4) rotate(0deg);
    }

    14% {
        opacity: 1;
    }

    72% {
        opacity: 1;
    }

    100% {
        opacity: 0;
        transform: translate3d(var(--confetti-x), var(--confetti-y), 0) scale(1)
            rotate(var(--confetti-rotate));
    }
}

@media (prefers-reduced-motion: reduce) {
    .publish-confetti-piece {
        animation-duration: 180ms;
    }
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
        class="link-page transition-colors duration-300"
        :class="[
            'min-h-screen',
            pageTheme,
            pageThemeClasses,
            isEditing ? 'link-page--editing' : '',
            isPreviewLayoutSwitching ? 'link-page--instant-layout' : '',
        ]"
    >
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
                        v-if="copiedProfileUrl"
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
                        <Check
                            v-if="copiedProfileUrl"
                            class="size-4 text-white"
                        />
                        <Copy v-else class="size-4" />
                        シェア
                    </button>
                </div>

                <DropdownMenu>
                    <DropdownMenuTrigger :as-child="true">
                        <button
                            type="button"
                            class="flex size-9 cursor-pointer items-center justify-center rounded-full text-gray-700 transition-colors hover:bg-gray-100"
                            aria-label="メニュー"
                            title="メニュー"
                        >
                            <MoreHorizontal class="size-5" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        align="end"
                        side="top"
                        class="z-[9001] w-56"
                    >
                        <DropdownMenuItem
                            class="text-red-600 focus:bg-red-50 focus:text-red-600"
                            @select="reportUser"
                        >
                            <Flag class="size-4" />
                            このユーザーを通報する
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>

        <!-- Floating Toolbar for Owner -->
        <LinkToolbar
            v-if="isOwner"
            :is-editing="isEditing"
            v-model:preview-mode="previewMode"
            :is-published="isLinkPublished"
            :is-share-copied="copiedProfileUrl"
            :has-widgets="localWidgets.length > 0"
            :page-theme="pageTheme"
            :widget-style="widgetStyle"
            :mobile-widget-operation-active="
                Boolean(
                    activeMobileLayoutItem &&
                    activeMobileLayoutItem.widget.type !== 'section',
                )
            "
            :mobile-size-options="activeMobileSizeOptions"
            :active-mobile-widget="activeMobileLayoutItem?.widget"
            @toggle-edit="toggleEdit"
            @add-link="openAddLinkFromToolbar"
            @add-media="addMediaWidget"
            @add-text="addTextWidget"
            @add-map="addMapWidget"
            @add-section="addSectionWidget"
            @publish="publishLink"
            @share="copyProfileUrl"
            @update:page-theme="pageTheme = $event"
            @update:widget-style="widgetStyle = $event"
            @resize-mobile-widget="resizeActiveMobileWidget"
            @complete-mobile-widget-operation="completeMobileWidgetOperation"
        />
        <div
            v-if="confettiPieces.length"
            class="pointer-events-none fixed inset-x-0 bottom-8 z-[1100] flex justify-center"
            aria-hidden="true"
        >
            <span
                v-for="piece in confettiPieces"
                :key="piece.id"
                class="publish-confetti-piece"
                :style="{
                    '--confetti-x': `${piece.x}px`,
                    '--confetti-y': `${piece.y}px`,
                    '--confetti-rotate': `${piece.rotate}deg`,
                    '--confetti-delay': `${piece.delay}ms`,
                    '--confetti-duration': `${piece.duration}ms`,
                    '--confetti-color': piece.color,
                    '--confetti-size': `${piece.size}px`,
                }"
            ></span>
        </div>
        <input
            ref="emptyMediaInput"
            type="file"
            accept="image/*,.apng"
            class="hidden"
            @change="handleEmptyMediaChange"
        />

        <div
            v-if="croppingWidgetId"
            class="pointer-events-none fixed inset-0 z-[70] bg-gray-200/75"
        ></div>
        <div
            v-if="activeMapMovingWidgetId"
            class="fixed inset-0 z-[2000] bg-white/70 backdrop-blur-[2px]"
            @click="closeMapMove"
        ></div>

        <div
            class="transition-all duration-300"
            :class="[
                activePreviewMode === 'mobile'
                    ? 'min-h-screen px-5 pt-8'
                    : 'min-h-screen px-5 pt-12 min-[1025px]:px-0 sm:px-8',
            ]"
        >
            <div
                id="profile"
                ref="pageScrollContainer"
                class="relative mx-auto w-full max-w-[1198px] transition-all duration-300"
                :class="[
                    activePreviewMode === 'mobile'
                        ? 'flex w-full max-w-[374px] flex-col gap-4 pb-32'
                        : 'flex w-full flex-col gap-y-8 pb-32 min-[1025px]:max-w-[1198px] min-[1025px]:flex-row min-[1025px]:justify-between min-[1025px]:gap-x-4 min-[1025px]:gap-y-0',
                ]"
            >
                <LinkProfile
                    v-model:display-name="editForm.display_name"
                    v-model:bio="editForm.bio"
                    :is-editing="isEditing"
                    :preview-mode="activePreviewMode"
                    :avatar-url="profileAvatarUrl"
                    :display-initial="displayInitial"
                    :letter-url="`/@${props.link.slug}/message`"
                    :page-theme="pageTheme"
                    @update:avatar="updateAvatar"
                    @remove:avatar="removeAvatar"
                />

                <section
                    id="grid"
                    class="relative mx-auto w-full max-w-[374px] pt-0 transition-all duration-300"
                    :class="
                        activePreviewMode === 'desktop'
                            ? 'pb-24 min-[1025px]:mx-0 min-[1025px]:w-[736px] min-[1025px]:max-w-none min-[1025px]:shrink-0'
                            : 'mt-2 pb-24'
                    "
                >
                    <div
                        v-if="croppingWidgetId !== null"
                        class="fixed inset-0 z-[9002] hidden bg-black/40 backdrop-blur-sm transition-opacity duration-300 min-[1025px]:block"
                    ></div>

                    <!-- Desktop Grid -->
                    <GridLayout
                        v-if="shouldShowDesktopGrid"
                        :key="`desktop-${isSmallViewport}-${previewMode}`"
                        v-model:layout="desktopLayout"
                        :col-num="4"
                        :row-height="81.5"
                        :margin="[12, 12]"
                        :is-draggable="
                            isEditing &&
                            !croppingWidgetId &&
                            !activeMapMovingWidgetId
                        "
                        :is-resizable="false"
                        :vertical-compact="true"
                        :use-css-transforms="true"
                        @layout-updated="syncWidgetsFromDesktop"
                        class="-m-3 w-[calc(100%+24px)]"
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
                                        ? openMediaPickerFromEmptyState({
                                              x: placeholder.x,
                                              y: placeholder.y,
                                              w: placeholder.w,
                                              h: placeholder.h,
                                          })
                                        : placeholder.type === 'link'
                                          ? openModalWithPos(
                                                placeholder.x,
                                                placeholder.y,
                                                placeholder.w,
                                                placeholder.h,
                                            )
                                          : addTextWidget({
                                                x: placeholder.x,
                                                y: placeholder.y,
                                                w: placeholder.w,
                                                h: placeholder.h,
                                            })
                                "
                            >
                                <div
                                    class="absolute top-4 right-4 text-gray-300"
                                >
                                    <Plus class="size-4" />
                                </div>
                                <component
                                    :is="placeholder.icon"
                                    class="mb-2 size-6"
                                />
                                <span class="text-xs font-semibold">
                                    {{ placeholder.label }}
                                </span>
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
                            :drag-ignore-from="'.widget-text-input--focused, a, input, textarea'"
                            @mouseenter="
                                draggingWidgetId === null
                                    ? (hoveredWidgetId = item.i)
                                    : null
                            "
                            @mouseleave="hoveredWidgetId = null"
                            class="group"
                            :class="[
                                croppingWidgetId === item.i
                                    ? 'is-cropping z-[9003]'
                                    : activeMapMovingWidgetId === item.i
                                      ? '!z-[3000]'
                                      : draggingWidgetId === item.i
                                        ? 'z-[1100]'
                                        : hoveredWidgetId === item.i ||
                                            lockedControlsWidgetId === item.i
                                          ? 'z-[1000]'
                                          : 'z-10',
                            ]"
                        >
                            <div
                                class="h-full w-full will-change-transform"
                                :class="widgetCornerClass"
                                :style="
                                    getDraggedWidgetStyle('desktop', item.i)
                                "
                            >
                                <component
                                    :is="
                                        item.widget.content &&
                                        item.widget.type !== 'section' &&
                                        item.widget.type !== 'map' &&
                                        !isSensitiveWidget(item.widget) &&
                                        !isYouTubeEmbedMode(item.widget) &&
                                        !isEditing
                                            ? 'a'
                                            : 'div'
                                    "
                                    :href="getWidgetHref(item.widget)"
                                    :target="
                                        item.widget.content &&
                                        item.widget.type !== 'section' &&
                                        item.widget.type !== 'map' &&
                                        !isSensitiveWidget(item.widget) &&
                                        !isYouTubeEmbedMode(item.widget) &&
                                        !isEditing
                                            ? '_blank'
                                            : undefined
                                    "
                                    rel="noopener noreferrer"
                                    @click="handleWidgetClick($event, item)"
                                    @pointerdown="
                                        startWidgetDragPointer(
                                            item.i,
                                            'desktop',
                                            $event,
                                        )
                                    "
                                    class="relative block h-full w-full"
                                    :class="[
                                        widgetCornerClass,
                                        newlyAddedWidgetIds.has(item.i)
                                            ? 'widget-bounce-enter'
                                            : '',
                                        draggingWidgetId === item.i
                                            ? ''
                                            : 'transition-[transform,box-shadow] duration-200 ease-out',
                                        isWidgetInteractionDisabled(item.i)
                                            ? 'pointer-events-none'
                                            : '',
                                        item.widget.content &&
                                        item.widget.type !== 'section' &&
                                        item.widget.type !== 'map' &&
                                        !isYouTubeEmbedMode(item.widget) &&
                                        !isEditing
                                            ? 'cursor-pointer'
                                            : '',
                                        isEditing && !croppingWidgetId
                                            ? draggingWidgetId === item.i
                                                ? 'cursor-grabbing'
                                                : 'cursor-grab active:cursor-grabbing'
                                            : '',
                                        item.widget.type === 'section' &&
                                        !isEditing
                                            ? ''
                                            : isEditing
                                              ? 'active:scale-[0.99]'
                                              : '',
                                        activeMapMovingWidgetId === item.i
                                            ? 'ring-2 ring-black'
                                            : '',
                                    ]"
                                >
                                    <LinkWidgetControls
                                        v-if="
                                            isEditing &&
                                            draggingWidgetId !== item.i &&
                                            (hoveredWidgetId === item.i ||
                                                croppingWidgetId === item.i ||
                                                activeMapMovingWidgetId ===
                                                    item.i ||
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
                                        :is-map-moving="
                                            activeMapMovingWidgetId === item.i
                                        "
                                        @delete="deleteWidget(item.i)"
                                        @lock-open="
                                            setControlsLock(item.i, $event)
                                        "
                                        @edit-link="
                                            updateWidgetLink(item.widget)
                                        "
                                        @toggle-crop="toggleImageCrop(item.i)"
                                        @toggle-map-move="toggleMapMove(item.i)"
                                        @close-map-move="closeMapMove"
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
                                            updateWidgetYoutubeMode(
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
                                        :widget-corner-class="widgetCornerClass"
                                        :page-theme="pageTheme"
                                        :is-editing="isEditing"
                                        :is-active="activeWidgetId === item.i"
                                        :is-cropping="
                                            croppingWidgetId === item.i
                                        "
                                        :is-map-moving="
                                            activeMapMovingWidgetId === item.i
                                        "
                                        :profile-image-url="profileAvatarUrl"
                                        @activate="activateWidget(item.i)"
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
                        v-if="shouldShowMobileGrid"
                        :key="`mobile-${isSmallViewport}-${previewMode}`"
                        v-model:layout="mobileLayout"
                        :col-num="2"
                        :row-height="84.5"
                        :margin="[12, 12]"
                        :is-draggable="
                            isEditing &&
                            !croppingWidgetId &&
                            !activeMapMovingWidgetId
                        "
                        :is-resizable="false"
                        :vertical-compact="true"
                        :use-css-transforms="true"
                        @layout-updated="syncWidgetsFromMobile"
                        class="-m-3 w-[calc(100%+24px)]"
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
                                @click="
                                    openModalWithPos(
                                        placeholder.x,
                                        placeholder.y,
                                        placeholder.w,
                                        placeholder.h,
                                    )
                                "
                            >
                                <div
                                    class="absolute top-4 right-4 text-gray-300"
                                >
                                    <Plus class="size-4" />
                                </div>
                                <component
                                    :is="placeholder.icon"
                                    class="mb-2 size-6"
                                />
                                <span class="text-xs font-semibold">
                                    {{ placeholder.label }}
                                </span>
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
                                !isSmallViewport
                                    ? undefined
                                    : '.mobile-widget-move-handle'
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
                                    ? 'is-cropping z-[9003]'
                                    : activeMapMovingWidgetId === item.i
                                      ? '!z-[3000]'
                                      : draggingWidgetId === item.i
                                        ? 'z-[1100] cursor-grabbing'
                                        : isSmallViewport &&
                                            activeWidgetId === item.i
                                          ? 'z-[4000] cursor-grab'
                                          : hoveredWidgetId === item.i ||
                                              lockedControlsWidgetId === item.i
                                            ? 'z-[1000] cursor-grab'
                                            : 'z-10',
                                isEditing && activeWidgetId === item.i
                                    ? 'cursor-default'
                                    : '',
                                isEditing && !isSmallViewport
                                    ? draggingWidgetId === item.i
                                        ? '!cursor-grabbing'
                                        : '!cursor-grab active:!cursor-grabbing'
                                    : '',
                            ]"
                        >
                            <div
                                class="h-full w-full will-change-transform"
                                :class="widgetCornerClass"
                                :style="getDraggedWidgetStyle('mobile', item.i)"
                            >
                                <component
                                    :is="
                                        item.widget.content &&
                                        item.widget.type !== 'section' &&
                                        item.widget.type !== 'map' &&
                                        !isSensitiveWidget(item.widget) &&
                                        !isYouTubeEmbedMode(item.widget) &&
                                        !isEditing
                                            ? 'a'
                                            : 'div'
                                    "
                                    :href="getWidgetHref(item.widget)"
                                    :target="
                                        item.widget.content &&
                                        item.widget.type !== 'section' &&
                                        item.widget.type !== 'map' &&
                                        !isSensitiveWidget(item.widget) &&
                                        !isYouTubeEmbedMode(item.widget) &&
                                        !isEditing
                                            ? '_blank'
                                            : undefined
                                    "
                                    rel="noopener noreferrer"
                                    class="relative block h-full w-full overflow-visible"
                                    :class="[
                                        widgetCornerClass,
                                        newlyAddedWidgetIds.has(item.i)
                                            ? 'widget-bounce-enter'
                                            : '',
                                        draggingWidgetId === item.i
                                            ? ''
                                            : 'transition-[transform,box-shadow] duration-200 ease-out',
                                        isWidgetInteractionDisabled(item.i)
                                            ? 'pointer-events-none'
                                            : '',
                                        activeWidgetId === item.i &&
                                        isEditing &&
                                        isSmallViewport
                                            ? 'border-2 border-black'
                                            : '',
                                        item.widget.content &&
                                        item.widget.type !== 'section' &&
                                        item.widget.type !== 'map' &&
                                        !isEditing
                                            ? 'cursor-pointer'
                                            : '',
                                        draggingWidgetId == item.i
                                            ? 'z-50'
                                            : '',
                                        activeMapMovingWidgetId === item.i
                                            ? 'ring-2 ring-black'
                                            : '',
                                    ]"
                                    @click="handleWidgetClick($event, item)"
                                    @pointerdown="
                                        !isSmallViewport
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
                                            isSmallViewport &&
                                            (activeWidgetId === item.i ||
                                                croppingWidgetId === item.i)
                                        "
                                        class="pointer-events-none absolute inset-0 z-[700]"
                                    >
                                        <button
                                            type="button"
                                            aria-label="ウィジェットを削除"
                                            class="mobile-widget-ignore-drag pointer-events-auto absolute -top-2.5 -left-2.5 flex size-8 items-center justify-center rounded-xl bg-red-600 text-white shadow-lg transition-transform active:scale-95"
                                            @click.stop="deleteWidget(item.i)"
                                            @pointerdown.stop
                                            @touchstart.stop
                                        >
                                            <Trash2 class="size-4" />
                                        </button>

                                        <button
                                            type="button"
                                            aria-label="ウィジェットを編集"
                                            class="mobile-widget-ignore-drag pointer-events-auto absolute -top-2.5 -right-2.5 flex size-8 items-center justify-center rounded-xl bg-black text-white shadow-lg transition-transform active:scale-95"
                                            @click.stop="
                                                editMobileWidget(item.widget)
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
                                    <LinkWidgetControls
                                        v-if="
                                            isEditing &&
                                            !isSmallViewport &&
                                            (hoveredWidgetId === item.i ||
                                                croppingWidgetId === item.i ||
                                                activeMapMovingWidgetId ===
                                                    item.i ||
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
                                        :is-map-moving="
                                            activeMapMovingWidgetId === item.i
                                        "
                                        @delete="deleteWidget(item.i)"
                                        @lock-open="
                                            setControlsLock(item.i, $event)
                                        "
                                        @edit-link="
                                            updateWidgetLink(item.widget)
                                        "
                                        @toggle-crop="toggleImageCrop(item.i)"
                                        @toggle-map-move="toggleMapMove(item.i)"
                                        @close-map-move="closeMapMove"
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
                                            updateWidgetYoutubeMode(
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
                                        :widget-corner-class="widgetCornerClass"
                                        :page-theme="pageTheme"
                                        :is-editing="
                                            isEditing && !isSmallViewport
                                        "
                                        :is-active="activeWidgetId === item.i"
                                        :is-cropping="
                                            croppingWidgetId === item.i
                                        "
                                        :is-map-moving="
                                            activeMapMovingWidgetId === item.i
                                        "
                                        :profile-image-url="profileAvatarUrl"
                                        @activate="activateWidget(item.i)"
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

        <footer
            v-if="!isOwner"
            class="px-5 pt-2 pb-24 text-center text-xs font-semibold text-gray-400"
        >
            <Link href="/" class="transition-colors hover:text-gray-700">
                Built with GridLink
            </Link>
        </footer>

        <Sheet
            :open="showMobileAddLinkSheet"
            @update:open="setMobileAddLinkSheetOpen"
        >
            <SheetContent
                side="bottom"
                :show-close="false"
                overlay-class="z-[9999]"
                :class="[
                    'z-[9999] max-h-[92vh] gap-0 overflow-hidden rounded-t-3xl border-gray-200 bg-white p-0 dark:border-zinc-800 dark:bg-zinc-950',
                    pageTheme,
                ]"
                @click.stop
                @pointerdown.stop
                @touchstart.stop
            >
                <SheetHeader
                    class="sticky top-0 z-10 border-b border-gray-100 bg-white p-0 dark:border-zinc-800 dark:bg-zinc-950"
                >
                    <div class="flex h-16 items-center justify-between px-5">
                        <button
                            type="button"
                            class="flex size-8 items-center justify-center rounded-full text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-950 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white"
                            aria-label="キャンセル"
                            title="キャンセル"
                            @click="closeMobileAddLinkSheet"
                        >
                            <X class="size-5" />
                        </button>
                        <SheetTitle
                            class="text-base font-bold text-gray-950 dark:text-white"
                        >
                            リンクを追加
                        </SheetTitle>
                        <button
                            type="button"
                            class="rounded-full bg-black px-3 py-1.5 text-xs font-bold text-white shadow-sm transition-colors hover:bg-black dark:bg-white dark:text-black"
                            @click="submitMobileAddLink"
                        >
                            追加
                        </button>
                    </div>
                </SheetHeader>

                <form
                    id="mobile-add-link-form"
                    class="flex max-h-[calc(92vh-64px)] flex-col gap-5 overflow-y-auto px-5 pt-2 pb-6"
                    @submit.prevent="submitMobileAddLink"
                >
                    <label class="grid gap-2">
                        <span
                            class="text-sm font-bold text-gray-800 dark:text-zinc-200"
                            >URL</span
                        >
                        <input
                            v-model="mobileAddLinkUrl"
                            type="url"
                            :maxlength="MAX_URL_LENGTH"
                            class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-base font-semibold text-gray-900 transition-colors outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/15 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-blue-500"
                            placeholder="https://..."
                            @input="mobileAddLinkError = ''"
                        />
                        <span
                            v-if="mobileAddLinkError"
                            class="text-xs font-semibold text-red-600"
                        >
                            {{ mobileAddLinkError }}
                        </span>
                    </label>

                    <label
                        class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-900/70"
                    >
                        <span
                            class="text-sm font-bold text-gray-800 dark:text-zinc-200"
                        >
                            センシティブ
                        </span>
                        <button
                            type="button"
                            role="switch"
                            :aria-checked="mobileAddLinkSensitive"
                            aria-label="センシティブ"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 focus:outline-none"
                            :class="
                                mobileAddLinkSensitive
                                    ? 'bg-blue-600'
                                    : 'bg-gray-300 dark:bg-zinc-700'
                            "
                            @click.prevent.stop="
                                mobileAddLinkSensitive = !mobileAddLinkSensitive
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
            </SheetContent>
        </Sheet>

        <Sheet
            :open="Boolean(mobileLinkEditorWidget)"
            @update:open="setMobileLinkEditorOpen"
        >
            <SheetContent
                side="bottom"
                :show-close="false"
                overlay-class="z-[9999]"
                :class="[
                    'z-[9999] max-h-[92vh] gap-0 overflow-hidden rounded-t-3xl border-gray-200 bg-white p-0 dark:border-zinc-800 dark:bg-zinc-950',
                    pageTheme,
                ]"
                @click.stop
                @pointerdown.stop
                @touchstart.stop
            >
                <SheetHeader
                    class="sticky top-0 z-10 border-b border-gray-100 bg-white p-0 dark:border-zinc-800 dark:bg-zinc-950"
                >
                    <div class="flex h-16 items-center justify-between px-5">
                        <SheetTitle
                            class="text-base font-bold text-gray-950 dark:text-white"
                        >
                            リンクを編集
                        </SheetTitle>
                        <button
                            type="button"
                            class="rounded-full bg-black px-3 py-1.5 text-xs font-bold text-white shadow-sm transition-colors hover:bg-black dark:bg-white dark:text-black"
                            @click="closeMobileLinkEditor"
                        >
                            完了
                        </button>
                    </div>
                </SheetHeader>

                <div
                    v-if="mobileLinkEditorWidget"
                    class="flex max-h-[calc(92vh-64px)] flex-col gap-5 overflow-y-auto px-5 pt-2 pb-6"
                >
                    <label class="grid gap-2">
                        <span
                            class="text-sm font-bold text-gray-800 dark:text-zinc-200"
                        >
                            タイトル
                        </span>
                        <input
                            :value="
                                mobileLinkEditorWidget.settings?.title ?? ''
                            "
                            type="text"
                            :maxlength="MAX_LINK_TITLE_LENGTH"
                            class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-base font-semibold text-gray-900 transition-colors outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/15 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-blue-500"
                            placeholder="タイトルを入力"
                            @input="updateMobileLinkTitle"
                        />
                    </label>

                    <div class="grid gap-2">
                        <span
                            class="text-sm font-bold text-gray-800 dark:text-zinc-200"
                            >画像</span
                        >
                        <div class="relative">
                            <button
                                type="button"
                                class="relative flex h-48 w-full items-center justify-center overflow-hidden rounded-3xl border border-gray-200 bg-gray-50 text-gray-400 transition-transform active:scale-[0.99] dark:border-zinc-800 dark:bg-zinc-900"
                                @click="chooseMobileLinkImage"
                            >
                                <img
                                    v-if="mobileLinkEditorWidget.thumbnail_url"
                                    :src="mobileLinkEditorWidget.thumbnail_url"
                                    alt=""
                                    class="h-full w-full object-cover"
                                    draggable="false"
                                />
                                <div
                                    v-else
                                    class="flex items-center text-gray-400 dark:text-zinc-500"
                                >
                                    <ImageIcon class="size-8" />
                                </div>
                            </button>
                            <button
                                v-if="mobileLinkEditorWidget.thumbnail_url"
                                type="button"
                                aria-label="画像を削除"
                                class="absolute top-3 right-3 flex size-10 items-center justify-center rounded-xl bg-red-600 text-white shadow-sm transition-colors hover:bg-red-700"
                                @click.stop="removeMobileLinkImage"
                            >
                                <Trash2 class="size-4" />
                            </button>
                        </div>
                        <input
                            ref="mobileLinkImageInput"
                            type="file"
                            accept="image/*,.apng"
                            class="hidden"
                            @change="updateMobileLinkImage"
                        />
                    </div>

                    <label
                        class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-900/70"
                    >
                        <span
                            class="text-sm font-bold text-gray-800 dark:text-zinc-200"
                        >
                            センシティブ
                        </span>
                        <button
                            type="button"
                            role="switch"
                            :aria-checked="
                                Boolean(
                                    mobileLinkEditorWidget.settings?.sensitive,
                                )
                            "
                            aria-label="センシティブ"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 focus:outline-none"
                            :class="
                                mobileLinkEditorWidget.settings?.sensitive
                                    ? 'bg-blue-600'
                                    : 'bg-gray-300 dark:bg-zinc-700'
                            "
                            @click.prevent.stop="updateMobileLinkSensitive"
                        >
                            <span
                                class="pointer-events-none inline-block size-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                :class="
                                    mobileLinkEditorWidget.settings?.sensitive
                                        ? 'translate-x-5'
                                        : 'translate-x-0'
                                "
                            ></span>
                        </button>
                    </label>
                </div>
            </SheetContent>
        </Sheet>

        <Sheet
            :open="Boolean(mobileImageEditorWidget)"
            @update:open="setMobileImageEditorOpen"
        >
            <SheetContent
                side="bottom"
                :show-close="false"
                overlay-class="z-[9999]"
                :class="[
                    'z-[9999] max-h-[92vh] gap-0 overflow-hidden rounded-t-3xl border-gray-200 bg-white p-0 dark:border-zinc-800 dark:bg-zinc-950',
                    pageTheme,
                ]"
                @click.stop
                @pointerdown.stop
                @touchstart.stop
            >
                <SheetHeader
                    class="sticky top-0 z-10 border-b border-gray-100 bg-white p-0 dark:border-zinc-800 dark:bg-zinc-950"
                >
                    <div class="flex h-16 items-center justify-between px-5">
                        <SheetTitle
                            class="text-base font-bold text-gray-950 dark:text-white"
                        >
                            メディアを編集
                        </SheetTitle>
                        <button
                            type="button"
                            class="rounded-full bg-black px-3 py-1.5 text-xs font-bold text-white shadow-sm transition-colors hover:bg-black dark:bg-white dark:text-black"
                            @click="closeMobileImageEditor"
                        >
                            完了
                        </button>
                    </div>
                </SheetHeader>

                <div
                    v-if="mobileImageEditorWidget"
                    class="flex max-h-[calc(92vh-64px)] flex-col gap-5 overflow-y-auto px-5 pt-2 pb-6"
                >
                    <div class="grid gap-2">
                        <span
                            class="text-sm font-bold text-gray-800 dark:text-zinc-200"
                        >
                            メディア
                        </span>
                        <div
                            class="relative flex min-h-[360px] items-center justify-center rounded-3xl bg-gray-100 p-8 dark:bg-zinc-900"
                        >
                            <div
                                ref="mobileImageCropPreview"
                                class="relative touch-none rounded-2xl"
                                :class="
                                    isMobileImageCropping
                                        ? 'is-cropping-active overflow-visible'
                                        : 'overflow-hidden'
                                "
                                :style="mobileImageEditorPreviewStyle"
                            >
                                <img
                                    ref="mobileImageRef"
                                    :src="mobileImageEditorWidget.thumbnail_url"
                                    alt=""
                                    class="h-full w-full object-cover"
                                    :style="
                                        isMobileImageCropping
                                            ? {}
                                            : mobileImageEditorCropStyle
                                    "
                                    draggable="false"
                                />
                            </div>

                            <button
                                type="button"
                                aria-label="クロップを調整"
                                class="absolute right-8 bottom-8 flex size-10 items-center justify-center rounded-xl shadow-md transition-colors"
                                :class="
                                    isMobileImageCropping
                                        ? 'bg-white text-black'
                                        : 'bg-black text-white dark:bg-zinc-800'
                                "
                                @click.stop="
                                    isMobileImageCropping =
                                        !isMobileImageCropping
                                "
                            >
                                <Crop class="size-5" />
                            </button>

                            <button
                                type="button"
                                aria-label="画像を変更"
                                :disabled="isMobileImageCropping"
                                class="absolute bottom-8 left-8 flex h-10 items-center justify-center rounded-xl bg-white px-4 text-sm font-bold text-black shadow-md transition-transform dark:bg-zinc-800 dark:text-white"
                                :class="[
                                    isMobileImageCropping
                                        ? 'cursor-not-allowed opacity-50'
                                        : 'active:scale-95',
                                ]"
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
                        <span
                            class="text-sm font-bold text-gray-800 dark:text-zinc-200"
                        >
                            キャプション
                        </span>
                        <input
                            :value="
                                mobileImageEditorWidget.settings?.title ?? ''
                            "
                            type="text"
                            :maxlength="MAX_TEXT_WIDGET_LENGTH"
                            class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-base font-semibold text-gray-900 transition-colors outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/15 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-blue-500"
                            placeholder="キャプションを入力"
                            @input="updateMobileImageCaption"
                        />
                    </label>

                    <label class="grid gap-2">
                        <span
                            class="text-sm font-bold text-gray-800 dark:text-zinc-200"
                        >
                            リンク
                        </span>
                        <input
                            :value="mobileImageEditorWidget.content ?? ''"
                            type="url"
                            :maxlength="MAX_URL_LENGTH"
                            class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-base font-semibold text-gray-900 transition-colors outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/15 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-blue-500"
                            placeholder="https://..."
                            @input="updateMobileImageLink"
                        />
                    </label>

                    <label
                        class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-900/70"
                        :class="
                            mobileImageEditorWidget.content ? '' : 'opacity-50'
                        "
                    >
                        <span
                            class="text-sm font-bold text-gray-800 dark:text-zinc-200"
                        >
                            センシティブ
                        </span>
                        <button
                            type="button"
                            role="switch"
                            :disabled="!mobileImageEditorWidget.content"
                            :aria-checked="
                                Boolean(
                                    mobileImageEditorWidget.content &&
                                    mobileImageEditorWidget.settings?.sensitive,
                                )
                            "
                            aria-label="センシティブ"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed"
                            :class="
                                mobileImageEditorWidget.content &&
                                mobileImageEditorWidget.settings?.sensitive
                                    ? 'bg-blue-600'
                                    : 'bg-gray-300 dark:bg-zinc-700'
                            "
                            @click.prevent.stop="updateMobileImageSensitive"
                        >
                            <span
                                class="pointer-events-none inline-block size-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                :class="
                                    mobileImageEditorWidget.content &&
                                    mobileImageEditorWidget.settings?.sensitive
                                        ? 'translate-x-5'
                                        : 'translate-x-0'
                                "
                            ></span>
                        </button>
                    </label>
                </div>
            </SheetContent>
        </Sheet>

        <Sheet
            :open="Boolean(mobileMapEditorWidget)"
            @update:open="setMobileMapEditorOpen"
        >
            <SheetContent
                side="bottom"
                :show-close="false"
                overlay-class="z-[9999]"
                :class="[
                    'z-[9999] max-h-[92vh] gap-0 overflow-hidden rounded-t-3xl border-gray-200 bg-white p-0 dark:border-zinc-800 dark:bg-zinc-950',
                    pageTheme,
                ]"
                @click.stop
                @pointerdown.stop
                @touchstart.stop
            >
                <SheetHeader
                    class="sticky top-0 z-10 border-b border-gray-100 bg-white p-0 dark:border-zinc-800 dark:bg-zinc-950"
                >
                    <div class="flex h-16 items-center justify-between px-5">
                        <SheetTitle
                            class="text-base font-bold text-gray-950 dark:text-white"
                        >
                            マップを編集
                        </SheetTitle>
                        <button
                            type="button"
                            class="rounded-full bg-black px-3 py-1.5 text-xs font-bold text-white shadow-sm transition-colors hover:bg-black dark:bg-white dark:text-black"
                            @click="closeMobileMapEditor"
                        >
                            完了
                        </button>
                    </div>
                </SheetHeader>

                <div
                    v-if="mobileMapEditorWidget"
                    class="flex max-h-[calc(92vh-64px)] flex-col gap-5 overflow-y-auto px-5 pt-2 pb-6"
                >
                    <div class="grid gap-2">
                        <span
                            class="text-sm font-bold text-gray-800 dark:text-zinc-200"
                        >
                            ロケーション
                        </span>
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400 dark:text-zinc-500"
                            />
                            <input
                                :value="mobileMapSearchQuery"
                                type="text"
                                class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50 pr-3 pl-9 text-base font-semibold text-gray-900 transition-colors outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/15 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-blue-500"
                                placeholder="ロケーションを入力"
                                @input="updateMobileMapSearchQuery"
                            />

                            <div
                                v-if="shouldShowMobileMapLocationDropdown"
                                class="absolute top-[calc(100%+0.5rem)] right-0 left-0 z-[500] max-h-56 overflow-y-auto rounded-2xl border border-gray-200 bg-white p-2 shadow-2xl dark:border-zinc-800 dark:bg-zinc-950"
                            >
                                <div
                                    v-if="isSearchingMobileMap"
                                    class="px-3 py-2 text-sm font-semibold text-gray-500 dark:text-zinc-400"
                                >
                                    検索中...
                                </div>
                                <div
                                    v-else-if="mobileMapSearchError"
                                    class="px-3 py-2 text-sm font-semibold text-red-600"
                                >
                                    {{ mobileMapSearchError }}
                                </div>
                                <div
                                    v-else-if="
                                        mobileMapSearchQuery.trim().length >=
                                            2 &&
                                        mobileMapSearchResults.length === 0
                                    "
                                    class="px-3 py-2 text-sm font-semibold text-gray-500 dark:text-zinc-400"
                                >
                                    候補が見つかりません
                                </div>
                                <button
                                    v-for="result in mobileMapSearchResults"
                                    :key="result.place_id"
                                    type="button"
                                    class="w-full truncate rounded-xl px-3 py-2 text-left text-sm font-semibold text-gray-800 transition-colors hover:bg-gray-100 dark:text-zinc-200 dark:hover:bg-zinc-900"
                                    @click.prevent.stop="
                                        selectMobileMapLocation(result)
                                    "
                                >
                                    {{ result.display_name }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <span
                            class="text-sm font-bold text-gray-800 dark:text-zinc-200"
                        >
                            マップ
                        </span>
                        <div
                            class="relative flex h-[340px] items-center justify-center overflow-hidden rounded-3xl border border-gray-200 bg-gray-100 dark:border-zinc-800 dark:bg-zinc-900"
                        >
                            <div class="absolute inset-0 z-0">
                                <LinkWidgetContent
                                    :widget="mobileMapEditorWidget"
                                    mode="mobile"
                                    :page-theme="pageTheme"
                                    :is-editing="false"
                                    :is-active="true"
                                    :is-map-moving="true"
                                    @update-map-center="updateMobileMapCenter"
                                />
                            </div>

                            <div
                                class="pointer-events-none relative z-10 rounded-2xl border-2 border-black bg-transparent shadow-[0_0_0_1000px_rgba(243,244,246,0.6)] dark:border-white dark:shadow-[0_0_0_1000px_rgba(9,9,11,0.6)]"
                                :style="mobileMapEditorPreviewStyle"
                            >
                                <div
                                    class="pointer-events-auto absolute bottom-3 left-3 z-[410] flex items-center gap-1.5 rounded-2xl bg-black/80 p-1.5 text-white shadow-xl ring-1 ring-white/10 backdrop-blur-md"
                                    @click.stop
                                    @pointerdown.stop
                                    @touchstart.stop
                                >
                                    <button
                                        type="button"
                                        aria-label="縮小"
                                        class="flex size-8 cursor-pointer items-center justify-center rounded-lg text-white/70 transition-colors hover:bg-white/10 hover:text-white"
                                        @click.prevent.stop="
                                            updateMobileMapZoom(-1)
                                        "
                                    >
                                        <Minus class="size-4" />
                                    </button>
                                    <button
                                        type="button"
                                        aria-label="拡大"
                                        class="flex size-8 cursor-pointer items-center justify-center rounded-lg text-white/70 transition-colors hover:bg-white/10 hover:text-white"
                                        @click.prevent.stop="
                                            updateMobileMapZoom(1)
                                        "
                                    >
                                        <Plus class="size-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <label class="grid gap-2">
                        <span
                            class="text-sm font-bold text-gray-800 dark:text-zinc-200"
                        >
                            タイトル
                        </span>
                        <input
                            :value="mobileMapEditorWidget.settings?.title ?? ''"
                            type="text"
                            :maxlength="MAX_TEXT_WIDGET_LENGTH"
                            class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-base font-semibold text-gray-900 transition-colors outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/15 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-blue-500"
                            placeholder="タイトルを入力"
                            @input="updateMobileMapTitle"
                        />
                    </label>
                </div>
            </SheetContent>
        </Sheet>

        <Sheet
            :open="Boolean(mobileTextEditorWidget)"
            @update:open="setMobileTextEditorOpen"
        >
            <SheetContent
                side="bottom"
                :show-close="false"
                overlay-class="z-[9999]"
                :class="[
                    'z-[9999] max-h-[92vh] gap-0 overflow-hidden rounded-t-3xl border-gray-200 bg-white p-0 dark:border-zinc-800 dark:bg-zinc-950',
                    pageTheme,
                ]"
                @click.stop
                @pointerdown.stop
                @touchstart.stop
            >
                <SheetHeader
                    class="sticky top-0 z-10 border-b border-gray-100 bg-white p-0 dark:border-zinc-800 dark:bg-zinc-950"
                >
                    <div class="flex h-16 items-center justify-between px-5">
                        <button
                            v-if="mobileTextEditorMode === 'add'"
                            type="button"
                            class="flex size-8 items-center justify-center rounded-full text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-950 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white"
                            aria-label="キャンセル"
                            title="キャンセル"
                            @click="closeMobileTextEditor"
                        >
                            <X class="size-5" />
                        </button>
                        <div v-else class="size-8"></div>

                        <SheetTitle
                            class="text-base font-bold text-gray-950 dark:text-white"
                        >
                            {{
                                mobileTextEditorMode === 'add'
                                    ? 'テキストを追加'
                                    : 'テキストを編集'
                            }}
                        </SheetTitle>

                        <button
                            type="button"
                            class="rounded-full bg-black px-3 py-1.5 text-xs font-bold text-white shadow-sm transition-colors hover:bg-black dark:bg-white dark:text-black"
                            @click="completeMobileTextEditor"
                        >
                            {{
                                mobileTextEditorMode === 'add' ? '追加' : '完了'
                            }}
                        </button>
                    </div>
                </SheetHeader>

                <div
                    v-if="mobileTextEditorWidget"
                    class="flex max-h-[calc(92vh-64px)] flex-col gap-5 overflow-y-auto px-5 pt-2 pb-6"
                >
                    <div class="grid gap-2">
                        <span
                            class="text-sm font-bold text-gray-800 dark:text-zinc-200"
                        >
                            テキスト
                        </span>
                        <div
                            class="relative flex min-h-[300px] items-center justify-center rounded-3xl bg-gray-100 p-8 dark:bg-zinc-900"
                        >
                            <div
                                class="flex flex-col overflow-auto rounded-2xl border border-gray-200 p-4 text-sm leading-snug font-semibold whitespace-pre-wrap transition-colors focus-within:ring-4 focus-within:ring-blue-500/15 dark:border-zinc-800"
                                :class="mobileTextEditorBoxClasses"
                                :style="[
                                    mobileTextEditorPreviewStyle,
                                    {
                                        backgroundColor:
                                            normalizedMobileTextBackgroundColor,
                                    },
                                ]"
                                @click="mobileTextEditorInput?.focus()"
                            >
                                <div
                                    ref="mobileTextEditorInput"
                                    contenteditable="true"
                                    role="textbox"
                                    aria-multiline="true"
                                    data-placeholder="テキストを入力"
                                    class="mobile-text-editor min-h-[1.25em] w-full bg-transparent break-words whitespace-pre-wrap outline-none"
                                    :class="mobileTextEditorInputClasses"
                                    @beforeinput="
                                        limitPlainTextBeforeInput(
                                            $event,
                                            MAX_TEXT_WIDGET_LENGTH,
                                        )
                                    "
                                    @input="updateMobileTextContent"
                                    @paste="
                                        pastePlainTextWithLimit(
                                            $event,
                                            MAX_TEXT_WIDGET_LENGTH,
                                        );
                                        updateMobileTextContent($event);
                                    "
                                    @focus="isMobileTextEditorFocused = true"
                                    @blur="
                                        isMobileTextEditorFocused = false;
                                        syncMobileTextEditor();
                                    "
                                    @click.stop
                                ></div>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <span
                            class="text-sm font-bold text-gray-800 dark:text-zinc-200"
                        >
                            スタイル
                        </span>
                        <div class="flex gap-2 overflow-x-auto pb-1">
                            <button
                                type="button"
                                class="flex size-10 shrink-0 items-center justify-center rounded-xl border transition-colors"
                                :class="
                                    (mobileTextEditorWidget.settings
                                        ?.textAlign ?? 'left') === 'left'
                                        ? 'border-black bg-black text-white dark:border-white dark:bg-white dark:text-black'
                                        : 'border-gray-200 bg-gray-50 text-gray-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400'
                                "
                                aria-label="左寄せ"
                                @click="
                                    updateTextWidgetAlign(
                                        mobileTextEditorWidget,
                                        'left',
                                    )
                                "
                            >
                                <AlignLeft class="size-4" />
                            </button>
                            <button
                                type="button"
                                class="flex size-10 shrink-0 items-center justify-center rounded-xl border transition-colors"
                                :class="
                                    (mobileTextEditorWidget.settings
                                        ?.textAlign ?? 'left') === 'center'
                                        ? 'border-black bg-black text-white dark:border-white dark:bg-white dark:text-black'
                                        : 'border-gray-200 bg-gray-50 text-gray-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400'
                                "
                                aria-label="中央寄せ"
                                @click="
                                    updateTextWidgetAlign(
                                        mobileTextEditorWidget,
                                        'center',
                                    )
                                "
                            >
                                <AlignCenter class="size-4" />
                            </button>
                            <button
                                type="button"
                                class="flex size-10 shrink-0 items-center justify-center rounded-xl border transition-colors"
                                :class="
                                    (mobileTextEditorWidget.settings
                                        ?.textAlign ?? 'left') === 'right'
                                        ? 'border-black bg-black text-white dark:border-white dark:bg-white dark:text-black'
                                        : 'border-gray-200 bg-gray-50 text-gray-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400'
                                "
                                aria-label="右寄せ"
                                @click="
                                    updateTextWidgetAlign(
                                        mobileTextEditorWidget,
                                        'right',
                                    )
                                "
                            >
                                <AlignRight class="size-4" />
                            </button>
                            <button
                                type="button"
                                class="flex size-10 shrink-0 items-center justify-center rounded-xl border transition-colors"
                                :class="
                                    (mobileTextEditorWidget.settings
                                        ?.verticalAlign ?? 'center') === 'start'
                                        ? 'border-black bg-black text-white dark:border-white dark:bg-white dark:text-black'
                                        : 'border-gray-200 bg-gray-50 text-gray-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400'
                                "
                                aria-label="上寄せ"
                                @click="
                                    updateTextWidgetVerticalAlign(
                                        mobileTextEditorWidget,
                                        'start',
                                    )
                                "
                            >
                                <AlignVerticalJustifyStart class="size-4" />
                            </button>
                            <button
                                type="button"
                                class="flex size-10 shrink-0 items-center justify-center rounded-xl border transition-colors"
                                :class="
                                    (mobileTextEditorWidget.settings
                                        ?.verticalAlign ?? 'center') ===
                                    'center'
                                        ? 'border-black bg-black text-white dark:border-white dark:bg-white dark:text-black'
                                        : 'border-gray-200 bg-gray-50 text-gray-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400'
                                "
                                aria-label="上下中央"
                                @click="
                                    updateTextWidgetVerticalAlign(
                                        mobileTextEditorWidget,
                                        'center',
                                    )
                                "
                            >
                                <AlignVerticalJustifyCenter class="size-4" />
                            </button>
                            <button
                                type="button"
                                class="flex size-10 shrink-0 items-center justify-center rounded-xl border transition-colors"
                                :class="
                                    (mobileTextEditorWidget.settings
                                        ?.verticalAlign ?? 'center') === 'end'
                                        ? 'border-black bg-black text-white dark:border-white dark:bg-white dark:text-black'
                                        : 'border-gray-200 bg-gray-50 text-gray-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400'
                                "
                                aria-label="下寄せ"
                                @click="
                                    updateTextWidgetVerticalAlign(
                                        mobileTextEditorWidget,
                                        'end',
                                    )
                                "
                            >
                                <AlignVerticalJustifyEnd class="size-4" />
                            </button>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <span
                            class="text-sm font-bold text-gray-800 dark:text-zinc-200"
                        >
                            背景色
                        </span>
                        <div class="flex gap-2 overflow-x-auto pb-1">
                            <button
                                v-for="color in mobileTextColorSwatches"
                                :key="color"
                                type="button"
                                class="size-10 shrink-0 rounded-xl border-2 transition-transform active:scale-95"
                                :class="
                                    normalizedMobileTextBackgroundColor.toLowerCase() ===
                                    color.toLowerCase()
                                        ? 'border-black ring-2 ring-black/15 dark:border-white dark:ring-white/15'
                                        : 'border-gray-200 dark:border-zinc-800'
                                "
                                :style="{ backgroundColor: color }"
                                :aria-label="`背景色 ${color}`"
                                @click="
                                    updateTextWidgetBackgroundColor(
                                        mobileTextEditorWidget,
                                        color,
                                    )
                                "
                            ></button>
                            <input
                                :value="normalizedMobileTextBackgroundColor"
                                type="text"
                                inputmode="text"
                                maxlength="7"
                                class="h-10 w-[90px] shrink-0 rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm font-bold text-gray-800 transition-colors outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/15 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-blue-500"
                                placeholder="#FFFFFF"
                                aria-label="カラーコード"
                                @input="updateMobileTextBackgroundColorInput"
                            />
                        </div>
                    </div>

                    <label class="grid gap-2">
                        <span
                            class="text-sm font-bold text-gray-800 dark:text-zinc-200"
                            >URL</span
                        >
                        <input
                            :value="mobileTextEditorWidget.content ?? ''"
                            type="url"
                            :maxlength="MAX_URL_LENGTH"
                            class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-base font-semibold text-gray-900 transition-colors outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/15 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-blue-500"
                            placeholder="https://..."
                            @input="updateMobileTextLink"
                        />
                    </label>

                    <label
                        class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-900/70"
                        :class="
                            mobileTextEditorWidget.content ? '' : 'opacity-50'
                        "
                    >
                        <span
                            class="text-sm font-bold text-gray-800 dark:text-zinc-200"
                        >
                            センシティブ
                        </span>
                        <button
                            type="button"
                            role="switch"
                            :disabled="!mobileTextEditorWidget.content"
                            :aria-checked="
                                Boolean(
                                    mobileTextEditorWidget.content &&
                                    mobileTextEditorWidget.settings?.sensitive,
                                )
                            "
                            aria-label="センシティブ"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed"
                            :class="
                                mobileTextEditorWidget.content &&
                                mobileTextEditorWidget.settings?.sensitive
                                    ? 'bg-blue-600'
                                    : 'bg-gray-300 dark:bg-zinc-700'
                            "
                            @click.prevent.stop="updateMobileTextSensitive"
                        >
                            <span
                                class="pointer-events-none inline-block size-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                :class="
                                    mobileTextEditorWidget.content &&
                                    mobileTextEditorWidget.settings?.sensitive
                                        ? 'translate-x-5'
                                        : 'translate-x-0'
                                "
                            ></span>
                        </button>
                    </label>
                </div>
            </SheetContent>
        </Sheet>

        <Sheet
            :open="Boolean(mobileSectionEditorWidget)"
            @update:open="setMobileSectionEditorOpen"
        >
            <SheetContent
                side="bottom"
                :show-close="false"
                overlay-class="z-[9999]"
                :class="[
                    'z-[9999] max-h-[92vh] gap-0 overflow-hidden rounded-t-3xl border-gray-200 bg-white p-0 dark:border-zinc-800 dark:bg-zinc-950',
                    pageTheme,
                ]"
                @click.stop
                @pointerdown.stop
                @touchstart.stop
            >
                <SheetHeader
                    class="sticky top-0 z-10 border-b border-gray-100 bg-white p-0 dark:border-zinc-800 dark:bg-zinc-950"
                >
                    <div class="flex h-16 items-center justify-between px-5">
                        <button
                            v-if="mobileSectionEditorMode === 'add'"
                            type="button"
                            class="flex size-8 items-center justify-center rounded-full text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-950 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white"
                            aria-label="キャンセル"
                            title="キャンセル"
                            @click="closeMobileSectionEditor"
                        >
                            <X class="size-5" />
                        </button>
                        <div v-else class="size-8"></div>

                        <SheetTitle
                            class="text-base font-bold text-gray-950 dark:text-white"
                        >
                            {{
                                mobileSectionEditorMode === 'add'
                                    ? 'セクションを追加'
                                    : 'セクションを編集'
                            }}
                        </SheetTitle>

                        <button
                            type="button"
                            class="rounded-full bg-black px-3 py-1.5 text-xs font-bold text-white shadow-sm transition-colors hover:bg-black dark:bg-white dark:text-black"
                            @click="completeMobileSectionEditor"
                        >
                            {{
                                mobileSectionEditorMode === 'add'
                                    ? '追加'
                                    : '完了'
                            }}
                        </button>
                    </div>
                </SheetHeader>

                <div
                    v-if="mobileSectionEditorWidget"
                    class="flex max-h-[calc(92vh-64px)] flex-col gap-5 overflow-y-auto px-5 pt-2 pb-6"
                >
                    <label class="grid gap-2">
                        <span
                            class="text-sm font-bold text-gray-800 dark:text-zinc-200"
                        >
                            セクション
                        </span>
                        <input
                            :value="
                                mobileSectionEditorWidget.content ??
                                mobileSectionEditorWidget.settings?.title ??
                                ''
                            "
                            type="text"
                            class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-base font-semibold text-gray-900 transition-colors outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/15 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-blue-500"
                            placeholder="セクションを入力"
                            @input="updateMobileSectionTitle"
                        />
                        <span
                            v-if="mobileSectionEditorError"
                            class="text-xs font-semibold text-red-600"
                        >
                            {{ mobileSectionEditorError }}
                        </span>
                    </label>
                </div>
            </SheetContent>
        </Sheet>

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
            <div
                class="w-full max-w-md rounded-3xl bg-white p-6 shadow-xl dark:border dark:border-zinc-800 dark:bg-zinc-950"
            >
                <h3 class="text-xl font-bold text-gray-950 dark:text-white">
                    センシティブなコンテンツ
                </h3>
                <p
                    class="mt-3 text-sm leading-6 text-gray-600 dark:text-zinc-400"
                >
                    このリンクはセンシティブなコンテンツを含む可能性があります。内容を確認したうえでリンクを開いてください。
                </p>
                <div class="mt-6 flex gap-3">
                    <button
                        type="button"
                        class="flex-1 rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-900"
                        @click="closeSensitiveWarning"
                    >
                        キャンセル
                    </button>
                    <button
                        type="button"
                        class="flex-1 rounded-xl bg-[#292929] px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-black dark:bg-white dark:text-black dark:hover:bg-zinc-200"
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
