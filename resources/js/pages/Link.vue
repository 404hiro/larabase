<script setup lang="ts">
import LinkAddModal from '@/components/links/LinkAddModal.vue';
import LinkPageNavigation from '@/components/links/LinkPageNavigation.vue';
import LinkProfile from '@/components/links/LinkProfile.vue';
import LinkToolbar from '@/components/links/LinkToolbar.vue';
import LinkWidgetContent from '@/components/links/LinkWidgetContent.vue';
import LinkWidgetControls from '@/components/links/LinkWidgetControls.vue';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Toaster } from '@/components/ui/toast';
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { GridItem, GridLayout } from 'grid-layout-plus';
import {
    AlignCenter,
    AlignLeft,
    AlignRight,
    AlignVerticalJustifyCenter,
    AlignVerticalJustifyEnd,
    AlignVerticalJustifyStart,
    Crop,
    Image as ImageIcon,
    LayoutGrid,
    Link as LinkIcon,
    Move,
    Music,
    Pencil,
    Plus,
    RectangleHorizontal,
    RectangleVertical,
    Square,
    Trash2,
    X,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

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
const isSmallViewport = ref(false);

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

onMounted(() => {
    updateViewportMode();
    window.addEventListener('resize', updateViewportMode);
    window.addEventListener('beforeunload', handleBeforeUnload);
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
    if (avatarPreviewUrl.value) {
        URL.revokeObjectURL(avatarPreviewUrl.value);
    }

    window.removeEventListener('resize', updateViewportMode);
    window.removeEventListener('beforeunload', handleBeforeUnload);
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
    () => [editForm.value.display_name, editForm.value.bio],
    () => markDirty(),
);

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
                            },
                            onFinish: () => {
                                isSavingChanges.value = false;
                            },
                        },
                    );
                },
                onError: () => {
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

    if (isSmallViewport.value) {
        openMobileSectionEditor(newWidget, 'add');

        return;
    }

    localWidgets.value.push(newWidget);
    updateLayoutsFromWidgets();
    markDirty();
    scrollToWidgetBottom();
};

const showAddLinkModal = ref(false);
const showMobileAddLinkSheet = ref(false);
const mobileAddLinkUrl = ref('');
const mobileAddLinkError = ref('');
const mobileAddLinkSensitive = ref(false);
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

const openAddLinkFromToolbar = () => {
    linkTargetWidget.value = null;
    pendingWidgetPosition.value = null;

    if (isSmallViewport.value) {
        mobileAddLinkUrl.value = '';
        mobileAddLinkError.value = '';
        mobileAddLinkSensitive.value = false;
        showMobileAddLinkSheet.value = true;

        return;
    }

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

    if (!normalizedUrl || !isValidLinkUrl(normalizedUrl)) {
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
const isDraggingMobileImageCrop = ref(false);
const lastMobileImageCropPointer = ref<{ x: number; y: number } | null>(null);
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
    if (
        !localWidgets.value.some(
            (localWidget) => String(localWidget.id) === String(widget.id),
        )
    ) {
        localWidgets.value.push(widget);
    }

    updateLayoutsFromWidgets();
    markDirty();
    activeWidgetId.value = widget.id;
    nextTick(scrollToWidgetBottom);
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
    ).value;
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
        await uploadWidgetImage(widget, file);
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
    isDraggingMobileImageCrop.value = false;
    lastMobileImageCropPointer.value = null;
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
        await uploadWidgetImage(widget, file);
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
    ).value.trim();

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

const updateMobileImageCrop = (crop: { x: number; y: number }) => {
    if (!mobileImageEditorWidget.value) {
        return;
    }

    updateImageWidgetCrop(mobileImageEditorWidget.value, crop);
};

const startMobileImageCrop = (event: PointerEvent) => {
    if (!isMobileImageCropping.value || !mobileImageEditorWidget.value) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();
    isDraggingMobileImageCrop.value = true;
    lastMobileImageCropPointer.value = { x: event.clientX, y: event.clientY };
    mobileImageCropPreview.value?.setPointerCapture(event.pointerId);
};

const dragMobileImageCrop = (event: PointerEvent) => {
    if (
        !isDraggingMobileImageCrop.value ||
        !lastMobileImageCropPointer.value ||
        !mobileImageEditorWidget.value
    ) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();

    const deltaX = event.clientX - lastMobileImageCropPointer.value.x;
    const deltaY = event.clientY - lastMobileImageCropPointer.value.y;
    const currentCrop = {
        x: Number(mobileImageEditorWidget.value.settings?.cropX ?? 50),
        y: Number(mobileImageEditorWidget.value.settings?.cropY ?? 50),
    };

    updateMobileImageCrop({
        x: Math.min(100, Math.max(0, currentCrop.x - deltaX * 0.35)),
        y: Math.min(100, Math.max(0, currentCrop.y - deltaY * 0.35)),
    });

    lastMobileImageCropPointer.value = { x: event.clientX, y: event.clientY };
};

const stopMobileImageCrop = (event?: PointerEvent) => {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
        mobileImageCropPreview.value?.releasePointerCapture(event.pointerId);
    }

    isDraggingMobileImageCrop.value = false;
    lastMobileImageCropPointer.value = null;
};

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
        commitMobileAddedWidget(mobileTextEditorWidget.value);
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
    ).value.trim();

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
        commitMobileAddedWidget(mobileSectionEditorWidget.value);
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
        markDirty();
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

    if (isSmallViewport.value) {
        openMobileTextEditor(newWidget, 'add');

        return;
    }

    localWidgets.value.push(newWidget);
    updateLayoutsFromWidgets();
    markDirty();
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
        markDirty();
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
        markDirty();
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
    markDirty();
    if (String(activeWidgetId.value) === String(widgetId)) {
        activeWidgetId.value = null;
    }
    if (String(croppingWidgetId.value) === String(widgetId)) {
        croppingWidgetId.value = null;
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
.link-page--editing .vgl-item.vgl-item--dragging {
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
.mobile-text-editor.is-empty::before {
    content: attr(data-placeholder);
    color: rgb(156 163 175);
    pointer-events: none;
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
            @add-section="addSectionWidget"
            @resize-mobile-widget="resizeActiveMobileWidget"
            @complete-mobile-widget-operation="completeMobileWidgetOperation"
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
                            :drag-allow-from="'.mobile-widget-move-handle'"
                            :drag-ignore-from="'.mobile-widget-ignore-drag, a, input, textarea, [contenteditable=true]'"
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
                                isEditing && activeWidgetId === item.i
                                    ? 'cursor-default'
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
                                        activeWidgetId === item.i && isEditing
                                            ? 'rounded-2xl border-2 border-black'
                                            : '',
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
                                    <div
                                        v-if="
                                            isEditing &&
                                            activeWidgetId === item.i
                                        "
                                        class="pointer-events-none absolute inset-0 z-[150]"
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
                                            class="mobile-widget-move-handle pointer-events-auto absolute right-1/2 -bottom-5 flex size-10 translate-x-1/2 touch-none cursor-grab items-center justify-center rounded-full bg-black text-white shadow-lg active:cursor-grabbing"
                                        >
                                            <Move class="size-5" />
                                        </button>
                                    </div>
                                    <LinkWidgetContent
                                        :widget="item.widget"
                                        mode="mobile"
                                        :is-editing="isEditing && !isSmallViewport"
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

        <Sheet
            :open="showMobileAddLinkSheet"
            @update:open="setMobileAddLinkSheetOpen"
        >
            <SheetContent
                side="bottom"
                :show-close="false"
                overlay-class="z-[9999]"
                class="z-[9999] max-h-[92vh] overflow-hidden rounded-t-3xl border-gray-200 bg-white p-0 gap-0"
                @click.stop
                @pointerdown.stop
                @touchstart.stop
            >
                <SheetHeader class="border-b border-gray-100 px-5 py-4 text-left">
                    <SheetTitle class="sr-only">
                        リンクを追加
                    </SheetTitle>
                    <button
                        type="button"
                        class="absolute top-4 left-4 z-30 flex size-8 items-center justify-center rounded-full text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-950"
                        aria-label="キャンセル"
                        title="キャンセル"
                        @click="closeMobileAddLinkSheet"
                    >
                        <X class="size-5" />
                    </button>
                    <button
                        type="button"
                        class="absolute top-4 right-4 z-30 rounded-full bg-black px-3 py-1.5 text-xs font-bold text-white shadow-sm transition-colors hover:bg-black"
                        @click="submitMobileAddLink"
                    >
                        追加
                    </button>
                </SheetHeader>

                <form
                    id="mobile-add-link-form"
                    class="flex max-h-[calc(92vh-64px)] flex-col gap-5 overflow-y-auto px-5 pt-2 pb-6"
                    @submit.prevent="submitMobileAddLink"
                >
                    <label class="grid gap-2">
                        <span class="text-sm font-bold text-gray-800">URL</span>
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
                        >
                            {{ mobileAddLinkError }}
                        </span>
                    </label>

                    <label
                        class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-3"
                    >
                        <span class="text-sm font-bold text-gray-800">
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
            </SheetContent>
        </Sheet>

        <Sheet
            :open="Boolean(mobileLinkEditorWidget)"
            @update:open="setMobileLinkEditorOpen"
        >
            <SheetContent
                side="bottom"
                close-label="完了"
                close-class="rounded-full bg-black px-3 py-1.5 text-xs font-bold text-white opacity-100 shadow-sm hover:bg-black hover:opacity-100 focus:ring-black/20"
                overlay-class="z-[9999]"
                class="z-[9999] max-h-[92vh] overflow-hidden rounded-t-3xl border-gray-200 bg-white p-0 gap-0"
                @click.stop
                @pointerdown.stop
                @touchstart.stop
            >
                <SheetHeader class="border-b border-gray-100 px-5 py-4 text-left">
                    <SheetTitle class="text-base font-bold text-gray-950">
                        リンクを編集
                    </SheetTitle>
                </SheetHeader>

                <div
                    v-if="mobileLinkEditorWidget"
                    class="flex max-h-[calc(92vh-64px)] flex-col gap-5 overflow-y-auto px-5 pt-2 pb-6"
                >
                    <label class="grid gap-2">
                        <span class="text-sm font-bold text-gray-800">
                            タイトル
                        </span>
                        <input
                            :value="mobileLinkEditorWidget.settings?.title ?? ''"
                            type="text"
                            class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-base font-semibold text-gray-900 transition-colors outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/15"
                            placeholder="タイトルを入力"
                            @input="updateMobileLinkTitle"
                        />
                    </label>

                    <div class="grid gap-2">
                        <span class="text-sm font-bold text-gray-800">画像</span>
                        <div class="relative">
                            <button
                                type="button"
                                class="relative flex h-48 w-full items-center justify-center overflow-hidden rounded-3xl border border-gray-200 bg-gray-50 text-gray-400 transition-transform active:scale-[0.99]"
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
                                    class="flex items-center text-gray-400"
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
                            accept="image/*"
                            class="hidden"
                            @change="updateMobileLinkImage"
                        />
                    </div>

                    <label
                        class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-3"
                    >
                        <span class="text-sm font-bold text-gray-800">
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
                                    : 'bg-gray-300'
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
                close-label="完了"
                close-class="rounded-full bg-black px-3 py-1.5 text-xs font-bold text-white opacity-100 shadow-sm hover:bg-black hover:opacity-100 focus:ring-black/20"
                overlay-class="z-[9999]"
                class="z-[9999] max-h-[92vh] overflow-hidden rounded-t-3xl border-gray-200 bg-white p-0 gap-0"
                @click.stop
                @pointerdown.stop
                @touchstart.stop
            >
                <SheetHeader class="border-b border-gray-100 px-5 py-4 text-left">
                    <SheetTitle class="text-base font-bold text-gray-950">
                        メディアを編集
                    </SheetTitle>
                </SheetHeader>

                <div
                    v-if="mobileImageEditorWidget"
                    class="flex max-h-[calc(92vh-64px)] flex-col gap-5 overflow-y-auto px-5 pt-2 pb-6"
                >
                    <div class="grid gap-2">
                        <span class="text-sm font-bold text-gray-800">
                            メディア
                        </span>
                        <div
                            class="relative flex min-h-[360px] items-center justify-center rounded-3xl bg-gray-100 p-8"
                        >
                            <div
                                ref="mobileImageCropPreview"
                                class="relative touch-none overflow-hidden rounded-2xl"
                                :style="mobileImageEditorPreviewStyle"
                                @pointerdown.stop="startMobileImageCrop"
                                @pointermove.stop="dragMobileImageCrop"
                                @pointerup.stop="stopMobileImageCrop"
                                @pointercancel.stop="stopMobileImageCrop"
                                @click="
                                    !isMobileImageCropping
                                        ? chooseMobileImage()
                                        : null
                                "
                            >
                                <img
                                    :src="mobileImageEditorWidget.thumbnail_url"
                                    alt=""
                                    class="h-full w-full object-cover"
                                    :style="mobileImageEditorCropStyle"
                                    draggable="false"
                                />
                                <div
                                    v-if="isMobileImageCropping"
                                    class="pointer-events-none absolute inset-0 rounded-2xl ring-2 ring-black ring-inset"
                                ></div>
                            </div>

                            <button
                                type="button"
                                aria-label="クロップを調整"
                                class="absolute right-8 bottom-8 flex size-10 items-center justify-center rounded-xl shadow-md transition-colors"
                                :class="
                                    isMobileImageCropping
                                        ? 'bg-white text-black'
                                        : 'bg-black text-white'
                                "
                                @click.stop="
                                    isMobileImageCropping =
                                        !isMobileImageCropping
                                "
                            >
                                <Crop class="size-5" />
                            </button>

                            <input
                                ref="mobileImageInput"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="updateMobileImage"
                            />
                        </div>
                    </div>

                    <label class="grid gap-2">
                        <span class="text-sm font-bold text-gray-800">
                            キャプション
                        </span>
                        <input
                            :value="mobileImageEditorWidget.settings?.title ?? ''"
                            type="text"
                            class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-base font-semibold text-gray-900 transition-colors outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/15"
                            placeholder="キャプションを入力"
                            @input="updateMobileImageCaption"
                        />
                    </label>

                    <label class="grid gap-2">
                        <span class="text-sm font-bold text-gray-800">
                            リンク
                        </span>
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
                            mobileImageEditorWidget.content ? '' : 'opacity-50'
                        "
                    >
                        <span class="text-sm font-bold text-gray-800">
                            センシティブ
                        </span>
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
                            aria-label="センシティブ"
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
            :open="Boolean(mobileTextEditorWidget)"
            @update:open="setMobileTextEditorOpen"
        >
            <SheetContent
                side="bottom"
                :show-close="mobileTextEditorMode !== 'add'"
                :close-label="mobileTextEditorMode === 'add' ? undefined : '完了'"
                close-class="rounded-full bg-black px-3 py-1.5 text-xs font-bold text-white opacity-100 shadow-sm hover:bg-black hover:opacity-100 focus:ring-black/20"
                overlay-class="z-[9999]"
                class="z-[9999] max-h-[92vh] overflow-hidden rounded-t-3xl border-gray-200 bg-white p-0 gap-0"
                @click.stop
                @pointerdown.stop
                @touchstart.stop
            >
                <SheetHeader class="border-b border-gray-100 px-5 py-4 text-left">
                    <SheetTitle
                        :class="
                            mobileTextEditorMode === 'add'
                                ? 'sr-only'
                                : 'text-base font-bold text-gray-950'
                        "
                    >
                        {{
                            mobileTextEditorMode === 'add'
                                ? 'テキストを追加'
                                : 'テキストを編集'
                        }}
                    </SheetTitle>
                    <button
                        v-if="mobileTextEditorMode === 'add'"
                        type="button"
                        class="absolute top-4 left-4 z-30 flex size-8 items-center justify-center rounded-full text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-950"
                        aria-label="キャンセル"
                        title="キャンセル"
                        @click="closeMobileTextEditor"
                    >
                        <X class="size-5" />
                    </button>
                    <button
                        v-if="mobileTextEditorMode === 'add'"
                        type="button"
                        class="absolute top-4 right-4 z-30 rounded-full bg-black px-3 py-1.5 text-xs font-bold text-white shadow-sm transition-colors hover:bg-black"
                        @click="completeMobileTextEditor"
                    >
                        追加
                    </button>
                </SheetHeader>

                <div
                    v-if="mobileTextEditorWidget"
                    class="flex max-h-[calc(92vh-64px)] flex-col gap-5 overflow-y-auto px-5 pt-2 pb-6"
                >
                    <div class="grid gap-2">
                        <span class="text-sm font-bold text-gray-800">
                            テキスト
                        </span>
                        <div
                            class="relative flex min-h-[300px] items-center justify-center rounded-3xl bg-gray-100 p-8"
                        >
                            <div
                                class="flex flex-col overflow-auto rounded-2xl border border-gray-200 p-4 text-sm leading-snug font-semibold whitespace-pre-wrap transition-colors focus-within:ring-4 focus-within:ring-blue-500/15"
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
                                    class="mobile-text-editor min-h-[1.25em] w-full break-words whitespace-pre-wrap bg-transparent outline-none"
                                    :class="mobileTextEditorInputClasses"
                                    @input="updateMobileTextContent"
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
                        <span class="text-sm font-bold text-gray-800">
                            スタイル
                        </span>
                        <div class="flex gap-2 overflow-x-auto pb-1">
                            <button
                                type="button"
                                class="flex size-10 shrink-0 items-center justify-center rounded-xl border transition-colors"
                                :class="
                                    (mobileTextEditorWidget.settings
                                        ?.textAlign ?? 'left') === 'left'
                                        ? 'border-black bg-black text-white'
                                        : 'border-gray-200 bg-gray-50 text-gray-700'
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
                                        ? 'border-black bg-black text-white'
                                        : 'border-gray-200 bg-gray-50 text-gray-700'
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
                                        ? 'border-black bg-black text-white'
                                        : 'border-gray-200 bg-gray-50 text-gray-700'
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
                                        ? 'border-black bg-black text-white'
                                        : 'border-gray-200 bg-gray-50 text-gray-700'
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
                                        ?.verticalAlign ?? 'center') === 'center'
                                        ? 'border-black bg-black text-white'
                                        : 'border-gray-200 bg-gray-50 text-gray-700'
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
                                        ? 'border-black bg-black text-white'
                                        : 'border-gray-200 bg-gray-50 text-gray-700'
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
                        <span class="text-sm font-bold text-gray-800">
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
                                        ? 'border-black ring-2 ring-black/15'
                                        : 'border-gray-200'
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
                                class="h-10 w-[90px] shrink-0 rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm font-bold text-gray-800 outline-none transition-colors focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/15"
                                placeholder="#FFFFFF"
                                aria-label="カラーコード"
                                @input="updateMobileTextBackgroundColorInput"
                            />
                        </div>
                    </div>

                    <label class="grid gap-2">
                        <span class="text-sm font-bold text-gray-800">URL</span>
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
                            mobileTextEditorWidget.content ? '' : 'opacity-50'
                        "
                    >
                        <span class="text-sm font-bold text-gray-800">
                            センシティブ
                        </span>
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
                            aria-label="センシティブ"
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
                :show-close="mobileSectionEditorMode !== 'add'"
                :close-label="mobileSectionEditorMode === 'add' ? undefined : '完了'"
                close-class="rounded-full bg-black px-3 py-1.5 text-xs font-bold text-white opacity-100 shadow-sm hover:bg-black hover:opacity-100 focus:ring-black/20"
                overlay-class="z-[9999]"
                class="z-[9999] max-h-[92vh] overflow-hidden rounded-t-3xl border-gray-200 bg-white p-0 gap-0"
                @click.stop
                @pointerdown.stop
                @touchstart.stop
            >
                <SheetHeader class="border-b border-gray-100 px-5 py-4 text-left">
                    <SheetTitle
                        :class="
                            mobileSectionEditorMode === 'add'
                                ? 'sr-only'
                                : 'text-base font-bold text-gray-950'
                        "
                    >
                        {{
                            mobileSectionEditorMode === 'add'
                                ? 'セクションを追加'
                                : 'セクションを編集'
                        }}
                    </SheetTitle>
                    <button
                        v-if="mobileSectionEditorMode === 'add'"
                        type="button"
                        class="absolute top-4 left-4 z-30 flex size-8 items-center justify-center rounded-full text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-950"
                        aria-label="キャンセル"
                        title="キャンセル"
                        @click="closeMobileSectionEditor"
                    >
                        <X class="size-5" />
                    </button>
                    <button
                        v-if="mobileSectionEditorMode === 'add'"
                        type="button"
                        class="absolute top-4 right-4 z-30 rounded-full bg-black px-3 py-1.5 text-xs font-bold text-white shadow-sm transition-colors hover:bg-black"
                        @click="completeMobileSectionEditor"
                    >
                        追加
                    </button>
                </SheetHeader>

                <div
                    v-if="mobileSectionEditorWidget"
                    class="flex max-h-[calc(92vh-64px)] flex-col gap-5 overflow-y-auto px-5 pt-2 pb-6"
                >
                    <label class="grid gap-2">
                        <span class="text-sm font-bold text-gray-800">
                            セクション
                        </span>
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
