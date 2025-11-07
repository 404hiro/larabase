<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { MultiSelect } from '@/components/ui/select';
import { ArrowLeft, Save } from 'lucide-vue-next';

interface Role {
    id: number;
    name: string;
    display_name?: string;
}

interface User {
    id: number;
    name: string;
    account: string;
    email: string;
    email_verified_at: string | null;
    roles: Role[];
}

interface Props {
    user: User;
    roles: Role[];
}

const props = defineProps<Props>();

// デバッグ情報を出力
console.log('Edit page props:', props);
console.log('User data:', props.user);
console.log('User roles:', props.user.roles);
console.log('Available roles:', props.roles);

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
    {
        title: '編集',
        href: `/admin/users/${props.user.id}/edit`,
    },
];

const form = useForm({
    name: props.user.name,
    account: props.user.account,
    email: props.user.email,
    roles: (props.user.roles && Array.isArray(props.user.roles)) 
        ? props.user.roles.map(role => role.name) 
        : [],
    email_verified: !!props.user.email_verified_at,
});

console.log('Form initialized with roles:', form.roles);

const submit = () => {
    console.log('Submitting roles:', form.roles);
    form.put(`/admin/users/${props.user.id}`, {
        onSuccess: () => {
            console.log('Update successful');
        },
        onError: (errors) => {
            console.log('Update errors:', errors);
        }
    });
};
</script>

<template>
    <Head :title="`ユーザ編集 - ${user.name}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center justify-between">
                <Button variant="ghost" as-child>
                    <Link :href="`/admin/users/${user.id}`">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        ユーザ詳細に戻る
                    </Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>ユーザ編集</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="name">名前 *</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    :class="{ 'border-red-500': form.errors.name }"
                                />
                                <p v-if="form.errors.name" class="text-sm text-red-500">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="account">アカウント *</Label>
                                <Input
                                    id="account"
                                    v-model="form.account"
                                    type="text"
                                    required
                                    :class="{ 'border-red-500': form.errors.account }"
                                />
                                <p v-if="form.errors.account" class="text-sm text-red-500">
                                    {{ form.errors.account }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="email">メールアドレス *</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    :class="{ 'border-red-500': form.errors.email }"
                                />
                                <p v-if="form.errors.email" class="text-sm text-red-500">
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <div class="space-y-2 md:col-span-2">
                                <Label for="roles">権限</Label>
                                <MultiSelect
                                    id="roles"
                                    v-model="form.roles"
                                    :size="Math.min(roles.length, 6)"
                                    :class="{ 'border-red-500': form.errors.roles }"
                                >
                                    <option
                                        v-for="role in roles"
                                        :key="role.id"
                                        :value="role.name"
                                    >
                                        {{ role.display_name || role.name }}
                                    </option>
                                </MultiSelect>
                                <p class="text-xs text-muted-foreground">
                                    Ctrl/Cmd + クリックで複数選択できます
                                </p>
                                <p v-if="form.errors.roles" class="text-sm text-red-500">
                                    {{ form.errors.roles }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="email_verified"
                                v-model:checked="form.email_verified"
                            />
                            <Label for="email_verified">メール認証済み</Label>
                        </div>

                        <div class="flex justify-end gap-4">
                            <Button type="button" variant="outline" as-child>
                                <Link :href="`/admin/users/${user.id}`">キャンセル</Link>
                            </Button>
                            <Button type="submit" :disabled="form.processing">
                                <Save class="mr-2 h-4 w-4" />
                                {{ form.processing ? '更新中...' : '更新' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>