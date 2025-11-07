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
import { ArrowLeft, Save, Upload, X } from 'lucide-vue-next';
import { ref } from 'vue';

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
    avatar_url: string | null;
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
    avatar: null as File | null,
    roles: (props.user.roles && Array.isArray(props.user.roles)) 
        ? props.user.roles.map(role => role.name) 
        : [],
    email_verified: !!props.user.email_verified_at,
});

const avatarPreview = ref<string | null>(props.user.avatar_url);
const fileInput = ref<HTMLInputElement | null>(null);

const handleAvatarChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    
    if (file) {
        form.avatar = file;
        
        // プレビュー用のURLを作成
        const reader = new FileReader();
        reader.onload = (e) => {
            avatarPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const removeAvatar = () => {
    form.avatar = null;
    avatarPreview.value = props.user.avatar_url;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

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
                        <!-- アバター画像 -->
                        <div class="space-y-2">
                            <Label>アバター画像</Label>
                            <div class="flex items-center gap-4">
                                <!-- プレビュー -->
                                <div class="relative h-24 w-24 overflow-hidden rounded-full border-2 border-border bg-muted">
                                    <img
                                        v-if="avatarPreview"
                                        :src="avatarPreview"
                                        alt="Avatar preview"
                                        class="h-full w-full object-cover"
                                    />
                                    <div v-else class="flex h-full w-full items-center justify-center text-muted-foreground">
                                        <Upload class="h-8 w-8" />
                                    </div>
                                </div>
                                
                                <!-- アップロードボタン -->
                                <div class="flex flex-col gap-2">
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        @change="handleAvatarChange"
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="fileInput?.click()"
                                    >
                                        <Upload class="mr-2 h-4 w-4" />
                                        画像を選択
                                    </Button>
                                    <Button
                                        v-if="form.avatar"
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        @click="removeAvatar"
                                    >
                                        <X class="mr-2 h-4 w-4" />
                                        削除
                                    </Button>
                                    <p class="text-xs text-muted-foreground">
                                        JPG, PNG, GIF (最大2MB)
                                    </p>
                                </div>
                            </div>
                            <p v-if="form.errors.avatar" class="text-sm text-red-500">
                                {{ form.errors.avatar }}
                            </p>
                        </div>

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