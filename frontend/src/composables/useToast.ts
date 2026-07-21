import { readonly, ref } from 'vue'

export interface Toast {
  id: number
  tone: 'success' | 'error' | 'info'
  message: string
}

const toasts = ref<Toast[]>([])
const timers = new Map<number, ReturnType<typeof setTimeout>>()
let nextId = 1

const DISMISS_AFTER = 6000

function push(tone: Toast['tone'], message: string): void {
  const id = nextId++
  toasts.value.push({ id, tone, message })

  // Errors stay until dismissed: they report a failed action the user needs to
  // read and act on, and WCAG 2.2.1 discourages time limits on such content.
  if (tone !== 'error') startTimer(id)
}

function startTimer(id: number): void {
  clearTimer(id)
  timers.set(
    id,
    setTimeout(() => dismiss(id), DISMISS_AFTER),
  )
}

function clearTimer(id: number): void {
  const timer = timers.get(id)
  if (timer) {
    clearTimeout(timer)
    timers.delete(id)
  }
}

function dismiss(id: number): void {
  clearTimer(id)
  toasts.value = toasts.value.filter((t) => t.id !== id)
}

export function useToast() {
  return {
    toasts: readonly(toasts),
    success: (message: string) => push('success', message),
    error: (message: string) => push('error', message),
    info: (message: string) => push('info', message),
    dismiss,
    /** Pause auto-dismiss while the user is reading or focused inside a toast. */
    hold: (id: number) => clearTimer(id),
    resume: (id: number, tone: Toast['tone']) => {
      if (tone !== 'error') startTimer(id)
    },
  }
}
