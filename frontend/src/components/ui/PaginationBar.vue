<script setup lang="ts">
defineProps<{
  meta: { current_page: number; last_page: number; total: number }
}>()

const emit = defineEmits<{ page: [page: number] }>()
</script>

<template>
  <nav
    v-if="meta.last_page > 1"
    class="mt-4 flex items-center justify-between"
    aria-label="Pagination"
  >
    <p class="text-sm text-zinc-500">
      Page <span class="font-medium text-ink">{{ meta.current_page }}</span> of
      {{ meta.last_page }} · {{ meta.total }} records
    </p>
    <div class="flex gap-2">
      <button
        type="button"
        class="rounded-lg border border-line bg-white px-3 py-1.5 text-sm font-medium text-ink transition-colors hover:bg-zinc-50 focus-visible:outline-2 focus-visible:outline-forest disabled:cursor-not-allowed disabled:text-zinc-400"
        :disabled="meta.current_page <= 1"
        @click="emit('page', meta.current_page - 1)"
      >
        Previous
      </button>
      <button
        type="button"
        class="rounded-lg border border-line bg-white px-3 py-1.5 text-sm font-medium text-ink transition-colors hover:bg-zinc-50 focus-visible:outline-2 focus-visible:outline-forest disabled:cursor-not-allowed disabled:text-zinc-400"
        :disabled="meta.current_page >= meta.last_page"
        @click="emit('page', meta.current_page + 1)"
      >
        Next
      </button>
    </div>
  </nav>
</template>
