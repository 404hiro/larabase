<script setup lang="ts">
import {
    AlignCenter,
    AlignLeft,
    AlignRight,
    AlignVerticalJustifyCenter,
    AlignVerticalJustifyEnd,
    AlignVerticalJustifyStart,
    Circle,
    Crop,
    Link,
    LockKeyhole,
    Trash2,
} from 'lucide-vue-next';
import { computed, onUnmounted, ref, watch } from 'vue';
import { onClickOutside } from '@vueuse/core';

const props = defineProps<{
    widget: any;
    mode: 'desktop' | 'mobile';
    sizeOptions: any[];
    isCropping?: boolean;
}>();

const emit = defineEmits<{
    delete: [];
    editLink: [];
    lockOpen: [isOpen: boolean];
    toggleCrop: [];
    updateBackgroundColor: [color: string];
    updateSensitive: [isSensitive: boolean];
    updateTextAlign: [align: 'left' | 'center' | 'right'];
    updateVerticalAlign: [align: 'start' | 'center' | 'end'];
    resize: [size: { w: number; h: number }];
}>();

const showColorPicker = ref(false);
const showContentLabels = ref(false);
const customColorValue = ref('');
const controlsRef = ref<HTMLElement | null>(null);

onClickOutside(controlsRef, () => {
    showColorPicker.value = false;
    showContentLabels.value = false;
});

const colorSwatches = [
    '#FFFFFF',
    '#FBCFE8',
    '#BFDBFE',
    '#BBF7D0',
    '#1F2937',
];

const backgroundColor = computed(
    () => props.widget.settings?.bgColor || '#FFFFFF',
);
const textAlign = computed(() => props.widget.settings?.textAlign || 'left');
const verticalAlign = computed(
    () => props.widget.settings?.verticalAlign || 'center',
);
const isSensitive = computed(() => Boolean(props.widget.settings?.sensitive));

const isWidgetSize = (size: { w: number; h: number }) => {
    const width =
        props.mode === 'desktop' ? props.widget.w : props.widget.w_mobile;
    const height =
        props.mode === 'desktop' ? props.widget.h : props.widget.h_mobile;
    return Number(width) === size.w && Number(height) === size.h;
};

const sizeButtonClass = (size: { w: number; h: number }) => {
    return [
        'cursor-pointer flex items-center justify-center rounded-lg transition-all duration-200',
        'size-8',
        isWidgetSize(size)
            ? 'bg-white text-gray-950 shadow-sm'
            : 'bg-black/0 text-white hover:bg-white/10',
    ];
};

const toolButtonClass = (isActive = false) => {
    return [
        'cursor-pointer flex items-center justify-center rounded-lg transition-all duration-200',
        'size-8',
        isActive
            ? 'bg-white text-gray-950 shadow-sm'
            : 'bg-black/0 text-white hover:bg-white/10',
    ];
};

const colorButtonClass = (color: string) => {
    return [
        'cursor-pointer size-8 rounded-lg border-2 transition-transform hover:scale-105',
        color.toLowerCase() === backgroundColor.value.toLowerCase()
            ? 'border-white ring-2 ring-gray-400'
            : 'border-gray-500',
    ];
};

const sizeIconClass = (key: string, isActive: boolean) => {
    return [
        'block border-2 transition-colors',
        key === 'small'
            ? 'size-3.5 rounded-[4px]'
            : key === 'wide'
              ? 'h-3 w-5 rounded-[5px]'
              : key === 'tall'
                ? 'h-5 w-3 rounded-[5px]'
                : key === 'large'
                  ? 'size-[18px] rounded-[4px]'
                  : 'size-4 rounded-[4px]',
        isActive ? 'border-gray-950' : 'border-white',
    ];
};

const setBackgroundColor = (color: string) => {
    customColorValue.value = color.toUpperCase();
    emit('updateBackgroundColor', color);
};

const normalizeColor = (color: string) => {
    const trimmedColor = color.trim();

    if (/^#[0-9a-f]{3}$/i.test(trimmedColor)) {
        return `#${trimmedColor
            .slice(1)
            .split('')
            .map((value) => value + value)
            .join('')}`.toUpperCase();
    }

    if (/^#[0-9a-f]{6}$/i.test(trimmedColor)) {
        return trimmedColor.toUpperCase();
    }

    return null;
};

const toggleColorPicker = () => {
    customColorValue.value = backgroundColor.value.toUpperCase();
    showColorPicker.value = !showColorPicker.value;
};

const openLinkSettings = () => {
    showColorPicker.value = false;
    emit('editLink');
};

const toggleContentLabels = () => {
    showContentLabels.value = !showContentLabels.value;
};

const toggleSensitive = () => {
    emit('updateSensitive', !isSensitive.value);
};

const shouldShowContentLabelsButton = computed(() => {
    return props.widget.type === 'link' || Boolean(props.widget.content);
});

const updateCustomColor = (event: Event) => {
    const value = (event.target as HTMLInputElement).value.toUpperCase();
    customColorValue.value = value;

    const normalizedColor = normalizeColor(value);

    if (normalizedColor) {
        setBackgroundColor(normalizedColor);
    }
};

watch([showColorPicker, showContentLabels], ([isColorOpen, isLabelsOpen]) =>
    emit('lockOpen', isColorOpen || isLabelsOpen),
);

watch(backgroundColor, (color) => {
    if (!showColorPicker.value) {
        customColorValue.value = color.toUpperCase();
    }
});

onUnmounted(() => emit('lockOpen', false));
</script>

<template>
    <div
        ref="controlsRef"
        class="link-widget-controls pointer-events-none absolute inset-0 z-[140]"
    >
        <!-- Delete Button -->
        <button
            @click.prevent.stop="emit('delete')"
            @pointerdown.prevent.stop
            @mousedown.prevent.stop
            @touchstart.prevent.stop
            class="pointer-events-auto absolute -top-2.5 -left-2.5 flex size-8 cursor-pointer items-center justify-center rounded-xl bg-black/90 text-white shadow-lg backdrop-blur-md transition-all duration-200 hover:scale-110 hover:bg-black active:scale-95"
        >
            <Trash2 class="size-4" />
        </button>

        <div
            v-if="widget.type === 'text' && showColorPicker"
            class="pointer-events-auto absolute right-1/2 z-[150] flex translate-x-1/2 items-center gap-2 rounded-xl bg-black/80 p-2 shadow-xl ring-1 ring-white/10 backdrop-blur-md"
            :class="
                mode === 'desktop' ? 'bottom-[-5.25rem]' : 'bottom-[-4.5rem]'
            "
            @click.stop
            @pointerdown.stop
            @mousedown.stop
            @touchstart.stop
        >
            <button
                v-for="color in colorSwatches"
                :key="color"
                :aria-label="`背景色 ${color}`"
                :title="color"
                :style="{ backgroundColor: color }"
                :class="colorButtonClass(color)"
                @click.prevent.stop="setBackgroundColor(color)"
            ></button>
            <input
                :value="customColorValue || backgroundColor"
                type="text"
                inputmode="text"
                maxlength="7"
                aria-label="カラーコード"
                class="h-8 w-20 rounded-lg border border-white/15 bg-white px-2 text-xs font-bold text-gray-800 shadow-sm outline-none focus:ring-2 focus:ring-white/50"
                placeholder="#000"
                @input="updateCustomColor"
                @click.stop
                @keydown.stop
            />
        </div>

        <!-- Resize Controls & Content Settings -->
        <div
            v-if="sizeOptions && sizeOptions.length > 0"
            class="pointer-events-auto absolute right-1/2 z-[140] flex translate-x-1/2 items-center gap-1.5 rounded-2xl bg-black/80 p-1.5 text-white shadow-xl ring-1 ring-white/10 backdrop-blur-md"
            :class="mode === 'desktop' ? '-bottom-10' : '-bottom-8'"
            @click.stop
            @pointerdown.prevent.stop
            @mousedown.prevent.stop
            @touchstart.prevent.stop
        >
            <button
                v-for="option in sizeOptions"
                :key="option.key"
                :aria-label="option.label"
                :title="option.label"
                @click.prevent.stop="emit('resize', option.size)"
                :class="sizeButtonClass(option.size)"
            >
                <span
                    v-if="widget.type !== 'section'"
                    :class="sizeIconClass(option.key, isWidgetSize(option.size))"
                ></span>
                <component
                    v-else
                    :is="option.icon"
                    :class="mode === 'desktop' ? 'size-4' : 'size-3.5'"
                />
            </button>

            <template v-if="widget.type === 'image'">
                <div class="mx-1 h-7 w-px bg-gray-600"></div>
                <button
                    aria-label="リンクを設定"
                    title="リンクを設定"
                    @click.prevent.stop="emit('editLink')"
                    :class="toolButtonClass(Boolean(widget.content))"
                >
                    <Link :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" />
                </button>
                <button
                    v-if="shouldShowContentLabelsButton"
                    aria-label="コンテンツ設定"
                    title="コンテンツ設定"
                    @click.prevent.stop="toggleContentLabels"
                    :class="toolButtonClass(showContentLabels || isSensitive)"
                >
                    <LockKeyhole
                        :class="mode === 'desktop' ? 'size-4' : 'size-3.5'"
                    />
                </button>
                <button
                    aria-label="クロップを調整"
                    title="クロップを調整"
                    @click.prevent.stop="emit('toggleCrop')"
                    :class="toolButtonClass(isCropping)"
                >
                    <Crop :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" />
                </button>
            </template>

            <template v-if="widget.type === 'link'">
                <div class="mx-1 h-7 w-px bg-gray-600"></div>
                <button
                    v-if="shouldShowContentLabelsButton"
                    aria-label="コンテンツ設定"
                    title="コンテンツ設定"
                    @click.prevent.stop="toggleContentLabels"
                    :class="toolButtonClass(showContentLabels || isSensitive)"
                >
                    <LockKeyhole
                        :class="mode === 'desktop' ? 'size-4' : 'size-3.5'"
                    />
                </button>
            </template>

            <!-- Content Settings Popup -->
            <div
                v-if="showContentLabels"
                class="absolute top-full left-0 w-full rounded-2xl bg-black/85 p-3 text-white shadow-xl ring-1 ring-white/10 backdrop-blur-md"
                @click.stop
                @pointerdown.stop
                @mousedown.stop
                @touchstart.stop
            >
                <div class="flex items-center gap-2 px-1 pb-3 text-base font-bold">
                    <LockKeyhole class="size-5 text-white/70" />
                    <span>コンテンツ設定</span>
                </div>
                <div class="h-px bg-white/15"></div>
                <label
                    class="mt-3 flex cursor-pointer items-center justify-between rounded-xl border border-white/15 bg-white/10 p-3"
                >
                    <span class="text-sm font-bold text-white/80">
                        センシティブ
                    </span>
                    <button
                        type="button"
                        role="switch"
                        :aria-checked="isSensitive"
                        aria-label="センシティブ"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 focus:outline-none"
                        :class="isSensitive ? 'bg-blue-600' : 'bg-white/20'"
                        @click.prevent.stop="toggleSensitive"
                    >
                        <span
                            class="pointer-events-none inline-block size-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                            :class="isSensitive ? 'translate-x-5' : 'translate-x-0'"
                        ></span>
                    </button>
                </label>
            </div>

            <template v-if="widget.type === 'text'">
                <div class="mx-1 h-7 w-px bg-gray-600"></div>
                <button
                    aria-label="背景色を変更"
                    title="背景色を変更"
                    @click.prevent.stop="toggleColorPicker"
                    :class="toolButtonClass(showColorPicker)"
                >
                    <Circle
                        :class="mode === 'desktop' ? 'size-4' : 'size-3.5'"
                        :fill="backgroundColor"
                    />
                </button>
                <button
                    aria-label="リンクを設定"
                    title="リンクを設定"
                    @click.prevent.stop="openLinkSettings"
                    :class="toolButtonClass(Boolean(widget.content))"
                >
                    <Link :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" />
                </button>
                <button
                    v-if="shouldShowContentLabelsButton"
                    aria-label="コンテンツ設定"
                    title="コンテンツ設定"
                    @click.prevent.stop="toggleContentLabels"
                    :class="toolButtonClass(showContentLabels || isSensitive)"
                >
                    <LockKeyhole
                        :class="mode === 'desktop' ? 'size-4' : 'size-3.5'"
                    />
                </button>
            </template>
        </div>

        <div
            v-if="widget.type === 'text' && !showColorPicker && !showContentLabels"
            class="pointer-events-auto absolute right-1/2 z-[140] flex translate-x-1/2 items-center gap-1.5 rounded-2xl bg-black/80 p-1.5 text-white shadow-xl ring-1 ring-white/10 backdrop-blur-md"
            :class="mode === 'desktop' ? 'bottom-[-5.25rem]' : 'bottom-[-4.5rem]'"
            @click.stop
            @pointerdown.prevent.stop
            @mousedown.prevent.stop
            @touchstart.prevent.stop
        >
            <button
                aria-label="左寄せ"
                title="左寄せ"
                @click.prevent.stop="emit('updateTextAlign', 'left')"
                :class="toolButtonClass(textAlign === 'left')"
            >
                <AlignLeft :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" />
            </button>
            <button
                aria-label="中央寄せ"
                title="中央寄せ"
                @click.prevent.stop="emit('updateTextAlign', 'center')"
                :class="toolButtonClass(textAlign === 'center')"
            >
                <AlignCenter :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" />
            </button>
            <button
                aria-label="右寄せ"
                title="右寄せ"
                @click.prevent.stop="emit('updateTextAlign', 'right')"
                :class="toolButtonClass(textAlign === 'right')"
            >
                <AlignRight :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" />
            </button>

            <div class="mx-1 h-7 w-px bg-gray-600"></div>

            <button
                aria-label="上寄せ"
                title="上寄せ"
                @click.prevent.stop="emit('updateVerticalAlign', 'start')"
                :class="toolButtonClass(verticalAlign === 'start')"
            >
                <AlignVerticalJustifyStart :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" />
            </button>
            <button
                aria-label="上下中央"
                title="上下中央"
                @click.prevent.stop="emit('updateVerticalAlign', 'center')"
                :class="toolButtonClass(verticalAlign === 'center')"
            >
                <AlignVerticalJustifyCenter :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" />
            </button>
            <button
                aria-label="下寄せ"
                title="下寄せ"
                @click.prevent.stop="emit('updateVerticalAlign', 'end')"
                :class="toolButtonClass(verticalAlign === 'end')"
            >
                <AlignVerticalJustifyEnd :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" />
            </button>
        </div>
    </div>
</template>
