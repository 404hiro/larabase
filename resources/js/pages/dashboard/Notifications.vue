<script setup lang="ts">
import { Button } from '@/components/ui/button';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Bell, Check, CheckCheck } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';

const page = usePage();
const notificationsData = computed(() => page.props.notifications as any);
const notifications = computed(() => notificationsData.value?.data || []);

const markAsReadForm = useForm({});
const markAllAsReadForm = useForm({});

const markAsRead = (id: string) => {
    markAsReadForm.patch(`/dashboard/notifications/${id}/mark-as-read`, {
        preserveScroll: true,
    });
};

const markAllAsRead = () => {
    markAllAsReadForm.patch('/dashboard/notifications/mark-all-as-read', {
        preserveScroll: true,
    });
};

const formatDate = (isoString: string) => {
    return new Date(isoString).toLocaleString('ja-JP', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

onMounted(() => {
    if (page.props.unreadNotificationsCount > 0) {
        markAllAsRead();
    }
});
</script>

<template>
    <Head title="すべてのお知らせ" />

    <DashboardLayout>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                    お知らせ
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-neutral-400">
                    最新のアクティビティやお知らせを確認できます。
                </p>
            </div>
            <div>
                <Button 
                    v-if="page.props.unreadNotificationsCount > 0"
                    variant="outline" 
                    @click="markAllAsRead"
                    :disabled="markAllAsReadForm.processing"
                >
                    <CheckCheck class="size-4 mr-2" />
                    すべて既読にする
                </Button>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white shadow-xs dark:border-neutral-800 dark:bg-neutral-900">
            <div v-if="notifications.length === 0" class="px-5 py-16 text-center">
                <Bell class="mx-auto size-12 text-gray-200 dark:text-neutral-700" />
                <p class="mt-3 text-sm font-semibold text-gray-400">お知らせはありません</p>
            </div>
            
            <div v-else class="divide-y divide-gray-200 dark:divide-neutral-800">
                <div 
                    v-for="notification in notifications" 
                    :key="notification.id"
                    class="p-5 transition hover:bg-gray-50 dark:hover:bg-neutral-800/50"
                    :class="{ 'bg-blue-50/30 dark:bg-blue-900/10': !notification.read_at }"
                >
                    <div class="flex gap-4">
                        <div class="mt-1 flex-shrink-0">
                            <div class="flex size-10 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                                <Bell class="size-5" />
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <Link :href="notification.data.url" class="block">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ notification.data.title }}
                                </p>
                                <p class="mt-1 text-sm text-gray-600 dark:text-neutral-400">
                                    {{ notification.data.body }}
                                </p>
                            </Link>
                            <p class="mt-2 text-xs text-gray-400">
                                {{ formatDate(notification.created_at) }}
                            </p>
                        </div>
                        <div v-if="!notification.read_at" class="flex-shrink-0 self-center">
                            <Button 
                                variant="ghost" 
                                size="sm" 
                                class="text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/20"
                                @click="markAsRead(notification.id)"
                                :disabled="markAsReadForm.processing"
                            >
                                <Check class="size-4 mr-1" />
                                既読にする
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination could go here if needed -->
            <div v-if="notificationsData.links && notificationsData.links.length > 3" class="px-5 py-4 border-t border-gray-200 dark:border-neutral-800 flex items-center justify-center gap-1">
                <template v-for="(link, i) in notificationsData.links" :key="i">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="px-3 py-1 rounded-md text-sm border"
                        :class="link.active ? 'bg-gray-900 text-white border-gray-900 dark:bg-white dark:text-gray-900' : 'bg-white text-gray-600 hover:bg-gray-50 border-gray-200 dark:bg-neutral-900 dark:border-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-800'"
                        v-html="link.label"
                    />
                    <span 
                        v-else 
                        class="px-3 py-1 rounded-md text-sm border border-gray-200 text-gray-400 bg-gray-50 dark:bg-neutral-900 dark:border-neutral-800 dark:text-neutral-600"
                        v-html="link.label"
                    ></span>
                </template>
            </div>
        </div>
    </DashboardLayout>
</template>
