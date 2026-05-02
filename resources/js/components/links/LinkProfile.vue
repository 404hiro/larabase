<script setup lang="ts">
import LinkWidgetControls from '@/components/links/LinkWidgetControls.vue';
import { Image as ImageIcon } from 'lucide-vue-next';
import { nextTick, ref, watch } from 'vue';
import imageCompression from 'browser-image-compression';

const props = defineProps<{
    isEditing: boolean;
    previewMode: 'desktop' | 'mobile';
    displayName: string;
    bio: string;
    avatarUrl: string | null;
    displayInitial: string;
    showPrivateNotice?: boolean;
}>();

const emit = defineEmits<{
    'update:displayName': [value: string];
    'update:bio': [value: string];
    'update:avatar': [file: File | null];
    'remove:avatar': [];
    publish: [];
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
    
    // アニメーションを保持するためgif/pngは圧縮をスキップ
    if (file.type === 'image/gif' || file.type === 'image/png') {
        emit('update:avatar', file);
    } else {
        try {
            const options = {
                maxSizeMB: 1,
                maxWidthOrHeight: 1024,
                useWebWorker: true,
            };
            const compressedFile = await imageCompression(file, options);
            emit('update:avatar', compressedFile as File);
        } catch (error) {
            console.error('Image compression error:', error);
            emit('update:avatar', file); // エラー時はオリジナルを送信
        }
    }
    
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
                    class="group relative mt-4 mb-6 size-[118px]"
                    :class="previewMode === 'desktop' ? 'lg:size-32' : ''"
                >
                    <button
                        type="button"
                        class="relative flex h-full w-full items-center justify-center overflow-hidden rounded-full border-4 border-gray-300 bg-gray-100 text-4xl font-bold text-gray-700 shadow-sm transition-colors"
                        :class="[
                            previewMode === 'desktop' ? 'lg:text-5xl' : '',
                            isEditing
                                ? 'cursor-pointer hover:border-gray-400'
                                : '',
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
                        <span v-else>{{ displayInitial }}</span>

                        <span
                            v-if="isEditing"
                            class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/0 text-white opacity-0 transition-all duration-150 group-hover:bg-black/20 group-hover:opacity-100"
                        >
                            <ImageIcon class="size-9" />
                        </span>
                    </button>

                    <LinkWidgetControls
                        v-if="isEditing && avatarUrl"
                        class="opacity-0 transition-opacity duration-150 [&_*]:pointer-events-none group-hover:opacity-100 group-hover:[&_*]:pointer-events-auto max-[1024px]:opacity-100 max-[1024px]:[&_*]:pointer-events-auto"
                        :widget="{}"
                        :mode="previewMode"
                        :size-options="[]"
                        @delete="removeAvatar"
                    />

                    <input
                        ref="avatarInput"
                        type="file"
                        accept="image/*"
                        class="hidden"
                        @change="updateAvatar"
                    />
                </div>

                <div
                    class="mb-3 flex flex-col items-start gap-2"
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
                        @keydown.enter.prevent
                        @input="updateDisplayName"
                        @paste="pastePlainText($event, true)"
                        @focus="isNameFocused = true"
                        @blur="isNameFocused = false"
                        class="w-full resize-none overflow-hidden rounded-xl border-2 border-transparent bg-transparent px-3 py-1 text-3xl font-bold tracking-tight transition-colors duration-200 outline-none hover:border-gray-200 hover:bg-gray-100/70 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/20"
                    ></div>
                    <h1
                        v-else
                        class="w-full border-2 border-transparent px-3 py-1 text-3xl font-bold tracking-tight break-words"
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
                    aria-multiline="true"
                    @input="updateBio"
                    @paste="pastePlainText($event)"
                    @focus="isBioFocused = true"
                    @blur="isBioFocused = false"
                    class="w-full max-w-[374px] resize-none overflow-hidden rounded-xl border-2 border-transparent bg-transparent px-3 py-2 text-base text-gray-700 transition-colors duration-200 outline-none hover:border-gray-200 hover:bg-gray-100/70 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/20"
                    :class="previewMode === 'desktop' ? 'lg:max-w-xl' : ''"
                ></div>
                <p
                    v-else
                    class="w-full max-w-[374px] border-2 border-transparent px-3 py-2 text-base break-words whitespace-pre-wrap text-gray-700"
                    :class="
                        previewMode === 'desktop'
                            ? 'lg:line-clamp-[15] lg:max-w-xl'
                            : ''
                    "
                >
                    {{ bio }}
                </p>

                <div
                    v-if="showPrivateNotice"
                    class="mt-8 w-full max-w-[374px] rounded-xl border border-yellow-200 bg-yellow-50 p-4"
                    role="alert"
                    tabindex="-1"
                    aria-labelledby="private-link-alert"
                    :class="previewMode === 'desktop' ? 'lg:max-w-xl' : ''"
                >
                    <div class="flex items-start gap-3">
                        <div class="shrink-0">
                            <svg
                                class="mt-0.5 size-4 text-yellow-600"
                                xmlns="http://www.w3.org/2000/svg"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path
                                    d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"
                                />
                                <path d="M12 9v4" />
                                <path d="M12 17h.01" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3
                                id="private-link-alert"
                                class="text-sm font-semibold text-yellow-800"
                            >
                                このページは非公開です
                            </h3>
                            <div class="mt-1 text-sm text-yellow-700">
                                あなたにだけ表示されています。
                            </div>
                            <div class="mt-4">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-x-2 rounded-lg border border-transparent bg-yellow-500 px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-yellow-600 focus:bg-yellow-600 focus:outline-none"
                                    @click="emit('publish')"
                                >
                                    公開する
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </aside>
</template>
