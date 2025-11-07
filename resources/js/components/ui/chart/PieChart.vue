<script setup lang="ts">
import { computed } from 'vue';

interface ChartData {
    name: string;
    value: number;
    color?: string;
}

interface Props {
    data: ChartData[];
    size?: number;
    strokeWidth?: number;
    centerText?: string;
    centerSubtext?: string;
}

const props = withDefaults(defineProps<Props>(), {
    size: 200,
    strokeWidth: 40,
});

// デフォルトカラーパレット
const defaultColors = [
    '#3b82f6', // blue-500
    '#ef4444', // red-500
    '#10b981', // emerald-500
    '#f59e0b', // amber-500
    '#8b5cf6', // violet-500
    '#06b6d4', // cyan-500
    '#f97316', // orange-500
    '#84cc16', // lime-500
];

const chartData = computed(() => {
    const total = props.data.reduce((sum, item) => sum + item.value, 0);
    let currentAngle = 0;
    
    return props.data.map((item, index) => {
        const percentage = total > 0 ? (item.value / total) * 100 : 0;
        const angle = total > 0 ? (item.value / total) * 360 : 0;
        const startAngle = currentAngle;
        currentAngle += angle;
        
        return {
            ...item,
            percentage,
            angle,
            startAngle,
            color: item.color || defaultColors[index % defaultColors.length],
        };
    });
});

const radius = computed(() => (props.size - props.strokeWidth) / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);

const createPath = (startAngle: number, angle: number) => {
    const start = (startAngle - 90) * (Math.PI / 180);
    const end = (startAngle + angle - 90) * (Math.PI / 180);
    
    const x1 = props.size / 2 + radius.value * Math.cos(start);
    const y1 = props.size / 2 + radius.value * Math.sin(start);
    const x2 = props.size / 2 + radius.value * Math.cos(end);
    const y2 = props.size / 2 + radius.value * Math.sin(end);
    
    const largeArc = angle > 180 ? 1 : 0;
    
    return `M ${props.size / 2} ${props.size / 2} L ${x1} ${y1} A ${radius.value} ${radius.value} 0 ${largeArc} 1 ${x2} ${y2} Z`;
};
</script>

<template>
    <div class="relative inline-block">
        <svg :width="size" :height="size" class="transform -rotate-90">
            <path
                v-for="(item, index) in chartData"
                :key="index"
                :d="createPath(item.startAngle, item.angle)"
                :fill="item.color"
                class="transition-opacity hover:opacity-80"
            />
        </svg>
        
        <!-- 中央のテキスト -->
        <div 
            class="absolute inset-0 flex flex-col items-center justify-center text-center"
            :style="{ width: size + 'px', height: size + 'px' }"
        >
            <div v-if="centerText" class="text-2xl font-bold text-foreground">
                {{ centerText }}
            </div>
            <div v-if="centerSubtext" class="text-sm text-muted-foreground">
                {{ centerSubtext }}
            </div>
        </div>
    </div>
</template>