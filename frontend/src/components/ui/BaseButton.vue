<script setup lang="ts">
withDefaults(
  defineProps<{
    variant?: 'primary' | 'secondary' | 'danger' | 'ghost'
    type?: 'button' | 'submit'
    loading?: boolean
    disabled?: boolean
  }>(),
  { variant: 'primary', type: 'button', loading: false, disabled: false },
)

// Disabled states go grey rather than fading the live colour. A tinted-down
// primary landed at 1.87:1 — WCAG exempts inactive controls, but "Run
// settlement" is disabled precisely when the operator needs to read why, so it
// stays legible. Grey fill plus not-allowed carries the state instead.
const styles: Record<string, string> = {
  // Violet is near-black, so the primary button carries white text and lightens
  // on hover rather than darkening.
  primary:
    'bg-violet text-white hover:bg-violet/85 focus-visible:outline-accent disabled:bg-line disabled:text-ink-soft',
  secondary:
    'bg-surface text-ink border border-line hover:bg-canvas focus-visible:outline-accent disabled:text-muted',
  danger:
    'bg-surface text-bad border border-bad/25 hover:bg-bad-wash focus-visible:outline-bad disabled:border-line disabled:text-muted',
  ghost:
    'text-ink-soft hover:bg-canvas hover:text-ink focus-visible:outline-accent disabled:text-muted',
}
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="[
      'inline-flex items-center justify-center gap-2 rounded-lg px-3.5 py-2 text-sm font-medium transition-colors',
      'focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed',
      styles[variant],
    ]"
  >
    <svg
      v-if="loading"
      class="size-4 animate-spin"
      viewBox="0 0 24 24"
      fill="none"
      aria-hidden="true"
    >
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
    </svg>
    <slot />
  </button>
</template>
