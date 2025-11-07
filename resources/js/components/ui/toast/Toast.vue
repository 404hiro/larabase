<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'
import { X } from 'lucide-vue-next'

interface Props {
    variant?: 'default' | 'success' | 'error' | 'warning'
    title?: string
    description?: string
    onClose?: () => void
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
})

const variantClasses = computed(() => {
    const variants = {
        default: 'bg-background border-border',
        success: 'bg-emerald-50 border-emerald-200 dark:bg-emerald-950 dark:border-emerald-800',
        error: 'bg-red-50 border-red-200 dark:bg-red-950 dark:border-red-800',
        warning: 'bg-amber-50 border-amber-200 dark:bg-amber-950 dark:border-amber-800',
    }
    return variants[props.variant]
})

const iconColor = computed(() => {
    const colors = {
        default: 'text-foreground',
        success: 'text-emerald-600 dark:text-emerald-400',
        error: 'text-red-600 dark:text-red-400',
        warning: 'text-amber-600 dark:text-amber-400',
    }
    return colors[props.variant]
})
</script>

<template>
    <div
        :class="cn(
            'pointer-events-auto relative flex w-full items-center justify-between space-x-4 overflow-hidden rounded-md border p-4 pr-8 shadow-lg transition-all',
            variantClasses
        )"
    >
        <div class="flex-1 space-y-1">
            <div v-if="title" class="text-sm font-semibold" :class="iconColor">
                {{ title }}
            </div>
            <div v-if="description" class="text-sm opacity-90">
                {{ description }}
            </div>
        </div>
        <button
            v-if="onClose"
            @click="onClose"
            class="absolute right-2 top-2 rounded-md p-1 opacity-70 transition-opacity hover:opacity-100"
        >
            <X class="h-4 w-4" />
        </button>
    </div>
</template>