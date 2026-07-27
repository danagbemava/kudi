<script setup lang="ts">
import { ref, useId } from 'vue'
import BaseButton from './BaseButton.vue'

withDefaults(
  defineProps<{
    /** How many filters are currently applied; surfaced on the toggle. */
    activeCount?: number
  }>(),
  { activeCount: 0 },
)

const emit = defineEmits<{ clear: [] }>()

/**
 * Pinned below the header on mobile so search stays reachable down a long
 * ledger, with the secondary filters collapsed behind a toggle — expanded,
 * they took roughly a third of a phone screen. Everything is inline from sm up,
 * where there is room and no toggle is needed.
 */
const expanded = ref(false)
const panelId = useId()
</script>

<template>
  <div
    class="sticky top-16 z-20 -mx-4 mb-4 border-b border-line bg-surface px-4 py-3 sm:static sm:mx-0 sm:border-0 sm:px-0 sm:py-0"
  >
    <div class="flex flex-wrap items-center gap-3">
      <div v-if="$slots.search" class="basis-full sm:max-w-56 sm:flex-1 sm:basis-auto">
        <slot name="search" />
      </div>

      <button
        v-if="$slots.filters"
        type="button"
        class="inline-flex items-center gap-1.5 rounded-lg border border-field bg-surface px-3 py-2 text-sm font-medium text-ink transition-colors hover:bg-canvas focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent sm:hidden"
        :aria-expanded="expanded"
        :aria-controls="panelId"
        @click="expanded = !expanded"
      >
        Filters
        <span
          v-if="activeCount > 0"
          class="rounded-full bg-violet px-1.5 text-xs font-semibold text-white"
        >
          {{ activeCount }}
        </span>
        <svg
          class="size-4 text-muted transition-transform"
          :class="expanded && 'rotate-180'"
          viewBox="0 0 20 20"
          fill="currentColor"
          aria-hidden="true"
        >
          <path
            fill-rule="evenodd"
            d="M5.22 8.22a.75.75 0 011.06 0L10 11.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 9.28a.75.75 0 010-1.06z"
            clip-rule="evenodd"
          />
        </svg>
      </button>

      <!-- Sits beside the toggle on mobile, but trails the filters on desktop:
           the primary action belongs at the end of the row, not mid-bar. -->
      <div v-if="$slots.action" class="ml-auto sm:order-last">
        <slot name="action" />
      </div>

      <div
        v-if="$slots.filters"
        :id="panelId"
        :class="[
          'gap-3',
          expanded ? 'order-last flex basis-full flex-wrap' : 'hidden',
          'sm:order-none sm:flex sm:basis-auto sm:flex-nowrap sm:items-center',
        ]"
      >
        <slot name="filters" />

        <!-- Collapsed filters are invisible, so give applied ones a way out. -->
        <BaseButton v-if="activeCount > 0" variant="ghost" class="sm:hidden" @click="emit('clear')">
          Clear filters
        </BaseButton>
      </div>
    </div>
  </div>
</template>
