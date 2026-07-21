<script setup lang="ts">
withDefaults(defineProps<{ light?: boolean }>(), { light: false })
</script>

<template>
  <span
    class="inline-flex items-baseline font-display text-lg font-bold tracking-tight select-none"
    :class="light ? 'text-white' : 'text-ink'"
  >
    <span aria-hidden="true"
      >KUD<span class="relative"
        >&#305;<span
          class="absolute -top-0.5 left-1/2 size-1.5 -translate-x-1/2 rounded-full bg-gold" /></span
    ></span>
    <span class="sr-only">Kudi</span>
    <span class="ml-1.5 text-[10px] font-semibold tracking-[0.2em] uppercase opacity-60"
      >Systems</span
    >
  </span>
</template>
