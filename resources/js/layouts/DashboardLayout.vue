<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import DashboardBottomNav from '@/components/DashboardBottomNav.vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Bell,
    ChevronDown,
    Inbox,
    LayoutDashboard,
    Link as LinkIcon,
    Settings,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

withDefaults(
    defineProps<{
        contentClass?: string;
        mainClass?: string;
    }>(),
    {
        contentClass: 'max-w-5xl',
        mainClass: 'p-4 md:p-6',
    },
);

type DashboardLink = {
    id: string; // UUID
    slug: string;
    display_name: string;
};

const page = usePage();
const auth = computed(() => page.props.auth || { user: { name: '', avatar_url: '' } });
const unreadMessagesCount = computed(() => (page.props.unreadMessagesCount ?? 0) as number);
const unreadNotificationsCount = computed(() => (page.props.unreadNotificationsCount ?? 0) as number);
const recentNotifications = computed(() => (page.props.recentNotifications ?? []) as any[]);
const currentPath = computed(() => page.url?.split('?')[0] || '');
const dashboardLinks = computed(() => {
    return (page.props.dashboardLinks ?? []) as DashboardLink[];
});
const isLinksOpen = ref(false);

const navigationItems = [
    {
        label: 'ダッシュボード',
        href: '/dashboard',
        icon: LayoutDashboard,
        isActive: () => currentPath.value === '/dashboard',
    },
];

const isActiveLinksSection = () => {
    return currentPath.value.startsWith('/dashboard/links');
};

const isActiveLinksOverview = () => {
    return currentPath.value === '/dashboard/links';
};

watch(
    () => currentPath.value,
    (path) => {
        if (path === '/dashboard') {
            isLinksOpen.value = false;
        } else {
            if (path.startsWith('/dashboard/links')) {
                isLinksOpen.value = true;
            }
        }
    },
    { immediate: true },
);

const toggleLinksSection = () => {
    isLinksOpen.value = !isLinksOpen.value;
};

const isActiveLink = (linkId: string) => {
    return currentPath.value === `/dashboard/links/${linkId}`;
};

const isActiveMessages = (linkId: string) => {
    return currentPath.value === `/dashboard/links/${linkId}/messages`;
};
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-neutral-950">
        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-black text-white transition-transform duration-200 ease-out min-[1025px]:static min-[1025px]:z-auto min-[1025px]:translate-x-0"
        >
            <div
                class="flex h-16 shrink-0 items-center gap-3 border-b border-white/10 px-6"
            >
                <div
                    class="flex size-9 items-center justify-center rounded-lg bg-white text-sm font-bold text-black"
                >
                    G
                </div>
                <div>
                    <p class="text-sm font-semibold text-white">grid.link</p>
                    <p class="text-xs text-white/50">クリエイター管理</p>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto p-3">
                <div class="space-y-1">
                    <Link
                        v-for="item in navigationItems"
                        :key="item.label"
                        :href="item.href"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition"
                        :class="
                            item.isActive()
                                ? 'bg-white text-black'
                                : 'text-white/65 hover:bg-white/10 hover:text-white'
                        "
                    >
                        <component :is="item.icon" class="size-4" />
                        {{ item.label }}
                    </Link>

                    <Link
                        href="/dashboard/messages"
                        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition"
                        :class="
                            currentPath === '/dashboard/messages'
                                ? 'bg-white text-black'
                                : 'text-white/65 hover:bg-white/10 hover:text-white'
                        "
                    >
                        <div class="flex items-center gap-3">
                            <Inbox class="size-4" />
                            メッセージ
                        </div>
                        <div
                            v-if="unreadMessagesCount > 0"
                            class="flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white"
                        >
                            {{ unreadMessagesCount }}
                        </div>
                    </Link>

                    <div class="pt-2">
                        <button
                            type="button"
                            id="dashboard-links-accordion"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-start text-sm font-medium transition"
                            :class="
                                isActiveLinksSection()
                                    ? 'bg-white text-black'
                                    : 'text-white/65 hover:bg-white/10 hover:text-white'
                            "
                            :aria-expanded="isLinksOpen"
                            aria-controls="dashboard-links-accordion-sub"
                            @click="toggleLinksSection"
                        >
                            <LinkIcon class="size-4" />
                            <span>リンク</span>
                            <ChevronDown
                                class="ms-auto size-4 transition-transform duration-200"
                                :class="isLinksOpen ? 'rotate-180' : ''"
                            />
                        </button>

                        <div
                            v-show="isLinksOpen"
                            id="dashboard-links-accordion-sub"
                            class="mt-1 space-y-1 pr-2 pl-7"
                            role="region"
                            aria-labelledby="dashboard-links-accordion"
                        >
                            <Link
                                href="/dashboard/links"
                                class="block w-full truncate rounded-lg px-3 py-1.5 text-sm font-medium transition"
                                :class="
                                    isActiveLinksOverview()
                                        ? 'bg-white text-black'
                                        : 'text-white/55 hover:bg-white/10 hover:text-white'
                                "
                            >
                                Overview
                            </Link>
                            <Link
                                v-for="link in dashboardLinks"
                                :key="link.id"
                                :href="`/dashboard/links/${link.id}`"
                                class="block w-full truncate rounded-lg px-3 py-1.5 text-sm font-medium transition"
                                :class="
                                    isActiveLink(link.id) ||
                                    isActiveMessages(link.id)
                                        ? 'bg-white text-black'
                                        : 'text-white/55 hover:bg-white/10 hover:text-white'
                                "
                            >
                                {{ link.display_name }}
                            </Link>
                        </div>
                    </div>

                    <div class="my-2 border-t border-white/10"></div>

                    <Link
                        href="/dashboard/notifications"
                        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition"
                        :class="
                            currentPath === '/dashboard/notifications'
                                ? 'bg-white text-black'
                                : 'text-white/65 hover:bg-white/10 hover:text-white'
                        "
                    >
                        <div class="flex items-center gap-3">
                            <Bell class="size-4" />
                            お知らせ
                        </div>
                        <div
                            v-if="unreadNotificationsCount > 0"
                            class="flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white"
                        >
                            {{ unreadNotificationsCount }}
                        </div>
                    </Link>

                    <Link
                        href="/settings"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition"
                        :class="
                            currentPath.startsWith('/settings')
                                ? 'bg-white text-black'
                                : 'text-white/65 hover:bg-white/10 hover:text-white'
                        "
                    >
                        <Settings class="size-4" />
                        アカウント設定
                    </Link>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex flex-1 flex-col overflow-hidden pb-16 min-[1025px]:pb-0">
            <header
                class="flex h-16 shrink-0 items-center justify-between border-b border-gray-200 bg-white/95 px-4 backdrop-blur md:px-6 dark:border-neutral-800 dark:bg-neutral-900/95"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex items-center gap-3 min-[1025px]:hidden"
                    >
                        <div
                            class="flex size-9 items-center justify-center rounded-lg bg-black text-sm font-bold text-white dark:bg-white dark:text-black"
                        >
                            G
                        </div>
                        <div>
                            <p
                                class="text-sm font-semibold text-gray-950 dark:text-white"
                            >
                                grid.link
                            </p>
                            <p class="text-xs text-gray-500 dark:text-neutral-400">
                                クリエイター管理
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="outline" size="icon" class="relative">
                                <Bell class="size-4" />
                                <span v-if="unreadNotificationsCount > 0" class="absolute top-1 right-1 flex size-2.5 rounded-full bg-red-500"></span>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-80 p-0">
                            <div class="px-4 py-3 border-b border-gray-100 dark:border-neutral-800">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">お知らせ</p>
                            </div>
                            <div class="py-2 max-h-80 overflow-y-auto">
                                <template v-if="recentNotifications && recentNotifications.length > 0">
                                    <div v-for="notification in recentNotifications" :key="notification.id" class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-neutral-800 border-b border-gray-50 last:border-0 dark:border-neutral-800/50">
                                        <Link :href="notification.data.url" class="block">
                                            <p class="text-sm text-gray-800 dark:text-neutral-200" :class="{ 'font-bold': !notification.read_at }">{{ notification.data.title }}</p>
                                            <p class="text-xs text-gray-500 dark:text-neutral-400 mt-1">{{ notification.data.body }}</p>
                                        </Link>
                                    </div>
                                </template>
                                <div v-else class="px-4 py-6 text-center text-sm text-gray-500">
                                    お知らせはありません
                                </div>
                            </div>
                            <div class="border-t border-gray-100 px-4 py-3 dark:border-neutral-800 text-center">
                                <Link href="/dashboard/notifications" class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                    すべてのお知らせ
                                </Link>
                            </div>
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button
                                type="button"
                                class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-sm font-medium text-gray-800 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-100"
                            >
                                <span
                                    class="flex size-7 items-center justify-center overflow-hidden rounded-full bg-gray-900 text-xs font-bold text-white"
                                >
                                    <img
                                        v-if="auth.user.avatar_url"
                                        :src="auth.user.avatar_url"
                                        :alt="auth.user.name"
                                        class="size-full object-cover"
                                    />
                                    <span v-else>
                                        {{ auth.user.name.charAt(0) }}
                                    </span>
                                </span>
                                <span class="hidden sm:inline">
                                    {{ auth.user.name }}
                                </span>
                                <ChevronDown
                                    class="hidden size-4 text-gray-400 sm:block"
                                />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto" :class="mainClass">
                <div class="mx-auto space-y-6" :class="contentClass">
                    <slot />
                </div>
            </main>
        </div>

        <DashboardBottomNav />
    </div>
</template>
