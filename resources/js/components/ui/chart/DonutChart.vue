<script setup lang="ts">
import { computed } from 'vue';

interface ChartData {
    name: string;
    value: number;
    fill?: string;
}

interface CenterContent {
    title: string;
    subtitle?: string;
}

interface Props {
    data: ChartData[];
    innerRadius?: number;
    outerRadius?: number;
    centerContent?: CenterContent;
}

const props = withDefaults(defineProps<Props>(), {
    innerRadius: 60,
    outerRadius: 100,
});

const colors = [
    'hsl(220, 70%, 50%)',  // blue
    'hsl(160, 60%, 45%)',  // emerald
    'hsl(30, 80%, 55%)',   // orange
    'hsl(280, 65%, 60%)',  // violet
    'hsl(340, 75%, 55%)',  // pink
];

const chartData = computed(() => {
    const total = props.data.reduce((sum, item) => sum + item.value, 0);
    let currentAngle = 0;
    
    return props.data.map((item, index) => {
        const percentage = total > 0 ? (item.value / total) * 100 : 0;
        const angle = total > 0 ? (item.value / total) * 360 : 0;
        const startAngle = currentAngle;
        const endAngle = currentAngle + angle;
        currentAngle += angle;
        
        return {
            ...item,
            percentage,
            startAngle,
            endAngle,
            fill: item.fill || colors[index % colors.length],
        };
    });
});

const size = computed(() => props.outerRadius * 2);

const createArcPath = (startAngle: number, endAngle: number, innerRadius: number, outerRadius: number) => {
    const startAngleRad = (startAngle - 90) * (Math.PI / 180);
    const endAngleRad = (endAngle - 90) * (Math.PI / 180);
    
    const x1 = props.outerRadius + outerRadius * Math.cos(startAngleRad);
    const y1 = props.outerRadius + outerRadius * Math.sin(startAngleRad);
    const x2 = props.outerRadius + outerRadius * Math.cos(endAngleRad);
    const y2 = props.outerRadius + outerRadius * Math.sin(endAngleRad);
    
    const x3 = props.outerRadius + innerRadius * Math.cos(endAngleRad);
    const y3 = props.outerRadius + innerRadius * Math.sin(endAngleRad);
    const x4 = props.outerRadius + innerRadius * Math.cos(startAngleRad);
    const y4 = props.outerRadius + innerRadius * Math.sin(startAngleRad);
    
    const largeArc = endAngle - startAngle > 180 ? 1 : 0;
    
    return `M ${x1} ${y1} A ${outerRadius} ${outerRadius} 0 ${largeArc} 1 ${x2} ${y2} L ${x3} ${y3} A ${innerRadius} ${innerRadius} 0 ${largeArc} 0 ${x4} ${y4} Z`;
};
</script>

<template>
    <div class="relative">
        <svg :width="size" :height="size" class="overflow-visible">
            <g>
                <path
                    v-for="(item, index) in chartData"
                    :key="index"
                    :d="createArcPath(item.startAngle, item.endAngle, innerRadius, outerRadius)"
                    :fill="item.fill"
                    class="transition-opacity hover:opacity-80 cursor-pointer"
                />
            </g>
        </svg>
        
        <!-- Center content -->
        <div 
            class="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none"
        >
            <div v-if="centerContent?.title" class="text-3xl font-bold text-foreground">
                {{ centerContent.title }}
            </div>
            <div v-if="centerContent?.subtitle" class="text-sm text-muted-foreground mt-1">
                {{ centerContent.subtitle }}
            </div>
        </div>
    </div>
</template>