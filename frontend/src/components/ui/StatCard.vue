<script setup lang="ts">
withDefaults(
  defineProps<{
    label: string
    value: string
    sub?: string
    loading?: boolean
  }>(),
  { loading: false },
)
</script>

<template>
  <div class="rounded-xl border border-line bg-white p-5">
    <p class="text-xs font-medium tracking-wide text-zinc-500 uppercase">{{ label }}</p>
    <template v-if="loading">
      <div class="mt-2 h-8 w-32 animate-pulse rounded bg-zinc-200" />
      <div class="mt-2 h-4 w-20 animate-pulse rounded bg-zinc-100" />
    </template>
    <template v-else>
      <p class="mt-1.5 font-display text-2xl font-semibold text-ink">{{ value }}</p>
      <p v-if="sub" class="mt-1 text-sm text-zinc-500">{{ sub }}</p>
    </template>
  </div>
</template>
