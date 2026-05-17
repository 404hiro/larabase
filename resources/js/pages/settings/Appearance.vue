<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
</script>

<template>
    <DashboardLayout>
        <Head title="外観設定" />
        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall
                    title="外観設定"
                    description="あなたのアカウントの外観設定を更新します"
                />
                <AppearanceTabs />
            </div>
        </SettingsLayout>
    </DashboardLayout>
</template>
