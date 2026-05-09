<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { MultiSelect } from '@/components/ui/select';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
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
    google_id: string;
    avatar_url: string | null;
    roles: Role[];
}

interface Props {
    user: User;
    roles: Role[];
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
    {
        title: '編集',
        href: `/admin/users/${props.user.id}/edit`,
    },
];

const form = useForm({
    name: props.user.name,
    avatar: null as File | null,
    roles:
        props.user.roles && Array.isArray(props.user.roles)
            ? props.user.roles.map((role) => role.name)
            : [],
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

const submit = () => {
    // 常にPOSTメソッドを使用し、_methodでPUTをエミュレート
    form.transform((data) => ({
        ...data,
        _method: 'PUT',
    })).post(`/admin/users/${props.user.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`ユーザ編集 - ${user.name}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
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
                                <div
                                    class="relative h-24 w-24 overflow-hidden rounded-full border-2 border-border bg-muted"
                                >
                                    <img
                                        v-if="avatarPreview"
                                        :src="avatarPreview"
                                        alt="Avatar preview"
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex h-full w-full items-center justify-center text-muted-foreground"
                                    >
                                        <Upload class="h-8 w-8" />
                                    </div>
                                </div>

                                <!-- アップロードボタン -->
                                <div class="flex flex-col gap-2">
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        accept="image/*,.apng"
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
                            <p
                                v-if="form.errors.avatar"
                                class="text-sm text-red-500"
                            >
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
                                    :class="{
                                        'border-red-500': form.errors.name,
                                    }"
                                />
                                <p
                                    v-if="form.errors.name"
                                    class="text-sm text-red-500"
                                >
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="google_id">Google ID</Label>
                                <Input
                                    id="google_id"
                                    :value="user.google_id"
                                    type="text"
                                    disabled
                                    class="bg-muted font-mono"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Google IDは編集できません。
                                </p>
                            </div>

                            <div class="space-y-2 md:col-span-2">
                                <Label for="roles">権限</Label>
                                <MultiSelect
                                    id="roles"
                                    v-model="form.roles"
                                    :size="Math.min(roles.length, 6)"
                                    :class="{
                                        'border-red-500': form.errors.roles,
                                    }"
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
                                <p
                                    v-if="form.errors.roles"
                                    class="text-sm text-red-500"
                                >
                                    {{ form.errors.roles }}
                                </p>
                            </div>
                        </div>

                        <div class="flex justify-end gap-4">
                            <Button type="button" variant="outline" as-child>
                                <Link :href="`/admin/users/${user.id}`"
                                    >キャンセル</Link
                                >
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
