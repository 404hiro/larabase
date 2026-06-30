<script setup lang="ts">
import Mailbox from './messages/Mailbox.vue';
import type { DashboardMessage } from './messages/types';

defineProps<{
    initialMailbox: 'inbox' | 'sent';
    inboxMessages: DashboardMessage[];
    sentMessages: DashboardMessage[];
}>();
</script>

<template>
    <Mailbox
        :initial-mailbox="initialMailbox"
        :inbox-messages="inboxMessages"
        :sent-messages="sentMessages"
    />
</template>
