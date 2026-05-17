<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getInitials } from '@/composables/useInitials';
import { Link, usePage } from '@inertiajs/vue3';
import { Bell, Ellipsis } from 'lucide-vue-next';
import { computed } from 'vue';

defineProps<{
    slug: string;
    activeTab?: 'profile' | 'message';
}>();

const page = usePage();
const auth = computed(() => page.props.auth);
const isLoggedIn = computed(() => Boolean(auth.value?.user));

</script>

<template>
    <nav
        class="fixed inset-x-0 top-0 z-[9000] h-9 border-b border-gray-200 bg-white/95 px-3 text-gray-900 backdrop-blur-md sm:px-4"
        aria-label="プロフィールナビゲーション"
    >
        <div
            class="mx-auto flex h-full w-full max-w-[374px] items-center px-3 min-[1025px]:max-w-[1198px] sm:px-4"
        >
            <Link
                v-if="isLoggedIn"
                :href="`/@${slug}`"
                class="min-w-0 flex-1 truncate text-base font-bold transition-colors hover:text-gray-700"
            >
                @{{ slug }}
            </Link>
            <Link
                v-else
                href="/"
                class="min-w-0 flex-1 truncate text-base font-bold transition-colors hover:text-gray-700"
            >
                @{{ slug }}
            </Link>

            <div class="flex flex-1 items-center justify-center"></div>

            <div class="flex flex-1 items-center justify-end gap-2 text-sm font-semibold">
                <template v-if="isLoggedIn && auth.user">
                    <button
                        type="button"
                        aria-label="通知"
                        title="通知"
                        class="flex size-7 cursor-pointer items-center justify-center rounded-full text-gray-800 transition-colors hover:bg-gray-100"
                    >
                        <Bell class="size-4" />
                    </button>
                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative size-8 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                                aria-label="アカウントメニュー"
                                title="アカウントメニュー"

                            >
                                <Avatar class="size-6 overflow-hidden rounded-full">
                                    <AvatarImage
                                        :src="auth.user.avatar_url"
                                        :alt="auth.user.name"
                                    />
                                    <AvatarFallback
                                        class="rounded-lg bg-neutral-200 text-xs font-semibold text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ getInitials(auth.user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="z-[9001] w-56">
                            <UserMenuContent :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </template>
                <template v-else>
                    <button
                        type="button"
                        aria-label="その他"
                        class="flex size-7 cursor-pointer items-center justify-center rounded-full text-gray-800 transition-colors hover:bg-gray-100"
                    >
                        <Ellipsis class="size-5" />
                    </button>
                    <Link
                        href="/login"
                        class="text-gray-800 transition-colors hover:text-gray-950"
                    >
                        Login
                    </Link>
                </template>
            </div>
        </div>
    </nav>
</template>
