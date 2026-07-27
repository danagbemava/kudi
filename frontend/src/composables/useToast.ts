import { readonly, ref } from 'vue'
import { playToastSound } from '../utils/sound'

export interface Toast {
  id: number
  tone: 'success' | 'error' | 'info'
  message: string
}

const toasts = ref<Toast[]>([])
const timers = new Map<number, ReturnType<typeof setTimeout>>()
let nextId = 1

/**
 * Everything clears itself, errors just get longer. An error carries more to
 * read and usually a recovery step, so it holds for long enough to act on
 * without becoming a panel the user has to tidy up after.
 *
 * Hovering or focusing a toast still pauses its timer, so a message can never
 * expire while it is being read.
 */
const DISMISS_AFTER: Record<Toast['tone'], number> = {
  success: 5000,
  info: 5000,
  error: 10000,
}

function push(tone: Toast['tone'], message: string): void {
  const id = nextId++
  toasts.value.push({ id, tone, message })
  startTimer(id, tone)
  playToastSound(tone)
}

function startTimer(id: number, tone: Toast['tone']): void {
  clearTimer(id)
  timers.set(
    id,
    setTimeout(() => dismiss(id), DISMISS_AFTER[tone]),
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
    resume: (id: number, tone: Toast['tone']) => startTimer(id, tone),
  }
}
