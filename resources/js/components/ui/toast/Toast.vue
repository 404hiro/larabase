<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'
import { X, CheckCircle2, AlertCircle } from 'lucide-vue-next'

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
        default: 'bg-white border-gray-200',
        success: 'bg-emerald-50 border-emerald-200 dark:bg-emerald-950 dark:border-emerald-800',
        error: 'bg-red-50 border-red-200 dark:bg-red-950 dark:border-red-800',
        warning: 'bg-amber-50 border-amber-200 dark:bg-amber-950 dark:border-amber-800',
    }
    return variants[props.variant]
})

const iconColor = computed(() => {
    const colors = {
        default: 'text-gray-600',
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
            'pointer-events-auto relative flex w-full items-center space-x-3 overflow-hidden rounded-2xl border p-4 shadow-xl transition-all',
            variantClasses
        )"
    >
        <div v-if="variant === 'success'" :class="iconColor">
            <CheckCircle2 class="h-5 w-5" />
        </div>
        <div v-else-if="variant === 'error'" :class="iconColor">
            <AlertCircle class="h-5 w-5" />
        </div>

        <div class="flex-1 min-w-0">
            <div v-if="title" class="text-sm font-bold" :class="iconColor">
                {{ title }}
            </div>
            <div v-if="description" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ description }}
            </div>
        </div>
        <button
            v-if="onClose"
            @click="onClose"
            class="ml-auto rounded-md p-1 opacity-40 transition-opacity hover:opacity-100"
        >
            <X class="h-4 w-4" />
        </button>
    </div>
</template>