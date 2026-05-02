<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="Grid - The only link you'll ever need">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div
        class="min-h-screen bg-white text-neutral-900 selection:bg-neutral-900 selection:text-white dark:bg-neutral-950 dark:text-neutral-50 dark:selection:bg-white dark:selection:text-neutral-900"
    >
        <!-- Navigation -->
        <nav
            class="mx-auto flex w-full max-w-7xl items-center justify-between p-6"
        >
            <div class="flex items-center gap-2">
                <span class="text-2xl font-black tracking-tighter">GRID</span>
            </div>
            <div class="flex items-center gap-4 text-sm font-medium">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="transition-opacity hover:opacity-60"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="transition-opacity hover:opacity-60"
                    >
                        Sign in
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="register()"
                        class="rounded-full bg-neutral-900 px-4 py-2 text-white transition-opacity hover:opacity-90 dark:bg-neutral-50 dark:text-neutral-950"
                    >
                        Create account
                    </Link>
                </template>
            </div>
        </nav>

        <!-- Hero Section -->
        <main
            class="mx-auto flex max-w-7xl flex-col items-center px-6 pt-20 pb-32 text-center"
        >
            <h1
                class="mb-8 max-w-4xl text-6xl font-black tracking-tighter md:text-8xl"
            >
                The only link you'll ever need.
            </h1>
            <p
                class="mb-12 max-w-2xl text-lg leading-relaxed text-neutral-500 md:text-xl dark:text-neutral-400"
            >
                Connect your audience to everything you do with one simple,
                beautiful link. Perfect for creators, designers, and everyone in
                between.
            </p>

            <!-- Claim your link -->
            <div class="flex w-full max-w-md flex-col items-center gap-4">
                <div class="relative flex w-full items-center">
                    <span class="absolute left-4 font-medium text-neutral-400"
                        >grid.me/</span
                    >
                    <input
                        type="text"
                        placeholder="yourname"
                        class="w-full rounded-2xl border-none bg-neutral-100 py-4 pr-4 pl-[4.5rem] font-medium transition-shadow outline-none focus:ring-2 focus:ring-neutral-900 dark:bg-neutral-900 dark:focus:ring-white"
                    />
                    <button
                        class="absolute right-2 rounded-xl bg-neutral-900 px-6 py-2 font-bold text-white transition-opacity hover:opacity-90 dark:bg-neutral-50 dark:text-neutral-950"
                    >
                        Claim
                    </button>
                </div>
                <p class="text-xs text-neutral-400">
                    It's free, and always will be.
                </p>
            </div>

            <!-- Preview Mockup -->
            <div
                class="relative mt-24 aspect-[9/19] w-full max-w-xs origin-top scale-75 cursor-default overflow-hidden rounded-[3rem] border-8 border-neutral-200 bg-neutral-100 shadow-2xl transition-transform duration-500 hover:scale-[1.02] md:max-w-sm md:scale-100 dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div
                    class="absolute inset-0 flex flex-col items-center gap-8 p-8"
                >
                    <div
                        class="h-20 w-20 rounded-full bg-neutral-200 dark:bg-neutral-800"
                    ></div>
                    <div class="flex flex-col items-center gap-2">
                        <div
                            class="h-4 w-32 rounded-full bg-neutral-200 dark:bg-neutral-800"
                        ></div>
                        <div
                            class="h-3 w-24 rounded-full bg-neutral-200 opacity-50 dark:bg-neutral-800"
                        ></div>
                    </div>
                    <div class="mt-4 flex w-full flex-col gap-3">
                        <div
                            v-for="i in 4"
                            :key="i"
                            class="h-12 w-full rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950"
                        ></div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer
            class="border-t border-neutral-100 px-6 py-12 dark:border-neutral-900"
        >
            <div
                class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-8 text-sm text-neutral-400 md:flex-row"
            >
                <div class="flex items-center gap-2">
                    <span
                        class="font-black tracking-tighter text-neutral-900 dark:text-neutral-50"
                        >GRID</span
                    >
                    <span>© 2026 Grid Clone.</span>
                </div>
                <div class="flex gap-8">
                    <a
                        href="#"
                        class="transition-colors hover:text-neutral-900 dark:hover:text-neutral-50"
                        >Twitter</a
                    >
                    <a
                        href="#"
                        class="transition-colors hover:text-neutral-900 dark:hover:text-neutral-50"
                        >Privacy</a
                    >
                    <a
                        href="#"
                        class="transition-colors hover:text-neutral-900 dark:hover:text-neutral-50"
                        >Terms</a
                    >
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
@import url('https://rsms.me/inter/inter.css');
:root {
    font-family: 'Inter', sans-serif;
}
@supports (font-variation-settings: normal) {
    :root {
        font-family: 'Inter var', sans-serif;
    }
}
</style>
