<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { dashboard } from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DonutChart, ChartLegend } from '@/components/ui/chart';
import { ExternalLink } from 'lucide-vue-next';
import { computed } from 'vue';

interface UserStats {
    total: number;
    active: number;
    new_this_month: number;
}

interface RoleStats {
    name: string;
    display_name: string;
    count: number;
}

const props = defineProps<{
    userStats: UserStats;
    roleStats: RoleStats[];
}>();

// ドーナツグラフ用のデータを準備
const chartData = computed(() => {
    const colors = [
        'hsl(220, 70%, 50%)',  // blue
        'hsl(160, 60%, 45%)',  // emerald
        'hsl(30, 80%, 55%)',   // orange
        'hsl(280, 65%, 60%)',  // violet
        'hsl(340, 75%, 55%)',  // pink
    ];
    
    return props.roleStats.map((role, index) => ({
        name: role.display_name,
        value: role.count,
        fill: colors[index % colors.length],
    }));
});

// 凡例用のデータを準備
const legendData = computed(() => {
    const total = props.userStats.total;
    const colors = [
        'hsl(220, 70%, 50%)',
        'hsl(160, 60%, 45%)',
        'hsl(30, 80%, 55%)',
        'hsl(280, 65%, 60%)',
        'hsl(340, 75%, 55%)',
    ];
    
    return props.roleStats.map((role, index) => ({
        name: role.name,
        display_name: role.display_name,
        value: role.count,
        color: colors[index % colors.length],
        percentage: total > 0 ? (role.count / total) * 100 : 0,
    }));
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: '管理画面',
        href: dashboard().url,
    },
];
</script>

<template>
    <Head title="管理画面" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- ユーザ数ドーナツグラフ -->
            <div class="flex justify-center">
                <Card class="w-full max-w-2xl">
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle>ユーザ数</CardTitle>
                        <Link 
                            href="/admin/users" 
                            class="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground transition-colors"
                        >
                            ユーザ管理
                            <ExternalLink class="h-4 w-4" />
                        </Link>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-col lg:flex-row items-center gap-8">
                            <!-- ドーナツグラフ -->
                            <div class="flex-shrink-0">
                                <DonutChart
                                    v-if="chartData.length > 0"
                                    :data="chartData"
                                    :inner-radius="60"
                                    :outer-radius="100"
                                    :center-content="{
                                        title: userStats.total.toString(),
                                        subtitle: '総ユーザ数'
                                    }"
                                />
                                <div v-else class="flex items-center justify-center w-48 h-48 text-muted-foreground">
                                    <p>データがありません</p>
                                </div>
                            </div>
                            
                            <!-- 凡例 -->
                            <div class="flex-1 min-w-0">
                                <ChartLegend v-if="legendData.length > 0" :items="legendData" />
                                <div v-else class="text-center text-muted-foreground">
                                    <p>データがありません</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AdminLayout>
</template>
