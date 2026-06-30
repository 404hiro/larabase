<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Bell,
    Inbox,
    LayoutDashboard,
    Link as LinkIcon,
    Settings,
} from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const currentPath = computed(() => page.url.split('?')[0]);
const unreadMessagesCount = computed(() => (page.props.unreadMessagesCount ?? 0) as number);
const unreadNotificationsCount = computed(() => (page.props.unreadNotificationsCount ?? 0) as number);

const navItems = [
    {
        label: 'ダッシュボード',
        href: '/dashboard',
        icon: LayoutDashboard,
        isActive: () => currentPath.value === '/dashboard',
    },
    {
        label: 'メッセージ',
        href: '/dashboard/messages',
        icon: Inbox,
        isActive: () => currentPath.value === '/dashboard/messages',
        badge: unreadMessagesCount,
    },
    {
        label: 'リンク',
        href: '/dashboard/links',
        icon: LinkIcon,
        isActive: () => currentPath.value.startsWith('/dashboard/links'),
    },
    {
        label: 'お知らせ',
        href: '/dashboard/notifications',
        icon: Bell,
        isActive: () => currentPath.value === '/dashboard/notifications',
        badge: unreadNotificationsCount,
    },
    {
        label: '設定',
        href: '/settings',
        icon: Settings,
        isActive: () => currentPath.value.startsWith('/settings'),
    },
];
</script>

<template>
    <nav
        class="fixed right-0 bottom-0 left-0 z-50 flex h-16 border-t border-gray-200 bg-white/95 px-2 pb-safe backdrop-blur min-[1025px]:hidden dark:border-neutral-800 dark:bg-neutral-900/95"
    >
        <Link
            v-for="item in navItems"
            :key="item.label"
            :href="item.href"
            class="relative flex flex-1 flex-col items-center justify-center gap-1 transition-colors"
            :class="
                item.isActive()
                    ? 'text-black dark:text-white'
                    : 'text-gray-500 dark:text-neutral-400'
            "
        >
            <div class="relative">
                <component
                    :is="item.icon"
                    class="size-5"
                    :stroke-width="item.isActive() ? 2.5 : 2"
                />
                <div
                    v-if="item.badge && item.badge.value > 0"
                    class="absolute -right-1.5 -top-1.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white shadow-sm ring-2 ring-white dark:ring-neutral-900"
                >
                    {{ item.badge.value > 99 ? '99+' : item.badge.value }}
                </div>
            </div>
            <span class="text-[10px] leading-none font-medium">{{
                item.label
            }}</span>
        </Link>
    </nav>
</template>

<style scoped>
.pb-safe {
    padding-bottom: env(safe-area-inset-bottom);
}
</style>
