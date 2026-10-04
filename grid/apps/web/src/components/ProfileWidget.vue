<script setup lang="ts">
import { apiUrl, type LinkWidget } from '@/lib/api';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';
import 'leaflet/dist/leaflet.css';
import { Image as ImageIcon, Link as LinkIcon, MapPin, MoveUpRight, Trash2 } from 'lucide-vue-next';
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';

const props = defineProps<{
  widget: LinkWidget;
  mode: 'desktop' | 'mobile';
  pageTheme: 'light' | 'dark';
  cornerClass: string;
  isEditing?: boolean;
  hideImageLinkIcon?: boolean;
  isCropping?: boolean;
  isMapMoving?: boolean;
}>();

const emit = defineEmits<{
  updateTitle: [title: string];
  updateCrop: [crop: { x: number; y: number }];
  updateMapCenter: [center: { lat: number; lng: number; zoom: number }];
  uploadImage: [file: File];
  removeImage: [];
}>();

const settings = computed(() => props.widget.settings || {});
const title = computed(() => {
  if (props.widget.type === 'section') {
    return props.widget.content || settings.value.title || '';
  }
  return settings.value.title || '';
});
const href = computed(() => props.widget.content || '');
const image = computed(() => (props.widget.thumbnail_url ? apiUrl(props.widget.thumbnail_url) : ''));
const domain = computed(() => {
  if (!href.value) return '';
  try {
    return new URL(href.value).hostname.replace(/^www\./, '');
  } catch {
    return '';
  }
});
const faviconUrl = computed(() => {
  if (settings.value.favicon_url) return settings.value.favicon_url;
  if (!domain.value) return '';
  return `https://www.google.com/s2/favicons?domain=${domain.value}&sz=128`;
});
const shape = computed(() => {
  const w = props.mode === 'desktop' ? props.widget.w : props.widget.w_mobile;
  const h = props.mode === 'desktop' ? props.widget.h : props.widget.h_mobile;
  if (w === 2 && h === 1) return 'inline';
  if (w === 1 && h === 2) return '1x1';
  if (w === 2 && h === 2) return '2x1';
  if (w === 1 && h === 4) return '1x2';
  if (w === 2 && h === 4) return '2x2';
  return '1x1';
});
const normalizedBgColor = computed(() => {
  const value = String(settings.value.bgColor || '#ffffff').trim();
  if (/^#[0-9a-f]{6}$/i.test(value)) return value;
  if (/^#[0-9a-f]{3}$/i.test(value)) {
    return `#${value
      .slice(1)
      .split('')
      .map((part) => part + part)
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
const textAlignClass = computed(() =>
  settings.value.textAlign === 'center'
    ? 'text-center'
    : settings.value.textAlign === 'right'
      ? 'text-right'
      : 'text-left',
);
const verticalAlignClass = computed(() =>
  settings.value.verticalAlign === 'start'
    ? 'justify-start'
    : settings.value.verticalAlign === 'end'
      ? 'justify-end'
      : 'justify-center',
);
const mapLat = computed(() => Number(settings.value.lat ?? 35.6585805));
const mapLng = computed(() => Number(settings.value.lng ?? 139.7454329));
const mapZoom = computed(() => Number(settings.value.zoom ?? 15));
const mapTitle = computed(() => {
  const rawTitle = settings.value.title;
  return rawTitle === undefined || rawTitle === null ? '東京タワー' : String(rawTitle);
});
const mapAriaLabel = computed(() => mapTitle.value.trim() || settings.value.address || 'マップ');
const linkTitle = computed(() => title.value || domain.value);
const youtubeVideoId = computed(() => {
  if (props.widget.type !== 'link' || !href.value) return null;
  try {
    const url = new URL(href.value);
    const host = url.hostname.replace(/^www\./, '');
    const pathParts = url.pathname.split('/').filter(Boolean);
    if (host === 'youtu.be') return pathParts[0] || null;
    if (host === 'youtube.com' || host.endsWith('.youtube.com')) {
      if (pathParts[0] === 'watch') return url.searchParams.get('v');
      if (['embed', 'shorts', 'live'].includes(pathParts[0] ?? '')) return pathParts[1] || null;
    }
    return null;
  } catch {
    return null;
  }
});
const vimeoVideoId = computed(() => {
  if (props.widget.type !== 'link' || !href.value) return null;
  try {
    const url = new URL(href.value);
    const host = url.hostname.replace(/^www\./, '');
    const pathParts = url.pathname.split('/').filter(Boolean);
    return host === 'vimeo.com' && pathParts[0] ? pathParts[0] : null;
  } catch {
    return null;
  }
});
const tiktokVideoId = computed(() => {
  if (props.widget.type !== 'link' || !href.value) return null;
  try {
    const url = new URL(href.value);
    const host = url.hostname.replace(/^www\./, '');
    const pathParts = url.pathname.split('/').filter(Boolean);
    return host === 'tiktok.com' && pathParts[1] === 'video' && pathParts[2] ? pathParts[2] : null;
  } catch {
    return null;
  }
});
const embedInfo = computed(() => {
  if (youtubeVideoId.value) return { service: 'youtube', url: `https://www.youtube.com/embed/${youtubeVideoId.value}` };
  if (vimeoVideoId.value) return { service: 'vimeo', url: `https://player.vimeo.com/video/${vimeoVideoId.value}` };
  if (tiktokVideoId.value) return { service: 'tiktok', url: `https://www.tiktok.com/embed/v2/${tiktokVideoId.value}` };
  return null;
});
const embedMode = computed(() => {
  if (!embedInfo.value || shape.value === 'inline') return 'link';
  return settings.value.youtubeMode || 'link';
});
const faviconFailed = ref(false);
const handleFaviconError = () => {
  faviconFailed.value = true;
};
type LinkService = {
  name: string;
  account?: string;
  color: string;
  backgroundColor: string;
  actionLabel: string;
  isAction?: boolean;
};
const linkService = computed<LinkService | null>(() => {
  if (!href.value) return null;
  try {
    const url = new URL(href.value);
    const host = url.hostname.replace(/^www\./, '');
    const pathParts = url.pathname.split('/').filter(Boolean);
    const rawAccount = pathParts.length > 0 ? pathParts[0] : '';
    const account = rawAccount ? `@${rawAccount.replace(/^@/, '')}` : '';
    const isHost = (domainName: string) => host === domainName || host.endsWith(`.${domainName}`);

    if (host === 'apps.apple.com' || (host === 'itunes.apple.com' && pathParts.includes('app'))) {
      return { name: 'App Store', color: 'bg-[#0A84FF] text-white hover:bg-[#006EDB]', backgroundColor: 'bg-[#eff6ff]', actionLabel: 'インストール', isAction: true };
    }
    if (host === 'play.google.com' && pathParts.includes('apps')) {
      return { name: 'Play Store', color: 'bg-[#01875F] text-white hover:bg-[#006B4B]', backgroundColor: 'bg-[#effaf5]', actionLabel: 'インストール', isAction: true };
    }
    if (isHost('amazon.com') || isHost('amazon.co.jp')) {
      return { name: 'Amazon', color: 'bg-[#FF9900] text-gray-950 hover:bg-[#E68A00]', backgroundColor: 'bg-[#fff8ed]', actionLabel: '購入', isAction: true };
    }
    if (isHost('rakuten.co.jp')) {
      return { name: 'Rakuten', color: 'bg-[#BF0000] text-white hover:bg-[#990000]', backgroundColor: 'bg-[#fff1f1]', actionLabel: '購入', isAction: true };
    }
    if (isHost('shopify.com') || isHost('myshopify.com')) {
      return { name: 'Shopify', color: 'bg-[#7AB55C] text-white hover:bg-[#659A4C]', backgroundColor: 'bg-[#f3faed]', actionLabel: '購入', isAction: true };
    }
    if (isHost('buymeacoffee.com')) {
      return { name: 'Buy Me a Coffee', color: 'bg-[#FFDD00] text-gray-950 hover:bg-[#E6C700]', backgroundColor: 'bg-[#fffbea]', actionLabel: 'サポート', isAction: true };
    }
    if (isHost('ko-fi.com')) {
      return { name: 'Ko-fi', color: 'bg-[#29ABE0] text-white hover:bg-[#168FBD]', backgroundColor: 'bg-[#effaff]', actionLabel: 'サポート', isAction: true };
    }
    if (isHost('patreon.com')) {
      return { name: 'Patreon', color: 'bg-[#FF424D] text-white hover:bg-[#E23640]', backgroundColor: 'bg-[#fff1f2]', actionLabel: 'サポート', isAction: true };
    }
    if (isHost('fantia.jp')) {
      return { name: 'Fantia', color: 'bg-[#FF7A00] text-white hover:bg-[#D96500]', backgroundColor: 'bg-[#fff6ed]', actionLabel: 'フォロー', isAction: true };
    }
    if (isHost('myfans.jp')) {
      return { name: 'Myfans', color: 'bg-[#FF5C8A] text-white hover:bg-[#E34773]', backgroundColor: 'bg-[#fff1f6]', actionLabel: 'フォロー', isAction: true };
    }
    if (isHost('onlyfans.com')) {
      return { name: 'OnlyFans', color: 'bg-[#00AFF0] text-white hover:bg-[#0096CE]', backgroundColor: 'bg-[#effaff]', actionLabel: 'フォロー', isAction: true };
    }
    if (isHost('fanbox.cc')) {
      return { name: 'FANBOX', color: 'bg-[#00A1E9] text-white hover:bg-[#0086C2]', backgroundColor: 'bg-[#effaff]', actionLabel: 'フォロー', isAction: true };
    }
    if (isHost('youtube.com') || host === 'youtu.be') {
      return { name: 'YouTube', account, color: 'bg-[#FF0000] text-white hover:bg-[#d90000]', backgroundColor: 'bg-[#fff1f1]', actionLabel: youtubeVideoId.value ? 'プレイ' : 'フォロー', isAction: true };
    }
    if (isHost('instagram.com')) {
      return { name: 'Instagram', account, color: 'bg-[#E4405F] text-white hover:bg-[#c13584]', backgroundColor: 'bg-[#fff1f6]', actionLabel: 'フォロー' };
    }
    if (isHost('x.com') || isHost('twitter.com')) {
      return { name: 'X', account, color: 'bg-black text-white hover:bg-neutral-800', backgroundColor: 'bg-neutral-950 text-white', actionLabel: 'フォロー' };
    }
    if (isHost('tiktok.com')) {
      return { name: 'TikTok', account, color: 'bg-black text-white hover:bg-neutral-800', backgroundColor: 'bg-[#fff1f2]', actionLabel: tiktokVideoId.value ? 'プレイ' : 'フォロー', isAction: Boolean(tiktokVideoId.value) };
    }
    if (isHost('spotify.com') || isHost('music.apple.com') || isHost('soundcloud.com')) {
      return { name: 'Music', color: 'bg-[#1DB954] text-white hover:bg-[#169c46]', backgroundColor: 'bg-[#effaf5]', actionLabel: 'プレイ', isAction: true };
    }
    return null;
  } catch {
    return null;
  }
});
const serviceClass = computed(() => {
  return linkService.value?.backgroundColor ?? 'bg-white';
});
const actionLabel = computed(() => linkService.value?.actionLabel ?? '開く');
const actionPillClass = computed(() => linkService.value?.color ?? 'bg-black text-white');
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
  isLinkTitleFocused.value ? 'cursor-text' : 'cursor-grab active:cursor-grabbing',
]);
const linkTitleDisplayClasses = computed(() => [
  'block whitespace-pre-wrap break-words text-base leading-6 text-gray-800',
  shape.value === 'inline'
    ? 'h-6 truncate whitespace-nowrap'
    : props.mode === 'mobile'
      ? 'h-[48px] line-clamp-2'
      : 'h-[72px] line-clamp-3',
]);
const linkDomainClasses = 'whitespace-normal break-words text-base font-semibold text-gray-500';
const linkActionPillClasses = computed(() => [
  'inline-flex h-8 w-fit shrink-0 items-center justify-center rounded-full px-4 text-xs leading-none font-semibold transition-colors',
  actionPillClass.value,
]);

const textEditor = ref<HTMLElement | null>(null);
const isTextEditorFocused = ref(false);
const linkTitleEditor = ref<HTMLElement | null>(null);
const isLinkTitleFocused = ref(false);
const ogpInput = ref<HTMLInputElement | null>(null);

const updateFromTextarea = (event: Event) => {
  emit('updateTitle', (event.target as HTMLTextAreaElement).value);
};

const syncTextEditor = () => {
  if (!textEditor.value || isTextEditorFocused.value) return;
  const nextTitle = title.value || '';
  if (textEditor.value.innerText !== nextTitle) {
    textEditor.value.innerText = nextTitle;
  }
  textEditor.value.classList.toggle('is-empty', nextTitle.length === 0);
};

const updateTextEditor = () => {
  const value = (textEditor.value?.innerText || '').slice(0, 4500);
  if (textEditor.value && textEditor.value.innerText !== value) {
    textEditor.value.innerText = value;
  }
  textEditor.value?.classList.toggle('is-empty', value.length === 0);
  emit('updateTitle', value);
};

const limitPlainTextBeforeInput = (event: InputEvent, maxLength: number) => {
  if (!textEditor.value || !event.data) return;
  const currentLength = textEditor.value.innerText.length;
  const selection = window.getSelection();
  const selectedLength = selection?.toString().length ?? 0;
  if (currentLength - selectedLength + event.data.length > maxLength) {
    event.preventDefault();
  }
};

const pastePlainText = (event: ClipboardEvent, maxLength: number) => {
  event.preventDefault();
  if (!textEditor.value) return;
  const text = event.clipboardData?.getData('text/plain') ?? '';
  const currentLength = textEditor.value.innerText.length;
  const selection = window.getSelection();
  const selectedLength = selection?.toString().length ?? 0;
  const allowed = Math.max(0, maxLength - (currentLength - selectedLength));
  document.execCommand('insertText', false, text.slice(0, allowed));
};

const focusTextEditor = () => {
  if (props.isEditing) {
    textEditor.value?.focus();
  }
};

const stopPointerWhenFocused = (event: Event) => {
  if (isTextEditorFocused.value || isLinkTitleFocused.value) {
    event.stopPropagation();
  }
};

const syncLinkTitleEditor = () => {
  if (!linkTitleEditor.value || isLinkTitleFocused.value) return;
  const nextTitle = linkTitle.value || '';
  if (linkTitleEditor.value.innerText !== nextTitle) {
    linkTitleEditor.value.innerText = nextTitle;
  }
  linkTitleEditor.value.classList.toggle('is-empty', nextTitle.length === 0);
};

const updateLinkTitleEditor = () => {
  const value = (linkTitleEditor.value?.innerText || '').slice(0, 100);
  if (linkTitleEditor.value && linkTitleEditor.value.innerText !== value) {
    linkTitleEditor.value.innerText = value;
  }
  linkTitleEditor.value?.classList.toggle('is-empty', value.length === 0);
  emit('updateTitle', value);
};

const chooseOgpImage = () => {
  if (!props.isEditing) return;
  ogpInput.value?.click();
};

const handleOgpUpdate = (event: Event) => {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0] ?? null;
  input.value = '';
  if (!file) return;
  emit('uploadImage', file);
};

const imageRef = ref<HTMLImageElement | null>(null);
const mapRef = ref<HTMLElement | null>(null);
const leafletMap = ref<any | null>(null);
type LegacyCropper = {
  getContainerData: () => { width: number; height: number };
  getImageData: () => { naturalWidth: number; naturalHeight: number };
  getCanvasData: () => { left: number; top: number; width: number; height: number };
  setCropBoxData: (data: { left: number; top: number; width: number; height: number }) => void;
  setCanvasData: (data: Partial<{ left: number; top: number; width: number; height: number }>) => void;
  destroy: () => void;
};
const cropper = ref<LegacyCropper | null>(null);
const isInitializingCropper = ref(false);

const initCropper = () => {
  if (!imageRef.value || cropper.value) return;

  isInitializingCropper.value = true;
  const CropperConstructor = Cropper as unknown as new (element: HTMLImageElement, options: Record<string, unknown>) => LegacyCropper;
  cropper.value = new CropperConstructor(imageRef.value, {
    viewMode: 1,
    dragMode: 'move',
    checkCrossOrigin: false,
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

      const scale = Math.max(containerData.width / imageData.naturalWidth, containerData.height / imageData.naturalHeight);
      cropper.value.setCanvasData({
        width: imageData.naturalWidth * scale,
        height: imageData.naturalHeight * scale,
      });

      const canvasData = cropper.value.getCanvasData();
      const xRange = containerData.width - canvasData.width;
      const yRange = containerData.height - canvasData.height;
      const xPercent = Number(settings.value.cropX ?? 50);
      const yPercent = Number(settings.value.cropY ?? 50);

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
      if (!canvasData || !containerData) return;

      const xRange = containerData.width - canvasData.width;
      const yRange = containerData.height - canvasData.height;
      const x = Math.abs(xRange) < 0.1 ? 50 : (canvasData.left / xRange) * 100;
      const y = Math.abs(yRange) < 0.1 ? 50 : (canvasData.top / yRange) * 100;

      emit('updateCrop', {
        x: Math.min(100, Math.max(0, x)),
        y: Math.min(100, Math.max(0, y)),
      });
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
  if (props.widget.type !== 'map' || !mapRef.value || leafletMap.value) return;
  const L = await import('leaflet');
  leafletMap.value = L.map(mapRef.value, {
    attributionControl: false,
    zoomControl: false,
    dragging: Boolean(props.isMapMoving),
    scrollWheelZoom: false,
    doubleClickZoom: Boolean(props.isMapMoving),
    boxZoom: false,
    keyboard: false,
    touchZoom: Boolean(props.isMapMoving),
  } as any).setView([mapLat.value, mapLng.value], mapZoom.value);

  L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
    maxZoom: 20,
    subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
    attribution: '&copy; Google',
  }).addTo(leafletMap.value);

  leafletMap.value.on('moveend zoomend', () => {
    if (!leafletMap.value || !props.isMapMoving) return;
    const center = leafletMap.value.getCenter();
    emit('updateMapCenter', {
      lat: center.lat,
      lng: center.lng,
      zoom: leafletMap.value.getZoom(),
    });
  });
};

const syncMapView = () => {
  if (!leafletMap.value) return;
  leafletMap.value.setView([mapLat.value, mapLng.value], mapZoom.value, { animate: false });
  if (props.isMapMoving) {
    leafletMap.value.dragging.enable();
    leafletMap.value.doubleClickZoom.enable();
    leafletMap.value.touchZoom.enable();
  } else {
    leafletMap.value.dragging.disable();
    leafletMap.value.doubleClickZoom.disable();
    leafletMap.value.touchZoom.disable();
  }
  leafletMap.value.invalidateSize();
};

const destroyMap = () => {
  if (!leafletMap.value) return;
  leafletMap.value.remove();
  leafletMap.value = null;
};

watch(
  () => props.isCropping,
  (isCropping) => {
    if (isCropping) {
      nextTick(initCropper);
    } else {
      destroyCropper();
    }
  },
);

watch(
  () => [props.widget.type, props.isEditing, mapLat.value, mapLng.value, mapZoom.value, props.isMapMoving, props.mode],
  () => {
    if (props.widget.type === 'map') {
      nextTick(() => {
        initializeMap();
        syncMapView();
      });
    }
  },
);

watch(
  () => props.widget.thumbnail_url,
  () => {
    if (props.isCropping) {
      destroyCropper();
      nextTick(initCropper);
    }
  },
);

watch(
  () => image.value,
  () => {
    if (props.isCropping) {
      destroyCropper();
      nextTick(initCropper);
    }
  },
);

watch(
  () => props.widget.type,
  (type) => {
    if (type === 'map') {
      nextTick(initializeMap);
    } else {
      destroyMap();
    }
  },
  { immediate: true },
);

watch(
  () => [props.widget.id, title.value, props.isEditing],
  () => nextTick(syncTextEditor),
  { immediate: true },
);

watch(
  () => [props.widget.id, linkTitle.value, props.isEditing, shape.value],
  () => nextTick(syncLinkTitleEditor),
  { immediate: true },
);

onUnmounted(() => {
  destroyCropper();
  destroyMap();
});
</script>

<template>
  <div
    class="relative h-full w-full"
    :class="[
      cornerClass,
      isCropping ? 'overflow-visible' : 'overflow-hidden',
      widget.type === 'section' && !isEditing
        ? 'bg-transparent'
        : widget.type === 'section' && pageTheme === 'dark'
        ? 'border border-white/15 bg-white/10'
        : widget.type === 'section'
          ? 'border border-gray-200 bg-gray-100/70'
          : widget.type === 'image'
            ? 'border border-gray-200 bg-transparent'
          : widget.type === 'text'
              ? 'border border-gray-200'
              : ['border border-gray-200', serviceClass],
    ]"
    :style="widget.type === 'text' ? { backgroundColor: normalizedBgColor } : undefined"
  >
    <div v-if="widget.type === 'section'" class="flex h-full min-h-12 items-center" :class="isEditing ? 'px-3' : 'px-0'">
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
        @input="updateFromTextarea"
        @click.stop
        @pointerdown.stop
      ></textarea>
      <p v-else class="truncate py-2 font-bold" :class="[mode === 'desktop' ? 'text-xl' : 'text-lg', pageTheme === 'dark' ? 'text-white' : 'text-gray-800']">
        {{ title }}
      </p>
    </div>

    <div
      v-else-if="widget.type === 'text'"
      class="flex h-full w-full flex-col p-4 transition-colors duration-150"
      :class="[textColor, textAlignClass, verticalAlignClass]"
      @click="focusTextEditor"
    >
      <div
        v-if="isEditing"
        class="flex min-h-0 flex-1 flex-col rounded-xl p-3 transition-colors duration-150"
        :class="[
          verticalAlignClass,
          pageTheme === 'dark' ? 'hover:bg-white/10 focus-within:bg-white/10' : 'hover:bg-gray-100 focus-within:bg-white',
        ]"
        @click.stop="focusTextEditor"
      >
        <div
          ref="textEditor"
          contenteditable="true"
          role="textbox"
          aria-multiline="true"
          data-placeholder="テキストを入力..."
          class="text-widget-editor max-h-full w-full overflow-auto border border-transparent bg-transparent text-lg leading-snug font-semibold break-words whitespace-pre-wrap transition-colors duration-150 outline-none"
          :class="[textColor, textAlignClass]"
          @beforeinput="limitPlainTextBeforeInput($event as InputEvent, 4500)"
          @input="updateTextEditor"
          @paste="
            pastePlainText($event, 4500);
            updateTextEditor();
          "
          @focus="isTextEditorFocused = true"
          @blur="
            isTextEditorFocused = false;
            syncTextEditor();
          "
          @click.stop
          @pointerdown="stopPointerWhenFocused"
          @mousedown="stopPointerWhenFocused"
          @touchstart="stopPointerWhenFocused"
        ></div>
      </div>
      <p v-else-if="title" class="p-3 text-lg leading-snug font-semibold" :class="shape === 'inline' ? 'truncate' : 'break-words whitespace-pre-wrap'">
        {{ title }}
      </p>
    </div>

    <div
      v-else-if="widget.type === 'image'"
      class="relative h-full w-full"
      :class="[cornerClass, isCropping ? 'is-cropping-active overflow-visible' : 'overflow-hidden']"
    >
      <img
        v-if="image"
        ref="imageRef"
        :src="image"
        :alt="title"
        crossorigin="anonymous"
        class="relative z-10 h-full w-full object-cover transition-[filter,transform] duration-150"
        :class="cornerClass"
        :style="!isCropping ? { objectPosition: `${Number(settings.cropX ?? 50)}% ${Number(settings.cropY ?? 50)}%` } : undefined"
        draggable="false"
      />
      <div v-else class="flex h-full w-full items-center justify-center bg-gray-100 text-gray-300">
        <ImageIcon class="size-10" />
      </div>
      <div
        v-if="href && !isCropping && !isEditing && !hideImageLinkIcon"
        class="pointer-events-none absolute top-2 right-2 z-20 flex size-6 items-center justify-center rounded-full bg-gray-700/75 text-white shadow-sm backdrop-blur-sm"
      >
        <MoveUpRight class="size-3.5" />
      </div>
      <div v-if="isCropping" class="pointer-events-none absolute inset-0 z-20" :class="cornerClass"></div>
      <div v-if="isEditing || title" class="absolute inset-x-0 bottom-0 z-30 p-3">
        <textarea
          v-if="isEditing"
          :value="title"
          placeholder="Add a title..."
          rows="1"
          maxlength="4500"
          class="max-w-full resize-none rounded-xl border border-white/60 bg-white/55 px-3 py-2 font-semibold break-words whitespace-pre-wrap text-gray-800 shadow-sm backdrop-blur-md placeholder:text-gray-800 focus:ring-2 focus:ring-white/80 focus:outline-none"
          :class="mode === 'desktop' ? 'text-sm' : 'text-xs'"
          @input="updateFromTextarea"
          @click.stop
          @pointerdown.stop
        ></textarea>
        <span
          v-else
          class="inline-block w-fit max-w-full rounded-xl border border-white/60 bg-white/55 px-3 py-2 font-semibold break-words whitespace-normal text-gray-800 shadow-sm backdrop-blur-md"
          :class="mode === 'desktop' ? 'text-sm' : 'text-xs'"
        >
          {{ title }}
        </span>
      </div>
    </div>

    <div
      v-else-if="widget.type === 'map'"
      class="grid-link-google-map relative h-full w-full overflow-hidden bg-[#f8f6f0]"
      :class="cornerClass"
      :aria-label="mapAriaLabel"
    >
      <div ref="mapRef" class="h-full w-full"></div>
      <div class="grid-link-map-center-marker pointer-events-none absolute top-1/2 left-1/2 z-[402] -translate-x-1/2 -translate-y-1/2" aria-hidden="true">
        <span class="grid-link-map-marker__pulse"></span>
        <span class="grid-link-map-marker__dot"></span>
      </div>
      <div
        v-if="isEditing"
        class="absolute inset-x-0 bottom-0 z-[403] p-3 transition-opacity duration-150 focus-within:opacity-100"
        :class="mapTitle ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'"
      >
        <textarea
          :value="mapTitle"
          placeholder="Add a title..."
          rows="1"
          maxlength="4500"
          class="widget-text-input max-w-full cursor-text resize-none rounded-xl border border-white/60 bg-white/55 px-3 py-2 font-semibold break-words whitespace-pre-wrap text-gray-800 shadow-sm backdrop-blur-md placeholder:text-gray-800 focus:ring-2 focus:ring-white/80 focus:outline-none"
          :class="mode === 'desktop' ? 'text-sm' : 'text-xs'"
          @keydown.enter.prevent
          @input="updateFromTextarea"
          @click.stop
          @pointerdown.stop
          @mousedown.stop
          @touchstart.stop
        ></textarea>
      </div>
      <div v-else-if="mapTitle" class="absolute inset-x-0 bottom-0 z-[403] p-3">
        <span
          class="inline-block w-fit max-w-full rounded-xl border border-white/60 bg-white/55 px-3 py-2 font-semibold break-words whitespace-normal text-gray-800 shadow-sm backdrop-blur-md"
          :class="mode === 'desktop' ? 'text-sm' : 'text-xs'"
        >
          {{ mapTitle }}
        </span>
      </div>
    </div>

    <div v-else class="h-full w-full overflow-hidden">
      <div v-if="embedInfo && embedMode === 'embed'" class="relative h-full w-full overflow-hidden bg-black">
        <iframe
          :src="embedInfo.url"
          class="h-full w-full border-0"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
          allowfullscreen
          loading="lazy"
          title="embedded content"
        ></iframe>
      </div>

      <div v-else-if="shape === 'inline'" class="flex h-full items-center px-5">
        <img v-if="faviconUrl && !faviconFailed" :src="faviconUrl" alt="" class="mr-3 size-8 rounded-lg" draggable="false" @error="handleFaviconError" />
        <LinkIcon v-else class="mr-3 size-8 rounded-lg text-gray-400" />
        <div
          v-if="isEditing"
          ref="linkTitleEditor"
          contenteditable="true"
          role="textbox"
          aria-label="リンクタイトル"
          data-placeholder="タイトルを入力"
          class="link-title-editor font-bold"
          :class="linkTitleEditorClasses"
          @beforeinput="limitPlainTextBeforeInput($event as InputEvent, 100)"
          @keydown.enter.prevent
          @input="updateLinkTitleEditor"
          @paste="
            pastePlainText($event, 100);
            updateLinkTitleEditor();
          "
          @focus="isLinkTitleFocused = true"
          @blur="
            isLinkTitleFocused = false;
            syncLinkTitleEditor();
          "
          @click.stop
          @pointerdown="stopPointerWhenFocused"
          @mousedown="stopPointerWhenFocused"
          @touchstart="stopPointerWhenFocused"
        ></div>
        <p v-else class="flex-1 truncate text-lg font-bold text-gray-800">
          {{ linkTitle }}
        </p>
      </div>

      <div v-else-if="shape === '1x1'" class="flex h-full flex-col p-5">
        <img v-if="faviconUrl && !faviconFailed" :src="faviconUrl" alt="" class="mb-3 size-8 rounded-lg" draggable="false" @error="handleFaviconError" />
        <LinkIcon v-else class="mb-3 size-8 rounded-lg text-gray-400" />
        <div
          v-if="isEditing"
          ref="linkTitleEditor"
          contenteditable="true"
          role="textbox"
          aria-label="リンクタイトル"
          data-placeholder="タイトルを入力"
          class="link-title-editor font-bold"
          :class="linkTitleEditorClasses"
          @beforeinput="limitPlainTextBeforeInput($event as InputEvent, 100)"
          @keydown.enter.prevent
          @input="updateLinkTitleEditor"
          @paste="
            pastePlainText($event, 100);
            updateLinkTitleEditor();
          "
          @focus="isLinkTitleFocused = true"
          @blur="
            isLinkTitleFocused = false;
            syncLinkTitleEditor();
          "
          @click.stop
          @pointerdown="stopPointerWhenFocused"
          @mousedown="stopPointerWhenFocused"
          @touchstart="stopPointerWhenFocused"
        ></div>
        <p v-else :class="linkTitleDisplayClasses">
          {{ linkTitle }}
        </p>
        <div class="flex-1"></div>
        <span v-if="linkService" :class="linkActionPillClasses">
          {{ actionLabel }}
        </span>
        <p v-else :class="linkDomainClasses">{{ domain }}</p>
      </div>

      <div v-else-if="shape === '2x1'" class="flex h-full gap-4 p-4">
        <div class="flex min-w-0 flex-1 flex-col py-1 pl-1">
          <div class="mb-3 flex items-center gap-2">
            <img v-if="faviconUrl && !faviconFailed" :src="faviconUrl" alt="" class="size-8 rounded-lg" draggable="false" @error="handleFaviconError" />
            <LinkIcon v-else class="size-8 rounded-lg text-gray-400" />
            <span v-if="linkService?.account" class="truncate text-sm font-semibold text-gray-500">{{ linkService.account }}</span>
          </div>
          <div
            v-if="isEditing"
            ref="linkTitleEditor"
            contenteditable="true"
            role="textbox"
            aria-label="リンクタイトル"
            data-placeholder="タイトルを入力"
            class="link-title-editor font-bold"
            :class="linkTitleEditorClasses"
            @beforeinput="limitPlainTextBeforeInput($event as InputEvent, 100)"
            @keydown.enter.prevent
            @input="updateLinkTitleEditor"
            @paste="
              pastePlainText($event, 100);
              updateLinkTitleEditor();
            "
            @focus="isLinkTitleFocused = true"
            @blur="
              isLinkTitleFocused = false;
              syncLinkTitleEditor();
            "
            @click.stop
            @pointerdown="stopPointerWhenFocused"
            @mousedown="stopPointerWhenFocused"
            @touchstart="stopPointerWhenFocused"
          ></div>
          <p v-else :class="linkTitleDisplayClasses">
            {{ linkTitle }}
          </p>
          <div class="flex-1"></div>
          <span v-if="linkService" :class="linkActionPillClasses">
            {{ actionLabel }}
          </span>
          <p v-else :class="linkDomainClasses">{{ domain }}</p>
        </div>
        <div v-if="embedInfo && embedMode === 'link_embed'" class="relative h-full min-w-0 flex-1 overflow-hidden rounded-xl bg-gray-100">
          <iframe
            :src="embedInfo.url"
            class="h-full w-full border-0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowfullscreen
            loading="lazy"
            title="embedded content"
          ></iframe>
        </div>
        <button
          v-else-if="isEditing"
          type="button"
          class="group relative h-full min-w-0 flex-1 cursor-pointer overflow-hidden rounded-xl bg-gray-100"
          @click.stop="chooseOgpImage"
        >
          <img v-if="image" :src="image" :alt="linkTitle" class="h-full w-full object-cover" draggable="false" />
          <span class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/0 text-white opacity-0 transition-all duration-150 group-hover:bg-black/20 group-hover:opacity-100">
            <ImageIcon class="size-8" />
          </span>
          <button
            v-if="image"
            type="button"
            class="absolute top-2 right-2 flex size-8 cursor-pointer items-center justify-center rounded-xl bg-red-600 text-white opacity-0 transition-opacity duration-150 group-hover:opacity-100 hover:bg-red-700"
            @click.stop="emit('removeImage')"
          >
            <Trash2 class="size-4" />
          </button>
        </button>
        <div v-else-if="image" class="relative h-full min-w-0 flex-1 overflow-hidden rounded-xl bg-gray-100">
          <img :src="image" :alt="linkTitle" class="h-full w-full object-cover" draggable="false" />
        </div>
      </div>

      <div v-else-if="shape === '1x2'" class="flex h-full flex-col gap-4 p-4">
        <div class="min-h-0">
          <div class="mb-3 flex items-center gap-2">
            <img v-if="faviconUrl && !faviconFailed" :src="faviconUrl" alt="" class="size-8 rounded-lg" draggable="false" @error="handleFaviconError" />
            <LinkIcon v-else class="size-8 rounded-lg text-gray-400" />
            <span v-if="linkService?.account" class="truncate text-sm font-semibold text-gray-500">{{ linkService.account }}</span>
          </div>
          <div
            v-if="isEditing"
            ref="linkTitleEditor"
            contenteditable="true"
            role="textbox"
            aria-label="リンクタイトル"
            data-placeholder="タイトルを入力"
            class="link-title-editor font-bold"
            :class="linkTitleEditorClasses"
            @beforeinput="limitPlainTextBeforeInput($event as InputEvent, 100)"
            @keydown.enter.prevent
            @input="updateLinkTitleEditor"
            @paste="
              pastePlainText($event, 100);
              updateLinkTitleEditor();
            "
            @focus="isLinkTitleFocused = true"
            @blur="
              isLinkTitleFocused = false;
              syncLinkTitleEditor();
            "
            @click.stop
            @pointerdown="stopPointerWhenFocused"
            @mousedown="stopPointerWhenFocused"
            @touchstart="stopPointerWhenFocused"
          ></div>
          <p v-else :class="linkTitleDisplayClasses">
            {{ linkTitle }}
          </p>
        </div>
        <div v-if="embedInfo && embedMode === 'link_embed'" class="relative min-h-0 flex-1 overflow-hidden rounded-xl bg-gray-100">
          <iframe
            :src="embedInfo.url"
            class="h-full w-full border-0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowfullscreen
            loading="lazy"
            title="embedded content"
          ></iframe>
        </div>
        <button
          v-else-if="isEditing"
          type="button"
          class="group relative min-h-0 flex-1 cursor-pointer overflow-hidden rounded-xl bg-gray-100"
          @click.stop="chooseOgpImage"
        >
          <img v-if="image" :src="image" :alt="linkTitle" class="h-full w-full object-cover" draggable="false" />
          <span class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/0 text-white opacity-0 transition-all duration-150 group-hover:bg-black/20 group-hover:opacity-100">
            <ImageIcon class="size-8" />
          </span>
          <button
            v-if="image"
            type="button"
            class="absolute top-2 right-2 flex size-8 cursor-pointer items-center justify-center rounded-xl bg-red-600 text-white opacity-0 transition-opacity duration-150 group-hover:opacity-100 hover:bg-red-700"
            @click.stop="emit('removeImage')"
          >
            <Trash2 class="size-4" />
          </button>
        </button>
        <div v-else-if="image" class="relative min-h-0 flex-1 overflow-hidden rounded-xl bg-gray-100">
          <img :src="image" :alt="linkTitle" class="h-full w-full object-cover" draggable="false" />
        </div>
        <span v-if="linkService" :class="linkActionPillClasses">
          {{ actionLabel }}
        </span>
        <p v-else :class="linkDomainClasses">{{ domain }}</p>
      </div>

      <div v-else class="flex h-full flex-col justify-between p-4">
        <div class="min-h-0">
          <div class="mb-3 flex items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-2">
              <img v-if="faviconUrl && !faviconFailed" :src="faviconUrl" alt="" class="size-8 rounded-lg" draggable="false" @error="handleFaviconError" />
              <LinkIcon v-else class="size-8 rounded-lg text-gray-400" />
              <span v-if="linkService?.account" class="truncate text-sm font-semibold text-gray-500">{{ linkService.account }}</span>
            </div>
            <span v-if="linkService" :class="linkActionPillClasses">
              {{ actionLabel }}
            </span>
          </div>
          <div
            v-if="isEditing"
            ref="linkTitleEditor"
            contenteditable="true"
            role="textbox"
            aria-label="リンクタイトル"
            data-placeholder="タイトルを入力"
            class="link-title-editor font-bold"
            :class="linkTitleEditorClasses"
            @beforeinput="limitPlainTextBeforeInput($event as InputEvent, 100)"
            @keydown.enter.prevent
            @input="updateLinkTitleEditor"
            @paste="
              pastePlainText($event, 100);
              updateLinkTitleEditor();
            "
            @focus="isLinkTitleFocused = true"
            @blur="
              isLinkTitleFocused = false;
              syncLinkTitleEditor();
            "
            @click.stop
            @pointerdown="stopPointerWhenFocused"
            @mousedown="stopPointerWhenFocused"
            @touchstart="stopPointerWhenFocused"
          ></div>
          <p v-else :class="linkTitleDisplayClasses">
            {{ linkTitle }}
          </p>
          <p v-if="!linkService" class="mt-2" :class="linkDomainClasses">{{ domain }}</p>
        </div>
        <div v-if="embedInfo && embedMode === 'link_embed'" class="relative mt-4 w-full shrink-0 overflow-hidden rounded-xl bg-gray-100" style="aspect-ratio: 1.91 / 1">
          <iframe
            :src="embedInfo.url"
            class="h-full w-full border-0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowfullscreen
            loading="lazy"
            title="embedded content"
          ></iframe>
        </div>
        <button
          v-else-if="isEditing"
          type="button"
          class="group relative mt-4 w-full shrink-0 cursor-pointer overflow-hidden rounded-xl bg-gray-100"
          style="aspect-ratio: 1.91 / 1"
          @click.stop="chooseOgpImage"
        >
          <img v-if="image" :src="image" :alt="linkTitle" class="h-full w-full object-cover" draggable="false" />
          <span class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/0 text-white opacity-0 transition-all duration-150 group-hover:bg-black/20 group-hover:opacity-100">
            <ImageIcon class="size-8" />
          </span>
          <button
            v-if="image"
            type="button"
            class="absolute top-2 right-2 flex size-8 cursor-pointer items-center justify-center rounded-xl bg-red-600 text-white opacity-0 transition-opacity duration-150 group-hover:opacity-100 hover:bg-red-700"
            @click.stop="emit('removeImage')"
          >
            <Trash2 class="size-4" />
          </button>
        </button>
        <div v-else-if="image" class="relative mt-4 w-full shrink-0 overflow-hidden rounded-xl bg-gray-100" style="aspect-ratio: 1.91 / 1">
          <img :src="image" :alt="linkTitle" class="h-full w-full object-cover" draggable="false" />
        </div>
      </div>
      <input ref="ogpInput" type="file" accept="image/*,.apng" class="hidden" @change="handleOgpUpdate" />
    </div>
  </div>
</template>

<style>
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
  outline: 1px solid #000 !important;
}

.is-cropping-active .cropper-view-box {
  border-radius: 1rem;
  outline: 3px solid #000 !important;
  outline-color: #000 !important;
  box-shadow: 0 0 0 1000px rgba(229, 231, 235, 0.45);
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

.text-widget-editor.is-empty::before {
  content: attr(data-placeholder);
  color: rgb(107 114 128 / 0.72);
  pointer-events: none;
}

.link-title-editor.is-empty::before {
  content: attr(data-placeholder);
  color: rgb(107 114 128 / 0.72);
  pointer-events: none;
}

.grid-link-map-center-marker {
  width: 34px;
  height: 34px;
}

.grid-link-map-marker__pulse {
  position: absolute;
  inset: 0;
  border-radius: 9999px;
  background-color: rgb(37 99 235 / 0.16);
  animation: mapMarkerPulse 1.8s ease-out infinite;
}

.grid-link-map-marker__dot {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 14px;
  height: 14px;
  border: 3px solid #fff;
  border-radius: 9999px;
  background-color: #2563eb;
  box-shadow: 0 8px 20px rgb(37 99 235 / 0.35);
  transform: translate(-50%, -50%);
}

.leaflet-control-container {
  display: none;
}

@keyframes mapMarkerPulse {
  from {
    opacity: 0.8;
    transform: scale(0.6);
  }

  to {
    opacity: 0;
    transform: scale(1.45);
  }
}
</style>
