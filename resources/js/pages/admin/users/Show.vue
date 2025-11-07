<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Edit, Trash2, ArrowLeft } from 'lucide-vue-next';

interface Role {
    id: number;
    name: string;
    display_name?: string;
}

interface Permission {
    id: number;
    name: string;
    display_name?: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    roles: Role[];
    permissions: Permission[];
}

interface Props {
    user: User;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: '管理画面',
        href: '/admin',
    },
    {
        title: 'ユーザ管理',
        href: '/admin/users',
    },
    {
        title: props.user.name,
        href: `/admin/users/${props.user.id}`,
    },
];

const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleString('ja-JP');
};

const deleteUser = () => {
    if (confirm(`${props.user.name} を削除してもよろしいですか？`)) {
        router.delete(`/admin/users/${props.user.id}`, {
            onSuccess: () => {
                router.visit('/admin/users');
            }
        });
    }
};
</script>

<template>
    <Head :title="`ユーザ詳細 - ${user.name}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center justify-between">
                <Button variant="ghost" as-child>
                    <Link href="/admin/users">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        ユーザ一覧に戻る
                    </Link>
                </Button>
                <div class="flex gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="`/admin/users/${user.id}/edit`">
                            <Edit class="mr-2 h-4 w-4" />
                            編集
                        </Link>
                    </Button>
                    <Button variant="destructive" @click="deleteUser">
                        <Trash2 class="mr-2 h-4 w-4" />
                        削除
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>ユーザ詳細情報</CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">ID</label>
                            <p class="mt-1 font-mono text-lg font-semibold">{{ user.id }}</p>
                        </div>
                        
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">名前</label>
                            <p class="mt-1 text-lg font-semibold">{{ user.name }}</p>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="text-sm font-medium text-muted-foreground">メールアドレス</label>
                            <p class="mt-1 text-lg">{{ user.email }}</p>
                        </div>
                        
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">ステータス</label>
                            <div class="mt-1">
                                <Badge
                                    :variant="user.email_verified_at ? 'default' : 'secondary'"
                                    class="text-sm"
                                >
                                    {{ user.email_verified_at ? 'アクティブ' : '未認証' }}
                                </Badge>
                            </div>
                        </div>
                        
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">メール認証日時</label>
                            <p class="mt-1">
                                {{ user.email_verified_at ? formatDateTime(user.email_verified_at) : '未認証' }}
                            </p>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="text-sm font-medium text-muted-foreground">権限（ロール）</label>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <Badge
                                    v-for="role in user.roles"
                                    :key="role.id"
                                    variant="outline"
                                    class="text-sm"
                                >
                                    {{ role.display_name || role.name }}
                                </Badge>
                                <Badge v-if="!user.roles.length" variant="secondary" class="text-sm">
                                    権限なし
                                </Badge>
                            </div>
                        </div>
                        
                        <div class="md:col-span-2" v-if="user.permissions && user.permissions.length">
                            <label class="text-sm font-medium text-muted-foreground">直接権限</label>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <Badge
                                    v-for="permission in user.permissions"
                                    :key="permission.id"
                                    variant="secondary"
                                    class="text-sm"
                                >
                                    {{ permission.display_name || permission.name }}
                                </Badge>
                            </div>
                        </div>
                        
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">登録日時</label>
                            <p class="mt-1">{{ formatDateTime(user.created_at) }}</p>
                        </div>
                        
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">最終更新日時</label>
                            <p class="mt-1">{{ formatDateTime(user.updated_at) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>