<script setup lang="ts">
import { apiGet, type CurrentUser } from '@/lib/api';
import {
  ArrowRight,
  CheckCircle2,
  Gift,
  Image as ImageIcon,
  LayoutGrid,
  Link as LinkIcon,
  Lock,
  MessageCircle,
  MousePointer2,
  Plus,
  Share2,
  ShieldCheck,
  Smartphone,
  Sparkles,
  Zap,
} from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

const user = ref<CurrentUser | null>(null);

onMounted(async () => {
  try {
    const data = await apiGet<{ user: CurrentUser | null }>('/api/me');
    user.value = data.user;
  } catch {
    user.value = null;
  }
});
</script>

<template>
  <div class="bg-dot-pattern min-h-screen overflow-x-hidden bg-[#f9fafb] text-neutral-900 selection:bg-neutral-900 selection:text-white">
    <nav class="sticky top-0 z-50 border-b border-neutral-200/50 bg-white/70 backdrop-blur-xl">
      <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 p-4 md:px-6">
        <RouterLink to="/" class="flex min-w-0 items-center gap-2.5">
          <div class="flex size-9 items-center justify-center rounded-xl bg-neutral-900 text-white shadow-lg shadow-neutral-200">
            <LayoutGrid class="size-5" />
          </div>
          <span class="truncate text-xl font-black tracking-tight">Grid.Link</span>
        </RouterLink>
        <div class="flex shrink-0 items-center gap-3 text-sm font-bold sm:gap-8">
          <RouterLink
            v-if="user"
            to="/login"
            class="rounded-full bg-neutral-900 px-4 py-2.5 text-white transition-all hover:bg-neutral-800 active:scale-95 sm:px-6"
          >
            マイページ
          </RouterLink>
          <template v-else>
            <RouterLink to="/login" class="hidden transition-colors hover:text-neutral-500 sm:block">
              ログイン
            </RouterLink>
            <RouterLink
              to="/login"
              class="rounded-full bg-neutral-900 px-4 py-2.5 text-white transition-all hover:bg-neutral-800 hover:shadow-lg active:scale-95 sm:px-6"
            >
              はじめる
            </RouterLink>
          </template>
        </div>
      </div>
    </nav>

    <main>
      <section class="relative mx-auto max-w-7xl px-5 pt-20 pb-20 text-center sm:px-6 md:pt-40">
        <div class="inline-flex max-w-full items-center gap-2 rounded-full border border-neutral-200 bg-white px-4 py-1.5 text-xs font-bold text-neutral-600 shadow-sm">
          <Sparkles class="size-3.5 text-yellow-500" />
          <span class="truncate">クリエイターのための、新しいプロフィール。</span>
        </div>
        <h1 class="mx-auto mt-10 max-w-4xl text-4xl leading-[1.08] font-black tracking-tight sm:text-5xl md:text-8xl">
          あなたの世界を、<br />1枚のグリッドに。
        </h1>
        <p class="mx-auto mt-8 max-w-2xl text-base leading-8 font-medium text-neutral-500 sm:text-lg md:mt-10 md:text-2xl md:leading-relaxed">
          Grid.Link（グリッドリンク）は、リンクや画像、テキストを自由に並べて、あなただけの美しいプロフィールページを作れるサービスです。
        </p>
        <div class="mt-12 flex flex-col items-center justify-center gap-4 sm:flex-row md:mt-14">
          <RouterLink
            to="/login"
            class="group relative flex w-full max-w-[280px] items-center justify-center gap-2 rounded-full bg-neutral-900 px-7 py-4 text-base font-black text-white transition-all hover:bg-neutral-800 hover:shadow-2xl hover:shadow-neutral-300 active:scale-95 sm:w-auto sm:max-w-none sm:px-10 sm:py-5 sm:text-xl"
          >
            無料で作成を開始
            <ArrowRight class="size-5 transition-transform group-hover:translate-x-1 sm:size-6" />
          </RouterLink>
        </div>

        <div class="mt-24 grid grid-cols-2 gap-4 md:mt-32 md:grid-cols-4 lg:gap-8">
          <div class="aspect-square rounded-[2rem] bg-white p-6 shadow-xl ring-1 shadow-neutral-200/50 ring-neutral-200/50">
            <div class="flex size-full items-center justify-center rounded-2xl bg-neutral-50">
              <ImageIcon class="size-12 text-neutral-200" />
            </div>
          </div>
          <div class="aspect-square translate-y-8 rounded-[2rem] bg-white p-6 shadow-xl ring-1 shadow-neutral-200/50 ring-neutral-200/50">
            <div class="flex size-full items-center justify-center rounded-2xl bg-neutral-50">
              <LinkIcon class="size-12 text-neutral-200" />
            </div>
          </div>
          <div class="aspect-square rounded-[2rem] bg-white p-6 shadow-xl ring-1 shadow-neutral-200/50 ring-neutral-200/50">
            <div class="flex size-full items-center justify-center rounded-2xl bg-neutral-50">
              <MessageCircle class="size-12 text-neutral-200" />
            </div>
          </div>
          <div class="aspect-square translate-y-8 rounded-[2rem] bg-white p-6 shadow-xl ring-1 shadow-neutral-200/50 ring-neutral-200/50">
            <div class="flex size-full items-center justify-center rounded-2xl bg-neutral-50">
              <MousePointer2 class="size-12 text-neutral-200" />
            </div>
          </div>
        </div>
      </section>

      <section class="mx-auto max-w-7xl px-6 py-32">
        <div class="relative overflow-hidden rounded-[3.5rem] bg-neutral-900 p-12 text-white shadow-2xl md:p-24">
          <div class="relative z-10 grid grid-cols-1 gap-16 lg:grid-cols-2 lg:items-center">
            <div>
              <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-sm font-bold text-white">
                <Plus class="size-4" />
                <span>Bento Grid Layout</span>
              </div>
              <h2 class="mt-8 text-4xl leading-tight font-black md:text-6xl">
                カードを並べて、<br />個性を表現する。
              </h2>
              <p class="mt-8 text-xl leading-relaxed text-neutral-400">
                決められたテンプレートはありません。グリッド上にウィジェットを自由に配置して、あなたらしいページを構築しましょう。
              </p>
              <div class="mt-12 grid gap-6">
                <div class="flex items-start gap-4">
                  <span class="mt-1 flex size-6 shrink-0 items-center justify-center rounded-full bg-white text-neutral-900">
                    <CheckCircle2 class="size-4" />
                  </span>
                  <div>
                    <h3 class="text-lg font-bold">レスポンシブ対応</h3>
                    <p class="mt-1 text-neutral-500">スマホでもPCでも、最適なレイアウトで表示されます。</p>
                  </div>
                </div>
                <div class="flex items-start gap-4">
                  <span class="mt-1 flex size-6 shrink-0 items-center justify-center rounded-full bg-white text-neutral-900">
                    <CheckCircle2 class="size-4" />
                  </span>
                  <div>
                    <h3 class="text-lg font-bold">直感的な編集</h3>
                    <p class="mt-1 text-neutral-500">ドラッグ＆ドロップでパズルのように組み立てられます。</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="relative">
              <div class="grid rotate-3 grid-cols-2 gap-4">
                <div class="h-40 rounded-3xl border border-white/10 bg-white/5 backdrop-blur-sm"></div>
                <div class="h-40 rounded-3xl border border-white/20 bg-white/10 backdrop-blur-sm"></div>
                <div class="col-span-2 h-64 rounded-3xl border border-white/30 bg-gradient-to-br from-white/20 to-transparent backdrop-blur-md"></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="mx-auto max-w-7xl px-6 py-32">
        <div class="mb-20 text-center">
          <h2 class="text-4xl font-black md:text-6xl">プロフィールを超えた機能。</h2>
          <p class="mt-6 text-xl font-medium text-neutral-500">
            ただのリンク集ではない、クリエイターのためのツールボックス。
          </p>
        </div>
        <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
          <article
            v-for="feature in [
              { icon: MessageCircle, title: 'メッセージ', text: 'プロフィールから直接メッセージを受け取れます。' },
              { icon: Share2, title: '共有', text: 'あなたの世界をワンタップでシェア。' },
              { icon: Smartphone, title: 'スマホ最適化', text: 'スマホ閲覧でも美しいカード配置を保ちます。' },
              { icon: ShieldCheck, title: '安全性', text: '外部リンクの確認表示にも対応。' },
              { icon: Lock, title: 'Googleログイン', text: 'Googleアカウントだけで素早く開始。' },
              { icon: Gift, title: '自由な表現', text: '画像、リンク、テキスト、地図を組み合わせられます。' },
            ]"
            :key="feature.title"
            class="group rounded-[2.5rem] bg-white p-10 shadow-xl ring-1 shadow-neutral-200/50 ring-neutral-200/50 transition-all hover:-translate-y-2"
          >
            <div class="mb-8 flex size-14 items-center justify-center rounded-2xl bg-neutral-50 text-neutral-900 transition-colors group-hover:bg-neutral-900 group-hover:text-white">
              <component :is="feature.icon" class="size-7" />
            </div>
            <h3 class="text-2xl font-black">{{ feature.title }}</h3>
            <p class="mt-4 leading-7 font-medium text-neutral-500">{{ feature.text }}</p>
          </article>
        </div>
      </section>

      <section class="px-6 py-32 text-center">
        <Zap class="mx-auto mb-6 size-10 text-neutral-900" />
        <h2 class="text-4xl font-black md:text-6xl">今すぐ、1枚目のグリッドを。</h2>
        <RouterLink
          to="/login"
          class="mt-10 inline-flex items-center justify-center gap-2 rounded-full bg-neutral-900 px-10 py-5 text-xl font-black text-white transition-all hover:bg-neutral-800 active:scale-95"
        >
          はじめる
          <ArrowRight class="size-6" />
        </RouterLink>
      </section>
    </main>
  </div>
</template>
