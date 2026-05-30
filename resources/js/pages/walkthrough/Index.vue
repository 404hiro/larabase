<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { store as walkthroughStore } from '@/routes/walkthrough';
import { Head, useForm } from '@inertiajs/vue3';
import { Check } from 'lucide-vue-next';

defineProps<{
    titleOptions: Array<{ id: number; name: string }>;
}>();

const form = useForm({
    slug: '',
    display_name: '',
    title_id: '',
    bio: '',
});

const submit = () => {
    form.post(walkthroughStore().url);
};
</script>

<template>
    <Head title="リンクを作成" />

    <div
        class="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8 dark:bg-[#111111]"
    >
        <Card
            class="w-full max-w-lg shadow-xl dark:border-zinc-800 dark:bg-zinc-950"
        >
            <form @submit.prevent="submit">
                <CardHeader class="space-y-1 text-center">
                    <CardTitle class="text-2xl font-bold dark:text-white">
                        プロフィールを作成
                    </CardTitle>
                </CardHeader>

                <CardContent class="grid gap-6">
                    <div class="grid gap-2">
                        <Label for="slug" class="dark:text-zinc-200"
                            >リンクURL</Label
                        >
                        <div class="flex items-center">
                            <span
                                class="inline-flex h-10 items-center rounded-s-xl border border-e-0 border-gray-200 bg-gray-50 px-3 text-sm text-gray-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400"
                            >
                                grid.link/@
                            </span>
                            <Input
                                id="slug"
                                v-model="form.slug"
                                class="h-10 rounded-s-none rounded-e-xl border-gray-200 focus:ring-blue-500/15 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                                placeholder="my-name"
                            />
                        </div>
                        <InputError :message="form.errors.slug" />
                        <p class="text-[10px] text-gray-500 dark:text-zinc-500">
                            後から変更することはできません。英数字、ドット、アンダースコア、ハイフンが使用できます。
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="display_name" class="dark:text-zinc-200"
                            >表示名</Label
                        >
                        <Input
                            id="display_name"
                            v-model="form.display_name"
                            class="h-10 rounded-xl border-gray-200 focus:ring-blue-500/15 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                            placeholder="名前またはニックネーム"
                        />
                        <InputError :message="form.errors.display_name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="title_id" class="dark:text-zinc-200"
                            >職業・肩書き</Label
                        >
                        <Select
                            id="title_id"
                            v-model="form.title_id"
                            class="h-10 rounded-xl border-gray-200 focus:ring-blue-500/15 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                        >
                            <option value="">肩書きを選択しない</option>
                            <option
                                v-for="title in titleOptions"
                                :key="title.id"
                                :value="String(title.id)"
                            >
                                {{ title.name }}
                            </option>
                        </Select>
                        <InputError :message="form.errors.title_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="bio" class="dark:text-zinc-200"
                            >自己紹介 (任意)</Label
                        >
                        <textarea
                            id="bio"
                            v-model="form.bio"
                            rows="3"
                            maxlength="280"
                            class="flex w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-sm shadow-sm transition-colors outline-none placeholder:text-gray-400 focus-visible:border-blue-500 focus-visible:ring-4 focus-visible:ring-blue-500/15 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                            placeholder="自己紹介を入力してください"
                        />
                        <div class="flex items-center justify-between gap-3">
                            <InputError :message="form.errors.bio" />
                            <span
                                class="ms-auto text-[10px] text-gray-500 dark:text-zinc-500"
                            >
                                {{ form.bio.length }}/280
                            </span>
                        </div>
                    </div>
                </CardContent>

                <CardFooter>
                    <Button
                        type="submit"
                        class="h-12 w-full rounded-xl bg-black text-white hover:bg-neutral-800 dark:bg-white dark:text-black dark:hover:bg-zinc-200"
                        :disabled="form.processing"
                    >
                        <Check v-if="!form.processing" class="mr-2 size-4" />
                        {{
                            form.processing
                                ? '作成中...'
                                : 'プロフィールを作成して開始'
                        }}
                    </Button>
                </CardFooter>
            </form>
        </Card>
    </div>
</template>
