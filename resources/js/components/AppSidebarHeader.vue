<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getInitials } from '@/composables/useInitials';
import { dashboard } from '@/routes';
import type { BreadcrumbItemType } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Menu, Search } from 'lucide-vue-next';
import { computed } from 'vue';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const auth = computed(() => page.props.auth);
</script>

<template>
    <header
        class="sticky inset-x-0 top-0 z-[48] flex w-full flex-wrap py-2.5 text-sm sm:flex-nowrap sm:justify-start sm:py-4 lg:ps-64"
    >
        <nav
            class="mx-auto flex w-full basis-full items-center px-4 sm:px-6"
            aria-label="Global"
        >
            <div class="me-5 lg:me-0 lg:hidden">
                <Link
                    :href="dashboard()"
                    class="inline-block flex-none rounded-xl text-xl font-semibold focus:opacity-80 focus:outline-none"
                    aria-label="Brand"
                >
                    <AppLogoIcon
                        class="size-6 fill-current text-black dark:text-white"
                    />
                </Link>
            </div>

            <div
                class="ms-auto flex w-full items-center justify-end sm:justify-between sm:gap-x-3"
            >
                <div class="lg:hidden">
                    <button
                        type="button"
                        class="inline-flex size-9 cursor-pointer items-center justify-center rounded-lg border border-gray-200 bg-transparent text-gray-800 hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800"
                        data-hs-overlay="#application-sidebar"
                        aria-controls="application-sidebar"
                        aria-label="Toggle navigation"
                    >
                        <Menu class="size-4 flex-shrink-0" />
                    </button>
                </div>

                <div class="hidden sm:block">
                    <template v-if="breadcrumbs && breadcrumbs.length > 0">
                        <Breadcrumbs :breadcrumbs="breadcrumbs" />
                    </template>
                </div>

                <div class="flex flex-row items-center justify-end gap-2">
                    <button
                        type="button"
                        class="inline-flex h-[2.375rem] w-[2.375rem] cursor-pointer items-center justify-center gap-x-2 rounded-full border border-transparent text-sm font-semibold text-gray-800 hover:bg-gray-100 disabled:pointer-events-none disabled:opacity-50 dark:text-white dark:hover:bg-neutral-700"
                    >
                        <Search class="size-4 flex-shrink-0" />
                    </button>

                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative size-10 w-auto cursor-pointer rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                            >
                                <Avatar
                                    class="size-8 overflow-hidden rounded-full"
                                >
                                    <AvatarImage
                                        :src="auth.user.avatar_url"
                                        :alt="auth.user.name"
                                    />
                                    <AvatarFallback
                                        class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ getInitials(auth.user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </nav>
    </header>
</template>
