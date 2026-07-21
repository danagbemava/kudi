import { readonly, ref } from 'vue'

export interface Toast {
  id: number
  tone: 'success' | 'error' | 'info'
  message: string
}

const toasts = ref<Toast[]>([])
let nextId = 1

function push(tone: Toast['tone'], message: string): void {
  const id = nextId++
  toasts.value.push({ id, tone, message })
  setTimeout(() => dismiss(id), 5000)
}

function dismiss(id: number): void {
  toasts.value = toasts.value.filter((t) => t.id !== id)
}

export function useToast() {
  return {
    toasts: readonly(toasts),
    success: (message: string) => push('success', message),
    error: (message: string) => push('error', message),
    info: (message: string) => push('info', message),
    dismiss,
  }
}
