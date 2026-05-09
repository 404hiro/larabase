<script setup lang="ts">
import { dashboard } from '@/routes';
import { google as googleAuth } from '@/routes/auth';
import { Head, Link } from '@inertiajs/vue3';
import { 
    ArrowRight, 
    Heart, 
    MessageCircle, 
    LayoutGrid, 
    Sparkles, 
    Coffee, 
    ShieldCheck,
    ChevronRight
} from 'lucide-vue-next';

defineProps<{
    canRegister: boolean;
}>();
</script>

<template>
    <Head title="GRID - 自分を表現し、支援を受け、優しさでつながる">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen bg-[#fafafa] text-neutral-900 selection:bg-neutral-900 selection:text-white dark:bg-neutral-950 dark:text-neutral-50">
        <!-- Navigation -->
        <nav class="sticky top-0 z-50 border-b border-neutral-200/50 bg-white/80 backdrop-blur-md dark:border-neutral-800/50 dark:bg-neutral-950/80">
            <div class="mx-auto flex max-w-7xl items-center justify-between p-4 md:px-6">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-neutral-900 text-white dark:bg-white dark:text-neutral-900">
                        <LayoutGrid class="h-5 w-5" />
                    </div>
                    <span class="text-xl font-black tracking-tighter">GRID</span>
                </div>
                <div class="flex items-center gap-6 text-sm font-semibold">
                    <Link v-if="$page.props.auth.user" :href="dashboard().url" class="transition-colors hover:text-neutral-500">
                        Dashboard
                    </Link>
                    <template v-else>
                        <a :href="googleAuth().url" class="transition-colors hover:text-neutral-500">ログイン</a>
                        <a :href="googleAuth().url" class="hidden rounded-full bg-neutral-900 px-5 py-2 text-white transition-all hover:scale-105 active:scale-95 sm:block dark:bg-white dark:text-neutral-900">
                            無料で始める
                        </a>
                    </template>
                </div>
            </div>
        </nav>

        <main>
            <!-- Hero Section -->
            <section class="mx-auto max-w-7xl px-6 pt-20 pb-16 text-center md:pt-32">
                <div class="inline-flex items-center gap-2 rounded-full border border-neutral-200 bg-white px-3 py-1 text-xs font-medium shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <Sparkles class="h-3 w-3 text-amber-500" />
                    <span>次世代のクリエイターページ</span>
                </div>
                <h1 class="mt-8 text-5xl font-[900] tracking-tight md:text-8xl lg:text-9xl">
                    Express.<br />
                    Get Support.<br />
                    Be Kind.
                </h1>
                <p class="mx-auto mt-8 max-w-2xl text-lg leading-relaxed text-neutral-500 md:text-xl dark:text-neutral-400">
                    GRIDは、Bentoスタイルの美しいポートフォリオ、匿名メッセージ、そしてファンからの支援を一箇所に集約。
                    あなただけの「居場所」を、数秒で作れます。
                </p>

                <!-- URL Claim -->
                <div class="mx-auto mt-12 flex w-full max-w-lg flex-col items-center gap-4">
                    <a :href="googleAuth().url" class="group relative flex w-full items-center overflow-hidden rounded-2xl border-2 border-neutral-200 bg-white p-1 transition-all focus-within:border-neutral-900 dark:border-neutral-800 dark:bg-neutral-900 dark:focus-within:border-white">
                        <span class="pl-4 font-bold text-neutral-400">grid.me/</span>
                        <div class="flex-1 bg-transparent py-4 text-left font-bold text-neutral-400">yourname</div>
                        <div class="mr-1 rounded-xl bg-neutral-900 px-8 py-3 font-bold text-white transition-transform group-hover:translate-x-0.5 dark:bg-white dark:text-neutral-900">
                            Claim
                        </div>
                    </a>
                    <div class="flex items-center gap-2 text-xs text-neutral-400">
                        <ShieldCheck class="h-3 w-3" />
                        <span>Googleログインで即座に開始できます。</span>
                    </div>
                </div>
            </section>

            <!-- Feature Bento Grid -->
            <section class="mx-auto max-w-7xl px-6 py-24">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3 md:grid-rows-2">
                    <!-- Bento Box 1: Design -->
                    <div class="group relative overflow-hidden rounded-[2.5rem] bg-white p-8 shadow-sm ring-1 ring-neutral-200 transition-all hover:shadow-xl md:col-span-2 dark:bg-neutral-900 dark:ring-neutral-800">
                        <div class="flex h-full flex-col justify-between">
                            <div>
                                <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/30">
                                    <LayoutGrid class="h-6 w-6" />
                                </div>
                                <h3 class="text-2xl font-bold">Bentoスタイルの美学</h3>
                                <p class="mt-2 text-neutral-500 dark:text-neutral-400">
                                    リンク、画像、SNS、音楽。すべてを美しいグリッドに。
                                    直感的なドラッグ＆ドロップで、あなたらしいレイアウトを。
                                </p>
                            </div>
                            <div class="mt-8 grid grid-cols-4 gap-2 opacity-40 transition-opacity group-hover:opacity-100">
                                <div v-for="i in 4" :key="i" class="h-20 rounded-xl bg-neutral-100 dark:bg-neutral-800"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Bento Box 2: Support -->
                    <div class="group overflow-hidden rounded-[2.5rem] bg-gradient-to-br from-amber-50 to-orange-50 p-8 shadow-sm ring-1 ring-amber-200/50 transition-all hover:shadow-xl dark:from-amber-900/10 dark:to-orange-900/10 dark:ring-amber-900/20">
                        <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-white text-amber-600 shadow-sm">
                            <Coffee class="h-6 w-6" />
                        </div>
                        <h3 class="text-2xl font-bold">手軽な支援</h3>
                        <p class="mt-2 text-neutral-600 dark:text-neutral-400">
                            コーヒーを一杯奢る感覚で。
                            複雑な審査なしで、ファンからの感謝を直接受け取れます。
                        </p>
                        <div class="mt-8 flex justify-center">
                            <div class="rounded-full bg-white px-6 py-3 font-bold shadow-sm">
                                ☕ Buy a Coffee
                            </div>
                        </div>
                    </div>

                    <!-- Bento Box 3: Kindness -->
                    <div class="group overflow-hidden rounded-[2.5rem] bg-neutral-900 p-8 shadow-sm transition-all hover:shadow-xl dark:bg-white">
                        <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-white dark:bg-neutral-900/10 dark:text-neutral-900">
                            <Heart class="h-6 w-6" />
                        </div>
                        <h3 class="text-2xl font-bold text-white dark:text-neutral-900">匿名の優しさ</h3>
                        <p class="mt-2 text-neutral-400 dark:text-neutral-500">
                            「マシュマロ」のように温かいメッセージを。
                            GRID独自のAIフィルターが不適切な言葉を遮断し、あなたに「優しさ」だけを届けます。
                        </p>
                        <div class="mt-8 flex gap-2">
                            <div class="h-8 w-24 rounded-full bg-white/10 dark:bg-neutral-900/10"></div>
                            <div class="h-8 w-16 rounded-full bg-white/20 dark:bg-neutral-900/20"></div>
                        </div>
                    </div>

                    <!-- Bento Box 4: Interactive -->
                    <div class="group overflow-hidden rounded-[2.5rem] bg-white p-8 shadow-sm ring-1 ring-neutral-200 transition-all hover:shadow-xl md:col-span-2 dark:bg-neutral-900 dark:ring-neutral-800">
                        <div class="flex flex-col items-center gap-6 md:flex-row">
                            <div class="flex-1">
                                <h3 class="text-2xl font-bold">会話を、安全に。</h3>
                                <p class="mt-2 text-neutral-500 dark:text-neutral-400">
                                    ファンとの交流をもっと身近に。
                                    Q&A形式で、あなたの活動をより深く知ってもらいましょう。
                                </p>
                            </div>
                            <div class="flex flex-col gap-2">
                                <div class="flex items-center gap-3 rounded-2xl bg-neutral-50 p-4 dark:bg-neutral-800">
                                    <MessageCircle class="h-5 w-5 text-neutral-400" />
                                    <span class="text-sm font-medium">新作のインスピレーションは？</span>
                                </div>
                                <div class="flex items-center gap-3 rounded-2xl bg-neutral-900 p-4 text-white dark:bg-white dark:text-neutral-900">
                                    <Sparkles class="h-5 w-5" />
                                    <span class="text-sm font-medium">夜の散歩中に思いつきました。</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Social Proof / CTA -->
            <section class="bg-neutral-900 py-24 text-white dark:bg-white dark:text-neutral-900">
                <div class="mx-auto max-w-4xl px-6 text-center">
                    <h2 class="text-4xl font-bold md:text-6xl">
                        あなたの活動を、<br />
                        ひとつのGRIDに。
                    </h2>
                    <p class="mt-6 text-xl text-neutral-400">
                        すでに数千人のクリエイターがGRIDで新しい表現を始めています。
                    </p>
                    <div class="mt-12 flex flex-col items-center justify-center gap-4 sm:flex-row">
                        <a :href="googleAuth().url" class="flex items-center gap-2 rounded-full bg-white px-8 py-4 text-lg font-bold text-neutral-900 transition-transform hover:scale-105 active:scale-95 dark:bg-neutral-900 dark:text-white">
                            今すぐ無料で始める
                            <ChevronRight class="h-5 w-5" />
                        </a>
                        <span class="text-sm text-neutral-500">初期費用・月額利用料 0円</span>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-t border-neutral-200 py-20 dark:border-neutral-900">
            <div class="mx-auto max-w-7xl px-6">
                <div class="flex flex-col items-center justify-between gap-8 md:flex-row">
                    <div class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <div class="flex h-6 w-6 items-center justify-center rounded bg-neutral-900 text-white dark:bg-white dark:text-neutral-900">
                                <LayoutGrid class="h-4 w-4" />
                            </div>
                            <span class="text-lg font-black tracking-tighter">GRID</span>
                        </div>
                        <p class="text-sm text-neutral-400">
                            Express yourself. Get supported. Be kind.
                        </p>
                    </div>
                    <div class="flex gap-12 text-sm font-medium">
                        <div class="flex flex-col gap-3">
                            <span class="font-bold text-neutral-900 dark:text-white">Product</span>
                            <a href="#" class="text-neutral-500 hover:text-neutral-900 dark:hover:text-white">Explore</a>
                            <a href="#" class="text-neutral-500 hover:text-neutral-900 dark:hover:text-white">Creators</a>
                            <a href="#" class="text-neutral-500 hover:text-neutral-900 dark:hover:text-white">Pricing</a>
                        </div>
                        <div class="flex flex-col gap-3">
                            <span class="font-bold text-neutral-900 dark:text-white">Company</span>
                            <a href="#" class="text-neutral-500 hover:text-neutral-900 dark:hover:text-white">About</a>
                            <a href="#" class="text-neutral-500 hover:text-neutral-900 dark:hover:text-white">Twitter</a>
                            <a href="#" class="text-neutral-500 hover:text-neutral-900 dark:hover:text-white">Privacy</a>
                        </div>
                    </div>
                </div>
                <div class="mt-20 flex items-center justify-between border-t border-neutral-100 pt-8 dark:border-neutral-900">
                    <p class="text-xs text-neutral-400">© 2026 GRID Inc. All rights reserved.</p>
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
