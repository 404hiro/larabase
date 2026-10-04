<script setup lang="ts">
import { apiBaseUrl } from '@/lib/api';
</script>

<template>
  <main class="grid min-h-screen place-items-center bg-neutral-50 px-6 py-12 text-neutral-950">
    <section class="w-full max-w-md rounded-2xl border border-neutral-200 bg-white p-8 shadow-sm">
      <div class="text-center">
        <div class="mx-auto mb-5 flex size-12 items-center justify-center rounded-2xl bg-neutral-950 text-white">
          <span class="text-xl font-black">G</span>
        </div>
        <h1 class="text-2xl font-black tracking-tight">アカウントにログイン</h1>
        <p class="mt-2 text-sm font-medium text-neutral-500">
          Googleアカウントを使用してログインしてください
        </p>
      </div>

      <a
        :href="`${apiBaseUrl}/auth/google`"
        class="mt-8 inline-flex h-11 w-full items-center justify-center gap-2 rounded-md border border-neutral-300 bg-white px-4 text-sm font-bold text-neutral-900 shadow-sm transition-colors hover:bg-neutral-50 active:scale-[0.99]"
      >
        <svg class="size-4" aria-hidden="true" focusable="false" viewBox="0 0 488 512">
          <path
            fill="currentColor"
            d="M488 261.8C488 403.3 391.1 504 248 504 110.8 504 0 393.2 0 256S110.8 8 248 8c66.8 0 123 24.5 166.3 64.9l-67.5 64.9C258.5 52.6 94.3 116.6 94.3 256c0 86.5 69.1 156.6 153.7 156.6 98.2 0 135-70.4 140.8-106.9H248v-85.3h236.1c2.3 12.7 3.9 24.9 3.9 41.4z"
          />
        </svg>
        Googleでログイン
      </a>
    </section>
  </main>
</template>
