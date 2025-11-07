<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Select } from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Plus, Search, Eye, Edit, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Role {
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
    roles: Role[];
}

interface PaginatedUsers {
    data: User[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

interface Props {
    users: PaginatedUsers;
    filters: {
        search?: string;
        status?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: '管理画面',
        href: '/admin',
    },
    {
        title: 'ユーザ管理',
        href: '/admin/users',
    },
];

// フィルター変更時の処理
let timeout: number;
watch([search, status], () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get('/admin/users', {
            search: search.value || undefined,
            status: status.value || undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('ja-JP');
};

const deleteUser = (user: User) => {
    if (confirm(`${user.name} を削除してもよろしいですか？`)) {
        router.delete(`/admin/users/${user.id}`);
    }
};
</script>

<template>
    <Head title="ユーザ管理" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>ユーザ管理</CardTitle>
                        <Button as-child>
                            <Link href="/admin/users/create">
                                <Plus class="mr-2 h-4 w-4" />
                                新規ユーザ作成
                            </Link>
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <!-- フィルター -->
                    <div class="mb-6 flex gap-4">
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="search"
                                placeholder="名前またはメールアドレスで検索..."
                                class="pl-10  h-10"
                            />
                        </div>
                        <Select v-model="status" class="w-48">
                            <option value="">すべて</option>
                            <option value="active">アクティブ</option>
                            <option value="inactive">未認証</option>
                        </Select>
                    </div>

                    <!-- ユーザ一覧テーブル -->
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>ID</TableHead>
                                    <TableHead>名前</TableHead>
                                    <TableHead>メールアドレス</TableHead>
                                    <TableHead>権限</TableHead>
                                    <TableHead>ステータス</TableHead>
                                    <TableHead>登録日</TableHead>
                                    <TableHead class="text-right">操作</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="user in users.data" :key="user.id">
                                    <TableCell class="font-mono text-sm">{{ user.id }}</TableCell>
                                    <TableCell class="font-medium">{{ user.name }}</TableCell>
                                    <TableCell>{{ user.email }}</TableCell>
                                    <TableCell>
                                        <div class="flex flex-wrap gap-1">
                                            <Badge
                                                v-for="role in user.roles"
                                                :key="role.id"
                                                variant="outline"
                                                class="text-xs"
                                            >
                                                {{ role.display_name || role.name }}
                                            </Badge>
                                            <Badge v-if="!user.roles.length" variant="secondary" class="text-xs">
                                                権限なし
                                            </Badge>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            :variant="user.email_verified_at ? 'default' : 'secondary'"
                                        >
                                            {{ user.email_verified_at ? 'アクティブ' : '未認証' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>{{ formatDate(user.created_at) }}</TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button variant="ghost" size="sm" as-child>
                                                <Link :href="`/admin/users/${user.id}`">
                                                    <Eye class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button variant="ghost" size="sm" as-child>
                                                <Link :href="`/admin/users/${user.id}/edit`">
                                                    <Edit class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click="deleteUser(user)"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- ページネーション -->
                    <div class="mt-4 flex items-center justify-between">
                        <div class="text-sm text-muted-foreground">
                            {{ users.total }}件中 {{ (users.current_page - 1) * users.per_page + 1 }}〜{{ Math.min(users.current_page * users.per_page, users.total) }}件を表示
                        </div>
                        <div class="flex gap-2">
                            <Button
                                v-for="link in users.links"
                                :key="link.label"
                                :variant="link.active ? 'default' : 'outline'"
                                size="sm"
                                :disabled="!link.url"
                                @click="link.url && router.visit(link.url)"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>