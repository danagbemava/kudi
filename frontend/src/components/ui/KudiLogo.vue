<script setup lang="ts">
// `muted` renders the wordmark in ink instead of violet, for surfaces where the
// violet would be competing with a nearby primary action.
withDefaults(defineProps<{ muted?: boolean }>(), { muted: false })
</script>

<template>
  <span
    class="inline-flex items-baseline text-lg font-bold tracking-tight select-none"
    :class="muted ? 'text-ink' : 'text-violet'"
  >
    <!-- The tittle is a square, not a dot. It is the one distinguishing detail
         of the Kudi mark, so the dotless i carries it as a block. -->
    <span aria-hidden="true"
      >Kud<span class="relative"
        >&#305;<span class="absolute -top-px left-1/2 size-1.5 -translate-x-1/2 bg-red" /></span
    ></span>
    <span class="sr-only">Kudi</span>
    <span class="ml-1.5 text-[10px] font-medium tracking-[0.18em] text-muted uppercase"
      >Systems</span
    >
  </span>
</template>
