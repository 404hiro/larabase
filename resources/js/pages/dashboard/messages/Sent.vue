<script setup lang="ts">
import Mailbox from './Mailbox.vue';
import type { DashboardMessage } from './types';

defineProps<{
    messages: DashboardMessage[];
}>();
</script>

<template>
    <Mailbox mailbox="sent" :messages="messages" />
</template>
