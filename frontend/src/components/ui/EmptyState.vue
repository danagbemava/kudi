<script setup lang="ts">
const ICONS = {
  wallet:
    'M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3',
  merchants:
    'M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64M3.75 21V9.349m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72',
  transactions: 'M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5',
  search: 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z',
} as const

// Heading level is caller-supplied so the empty state slots into the
// surrounding document outline instead of always jumping to h3.
withDefaults(
  defineProps<{
    title: string
    message: string
    level?: 2 | 3 | 4
    /** Matches the icon to what is missing, rather than a wallet everywhere. */
    icon?: keyof typeof ICONS
  }>(),
  { level: 2, icon: 'wallet' },
)
</script>

<template>
  <div class="mx-auto max-w-sm text-center">
    <div
      class="mx-auto flex size-11 items-center justify-center rounded-full bg-canvas text-field"
      aria-hidden="true"
    >
      <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <path stroke-linecap="round" stroke-linejoin="round" :d="ICONS[icon]" />
      </svg>
    </div>
    <component :is="`h${level}`" class="font-display mt-3 text-sm font-semibold text-ink">
      {{ title }}
    </component>
    <p class="mt-1 text-sm text-muted">{{ message }}</p>
    <div v-if="$slots.action" class="mt-4">
      <slot name="action" />
    </div>
  </div>
</template>
