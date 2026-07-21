<script setup lang="ts">
import { ref, watch } from 'vue'
import BaseButton from './BaseButton.vue'

const props = withDefaults(
  defineProps<{
    /** Column headers; also drives skeleton column count. */
    columns: string[]
    /** Names the table for assistive tech, e.g. "Merchants". */
    label: string
    loading?: boolean
    error?: string | null
    empty?: boolean
    /** Row count, announced when a load finishes. */
    count?: number
  }>(),
  { loading: false, error: null, empty: false },
)

const emit = defineEmits<{ retry: [] }>()

// Filtering and searching swap the table body with no visual "loading" moment
// long enough to notice, and nothing tells a screen reader user what changed.
const status = ref('')

watch(
  () => props.loading,
  (loading, wasLoading) => {
    if (loading || !wasLoading) return
    if (props.error) return // the error region announces itself
    status.value = props.count === 1 ? '1 result' : `${props.count ?? 0} results`
  },
)
</script>

<template>
  <div class="overflow-hidden rounded-xl border border-line bg-white">
    <p class="sr-only" role="status" aria-live="polite">{{ status }}</p>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-sm whitespace-nowrap" :aria-busy="loading">
        <caption class="sr-only">
          {{ label }}
        </caption>
        <thead>
          <tr class="border-b border-line bg-slate-50/80">
            <th
              v-for="column in columns"
              :key="column"
              scope="col"
              class="px-4 py-3 text-xs font-semibold tracking-wide text-slate-500 uppercase"
            >
              {{ column }}
            </th>
          </tr>
        </thead>

        <!-- Placeholder rows carry no information; keep them out of the
             accessibility tree rather than exposing six empty rows. -->
        <tbody v-if="loading" aria-hidden="true">
          <tr v-for="row in 6" :key="row" class="border-b border-line/60 last:border-0">
            <td v-for="(_, index) in columns" :key="index" class="px-4 py-3.5">
              <div
                class="h-4 animate-pulse rounded bg-slate-200"
                :style="{ width: `${55 + ((row * 7 + index * 13) % 40)}%` }"
              />
            </td>
          </tr>
        </tbody>

        <tbody v-else-if="!error && !empty" class="divide-y divide-line/60">
          <slot />
        </tbody>
      </table>
    </div>

    <div v-if="!loading && error" class="px-4 py-12 text-center">
      <p class="text-sm text-slate-600" role="alert">{{ error }}</p>
      <BaseButton variant="secondary" class="mt-4" @click="emit('retry')">Retry</BaseButton>
    </div>

    <div v-else-if="!loading && empty" class="px-4 py-12">
      <slot name="empty" />
    </div>
  </div>
</template>
