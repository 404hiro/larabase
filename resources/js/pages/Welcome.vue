<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import {
    ChevronRight,
    LayoutGrid,
    MessageCircle,
    ShieldCheck,
    Sparkles,
    Image as ImageIcon,
    Share2,
    Lock,
    Zap,
    Gift,
    Smartphone,
    CheckCircle2,
    ArrowRight,
    Plus,
    MousePointer2,
} from 'lucide-vue-next';

defineProps<{
    canRegister: boolean;
}>();
</script>

<template>
    <Head title="Grid.Link - プロフィールに、メッセージが届く。">
    </Head>

    <div
        class="min-h-screen bg-[#f9fafb] text-neutral-900 selection:bg-neutral-900 selection:text-white bg-dot-pattern"
    >
        <!-- Navigation -->
        <nav
            class="sticky top-0 z-50 border-b border-neutral-200/50 bg-white/70 backdrop-blur-xl"
        >
            <div
                class="mx-auto flex max-w-7xl items-center justify-between p-4 md:px-6"
            >
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-neutral-900 text-white shadow-lg shadow-neutral-200"
                    >
                        <LayoutGrid class="h-5 w-5" />
                    </div>
                    <span class="text-xl font-[900] tracking-tighter"
                        >Grid.Link</span
                    >
                </div>
                <div class="flex items-center gap-4 sm:gap-8 text-sm font-bold">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard().url"
                        class="transition-colors hover:text-neutral-500"
                    >
                        ダッシュボード
                    </Link>
                    <template v-else>
                        <Link
                            :href="login().url"
                            class="transition-colors hover:text-neutral-500 hidden sm:block"
                            >ログイン</Link
                        >
                        <Link
                            :href="register().url"
                            class="rounded-full bg-neutral-900 px-6 py-2.5 text-white transition-all hover:bg-neutral-800 hover:shadow-lg active:scale-95"
                        >
                            はじめる
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <main>
            <!-- Hero Section -->
            <section
                class="relative mx-auto max-w-7xl px-6 pt-24 pb-20 text-center md:pt-40"
            >
                <div
                    class="inline-flex animate-fade-in-up items-center gap-2 rounded-full border border-neutral-200 bg-white px-4 py-1.5 text-xs font-bold text-neutral-600 shadow-sm"
                >
                    <Sparkles class="h-3.5 w-3.5 text-yellow-500" />
                    <span>クリエイターのための、新しいプロフィール。</span>
                </div>
                <h1
                    class="mt-10 animate-fade-in-up text-5xl font-[1000] tracking-tight md:text-8xl [text-wrap:balance]"
                    style="animation-delay: 100ms;"
                >
                    あなたの世界を、<br class="hidden md:block" />1枚のグリッドに。
                </h1>
                <p
                    class="mx-auto mt-10 max-w-2xl animate-fade-in-up text-lg font-medium leading-relaxed text-neutral-500 md:text-2xl"
                    style="animation-delay: 200ms;"
                >
                    Grid.Link（グリッドリンク）は、リンクや画像、テキストを自由に並べて、あなただけの美しいプロフィールページを作れるサービスです。
                </p>

                <div class="mt-14 flex animate-fade-in-up flex-col items-center justify-center gap-4 sm:flex-row" style="animation-delay: 300ms;">
                    <Link
                        :href="register().url"
                        class="group relative flex items-center gap-2 rounded-full bg-neutral-900 px-10 py-5 text-xl font-black text-white transition-all hover:bg-neutral-800 hover:shadow-2xl hover:shadow-neutral-300 active:scale-95"
                    >
                        無料で作成を開始
                        <ArrowRight class="h-6 w-6 transition-transform group-hover:translate-x-1" />
                    </Link>
                </div>

                <!-- Abstract Visual -->
                <div class="mt-24 grid animate-fade-in-up grid-cols-2 gap-4 md:mt-32 md:grid-cols-4 lg:gap-8" style="animation-delay: 400ms;">
                    <div class="aspect-square rounded-[2rem] bg-white p-6 shadow-xl shadow-neutral-200/50 ring-1 ring-neutral-200/50">
                        <div class="h-full w-full rounded-2xl bg-neutral-50 flex items-center justify-center">
                            <ImageIcon class="h-12 w-12 text-neutral-200" />
                        </div>
                    </div>
                    <div class="aspect-square translate-y-8 rounded-[2rem] bg-white p-6 shadow-xl shadow-neutral-200/50 ring-1 ring-neutral-200/50">
                        <div class="h-full w-full rounded-2xl bg-neutral-50 flex items-center justify-center">
                            <LinkIcon class="h-12 w-12 text-neutral-200" />
                        </div>
                    </div>
                    <div class="aspect-square rounded-[2rem] bg-white p-6 shadow-xl shadow-neutral-200/50 ring-1 ring-neutral-200/50">
                        <div class="h-full w-full rounded-2xl bg-neutral-50 flex items-center justify-center">
                            <MessageCircle class="h-12 w-12 text-neutral-200" />
                        </div>
                    </div>
                    <div class="aspect-square translate-y-8 rounded-[2rem] bg-white p-6 shadow-xl shadow-neutral-200/50 ring-1 ring-neutral-200/50">
                        <div class="h-full w-full rounded-2xl bg-neutral-50 flex items-center justify-center">
                            <MousePointer2 class="h-12 w-12 text-neutral-200" />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Grid Layout Section -->
            <section class="mx-auto max-w-7xl px-6 py-32">
                <div class="rounded-[3.5rem] bg-neutral-900 p-12 text-white shadow-3xl md:p-24 overflow-hidden relative">
                    <div class="absolute top-0 right-0 -translate-y-1/2 translate-x-1/2 size-96 rounded-full bg-white/5 blur-3xl"></div>
                    <div class="relative z-10 grid grid-cols-1 gap-16 lg:grid-cols-2 lg:items-center">
                        <div>
                            <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-sm font-bold text-white">
                                <Plus class="h-4 w-4" />
                                <span>Bento Grid Layout</span>
                            </div>
                            <h2 class="mt-8 text-4xl font-black leading-tight md:text-6xl">
                                カードを並べて、<br />個性を表現する。
                            </h2>
                            <p class="mt-8 text-xl leading-relaxed text-neutral-400">
                                決められたテンプレートはありません。グリッド上にウィジェットを自由に配置して、あなたらしいページを構築しましょう。
                            </p>
                            <div class="mt-12 space-y-6">
                                <div class="flex items-start gap-4">
                                    <div class="mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white text-neutral-900">
                                        <CheckCircle2 class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold">レスポンシブ対応</h3>
                                        <p class="text-neutral-500 mt-1">スマホでもPCでも、最適なレイアウトで表示されます。</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-4">
                                    <div class="mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white text-neutral-900">
                                        <CheckCircle2 class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold">直感的な編集</h3>
                                        <p class="text-neutral-500 mt-1">ドラッグ＆ドロップでパズルのように組み立てられます。</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="relative">
                            <div class="grid grid-cols-2 gap-4 rotate-3">
                                <div class="h-40 rounded-3xl bg-white/5 backdrop-blur-sm border border-white/10"></div>
                                <div class="h-40 rounded-3xl bg-white/10 backdrop-blur-sm border border-white/20"></div>
                                <div class="col-span-2 h-64 rounded-3xl bg-gradient-to-br from-white/20 to-transparent backdrop-blur-md border border-white/30"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Features Cards -->
            <section class="mx-auto max-w-7xl px-6 py-32">
                <div class="mb-20 text-center">
                    <h2 class="text-4xl font-black md:text-6xl">プロフィールを超えた機能。</h2>
                    <p class="mt-6 text-xl font-medium text-neutral-500">ただのリンク集ではない、クリエイターのためのツールボックス。</p>
                </div>
                <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                    <!-- Feature 1 -->
                    <div class="group rounded-[2.5rem] bg-white p-10 shadow-xl shadow-neutral-200/50 ring-1 ring-neutral-200/50 transition-all hover:-translate-y-2">
                        <div class="mb-8 flex h-14 w-14 items-center justify-center rounded-2xl bg-neutral-50 text-neutral-900 transition-colors group-hover:bg-neutral-900 group-hover:text-white">
                            <MessageCircle class="h-7 w-7" />
                        </div>
                        <h3 class="text-2xl font-black">匿名メッセージ</h3>
                        <p class="mt-4 leading-relaxed text-neutral-500">
                            プロフィールから直接メッセージを受け取れます。匿名でも実名でも、やさしい交流を。
                        </p>
                    </div>
                    <!-- Feature 2 -->
                    <div class="group rounded-[2.5rem] bg-white p-10 shadow-xl shadow-neutral-200/50 ring-1 ring-neutral-200/50 transition-all hover:-translate-y-2">
                        <div class="mb-8 flex h-14 w-14 items-center justify-center rounded-2xl bg-neutral-50 text-neutral-900 transition-colors group-hover:bg-neutral-900 group-hover:text-white">
                            <Gift class="h-7 w-7" />
                        </div>
                        <h3 class="text-2xl font-black">差し入れ機能</h3>
                        <p class="mt-4 leading-relaxed text-neutral-500">
                            メッセージにコーヒー1杯からの応援を添えて。自然な形でクリエイターを支援できます。
                        </p>
                    </div>
                    <!-- Feature 3 -->
                    <div class="group rounded-[2.5rem] bg-white p-10 shadow-xl shadow-neutral-200/50 ring-1 ring-neutral-200/50 transition-all hover:-translate-y-2">
                        <div class="mb-8 flex h-14 w-14 items-center justify-center rounded-2xl bg-neutral-50 text-neutral-900 transition-colors group-hover:bg-neutral-900 group-hover:text-white">
                            <Share2 class="h-7 w-7" />
                        </div>
                        <h3 class="text-2xl font-black">SNSシェア画像</h3>
                        <p class="mt-4 leading-relaxed text-neutral-500">
                            届いたメッセージを美しい画像として生成。XやInstagramでの共有が楽しくなります。
                        </p>
                    </div>
                </div>
            </section>

            <!-- Security Section -->
            <section class="py-32">
                <div class="mx-auto max-w-7xl px-6">
                    <div class="rounded-[3.5rem] border border-neutral-200/60 bg-white/50 p-12 backdrop-blur-sm md:p-24">
                        <div class="flex flex-col gap-12 lg:flex-row lg:items-center">
                            <div class="lg:w-1/2">
                                <h2 class="text-4xl font-black md:text-5xl">安全な場所を、<br />あなたに。</h2>
                                <p class="mt-8 text-xl leading-relaxed text-neutral-500">
                                    クリエイターが安心してメッセージを募集できるよう、ログイン必須の送信システムやAIフィルターによる保護機能を提供しています。
                                </p>
                            </div>
                            <div class="grid flex-1 grid-cols-1 gap-6 sm:grid-cols-2 lg:w-1/2">
                                <div class="rounded-3xl bg-white p-8 shadow-lg shadow-neutral-100 ring-1 ring-neutral-100">
                                    <Lock class="h-8 w-8 text-neutral-900" />
                                    <h4 class="mt-4 font-black">ログイン必須</h4>
                                </div>
                                <div class="rounded-3xl bg-white p-8 shadow-lg shadow-neutral-100 ring-1 ring-neutral-100">
                                    <Zap class="h-8 w-8 text-neutral-900" />
                                    <h4 class="mt-4 font-black">AIフィルター</h4>
                                </div>
                                <div class="rounded-3xl bg-white p-8 shadow-lg shadow-neutral-100 ring-1 ring-neutral-100">
                                    <ShieldCheck class="h-8 w-8 text-neutral-900" />
                                    <h4 class="mt-4 font-black">通報・ブロック</h4>
                                </div>
                                <div class="rounded-3xl bg-white p-8 shadow-lg shadow-neutral-100 ring-1 ring-neutral-100">
                                    <Smartphone class="h-8 w-8 text-neutral-900" />
                                    <h4 class="mt-4 font-black">スマホ完結</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Final Call to Action -->
            <section class="mx-auto max-w-7xl px-6 py-40 text-center">
                <h2 class="text-5xl font-[1000] tracking-tight md:text-8xl">さあ、グリッドを<br />はじめよう。</h2>
                <p class="mt-10 text-xl font-medium text-neutral-500 md:text-2xl">
                    数分で自分だけのプロフィールページを作成し、<br class="hidden md:block" />新しいつながりをスタートさせましょう。
                </p>
                <div class="mt-16">
                    <Link
                        :href="register().url"
                        class="inline-flex items-center gap-2 rounded-full bg-neutral-900 px-12 py-6 text-2xl font-black text-white shadow-3xl shadow-neutral-300 transition-all hover:bg-neutral-800 hover:scale-105 active:scale-95"
                    >
                        無料で作成
                        <ChevronRight class="h-8 w-8" />
                    </Link>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer
            class="border-t border-neutral-200/50 py-24"
        >
            <div class="mx-auto max-w-7xl px-6">
                <div
                    class="flex flex-col items-center justify-between gap-12 md:flex-row md:items-start"
                >
                    <div class="flex flex-col gap-6 text-center md:text-left">
                        <div class="flex items-center justify-center gap-2.5 md:justify-start">
                            <div
                                class="flex h-7 w-7 items-center justify-center rounded-lg bg-neutral-900 text-white"
                            >
                                <LayoutGrid class="h-4 w-4" />
                            </div>
                            <span class="text-xl font-[1000] tracking-tighter"
                                >Grid.Link</span
                            >
                        </div>
                        <p class="max-w-xs text-sm font-medium leading-relaxed text-neutral-400">
                            クリエイターの個性をグリッドに。
                            Express yourself. Get supported.
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-16 text-sm">
                        <div class="flex flex-col gap-4">
                            <span class="font-black text-neutral-900 uppercase tracking-widest text-[10px]">プロダクト</span>
                            <Link href="#" class="font-bold text-neutral-500 hover:text-neutral-900">サンプル</Link>
                            <Link href="#" class="font-bold text-neutral-500 hover:text-neutral-900">料金プラン</Link>
                        </div>
                        <div class="flex flex-col gap-4">
                            <span class="font-black text-neutral-900 uppercase tracking-widest text-[10px]">サポート</span>
                            <Link href="#" class="font-bold text-neutral-500 hover:text-neutral-900">利用規約</Link>
                            <Link href="#" class="font-bold text-neutral-500 hover:text-neutral-900">プライバシー</Link>
                            <Link href="#" class="font-bold text-neutral-500 hover:text-neutral-900">お問い合わせ</Link>
                        </div>
                    </div>
                </div>
                <div
                    class="mt-24 flex flex-col items-center justify-between border-t border-neutral-100 pt-10 md:flex-row"
                >
                    <p class="text-[10px] font-black uppercase tracking-widest text-neutral-300">
                        © 2026 Grid.Link Inc.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.bg-dot-pattern {
    background-image: radial-gradient(circle, #e5e7eb 1.2px, transparent 1.2px);
    background-size: 32px 32px;
}

@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fade-in-up 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
    opacity: 0;
}

.shadow-3xl {
    box-shadow: 0 35px 60px -15px rgba(0, 0, 0, 0.3);
}
</style>

