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

const styles: Record<string, string> = {
  primary:
    'bg-primary-600 text-white hover:bg-primary-700 focus-visible:outline-primary-600 disabled:bg-primary-600/50',
  secondary:
    'bg-white text-ink border border-line hover:bg-slate-50 focus-visible:outline-primary-600 disabled:text-slate-400',
  danger:
    'bg-white text-red-600 border border-red-200 hover:bg-red-50 focus-visible:outline-red-600 disabled:text-red-300',
  ghost:
    'text-slate-600 hover:bg-slate-100 hover:text-ink focus-visible:outline-primary-600 disabled:text-slate-300',
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
