<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import {
    ChevronRight,
    Coffee,
    Heart,
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
    CheckCircle2
} from 'lucide-vue-next';

defineProps<{
    canRegister: boolean;
}>();
</script>

<template>
    <Head title="Grid.Link - プロフィールに、メッセージが届く。">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div
        class="min-h-screen bg-white text-neutral-900 selection:bg-neutral-900 selection:text-white"
    >
        <!-- Navigation -->
        <nav
            class="sticky top-0 z-50 border-b border-neutral-100 bg-white/80 backdrop-blur-md"
        >
            <div
                class="mx-auto flex max-w-7xl items-center justify-between p-4 md:px-6"
            >
                <div class="flex items-center gap-2">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-neutral-900 text-white"
                    >
                        <LayoutGrid class="h-5 w-5" />
                    </div>
                    <span class="text-xl font-black tracking-tighter"
                        >Grid.Link</span
                    >
                </div>
                <div class="flex items-center gap-6 text-sm font-semibold">
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
                            class="transition-colors hover:text-neutral-500"
                            >ログイン</Link
                        >
                        <Link
                            :href="register().url"
                            class="rounded-full bg-neutral-900 px-5 py-2 text-white transition-all hover:scale-105 active:scale-95"
                        >
                            無料で始める
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <main>
            <!-- Hero Section -->
            <section
                class="mx-auto max-w-7xl px-6 pt-20 pb-16 text-center md:pt-32"
            >
                <div
                    class="inline-flex items-center gap-2 rounded-full border border-neutral-100 bg-neutral-50 px-3 py-1 text-xs font-medium text-neutral-600"
                >
                    <Sparkles class="h-3 w-3" />
                    <span>美しいプロフィールと、やさしいメッセージ受け取り</span>
                </div>
                <h1
                    class="mt-8 text-4xl font-[900] tracking-tight md:text-7xl"
                >
                    プロフィールに、<br class="md:hidden" />メッセージが届く。
                </h1>
                <p
                    class="mx-auto mt-8 max-w-2xl text-lg leading-relaxed text-neutral-500 md:text-xl"
                >
                    Grid.Linkは、美しいプロフィールページを作り、フォロワーから匿名メッセージを受け取れるサービスです。メッセージには、任意で差し入れを添えることもできます。
                </p>

                <div class="mt-12 flex flex-col items-center justify-center gap-4 sm:flex-row">
                    <Link
                        :href="register().url"
                        class="flex items-center gap-2 rounded-full bg-neutral-900 px-8 py-4 text-lg font-bold text-white transition-transform hover:scale-105 active:scale-95"
                    >
                        無料で始める
                        <ChevronRight class="h-5 w-5" />
                    </Link>
                    <Link
                        href="#"
                        class="flex items-center gap-2 rounded-full border border-neutral-200 bg-white px-8 py-4 text-lg font-bold text-neutral-900 transition-all hover:bg-neutral-50"
                    >
                        サンプルを見る
                    </Link>
                </div>

                <!-- Visual Mockup -->
                <div class="mt-20 flex flex-col items-center justify-center gap-8 md:flex-row">
                    <!-- Profile Mockup -->
                    <div class="w-full max-w-[320px] overflow-hidden rounded-[2.5rem] border border-neutral-200 bg-white p-6 shadow-2xl transition-all hover:-translate-y-1">
                        <div class="mb-6 flex flex-col items-center">
                            <div class="h-20 w-20 rounded-full bg-neutral-100"></div>
                            <div class="mt-4 h-4 w-32 rounded-full bg-neutral-100"></div>
                            <div class="mt-2 h-3 w-48 rounded-full bg-neutral-50"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="h-24 rounded-2xl bg-neutral-50"></div>
                            <div class="h-24 rounded-2xl bg-neutral-50"></div>
                            <div class="col-span-2 h-32 rounded-2xl bg-neutral-50"></div>
                        </div>
                    </div>
                    <!-- Message Mockup -->
                    <div class="w-full max-w-[320px] overflow-hidden rounded-[2.5rem] border border-neutral-200 bg-white p-6 shadow-2xl transition-all hover:-translate-y-1 md:mt-12">
                        <div class="flex flex-col gap-4">
                            <div class="flex items-center gap-3">
                                <MessageCircle class="h-5 w-5 text-neutral-400" />
                                <div class="h-4 w-24 rounded-full bg-neutral-100"></div>
                            </div>
                            <div class="h-32 w-full rounded-2xl border border-neutral-100 bg-neutral-50 p-4">
                                <div class="h-3 w-3/4 rounded-full bg-neutral-200"></div>
                                <div class="mt-2 h-3 w-1/2 rounded-full bg-neutral-100"></div>
                            </div>
                            <div class="flex justify-between items-center">
                                <div class="flex gap-2">
                                    <div class="h-8 w-8 rounded-full bg-neutral-50 flex items-center justify-center">☕</div>
                                    <div class="h-8 w-8 rounded-full bg-neutral-50 flex items-center justify-center">🍰</div>
                                </div>
                                <div class="h-10 w-24 rounded-full bg-neutral-900"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Features -->
            <section class="mx-auto max-w-7xl px-6 py-24">
                <div class="grid grid-cols-1 gap-12 md:grid-cols-2 lg:gap-24">
                    <!-- 1. Bento Profile -->
                    <div class="flex flex-col justify-center">
                        <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-2xl bg-neutral-50 text-neutral-900">
                            <LayoutGrid class="h-6 w-6" />
                        </div>
                        <h2 class="text-3xl font-bold tracking-tight md:text-4xl">
                            自分らしいプロフィールを、<br />気持ちよく作る。
                        </h2>
                        <p class="mt-6 text-lg leading-relaxed text-neutral-500">
                            リンク、画像、テキスト、動画をカードのように並べて、SNSに貼れるプロフィールページを作れます。余白とグリッドにこだわった、シンプルで美しい編集体験を目指しています。
                        </p>
                        <ul class="mt-8 space-y-4 text-neutral-600">
                            <li class="flex items-center gap-3">
                                <CheckCircle2 class="h-5 w-5 text-neutral-900" />
                                <span>SNSリンクをまとめられる</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <CheckCircle2 class="h-5 w-5 text-neutral-900" />
                                <span>画像やテキストを自由に配置</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <CheckCircle2 class="h-5 w-5 text-neutral-900" />
                                <span>URLは grid.link/@username</span>
                            </li>
                        </ul>
                    </div>
                    <div class="rounded-[2.5rem] bg-neutral-50 p-8 ring-1 ring-neutral-200">
                        <div class="aspect-square w-full overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-neutral-100">
                            <!-- Placeholder for actual feature screenshot or animation -->
                            <div class="flex h-full items-center justify-center text-neutral-200">
                                <Smartphone class="h-32 w-32" />
                            </div>
                        </div>
                    </div>

                    <!-- 2. Anonymous Message -->
                    <div class="order-last flex flex-col justify-center md:order-none">
                        <div class="rounded-[2.5rem] bg-neutral-50 p-8 ring-1 ring-neutral-200">
                            <div class="aspect-square w-full overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-neutral-100">
                                <div class="flex h-full items-center justify-center text-neutral-200">
                                    <MessageCircle class="h-32 w-32" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col justify-center">
                        <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-2xl bg-neutral-50 text-neutral-900">
                            <MessageCircle class="h-6 w-6" />
                        </div>
                        <h2 class="text-3xl font-bold tracking-tight md:text-4xl">
                            フォロワーから、<br />匿名メッセージが届く。
                        </h2>
                        <p class="mt-6 text-lg leading-relaxed text-neutral-500">
                            プロフィールからそのままメッセージページへ。感想、応援、質問などを、匿名またはユーザー名付きで受け取れます。
                        </p>
                        <div class="mt-8 rounded-2xl border border-neutral-100 bg-neutral-50 p-6">
                            <div class="flex gap-4">
                                <Lock class="mt-1 h-5 w-5 text-neutral-400" />
                                <p class="text-sm text-neutral-600">
                                    メッセージ送信にはログインが必要なので、完全匿名サービスよりも安心して使えます。
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Share to SNS -->
                    <div class="flex flex-col justify-center">
                        <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-2xl bg-neutral-50 text-neutral-900">
                            <Share2 class="h-6 w-6" />
                        </div>
                        <h2 class="text-3xl font-bold tracking-tight md:text-4xl">
                            届いたメッセージを、<br />きれいな画像でシェア。
                        </h2>
                        <p class="mt-6 text-lg leading-relaxed text-neutral-500">
                            受け取ったメッセージは、画像化してXやInstagramストーリーに投稿できます。返信と一緒に共有すれば、自然に新しいメッセージも集まります。
                        </p>
                        <ul class="mt-8 flex flex-wrap gap-4">
                            <li class="rounded-full border border-neutral-200 px-4 py-2 text-sm font-medium">X (旧Twitter) 向け</li>
                            <li class="rounded-full border border-neutral-200 px-4 py-2 text-sm font-medium">Instagram ストーリー向け</li>
                            <li class="rounded-full border border-neutral-200 px-4 py-2 text-sm font-medium">正方形サイズ</li>
                        </ul>
                    </div>
                    <div class="rounded-[2.5rem] bg-neutral-50 p-8 ring-1 ring-neutral-200">
                        <div class="aspect-square w-full overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-neutral-100">
                            <div class="flex h-full items-center justify-center text-neutral-200">
                                <ImageIcon class="h-32 w-32" />
                            </div>
                        </div>
                    </div>

                    <!-- 4. Sashiire -->
                    <div class="order-last flex flex-col justify-center md:order-none">
                        <div class="rounded-[2.5rem] bg-neutral-50 p-8 ring-1 ring-neutral-200">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex aspect-square flex-col items-center justify-center rounded-2xl bg-white p-4 shadow-sm ring-1 ring-neutral-100 transition-all hover:scale-105">
                                    <span class="text-4xl">☕</span>
                                    <span class="mt-2 text-sm font-bold">コーヒー</span>
                                </div>
                                <div class="flex aspect-square flex-col items-center justify-center rounded-2xl bg-white p-4 shadow-sm ring-1 ring-neutral-100 transition-all hover:scale-105">
                                    <span class="text-4xl">🍰</span>
                                    <span class="mt-2 text-sm font-bold">おやつ</span>
                                </div>
                                <div class="flex aspect-square flex-col items-center justify-center rounded-2xl bg-white p-4 shadow-sm ring-1 ring-neutral-100 transition-all hover:scale-105">
                                    <span class="text-4xl">💐</span>
                                    <span class="mt-2 text-sm font-bold">ブーケ</span>
                                </div>
                                <div class="flex aspect-square flex-col items-center justify-center rounded-2xl bg-white p-4 shadow-sm ring-1 ring-neutral-100 transition-all hover:scale-105">
                                    <span class="text-4xl">✨</span>
                                    <span class="mt-2 text-sm font-bold">キラキラ</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col justify-center">
                        <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-2xl bg-neutral-50 text-neutral-900">
                            <Gift class="h-6 w-6" />
                        </div>
                        <h2 class="text-3xl font-bold tracking-tight md:text-4xl">
                            メッセージに、<br />差し入れを添えられる。
                        </h2>
                        <p class="mt-6 text-lg leading-relaxed text-neutral-500">
                            メッセージには、任意で差し入れを添えることができます。お金を前面に出さず、感想や応援の気持ちを自然に届けられる設計です。
                        </p>
                    </div>
                </div>
            </section>

            <!-- Safety -->
            <section class="bg-neutral-50 py-24">
                <div class="mx-auto max-w-7xl px-6">
                    <div class="text-center">
                        <h2 class="text-3xl font-bold md:text-4xl">安心してメッセージを募集できる。</h2>
                        <p class="mt-4 text-neutral-500">荒らしやスパム対策のための仕組みを用意しています。</p>
                    </div>
                    <div class="mt-16 grid grid-cols-1 gap-8 md:grid-cols-3">
                        <div class="rounded-3xl bg-white p-8 ring-1 ring-neutral-100">
                            <div class="mb-4 h-10 w-10 rounded-xl bg-neutral-50 p-2 text-neutral-900">
                                <Lock class="h-6 w-6" />
                            </div>
                            <h3 class="text-xl font-bold">ログイン必須</h3>
                            <p class="mt-2 text-neutral-500">送信者はGoogleなどのアカウントでログインが必要です。</p>
                        </div>
                        <div class="rounded-3xl bg-white p-8 ring-1 ring-neutral-100">
                            <div class="mb-4 h-10 w-10 rounded-xl bg-neutral-50 p-2 text-neutral-900">
                                <Zap class="h-6 w-6" />
                            </div>
                            <h3 class="text-xl font-bold">AIフィルター</h3>
                            <p class="mt-2 text-neutral-500">不適切な表現を自動的に検知し、あなたを保護します。</p>
                        </div>
                        <div class="rounded-3xl bg-white p-8 ring-1 ring-neutral-100">
                            <div class="mb-4 h-10 w-10 rounded-xl bg-neutral-50 p-2 text-neutral-900">
                                <ShieldCheck class="h-6 w-6" />
                            </div>
                            <h3 class="text-xl font-bold">通報とブロック</h3>
                            <p class="mt-2 text-neutral-500">嫌なユーザーを簡単にブロックしたり、通報したりできます。</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Usage -->
            <section class="mx-auto max-w-7xl px-6 py-24">
                <h2 class="text-center text-3xl font-bold md:text-4xl">使い方はシンプル。</h2>
                <div class="mt-16 grid grid-cols-1 gap-8 md:grid-cols-4">
                    <div class="text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-neutral-900 text-xl font-bold text-white">1</div>
                        <h3 class="mt-6 text-lg font-bold">プロフィールを作る</h3>
                    </div>
                    <div class="text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-neutral-900 text-xl font-bold text-white">2</div>
                        <h3 class="mt-6 text-lg font-bold">SNSにURLを貼る</h3>
                    </div>
                    <div class="text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-neutral-900 text-xl font-bold text-white">3</div>
                        <h3 class="mt-6 text-lg font-bold">メッセージを受け取る</h3>
                    </div>
                    <div class="text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-neutral-900 text-xl font-bold text-white">4</div>
                        <h3 class="mt-6 text-lg font-bold">画像でシェアする</h3>
                    </div>
                </div>
            </section>

            <!-- Pricing -->
            <section class="mx-auto max-w-4xl px-6 py-24">
                <div class="rounded-[3rem] border border-neutral-200 bg-white p-12 text-center shadow-xl">
                    <h2 class="text-4xl font-black md:text-5xl">無料ではじめられます。</h2>
                    <p class="mt-6 text-xl text-neutral-500">
                        プロフィール作成とメッセージ受け取りは無料で使えます。差し入れがあった場合のみ、決済手数料とサービス運営手数料がかかります。
                    </p>
                    <div class="mt-12">
                        <Link
                            :href="register().url"
                            class="inline-flex items-center gap-2 rounded-full bg-neutral-900 px-10 py-5 text-xl font-bold text-white transition-transform hover:scale-105 active:scale-95"
                        >
                            今すぐ無料で始める
                            <ChevronRight class="h-6 w-6" />
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer
            class="border-t border-neutral-100 py-20"
        >
            <div class="mx-auto max-w-7xl px-6">
                <div
                    class="flex flex-col items-center justify-between gap-8 md:flex-row"
                >
                    <div class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <div
                                class="flex h-6 w-6 items-center justify-center rounded bg-neutral-900 text-white"
                            >
                                <LayoutGrid class="h-4 w-4" />
                            </div>
                            <span class="text-lg font-black tracking-tighter"
                                >Grid.Link</span
                            >
                        </div>
                        <p class="text-sm text-neutral-400">
                            Express yourself. Get supported. Be kind.
                        </p>
                    </div>
                    <div class="flex gap-12 text-sm font-medium">
                        <div class="flex flex-col gap-3">
                            <span class="font-bold text-neutral-900">プロダクト</span>
                            <a href="#" class="text-neutral-500 hover:text-neutral-900">サンプルを見る</a>
                            <a href="#" class="text-neutral-500 hover:text-neutral-900">クリエイター一覧</a>
                            <a href="#" class="text-neutral-500 hover:text-neutral-900">料金プラン</a>
                        </div>
                        <div class="flex flex-col gap-3">
                            <span class="font-bold text-neutral-900">サポート</span>
                            <a href="#" class="text-neutral-500 hover:text-neutral-900">利用規約</a>
                            <a href="#" class="text-neutral-500 hover:text-neutral-900">プライバシーポリシー</a>
                            <a href="#" class="text-neutral-500 hover:text-neutral-900">お問い合わせ</a>
                        </div>
                    </div>
                </div>
                <div
                    class="mt-20 flex items-center justify-between border-t border-neutral-50 pt-8"
                >
                    <p class="text-xs text-neutral-400">
                        © 2026 Grid.Link Inc. All rights reserved.
                    </p>
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
