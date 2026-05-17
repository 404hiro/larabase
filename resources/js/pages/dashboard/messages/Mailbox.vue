<script setup lang="ts">
import {
    destroy as messageDestroy,
    update as messageUpdate,
} from '@/actions/App/Http/Controllers/MessageController';
import { Button } from '@/components/ui/button';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ExternalLink,
    Globe,
    Lock,
    Mail,
    MailOpen,
    MessageCircle,
    Reply,
    Trash2,
    User,
} from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import type { DashboardMessage } from './types';

const props = defineProps<{
    mailbox: 'inbox' | 'sent';
    messages: DashboardMessage[];
}>();

const getInitialSelectedMessageId = (): string | null => {
    if (typeof window === 'undefined') {
        return null;
    }

    const messageId = new URLSearchParams(window.location.search).get('message');

    return messageId && /^[0-9a-f-]{36}$/i.test(messageId) ? messageId : null;
};

const replyingToId = ref<string | null>(null);
const selectedMessageId = ref<string | null>(getInitialSelectedMessageId());
const isDetailOpen = ref(
    props.messages.some((message) => message.id === selectedMessageId.value),
);
const sortDirection = ref<'desc' | 'asc'>('desc');

const replyForm = useForm({
    reply_body: '',
});

const getSenderName = (message: DashboardMessage) => {
    if (message.sender_mode === 'anonymous') {
        return 'とくめいさん';
    }

    return message.sender_display_name || 'ユーザー';
};

const getSenderInitial = (message: DashboardMessage) => {
    return getSenderName(message).charAt(0);
};

const getListName = (message: DashboardMessage) => {
    return isInbox.value ? getSenderName(message) : message.link.display_name;
};

const getListInitial = (message: DashboardMessage) => {
    return getListName(message).charAt(0);
};

const getListAvatarUrl = (message: DashboardMessage) => {
    return isInbox.value ? message.sender.avatar_url : message.link.avatar_url;
};

const isAnonymousInboxMessage = (message: DashboardMessage) => {
    return isInbox.value && message.sender_mode === 'anonymous';
};

const messageListButtonClass = (message: DashboardMessage) => {
    if (selectedMessage.value?.id === message.id) {
        return 'bg-gray-100';
    }

    if (message.is_read) {
        return 'bg-gray-50 hover:bg-gray-100';
    }

    return 'bg-white hover:bg-gray-50';
};

const unreadCount = computed(() => {
    return props.messages.filter((message) => !message.is_read).length;
});

const sortedMessages = computed(() => {
    return [...props.messages].sort((messageA, messageB) => {
        const timestampA = new Date(messageA.created_at).getTime();
        const timestampB = new Date(messageB.created_at).getTime();

        return sortDirection.value === 'desc'
            ? timestampB - timestampA
            : timestampA - timestampB;
    });
});

const selectedMessage = computed(() => {
    return (
        props.messages.find(
            (message) => message.id === selectedMessageId.value,
        ) ?? null
    );
});

const isInbox = computed(() => props.mailbox === 'inbox');

const mailboxTitle = computed(() => {
    return isInbox.value ? '受信箱' : '送信箱';
});

const sortLabel = computed(() => {
    return sortDirection.value === 'desc' ? '新しい順' : '古い順';
});

const formatDate = (dateString: string) => {
    return new Intl.DateTimeFormat('ja-JP', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(dateString));
};

const formatDetailDate = (dateString: string) => {
    return new Intl.DateTimeFormat('ja-JP', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(dateString));
};

const selectMessage = (message: DashboardMessage) => {
    selectedMessageId.value = message.id;
    isDetailOpen.value = true;
    replyingToId.value = null;

    const url = new URL(window.location.href);
    url.searchParams.set('message', String(message.id));
    window.history.pushState({}, '', `${url.pathname}${url.search}`);

    if (isInbox.value && !message.is_read) {
        useForm({ is_read: true }).patch(messageUpdate.url(message.id), {
            preserveScroll: true,
        });
    }
};

const returnToMessageList = () => {
    isDetailOpen.value = false;
    selectedMessageId.value = null;
    replyingToId.value = null;

    const url = new URL(window.location.href);
    url.searchParams.delete('message');
    window.history.pushState({}, '', `${url.pathname}${url.search}`);
};

const toggleSortDirection = () => {
    sortDirection.value = sortDirection.value === 'desc' ? 'asc' : 'desc';
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

watch(
    () => props.messages,
    (messages) => {
        if (!messages.length) {
            selectedMessageId.value = null;

            return;
        }

        if (!messages.some((message) => message.id === selectedMessageId.value)) {
            selectedMessageId.value = null;
            isDetailOpen.value = false;
        }
    },
);

watch(
    () => props.mailbox,
    () => {
        selectedMessageId.value = getInitialSelectedMessageId();
        isDetailOpen.value = props.messages.some(
            (message) => message.id === selectedMessageId.value,
        );
        replyingToId.value = null;
    },
);

onMounted(() => {
    if (selectedMessageId.value && isInbox.value) {
        const message = props.messages.find(m => m.id === selectedMessageId.value);
        if (message && !message.is_read) {
            useForm({ is_read: true }).patch(messageUpdate.url(message.id), {
                preserveScroll: true,
            });
        }
    }
});
</script>

<template>

    <Head title="メッセージ管理" />

    <DashboardLayout content-class="h-full max-w-none" main-class="p-0">
        <div class="h-full">
            <section class="h-full overflow-hidden bg-white">
                <div
                    class="relative grid h-full min-h-0 min-[1025px]:grid-cols-[340px_minmax(0,1fr)] xl:grid-cols-[400px_minmax(0,1fr)]">
                    <div
                        class="flex h-full min-h-0 flex-col border-b border-gray-200 min-[1025px]:border-r min-[1025px]:border-b-0">
                        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                            <div>
                                <p class="text-sm font-bold text-gray-900">
                                    {{ mailboxTitle }}
                                </p>
                                <p class="mt-0.5 text-xs font-medium text-gray-400" v-if="unreadCount > 0">
                                    {{ unreadCount }} 件の未読メッセージ
                                </p>
                            </div>
                            <button type="button"
                                class="inline-flex h-9 items-center justify-center rounded-full border border-gray-200 bg-white px-3 text-xs font-bold text-gray-700 transition-colors hover:bg-gray-50"
                                @click="toggleSortDirection">
                                {{ sortLabel }}
                            </button>
                        </div>

                        <div v-if="messages.length === 0" class="px-5 py-16 text-center">
                            <MessageCircle class="mx-auto size-12 text-gray-200" />
                            <p class="mt-3 text-sm font-semibold text-gray-400">
                                メッセージはまだありません
                            </p>
                        </div>

                        <div v-else class="min-h-0 flex-1 overflow-y-auto">
                            <button v-for="message in sortedMessages" :key="message.id" type="button"
                                class="block w-full border-b border-gray-200 px-5 py-4 text-left transition-colors"
                                :class="messageListButtonClass(message)" @click="selectMessage(message)">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex min-w-0 items-start gap-3">
                                        <div
                                            class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100 text-sm font-bold text-gray-700">
                                            <MailOpen v-if="message.is_read" class="size-5 text-gray-500" />
                                            <Mail v-else class="size-5 text-gray-500" />
                                        </div>

                                        <div class="min-w-0">
                                            <p class="truncate text-base font-bold text-gray-950">
                                                {{ getListName(message) }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 flex-col items-end gap-2">
                                        <p class="text-xs font-semibold text-gray-400">
                                            {{ formatDate(message.created_at) }}
                                        </p>
                                        <div v-if="!message.is_public || message.amount > 0" class="flex flex-wrap justify-end gap-1">
                                            <span v-if="message.amount > 0"
                                                class="inline-flex items-center rounded-full bg-pink-50 px-2 py-0.5 text-[11px] font-bold text-pink-600">
                                                ❤️￥{{ message.amount.toLocaleString() }}
                                            </span>
                                            <span v-if="!message.is_public"
                                                class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-bold text-gray-500">
                                                プライベート
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <article v-if="selectedMessage"
                        class="absolute inset-0 z-10 flex h-full min-h-0 flex-col bg-white transition-transform duration-300 ease-out min-[1025px]:static min-[1025px]:z-auto min-[1025px]:translate-x-0 min-[1025px]:transition-none"
                        :class="isDetailOpen
                            ? 'translate-x-0'
                            : 'pointer-events-none translate-x-full min-[1025px]:pointer-events-auto'
                            ">
                        <header class="border-b border-gray-200 px-5 py-4 min-[1025px]:px-8 min-[1025px]:py-5">
                            <button type="button" aria-label="メッセージ一覧に戻る"
                                class="mb-4 inline-flex size-10 items-center justify-center rounded-full border border-gray-200 text-gray-700 transition-colors hover:bg-gray-50 min-[1025px]:hidden"
                                @click="returnToMessageList">
                                <ArrowLeft class="size-5" />
                            </button>

                            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold tracking-wide text-gray-400 uppercase">
                                        {{ selectedMessage.link.display_name }}宛
                                    </p>
                                    <h2 class="mt-2 text-2xl font-black tracking-tight text-gray-950">
                                        {{ getSenderName(selectedMessage) }}さんからのメッセージ
                                    </h2>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <Button as-child type="button" variant="outline" size="sm">
                                        <Link :href="isInbox
                                            ? `/dashboard/links/${selectedMessage.link.id}`
                                            : `/@${selectedMessage.link.slug}`
                                            ">
                                            <ExternalLink class="size-4" />
                                            {{
                                                isInbox
                                                    ? 'リンク設定'
                                                    : 'リンクを見る'
                                            }}
                                        </Link>
                                    </Button>
                                    <Button v-if="isInbox" type="button" variant="outline" size="sm"
                                        @click="togglePublish(selectedMessage)">
                                        <Globe v-if="selectedMessage.is_public" class="size-4 text-blue-500" />
                                        <Lock v-else class="size-4" />
                                        {{
                                            selectedMessage.is_public
                                                ? '非公開にする'
                                                : '公開する'
                                        }}
                                    </Button>
                                    <Button v-if="isInbox" type="button" variant="outline" size="sm"
                                        @click="startReply(selectedMessage)">
                                        <Reply class="size-4" />
                                        返信
                                    </Button>
                                    <Button v-if="isInbox" type="button" variant="outline" size="sm"
                                        class="text-red-600 hover:text-red-700" @click="deleteMessage(selectedMessage)">
                                        <Trash2 class="size-4" />
                                        削除
                                    </Button>
                                </div>
                            </div>

                            <div class="mt-5 flex items-start gap-4">
                                <div
                                    class="flex size-12 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-bold text-gray-700">
                                    <MailOpen v-if="selectedMessage.is_read" class="size-6 text-gray-500" />
                                    <Mail v-else class="size-6 text-gray-500" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-base font-bold text-gray-950">
                                        {{ getSenderName(selectedMessage) }}からのメッセージ
                                    </p>
                                    <p class="mt-0.5 text-sm font-medium text-gray-500">
                                        {{ selectedMessage.link.display_name }}宛
                                    </p>
                                </div>
                                <time class="shrink-0 text-sm font-medium text-gray-400">
                                    {{
                                        formatDetailDate(
                                            selectedMessage.created_at,
                                        )
                                    }}
                                </time>
                            </div>
                        </header>

                        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-6 min-[1025px]:px-8 min-[1025px]:py-8">
                            <p class="max-w-3xl text-lg leading-9 whitespace-pre-wrap text-gray-900">
                                {{ selectedMessage.body }}
                            </p>

                            <div v-if="
                                selectedMessage.reply_body ||
                                replyingToId === selectedMessage.id
                            " class="mt-8 max-w-3xl rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <div class="flex items-center gap-2 text-xs font-semibold text-gray-500">
                                    <Reply class="size-3.5" />
                                    <span>返信</span>
                                </div>

                                <div v-if="replyingToId === selectedMessage.id" class="mt-3">
                                    <textarea v-model="replyForm.reply_body" rows="5"
                                        class="w-full resize-none rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-gray-900 focus:ring-4 focus:ring-gray-100"
                                        placeholder="返信を書く..."></textarea>
                                    <div class="mt-3 flex justify-end gap-2">
                                        <Button type="button" variant="outline" size="sm" @click="cancelReply">
                                            キャンセル
                                        </Button>
                                        <Button type="button" size="sm" :disabled="replyForm.processing" @click="
                                            submitReply(selectedMessage)
                                            ">
                                            保存
                                        </Button>
                                    </div>
                                </div>
                                <p v-else class="mt-3 text-sm leading-relaxed whitespace-pre-wrap text-gray-700">
                                    {{ selectedMessage.reply_body }}
                                </p>
                            </div>

                            <div v-else class="mt-8">
                                <Button type="button" variant="outline" @click="startReply(selectedMessage)">
                                    <Reply class="size-4" />
                                    このメッセージに返信
                                </Button>
                            </div>
                        </div>
                    </article>

                    <div v-else class="hidden bg-white min-[1025px]:block"></div>
                </div>
            </section>
        </div>
    </DashboardLayout>
</template>
