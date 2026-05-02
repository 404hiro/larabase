<script setup lang="ts">
import AppSidebarDashboard from '@/components/AppSidebarDashboard.vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <template #sidebar>
            <AppSidebarDashboard />
        </template>
        <slot />
    </AppLayout>
</template>
