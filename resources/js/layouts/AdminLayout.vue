<script setup lang="ts">
import AppSidebarAdmin from '@/components/AppSidebarAdmin.vue';
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
            <AppSidebarAdmin />
        </template>
        <slot />
    </AppLayout>
</template>
