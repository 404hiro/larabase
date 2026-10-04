<script setup lang="ts">
import { apiGet, apiJSON, apiUrl } from '@/lib/api';
import { ArrowLeft, Check, Image as ImageIcon, Send } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';

type MessageLink = {
  slug: string;
  display_name: string;
  avatar_url: string | null;
  is_accepting_messages: boolean;
};

const route = useRoute();
const link = ref<MessageLink | null>(null);
const body = ref('');
const senderName = ref('');
const senderEmail = ref('');
const isLoading = ref(true);
const isSending = ref(false);
const isSent = ref(false);
const error = ref('');

const slug = computed(() => String(route.params.slug || ''));
const avatarUrl = computed(() => (link.value?.avatar_url ? apiUrl(link.value.avatar_url) : ''));
const bodyLimit = 1000;

const loadMessageForm = async () => {
  isLoading.value = true;
  error.value = '';
  try {
    const data = await apiGet<{ link: MessageLink }>(`/api/links/${slug.value}/message`);
    link.value = data.link;
  } catch {
    error.value = 'ページを読み込めませんでした';
  } finally {
    isLoading.value = false;
  }
};

const submitMessage = async () => {
  if (!link.value || isSending.value) return;
  const trimmedBody = body.value.trim();
  if (!trimmedBody) {
    error.value = 'メッセージを入力してください';
    return;
  }
  if (trimmedBody.length > bodyLimit) {
    error.value = `${bodyLimit}文字以内で入力してください`;
    return;
  }

  isSending.value = true;
  error.value = '';
  try {
    await apiJSON(`/links/${link.value.slug}/messages`, 'POST', {
      body: trimmedBody,
      sender_name: senderName.value.trim(),
      sender_email: senderEmail.value.trim(),
    });
    isSent.value = true;
    body.value = '';
  } catch {
    error.value = '送信できませんでした';
  } finally {
    isSending.value = false;
  }
};

onMounted(loadMessageForm);
</script>

<template>
  <main class="min-h-screen bg-[#f7f7f5] px-5 py-6 text-gray-950">
    <div class="mx-auto grid min-h-[calc(100vh-3rem)] w-full max-w-[430px] grid-rows-[auto_1fr] gap-6">
      <RouterLink :to="`/@${slug}`" class="inline-flex size-10 items-center justify-center rounded-full bg-white text-gray-700 shadow-sm ring-1 ring-black/5">
        <ArrowLeft class="size-5" />
      </RouterLink>

      <section class="grid content-center gap-6">
        <div v-if="isLoading" class="rounded-3xl bg-white p-6 text-center shadow-sm ring-1 ring-black/5">
          <p class="text-sm font-black text-gray-400">読み込み中...</p>
        </div>

        <div v-else-if="error && !link" class="rounded-3xl bg-white p-6 text-center shadow-sm ring-1 ring-black/5">
          <p class="text-sm font-black text-red-500">{{ error }}</p>
        </div>

        <template v-else-if="link">
          <div class="text-center">
            <div class="mx-auto mb-4 flex size-24 items-center justify-center overflow-hidden rounded-full bg-white text-3xl font-black text-gray-400 shadow-sm ring-1 ring-black/5">
              <img v-if="avatarUrl" :src="avatarUrl" :alt="link.display_name" class="size-full object-cover" />
              <ImageIcon v-else class="size-8" />
            </div>
            <h1 class="text-2xl font-black">{{ link.display_name }}</h1>
          </div>

          <div v-if="isSent" class="rounded-3xl bg-white p-6 text-center shadow-sm ring-1 ring-black/5">
            <Check class="mx-auto mb-4 size-9 text-green-500" />
            <p class="text-lg font-black">送信しました</p>
          </div>

          <form v-else-if="link.is_accepting_messages" class="grid gap-3 rounded-3xl bg-white p-4 shadow-sm ring-1 ring-black/5" @submit.prevent="submitMessage">
            <textarea
              v-model="body"
              :maxlength="bodyLimit"
              rows="7"
              class="resize-none rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-base leading-7 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
              placeholder="メッセージを書く"
            ></textarea>
            <div class="flex items-center justify-between text-xs font-bold text-gray-400">
              <span>{{ error }}</span>
              <span>{{ body.length }}/{{ bodyLimit }}</span>
            </div>
            <input
              v-model="senderName"
              maxlength="80"
              class="h-12 rounded-2xl border border-gray-200 bg-gray-50 px-4 text-sm font-semibold outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
              placeholder="名前（任意）"
            />
            <input
              v-model="senderEmail"
              maxlength="255"
              type="email"
              class="h-12 rounded-2xl border border-gray-200 bg-gray-50 px-4 text-sm font-semibold outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
              placeholder="メールアドレス（任意）"
            />
            <button
              type="submit"
              class="mt-2 inline-flex h-13 items-center justify-center gap-2 rounded-full bg-black px-6 text-sm font-black text-white transition-colors hover:bg-gray-800 disabled:opacity-60"
              :disabled="isSending"
            >
              <Send class="size-4" />
              {{ isSending ? '送信中' : '送信' }}
            </button>
          </form>

          <div v-else class="rounded-3xl bg-white p-6 text-center shadow-sm ring-1 ring-black/5">
            <p class="text-sm font-black text-gray-500">現在メッセージを受け付けていません</p>
          </div>
        </template>
      </section>
    </div>
  </main>
</template>
