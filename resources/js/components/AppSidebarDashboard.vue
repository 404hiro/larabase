<script setup lang="ts">
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import {
    BookOpen,
    Folder,
    LayoutGrid,
    Link as LinkIcon,
    UserCog,
} from 'lucide-vue-next';
import AppSidebar from './AppSidebar.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'ダッシュボード',
        href: dashboard().url,
        icon: LayoutGrid,
    },
    {
        title: 'リンク',
        href: '/links',
        icon: LinkIcon,
    },
    {
        title: 'ユーザ設定',
        href: '/settings',
        icon: UserCog,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <AppSidebar
        :main-nav-items="mainNavItems"
        :footer-nav-items="footerNavItems"
    />
</template>
