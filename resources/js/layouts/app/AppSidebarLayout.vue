<script setup lang="ts">
import AppShell from '@/components/AppShell.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="header">
        <slot name="sidebar" />
        <AppSidebarHeader :breadcrumbs="breadcrumbs" />

        <main class="w-full px-4 pt-0 sm:px-6 md:px-8 lg:ps-64">
            <slot />
        </main>
    </AppShell>
</template>
