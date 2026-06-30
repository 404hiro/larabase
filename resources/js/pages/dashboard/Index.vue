<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { index as walkthroughIndex } from '@/routes/walkthrough';
import { Head, Link } from '@inertiajs/vue3';
import {
    BarChart3,
    Link as LinkIcon,
    MessageCircle,
    MousePointerClick,
    TriangleAlert,
} from 'lucide-vue-next';
import { computed } from 'vue';

type ViewChartPoint = {
    date: string;
    label: string;
    views: number;
};

type ClickChartPoint = {
    date: string;
    label: string;
    clicks: number;
};

interface Props {
    linksCount?: number;
    messagesCount?: number;
    totalViewsLast30Days?: number;
    totalClicksLast30Days?: number;
    viewChartData?: ViewChartPoint[];
    clickChartData?: ClickChartPoint[];
    titleOptions: Array<{
        id: number;
        name: string;
    }>;
    userName: string;
}

const props = defineProps<Props>();

const linksCount = computed(() => props.linksCount ?? 0);
const messagesCount = computed(() => props.messagesCount ?? 0);
const totalViewsLast30Days = computed(() => props.totalViewsLast30Days ?? 0);
const totalClicksLast30Days = computed(() => props.totalClicksLast30Days ?? 0);
const viewChartData = computed(() => props.viewChartData ?? []);
const clickChartData = computed(() => props.clickChartData ?? []);

const maxViews = computed(() => {
    return Math.max(...viewChartData.value.map((point) => point.views), 1);
});

const maxClicks = computed(() => {
    return Math.max(...clickChartData.value.map((point) => point.clicks), 1);
});

const totalViewsLabel = computed(() => {
    return totalViewsLast30Days.value.toLocaleString();
});

const totalClicksLabel = computed(() => {
    return totalClicksLast30Days.value.toLocaleString();
});
</script>

<template>
    <Head title="ダッシュボード" />

    <DashboardLayout>
        <div class="space-y-6">
            <Alert
                v-if="linksCount === 0"
                class="border-yellow-200 bg-yellow-50 text-yellow-950 dark:border-yellow-900/60 dark:bg-yellow-950/30 dark:text-yellow-100"
            >
                <TriangleAlert class="size-4" />
                <AlertTitle>リンクがまだありません</AlertTitle>
                <AlertDescription
                    class="flex flex-col gap-4 text-yellow-800 sm:flex-row sm:items-center sm:justify-between dark:text-yellow-200"
                >
                    <span>
                        最初のリンクページを作成して、grid.link
                        で公開しましょう。
                    </span>

                    <Link :href="walkthroughIndex().url">
                        <Button
                            class="w-full bg-yellow-500 text-yellow-950 hover:bg-yellow-400 sm:w-auto"
                        >
                            リンクを作る
                        </Button>
                    </Link>
                </AlertDescription>
            </Alert>

            <div class="flex flex-col gap-4">
                <div>
                    <h1
                        class="mt-1 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white"
                    >
                        ダッシュボード
                    </h1>
                </div>
            </div>

            <section class="grid grid-cols-4 gap-4">
                <Link
                    href="/dashboard/links"
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs transition hover:border-gray-300 hover:bg-gray-50 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:bg-neutral-800/60"
                >
                    <div class="flex items-center justify-between">
                        <p
                            class="text-sm font-medium text-gray-500 dark:text-neutral-400"
                        >
                            リンク
                        </p>
                        <LinkIcon class="size-4 text-gray-400" />
                    </div>
                    <p
                        class="mt-4 text-3xl font-semibold text-gray-950 dark:text-white"
                    >
                        {{ linksCount.toLocaleString() }}
                    </p>
                    <p class="mt-2 text-xs text-gray-500 dark:text-neutral-400">
                        公開ページの作成数
                    </p>
                </Link>

                <Link
                    href="/dashboard/messages"
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs transition hover:border-gray-300 hover:bg-gray-50 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:bg-neutral-800/60"
                >
                    <div class="flex items-center justify-between">
                        <p
                            class="text-sm font-medium text-gray-500 dark:text-neutral-400"
                        >
                            メッセージ
                        </p>
                        <MessageCircle class="size-4 text-gray-400" />
                    </div>
                    <p
                        class="mt-4 text-3xl font-semibold text-gray-950 dark:text-white"
                    >
                        {{ messagesCount.toLocaleString() }}
                    </p>
                    <p class="mt-2 text-xs text-gray-500 dark:text-neutral-400">
                        受信したメッセージ総数
                    </p>
                </Link>

                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <div class="flex items-center justify-between">
                        <p
                            class="text-sm font-medium text-gray-500 dark:text-neutral-400"
                        >
                            閲覧数(30日)
                        </p>
                        <BarChart3 class="size-4 text-gray-400" />
                    </div>
                    <p
                        class="mt-4 text-3xl font-semibold text-gray-950 dark:text-white"
                    >
                        {{ totalViewsLabel }}
                    </p>
                    <p class="mt-2 text-xs text-gray-500 dark:text-neutral-400">
                        全リンクの合計閲覧数
                    </p>
                </div>

                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <div class="flex items-center justify-between">
                        <p
                            class="text-sm font-medium text-gray-500 dark:text-neutral-400"
                        >
                            クリック数(30日)
                        </p>
                        <MousePointerClick class="size-4 text-gray-400" />
                    </div>
                    <p
                        class="mt-4 text-3xl font-semibold text-gray-950 dark:text-white"
                    >
                        {{ totalClicksLabel }}
                    </p>
                    <p class="mt-2 text-xs text-gray-500 dark:text-neutral-400">
                        ウィジェットの合計クリック数
                    </p>
                </div>
            </section>

            <section
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div
                    class="flex flex-col gap-2 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800"
                >
                    <div>
                        <h2
                            class="text-base font-semibold text-gray-950 dark:text-white"
                        >
                            閲覧数の推移
                        </h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-neutral-400">
                            直近30日間の全リンク閲覧数を日別に表示しています。
                        </p>
                    </div>
                    <p
                        class="text-sm font-semibold text-gray-700 dark:text-neutral-200"
                    >
                        合計 {{ totalViewsLabel }}
                    </p>
                </div>

                <div class="px-5 py-6">
                    <div
                        v-if="totalViewsLast30Days === 0"
                        class="flex min-h-64 items-center justify-center rounded-lg border border-dashed border-gray-200 bg-gray-50 text-sm font-medium text-gray-400 dark:border-neutral-800 dark:bg-neutral-950"
                    >
                        まだ閲覧データがありません
                    </div>

                    <div v-else class="overflow-x-auto">
                        <div
                            class="flex h-72 min-w-[720px] items-end gap-2 border-b border-l border-gray-200 px-3 pt-4 dark:border-neutral-800"
                        >
                            <div
                                v-for="(point, index) in viewChartData"
                                :key="point.date"
                                class="flex h-full flex-1 flex-col items-center justify-end gap-2"
                            >
                                <div
                                    class="flex w-full flex-1 items-end justify-center"
                                    :title="`${point.label}: ${point.views.toLocaleString()}閲覧`"
                                >
                                    <div
                                        class="w-full max-w-5 rounded-t bg-blue-500 transition-colors hover:bg-blue-600 dark:bg-blue-400 dark:hover:bg-blue-300"
                                        :style="{
                                            height: `${Math.max((point.views / maxViews) * 100, point.views > 0 ? 4 : 0)}%`,
                                        }"
                                    ></div>
                                </div>
                                <span
                                    class="text-[11px] font-medium text-gray-400"
                                    :class="{
                                        'opacity-100':
                                            index % 5 === 0 ||
                                            index === viewChartData.length - 1,
                                        'opacity-0':
                                            index % 5 !== 0 &&
                                            index !== viewChartData.length - 1,
                                    }"
                                >
                                    {{ point.label }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div
                    class="flex flex-col gap-2 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800"
                >
                    <div>
                        <h2
                            class="text-base font-semibold text-gray-950 dark:text-white"
                        >
                            クリック数の推移
                        </h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-neutral-400">
                            直近30日間のウィジェットクリック数を日別に表示しています。
                        </p>
                    </div>
                    <p
                        class="text-sm font-semibold text-gray-700 dark:text-neutral-200"
                    >
                        合計 {{ totalClicksLabel }}
                    </p>
                </div>

                <div class="px-5 py-6">
                    <div
                        v-if="totalClicksLast30Days === 0"
                        class="flex min-h-64 items-center justify-center rounded-lg border border-dashed border-gray-200 bg-gray-50 text-sm font-medium text-gray-400 dark:border-neutral-800 dark:bg-neutral-950"
                    >
                        まだクリックデータがありません
                    </div>

                    <div v-else class="overflow-x-auto">
                        <div
                            class="flex h-72 min-w-[720px] items-end gap-2 border-b border-l border-gray-200 px-3 pt-4 dark:border-neutral-800"
                        >
                            <div
                                v-for="(point, index) in clickChartData"
                                :key="point.date"
                                class="flex h-full flex-1 flex-col items-center justify-end gap-2"
                            >
                                <div
                                    class="flex w-full flex-1 items-end justify-center"
                                    :title="`${point.label}: ${point.clicks.toLocaleString()}クリック`"
                                >
                                    <div
                                        class="w-full max-w-5 rounded-t bg-emerald-500 transition-colors hover:bg-emerald-600 dark:bg-emerald-400 dark:hover:bg-emerald-300"
                                        :style="{
                                            height: `${Math.max((point.clicks / maxClicks) * 100, point.clicks > 0 ? 4 : 0)}%`,
                                        }"
                                    ></div>
                                </div>
                                <span
                                    class="text-[11px] font-medium text-gray-400"
                                    :class="{
                                        'opacity-100':
                                            index % 5 === 0 ||
                                            index ===
                                                clickChartData.length - 1,
                                        'opacity-0':
                                            index % 5 !== 0 &&
                                            index !==
                                                clickChartData.length - 1,
                                    }"
                                >
                                    {{ point.label }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </DashboardLayout>
</template>
