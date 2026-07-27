<script setup lang="ts">
import type { PageMeta } from '../../api/types'

defineProps<{
  meta: PageMeta
}>()

const emit = defineEmits<{ page: [page: number] }>()

/**
 * Paginating swaps the entire result set, so the viewport has to be put
 * somewhere deliberate. Left alone it stays where it was — mid-table on a full
 * page, but snapped to the top on a short final page, because the shorter
 * document clamps the scroll. Same click, different landing spot.
 *
 * Jumping is instant rather than smooth: the rows underneath are being replaced
 * at the same time, and animating past content that is already gone reads as a
 * glitch.
 */
function go(page: number): void {
  emit('page', page)
  if (window.scrollY > 0) window.scrollTo({ top: 0, behavior: 'auto' })
}
</script>

<template>
  <nav
    v-if="meta.last_page > 1"
    class="mt-4 flex items-center justify-between"
    aria-label="Pagination"
  >
    <p class="text-sm text-muted">
      Page <span class="font-medium text-ink">{{ meta.current_page }}</span> of
      {{ meta.last_page }} · {{ meta.total }} records
    </p>
    <div class="flex gap-2">
      <button
        type="button"
        class="rounded-lg border border-line bg-surface px-3 py-1.5 text-sm font-medium text-ink transition-colors hover:bg-canvas focus-visible:outline-2 focus-visible:outline-accent disabled:cursor-not-allowed disabled:text-muted"
        data-test="previous"
        :disabled="meta.current_page <= 1"
        @click="go(meta.current_page - 1)"
      >
        Previous
      </button>
      <button
        type="button"
        class="rounded-lg border border-line bg-surface px-3 py-1.5 text-sm font-medium text-ink transition-colors hover:bg-canvas focus-visible:outline-2 focus-visible:outline-accent disabled:cursor-not-allowed disabled:text-muted"
        data-test="next"
        :disabled="meta.current_page >= meta.last_page"
        @click="go(meta.current_page + 1)"
      >
        Next
      </button>
    </div>
  </nav>
</template>
