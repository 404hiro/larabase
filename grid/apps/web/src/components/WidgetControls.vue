<script setup lang="ts">
import type { LinkWidget } from '@/lib/api';
import {
  AlignCenter,
  AlignLeft,
  AlignRight,
  AlignVerticalJustifyCenter,
  AlignVerticalJustifyEnd,
  AlignVerticalJustifyStart,
  Circle,
  CirclePlay,
  Crop,
  Link,
  ListVideo,
  LockKeyhole,
  Minus,
  Move,
  Plus,
  Search,
  Trash2,
} from 'lucide-vue-next';
import { computed, onUnmounted, ref, watch } from 'vue';

type MapLocation = {
  title: string;
  address: string;
  lat: number;
  lng: number;
  zoom: number;
};

const props = defineProps<{
  widget: LinkWidget;
  mode: 'desktop' | 'mobile';
  isCropping?: boolean;
  isMapMoving?: boolean;
  sizeOptions: Array<{ key: string; label: string; size: { w: number; h: number }; icon?: unknown }>;
}>();

const emit = defineEmits<{
  delete: [];
  resize: [size: { w: number; h: number }];
  editLink: [];
  lockOpen: [isOpen: boolean];
  toggleCrop: [];
  toggleMapMove: [];
  closeMapMove: [];
  updateMapZoom: [delta: number];
  updateMapLocation: [location: MapLocation];
  updateBackgroundColor: [color: string];
  updateSensitive: [isSensitive: boolean];
  updateTextAlign: [align: 'left' | 'center' | 'right'];
  updateVerticalAlign: [align: 'start' | 'center' | 'end'];
  updateYoutubeMode: [mode: 'link' | 'link_embed' | 'embed'];
}>();

const colorSwatches = ['#FFFFFF', '#FBCFE8', '#BFDBFE', '#BBF7D0', '#1F2937'];
const controlsRef = ref<HTMLElement | null>(null);
const showColorPicker = ref(false);
const showContentLabels = ref(false);
const showMapSearch = ref(false);
const mapSearchQuery = ref('');
const mapSearchResults = ref<Array<{ place_id: number; display_name: string; lat: string; lon: string; name?: string }>>([]);
const isSearchingMap = ref(false);
const mapSearchError = ref('');
const customColorValue = ref('');
let mapSearchTimeout: number | null = null;
let mapSearchAbortController: AbortController | null = null;

const backgroundColor = computed(() => props.widget.settings?.bgColor || '#FFFFFF');
const textAlign = computed(() => props.widget.settings?.textAlign || 'left');
const verticalAlign = computed(() => props.widget.settings?.verticalAlign || 'center');
const isSensitive = computed(() => Boolean(props.widget.settings?.sensitive));
const youtubeMode = computed(() => props.widget.settings?.youtubeMode || 'link');

const isWidgetSize = (size: { w: number; h: number }) => {
  const width = props.mode === 'desktop' ? props.widget.w : props.widget.w_mobile;
  const height = props.mode === 'desktop' ? props.widget.h : props.widget.h_mobile;
  return Number(width) === size.w && Number(height) === size.h;
};

const isYouTubeVideoUrl = (value: string | null | undefined) => {
  if (!value) return false;
  try {
    const url = new URL(value);
    const host = url.hostname.toLowerCase();
    const isYouTubeHost = host === 'youtube.com' || host.endsWith('.youtube.com') || host === 'youtu.be' || host.endsWith('.youtu.be');
    if (!isYouTubeHost) return false;
    return url.pathname === '/watch' || url.pathname.startsWith('/live/') || url.pathname.startsWith('/shorts/') || (host === 'youtu.be' && url.pathname.length > 1);
  } catch {
    return false;
  }
};

const isMusicUrl = (value: string | null | undefined) => {
  if (!value) return false;
  try {
    const url = new URL(value);
    const host = url.hostname.toLowerCase().replace(/^www\./, '');
    const pathParts = url.pathname.split('/').filter(Boolean);
    return host === 'music.youtube.com' || host === 'vimeo.com' || (host === 'tiktok.com' && pathParts[1] === 'video');
  } catch {
    return false;
  }
};

const isEmbeddableWidget = computed(() => {
  const width = props.mode === 'desktop' ? props.widget.w : props.widget.w_mobile;
  const height = props.mode === 'desktop' ? props.widget.h : props.widget.h_mobile;
  const isInline = Number(width) === 2 && Number(height) === 1;
  return props.widget.type === 'link' && !isInline && (isYouTubeVideoUrl(props.widget.content) || isMusicUrl(props.widget.content));
});

const shouldShowContentLabelsButton = computed(() => props.widget.type === 'link' || Boolean(props.widget.content));

const sizeButtonClass = (size: { w: number; h: number }) => [
  'flex size-8 cursor-pointer items-center justify-center rounded-lg transition-all duration-200',
  isWidgetSize(size) ? 'bg-white text-gray-950 shadow-sm' : 'bg-black/0 text-white hover:bg-white/10',
];

const toolButtonClass = (isActive = false) => [
  'flex size-8 cursor-pointer items-center justify-center rounded-lg transition-all duration-200',
  isActive ? 'bg-white text-gray-950 shadow-sm' : 'bg-black/0 text-white hover:bg-white/10',
];

const colorButtonClass = (color: string) => [
  'size-8 cursor-pointer rounded-lg border-2 transition-transform hover:scale-105',
  color.toLowerCase() === backgroundColor.value.toLowerCase() ? 'border-white ring-2 ring-gray-400' : 'border-gray-500',
];

const sizeIconClass = (key: string, isActive: boolean) => [
  'block border-2 transition-colors',
  key === 'small'
    ? 'size-3.5 rounded-[4px]'
    : key === 'wide'
      ? 'h-3 w-5 rounded-[5px]'
      : key === 'tall'
        ? 'h-5 w-3 rounded-[5px]'
        : key === 'large'
          ? 'size-[18px] rounded-[4px]'
          : key === 'inline'
            ? 'h-2 w-5 rounded-[4px]'
            : 'size-4 rounded-[4px]',
  isActive ? 'border-gray-950' : 'border-white',
];

const closeMapMoveIfActive = () => {
  if (props.isMapMoving) {
    emit('closeMapMove');
  }
};

const resizeWidget = (size: { w: number; h: number }) => {
  closeMapMoveIfActive();
  emit('resize', size);
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

const setBackgroundColor = (color: string) => {
  customColorValue.value = color.toUpperCase();
  emit('updateBackgroundColor', color);
};

const updateCustomColor = (event: Event) => {
  const value = (event.target as HTMLInputElement).value.toUpperCase();
  customColorValue.value = value;
  const normalizedColor = normalizeColor(value);
  if (normalizedColor) {
    setBackgroundColor(normalizedColor);
  }
};

const toggleColorPicker = () => {
  closeMapMoveIfActive();
  customColorValue.value = backgroundColor.value.toUpperCase();
  showContentLabels.value = false;
  showMapSearch.value = false;
  showColorPicker.value = !showColorPicker.value;
};

const openLinkSettings = () => {
  closeMapMoveIfActive();
  showColorPicker.value = false;
  showContentLabels.value = false;
  showMapSearch.value = false;
  emit('editLink');
};

const toggleContentLabels = () => {
  closeMapMoveIfActive();
  showColorPicker.value = false;
  showMapSearch.value = false;
  showContentLabels.value = !showContentLabels.value;
};

const toggleMapMove = () => {
  showColorPicker.value = false;
  showContentLabels.value = false;
  showMapSearch.value = false;
  emit('toggleMapMove');
};

const toggleMapSearch = () => {
  closeMapMoveIfActive();
  showColorPicker.value = false;
  showContentLabels.value = false;
  mapSearchError.value = '';
  mapSearchQuery.value = props.widget.settings?.address || props.widget.settings?.title || '';
  showMapSearch.value = !showMapSearch.value;
  if (showMapSearch.value) {
    searchMapLocations(mapSearchQuery.value);
  }
};

const toggleSensitive = () => {
  closeMapMoveIfActive();
  emit('updateSensitive', !isSensitive.value);
};

const extractMapLocationTitle = (displayName: string, name?: string) => {
  const title = (name || displayName.split(',')[0] || '').trim();
  return title || displayName;
};

const searchMapLocations = async (query: string) => {
  const trimmedQuery = query.trim();
  mapSearchAbortController?.abort();
  if (trimmedQuery.length < 2) {
    mapSearchResults.value = [];
    mapSearchError.value = '';
    isSearchingMap.value = false;
    return;
  }

  mapSearchAbortController = new AbortController();
  isSearchingMap.value = true;
  mapSearchError.value = '';
  try {
    const params = new URLSearchParams({
      q: trimmedQuery,
      format: 'jsonv2',
      limit: '5',
      addressdetails: '1',
      'accept-language': 'ja,en',
    });
    const response = await fetch(`https://nominatim.openstreetmap.org/search?${params.toString()}`, {
      signal: mapSearchAbortController.signal,
      headers: { Accept: 'application/json' },
    });
    if (!response.ok) throw new Error('Failed to search map locations.');
    mapSearchResults.value = await response.json();
  } catch (error) {
    if ((error as DOMException).name === 'AbortError') return;
    mapSearchError.value = '候補を取得できませんでした';
    mapSearchResults.value = [];
  } finally {
    isSearchingMap.value = false;
  }
};

const selectMapLocation = (result: { display_name: string; lat: string; lon: string; name?: string }) => {
  emit('updateMapLocation', {
    title: extractMapLocationTitle(result.display_name, result.name),
    address: result.display_name,
    lat: Number(result.lat),
    lng: Number(result.lon),
    zoom: 15,
  });
  showMapSearch.value = false;
  mapSearchQuery.value = result.display_name;
  mapSearchResults.value = [];
};

watch([showColorPicker, showContentLabels, showMapSearch], ([isColorOpen, isLabelsOpen, isMapSearchOpen]) => {
  emit('lockOpen', isColorOpen || isLabelsOpen || isMapSearchOpen);
});

watch(mapSearchQuery, (query) => {
  if (!showMapSearch.value) return;
  if (mapSearchTimeout !== null) {
    window.clearTimeout(mapSearchTimeout);
  }
  mapSearchTimeout = window.setTimeout(() => searchMapLocations(query), 300);
});

watch(backgroundColor, (color) => {
  if (!showColorPicker.value) {
    customColorValue.value = color.toUpperCase();
  }
});

const closeFloatingPanels = (event: PointerEvent) => {
  const target = event.target as Node | null;
  if (target && controlsRef.value?.contains(target)) return;
  showColorPicker.value = false;
  showContentLabels.value = false;
  showMapSearch.value = false;
};

window.addEventListener('pointerdown', closeFloatingPanels);

onUnmounted(() => {
  window.removeEventListener('pointerdown', closeFloatingPanels);
  if (mapSearchTimeout !== null) {
    window.clearTimeout(mapSearchTimeout);
  }
  mapSearchAbortController?.abort();
  emit('lockOpen', false);
});
</script>

<template>
  <div ref="controlsRef" class="link-widget-controls pointer-events-none absolute inset-0 z-[3200]">
    <button
      type="button"
      class="pointer-events-auto absolute -top-2.5 -left-2.5 flex size-8 cursor-pointer items-center justify-center rounded-xl bg-red-600 text-white shadow-lg backdrop-blur-md transition-all duration-200 hover:scale-110 hover:bg-red-700 active:scale-95"
      aria-label="ウィジェットを削除"
      @click.prevent.stop="emit('delete')"
      @pointerdown.stop
      @mousedown.stop
      @touchstart.stop
    >
      <Trash2 class="size-4" />
    </button>

    <div
      v-if="widget.type === 'text' && showColorPicker"
      class="pointer-events-auto absolute right-1/2 z-[150] flex h-11 translate-x-1/2 items-center gap-1.5 rounded-2xl bg-black/80 p-1.5 shadow-xl ring-1 ring-white/10 backdrop-blur-md"
      :class="mode === 'desktop' ? 'bottom-1' : 'bottom-[-4.25rem]'"
      @click.stop
      @pointerdown.stop
      @mousedown.stop
      @touchstart.stop
    >
      <button
        v-for="color in colorSwatches"
        :key="color"
        type="button"
        :aria-label="`背景色 ${color}`"
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

    <div
      v-if="sizeOptions && sizeOptions.length > 0"
      class="pointer-events-auto absolute right-1/2 z-[3200] flex translate-x-1/2 items-center gap-1.5 rounded-2xl bg-black/80 p-1.5 text-white shadow-xl ring-1 ring-white/10 backdrop-blur-md"
      :class="mode === 'desktop' ? '-bottom-10' : '-bottom-8'"
      @click.stop
      @pointerdown.prevent.stop
      @mousedown.prevent.stop
      @touchstart.prevent.stop
    >
      <button
        v-for="option in sizeOptions"
        :key="option.key"
        type="button"
        class="cursor-pointer"
        :aria-label="option.label"
        :class="sizeButtonClass(option.size)"
        :title="option.label"
        @click.prevent.stop="resizeWidget(option.size)"
      >
        <span :class="sizeIconClass(option.key, isWidgetSize(option.size))"></span>
      </button>

      <template v-if="widget.type === 'map'">
        <div class="mx-1 h-7 w-px bg-gray-600"></div>
        <button type="button" aria-label="ロケーションを検索" :class="toolButtonClass(showMapSearch)" title="ロケーションを検索" @click.prevent.stop="toggleMapSearch">
          <Search :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" />
        </button>
        <button type="button" aria-label="マップを移動" :class="toolButtonClass(Boolean(isMapMoving))" title="マップを移動" @click.prevent.stop="toggleMapMove">
          <Move :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" />
        </button>
        <div
          v-if="isMapMoving"
          class="pointer-events-auto absolute top-[calc(100%+0.125rem)] right-0 z-[3300] flex items-center gap-1.5 rounded-2xl bg-black/80 p-1.5 text-white shadow-2xl ring-1 ring-white/10 backdrop-blur-md"
          @click.stop
          @pointerdown.stop
          @mousedown.stop
          @touchstart.stop
        >
          <button type="button" aria-label="縮小" class="flex size-8 cursor-pointer items-center justify-center rounded-lg text-white/70 transition-colors hover:bg-white/10 hover:text-white" @click.prevent.stop="emit('updateMapZoom', -1)">
            <Minus class="size-4" />
          </button>
          <button type="button" aria-label="拡大" class="flex size-8 cursor-pointer items-center justify-center rounded-lg text-white/70 transition-colors hover:bg-white/10 hover:text-white" @click.prevent.stop="emit('updateMapZoom', 1)">
            <Plus class="size-4" />
          </button>
        </div>
        <div
          v-if="showMapSearch"
          class="pointer-events-auto absolute top-[calc(100%+0.125rem)] left-1/2 z-[3300] flex w-72 -translate-x-1/2 flex-col gap-2 rounded-2xl bg-[#292929] p-3 text-sm text-white shadow-2xl ring-1 ring-white/10"
          @click.stop
          @pointerdown.stop
          @mousedown.stop
          @touchstart.stop
        >
          <input
            v-model="mapSearchQuery"
            type="text"
            aria-label="ロケーション"
            placeholder="ロケーションを入力"
            class="h-10 w-full rounded-xl border border-white/20 bg-white/8 px-3 text-sm font-semibold text-white outline-none placeholder:text-white/45 focus:border-white/50 focus:ring-2 focus:ring-white/20"
            @keydown.stop
          />
          <div v-if="isSearchingMap" class="px-2 py-2 text-sm font-semibold text-white/60">検索中...</div>
          <div v-else-if="mapSearchError" class="px-2 py-2 text-sm font-semibold text-red-200">{{ mapSearchError }}</div>
          <div v-else-if="mapSearchQuery.trim().length >= 2 && mapSearchResults.length === 0" class="px-2 py-2 text-sm font-semibold text-white/60">
            候補が見つかりません
          </div>
          <button
            v-for="result in mapSearchResults"
            :key="result.place_id"
            type="button"
            class="block w-full cursor-pointer truncate rounded-xl px-3 py-2 text-left text-sm font-semibold text-white/85 transition-colors hover:bg-white/10"
            @click.prevent.stop="selectMapLocation(result)"
          >
            {{ result.display_name }}
          </button>
        </div>
      </template>

      <template v-if="widget.type === 'image'">
        <div class="mx-1 h-7 w-px bg-gray-600"></div>
        <button type="button" aria-label="リンクを設定" :class="toolButtonClass(Boolean(widget.content))" title="リンクを設定" @click.prevent.stop="emit('editLink')">
          <Link :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" />
        </button>
        <button
          v-if="shouldShowContentLabelsButton"
          type="button"
          aria-label="リンクの確認設定"
          :class="toolButtonClass(showContentLabels || isSensitive)"
          title="リンクの確認設定"
          @click.prevent.stop="toggleContentLabels"
        >
          <LockKeyhole :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" />
        </button>
        <button
          type="button"
          :aria-label="isCropping ? '完了' : 'トリミング'"
          :class="[...toolButtonClass(isCropping), isCropping ? '!bg-white !text-gray-950' : '']"
          :title="isCropping ? '完了' : 'トリミング'"
          @click.prevent.stop="emit('toggleCrop')"
        >
          <Crop :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" />
        </button>
      </template>

      <template v-if="widget.type === 'link'">
        <div class="mx-1 h-7 w-px bg-gray-600"></div>
        <button
          v-if="shouldShowContentLabelsButton"
          type="button"
          aria-label="リンクの確認設定"
          :class="toolButtonClass(showContentLabels || isSensitive)"
          title="リンクの確認設定"
          @click.prevent.stop="toggleContentLabels"
        >
          <LockKeyhole :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" />
        </button>
      </template>

      <template v-if="widget.type === 'text'">
        <div class="mx-1 h-7 w-px bg-gray-600"></div>
        <button type="button" aria-label="背景色を変更" :class="toolButtonClass(showColorPicker)" title="背景色を変更" @click.prevent.stop="toggleColorPicker">
          <Circle :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" :fill="backgroundColor" />
        </button>
        <button type="button" aria-label="リンクを設定" :class="toolButtonClass(Boolean(widget.content))" title="リンクを設定" @click.prevent.stop="openLinkSettings">
          <Link :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" />
        </button>
        <button
          v-if="shouldShowContentLabelsButton"
          type="button"
          aria-label="リンクの確認設定"
          :class="toolButtonClass(showContentLabels || isSensitive)"
          title="リンクの確認設定"
          @click.prevent.stop="toggleContentLabels"
        >
          <LockKeyhole :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" />
        </button>
      </template>

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
          <span>リンクの確認設定</span>
        </div>
        <div class="h-px bg-white/15"></div>
        <label class="mt-3 flex cursor-pointer items-center justify-between rounded-xl border border-white/15 bg-white/10 p-3">
          <span class="text-sm font-bold text-white/80">開く前に確認を表示</span>
          <button
            type="button"
            role="switch"
            :aria-checked="isSensitive"
            aria-label="開く前に確認を表示"
            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 focus:outline-none"
            :class="isSensitive ? 'bg-blue-600' : 'bg-white/20'"
            @click.prevent.stop="toggleSensitive"
          >
            <span class="pointer-events-none inline-block size-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="isSensitive ? 'translate-x-5' : 'translate-x-0'"></span>
          </button>
        </label>
      </div>
    </div>

    <div
      v-if="isEmbeddableWidget && !showContentLabels"
      class="pointer-events-auto absolute right-1/2 z-[3200] flex translate-x-1/2 items-center gap-1.5 rounded-2xl bg-black/80 p-1.5 text-white shadow-xl ring-1 ring-white/10 backdrop-blur-md"
      :class="mode === 'desktop' ? (sizeOptions && sizeOptions.length > 0 ? '-bottom-[5rem]' : '-bottom-10') : sizeOptions && sizeOptions.length > 0 ? '-bottom-[4.5rem]' : '-bottom-8'"
      @click.stop
      @pointerdown.prevent.stop
      @mousedown.prevent.stop
      @touchstart.prevent.stop
    >
      <button type="button" aria-label="通常リンク" :class="toolButtonClass(youtubeMode === 'link')" title="通常リンク" @click.prevent.stop="emit('updateYoutubeMode', 'link')">
        <Link :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" />
      </button>
      <button type="button" aria-label="リンク＋埋め込み" :class="toolButtonClass(youtubeMode === 'link_embed')" title="リンク＋埋め込み" @click.prevent.stop="emit('updateYoutubeMode', 'link_embed')">
        <ListVideo :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" />
      </button>
      <button type="button" aria-label="埋め込みプレイヤー" :class="toolButtonClass(youtubeMode === 'embed')" title="埋め込みプレイヤー" @click.prevent.stop="emit('updateYoutubeMode', 'embed')">
        <CirclePlay :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" />
      </button>
    </div>

    <div
      v-if="widget.type === 'text' && !showContentLabels"
      class="pointer-events-auto absolute right-1/2 z-[3200] flex translate-x-1/2 items-center gap-1.5 rounded-2xl bg-black/80 p-1.5 text-white shadow-xl ring-1 ring-white/10 backdrop-blur-md"
      :class="mode === 'desktop' ? 'bottom-[-5rem]' : 'bottom-[-4.25rem]'"
      @click.stop
      @pointerdown.prevent.stop
      @mousedown.prevent.stop
      @touchstart.prevent.stop
    >
      <button type="button" aria-label="左寄せ" :class="toolButtonClass(textAlign === 'left')" title="左寄せ" @click.prevent.stop="emit('updateTextAlign', 'left')">
        <AlignLeft :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" />
      </button>
      <button type="button" aria-label="中央寄せ" :class="toolButtonClass(textAlign === 'center')" title="中央寄せ" @click.prevent.stop="emit('updateTextAlign', 'center')">
        <AlignCenter :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" />
      </button>
      <button type="button" aria-label="右寄せ" :class="toolButtonClass(textAlign === 'right')" title="右寄せ" @click.prevent.stop="emit('updateTextAlign', 'right')">
        <AlignRight :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" />
      </button>
      <div class="mx-1 h-7 w-px bg-gray-600"></div>
      <button type="button" aria-label="上揃え" :class="toolButtonClass(verticalAlign === 'start')" title="上揃え" @click.prevent.stop="emit('updateVerticalAlign', 'start')">
        <AlignVerticalJustifyStart :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" />
      </button>
      <button type="button" aria-label="中央揃え" :class="toolButtonClass(verticalAlign === 'center')" title="中央揃え" @click.prevent.stop="emit('updateVerticalAlign', 'center')">
        <AlignVerticalJustifyCenter :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" />
      </button>
      <button type="button" aria-label="下揃え" :class="toolButtonClass(verticalAlign === 'end')" title="下揃え" @click.prevent.stop="emit('updateVerticalAlign', 'end')">
        <AlignVerticalJustifyEnd :class="mode === 'desktop' ? 'size-4' : 'size-3.5'" />
      </button>
    </div>
  </div>
</template>
