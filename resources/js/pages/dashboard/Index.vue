<script setup lang="ts">
import {
    destroy as messageDestroy,
    update as messageUpdate,
} from '@/actions/App/Http/Controllers/MessageController';
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
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Globe,
    Inbox,
    Link as LinkIcon,
    Lock,
    MessageCircle,
    MoreHorizontal,
    Reply,
    Trash2,
    TriangleAlert,
    User,
} from 'lucide-vue-next';
import { ref } from 'vue';

type DashboardMessage = {
    id: string;
    body: string;
    sender_mode: 'anonymous' | 'named';
    sender_display_name: string | null;
    is_public: boolean;
    created_at: string;
    reply_body: string | null;
    sender: {
        id: number;
        name: string;
        avatar_url: string | null;
    };
    link: {
        slug: string;
        display_name: string;
    };
};

interface Props {
    linksCount: number;
    messagesCount: number;
    unreadMessagesCount: number;
    messages: DashboardMessage[];
    titleOptions: Array<{
        id: number;
        name: string;
    }>;
    userName: string;
}

const props = defineProps<Props>();

const isCreateDialogOpen = ref(false);
const replyingToId = ref<string | null>(null);

const form = useForm({
    slug: '',
    display_name: props.linksCount === 0 ? props.userName : '',
    title_id: '',
    bio: '',
});

const replyForm = useForm({
    reply_body: '',
});

const submit = () => {
    form.post('/dashboard/links', {
        preserveScroll: true,
        onSuccess: () => {
            isCreateDialogOpen.value = false;
            form.reset();
        },
    });
};

const getSenderName = (message: DashboardMessage) => {
    if (message.sender_mode === 'anonymous') {
        return 'とくめいさん';
    }

    return message.sender_display_name || 'ユーザー';
};

const getSenderInitial = (message: DashboardMessage) => {
    return getSenderName(message).charAt(0);
};

const formatDate = (dateString: string) => {
    return new Intl.DateTimeFormat('ja-JP', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(dateString));
};

const togglePublish = (message: DashboardMessage) => {
    useForm({ is_public: !message.is_public }).patch(
        messageUpdate.url(message.id),
        {
            preserveScroll: true,
        },
    );
};

const startReply = (message: DashboardMessage) => {
    replyingToId.value = message.id;
    replyForm.reply_body = message.reply_body ?? '';
};

const cancelReply = () => {
    replyingToId.value = null;
    replyForm.reset();
};

const submitReply = (message: DashboardMessage) => {
    replyForm.patch(messageUpdate.url(message.id), {
        preserveScroll: true,
        onSuccess: () => {
            replyingToId.value = null;
            replyForm.reset();
        },
    });
};

const deleteMessage = (message: DashboardMessage) => {
    if (!confirm('メッセージを削除しますか？')) {
        return;
    }

    useForm({}).delete(messageDestroy.url(message.id), {
        preserveScroll: true,
    });
};
</script>

<template>

    <Head title="ダッシュボード" />

    <DashboardLayout>
        <div class="space-y-6">
            <Alert v-if="linksCount === 0"
                class="border-yellow-200 bg-yellow-50 text-yellow-950 dark:border-yellow-900/60 dark:bg-yellow-950/30 dark:text-yellow-100">
                <TriangleAlert class="size-4" />
                <AlertTitle>リンクがまだありません</AlertTitle>
                <AlertDescription
                    class="flex flex-col gap-4 text-yellow-800 sm:flex-row sm:items-center sm:justify-between dark:text-yellow-200">
                    <span>
                        最初のリンクページを作成して、grid.link
                        で公開しましょう。
                    </span>

                    <Dialog v-model:open="isCreateDialogOpen">
                        <DialogTrigger as-child>
                            <Button class="w-full bg-yellow-500 text-yellow-950 hover:bg-yellow-400 sm:w-auto">
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
                                                class="inline-flex h-9 items-center rounded-s-md border border-e-0 border-input bg-muted px-3 text-sm text-muted-foreground">
                                                /@
                                            </span>
                                            <Input id="slug" v-model="form.slug" class="rounded-s-none"
                                                placeholder="my-link" />
                                        </div>
                                        <InputError :message="form.errors.slug" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="display_name">表示名</Label>
                                        <Input id="display_name" v-model="form.display_name" placeholder="My Profile" />
                                        <InputError :message="form.errors.display_name" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="title_id">職業</Label>
                                        <Select id="title_id" v-model="form.title_id">
                                            <option value="">
                                                職業を選択しない
                                            </option>
                                            <option v-for="title in titleOptions" :key="title.id"
                                                :value="String(title.id)">
                                                {{ title.name }}
                                            </option>
                                        </Select>
                                        <InputError :message="form.errors.title_id" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="bio">BIO</Label>
                                        <textarea id="bio" v-model="form.bio" rows="4" maxlength="280"
                                            class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:bg-input/30 dark:aria-invalid:ring-destructive/40"
                                            placeholder="自己紹介を入力" />
                                        <div class="flex items-center justify-between gap-3">
                                            <InputError :message="form.errors.bio" />
                                            <span class="ms-auto text-xs text-muted-foreground">
                                                {{ form.bio.length }}/280
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <DialogFooter>
                                    <Button type="submit" :disabled="form.processing">
                                        作成する
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </AlertDescription>
            </Alert>

            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        ダッシュボード
                    </h1>
                </div>

                <div class="flex items-center gap-3">
                    <Dialog v-model:open="isCreateDialogOpen" v-if="linksCount > 0">
                        <DialogTrigger as-child>
                            <Button>
                                <LinkIcon class="size-4" />
                                リンクを追加
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-lg">
                            <form class="space-y-6" @submit.prevent="submit">
                                <DialogHeader>
                                    <DialogTitle>リンクを追加</DialogTitle>
                                    <DialogDescription>
                                        新しいプロフィールページを作成します。
                                    </DialogDescription>
                                </DialogHeader>

                                <div class="grid gap-4">
                                    <div class="grid gap-2">
                                        <Label for="slug">リンクURL</Label>
                                        <div class="flex items-center">
                                            <span
                                                class="inline-flex h-9 items-center rounded-s-md border border-e-0 border-input bg-muted px-3 text-sm text-muted-foreground">/@</span>
                                            <Input id="slug" v-model="form.slug" class="rounded-s-none"
                                                placeholder="my-link" />
                                        </div>
                                        <InputError :message="form.errors.slug" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="display_name">表示名</Label>
                                        <Input id="display_name" v-model="form.display_name" placeholder="名前を入力" />
                                        <InputError :message="form.errors.display_name" />
                                    </div>
                                </div>

                                <DialogFooter>
                                    <Button type="submit" :disabled="form.processing">作成する</Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <section class="grid gap-4 md:grid-cols-3">
                <Link href="/dashboard/messages/inbox"
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs transition hover:border-gray-300 hover:bg-gray-50 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:bg-neutral-800/60">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-500 dark:text-neutral-400">
                            プロフィール
                        </p>
                        <LinkIcon class="size-4 text-gray-400" />
                    </div>
                    <p class="mt-4 text-3xl font-semibold text-gray-950 dark:text-white">
                        {{ linksCount }}
                    </p>
                    <p class="mt-2 text-xs text-gray-500 dark:text-neutral-400">
                        公開ページの作成数
                    </p>
                </Link>

                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs dark:border-neutral-800 dark:bg-neutral-900">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-500 dark:text-neutral-400">
                            メッセージ
                        </p>
                        <MessageCircle class="size-4 text-gray-400" />
                    </div>
                    <p class="mt-4 text-3xl font-semibold text-gray-950 dark:text-white">
                        {{ messagesCount }}
                    </p>
                    <p class="mt-2 text-xs text-gray-500 dark:text-neutral-400">
                        受信したメッセージ総数
                    </p>
                </div>

                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs dark:border-neutral-800 dark:bg-neutral-900">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-500 dark:text-neutral-400">
                            未読
                        </p>
                        <Inbox class="size-4 text-gray-400" />
                    </div>
                    <p class="mt-4 text-3xl font-semibold text-gray-950 dark:text-white">
                        {{ unreadMessagesCount }}
                    </p>
                    <p class="mt-2 text-xs text-gray-500 dark:text-neutral-400">
                        対応待ちのメッセージ
                    </p>
                </div>
            </section>

            <section
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-neutral-800 dark:bg-neutral-900">
                <div
                    class="flex flex-col gap-3 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800">
                    <div>
                        <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                            最近のメッセージ
                        </h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-neutral-400">
                            すべてのプロフィールに届いた最新20件を表示しています。
                        </p>
                    </div>
                    <Button variant="outline" size="sm">
                        <MoreHorizontal class="size-4" />
                    </Button>
                </div>

                <div v-if="messages.length === 0" class="px-5 py-16 text-center">
                    <MessageCircle class="mx-auto size-12 text-gray-200 dark:text-neutral-700" />
                    <p class="mt-3 text-sm font-semibold text-gray-400">
                        メッセージはまだありません
                    </p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-800">
                        <thead class="bg-gray-50 dark:bg-neutral-950/60">
                            <tr>
                                <th
                                    class="px-5 py-3 text-start text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-neutral-400">
                                    送信者
                                </th>
                                <th
                                    class="px-5 py-3 text-start text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-neutral-400">
                                    メッセージ
                                </th>
                                <th
                                    class="px-5 py-3 text-start text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-neutral-400">
                                    状態
                                </th>
                                <th
                                    class="px-5 py-3 text-end text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-neutral-400">
                                    操作
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-neutral-800">
                            <tr v-for="message in messages" :key="message.id"
                                class="align-top hover:bg-gray-50/80 dark:hover:bg-neutral-800/40">
                                <td class="w-72 px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex size-10 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-bold text-gray-700 dark:bg-neutral-800 dark:text-neutral-200">
                                            <User v-if="
                                                message.sender_mode ===
                                                'anonymous'
                                            " class="size-5" />
                                            <span v-else>{{
                                                getSenderInitial(message)
                                                }}</span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                                {{ getSenderName(message) }}
                                            </p>
                                            <p class="truncate text-xs text-gray-500 dark:text-neutral-400">
                                                {{
                                                    message.link.display_name
                                                }}・{{
                                                    formatDate(
                                                        message.created_at,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="min-w-96 px-5 py-4">
                                    <p
                                        class="line-clamp-3 text-sm leading-6 whitespace-pre-wrap text-gray-700 dark:text-neutral-200">
                                        {{ message.body }}
                                    </p>

                                    <div v-if="
                                        message.reply_body ||
                                        replyingToId === message.id
                                    "
                                        class="mt-3 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-neutral-800 dark:bg-neutral-950">
                                        <div
                                            class="flex items-center gap-2 text-xs font-semibold text-gray-500 dark:text-neutral-400">
                                            <Reply class="size-3.5" />
                                            <span>返信</span>
                                        </div>

                                        <div v-if="replyingToId === message.id" class="mt-3">
                                            <textarea v-model="replyForm.reply_body" rows="3"
                                                class="w-full resize-none rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-gray-900 focus:ring-4 focus:ring-gray-100 dark:border-neutral-800 dark:bg-neutral-900 dark:focus:border-neutral-500 dark:focus:ring-neutral-800"
                                                placeholder="返信を書く..."></textarea>
                                            <div class="mt-2 flex justify-end gap-2">
                                                <Button type="button" variant="outline" size="sm" @click="cancelReply">
                                                    キャンセル
                                                </Button>
                                                <Button type="button" size="sm" :disabled="replyForm.processing
                                                    " @click="
                                                        submitReply(message)
                                                        ">
                                                    保存
                                                </Button>
                                            </div>
                                        </div>

                                        <p v-else
                                            class="mt-2 text-sm leading-6 whitespace-pre-wrap text-gray-700 dark:text-neutral-200">
                                            {{ message.reply_body }}
                                        </p>
                                    </div>
                                </td>

                                <td class="w-40 px-5 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                                        :class="message.is_public
                                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
                                                : 'bg-gray-100 text-gray-600 dark:bg-neutral-800 dark:text-neutral-300'
                                            ">
                                        <CheckCircle2 v-if="message.is_public" class="size-3.5" />
                                        <Lock v-else class="size-3.5" />
                                        {{
                                            message.is_public
                                                ? '公開中'
                                                : '非公開'
                                        }}
                                    </span>
                                </td>

                                <td class="w-72 px-5 py-4">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <Button type="button" variant="outline" size="sm"
                                            @click="togglePublish(message)">
                                            <Globe v-if="message.is_public" class="size-4" />
                                            <Lock v-else class="size-4" />
                                            {{
                                                message.is_public
                                                    ? '非公開にする'
                                                    : '公開する'
                                            }}
                                        </Button>
                                        <Button type="button" variant="outline" size="sm" @click="startReply(message)">
                                            <Reply class="size-4" />
                                            返信
                                        </Button>
                                        <Button type="button" variant="outline" size="sm"
                                            class="text-red-600 hover:text-red-700" @click="deleteMessage(message)">
                                            <Trash2 class="size-4" />
                                            削除
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </DashboardLayout>
</template>
