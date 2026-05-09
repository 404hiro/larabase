<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Plus, Save, Trash2 } from 'lucide-vue-next';
import { reactive, watch } from 'vue';

interface Title {
    id: number;
    name: string;
    sort_order: number;
    is_active: boolean;
    links_count: number;
}

const props = defineProps<{
    titles: Title[];
}>();

const titleRows = reactive(
    props.titles.map((title) => ({
        ...title,
    })),
);

watch(
    () => props.titles,
    (titles) => {
        titleRows.splice(
            0,
            titleRows.length,
            ...titles.map((title) => ({
                ...title,
            })),
        );
    },
);

const createForm = useForm({
    name: '',
    sort_order: props.titles.length + 1,
    is_active: true,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: '管理画面',
        href: '/admin',
    },
    {
        title: '職業管理',
        href: '/admin/titles',
    },
];

const submitCreate = () => {
    createForm.post('/admin/titles', {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            createForm.sort_order = props.titles.length + 2;
            createForm.is_active = true;
        },
    });
};

const updateTitle = (title: Title) => {
    router.put(
        `/admin/titles/${title.id}`,
        {
            name: title.name,
            sort_order: title.sort_order,
            is_active: title.is_active,
        },
        {
            preserveScroll: true,
        },
    );
};

const deleteTitle = (title: Title) => {
    if (confirm(`${title.name} を削除してもよろしいですか？`)) {
        router.delete(`/admin/titles/${title.id}`, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="職業管理" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card>
                <CardHeader>
                    <CardTitle>職業管理</CardTitle>
                </CardHeader>
                <CardContent>
                    <form
                        class="mb-6 grid gap-4 rounded-md border p-4 lg:grid-cols-[1fr_8rem_9rem_auto]"
                        @submit.prevent="submitCreate"
                    >
                        <div class="grid gap-2">
                            <Label for="name">職業名</Label>
                            <Input
                                id="name"
                                v-model="createForm.name"
                                placeholder="職業名"
                            />
                            <InputError :message="createForm.errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="sort_order">並び順</Label>
                            <Input
                                id="sort_order"
                                v-model="createForm.sort_order"
                                type="number"
                                min="0"
                            />
                            <InputError
                                :message="createForm.errors.sort_order"
                            />
                        </div>
                        <label
                            class="flex items-center gap-2 self-end rounded-md border px-3 py-2 text-sm"
                        >
                            <input
                                v-model="createForm.is_active"
                                type="checkbox"
                                class="size-4 rounded border-input"
                            />
                            作成時に表示
                        </label>
                        <Button type="submit" class="self-end">
                            <Plus class="size-4" />
                            追加
                        </Button>
                    </form>

                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>職業名</TableHead>
                                    <TableHead class="w-28">並び順</TableHead>
                                    <TableHead class="w-36">表示</TableHead>
                                    <TableHead class="w-28">利用数</TableHead>
                                    <TableHead class="text-right"
                                        >操作</TableHead
                                    >
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="title in titleRows"
                                    :key="title.id"
                                >
                                    <TableCell>
                                        <Input v-model="title.name" />
                                    </TableCell>
                                    <TableCell>
                                        <Input
                                            v-model="title.sort_order"
                                            type="number"
                                            min="0"
                                        />
                                    </TableCell>
                                    <TableCell>
                                        <label
                                            class="inline-flex items-center gap-2 text-sm"
                                        >
                                            <input
                                                v-model="title.is_active"
                                                type="checkbox"
                                                class="size-4 rounded border-input"
                                            />
                                            <Badge
                                                :variant="
                                                    title.is_active
                                                        ? 'default'
                                                        : 'secondary'
                                                "
                                            >
                                                {{
                                                    title.is_active
                                                        ? '表示'
                                                        : '非表示'
                                                }}
                                            </Badge>
                                        </label>
                                    </TableCell>
                                    <TableCell>
                                        {{ title.links_count }}
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex justify-end gap-2">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                @click="updateTitle(title)"
                                            >
                                                <Save class="size-4" />
                                                保存
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                @click="deleteTitle(title)"
                                            >
                                                <Trash2 class="size-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
