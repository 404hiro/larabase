<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Plus, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
    show: boolean;
    initialUrl?: string | null;
    initialSensitive?: boolean;
    allowEmpty?: boolean;
    title?: string;
    submitLabel?: string;
}>();

const emit = defineEmits<{
    close: [];
    add: [url: string, isSensitive: boolean];
}>();

const url = ref('');
const errorMessage = ref('');
const isSensitive = ref(false);
const maxUrlLength = 2000;

watch(
    () => props.show,
    (show) => {
        if (show) {
            url.value = props.initialUrl ?? '';
            isSensitive.value = Boolean(props.initialSensitive);
            errorMessage.value = '';
        }
    },
);

const normalizeUrl = (value: string) => {
    const trimmedUrl = value.trim();

    if (!trimmedUrl) {
        return '';
    }

    return /^[a-z][a-z\d+\-.]*:/i.test(trimmedUrl)
        ? trimmedUrl
        : `https://${trimmedUrl}`;
};

const isValidUrl = (string: string) => {
    try {
        new URL(string);
        return true;
    } catch (_) {
        return false;
    }
};

const handleAdd = () => {
    errorMessage.value = '';
    const normalizedUrl = normalizeUrl(url.value);

    if (!normalizedUrl) {
        if (props.allowEmpty) {
            emit('add', '', isSensitive.value);
        }

        return;
    }

    if (normalizedUrl.length > maxUrlLength || !isValidUrl(normalizedUrl)) {
        errorMessage.value = '有効なURLを入力してください';
        return;
    }
    emit('add', normalizedUrl, isSensitive.value);
    url.value = '';
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
    >
        <div
            class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-xl"
        >
            <button
                @click="emit('close')"
                class="absolute top-4 right-4 text-gray-400 hover:text-gray-600"
            >
                <Plus class="size-6 rotate-45" />
            </button>
            <h3 class="mb-6 text-xl font-bold">
                {{ title ?? 'リンクを追加' }}
            </h3>
            <div class="relative mb-2">
                <input
                    v-model="url"
                    type="url"
                    :maxlength="maxUrlLength"
                    placeholder="https://..."
                    class="w-full rounded-xl border px-4 py-3 pr-11 focus:ring-2 focus:outline-none"
                    :class="
                        errorMessage
                            ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20'
                            : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20'
                    "
                    @keyup.enter="handleAdd"
                    @input="errorMessage = ''"
                />
                <button
                    v-if="url"
                    type="button"
                    aria-label="リンクをクリア"
                    title="リンクをクリア"
                    class="absolute top-1/2 right-3 flex size-7 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700"
                    @click="url = ''"
                >
                    <X class="size-4" />
                </button>
            </div>
            <p v-if="errorMessage" class="mb-4 text-sm text-red-500">
                {{ errorMessage }}
            </p>
            <div v-else class="mb-4"></div>
            <label
                class="mb-6 flex cursor-pointer items-center justify-between rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-3"
            >
                <span class="text-sm font-semibold text-gray-800">
                    センシティブ
                </span>
                <button
                    type="button"
                    role="switch"
                    :aria-checked="isSensitive"
                    aria-label="センシティブ"
                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 focus:outline-none"
                    :class="isSensitive ? 'bg-blue-600' : 'bg-gray-300'"
                    @click.prevent.stop="isSensitive = !isSensitive"
                >
                    <span
                        class="pointer-events-none inline-block size-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                        :class="isSensitive ? 'translate-x-5' : 'translate-x-0'"
                    ></span>
                </button>
            </label>
            <div class="flex justify-end">
                <Button
                    @click="handleAdd"
                    class="w-full rounded-xl bg-[#292929] py-6 text-base font-semibold text-white hover:bg-black"
                >
                    {{ submitLabel ?? '追加' }}
                </Button>
            </div>
        </div>
    </div>
</template>
