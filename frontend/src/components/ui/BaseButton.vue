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
    'bg-gold text-ink hover:bg-gold-deep focus-visible:outline-forest disabled:bg-gold/40 disabled:text-ink/45',
  secondary:
    'bg-white text-ink border border-line hover:bg-zinc-50 focus-visible:outline-forest disabled:text-zinc-400',
  danger:
    'bg-white text-clay border border-clay/25 hover:bg-clay-wash focus-visible:outline-clay disabled:text-clay/40',
  ghost:
    'text-zinc-600 hover:bg-zinc-100 hover:text-ink focus-visible:outline-forest disabled:text-zinc-300',
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
