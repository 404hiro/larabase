<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Toast from './Toast.vue'

interface ToastItem {
    id: number
    variant: 'default' | 'success' | 'error' | 'warning'
    title?: string
    description?: string
}

const toasts = ref<ToastItem[]>([])
let toastId = 0

const addToast = (toast: Omit<ToastItem, 'id'>) => {
    const id = toastId++
    toasts.value.push({ ...toast, id })
    
    // 5秒後に自動削除
    setTimeout(() => {
        removeToast(id)
    }, 5000)
}

const removeToast = (id: number) => {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index > -1) {
        toasts.value.splice(index, 1)
    }
}

// Inertiaのフラッシュメッセージを監視
const page = usePage()
const lastFlash = ref<string>('')

const checkFlashMessages = (flash: any) => {
    // フラッシュメッセージのハッシュを作成して重複チェック
    const flashHash = JSON.stringify(flash)
    
    // 同じメッセージは表示しない
    if (flashHash === lastFlash.value || !flash) {
        return
    }
    
    lastFlash.value = flashHash
    
    // 成功メッセージ
    if (flash.success) {
        addToast({
            variant: 'success',
            title: '成功',
            description: flash.success,
        })
    }
    
    // エラーメッセージ
    if (flash.error) {
        addToast({
            variant: 'error',
            title: 'エラー',
            description: flash.error,
        })
    }
    
    // 警告メッセージ
    if (flash.warning) {
        addToast({
            variant: 'warning',
            title: '警告',
            description: flash.warning,
        })
    }
    
    // 情報メッセージ
    if (flash.info) {
        addToast({
            variant: 'default',
            title: '情報',
            description: flash.info,
        })
    }
}

// ページ遷移時にチェック（immediate: trueで初回も実行）
watch(() => page.props.flash, (newFlash) => {
    checkFlashMessages(newFlash)
}, { deep: true, immediate: true })
</script>

<template>
    <div class="pointer-events-none fixed bottom-0 right-0 z-[100] flex max-h-screen w-full flex-col-reverse p-4 sm:flex-col md:max-w-[420px]">
        <TransitionGroup
            name="toast"
            tag="div"
            class="flex flex-col gap-2"
        >
            <Toast
                v-for="toast in toasts"
                :key="toast.id"
                :variant="toast.variant"
                :title="toast.title"
                :description="toast.description"
                :on-close="() => removeToast(toast.id)"
            />
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}

.toast-enter-from {
    opacity: 0;
    transform: translateX(100%);
}

.toast-leave-to {
    opacity: 0;
    transform: translateX(100%);
}
</style>