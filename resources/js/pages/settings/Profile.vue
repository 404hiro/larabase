<script setup lang="ts">
import { update } from '@/routes/profile';
import { onboarding as stripeConnectOnboarding } from '@/routes/stripe-connect';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { getInitials } from '@/composables/useInitials';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { compressImage } from '@/utils/imageCompression';
import { Image as ImageIcon, Trash2 } from 'lucide-vue-next';

interface Props {
    status?: string;
    stripeAccount?: {
        details_submitted: boolean;
        charges_enabled: boolean;
        payouts_enabled: boolean;
        onboarding_completed_at: string | null;
    } | null;
}

const props = defineProps<Props>();

const page = usePage();
const user = computed(() => page.props.auth.user);
const stripeAccount = computed(() => props.stripeAccount);

const form = useForm({
    name: user.value.name,
    avatar: null as File | null,
    remove_avatar: false,
});
const stripeForm = useForm({});

const avatarPreview = ref<string | null>(null);
const avatarInput = ref<InstanceType<typeof Input> | null>(null);

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

async function updateAvatarPreview() {
    const avatar = (avatarInput.value?.$el as HTMLInputElement)?.files?.[0];

    if (!avatar) {
        return;
    }

    const compressedAvatar = await compressImage(avatar, { preset: 'avatar' });

    if (!compressedAvatar) {
        return;
    }

    form.avatar = compressedAvatar;
    form.remove_avatar = false;

    const reader = new FileReader();

    reader.onload = (e) => {
        avatarPreview.value = e.target?.result as string;
    };

    reader.readAsDataURL(compressedAvatar);
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

const isStripeReady = computed(() => {
    return Boolean(
        stripeAccount.value?.charges_enabled &&
            stripeAccount.value?.payouts_enabled,
    );
});

const stripeStatusLabel = computed(() => {
    if (isStripeReady.value) {
        return '有効';
    }

    if (stripeAccount.value?.details_submitted) {
        return '確認中';
    }

    if (stripeAccount.value) {
        return '設定中';
    }

    return '未設定';
});

const stripeButtonLabel = computed(() => {
    return stripeAccount.value
        ? 'StripeConnection設定を再開'
        : 'StripeConnection設定をする';
});
const stripeError = computed(() => {
    return (stripeForm.errors as Record<string, string | undefined>).stripe;
});

function startStripeOnboarding() {
    stripeForm.post(stripeConnectOnboarding().url);
}
</script>

<template>
    <DashboardLayout>
        <Head title="アカウント設定" />

        <SettingsLayout>
            <Card>
                <CardContent class="space-y-6">
                    <HeadingSmall
                        title="プロフィール情報"
                        description="このプロフィール情報はメッセージを送る際に使用されます。"
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
                                    accept="image/*,.apng"
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
                            <InputError
                                class="mt-2"
                                :message="form.errors.name"
                            />
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
                </CardContent>
            </Card>

            <Card id="revenue">
                <CardContent class="space-y-6">
                    <HeadingSmall
                        title="収益設定"
                        description="Stripe Connectを設定すると、メッセージに添えられた差し入れを受け取れます。"
                    />

                    <div
                        class="grid gap-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-neutral-800 dark:bg-neutral-900"
                    >
                        <div
                            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <p
                                    class="text-sm font-semibold text-gray-950 dark:text-white"
                                >
                                    Stripe Connect
                                </p>
                                <p
                                    class="mt-1 text-sm text-gray-500 dark:text-neutral-400"
                                >
                                    現在の状態: {{ stripeStatusLabel }}
                                </p>
                            </div>

                            <Button
                                type="button"
                                class="w-full sm:w-auto"
                                :disabled="stripeForm.processing"
                                @click="startStripeOnboarding"
                            >
                                {{ stripeButtonLabel }}
                            </Button>
                        </div>

                        <div
                            v-if="stripeAccount"
                            class="grid gap-2 text-sm text-gray-600 sm:grid-cols-3 dark:text-neutral-300"
                        >
                            <div>
                                <span class="font-medium">本人確認</span>
                                <p>
                                    {{
                                        stripeAccount.details_submitted
                                            ? '送信済み'
                                            : '未送信'
                                    }}
                                </p>
                            </div>
                            <div>
                                <span class="font-medium">決済受付</span>
                                <p>
                                    {{
                                        stripeAccount.charges_enabled
                                            ? '有効'
                                            : '無効'
                                    }}
                                </p>
                            </div>
                            <div>
                                <span class="font-medium">入金</span>
                                <p>
                                    {{
                                        stripeAccount.payouts_enabled
                                            ? '有効'
                                            : '無効'
                                    }}
                                </p>
                            </div>
                        </div>

                        <InputError
                            class="mt-1"
                            :message="stripeError"
                        />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent>
                    <DeleteUser />
                </CardContent>
            </Card>
        </SettingsLayout>
    </DashboardLayout>
</template>
