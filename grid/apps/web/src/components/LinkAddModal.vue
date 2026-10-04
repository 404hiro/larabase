<script setup lang="ts">
import { X } from 'lucide-vue-next';
import { nextTick, ref, watch } from 'vue';

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
const inputRef = ref<HTMLInputElement | null>(null);
const maxUrlLength = 2000;

watch(
  () => props.show,
  (show) => {
    if (!show) return;
    url.value = props.initialUrl ?? '';
    isSensitive.value = Boolean(props.initialSensitive);
    errorMessage.value = '';
    nextTick(() => inputRef.value?.focus());
  },
);

const normalizeUrl = (value: string) => {
  const trimmedUrl = value.trim();
  if (!trimmedUrl) return '';
  return /^[a-z][a-z\d+\-.]*:/i.test(trimmedUrl) ? trimmedUrl : `https://${trimmedUrl}`;
};

const isValidUrl = (value: string) => {
  try {
    const parsed = new URL(value);
    return (parsed.protocol === 'http:' || parsed.protocol === 'https:') && parsed.hostname.includes('.');
  } catch {
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
      return;
    }
    errorMessage.value = 'URLを入力してください';
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
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
      @click.self="emit('close')"
    >
      <div class="link-modal-enter relative w-full max-w-md rounded-3xl bg-white p-6 text-gray-950 shadow-xl">
        <button
          type="button"
          @click="emit('close')"
          class="absolute top-4 right-4 flex size-8 cursor-pointer items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700"
          aria-label="閉じる"
          title="閉じる"
        >
          <X class="size-5" />
        </button>

        <h3 class="mb-6 text-xl font-bold">
          {{ title ?? 'リンクを追加' }}
        </h3>

        <div class="mb-4">
          <div class="relative">
            <input
              ref="inputRef"
              v-model="url"
              type="url"
              :maxlength="maxUrlLength"
              placeholder="https://..."
              class="block w-full rounded-xl border px-4 py-3 pr-11 text-sm transition-colors focus:ring-2 focus:outline-none"
              :class="
                errorMessage
                  ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
                  : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'
              "
              :aria-describedby="errorMessage ? 'url-error' : undefined"
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
          <p v-if="errorMessage" id="url-error" class="mt-2 text-sm text-red-600">
            {{ errorMessage }}
          </p>
        </div>

        <label class="mb-6 flex cursor-pointer items-center justify-between rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-3 transition-colors">
          <span class="text-sm font-semibold text-gray-800">開く前に確認を表示</span>
          <button
            type="button"
            role="switch"
            :aria-checked="isSensitive"
            aria-label="開く前に確認を表示"
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

        <button
          type="button"
          class="w-full rounded-xl bg-[#292929] py-4 text-base font-semibold text-white transition-colors hover:bg-black"
          @click="handleAdd"
        >
          {{ submitLabel ?? '追加' }}
        </button>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.link-modal-enter {
  animation: modalPopIn 0.18s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes modalPopIn {
  from {
    opacity: 0;
    transform: translateY(10px) scale(0.97);
  }

  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
</style>
