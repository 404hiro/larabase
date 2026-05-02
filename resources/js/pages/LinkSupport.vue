<script setup lang="ts">
import LinkPageNavigation from '@/components/links/LinkPageNavigation.vue';
import { Head } from '@inertiajs/vue3';
import { Coffee, Heart, MessageCircle } from 'lucide-vue-next';

const props = defineProps<{
    link: {
        id: number;
        slug: string;
        display_name: string;
        bio?: string | null;
        avatar_url?: string | null;
    };
}>();

const supportOptions = [
    { amount: 300, label: 'コーヒー' },
    { amount: 500, label: 'ランチ代' },
    { amount: 1000, label: '制作応援' },
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
</script>

<template>
    <Head :title="`${link.display_name} のサポート`" />

    <main class="min-h-screen bg-white px-5 pt-14 text-gray-950 min-[1025px]:pt-20">
        <LinkPageNavigation :slug="props.link.slug" active-tab="support" />

        <section
            class="mx-auto grid w-full max-w-[374px] gap-5 pb-20 min-[1025px]:max-w-[1198px] min-[1025px]:grid-cols-[minmax(0,420px)_minmax(0,1fr)] min-[1025px]:gap-8"
        >
            <div class="space-y-5">
                <div class="rounded-2xl border border-gray-200 bg-white p-5">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-14 items-center justify-center overflow-hidden rounded-full bg-gray-100 text-lg font-bold"
                        >
                            <img
                                v-if="link.avatar_url"
                                :src="link.avatar_url"
                                :alt="link.display_name"
                                class="h-full w-full object-cover"
                            />
                            <span v-else>{{ link.display_name.charAt(0) }}</span>
                        </div>
                        <div class="min-w-0">
                            <h1 class="truncate text-xl font-bold">
                                {{ link.display_name }}
                            </h1>
                            <p class="text-sm text-gray-500">@{{ link.slug }}</p>
                        </div>
                    </div>
                    <p
                        v-if="link.bio"
                        class="mt-4 whitespace-pre-wrap text-sm leading-6 text-gray-700"
                    >
                        {{ link.bio }}
                    </p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5">
                    <div class="flex items-center gap-2">
                        <Coffee class="size-5 text-amber-500" />
                        <h2 class="text-lg font-bold">少額支援</h2>
                    </div>
                    <p class="mt-2 text-sm leading-6 text-gray-600">
                        Buymeacoffee や Ko-fi のように、好きな金額を選んで
                        クリエイターの活動を応援できます。
                    </p>

                    <div class="mt-5 grid grid-cols-3 gap-2">
                        <button
                            v-for="option in supportOptions"
                            :key="option.amount"
                            type="button"
                            class="rounded-xl border border-gray-200 px-3 py-3 text-center transition-colors hover:bg-gray-50"
                        >
                            <span class="block text-sm font-bold">
                                ¥{{ option.amount.toLocaleString() }}
                            </span>
                            <span class="mt-1 block text-xs text-gray-500">
                                {{ option.label }}
                            </span>
                        </button>
                    </div>

                    <textarea
                        rows="4"
                        placeholder="応援メッセージを書く"
                        class="mt-4 w-full resize-none rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-3 text-sm outline-none focus:border-gray-300 focus:ring-2 focus:ring-gray-200"
                    ></textarea>

                    <button
                        type="button"
                        class="mt-4 w-full rounded-xl bg-black px-4 py-3 text-sm font-bold text-white transition-colors hover:bg-gray-800"
                    >
                        サポートする
                    </button>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <div class="flex items-center gap-2">
                    <MessageCircle class="size-5 text-pink-500" />
                    <h2 class="text-lg font-bold">ファンからのメッセージ</h2>
                </div>

                <div class="mt-5 space-y-3">
                    <article
                        v-for="message in fanMessages"
                        :key="message.name"
                        class="rounded-xl border border-gray-100 bg-gray-50/70 p-4"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex size-8 items-center justify-center rounded-full bg-white text-sm font-bold text-gray-700"
                                >
                                    {{ message.name.charAt(0) }}
                                </div>
                                <span class="font-bold">{{ message.name }}</span>
                            </div>
                            <span class="inline-flex items-center gap-1 text-sm font-bold text-pink-500">
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
