<script setup lang="ts">
import Mailbox from './messages/Mailbox.vue';
import type { DashboardMessage } from './messages/types';

defineProps<{
    mailbox: 'inbox' | 'sent';
    messages: DashboardMessage[];
}>();
</script>

<template>
    <Mailbox :mailbox="mailbox" :messages="messages" />
</template>
