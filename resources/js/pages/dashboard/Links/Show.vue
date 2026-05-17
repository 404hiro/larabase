<script setup lang="ts">
import { destroy as linkDestroy, update as linkUpdate } from '@/actions/App/Http/Controllers/LinkController';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
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
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Form, Head, Link, useForm } from '@inertiajs/vue3';
import { ExternalLink, Save } from 'lucide-vue-next';

type ManagedLink = {
    id: string;
    slug: string;
    display_name: string;
    title?: {
        id: number;
        name: string;
    } | null;
    bio?: string | null;
    avatar_url?: string | null;
    is_published: boolean;
    has_web_display: boolean;
    updated_at?: string | null;
};

const props = defineProps<{
    link: ManagedLink;
    titleOptions: Array<{ id: number; name: string }>;
}>();

const settingsForm = useForm({
    display_name: props.link.display_name,
    title_id: props.link.title?.id ? String(props.link.title.id) : '',
    bio: props.link.bio ?? '',
    is_published: props.link.is_published,
    has_web_display: props.link.has_web_display,
});

const submitSettings = () => {
    settingsForm.put(linkUpdate.url(props.link.slug), {
        preserveScroll: true,
    });
};
</script>

<template>

    <Head :title="`${link.display_name} 設定`" />

    <DashboardLayout>
        <div class="space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ link.display_name }}
                        </h1>
                        <Badge :variant="link.is_published ? 'default' : 'secondary'">
                            {{ link.is_published ? '公開中' : '下書き' }}
                        </Badge>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-neutral-400">
                        /@{{ link.slug }} の設定を管理します。
                    </p>
                </div>

                <Button as-child variant="outline">
                    <Link :href="`/@${link.slug}`" target="_blank">
                        <ExternalLink class="size-4" />
                        表示
                    </Link>
                </Button>
            </div>

            <div class="max-w-2xl">
                <form
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-xs dark:border-neutral-800 dark:bg-neutral-900"
                    @submit.prevent="submitSettings">
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
                        プロフィール設定
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-neutral-400">
                        基本情報を編集できます。
                    </p>

                    <div class="mt-6 grid gap-6">
                        <div class="grid gap-2">
                            <Label for="display_name">表示名</Label>
                            <Input id="display_name" v-model="settingsForm.display_name" placeholder="名前を入力" />
                            <InputError :message="settingsForm.errors.display_name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="title_id">肩書き</Label>
                            <Select id="title_id" v-model="settingsForm.title_id">
                                <option value="">肩書きを選択しない</option>
                                <option v-for="option in titleOptions" :key="option.id" :value="String(option.id)">
                                    {{ option.name }}
                                </option>
                            </Select>
                            <InputError :message="settingsForm.errors.title_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="bio">BIO</Label>
                            <textarea id="bio" v-model="settingsForm.bio" rows="5" maxlength="280"
                                class="flex w-full resize-none rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                placeholder="自己紹介を入力" />
                            <div class="flex items-center justify-between gap-3">
                                <InputError :message="settingsForm.errors.bio" />
                                <span class="ms-auto text-xs text-muted-foreground">
                                    {{ settingsForm.bio.length }}/280
                                </span>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label
                                class="flex cursor-pointer items-center justify-between gap-4 rounded-lg border border-gray-200 p-4 transition hover:bg-gray-50 dark:border-neutral-800 dark:hover:bg-neutral-800/50">
                                <div class="space-y-0.5">
                                    <span class="block font-medium text-gray-900 dark:text-white">
                                        公開する
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-neutral-400">
                                        オフにするとプロフィールページを下書きにします。
                                    </span>
                                </div>
                                <input v-model="settingsForm.is_published" type="checkbox" class="size-4" />
                            </label>

                            <label
                                class="flex cursor-pointer items-center justify-between gap-4 rounded-lg border border-gray-200 p-4 transition hover:bg-gray-50 dark:border-neutral-800 dark:hover:bg-neutral-800/50">
                                <div class="space-y-0.5">
                                    <span class="block font-medium text-gray-900 dark:text-white">
                                        Web表示
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-neutral-400">
                                        リンクページをWeb向け表示で使います。
                                    </span>
                                </div>
                                <input v-model="settingsForm.has_web_display" type="checkbox" class="size-4" />
                            </label>
                        </div>
                    </div>

                    <div class="mt-8">
                        <Button type="submit" class="w-full sm:w-auto" :disabled="settingsForm.processing">
                            <Save class="size-4" />
                            設定を保存
                        </Button>
                    </div>
                </form>
            </div>

            <div class="max-w-2xl space-y-6">
                <HeadingSmall title="リンクの削除" description="このリンクを完全に削除します。" />
                <div
                    class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                    <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                        <p class="font-medium">警告</p>
                        <p class="text-sm">
                            この操作は元に戻せません。ご注意ください。
                        </p>
                    </div>
                    <Dialog>
                        <DialogTrigger as-child>
                            <Button variant="destructive">リンクを削除</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <Form v-bind="linkDestroy.form(link.id)" reset-on-success :options="{
                                preserveScroll: true,
                            }" class="space-y-6" v-slot="{ processing, reset, clearErrors }">
                                <DialogHeader class="space-y-3">
                                    <DialogTitle>リンクを完全に削除しますか？</DialogTitle>
                                    <DialogDescription>
                                        リンクが削除されると、すべてのデータも完全に削除されます。
                                    </DialogDescription>
                                </DialogHeader>

                                <DialogFooter class="gap-2">
                                    <DialogClose as-child>
                                        <Button variant="secondary" @click="
                                            () => {
                                                clearErrors();
                                                reset();
                                            }
                                        ">
                                            キャンセル
                                        </Button>
                                    </DialogClose>

                                    <Button type="submit" variant="destructive" :disabled="processing">
                                        リンクを削除
                                    </Button>
                                </DialogFooter>
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
