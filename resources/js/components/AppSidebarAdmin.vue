<script setup lang="ts">
import admin from '@/routes/admin';
import { type NavItem } from '@/types';
import { LayoutGrid, Tags, Users } from 'lucide-vue-next';
import AppSidebar from './AppSidebar.vue';

const mainNavItems: NavItem[] = [
    {
        title: '管理ダッシュボード',
        href: admin.dashboard().url,
        icon: LayoutGrid,
    },
    {
        title: 'ユーザ管理',
        href: admin.users.index().url,
        icon: Users,
    },
    {
        title: '職業管理',
        href: '/admin/titles',
        icon: Tags,
    },
];
</script>

<template>
    <AppSidebar :main-nav-items="mainNavItems" />
</template>
