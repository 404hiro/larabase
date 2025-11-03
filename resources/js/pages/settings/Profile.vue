<script setup lang="ts">
import { edit, update } from '@/routes/profile';
import { send } from '@/routes/verification';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { getInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'プロフィール設定',
        href: edit().url,
    },
];

const page = usePage();
const user = computed(() => page.props.auth.user);

const form = useForm({
    name: user.value.name,
    account: user.value.account,
    email: user.value.email,
    avatar: null as File | null,
});

const avatarPreview = ref<string | null>(null);
const avatarInput = ref<InstanceType<typeof Input> | null>(null);

function selectNewAvatar() {
    (avatarInput.value?.$el as HTMLInputElement)?.click();
}

function updateAvatarPreview() {
    const avatar = (avatarInput.value?.$el as HTMLInputElement)?.files?.[0];

    if (!avatar) {
        return;
    }

    form.avatar = avatar;

    const reader = new FileReader();

    reader.onload = (e) => {
        avatarPreview.value = e.target?.result as string;
    };

    reader.readAsDataURL(avatar);
}

function submit() {
    form.post(update().url, {
        onSuccess: () => {
            avatarPreview.value = null;
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="プロフィール設定" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall
                    title="プロフィール情報"
                    description="名前、アカウント名、メールアドレス、アバターを更新します"
                />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="avatar">アバター</Label>
                        <div class="flex items-center gap-4">
                            <Avatar class="h-20 w-20">
                                <AvatarImage
                                    :src="avatarPreview ?? user.avatar_url"
                                />
                                <AvatarFallback>
                                    {{ getInitials(user.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <Input
                                id="avatar"
                                type="file"
                                class="hidden"
                                ref="avatarInput"
                                @change="updateAvatarPreview"
                                name="avatar"
                            />
                            <Button
                                type="button"
                                variant="outline"
                                @click="selectNewAvatar"
                                >新しいアバターを選択</Button
                            >
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
                        <Label for="account">アカウント名</Label>
                        <Input
                            id="account"
                            class="mt-1 block w-full"
                            v-model="form.account"
                            required
                            autocomplete="username"
                            placeholder="例: your_account"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.account"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">メールアドレス</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="form.email"
                            required
                            autocomplete="username"
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

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
