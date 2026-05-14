<script setup lang="ts">
import LinkPageNavigation from '@/components/links/LinkPageNavigation.vue';
import { Head, usePage } from '@inertiajs/vue3';
import {
    Gift,
    Heart,
    MessageCircle,
    MessageCircleHeart,
    PencilLine,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    link: {
        id: number;
        user_id: number;
        slug: string;
        display_name: string;
        bio?: string | null;
        avatar_url?: string | null;
    };
}>();

const page = usePage();
const isOwner = computed(() => {
    return Boolean(
        page.props.auth?.user && page.props.auth.user.id === props.link.user_id,
    );
});

const supportOptions = [
    { amount: 300, icon: '☕', label: 'コーヒー' },
    { amount: 500, icon: '🍰', label: 'おやつ' },
    { amount: 1000, icon: '💐', label: 'ブーケ' },
];

const fanMessages = [
    {
        name: 'Mika',
        message: 'いつも投稿を楽しみにしています。次の作品も応援しています。',
        amount: 500,
    },
    {
        name: 'Ryo',
        message: '素敵な活動をありがとうございます。小さくですが応援です。',
        amount: 300,
    },
    {
        name: 'Aya',
        message: 'これからも無理なく続けてください。',
        amount: 1000,
    },
];

const activeTab = ref<'write' | 'read'>('write');
const messageText = ref('');
const selectedSupportAmount = ref<number | null>(null);

const canSendMessage = computed(() => {
    return !isOwner.value && messageText.value.trim().length > 0;
});
</script>

<template>
    <Head :title="`${link.display_name} へメッセージ`" />

    <main
        class="relative min-h-screen overflow-hidden bg-neutral-100 px-5 pt-14 text-gray-950"
    >
        <div
            class="absolute inset-x-0 top-9 h-28 bg-black min-[1025px]:h-36"
            aria-hidden="true"
        ></div>

        <LinkPageNavigation :slug="props.link.slug" active-tab="letter" />

        <section
            class="relative z-10 mx-auto w-full max-w-[374px] pb-20 min-[1025px]:max-w-[1198px]"
        >
            <div
                class="mt-8 rounded-2xl border border-neutral-200 bg-white shadow-[0_24px_70px_rgba(0,0,0,0.08)] min-[1025px]:mt-10"
            >
                <div class="px-5 pt-0 min-[1025px]:px-8">
                    <div class="flex flex-col items-center text-center">
                        <div
                            class="-mt-10 flex size-20 items-center justify-center overflow-hidden rounded-full border-[5px] border-white bg-gray-100 text-2xl font-bold shadow-[0_16px_34px_rgba(0,0,0,0.16)] min-[1025px]:-mt-12 min-[1025px]:size-28"
                        >
                            <img
                                v-if="link.avatar_url"
                                :src="link.avatar_url"
                                :alt="link.display_name"
                                class="h-full w-full object-cover"
                            />
                            <span v-else>{{
                                link.display_name.charAt(0)
                            }}</span>
                        </div>
                        <div class="mt-3 min-w-0">
                            <div
                                class="flex flex-wrap items-center justify-center gap-2"
                            >
                                <h1
                                    class="truncate text-2xl font-black min-[1025px]:text-4xl"
                                >
                                    {{ link.display_name }}
                                </h1>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 min-[1025px]:mt-6">
                        <div
                            class="mx-auto flex w-full max-w-md items-center justify-center gap-10 border-b border-gray-200 min-[1025px]:gap-16"
                        >
                            <button
                                type="button"
                                class="relative flex h-12 items-center justify-center gap-2 px-2 text-sm font-bold transition-colors"
                                :class="
                                    activeTab === 'write'
                                        ? 'text-black after:absolute after:inset-x-0 after:bottom-0 after:h-1 after:bg-black'
                                        : 'text-gray-500 hover:text-gray-900'
                                "
                                @click="activeTab = 'write'"
                            >
                                <PencilLine class="size-4" />
                                書く
                            </button>
                            <button
                                type="button"
                                class="relative flex h-12 items-center justify-center gap-2 px-2 text-sm font-bold transition-colors"
                                :class="
                                    activeTab === 'read'
                                        ? 'text-black after:absolute after:inset-x-0 after:bottom-0 after:h-1 after:bg-black'
                                        : 'text-gray-500 hover:text-gray-900'
                                "
                                @click="activeTab = 'read'"
                            >
                                <MessageCircleHeart class="size-4" />
                                読む
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="activeTab === 'write'"
                class="mt-4 rounded-2xl border border-neutral-200 bg-white p-5 shadow-[0_20px_60px_rgba(0,0,0,0.06)] min-[1025px]:mt-5 min-[1025px]:p-8"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <MessageCircleHeart class="size-5 text-black" />
                            <h2 class="text-lg font-bold">メッセージを書く</h2>
                        </div>
                        <p class="mt-2 text-sm leading-6 text-gray-600">
                            感想や応援の気持ちを匿名で届けられます。
                            メッセージに差し入れを添えることもできます。
                        </p>
                    </div>
                </div>

                <div
                    v-if="isOwner"
                    class="mt-4 rounded-xl border border-neutral-200 bg-white p-3"
                >
                    <p class="text-xs leading-5 text-gray-600">
                        収益設定をすると、メッセージに添えられた差し入れを受け取れます。
                    </p>
                    <button
                        type="button"
                        class="mt-2 inline-flex items-center justify-center rounded-full border border-neutral-200 px-3 py-1.5 text-xs font-bold text-gray-800 transition-colors hover:border-neutral-400 hover:bg-neutral-50"
                    >
                        収益設定
                    </button>
                </div>

                <div class="mt-6 rounded-2xl border border-neutral-200 bg-neutral-50/50 p-4">
                    <p class="text-xs font-bold text-gray-500">
                        匿名で届きます
                    </p>
                    <p class="mt-1 text-xs leading-5 text-gray-500">
                        公開するかどうかは受け取った人が選べます。
                    </p>
                    <textarea
                        v-model="messageText"
                        rows="8"
                        placeholder="感想や応援の気持ちを書いてみる"
                        :disabled="isOwner"
                        class="mt-4 w-full resize-none rounded-2xl border border-neutral-200 bg-white px-4 py-4 text-base leading-7 outline-none transition focus:border-black focus:ring-4 focus:ring-neutral-200 disabled:cursor-not-allowed disabled:text-gray-400 disabled:placeholder:text-gray-400"
                    ></textarea>
                </div>

                <div v-if="!isOwner" class="mt-5">
                    <div class="flex items-center gap-2">
                        <Gift class="size-4 text-gray-700" />
                        <h3 class="text-sm font-bold text-gray-900">
                            差し入れを添える
                            <span class="font-medium text-gray-500">
                                （任意）
                            </span>
                        </h3>
                    </div>
                    <div class="mt-3 grid grid-cols-3 gap-2">
                        <button
                            v-for="option in supportOptions"
                            :key="option.amount"
                            type="button"
                            class="rounded-2xl border bg-white px-3 py-3 text-center transition-colors hover:border-black"
                            :class="
                                selectedSupportAmount === option.amount
                                    ? 'border-black'
                                    : 'border-neutral-200'
                            "
                            @click="selectedSupportAmount = option.amount"
                        >
                            <span class="block text-xl leading-none">
                                {{ option.icon }}
                            </span>
                            <span class="mt-2 block text-xs font-bold">
                                {{ option.label }}
                            </span>
                            <span class="mt-1 block text-xs text-gray-500">
                                ¥{{ option.amount.toLocaleString() }}
                            </span>
                        </button>
                    </div>
                </div>

                <button
                    type="button"
                    :disabled="!canSendMessage"
                    class="mt-5 inline-flex w-full items-center justify-center rounded-full px-4 py-4 text-sm font-bold transition-colors"
                    :class="
                        canSendMessage
                            ? 'bg-black text-white hover:bg-neutral-800'
                            : 'border border-neutral-200 bg-white text-gray-400'
                    "
                >
                    メッセージを届ける
                </button>
            </div>

            <div
                v-else
                class="mt-5 rounded-2xl border border-neutral-200 bg-white p-5 shadow-[0_20px_60px_rgba(0,0,0,0.07)] min-[1025px]:mt-6 min-[1025px]:p-8"
            >
                <div class="flex items-center gap-2">
                    <MessageCircle class="size-5 text-black" />
                    <h2 class="text-lg font-bold">届いたメッセージ</h2>
                </div>

                <div class="mt-5 space-y-3">
                    <article
                        v-for="message in fanMessages"
                        :key="message.name"
                        class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex size-8 items-center justify-center rounded-full bg-white text-sm font-bold text-gray-700"
                                >
                                    {{ message.name.charAt(0) }}
                                </div>
                                <span class="font-bold">{{
                                    message.name
                                }}</span>
                            </div>
                            <span
                                class="inline-flex items-center gap-1 text-sm font-bold text-black"
                            >
                                <Heart class="size-4 fill-current" />
                                ¥{{ message.amount.toLocaleString() }}
                            </span>
                        </div>
                        <p class="mt-3 text-sm leading-6 text-gray-700">
                            {{ message.message }}
                        </p>
                    </article>
                </div>
            </div>
        </section>
    </main>
</template>
