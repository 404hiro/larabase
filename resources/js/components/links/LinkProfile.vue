<script setup lang="ts">
import LinkWidgetControls from '@/components/links/LinkWidgetControls.vue';
import { compressImage } from '@/utils/imageCompression';
import { Link as InertiaLink } from '@inertiajs/vue3';
import { Image as ImageIcon, MessageCircleHeart } from 'lucide-vue-next';
import { nextTick, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    isEditing: boolean;
    previewMode: 'desktop' | 'mobile';
    displayName: string;
    bio: string;
    avatarUrl: string | null;
    displayInitial: string;
    letterUrl: string;
    pageTheme?: 'light' | 'dark';
}>();

const emit = defineEmits<{
    'update:displayName': [value: string];
    'update:bio': [value: string];
    'update:avatar': [file: File | null];
    'remove:avatar': [];
}>();

const avatarInput = ref<HTMLInputElement | null>(null);
const bioEditor = ref<HTMLElement | null>(null);
const nameEditor = ref<HTMLElement | null>(null);
const isNameFocused = ref(false);
const isBioFocused = ref(false);

const syncEditorText = (
    editor: HTMLElement | null,
    value: string,
    isFocused: boolean,
) => {
    if (!editor || isFocused) {
        return;
    }

    if (editor.innerText !== value) {
        editor.innerText = value;
    }
};

const syncEditors = () => {
    syncEditorText(nameEditor.value, props.displayName, isNameFocused.value);
    syncEditorText(bioEditor.value, props.bio, isBioFocused.value);
};

onMounted(() => {
    if (props.isEditing) {
        nextTick(() => syncEditors());
    }
});

watch(
    () => [props.bio, props.displayName],
    () => {
        if (props.isEditing) {
            nextTick(() => syncEditors());
        }
    },
);

watch(
    () => props.isEditing,
    (val) => {
        if (val) {
            nextTick(() => syncEditors());
        }
    },
);

const updateDisplayName = () => {
    const value = (nameEditor.value?.innerText ?? '').replace(/[\r\n]/g, '');

    if (nameEditor.value && nameEditor.value.innerText !== value) {
        nameEditor.value.innerText = value;
    }

    emit('update:displayName', value);
};

const updateBio = () => {
    emit('update:bio', bioEditor.value?.innerText ?? '');
};

const pastePlainText = (event: ClipboardEvent, singleLine = false) => {
    event.preventDefault();

    const text = event.clipboardData?.getData('text/plain') ?? '';
    document.execCommand(
        'insertText',
        false,
        singleLine ? text.replace(/[\r\n]+/g, ' ') : text,
    );

    if (singleLine) {
        updateDisplayName();
    } else {
        updateBio();
    }
};

const chooseAvatar = () => {
    if (!props.isEditing) return;
    avatarInput.value?.click();
};

const updateAvatar = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    if (!file) return;

    const compressedFile = await compressImage(file, { preset: 'avatar' });
    if (!compressedFile) return;

    emit('update:avatar', compressedFile);

    input.value = '';
};

const removeAvatar = () => {
    emit('remove:avatar');
};
</script>

<template>
    <aside
        class="mx-auto w-full transition-all duration-300"
        :class="
            previewMode === 'mobile'
                ? 'max-w-[374px] flex-shrink-0'
                : 'max-w-[374px] min-[1025px]:mx-0 min-[1025px]:w-[280px] min-[1025px]:min-w-[200px] min-[1025px]:flex-shrink'
        "
    >
        <div
            class="transition-all duration-300"
            :class="[
                previewMode === 'desktop'
                    ? 'min-[1025px]:sticky min-[1025px]:top-16 min-[1025px]:-mx-4 min-[1025px]:max-h-[calc(100vh-64px)] min-[1025px]:overflow-y-auto min-[1025px]:px-4'
                    : '',
            ]"
        >
            <div class="text-left">
                <div
                    class="mb-4 flex items-start gap-8"
                    :class="previewMode === 'mobile' ? 'mt-4' : 'mt-4'"
                >
                    <div
                        class="group relative shrink-0"
                        :class="[
                            previewMode === 'mobile'
                                ? 'size-[120px]'
                                : 'size-[120px] min-[1025px]:size-[184px]',
                        ]"
                    >
                        <button
                            type="button"
                            class="relative flex h-full w-full items-center justify-center overflow-hidden rounded-full border-4 text-[32px] font-bold shadow-sm transition-colors"
                            :class="[
                                isEditing
                                    ? 'cursor-pointer hover:border-gray-400'
                                    : '',
                                pageTheme === 'dark'
                                    ? 'border-white/20 bg-white/10 text-white'
                                    : 'border-gray-300 bg-gray-100 text-gray-700',
                                previewMode === 'mobile'
                                    ? 'text-[32px]'
                                    : 'min-[1025px]:text-[44px]',
                            ]"
                            @click="chooseAvatar"
                        >
                            <img
                                v-if="avatarUrl"
                                :src="avatarUrl"
                                :alt="displayName"
                                class="h-full w-full object-cover"
                                draggable="false"
                            />
                            <template v-else>
                                <ImageIcon
                                    v-if="isEditing"
                                    class="size-12 text-gray-400"
                                />
                                <span v-else>{{ displayInitial }}</span>
                            </template>

                            <span
                                v-if="isEditing"
                                class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/0 text-white opacity-0 transition-all duration-150 group-hover:bg-black/20 group-hover:opacity-100"
                            >
                                <ImageIcon class="size-9" />
                            </span>
                        </button>

                        <LinkWidgetControls
                            v-if="isEditing && avatarUrl"
                            class="opacity-0 transition-opacity duration-150 group-hover:opacity-100 max-[1024px]:opacity-100 [&_*]:pointer-events-none group-hover:[&_*]:pointer-events-auto max-[1024px]:[&_*]:pointer-events-auto"
                            :widget="{}"
                            :mode="previewMode"
                            :size-options="[]"
                            @delete="removeAvatar"
                        />

                        <input
                            ref="avatarInput"
                            type="file"
                            accept="image/*,.apng"
                            class="hidden"
                            @change="updateAvatar"
                        />
                    </div>
                </div>

                <div
                    class="mb-2 flex flex-col items-start gap-2"
                    :class="
                        previewMode === 'desktop'
                            ? 'lg:flex-row lg:items-baseline lg:gap-3'
                            : ''
                    "
                >
                    <div
                        v-if="isEditing"
                        ref="nameEditor"
                        contenteditable="true"
                        role="textbox"
                        aria-label="表示名"
                        data-placeholder="名前を入力"
                        @keydown.enter.prevent
                        @input="updateDisplayName"
                        @paste="pastePlainText($event, true)"
                        @focus="isNameFocused = true"
                        @blur="isNameFocused = false"
                        class="editor-placeholder w-full resize-none overflow-hidden rounded-xl border-2 px-3 py-1 text-[30px] leading-tight font-bold tracking-tight transition-colors duration-200 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20"
                        :class="
                            pageTheme === 'dark'
                                ? 'border-white/15 bg-white/10 text-white hover:border-white/25 hover:bg-white/15 focus:bg-white/15'
                                : 'border-gray-200 bg-gray-100/70 text-gray-950 hover:border-gray-300 hover:bg-gray-100 focus:bg-white'
                        "
                    ></div>
                    <h1
                        v-else
                        class="w-full border-2 border-transparent px-3 py-1 text-[30px] leading-tight font-bold tracking-tight break-words"
                        :class="pageTheme === 'dark' ? 'text-white' : ''"
                    >
                        {{ displayName }}
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
                    @input="updateBio"
                    @paste="pastePlainText($event)"
                    @focus="isBioFocused = true"
                    @blur="isBioFocused = false"
                    class="editor-placeholder w-full max-w-[374px] resize-none overflow-hidden rounded-xl border-2 px-3 py-2 text-[16px] leading-relaxed transition-colors duration-200 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20"
                    :class="[
                        previewMode === 'desktop' ? 'lg:max-w-xl' : '',
                        pageTheme === 'dark'
                            ? 'border-white/15 bg-white/10 text-white hover:border-white/25 hover:bg-white/15 focus:bg-white/15'
                            : 'border-gray-200 bg-gray-100/70 text-gray-700 hover:border-gray-300 hover:bg-gray-100 focus:bg-white',
                    ]"
                ></div>
                <p
                    v-else
                    class="w-full max-w-[374px] border-2 border-transparent px-3 py-2 text-[16px] leading-relaxed break-words whitespace-pre-wrap"
                    :class="[
                        previewMode === 'desktop'
                            ? 'lg:line-clamp-[15] lg:max-w-xl'
                            : '',
                        pageTheme === 'dark' ? 'text-white/70' : 'text-gray-700',
                    ]"
                >
                    {{ bio }}
                </p>

                <div class="mt-3 px-3">
                    <button
                        v-if="isEditing"
                        type="button"
                        disabled
                        class="grid h-12 w-full max-w-[374px] cursor-not-allowed grid-cols-[40px_1fr_40px] items-center rounded-full border border-gray-300 bg-white px-1.5 text-gray-500 opacity-60"
                        :class="previewMode === 'desktop' ? 'lg:max-w-xl' : ''"
                    >
                        <span
                            class="flex size-9 items-center justify-center rounded-full bg-white text-black"
                        >
                            <MessageCircleHeart class="size-5" />
                        </span>
                        <span class="text-center text-sm font-bold">
                            メッセージ
                        </span>
                        <span aria-hidden="true"></span>
                    </button>
                    <InertiaLink
                        v-else
                        :href="letterUrl"
                        class="grid h-12 w-full max-w-[374px] grid-cols-[40px_1fr_40px] items-center rounded-full border px-1.5 transition-colors"
                        :class="[
                            previewMode === 'desktop' ? 'lg:max-w-xl' : '',
                            pageTheme === 'dark'
                                ? 'border-white/15 bg-white/10 text-white hover:border-white/25 hover:bg-white/15'
                                : 'border-gray-300 bg-white text-gray-950 hover:border-gray-400 hover:bg-gray-50',
                        ]"
                    >
                        <span
                            class="flex size-9 items-center justify-center rounded-full bg-white text-black"
                        >
                            <MessageCircleHeart class="size-5" />
                        </span>
                        <span class="text-center text-sm font-bold">
                            メッセージ
                        </span>
                        <span aria-hidden="true"></span>
                    </InertiaLink>
                </div>
            </div>
        </div>
    </aside>
</template>

<style scoped>
.editor-placeholder:empty:before {
    content: attr(data-placeholder);
    color: #9ca3af;
    pointer-events: none;
    display: block;
}
</style>
