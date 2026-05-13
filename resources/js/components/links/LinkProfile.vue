<script setup lang="ts">
import LinkWidgetControls from '@/components/links/LinkWidgetControls.vue';
import { compressImage } from '@/utils/imageCompression';
import { Image as ImageIcon } from 'lucide-vue-next';
import { nextTick, ref, watch } from 'vue';

const props = defineProps<{
    isEditing: boolean;
    previewMode: 'desktop' | 'mobile';
    displayName: string;
    bio: string;
    avatarUrl: string | null;
    displayInitial: string;
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
                        class="group relative size-[118px] shrink-0"
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
                        class="editor-placeholder w-full resize-none overflow-hidden rounded-xl border-2 border-gray-200 bg-gray-100/70 px-3 py-1 text-3xl font-bold tracking-tight transition-colors duration-200 outline-none hover:border-gray-300 hover:bg-gray-100 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/20"
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
                    data-placeholder="自己紹介を入力"
                    aria-multiline="true"
                    @input="updateBio"
                    @paste="pastePlainText($event)"
                    @focus="isBioFocused = true"
                    @blur="isBioFocused = false"
                    class="editor-placeholder w-full max-w-[374px] resize-none overflow-hidden rounded-xl border-2 border-gray-200 bg-gray-100/70 px-3 py-2 text-base text-gray-700 transition-colors duration-200 outline-none hover:border-gray-300 hover:bg-gray-100 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/20"
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
