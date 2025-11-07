<script setup lang="ts">
interface LegendItem {
    name: string;
    display_name?: string;
    value: number;
    color: string;
    percentage: number;
}

interface Props {
    items: LegendItem[];
}

defineProps<Props>();
</script>

<template>
    <div class="space-y-2">
        <div
            v-for="item in items"
            :key="item.name"
            class="flex items-center justify-between text-sm"
        >
            <div class="flex items-center gap-2">
                <div
                    class="h-3 w-3 rounded-full"
                    :style="{ backgroundColor: item.color }"
                />
                <span class="text-foreground">{{ item.display_name || item.name }}</span>
            </div>
            <div class="flex items-center gap-2 text-muted-foreground">
                <span>{{ item.value }}</span>
                <span class="text-xs">({{ item.percentage.toFixed(1) }}%)</span>
            </div>
        </div>
    </div>
</template>