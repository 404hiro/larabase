<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link as InertiaLink } from '@inertiajs/vue3';
import {
    Check,
    Copy,
    Image,
    Link as LinkIcon,
    MessageCircleHeart,
    Monitor,
    PartyPopper,
    Pencil,
    Save,
    Smartphone,
    Type,
} from 'lucide-vue-next';
import { HSStaticMethods } from 'preline';
import { computed, onMounted, ref, watch } from 'vue';
import { compressImage } from '@/utils/imageCompression';

const props = defineProps<{
    isEditing: boolean;
    previewMode: 'desktop' | 'mobile';
    letterUrl: string;
    isPublished?: boolean;
    isShareCopied?: boolean;
    hasWidgets?: boolean;
    mobileWidgetOperationActive?: boolean;
    mobileSizeOptions?: any[];
    activeMobileWidget?: any | null;
}>();

const emit = defineEmits<{
    toggleEdit: [];
    'update:previewMode': [mode: 'desktop' | 'mobile'];
    addLink: [];
    addMedia: [file: File];
    addSection: [];
    addText: [];
    publish: [];
    share: [];
    resizeMobileWidget: [size: { w: number; h: number }];
    completeMobileWidgetOperation: [];
}>();

onMounted(() => {
    HSStaticMethods.autoInit();
});

watch(
    () => props.isEditing,
    () => {
        setTimeout(() => {
            HSStaticMethods.autoInit();
        }, 100);
    },
);

const mediaInput = ref<HTMLInputElement | null>(null);

const chooseMedia = () => {
    mediaInput.value?.click();
};

const handleMediaChange = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    if (file) {
        const compressedFile = await compressImage(file, { preset: 'card' });
        if (!compressedFile) return;

        emit('addMedia', compressedFile);
    }

    input.value = '';
};

const isActiveMobileSize = (size: { w: number; h: number }) => {
    const widget = props.activeMobileWidget;

    return (
        widget &&
        Number(widget.w_mobile) === size.w &&
        Number(widget.h_mobile) === size.h
    );
};

const mobileSizeIconClass = (option: any) => {
    const isActive = isActiveMobileSize(option.size);

    return [
        'block border-2 transition-colors',
        option.key === 'small'
            ? 'size-3.5 rounded-[4px]'
            : option.key === 'wide'
                ? 'h-3 w-5 rounded-[5px]'
                : option.key === 'tall'
                    ? 'h-5 w-3 rounded-[5px]'
                    : option.key === 'large'
                        ? 'size-[18px] rounded-[4px]'
                        : 'size-4 rounded-[4px]',
        isActive ? 'border-gray-950' : 'border-white/70',
    ];
};
</script>

<template>
    <div class="fixed bottom-6 left-1/2 z-[1000] flex -translate-x-1/2 items-center justify-center">
        <!-- Desktop Toolbar -->
        <div
            class="hidden h-11 items-center gap-2 rounded-2xl border border-gray-200 bg-white/90 p-1.5 shadow-[0_8px_30px_rgb(0,0,0,0.08)] backdrop-blur-md min-[1025px]:flex">
            <Button v-if="!isEditing && !isPublished && hasWidgets" variant="ghost"
                class="h-8 gap-2 rounded-xl bg-black px-4 text-sm text-white hover:bg-neutral-800 hover:text-white cursor-pointer"
                @click="emit('publish')">
                <PartyPopper class="size-4" />
                <span class="font-semibold">公開</span>
            </Button>

            <div v-if="!isEditing && isPublished" class="relative">
                <div v-if="isShareCopied"
                    class="absolute bottom-full left-1/2 mb-2 -translate-x-1/2 whitespace-nowrap rounded-lg bg-black px-3 py-1.5 text-xs font-bold text-white shadow-lg">
                    URLをコピーしました
                </div>
                <Button variant="ghost"
                    class="h-8 gap-2 rounded-xl bg-black px-4 text-sm text-white cursor-pointer hover:bg-neutral-800 hover:text-white"
                    @click="emit('share')">
                    <Check v-if="isShareCopied" class="size-4 text-white" />
                    <Copy v-else class="size-4" />
                    <span class="font-semibold">シェア</span>
                </Button>
            </div>

            <Button :variant="isEditing ? 'default' : 'ghost'" class="h-8 gap-2 rounded-xl px-4 cursor-pointer" :class="isEditing
                    ? 'bg-black text-white hover:bg-neutral-800'
                    : 'text-gray-900 hover:bg-gray-100'
                " @click="emit('toggleEdit')">
                <Save v-if="isEditing" class="size-4" />
                <Pencil v-else class="size-4" />
                <span class="font-semibold">{{
                    isEditing ? '保存' : '編集'
                    }}</span>
            </Button>

            <div class="mx-1 h-6 w-px bg-gray-200"></div>

            <div class="flex h-full items-center rounded-xl bg-gray-100/80 p-1">
                <button @click="emit('update:previewMode', 'desktop')"
                    class="flex size-8 cursor-pointer items-center justify-center rounded-lg transition-colors" :class="previewMode === 'desktop'
                            ? 'bg-[#292929] text-white shadow-sm'
                            : 'text-gray-500 hover:text-gray-900'
                        ">
                    <Monitor class="size-5" />
                </button>
                <button @click="emit('update:previewMode', 'mobile')"
                    class="flex size-8 cursor-pointer items-center justify-center rounded-lg transition-colors" :class="previewMode === 'mobile'
                            ? 'bg-[#292929] text-white shadow-sm'
                            : 'text-gray-500 hover:text-gray-900'
                        ">
                    <Smartphone class="size-[18px]" />
                </button>
            </div>

            <template v-if="isEditing">
                <div class="mx-1 h-6 w-px bg-gray-200"></div>
                <Button variant="ghost" class="h-8 gap-2 rounded-xl px-4 text-sm text-gray-900 cursor-pointer"
                    @click="emit('addLink')">
                    <LinkIcon class="size-4" />
                    <span class="font-semibold">リンク</span>
                </Button>
                <Button variant="ghost" class="h-8 gap-2 rounded-xl px-4 text-sm text-gray-900 cursor-pointer"
                    @click="chooseMedia">
                    <Image class="size-4" />
                    <span class="font-semibold">メディア</span>
                </Button>
                <Button variant="ghost" class="h-8 gap-2 rounded-xl px-4 text-sm text-gray-900 cursor-pointer"
                    @click="emit('addText')">
                    <Type class="size-4" />
                    <span class="font-semibold">テキスト</span>
                </Button>
                <Button variant="ghost" class="h-8 gap-2 rounded-xl px-4 text-sm text-gray-900 cursor-pointer"
                    @click="emit('addSection')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                        class="size-4">
                        <path
                            d="M19 4.22107C19 3.66878 18.5523 3.22107 18 3.22107H6C5.44772 3.22107 5 3.66878 5 4.22107C5 4.77335 5.44772 5.22107 6 5.22107H18C18.5523 5.22107 19 4.77335 19 4.22107ZM21 4.22107C21 5.87792 19.6569 7.22107 18 7.22107H6C4.34315 7.22107 3 5.87792 3 4.22107C3 2.56422 4.34315 1.22107 6 1.22107H18C19.6569 1.22107 21 2.56422 21 4.22107Z"
                            fill="currentColor"></path>
                        <path
                            d="M19 13.2211C19 12.1165 18.1046 11.2211 17 11.2211H7C5.89543 11.2211 5 12.1165 5 13.2211V18.7787C5.0002 19.8831 5.89555 20.7787 7 20.7787H17C18.1044 20.7787 18.9998 19.8831 19 18.7787V13.2211ZM21 18.7787C20.9998 20.9877 19.209 22.7787 17 22.7787H7C4.79098 22.7787 3.0002 20.9877 3 18.7787V13.2211C3 11.012 4.79086 9.22112 7 9.22112H17C19.2091 9.22112 21 11.012 21 13.2211V18.7787Z"
                            fill="currentColor"></path>
                    </svg>
                    <span class="font-semibold">セクション</span>
                </Button>
                <Button as-child variant="ghost" class="h-8 gap-2 rounded-xl px-4 text-sm text-gray-900 cursor-pointer">
                    <InertiaLink :href="letterUrl">
                        <MessageCircleHeart class="size-4" />
                        <span class="font-semibold">ファンレター</span>
                    </InertiaLink>
                </Button>
            </template>
        </div>

        <!-- Mobile Toolbar -->
        <div v-if="!mobileWidgetOperationActive"
            class="hidden h-11 items-center rounded-2xl border border-gray-200 bg-white px-2 text-slate-500 shadow-[0_12px_30px_rgb(15,23,42,0.14)] max-[1024px]:flex min-[1025px]:hidden">
            <button v-if="!isEditing && !isPublished && hasWidgets" type="button" aria-label="公開" title="公開"
                class="mr-1 flex h-8 cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-black px-3 text-sm font-bold text-white shadow-sm transition-colors hover:bg-neutral-800"
                @click="emit('publish')">
                <PartyPopper class="size-4" stroke-width="2.2" />
                <span>公開</span>
            </button>

            <div v-if="!isEditing && isPublished" class="relative mr-1">
                <div v-if="isShareCopied"
                    class="absolute bottom-full left-1/2 mb-2 -translate-x-1/2 whitespace-nowrap rounded-lg bg-black px-3 py-1.5 text-xs font-bold text-white shadow-lg">
                    URLをコピーしました
                </div>
                <button type="button" aria-label="シェア" title="シェア"
                    class="flex h-8 cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-black px-3 text-sm font-bold text-white transition-colors hover:bg-neutral-800"
                    @click="emit('share')">
                    <Check v-if="isShareCopied" class="size-4 text-white" stroke-width="2.2" />
                    <Copy v-else class="size-4" stroke-width="2.2" />
                    <span>シェア</span>
                </button>
            </div>

            <button @click="emit('toggleEdit')"
                class="flex h-8 cursor-pointer items-center justify-center rounded-xl transition-colors" :class="isEditing
                        ? 'min-w-16 gap-1.5 bg-black px-3 text-sm font-bold text-white shadow-sm hover:bg-neutral-800'
                        : 'gap-1.5 px-3 text-sm font-bold hover:bg-slate-100 hover:text-slate-800'
                    " :aria-label="isEditing ? '保存' : '編集'" :title="isEditing ? '保存' : '編集'">
                <Pencil v-if="!isEditing" class="size-4" stroke-width="2.2" />
                <span v-if="!isEditing">編集</span>
                <span v-if="isEditing">保存</span>
            </button>

            <template v-if="isEditing">
                <div class="mx-3 h-7 w-px bg-gray-200"></div>
                <div class="hs-tooltip inline-block">
                    <button aria-label="リンクを追加" @click="emit('addLink')"
                        class="flex size-8 cursor-pointer items-center justify-center rounded-xl transition-colors hover:bg-slate-100 hover:text-slate-800 hs-tooltip-toggle">
                        <LinkIcon class="size-5" stroke-width="2.2" />
                    </button>
                    <span
                        class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-white text-xs rounded-md whitespace-nowrap"
                        role="tooltip">
                        リンクを追加
                    </span>
                </div>
                <div class="hs-tooltip inline-block">
                    <button aria-label="メディアを追加" @click="chooseMedia"
                        class="ml-2 flex size-8 cursor-pointer items-center justify-center rounded-xl transition-colors hover:bg-slate-100 hover:text-slate-800 hs-tooltip-toggle">
                        <Image class="size-5" stroke-width="2.2" />
                    </button>
                    <span
                        class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-white text-xs rounded-md whitespace-nowrap"
                        role="tooltip">
                        メディアを追加
                    </span>
                </div>
                <div class="hs-tooltip inline-block">
                    <button aria-label="テキストを追加" @click="emit('addText')"
                        class="ml-2 flex size-8 cursor-pointer items-center justify-center rounded-xl transition-colors hover:bg-slate-100 hover:text-slate-800 hs-tooltip-toggle">
                        <Type class="size-5" stroke-width="2.2" />
                    </button>
                    <span
                        class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-white text-xs rounded-md whitespace-nowrap"
                        role="tooltip">
                        テキストを追加
                    </span>
                </div>
                <div class="hs-tooltip inline-block">
                    <button aria-label="セクションを追加" @click="emit('addSection')"
                        class="ml-2 flex size-8 cursor-pointer items-center justify-center rounded-xl transition-colors hover:bg-slate-100 hover:text-slate-800 hs-tooltip-toggle">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                            class="size-5">
                            <path
                                d="M19 4.22107C19 3.66878 18.5523 3.22107 18 3.22107H6C5.44772 3.22107 5 3.66878 5 4.22107C5 4.77335 5.44772 5.22107 6 5.22107H18C18.5523 5.22107 19 4.77335 19 4.22107ZM21 4.22107C21 5.87792 19.6569 7.22107 18 7.22107H6C4.34315 7.22107 3 5.87792 3 4.22107C3 2.56422 4.34315 1.22107 6 1.22107H18C19.6569 1.22107 21 2.56422 21 4.22107Z"
                                fill="currentColor"></path>
                            <path
                                d="M19 13.2211C19 12.1165 18.1046 11.2211 17 11.2211H7C5.89543 11.2211 5 12.1165 5 13.2211V18.7787C5.0002 19.8831 5.89555 20.7787 7 20.7787H17C18.1044 20.7787 18.9998 19.8831 19 18.7787V13.2211ZM21 18.7787C20.9998 20.9877 19.209 22.7787 17 22.7787H7C4.79098 22.7787 3.0002 20.9877 3 18.7787V13.2211C3 11.012 4.79086 9.22112 7 9.22112H17C19.2091 9.22112 21 11.012 21 13.2211V18.7787Z"
                                fill="currentColor"></path>
                        </svg>
                    </button>
                    <span
                        class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-white text-xs rounded-md whitespace-nowrap"
                        role="tooltip">
                        セクションを追加
                    </span>
                </div>
                <div class="hs-tooltip inline-block">
                    <InertiaLink aria-label="ファンレターページを開く" :href="letterUrl"
                        class="ml-2 flex size-8 cursor-pointer items-center justify-center rounded-xl transition-colors hover:bg-slate-100 hover:text-slate-800 hs-tooltip-toggle">
                        <MessageCircleHeart class="size-5" stroke-width="2.2" />
                    </InertiaLink>
                    <span
                        class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-white text-xs rounded-md whitespace-nowrap"
                        role="tooltip">
                        ファンレターページを開く
                    </span>
                </div>
            </template>
        </div>

        <div v-else
            class="hidden h-11 items-center gap-1.5 rounded-2xl border border-gray-700 bg-[#292929] p-1.5 text-white shadow-[0_12px_30px_rgb(15,23,42,0.22)] max-[1024px]:flex min-[1025px]:hidden"
            @click.stop>
            <div v-for="option in mobileSizeOptions" :key="option.key" class="hs-tooltip inline-block">
                <button type="button" :aria-label="option.label"
                    class="flex size-8 cursor-pointer items-center justify-center rounded-lg transition-colors hs-tooltip-toggle"
                    :class="isActiveMobileSize(option.size)
                            ? 'bg-white text-gray-950 shadow-sm'
                            : 'text-white/70 hover:bg-white/10 hover:text-white'
                        " @click.stop="emit('resizeMobileWidget', option.size)">
                    <span v-if="activeMobileWidget?.type !== 'section' && option.key !== 'inline'"
                        :class="mobileSizeIconClass(option)"></span>
                    <component v-else :is="option.icon" class="size-3.5" />
                    <span
                        class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-white text-xs rounded-md whitespace-nowrap"
                        role="tooltip">
                        {{ option.label }}
                    </span>
                </button>
            </div>

            <div class="mx-1 h-7 w-px bg-white/20"></div>

            <button type="button"
                class="flex h-8 min-w-12 cursor-pointer items-center justify-center whitespace-nowrap rounded-lg bg-white px-4 text-xs font-bold text-black transition-transform active:scale-95"
                @click.stop="emit('completeMobileWidgetOperation')">
                完了
            </button>
        </div>

        <input ref="mediaInput" type="file" accept="image/*,.apng" class="hidden" @change="handleMediaChange" />
    </div>
</template>
