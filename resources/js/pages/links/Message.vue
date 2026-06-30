<script setup lang="ts">
import {
    show as linkShow,
    update as linkUpdate,
} from '@/actions/App/Http/Controllers/LinkController';
import { store as messageStore } from '@/actions/App/Http/Controllers/MessageController';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { store as loginStore } from '@/routes/login/index';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    Check,
    Copy,
    HelpCircle,
    LinkIcon,
    Paintbrush,
    PencilLine,
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
            background_color?: string | null;
        } | null;
    };
    can_receive_payments: boolean;
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
const copiedMessageUrl = ref(false);
const oneLinerEditor = ref<HTMLElement | null>(null);
const isBackgroundColorDialogOpen = ref(false);
const oneLinerForm = useForm({
    display_name: props.link.display_name,
    message_one_liner: props.link.message_settings?.one_liner ?? '',
    message_background_color:
        props.link.message_settings?.background_color ?? '#000000',
});

const backgroundColorSwatches = [
    '#000000',
    '#111827',
    '#7F1D1D',
    '#14532D',
    '#1E3A8A',
    '#581C87',
    '#BE123C',
    '#F97316',
];

const messageBackgroundColor = computed(() => {
    return /^#[0-9a-f]{6}$/i.test(oneLinerForm.message_background_color)
        ? oneLinerForm.message_background_color
        : '#000000';
});

const updateMessageBackgroundColor = (color: string) => {
    if (!/^#[0-9a-f]{6}$/i.test(color)) {
        return;
    }

    oneLinerForm.message_background_color = color.toUpperCase();
};

const messagePageUrl = computed(() => {
    if (typeof window === 'undefined') {
        return `/@${props.link.slug}/message`;
    }

    return `${window.location.origin}/@${props.link.slug}/message`;
});

const copyMessageUrl = async () => {
    try {
        if (navigator.clipboard) {
            await navigator.clipboard.writeText(messagePageUrl.value);
        } else {
            const textArea = document.createElement('textarea');
            textArea.value = messagePageUrl.value;
            textArea.setAttribute('readonly', 'true');
            textArea.className = 'fixed -left-[9999px] top-0';
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }

        copiedMessageUrl.value = true;
        window.setTimeout(() => {
            copiedMessageUrl.value = false;
        }, 2400);
    } catch (error) {
        console.error('Failed to copy message URL:', error);
    }
};

const startEditingOneLiner = async () => {
    isEditingOneLiner.value = true;

    await nextTick();

    if (!oneLinerEditor.value) {
        return;
    }

    oneLinerEditor.value.textContent = oneLinerForm.message_one_liner;
    oneLinerEditor.value.focus();

    const selection = window.getSelection();
    const range = document.createRange();
    range.selectNodeContents(oneLinerEditor.value);
    selection?.removeAllRanges();
    selection?.addRange(range);
};

const updateOneLiner = () => {
    oneLinerForm.message_one_liner = oneLinerEditor.value?.innerText ?? '';
};

const handleOneLinerEnter = (event: KeyboardEvent) => {
    if (event.isComposing) {
        return;
    }

    event.preventDefault();
    saveOneLiner();
};

const saveOneLiner = () => {
    oneLinerForm.put(linkUpdate.url(props.link.slug), {
        preserveScroll: true,
        onSuccess: () => {
            isEditingOneLiner.value = false;
        },
    });
};

const supportOptions = [
    { key: 'coffee', emoji: '☕', amount: 500, label: 'コーヒー' },
    { key: 'cake', emoji: '🍰', amount: 1000, label: 'ケーキ' },
    { key: 'bouquet', emoji: '💐', amount: 1500, label: 'ブーケ' },
] as const;

type SupportOptionKey = (typeof supportOptions)[number]['key'] | 'custom';

const isSupportEnabled = ref(false);
const selectedSupportOption = ref<SupportOptionKey>('coffee');
const customSupportAmount = ref('500');
const customSupportAmountElement = ref<HTMLElement | null>(null);
const isComposingCustomSupportAmount = ref(false);

const messageForm = useForm({
    body: '',
    sender_mode: 'named' as 'anonymous' | 'named',
    sender_display_name: null as string | null,
    has_gift: false,
    gift_amount: null as number | null,
    gift_label: null as string | null,
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

const supportBreakdown = computed(() => {
    if (!isSupportEnabled.value || !canOfferSupport.value) {
        return null;
    }

    const amount = selectedSupportAmount.value;
    const platformFee = Math.floor(amount * 0.1);
    const creatorPayout = Math.max(0, amount - platformFee);

    return {
        amount,
        platformFee,
        creatorPayout,
    };
});

const displayedSupportAmount = computed(() => {
    return formattedSupportAmount.value;
});

const selectedSupportName = computed(() => {
    if (selectedSupportOption.value === 'custom') {
        return 'ラブ♡';
    }

    return (
        supportOptions.find((option) => {
            return option.key === selectedSupportOption.value;
        })?.label ?? supportOptions[0].label
    );
});

const messageFormErrors = computed(() => {
    return Object.values(messageForm.errors).flat().filter(Boolean) as string[];
});

const canSendMessage = computed(() => {
    return (
        authUser.value && !isOwner.value && messageForm.body.trim().length > 0
    );
});

const canOfferSupport = computed(() => {
    return props.can_receive_payments;
});

const paymentStatus = computed(() => {
    const params = new URLSearchParams((page.url ?? '').split('?')[1] ?? '');

    return params.get('payment');
});

const paymentNotice = computed(() => {
    if (paymentStatus.value === 'success') {
        return {
            title: '差し入れありがとうございます！',
            description: 'メッセージと差し入れの手続きを受け付けました。',
            type: 'success',
        };
    }

    if (paymentStatus.value === 'cancel') {
        return {
            title: '差し入れはキャンセルされました。',
            description: '送信待ち状態のメッセージをキャンセルしました。',
            type: 'warning',
        };
    }

    return null;
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
    messageForm.gift_amount = Number(customSupportAmount.value);
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
    messageForm.gift_amount = Number(normalizedAmount);
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

    const shouldSendSupport = isSupportEnabled.value && canOfferSupport.value;

    messageForm.has_gift = shouldSendSupport;
    messageForm.gift_amount = shouldSendSupport
        ? selectedSupportAmount.value
        : null;
    messageForm.gift_label = shouldSendSupport
        ? selectedSupportName.value
        : null;

    messageForm.post(messageStore.url(props.link.slug), {
        preserveScroll: true,
        onSuccess: () => {
            messageForm.reset('body');
            isSupportEnabled.value = false;
        },
    });
};
</script>

<template>
    <Head :title="`${link.display_name} へメッセージ`" />

    <main
        class="relative min-h-screen overflow-hidden bg-white px-5 pt-5 text-gray-950"
    >
        <div
            v-if="paymentNotice"
            class="mx-auto mb-4 max-w-[600px] rounded-2xl border border-neutral-200 bg-white px-5 py-4 text-sm shadow-[0_18px_30px_rgba(0,0,0,0.06)] min-[1025px]:px-6"
        >
            <p class="font-bold text-gray-900">{{ paymentNotice.title }}</p>
            <p class="mt-1 text-gray-700">{{ paymentNotice.description }}</p>
        </div>
        <div
            class="absolute inset-x-0 top-0 h-28 transition-colors min-[1025px]:h-36"
            :style="{ backgroundColor: messageBackgroundColor }"
            aria-hidden="true"
        ></div>
        <button
            v-if="isOwner && isEditingOneLiner"
            type="button"
            class="absolute top-5 right-5 z-20 flex size-10 cursor-pointer items-center justify-center rounded-full border border-white/20 bg-white/95 text-gray-900 shadow-[0_14px_36px_rgba(0,0,0,0.2)] transition-colors hover:bg-white"
            aria-label="背景色を変更"
            @click="isBackgroundColorDialogOpen = true"
        >
            <Paintbrush class="size-5" />
        </button>

        <section class="relative z-10 mx-auto w-full max-w-[600px] pb-20">
            <div
                class="relative mt-10 rounded-2xl border border-neutral-200 bg-white px-5 pt-12 pb-6 shadow-[0_24px_70px_rgba(0,0,0,0.08)] min-[1025px]:mt-10 min-[1025px]:p-8 min-[1025px]:pt-14"
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
                    <span v-else>{{ link.display_name.charAt(0) }}</span>
                </div>

                <div class="flex flex-col items-center text-center">
                    <button
                        v-if="isOwner"
                        type="button"
                        disabled
                        aria-disabled="true"
                        class="group flex cursor-not-allowed flex-wrap items-center justify-center gap-2 opacity-60"
                    >
                        <h1
                            class="truncate text-xl font-black min-[1025px]:text-2xl"
                        >
                            {{ link.display_name }}
                        </h1>
                        <span class="sr-only">プロフィールを見る</span>
                        <LinkIcon
                            class="size-4 text-gray-400 min-[1025px]:size-5"
                        />
                    </button>
                    <Link
                        v-else
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
                            <p
                                ref="oneLinerEditor"
                                role="textbox"
                                contenteditable="true"
                                data-placeholder="ひとことを入力"
                                :data-empty="
                                    oneLinerForm.message_one_liner.trim()
                                        .length === 0
                                "
                                class="message-one-liner-editor min-h-8 w-full rounded-lg border border-neutral-300 bg-neutral-100 px-3 py-1.5 text-center text-sm font-medium text-gray-600 outline-none focus:border-black focus:ring-1 focus:ring-black"
                                @input="updateOneLiner"
                                @keydown.enter="handleOneLinerEnter"
                            ></p>
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
                            <p
                                v-else-if="isOwner"
                                class="text-sm font-medium text-gray-400"
                            >
                                一言メッセージ未設定
                            </p>
                        </div>
                    </div>
                </div>

                <div class="pt-6">
                    <h2
                        class="flex flex-wrap items-center gap-2 text-[18px] leading-tight font-black tracking-normal min-[1025px]:text-[18px]"
                    >
                        <span>メッセージを送る</span>
                        <span class="group relative inline-flex" tabindex="0">
                            <HelpCircle class="size-4 shrink-0 stroke-[2.3]" />
                            <span
                                class="invisible absolute bottom-6 left-1/2 z-20 w-64 -translate-x-1/2 rounded-lg border border-neutral-200 bg-white px-3 py-2 text-center text-xs leading-5 font-medium text-gray-700 opacity-0 shadow-lg transition group-hover:visible group-hover:opacity-100 group-focus:visible group-focus:opacity-100"
                            >
                                相手が安心して読める言葉を選びましょう。誹謗中傷や攻撃的な内容は送らないでください。
                            </span>
                        </span>
                    </h2>

                    <div
                        v-if="isOwner && !can_receive_payments"
                        class="mt-8 rounded-[22px] border border-neutral-200 bg-neutral-50 p-5"
                    >
                        <p class="text-sm leading-6 text-neutral-600">
                            収益設定をすると、メッセージに添えられた差し入れを受け取れます。
                        </p>
                        <Link
                            href="/settings#revenue"
                            class="mt-4 inline-flex h-12 items-center justify-center rounded-full bg-black px-6 text-sm font-bold text-white transition-colors hover:bg-neutral-800"
                        >
                            収益設定
                        </Link>
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
                                        messageForm.sender_mode === 'anonymous'
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
                                    v-if="messageFormErrors.length"
                                    class="mt-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                                >
                                    <p
                                        v-for="(
                                            error, index
                                        ) in messageFormErrors"
                                        :key="index"
                                    >
                                        {{ error }}
                                    </p>
                                </div>

                                <div
                                    v-if="messageForm.errors.body"
                                    class="mt-1 text-xs font-bold text-red-600"
                                >
                                    {{ messageForm.errors.body }}
                                </div>
                            </div>

                            <div
                                v-if="canOfferSupport"
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
                                            isSupportEnabled = !isSupportEnabled
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
                                    <div v-if="isSupportEnabled" class="mt-3">
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
                                            class="mt-4 flex min-h-16 flex-col items-center justify-center rounded-xl border border-transparent bg-white px-4 py-2 text-center text-[#222326] transition focus-within:border-black focus-within:ring-4 focus-within:ring-neutral-200"
                                            :class="
                                                selectedSupportOption ===
                                                'custom'
                                                    ? 'border-black'
                                                    : ''
                                            "
                                        >
                                            <div
                                                class="text-xs font-bold text-neutral-500"
                                            >
                                                {{ selectedSupportName }}
                                            </div>
                                            <div
                                                class="mt-1 flex items-center justify-center text-lg font-black"
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

                                        <div
                                            v-if="supportBreakdown"
                                            class="mt-3 rounded-xl border border-neutral-200 bg-neutral-50 px-3 py-3 text-sm text-gray-700"
                                        >
                                            <div
                                                class="flex items-center justify-between"
                                            >
                                                <span>支援金額</span>
                                                <span class="font-semibold"
                                                    >¥{{
                                                        supportBreakdown.amount.toLocaleString()
                                                    }}</span
                                                >
                                            </div>
                                            <div
                                                class="mt-2 flex items-center justify-between text-gray-600"
                                            >
                                                <span>運用手数料（10%）</span>
                                                <span
                                                    >¥{{
                                                        supportBreakdown.platformFee.toLocaleString()
                                                    }}</span
                                                >
                                            </div>
                                            <div
                                                class="mt-2 flex items-center justify-between text-gray-600"
                                            >
                                                <span
                                                    >クリエイター受け取り額</span
                                                >
                                                <span
                                                    >¥{{
                                                        supportBreakdown.creatorPayout.toLocaleString()
                                                    }}</span
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </Transition>
                            </div>

                            <button
                                type="button"
                                :disabled="
                                    !canSendMessage || messageForm.processing
                                "
                                class="mt-5 flex h-12 w-full cursor-pointer items-center justify-center rounded-full bg-black px-5 text-base font-black text-white transition-colors hover:bg-neutral-800 disabled:cursor-not-allowed disabled:bg-neutral-300"
                                @click.prevent.stop="sendMessage"
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

                            <div
                                v-if="isSupportEnabled && supportBreakdown"
                                class="mt-4 rounded-xl border border-blue-100 bg-blue-50/70 px-3 py-3 text-sm text-blue-900"
                            >
                                <p class="font-semibold">差し入れが有効です</p>
                                <p class="mt-1">
                                    クリエイターへは ¥{{
                                        supportBreakdown.creatorPayout.toLocaleString()
                                    }}
                                    が届きます。
                                </p>
                            </div>

                            <p
                                v-if="
                                    authUser &&
                                    !isOwner &&
                                    !messageForm.processing &&
                                    messageForm.body.trim().length === 0
                                "
                                class="mt-3 text-sm text-gray-500"
                            >
                                メッセージ本文を入力すると送信できます。
                            </p>
                        </template>
                    </template>
                </div>
            </div>
        </section>

        <div
            v-if="isOwner"
            class="fixed inset-x-0 bottom-4 z-[9005] flex justify-center px-4"
            aria-label="メッセージページアクション"
        >
            <div
                class="flex h-11 items-center gap-2 rounded-full border border-neutral-200 bg-white/95 p-1 shadow-[0_18px_50px_rgba(0,0,0,0.16)] backdrop-blur-md"
            >
                <div class="relative">
                    <div
                        v-if="copiedMessageUrl"
                        class="absolute bottom-full left-1/2 mb-2 -translate-x-1/2 rounded-lg bg-black px-3 py-1.5 text-xs font-bold whitespace-nowrap text-white shadow-lg"
                    >
                        URLをコピーしました
                    </div>
                    <button
                        type="button"
                        class="flex h-9 cursor-pointer items-center justify-center gap-2 rounded-full bg-black px-5 text-sm font-bold text-white transition-colors hover:bg-neutral-800"
                        :aria-label="isEditingOneLiner ? '保存' : 'シェア'"
                        :disabled="oneLinerForm.processing"
                        @click="
                            isEditingOneLiner
                                ? saveOneLiner()
                                : copyMessageUrl()
                        "
                    >
                        <Check
                            v-if="isEditingOneLiner || copiedMessageUrl"
                            class="size-4 text-white"
                        />
                        <Copy v-else class="size-4" />
                        {{ isEditingOneLiner ? '保存' : 'シェア' }}
                    </button>
                </div>

                <button
                    v-if="!isEditingOneLiner"
                    type="button"
                    class="flex h-9 cursor-pointer items-center justify-center gap-2 rounded-full px-5 text-sm font-bold text-gray-800 transition-colors hover:bg-gray-100"
                    aria-label="一言を編集"
                    @click="startEditingOneLiner"
                >
                    <PencilLine class="size-4" />
                    編集
                </button>
            </div>
        </div>

        <Dialog v-model:open="isBackgroundColorDialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>背景色を変更</DialogTitle>
                </DialogHeader>

                <div class="space-y-5">
                    <div
                        class="h-28 rounded-xl border border-neutral-200 transition-colors"
                        :style="{ backgroundColor: messageBackgroundColor }"
                        aria-hidden="true"
                    ></div>

                    <div class="grid grid-cols-4 gap-3">
                        <button
                            v-for="color in backgroundColorSwatches"
                            :key="color"
                            type="button"
                            class="flex h-12 cursor-pointer items-center justify-center rounded-xl border transition-colors"
                            :class="
                                messageBackgroundColor === color
                                    ? 'border-black ring-2 ring-black/10'
                                    : 'border-neutral-200 hover:border-neutral-400'
                            "
                            :style="{ backgroundColor: color }"
                            :aria-label="`背景色 ${color}`"
                            @click="updateMessageBackgroundColor(color)"
                        >
                            <Check
                                v-if="messageBackgroundColor === color"
                                class="size-4 text-white"
                            />
                        </button>
                    </div>

                    <label
                        class="flex items-center justify-between gap-4 rounded-xl border border-neutral-200 p-3"
                    >
                        <span class="text-sm font-bold text-gray-800"
                            >カラーを選択</span
                        >
                        <input
                            :value="messageBackgroundColor"
                            type="color"
                            class="size-10 cursor-pointer rounded-lg border border-neutral-200 bg-white p-1"
                            aria-label="背景色のカラーピッカー"
                            @input="
                                updateMessageBackgroundColor(
                                    ($event.target as HTMLInputElement).value,
                                )
                            "
                        />
                    </label>
                </div>
            </DialogContent>
        </Dialog>

        <footer
            v-if="!isOwner"
            class="px-5 pt-2 pb-24 text-center text-xs font-semibold text-gray-400"
        >
            <Link href="/" class="transition-colors hover:text-gray-700">
                Built with GridLink
            </Link>
        </footer>
    </main>
</template>

<style scoped>
.message-one-liner-editor[data-empty='true']::before {
    color: rgb(156 163 175);
    content: attr(data-placeholder);
    pointer-events: none;
}
</style>
