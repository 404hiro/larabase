<script setup lang="ts">
import Heading from '@/components/Heading.vue';
</script>

<template>
    <div class="px-4 py-6">
        <Heading title="アカウント設定" description="アカウント設定を管理します。" />

        <section class="mt-8 max-w-xl space-y-12">
            <slot />
        </section>
    </div>
</template>
