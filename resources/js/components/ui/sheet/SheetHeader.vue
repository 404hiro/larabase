<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: HTMLAttributes['class'] }>()
</script>

<template>
  <div
    data-slot="sheet-header"
    :class="cn('bg-background sticky top-0 z-10 flex flex-col gap-1.5 p-4', props.class)"
  >
    <slot />
  </div>
</template>
