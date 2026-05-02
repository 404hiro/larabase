<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ExternalLink,
    Link as LinkIcon,
    Pencil,
    Plus,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface ManagedLink {
    id: number;
    slug: string;
    display_name: string;
    bio?: string | null;
    avatar_url?: string | null;
    is_published: boolean;
    widgets_count: number;
    updated_at?: string | null;
}

const props = defineProps<{
    links: ManagedLink[];
    userName: string;
}>();

const isCreateDialogOpen = ref(false);

const form = useForm({
    slug: '',
    display_name: props.links.length === 0 ? props.userName : '',
    bio: '',
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'リンク',
        href: '/links',
    },
];

const hasLinks = computed(() => props.links.length > 0);

const formatDate = (value?: string | null) => {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat('ja-JP', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(new Date(value));
};

const submit = () => {
    form.post('/links', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            isCreateDialogOpen.value = false;
        },
    });
};
</script>

<template>
    <Head title="リンク" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="mx-auto flex h-full w-full max-w-6xl flex-1 flex-col gap-6 px-4 py-6 md:p-8"
        >
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1
                        class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white"
                    >
                        リンク
                    </h1>
                    <p
                        class="mt-1 text-sm text-gray-500 dark:text-neutral-400"
                    >
                        {{ props.links.length }} 件のプロフィールページ
                    </p>
                </div>

                <Dialog v-model:open="isCreateDialogOpen">
                    <DialogTrigger as-child>
                        <Button class="w-full sm:w-auto">
                            <Plus class="size-4" />
                            作成
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
            </div>

            <div
                v-if="!hasLinks"
                class="flex min-h-80 flex-col items-center justify-center rounded-lg border border-dashed border-gray-300 bg-white px-6 text-center dark:border-neutral-700 dark:bg-neutral-900"
            >
                <LinkIcon
                    class="size-10 text-gray-400 dark:text-neutral-500"
                />
                <h2
                    class="mt-4 text-lg font-semibold text-gray-900 dark:text-white"
                >
                    リンクがありません
                </h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-neutral-400">
                    最初のプロフィールページを作成しましょう。
                </p>
            </div>

            <section v-else class="grid gap-4 lg:grid-cols-2">
                <Card v-for="link in props.links" :key="link.id">
                    <CardHeader>
                        <div
                            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                        >
                            <div class="min-w-0">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-md bg-muted"
                                    >
                                        <img
                                            v-if="link.avatar_url"
                                            :src="link.avatar_url"
                                            :alt="link.display_name"
                                            class="size-full object-cover"
                                        />
                                        <LinkIcon
                                            v-else
                                            class="size-5 text-muted-foreground"
                                        />
                                    </div>
                                    <div class="min-w-0">
                                        <CardTitle class="truncate text-base">
                                            {{ link.display_name }}
                                        </CardTitle>
                                        <CardDescription class="truncate">
                                            /@{{ link.slug }}
                                        </CardDescription>
                                    </div>
                                </div>
                            </div>

                            <Badge
                                :variant="
                                    link.is_published ? 'default' : 'secondary'
                                "
                            >
                                {{ link.is_published ? '公開中' : '下書き' }}
                            </Badge>
                        </div>
                    </CardHeader>

                    <CardContent class="grid gap-5">
                        <p
                            class="min-h-10 text-sm text-gray-600 dark:text-neutral-300"
                        >
                            {{ link.bio || 'BIO 未設定' }}
                        </p>

                        <dl class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-muted-foreground">
                                    ウィジェット
                                </dt>
                                <dd
                                    class="mt-1 font-medium text-gray-900 dark:text-white"
                                >
                                    {{ link.widgets_count }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">更新日</dt>
                                <dd
                                    class="mt-1 font-medium text-gray-900 dark:text-white"
                                >
                                    {{ formatDate(link.updated_at) }}
                                </dd>
                            </div>
                        </dl>

                        <div class="flex flex-col gap-2 sm:flex-row">
                            <Button as-child class="w-full sm:w-auto">
                                <Link :href="`/@${link.slug}`">
                                    <Pencil class="size-4" />
                                    管理
                                </Link>
                            </Button>
                            <Button
                                as-child
                                variant="outline"
                                class="w-full sm:w-auto"
                            >
                                <a :href="`/@${link.slug}`" target="_blank">
                                    <ExternalLink class="size-4" />
                                    表示
                                </a>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </section>
        </div>
    </AppLayout>
</template>
