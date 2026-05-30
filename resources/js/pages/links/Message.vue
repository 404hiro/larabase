<script setup lang="ts">
import { show as linkShow } from '@/actions/App/Http/Controllers/LinkController';
import {
    destroy as messageDestroy,
    store as messageStore,
    update as messageUpdate,
} from '@/actions/App/Http/Controllers/MessageController';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { store as loginStore } from '@/routes/login/index';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    Check,
    Globe,
    Heart,
    HelpCircle,
    LinkIcon,
    Lock,
    MessageCircle,
    MessageCircleHeart,
    MoreHorizontal,
    PencilLine,
    Reply,
    Trash2,
    User as UserIcon,
} from 'lucide-vue-next';
import { computed, nextTick, ref } from 'vue';

const props = defineProps<{
    link: {
        id: number;
        user_id: number;
        slug: string;
        display_name: string;
        bio?: string | null;
        avatar_url?: string | null;
        message_settings?: {
            one_liner?: string | null;
        } | null;
    };
    messages?:
        | {
              data: any[];
          }
        | any[];
}>();

const page = usePage();
const authUser = computed(() => {
    return page.props.auth?.user ?? null;
});
const isOwner = computed(() => {
    return Boolean(
        page.props.auth?.user && page.props.auth.user.id === props.link.user_id,
    );
});

const isEditingOneLiner = ref(false);
const oneLinerForm = useForm({
    display_name: props.link.display_name,
    message_one_liner: props.link.message_settings?.one_liner ?? '',
});

const startEditingOneLiner = () => {
    isEditingOneLiner.value = true;
};

const cancelEditingOneLiner = () => {
    isEditingOneLiner.value = false;
    oneLinerForm.reset('message_one_liner');
};

const saveOneLiner = () => {
    oneLinerForm.put(`/links/${props.link.slug}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditingOneLiner.value = false;
        },
    });
};

const messageItems = computed<any[]>(() => {
    if (Array.isArray(props.messages)) {
        return props.messages;
    }

    return props.messages?.data ?? [];
});

const messageCount = computed(() => {
    return messageItems.value.length;
});

const supportOptions = [
    { key: 'coffee', emoji: '☕', amount: 500, label: 'コーヒー' },
    { key: 'cake', emoji: '🍰', amount: 1000, label: 'ケーキ' },
    { key: 'bouquet', emoji: '💐', amount: 1500, label: 'ブーケ' },
] as const;

type SupportOptionKey = (typeof supportOptions)[number]['key'] | 'custom';

const activeTab = ref<'write' | 'read'>('write');
const isSupportEnabled = ref(false);
const selectedSupportOption = ref<SupportOptionKey>('coffee');
const customSupportAmount = ref('500');
const customSupportAmountElement = ref<HTMLElement | null>(null);
const isComposingCustomSupportAmount = ref(false);

const messageForm = useForm({
    body: '',
    sender_mode: 'named' as 'anonymous' | 'named',
    amount: 0,
    is_public: true,
});

const selectedSupportAmount = computed(() => {
    if (!isSupportEnabled.value) {
        return 0;
    }

    if (selectedSupportOption.value === 'custom') {
        return Math.max(0, Number(customSupportAmount.value) || 0);
    }

    return (
        supportOptions.find((option) => {
            return option.key === selectedSupportOption.value;
        })?.amount ?? supportOptions[0].amount
    );
});

const formattedSupportAmount = computed(() => {
    return selectedSupportAmount.value.toLocaleString();
});

const displayedSupportAmount = computed(() => {
    return formattedSupportAmount.value;
});

const supportLabel = computed(() => {
    return 'メッセージを送る';
});

const canSendMessage = computed(() => {
    return (
        authUser.value && !isOwner.value && messageForm.body.trim().length > 0
    );
});

const selectCustomSupportAmount = async () => {
    if (selectedSupportOption.value !== 'custom') {
        customSupportAmount.value = String(selectedSupportAmount.value);
    }

    selectedSupportOption.value = 'custom';

    await nextTick();

    if (!customSupportAmountElement.value) {
        return;
    }

    customSupportAmountElement.value.textContent = Number(
        customSupportAmount.value,
    ).toLocaleString();
    customSupportAmountElement.value.focus();
    selectCustomSupportAmountText();
};

const selectCustomSupportAmountText = () => {
    if (!customSupportAmountElement.value) {
        return;
    }

    const selection = window.getSelection();

    if (!selection) {
        return;
    }

    const range = document.createRange();
    range.selectNodeContents(customSupportAmountElement.value);
    selection.removeAllRanges();
    selection.addRange(range);
};

const normalizeNumberText = (value: string) => {
    return value
        .replace(/[０-９]/g, (number) => {
            return String.fromCharCode(number.charCodeAt(0) - 0xfee0);
        })
        .replace(/[^\d]/g, '')
        .replace(/^0+(?=\d)/, '');
};

const updateCustomSupportAmount = (event: Event) => {
    const target = event.target as HTMLElement;
    customSupportAmount.value = normalizeNumberText(target.innerText);
    messageForm.amount = Number(customSupportAmount.value);
};

const normalizeCustomSupportAmountElement = () => {
    if (!customSupportAmountElement.value) {
        return;
    }

    const normalizedAmount = normalizeNumberText(
        customSupportAmountElement.value.innerText,
    );

    customSupportAmount.value = normalizedAmount;
    customSupportAmountElement.value.textContent =
        Number(normalizedAmount).toLocaleString();
    messageForm.amount = Number(normalizedAmount);
};

const handleCustomSupportAmountEnter = (event: KeyboardEvent) => {
    if (event.isComposing || isComposingCustomSupportAmount.value) {
        return;
    }

    event.preventDefault();
    normalizeCustomSupportAmountElement();
    customSupportAmountElement.value?.blur();
};

const sendMessage = () => {
    if (!canSendMessage.value) return;

    messageForm.amount = selectedSupportAmount.value;

    messageForm.post(messageStore.url(props.link.slug), {
        preserveScroll: true,
        onSuccess: () => {
            messageForm.reset('body');
            activeTab.value = 'read';
        },
    });
};

const togglePublish = (message: any) => {
    const newStatus = !message.is_public;
    useForm({ is_public: newStatus }).patch(messageUpdate.url(message.id), {
        preserveScroll: true,
    });
};

const deleteMessage = (message: any) => {
    if (!confirm('メッセージを削除しますか？')) return;

    useForm({}).delete(messageDestroy.url(message.id), {
        preserveScroll: true,
    });
};

const replyingToId = ref<string | null>(null);
const replyForm = useForm({
    reply_body: '',
});

const startReply = (message: any) => {
    replyingToId.value = message.id;
    replyForm.reply_body = message.reply_body || '';
};

const cancelReply = () => {
    replyingToId.value = null;
    replyForm.reset();
};

const submitReply = (message: any) => {
    replyForm.patch(messageUpdate.url(message.id), {
        preserveScroll: true,
        onSuccess: () => {
            replyingToId.value = null;
            replyForm.reset();
        },
    });
};

const getSenderName = (message: any) => {
    if (message.sender_mode === 'anonymous') {
        return 'とくめいさん';
    }
    return message.sender_display_name || 'ユーザー';
};

const getSenderInitial = (message: any) => {
    return getSenderName(message).charAt(0);
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('ja-JP', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};
</script>

<template>
    <Head :title="`${link.display_name} へメッセージ`" />

    <main class="relative min-h-screen overflow-hidden bg-white px-5 pt-5 text-gray-950">
        <div
            class="absolute inset-x-0 top-0 h-28 bg-black min-[1025px]:h-36"
            aria-hidden="true"
        ></div>

        <section
            class="relative z-10 mx-auto w-full max-w-[374px] pb-20 min-[1025px]:max-w-[1198px]"
        >
            <div
                class="min-[1025px]:grid min-[1025px]:grid-cols-12 min-[1025px]:gap-6"
            >
                <div class="min-[1025px]:order-1 min-[1025px]:col-span-4">
                    <div
                        class="relative mt-10 rounded-2xl border border-neutral-200 bg-white px-5 pt-12 shadow-[0_24px_70px_rgba(0,0,0,0.08)] min-[1025px]:mt-10 min-[1025px]:p-8 min-[1025px]:pt-14"
                    >
                        <div
                            class="absolute top-0 left-1/2 flex size-20 shrink-0 -translate-x-1/2 -translate-y-1/2 items-center justify-center overflow-hidden rounded-full border border-neutral-100 bg-gray-100 text-xl font-bold shadow-sm min-[1025px]:size-24"
                        >
                            <img
                                v-if="link.avatar_url"
                                :src="link.avatar_url"
                                :alt="link.display_name"
                                class="h-full w-full object-cover"
                            />
                            <span v-else>{{
                                link.display_name.charAt(0)
                            }}</span>
                        </div>

                        <div class="flex flex-col items-center text-center">
                            <Link
                                :href="linkShow.url(link.slug)"
                                class="group flex flex-wrap items-center justify-center gap-2"
                            >
                                <h1
                                    class="truncate text-xl font-black group-hover:underline group-hover:underline-offset-4 min-[1025px]:text-2xl"
                                >
                                    {{ link.display_name }}
                                </h1>
                                <span class="sr-only">プロフィールを見る</span>
                                <LinkIcon
                                    class="size-4 text-gray-400 transition-colors group-hover:text-black min-[1025px]:size-5"
                                />
                            </Link>

                            <div class="mt-2 w-full max-w-sm">
                                <div
                                    v-if="isEditingOneLiner"
                                    class="flex flex-col items-center gap-2"
                                >
                                    <input
                                        v-model="oneLinerForm.message_one_liner"
                                        type="text"
                                        class="w-full rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-1.5 text-sm outline-none focus:border-black focus:ring-1 focus:ring-black"
                                        placeholder="一言メッセージを入力..."
                                        maxlength="100"
                                        @keydown.enter="saveOneLiner"
                                        @keydown.esc="cancelEditingOneLiner"
                                    />
                                    <div class="flex gap-2">
                                        <button
                                            type="button"
                                            class="cursor-pointer text-xs font-bold text-gray-500 hover:text-black"
                                            @click="cancelEditingOneLiner"
                                        >
                                            キャンセル
                                        </button>
                                        <button
                                            type="button"
                                            class="cursor-pointer text-xs font-bold text-black"
                                            :disabled="oneLinerForm.processing"
                                            @click="saveOneLiner"
                                        >
                                            保存
                                        </button>
                                    </div>
                                </div>
                                <div
                                    v-else
                                    class="flex flex-wrap items-center justify-center gap-2"
                                >
                                    <p
                                        v-if="link.message_settings?.one_liner"
                                        class="text-sm font-medium text-gray-600"
                                    >
                                        {{ link.message_settings.one_liner }}
                                    </p>
                                    <button
                                        v-if="isOwner"
                                        type="button"
                                        class="flex items-center gap-1 text-xs font-bold text-gray-400 transition-colors hover:text-black"
                                        @click="startEditingOneLiner"
                                    >
                                        <PencilLine class="size-3" />
                                        <span>{{
                                            link.message_settings?.one_liner
                                                ? '編集'
                                                : '一言メッセージを追加'
                                        }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 min-[1025px]:mt-6">
                            <div
                                class="flex w-full items-center justify-center gap-10 min-[1025px]:hidden"
                            >
                                <button
                                    type="button"
                                    class="relative flex h-12 items-center justify-center gap-2 px-2 text-sm font-bold transition-colors"
                                    :class="
                                        activeTab === 'write'
                                            ? 'text-black after:absolute after:inset-x-0 after:bottom-0 after:h-1 after:bg-black'
                                            : 'text-gray-500 hover:text-gray-900'
                                    "
                                    @click="activeTab = 'write'"
                                >
                                    <PencilLine class="size-4" />
                                    書く
                                </button>
                                <button
                                    type="button"
                                    class="relative flex h-12 items-center justify-center gap-2 px-2 text-sm font-bold transition-colors"
                                    :class="
                                        activeTab === 'read'
                                            ? 'text-black after:absolute after:inset-x-0 after:bottom-0 after:h-1 after:bg-black'
                                            : 'text-gray-500 hover:text-gray-900'
                                    "
                                    @click="activeTab = 'read'"
                                >
                                    <MessageCircleHeart class="size-4" />
                                    読む
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-5 rounded-2xl border border-neutral-200 bg-white p-6 shadow-[0_20px_60px_rgba(0,0,0,0.06)] min-[1025px]:p-4"
                        :class="
                            activeTab === 'write'
                                ? 'block'
                                : 'hidden min-[1025px]:block'
                        "
                    >
                        <h2
                            class="flex flex-wrap items-center gap-2 text-[18px] leading-tight font-black tracking-normal min-[1025px]:text-[18px]"
                        >
                            <span>メッセージを送る</span>
                            <span
                                class="group relative inline-flex"
                                tabindex="0"
                            >
                                <HelpCircle
                                    class="size-4 shrink-0 stroke-[2.3]"
                                />
                                <span
                                    class="invisible absolute bottom-6 left-1/2 z-20 w-64 -translate-x-1/2 rounded-lg border border-neutral-200 bg-white px-3 py-2 text-center text-xs leading-5 font-medium text-gray-700 opacity-0 shadow-lg transition group-hover:visible group-hover:opacity-100 group-focus:visible group-focus:opacity-100"
                                >
                                    相手が安心して読める言葉を選びましょう。誹謗中傷や攻撃的な内容は送らないでください。
                                </span>
                            </span>
                        </h2>

                        <div
                            v-if="isOwner"
                            class="mt-8 rounded-[22px] border border-neutral-200 bg-neutral-50 p-5"
                        >
                            <p class="text-sm leading-6 text-neutral-600">
                                収益設定をすると、メッセージに添えられた差し入れを受け取れます。
                            </p>
                            <button
                                type="button"
                                class="mt-4 inline-flex h-12 items-center justify-center rounded-full bg-black px-6 text-sm font-bold text-white transition-colors hover:bg-neutral-800"
                            >
                                収益設定
                            </button>
                        </div>

                        <template v-else>
                            <div v-if="!authUser" class="mt-8 space-y-4">
                                <p
                                    class="text-center text-sm font-bold text-gray-500"
                                >
                                    メッセージを送るにはログインが必要です。
                                </p>
                                <Link
                                    :href="loginStore.url()"
                                    class="flex h-12 w-full items-center justify-center rounded-full bg-black text-sm font-bold text-white transition-colors hover:bg-neutral-800"
                                >
                                    ログインする
                                </Link>
                            </div>

                            <template v-else>
                                <label
                                    class="mt-5 flex cursor-pointer items-center gap-2 text-sm font-medium text-[#222326]"
                                >
                                    <input
                                        type="checkbox"
                                        class="peer sr-only"
                                        :checked="
                                            messageForm.sender_mode ===
                                            'anonymous'
                                        "
                                        @change="
                                            messageForm.sender_mode =
                                                messageForm.sender_mode ===
                                                'anonymous'
                                                    ? 'named'
                                                    : 'anonymous'
                                        "
                                    />
                                    <span
                                        class="flex size-5 shrink-0 items-center justify-center rounded-md border border-neutral-400 bg-white transition-colors peer-checked:border-black peer-checked:bg-black"
                                    >
                                        <Check
                                            v-if="
                                                messageForm.sender_mode ===
                                                'anonymous'
                                            "
                                            class="size-3.5 stroke-[3] text-white"
                                        />
                                    </span>
                                    <span>匿名で送る</span>
                                </label>

                                <div
                                    class="mt-3 flex items-center gap-3 rounded-xl bg-neutral-100 px-4 py-3"
                                >
                                    <div
                                        class="flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white text-sm font-bold text-gray-700"
                                    >
                                        <UserIcon
                                            v-if="
                                                messageForm.sender_mode ===
                                                'anonymous'
                                            "
                                            class="size-5"
                                        />
                                        <img
                                            v-else-if="authUser.avatar_url"
                                            :src="authUser.avatar_url"
                                            :alt="authUser.name"
                                            class="h-full w-full object-cover"
                                        />
                                        <span v-else>{{
                                            authUser.name?.charAt(0)
                                        }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold">
                                            {{
                                                messageForm.sender_mode ===
                                                'anonymous'
                                                    ? 'とくめいさん'
                                                    : authUser.name
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <label class="sr-only" for="support-message">
                                    Message
                                </label>
                                <div class="relative mt-4">
                                    <textarea
                                        id="support-message"
                                        v-model="messageForm.body"
                                        rows="5"
                                        placeholder="Say something nice..."
                                        class="min-h-28 w-full resize-none rounded-xl border border-transparent bg-neutral-100 px-4 pt-3 pb-10 text-sm leading-6 font-medium text-[#222326] transition outline-none placeholder:text-[#6f7788] focus:border-black focus:bg-white focus:ring-4 focus:ring-neutral-200"
                                        :disabled="messageForm.processing"
                                    ></textarea>
                                    <div
                                        v-if="messageForm.errors.body"
                                        class="mt-1 text-xs font-bold text-red-600"
                                    >
                                        {{ messageForm.errors.body }}
                                    </div>
                                </div>

                                <div
                                    class="mt-5 rounded-xl border border-neutral-200 bg-neutral-50/40 p-4"
                                >
                                    <div
                                        class="flex items-center justify-between gap-4"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            <p
                                                class="text-left text-sm font-bold text-gray-900"
                                            >
                                                差し入れ
                                            </p>
                                            <span
                                                class="group relative inline-flex"
                                                tabindex="0"
                                            >
                                                <HelpCircle
                                                    class="size-4 shrink-0 stroke-[2.3] text-gray-700"
                                                />
                                                <span
                                                    class="invisible absolute bottom-6 left-1/2 z-20 w-56 -translate-x-1/2 rounded-lg border border-neutral-200 bg-white px-3 py-2 text-center text-xs leading-5 font-medium text-gray-700 opacity-0 shadow-lg transition group-hover:visible group-hover:opacity-100 group-focus:visible group-focus:opacity-100"
                                                >
                                                    500円から差し入れを贈ることができます。
                                                </span>
                                            </span>
                                        </div>

                                        <button
                                            type="button"
                                            role="switch"
                                            :aria-checked="isSupportEnabled"
                                            aria-label="差し入れを有効にする"
                                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 focus:outline-none"
                                            :class="
                                                isSupportEnabled
                                                    ? 'bg-blue-600'
                                                    : 'bg-neutral-200'
                                            "
                                            @click="
                                                isSupportEnabled =
                                                    !isSupportEnabled
                                            "
                                        >
                                            <span
                                                class="pointer-events-none inline-block size-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                                :class="
                                                    isSupportEnabled
                                                        ? 'translate-x-5'
                                                        : 'translate-x-0'
                                                "
                                            ></span>
                                        </button>
                                    </div>

                                    <Transition
                                        enter-active-class="transition-all duration-300 ease-out"
                                        enter-from-class="max-h-0 opacity-0 overflow-hidden"
                                        enter-to-class="max-h-64 opacity-100 overflow-hidden"
                                        leave-active-class="transition-all duration-200 ease-in"
                                        leave-from-class="max-h-64 opacity-100 overflow-hidden"
                                        leave-to-class="max-h-0 opacity-0 overflow-hidden"
                                    >
                                        <div
                                            v-if="isSupportEnabled"
                                            class="mt-3"
                                        >
                                            <div
                                                class="flex w-full flex-wrap items-center justify-center gap-3"
                                            >
                                                <button
                                                    v-for="option in supportOptions"
                                                    :key="option.key"
                                                    type="button"
                                                    :aria-label="`${option.label} ${option.amount.toLocaleString()}円`"
                                                    class="flex size-12 shrink-0 cursor-pointer items-center justify-center rounded-full border text-[18px] font-black transition-colors"
                                                    :class="
                                                        selectedSupportOption ===
                                                        option.key
                                                            ? 'border-neutral-200 bg-neutral-200 text-black'
                                                            : 'border-neutral-200 bg-white text-black hover:border-black'
                                                    "
                                                    @click="
                                                        selectedSupportOption =
                                                            option.key
                                                    "
                                                >
                                                    {{ option.emoji }}
                                                </button>

                                                <button
                                                    type="button"
                                                    aria-label="金額を編集"
                                                    class="flex size-12 shrink-0 cursor-pointer items-center justify-center rounded-full border text-[18px] transition-colors"
                                                    :class="
                                                        selectedSupportOption ===
                                                        'custom'
                                                            ? 'border-neutral-200 bg-neutral-200 text-black'
                                                            : 'border-neutral-200 bg-white text-black hover:border-black'
                                                    "
                                                    @click="
                                                        selectCustomSupportAmount
                                                    "
                                                >
                                                    ❤️
                                                </button>
                                            </div>

                                            <label
                                                class="sr-only"
                                                for="support-amount"
                                            >
                                                Support amount
                                            </label>
                                            <div
                                                class="mt-4 flex h-12 items-center justify-center rounded-xl border border-transparent bg-white px-4 text-center text-lg font-black text-[#222326] transition focus-within:border-black focus-within:ring-4 focus-within:ring-neutral-200"
                                                :class="
                                                    selectedSupportOption ===
                                                    'custom'
                                                        ? 'border-black'
                                                        : ''
                                                "
                                            >
                                                <span
                                                    class="mr-1 text-neutral-500"
                                                    >¥</span
                                                >
                                                <div
                                                    v-if="
                                                        selectedSupportOption ===
                                                        'custom'
                                                    "
                                                    id="support-amount"
                                                    ref="customSupportAmountElement"
                                                    role="textbox"
                                                    inputmode="numeric"
                                                    aria-readonly="false"
                                                    contenteditable="true"
                                                    class="min-w-16 cursor-text outline-none"
                                                    @compositionstart="
                                                        isComposingCustomSupportAmount = true
                                                    "
                                                    @compositionend="
                                                        isComposingCustomSupportAmount = false
                                                    "
                                                    @input="
                                                        updateCustomSupportAmount
                                                    "
                                                    @keydown.enter="
                                                        handleCustomSupportAmountEnter
                                                    "
                                                    @blur="
                                                        normalizeCustomSupportAmountElement
                                                    "
                                                ></div>
                                                <div
                                                    v-else
                                                    class="min-w-16 cursor-default"
                                                >
                                                    {{ displayedSupportAmount }}
                                                </div>
                                            </div>
                                        </div>
                                    </Transition>
                                </div>

                                <label
                                    class="mt-5 flex cursor-pointer items-center gap-2 text-sm font-medium text-[#222326]"
                                >
                                    <input
                                        v-model="messageForm.is_public"
                                        type="checkbox"
                                        class="peer sr-only"
                                    />
                                    <span
                                        class="flex size-5 shrink-0 items-center justify-center rounded-md border border-neutral-400 bg-white transition-colors peer-checked:border-black peer-checked:bg-black"
                                    >
                                        <Check
                                            v-if="messageForm.is_public"
                                            class="size-3.5 stroke-[3] text-white"
                                        />
                                    </span>
                                    <span>公開メッセージ</span>
                                    <span
                                        class="group relative inline-flex"
                                        tabindex="0"
                                        @click.prevent
                                    >
                                        <HelpCircle
                                            class="size-4 shrink-0 stroke-[2.3] text-gray-500"
                                        />
                                        <span
                                            class="invisible absolute bottom-6 left-1/2 z-20 w-64 -translate-x-1/2 rounded-lg border border-neutral-200 bg-white px-3 py-2 text-center text-xs leading-5 font-medium text-gray-700 opacity-0 shadow-lg transition group-hover:visible group-hover:opacity-100 group-focus:visible group-focus:opacity-100"
                                        >
                                            クリエイターが開封したら自動で公開されます。チェックを外すとユーザが公開しなければ公開されません。
                                        </span>
                                    </span>
                                </label>

                                <button
                                    type="button"
                                    :disabled="
                                        !canSendMessage ||
                                        messageForm.processing
                                    "
                                    class="mt-5 flex h-12 w-full cursor-pointer items-center justify-center rounded-full bg-black px-5 text-base font-black text-white transition-colors hover:bg-neutral-800 disabled:cursor-not-allowed disabled:bg-neutral-300"
                                    @click="sendMessage"
                                >
                                    <span v-if="messageForm.processing"
                                        >送信中...</span
                                    >
                                    <span v-else>{{
                                        isSupportEnabled
                                            ? '差し入れを添えてメッセージを送る'
                                            : 'メッセージを送る'
                                    }}</span>
                                </button>
                            </template>
                        </template>
                    </div>
                </div>

                <div
                    class="mt-5 rounded-2xl border border-neutral-200 bg-white p-5 shadow-[0_20px_60px_rgba(0,0,0,0.07)] min-[1025px]:order-2 min-[1025px]:col-span-8 min-[1025px]:mt-10 min-[1025px]:p-8"
                    :class="
                        activeTab === 'read'
                            ? 'block'
                            : 'hidden min-[1025px]:block'
                    "
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <MessageCircle class="size-5 text-black" />
                            <h2 class="text-lg font-bold">届いたメッセージ</h2>
                        </div>
                    </div>

                    <div class="mt-5 space-y-4">
                        <div
                            v-if="messageCount === 0"
                            class="py-12 text-center"
                        >
                            <MessageCircleHeart
                                class="mx-auto size-12 text-gray-200"
                            />
                            <p class="mt-3 text-sm font-bold text-gray-400">
                                公開メッセージはまだありません
                            </p>
                        </div>

                        <article
                            v-for="message in messageItems"
                            :key="message.id"
                            class="group relative rounded-3xl border border-gray-100 bg-gray-50/70 p-5 transition-colors hover:border-gray-200"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-white text-sm font-bold text-gray-700 shadow-sm"
                                    >
                                        <UserIcon
                                            v-if="
                                                message.sender_mode ===
                                                'anonymous'
                                            "
                                            class="size-5"
                                        />
                                        <span v-else>{{
                                            getSenderInitial(message)
                                        }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold">{{
                                                getSenderName(message)
                                            }}</span>
                                            <span
                                                v-if="message.amount > 0"
                                                class="inline-flex items-center gap-1 text-xs font-black text-rose-500"
                                            >
                                                <Heart
                                                    class="size-3 fill-current"
                                                />
                                                ¥{{
                                                    message.amount.toLocaleString()
                                                }}
                                            </span>
                                        </div>
                                        <p
                                            class="text-[10px] font-bold text-gray-400"
                                        >
                                            {{ formatDate(message.created_at) }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    v-if="isOwner"
                                    class="flex items-center gap-1"
                                >
                                    <button
                                        type="button"
                                        class="flex size-8 cursor-pointer items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-white hover:text-black"
                                        :title="
                                            message.is_public
                                                ? '非公開にする'
                                                : '公開する'
                                        "
                                        @click="togglePublish(message)"
                                    >
                                        <Globe
                                            v-if="message.is_public"
                                            class="size-4 text-blue-500"
                                        />
                                        <Lock v-else class="size-4" />
                                    </button>

                                    <DropdownMenu>
                                        <DropdownMenuTrigger :as-child="true">
                                            <button
                                                type="button"
                                                class="flex size-8 cursor-pointer items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-white hover:text-black"
                                            >
                                                <MoreHorizontal
                                                    class="size-4"
                                                />
                                            </button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem
                                                @select="startReply(message)"
                                            >
                                                <Reply class="mr-2 size-4" />
                                                <span>返信する</span>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                class="text-red-600 focus:bg-red-50 focus:text-red-600"
                                                @select="deleteMessage(message)"
                                            >
                                                <Trash2 class="mr-2 size-4" />
                                                <span>削除する</span>
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </div>

                            <div class="mt-4">
                                <p
                                    class="text-sm leading-relaxed whitespace-pre-wrap text-gray-700"
                                >
                                    {{ message.body }}
                                </p>
                            </div>

                            <!-- Reply Section -->
                            <div
                                v-if="
                                    message.reply_body ||
                                    replyingToId === message.id
                                "
                                class="mt-5 rounded-2xl bg-white p-4 shadow-sm"
                            >
                                <div
                                    class="flex items-center gap-2 text-xs font-bold text-gray-400"
                                >
                                    <Reply class="size-3" />
                                    <span
                                        >{{
                                            link.display_name
                                        }}
                                        からの返信</span
                                    >
                                </div>

                                <div
                                    v-if="replyingToId === message.id"
                                    class="mt-3"
                                >
                                    <textarea
                                        v-model="replyForm.reply_body"
                                        class="min-h-24 w-full resize-none rounded-xl border-none bg-gray-50 p-3 text-sm outline-none focus:ring-2 focus:ring-black/5"
                                        placeholder="お返事を書く..."
                                    ></textarea>
                                    <div class="mt-2 flex justify-end gap-2">
                                        <button
                                            type="button"
                                            class="h-8 cursor-pointer rounded-lg px-3 text-xs font-bold text-gray-500 hover:bg-gray-100"
                                            @click="cancelReply"
                                        >
                                            キャンセル
                                        </button>
                                        <button
                                            type="button"
                                            class="h-8 cursor-pointer rounded-lg bg-black px-4 text-xs font-bold text-white hover:bg-neutral-800"
                                            :disabled="replyForm.processing"
                                            @click="submitReply(message)"
                                        >
                                            保存
                                        </button>
                                    </div>
                                </div>
                                <p
                                    v-else
                                    class="mt-2 text-sm leading-relaxed whitespace-pre-wrap text-gray-800"
                                >
                                    {{ message.reply_body }}
                                </p>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>
    </main>
</template>
