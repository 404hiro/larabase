<script setup lang="ts">
import PasswordController from '@/actions/App/Http/Controllers/Settings/PasswordController';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import { edit, update } from '@/routes/profile';
import { send } from '@/routes/verification';
import { Form, Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { getInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Image as ImageIcon, Trash2 } from 'lucide-vue-next';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'ユーザ設定',
        href: edit().url,
    },
];

const page = usePage();
const user = computed(() => page.props.auth.user);

const form = useForm({
    name: user.value.name,
    email: user.value.email,
    avatar: null as File | null,
    remove_avatar: false,
});

const avatarPreview = ref<string | null>(null);
const avatarInput = ref<InstanceType<typeof Input> | null>(null);
const passwordInput = ref<HTMLInputElement | null>(null);
const currentPasswordInput = ref<HTMLInputElement | null>(null);
const profileAvatarUrl = computed(() => {
    if (avatarPreview.value) {
        return avatarPreview.value;
    }

    if (form.remove_avatar) {
        return null;
    }

    return user.value.avatar_url;
});
const profileInitials = computed(() =>
    getInitials(form.name || user.value.name),
);

function selectNewAvatar() {
    (avatarInput.value?.$el as HTMLInputElement)?.click();
}

function updateAvatarPreview() {
    const avatar = (avatarInput.value?.$el as HTMLInputElement)?.files?.[0];

    if (!avatar) {
        return;
    }

    form.avatar = avatar;
    form.remove_avatar = false;

    const reader = new FileReader();

    reader.onload = (e) => {
        avatarPreview.value = e.target?.result as string;
    };

    reader.readAsDataURL(avatar);
}

function removeAvatar() {
    form.avatar = null;
    form.remove_avatar = true;
    avatarPreview.value = null;

    const input = avatarInput.value?.$el as HTMLInputElement | undefined;

    if (input) {
        input.value = '';
    }
}

function submit() {
    form.post(update().url, {
        onSuccess: () => {
            avatarPreview.value = null;
            form.remove_avatar = false;
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="ユーザ設定" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall
                    title="プロフィール情報"
                    description="このプロフィール情報はサポートコメントをする時に表示されます。"
                />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="avatar">アバター</Label>
                        <div class="flex items-center">
                            <div class="group relative size-[118px]">
                                <button
                                    type="button"
                                    class="relative flex h-full w-full cursor-pointer items-center justify-center overflow-hidden rounded-full border-4 border-gray-300 bg-gray-100 text-4xl font-bold text-gray-700 shadow-sm transition-colors hover:border-gray-400 focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                                    aria-label="プロフィール画像を選択"
                                    @click="selectNewAvatar"
                                >
                                    <img
                                        v-if="profileAvatarUrl"
                                        :src="profileAvatarUrl"
                                        :alt="form.name"
                                        class="h-full w-full object-cover"
                                        draggable="false"
                                    />
                                    <span v-else>{{ profileInitials }}</span>

                                    <span
                                        class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/0 text-white opacity-0 transition-all duration-150 group-hover:bg-black/20 group-hover:opacity-100"
                                    >
                                        <ImageIcon class="size-9" />
                                    </span>
                                </button>
                                <Button
                                    v-if="profileAvatarUrl"
                                    type="button"
                                    variant="secondary"
                                    size="icon"
                                    class="absolute -top-1 -right-1 size-8 rounded-full border border-gray-200 bg-white text-red-500 shadow-sm hover:bg-red-50 hover:text-red-600 dark:border-neutral-700 dark:bg-neutral-900 dark:hover:bg-red-950"
                                    aria-label="プロフィール画像を削除"
                                    @click="removeAvatar"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </div>
                            <Input
                                id="avatar"
                                type="file"
                                class="hidden"
                                ref="avatarInput"
                                @change="updateAvatarPreview"
                                name="avatar"
                                accept="image/*"
                            />
                        </div>
                        <InputError
                            class="mt-2"
                            :message="form.errors.avatar"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="name">名前</Label>
                        <Input
                            id="name"
                            class="mt-1 block w-full"
                            v-model="form.name"
                            required
                            autocomplete="name"
                            placeholder="氏名"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">メールアドレス</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="form.email"
                            required
                            autocomplete="email"
                            placeholder="メールアドレス"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            メールアドレスが未認証です。
                            <Link
                                :href="send()"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                認証メールを再送信するには、ここをクリックしてください。
                            </Link>
                        </p>

                        <div
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            新しい認証リンクがメールアドレスに送信されました。
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="form.processing"
                            data-test="update-profile-button"
                            >保存</Button
                        >

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="form.recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                保存しました。
                            </p>
                        </Transition>
                    </div>
                </form>
            </div>

            <div class="space-y-6">
                <HeadingSmall
                    title="パスワードの更新"
                    description="アカウントを安全に保つため、長くてランダムなパスワードを使用してください"
                />

                <Form
                    v-bind="PasswordController.update.form()"
                    :options="{
                        preserveScroll: true,
                    }"
                    reset-on-success
                    :reset-on-error="[
                        'password',
                        'password_confirmation',
                        'current_password',
                    ]"
                    class="space-y-6"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div class="grid gap-2">
                        <Label for="current_password">現在のパスワード</Label>
                        <Input
                            id="current_password"
                            ref="currentPasswordInput"
                            name="current_password"
                            type="password"
                            class="mt-1 block w-full"
                            autocomplete="current-password"
                            placeholder="現在のパスワード"
                        />
                        <InputError :message="errors.current_password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="new_password">新しいパスワード</Label>
                        <Input
                            id="new_password"
                            ref="passwordInput"
                            name="password"
                            type="password"
                            class="mt-1 block w-full"
                            autocomplete="new-password"
                            placeholder="新しいパスワード"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation"
                            >パスワードの確認</Label
                        >
                        <Input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            class="mt-1 block w-full"
                            autocomplete="new-password"
                            placeholder="パスワードを再入力"
                        />
                        <InputError :message="errors.password_confirmation" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-password-button"
                            >パスワードを保存</Button
                        >

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                保存しました。
                            </p>
                        </Transition>
                    </div>
                </Form>
            </div>

            <div class="space-y-6">
                <HeadingSmall
                    title="外観設定"
                    description="あなたのアカウントの外観設定を更新します"
                />
                <AppearanceTabs />
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
