<script setup lang="ts">
import { useToast } from '../../composables/useToast'

const { toasts, dismiss, hold, resume } = useToast()

const tones = {
  success: {
    panel: 'border-good/25 bg-good-wash text-good',
    close: 'text-good hover:bg-good-wash',
  },
  error: {
    panel: 'border-bad/25 bg-bad-wash text-bad',
    close: 'text-bad hover:bg-bad-wash',
  },
  info: {
    panel: 'border-line bg-surface text-ink',
    close: 'text-ink-soft hover:bg-canvas',
  },
}
</script>

<template>
  <!-- One live region on the container only. Putting role="status" on each toast
       as well would nest live regions and pull the dismiss button's name into
       the announcement. -->
  <!-- Top right, clear of the 4rem header. Every action that raises a toast —
       run settlement, simulate payment, register merchant, sign out — is itself
       in the top right, so the confirmation appears where the click happened
       rather than diagonally across the screen. -->
  <div
    class="pointer-events-none fixed inset-x-4 top-20 z-[60] flex flex-col items-center gap-2 sm:inset-x-auto sm:right-6 sm:items-end"
    role="status"
    aria-live="polite"
  >
    <TransitionGroup
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="-translate-y-2 opacity-0"
      leave-active-class="transition duration-150 ease-in"
      leave-to-class="opacity-0"
    >
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :class="[
          'pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-lg',
          tones[toast.tone].panel,
        ]"
        @mouseenter="hold(toast.id)"
        @mouseleave="resume(toast.id, toast.tone)"
        @focusin="hold(toast.id)"
        @focusout="resume(toast.id, toast.tone)"
      >
        <p class="flex-1">{{ toast.message }}</p>
        <button
          type="button"
          :class="[
            'shrink-0 rounded-md p-0.5 transition-colors focus-visible:outline-2 focus-visible:outline-current',
            tones[toast.tone].close,
          ]"
          aria-label="Dismiss notification"
          @click="dismiss(toast.id)"
        >
          <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path
              d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"
            />
          </svg>
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>
