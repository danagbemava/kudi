<script setup lang="ts">
defineProps<{
  title: string
  message: string
}>()
</script>

<template>
  <div class="mx-auto max-w-sm text-center">
    <div
      class="mx-auto flex size-11 items-center justify-center rounded-full bg-primary-50 text-primary-600"
      aria-hidden="true"
    >
      <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3"
        />
      </svg>
    </div>
    <h3 class="font-display mt-3 text-sm font-semibold text-ink">{{ title }}</h3>
    <p class="mt-1 text-sm text-slate-500">{{ message }}</p>
    <div v-if="$slots.action" class="mt-4">
      <slot name="action" />
    </div>
  </div>
</template>
