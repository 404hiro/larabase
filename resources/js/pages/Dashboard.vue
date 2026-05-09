<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Link as LinkIcon, TriangleAlert } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    linksCount: number;
    titleOptions: Array<{
        id: number;
        name: string;
    }>;
    userName: string;
}

const props = defineProps<Props>();

const isCreateDialogOpen = ref(false);

const form = useForm({
    slug: '',
    display_name: props.linksCount === 0 ? props.userName : '',
    title_id: '',
    bio: '',
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const submit = () => {
    form.post('/links', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="mx-auto flex h-full w-full max-w-6xl flex-1 flex-col gap-6 px-4 md:p-8"
        >
            <Alert
                v-if="linksCount === 0"
                class="border-yellow-200 bg-yellow-50 text-yellow-950 dark:border-yellow-900/60 dark:bg-yellow-950/30 dark:text-yellow-100"
            >
                <TriangleAlert class="size-4" />
                <AlertTitle>プロフィールがまだありません</AlertTitle>
                <AlertDescription
                    class="flex flex-col gap-4 text-yellow-800 sm:flex-row sm:items-center sm:justify-between dark:text-yellow-200"
                >
                    <span>
                        最初のプロフィールページを作成して、grid.link
                        で公開しましょう。
                    </span>

                    <Dialog v-model:open="isCreateDialogOpen">
                        <DialogTrigger as-child>
                            <Button
                                class="w-full bg-yellow-500 text-yellow-950 hover:bg-yellow-400 sm:w-auto"
                            >
                                リンクを作る
                            </Button>
                        </DialogTrigger>

                        <DialogContent class="sm:max-w-lg">
                            <form class="space-y-6" @submit.prevent="submit">
                                <DialogHeader>
                                    <DialogTitle>リンクを作る</DialogTitle>
                                    <DialogDescription>
                                        プロフィールページの URL
                                        と表示情報を入力してください。
                                    </DialogDescription>
                                </DialogHeader>

                                <div class="grid gap-4">
                                    <div class="grid gap-2">
                                        <Label for="slug">リンクURL</Label>
                                        <div class="flex items-center">
                                            <span
                                                class="inline-flex h-9 items-center rounded-s-md border border-e-0 border-input bg-muted px-3 text-sm text-muted-foreground"
                                            >
                                                /@
                                            </span>
                                            <Input
                                                id="slug"
                                                v-model="form.slug"
                                                class="rounded-s-none"
                                                placeholder="my-link"
                                            />
                                        </div>
                                        <InputError
                                            :message="form.errors.slug"
                                        />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="display_name">表示名</Label>
                                        <Input
                                            id="display_name"
                                            v-model="form.display_name"
                                            placeholder="My Profile"
                                        />
                                        <InputError
                                            :message="form.errors.display_name"
                                        />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="title_id">職業</Label>
                                        <Select
                                            id="title_id"
                                            v-model="form.title_id"
                                        >
                                            <option value="">
                                                職業を選択しない
                                            </option>
                                            <option
                                                v-for="title in titleOptions"
                                                :key="title.id"
                                                :value="String(title.id)"
                                            >
                                                {{ title.name }}
                                            </option>
                                        </Select>
                                        <InputError
                                            :message="form.errors.title_id"
                                        />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="bio">BIO</Label>
                                        <textarea
                                            id="bio"
                                            v-model="form.bio"
                                            rows="4"
                                            maxlength="280"
                                            class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:bg-input/30 dark:aria-invalid:ring-destructive/40"
                                            placeholder="自己紹介を入力"
                                        />
                                        <div
                                            class="flex items-center justify-between gap-3"
                                        >
                                            <InputError
                                                :message="form.errors.bio"
                                            />
                                            <span
                                                class="ms-auto text-xs text-muted-foreground"
                                            >
                                                {{ form.bio.length }}/280
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <DialogFooter>
                                    <Button
                                        type="submit"
                                        :disabled="form.processing"
                                    >
                                        作成する
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </AlertDescription>
            </Alert>

            <div>
                <h1
                    class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white"
                >
                    ダッシュボード
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-neutral-400">
                    作成済みプロフィールページの概要を確認できます。
                </p>
            </div>

            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    class="rounded-lg border border-gray-200 bg-white p-5 dark:border-neutral-700 dark:bg-neutral-800"
                >
                    <div
                        class="text-sm font-medium text-gray-500 dark:text-neutral-400"
                    >
                        プロフィール数
                    </div>
                    <div
                        class="mt-3 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white"
                    >
                        {{ linksCount }}
                    </div>
                    <p class="mt-2 text-sm text-gray-500 dark:text-neutral-400">
                        ログイン中のユーザに紐づく Links テーブルの件数
                    </p>
                    <Button
                        v-if="linksCount > 0"
                        as-child
                        variant="outline"
                        class="mt-4 w-full"
                    >
                        <Link href="/links">
                            <LinkIcon class="size-4" />
                            リンクを管理
                        </Link>
                    </Button>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
