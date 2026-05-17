<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    BarChart3,
    ExternalLink,
    Link as LinkIcon,
    MousePointerClick,
} from 'lucide-vue-next';

type ManagedLink = {
    id: string;
    slug: string;
    display_name: string;
    title?: {
        id: number;
        name: string;
    } | null;
    bio?: string | null;
    avatar_url?: string | null;
    is_published: boolean;
    widgets_count: number;
    updated_at?: string | null;
};

defineProps<{
    links: ManagedLink[];
    linksCount: number;
    totalAccessesLast30Days: number;
    totalClicksLast30Days: number;
}>();

const formatDate = (value?: string | null) => {
    if (!value) {
        return '未更新';
    }

    return new Intl.DateTimeFormat('ja-JP', {
        month: 'short',
        day: 'numeric',
    }).format(new Date(value));
};
</script>

<template>
    <Head title="リンク" />

    <DashboardLayout>
        <div class="space-y-6">
            <div>
                <h1
                    class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white"
                >
                    リンク
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-neutral-400">
                    所有しているリンクの状態と直近30日の概要です。
                </p>
            </div>

            <section class="grid gap-4 md:grid-cols-3">
                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <div class="flex items-center justify-between">
                        <p
                            class="text-sm font-medium text-gray-500 dark:text-neutral-400"
                        >
                            リンク数
                        </p>
                        <LinkIcon class="size-4 text-gray-400" />
                    </div>
                    <p
                        class="mt-4 text-3xl font-semibold text-gray-950 dark:text-white"
                    >
                        {{ linksCount }}
                    </p>
                </div>

                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <div class="flex items-center justify-between">
                        <p
                            class="text-sm font-medium text-gray-500 dark:text-neutral-400"
                        >
                            合計アクセス数(30日)
                        </p>
                        <BarChart3 class="size-4 text-gray-400" />
                    </div>
                    <p
                        class="mt-4 text-3xl font-semibold text-gray-950 dark:text-white"
                    >
                        {{ totalAccessesLast30Days.toLocaleString() }}
                    </p>
                </div>

                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <div class="flex items-center justify-between">
                        <p
                            class="text-sm font-medium text-gray-500 dark:text-neutral-400"
                        >
                            合計クリック数(30日)
                        </p>
                        <MousePointerClick class="size-4 text-gray-400" />
                    </div>
                    <p
                        class="mt-4 text-3xl font-semibold text-gray-950 dark:text-white"
                    >
                        {{ totalClicksLast30Days.toLocaleString() }}
                    </p>
                </div>
            </section>

            <section
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div
                    class="border-b border-gray-200 px-5 py-4 dark:border-neutral-800"
                >
                    <h2
                        class="text-base font-semibold text-gray-950 dark:text-white"
                    >
                        所有リンク
                    </h2>
                </div>

                <div v-if="links.length === 0" class="px-5 py-16 text-center">
                    <LinkIcon
                        class="mx-auto size-12 text-gray-200 dark:text-neutral-700"
                    />
                    <p class="mt-3 text-sm font-semibold text-gray-400">
                        リンクはまだありません
                    </p>
                </div>

                <div
                    v-else
                    class="divide-y divide-gray-200 dark:divide-neutral-800"
                >
                    <article
                        v-for="link in links"
                        :key="link.id"
                        class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <Link
                                    :href="`/dashboard/links/${link.id}`"
                                    class="truncate text-base font-semibold text-gray-950 hover:underline dark:text-white"
                                >
                                    {{ link.display_name }}
                                </Link>
                                <Badge
                                    :variant="
                                        link.is_published
                                            ? 'default'
                                            : 'secondary'
                                    "
                                >
                                    {{ link.is_published ? '公開中' : '下書き' }}
                                </Badge>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">
                                /@{{ link.slug }}・{{ link.title?.name || '肩書き未設定' }}
                            </p>
                            <p
                                class="mt-2 line-clamp-2 text-sm text-gray-600 dark:text-neutral-300"
                            >
                                {{ link.bio || 'BIO 未設定' }}
                            </p>
                        </div>

                        <div class="flex shrink-0 flex-wrap gap-2">
                            <div
                                class="rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-600 dark:bg-neutral-800 dark:text-neutral-300"
                            >
                                {{ link.widgets_count }} widgets
                            </div>
                            <div
                                class="rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-600 dark:bg-neutral-800 dark:text-neutral-300"
                            >
                                更新 {{ formatDate(link.updated_at) }}
                            </div>
                            <Button as-child variant="outline" size="sm">
                                <Link :href="`/@${link.slug}`" target="_blank">
                                    <ExternalLink class="size-4" />
                                    表示
                                </Link>
                            </Button>
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </DashboardLayout>
</template>
