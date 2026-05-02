<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link as InertiaLink } from '@inertiajs/vue3';
import {
    Heart,
    Image,
    Link as LinkIcon,
    Monitor,
    Pencil,
    Save,
    Smartphone,
    Type,
} from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    isEditing: boolean;
    previewMode: 'desktop' | 'mobile';
    supportUrl: string;
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
    resizeMobileWidget: [size: { w: number; h: number }];
    completeMobileWidgetOperation: [];
}>();

const mediaInput = ref<HTMLInputElement | null>(null);

const chooseMedia = () => {
    mediaInput.value?.click();
};

const handleMediaChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    if (file) {
        emit('addMedia', file);
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
    <div
        class="fixed bottom-6 left-1/2 z-[1000] flex -translate-x-1/2 items-center justify-center"
    >
        <!-- Desktop Toolbar -->
        <div
            class="hidden h-11 items-center gap-2 rounded-2xl border border-gray-200 bg-white/90 p-1.5 shadow-[0_8px_30px_rgb(0,0,0,0.08)] backdrop-blur-md min-[1025px]:flex"
        >
            <Button
                variant="ghost"
                class="h-8 gap-2 rounded-xl px-4 text-gray-900 hover:bg-gray-100 cursor-pointer"
                @click="emit('toggleEdit')"
            >
                <Save v-if="isEditing" class="size-4" />
                <Pencil v-else class="size-4" />
                <span class="font-semibold">{{
                    isEditing ? '保存' : '編集'
                }}</span>
            </Button>

            <div class="mx-1 h-6 w-px bg-gray-200"></div>

            <div class="flex h-full items-center rounded-xl bg-gray-100/80 p-1">
                <button
                    @click="emit('update:previewMode', 'desktop')"
                    class="flex size-8 items-center justify-center rounded-lg transition-colors"
                    :class="
                        previewMode === 'desktop'
                            ? 'bg-[#292929] text-white shadow-sm'
                            : 'text-gray-500 hover:text-gray-900'
                    "
                >
                    <Monitor class="size-5" />
                </button>
                <button
                    @click="emit('update:previewMode', 'mobile')"
                    class="flex size-8 items-center justify-center rounded-lg transition-colors"
                    :class="
                        previewMode === 'mobile'
                            ? 'bg-[#292929] text-white shadow-sm'
                            : 'text-gray-500 hover:text-gray-900'
                    "
                >
                    <Smartphone class="size-[18px]" />
                </button>
            </div>

            <template v-if="isEditing">
                <div class="mx-1 h-6 w-px bg-gray-200"></div>
                <Button
                    variant="ghost"
                    class="h-8 gap-2 rounded-xl px-4 text-sm text-gray-900 cursor-pointer"
                    @click="emit('addLink')"
                >
                    <LinkIcon class="size-4" />
                    <span class="font-semibold">リンク</span>
                </Button>
                <Button
                    variant="ghost"
                    class="h-8 gap-2 rounded-xl px-4 text-sm text-gray-900 cursor-pointer"
                    @click="chooseMedia"
                >
                    <Image class="size-4" />
                    <span class="font-semibold">メディア</span>
                </Button>
                <Button
                    variant="ghost"
                    class="h-8 gap-2 rounded-xl px-4 text-sm text-gray-900 cursor-pointer"
                    @click="emit('addText')"
                >
                    <Type class="size-4" />
                    <span class="font-semibold">テキスト</span>
                </Button>
                <Button
                    variant="ghost"
                    class="h-8 gap-2 rounded-xl px-4 text-sm text-gray-900 cursor-pointer"
                    @click="emit('addSection')"
                >
                    <svg
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                        class="size-4"
                    >
                        <path
                            d="M19 4.22107C19 3.66878 18.5523 3.22107 18 3.22107H6C5.44772 3.22107 5 3.66878 5 4.22107C5 4.77335 5.44772 5.22107 6 5.22107H18C18.5523 5.22107 19 4.77335 19 4.22107ZM21 4.22107C21 5.87792 19.6569 7.22107 18 7.22107H6C4.34315 7.22107 3 5.87792 3 4.22107C3 2.56422 4.34315 1.22107 6 1.22107H18C19.6569 1.22107 21 2.56422 21 4.22107Z"
                            fill="currentColor"
                        ></path>
                        <path
                            d="M19 13.2211C19 12.1165 18.1046 11.2211 17 11.2211H7C5.89543 11.2211 5 12.1165 5 13.2211V18.7787C5.0002 19.8831 5.89555 20.7787 7 20.7787H17C18.1044 20.7787 18.9998 19.8831 19 18.7787V13.2211ZM21 18.7787C20.9998 20.9877 19.209 22.7787 17 22.7787H7C4.79098 22.7787 3.0002 20.9877 3 18.7787V13.2211C3 11.012 4.79086 9.22112 7 9.22112H17C19.2091 9.22112 21 11.012 21 13.2211V18.7787Z"
                            fill="currentColor"
                        ></path>
                    </svg>
                    <span class="font-semibold">セクション</span>
                </Button>
                <Button
                    as-child
                    variant="ghost"
                    class="h-8 gap-2 rounded-xl px-4 text-sm text-gray-900 cursor-pointer"
                >
                    <InertiaLink :href="supportUrl">
                        <Heart class="size-4" />
                        <span class="font-semibold">サポート</span>
                    </InertiaLink>
                </Button>
            </template>
        </div>

        <!-- Mobile Toolbar -->
        <div
            v-if="!mobileWidgetOperationActive"
            class="hidden h-11 items-center rounded-2xl border border-gray-200 bg-white px-2 text-slate-500 shadow-[0_12px_30px_rgb(15,23,42,0.14)] max-[1024px]:flex min-[1025px]:hidden"
        >
            <button
                @click="emit('toggleEdit')"
                class="flex h-8 items-center justify-center rounded-xl transition-colors"
                :class="
                    isEditing
                        ? 'min-w-16 gap-1.5 bg-emerald-500 px-3 text-sm font-bold text-white shadow-sm hover:bg-emerald-600'
                        : 'w-8 hover:bg-slate-100 hover:text-slate-800'
                "
                :aria-label="isEditing ? '保存' : '編集'"
                :title="isEditing ? '保存' : '編集'"
            >
                <Save v-if="isEditing" class="size-5" stroke-width="2.2" />
                <Pencil v-else class="size-5" stroke-width="2.2" />
                <span v-if="isEditing">保存</span>
            </button>

            <template v-if="isEditing">
                <div class="mx-3 h-7 w-px bg-gray-200"></div>
                <button
                    @click="emit('addLink')"
                    class="flex size-8 items-center justify-center rounded-xl transition-colors hover:bg-slate-100 hover:text-slate-800"
                    aria-label="リンクを追加"
                    title="リンクを追加"
                >
                    <LinkIcon class="size-5" stroke-width="2.2" />
                </button>
                <button
                    @click="chooseMedia"
                    class="ml-2 flex size-8 items-center justify-center rounded-xl transition-colors hover:bg-slate-100 hover:text-slate-800"
                    aria-label="メディアを追加"
                    title="メディアを追加"
                >
                    <Image class="size-5" stroke-width="2.2" />
                </button>
                <button
                    @click="emit('addText')"
                    class="ml-2 flex size-8 items-center justify-center rounded-xl transition-colors hover:bg-slate-100 hover:text-slate-800"
                    aria-label="テキストを追加"
                    title="テキストを追加"
                >
                    <Type class="size-5" stroke-width="2.2" />
                </button>
                <button
                    @click="emit('addSection')"
                    class="ml-2 flex size-8 items-center justify-center rounded-xl transition-colors hover:bg-slate-100 hover:text-slate-800"
                    aria-label="セクションを追加"
                    title="セクションを追加"
                >
                    <svg
                        width="20"
                        height="20"
                        viewBox="0 0 24 24"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                        class="size-5"
                    >
                        <path
                            d="M19 4.22107C19 3.66878 18.5523 3.22107 18 3.22107H6C5.44772 3.22107 5 3.66878 5 4.22107C5 4.77335 5.44772 5.22107 6 5.22107H18C18.5523 5.22107 19 4.77335 19 4.22107ZM21 4.22107C21 5.87792 19.6569 7.22107 18 7.22107H6C4.34315 7.22107 3 5.87792 3 4.22107C3 2.56422 4.34315 1.22107 6 1.22107H18C19.6569 1.22107 21 2.56422 21 4.22107Z"
                            fill="currentColor"
                        ></path>
                        <path
                            d="M19 13.2211C19 12.1165 18.1046 11.2211 17 11.2211H7C5.89543 11.2211 5 12.1165 5 13.2211V18.7787C5.0002 19.8831 5.89555 20.7787 7 20.7787H17C18.1044 20.7787 18.9998 19.8831 19 18.7787V13.2211ZM21 18.7787C20.9998 20.9877 19.209 22.7787 17 22.7787H7C4.79098 22.7787 3.0002 20.9877 3 18.7787V13.2211C3 11.012 4.79086 9.22112 7 9.22112H17C19.2091 9.22112 21 11.012 21 13.2211V18.7787Z"
                            fill="currentColor"
                        ></path>
                    </svg>
                </button>
                <InertiaLink
                    :href="supportUrl"
                    class="ml-2 flex size-8 items-center justify-center rounded-xl transition-colors hover:bg-slate-100 hover:text-slate-800"
                    aria-label="サポートページを開く"
                    title="サポートページを開く"
                >
                    <Heart class="size-5" stroke-width="2.2" />
                </InertiaLink>
            </template>
        </div>

        <div
            v-else
            class="hidden h-11 items-center gap-1.5 rounded-2xl border border-gray-700 bg-[#292929] p-1.5 text-white shadow-[0_12px_30px_rgb(15,23,42,0.22)] max-[1024px]:flex min-[1025px]:hidden"
            @click.stop
        >
            <button
                v-for="option in mobileSizeOptions"
                :key="option.key"
                type="button"
                :aria-label="option.label"
                :title="option.label"
                class="flex size-8 items-center justify-center rounded-lg transition-colors"
                :class="
                    isActiveMobileSize(option.size)
                        ? 'bg-white text-gray-950 shadow-sm'
                        : 'text-white/70 hover:bg-white/10 hover:text-white'
                "
                @click.stop="emit('resizeMobileWidget', option.size)"
            >
                <span
                    v-if="activeMobileWidget?.type !== 'section'"
                    :class="mobileSizeIconClass(option)"
                ></span>
                <component v-else :is="option.icon" class="size-3.5" />
            </button>

            <div class="mx-1 h-7 w-px bg-white/20"></div>

            <button
                type="button"
                class="flex h-8 min-w-12 items-center justify-center whitespace-nowrap rounded-lg bg-white px-4 text-xs font-bold text-black transition-transform active:scale-95"
                @click.stop="emit('completeMobileWidgetOperation')"
            >
                完了
            </button>
        </div>

        <input
            ref="mediaInput"
            type="file"
            accept="image/*,.apng"
            class="hidden"
            @change="handleMediaChange"
        />
    </div>
</template>
