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
        const parsed = new URL(string);
        // プロトコルが http または https であり、ホスト名にドットが含まれていることを確認（ドメイン形式）
        return (parsed.protocol === 'http:' || parsed.protocol === 'https:') && parsed.hostname.includes('.');
    } catch (_) {
        return false;
    }
};

const handleAdd = () => {
    errorMessage.value = '';
    const trimmedValue = url.value.trim();

    if (!trimmedValue) {
        if (props.allowEmpty) {
            emit('add', '', isSensitive.value);
            url.value = '';
        } else {
            errorMessage.value = 'URLを入力してください';
        }
        return;
    }

    const normalizedUrl = normalizeUrl(trimmedValue);

    if (normalizedUrl.length > maxUrlLength || !isValidUrl(normalizedUrl)) {
        errorMessage.value = '有効なURLを入力してください（例: google.com）';
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
            <div class="mb-4">
                <div class="relative">
                    <input
                        v-model="url"
                        type="url"
                        :maxlength="maxUrlLength"
                        placeholder="https://..."
                        class="block w-full rounded-xl border px-4 py-3 pr-11 text-sm focus:ring-2 focus:outline-none"
                        :class="
                            errorMessage
                                ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
                                : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'
                        "
                        :aria-describedby="errorMessage ? 'url-error' : undefined"
                        @keyup.enter="handleAdd"
                        @input="errorMessage = ''"
                    />
                    <div v-if="errorMessage" class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                        <svg class="size-4 shrink-0 text-red-500" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                    </div>
                    <button
                        v-else-if="url"
                        type="button"
                        aria-label="リンクをクリア"
                        title="リンクをクリア"
                        class="absolute top-1/2 right-3 flex size-7 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700"
                        @click="url = ''"
                    >
                        <X class="size-4" />
                    </button>
                </div>
                <p v-if="errorMessage" class="mt-2 text-sm text-red-600" id="url-error">
                    {{ errorMessage }}
                </p>
            </div>
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
