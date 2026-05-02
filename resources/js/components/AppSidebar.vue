<script setup lang="ts">
import { urlIsActive } from '@/lib/utils';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import AppLogo from './AppLogo.vue';

interface Props {
    mainNavItems: NavItem[];
    footerNavItems?: NavItem[];
}

withDefaults(defineProps<Props>(), {
    footerNavItems: () => [],
});

const page = usePage();
</script>

<template>
    <div
        id="application-sidebar"
        class="hs-overlay fixed start-0 top-0 bottom-0 z-[60] hidden w-64 -translate-x-full transform overflow-y-auto border-e border-gray-200 bg-white pt-7 pb-10 transition-all duration-300 [--auto-close:lg] lg:end-auto lg:bottom-0 lg:block lg:translate-x-0 dark:border-neutral-700 dark:bg-neutral-800 hs-overlay-open:translate-x-0"
    >
        <div class="px-6">
            <Link
                :href="dashboard()"
                class="flex-none text-xl font-semibold dark:text-white"
                aria-label="Brand"
            >
                <AppLogo />
            </Link>
        </div>

        <nav
            class="hs-accordion-group flex w-full flex-col flex-wrap p-6"
            data-hs-accordion-always-open
        >
            <ul class="space-y-1.5">
                <li v-for="item in mainNavItems" :key="item.title">
                    <Link
                        class="flex items-center gap-x-3.5 rounded-lg px-2.5 py-2 text-sm text-neutral-700 hover:bg-gray-100 dark:bg-neutral-800 dark:text-neutral-400 dark:hover:text-neutral-300"
                        :class="{
                            'bg-gray-100 dark:bg-neutral-700 dark:text-white':
                                urlIsActive(item.href, page.url),
                        }"
                        :href="item.href"
                    >
                        <component
                            :is="item.icon"
                            v-if="item.icon"
                            class="size-4 flex-shrink-0"
                        />
                        {{ item.title }}
                    </Link>
                </li>
            </ul>

            <div v-if="footerNavItems.length > 0" class="mt-auto pt-10">
                <ul
                    class="space-y-1.5 border-t border-gray-200 pt-5 dark:border-neutral-700"
                >
                    <li v-for="item in footerNavItems" :key="item.title">
                        <a
                            class="flex items-center gap-x-3.5 rounded-lg px-2.5 py-2 text-sm text-neutral-700 hover:bg-gray-100 dark:text-neutral-400 dark:hover:text-neutral-300"
                            :href="item.href"
                            target="_blank"
                        >
                            <component
                                :is="item.icon"
                                v-if="item.icon"
                                class="size-4 flex-shrink-0"
                            />
                            {{ item.title }}
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</template>
