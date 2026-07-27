<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import BaseButton from './BaseButton.vue'

/**
 * A column is either a plain heading or a description of one.
 *
 * `align: 'right'` is for money and counts. A column of figures is read by
 * comparing magnitudes, and left-aligned they cannot be: "GHS 74.30" and
 * "GHS 3,015.32" put their decimal points two characters apart, so the eye has
 * to parse each number instead of seeing the shape of the column. A monospaced
 * face does not fix that on its own — the strings are different lengths.
 */
export interface Column {
  label: string
  align?: 'left' | 'right'
  /** Sort key the API understands. Omit for columns that cannot be sorted. */
  sort?: string
}

const props = withDefaults(
  defineProps<{
    /** Column headers; also drives skeleton column count. */
    columns: (string | Column)[]
    /** Names the table for assistive tech, e.g. "Merchants". */
    label: string
    loading?: boolean
    error?: string | null
    empty?: boolean
    /** Row count, announced when a load finishes. */
    count?: number
    /** Which column is currently ordering the data, and which way. */
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }>(),
  { loading: false, error: null, empty: false, sortKey: undefined, sortDirection: 'desc' },
)

const emit = defineEmits<{ retry: []; sort: [key: string] }>()

const resolved = computed<Column[]>(() =>
  props.columns.map((column) => (typeof column === 'string' ? { label: column } : column)),
)

/**
 * `aria-sort` belongs on exactly one header at a time; on every sortable one it
 * would claim the table is ordered by all of them at once.
 */
function ariaSort(column: Column): 'ascending' | 'descending' | 'none' | undefined {
  if (!column.sort) return undefined
  if (column.sort !== props.sortKey) return 'none'

  return props.sortDirection === 'asc' ? 'ascending' : 'descending'
}

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
  <div class="panel">
    <p class="sr-only" role="status" aria-live="polite">{{ status }}</p>

    <!--
      Below md the same records render as stacked cards. A seven-column ledger
      squeezed into a phone pushes the amount and status off-screen behind a
      horizontal scroll most people never find.
    -->
    <div class="hidden overflow-x-auto md:block">
      <table class="w-full text-left text-sm whitespace-nowrap" :aria-busy="loading">
        <caption class="sr-only">
          {{
            label
          }}
        </caption>
        <thead>
          <tr class="border-b border-line bg-canvas/80">
            <th
              v-for="(column, index) in resolved"
              :key="index"
              scope="col"
              :aria-sort="ariaSort(column)"
              class="px-4 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
              :class="column.align === 'right' ? 'text-right' : 'text-left'"
            >
              <!-- Lets a page put a control in a header cell (e.g. select-all)
                   while every other column keeps its plain label. -->
              <slot :name="`header-${index}`">
                <button
                  v-if="column.sort"
                  type="button"
                  class="group inline-flex items-center gap-1 tracking-wide uppercase hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                  :class="[
                    column.align === 'right' ? 'flex-row-reverse' : '',
                    column.sort === sortKey ? 'text-ink' : '',
                  ]"
                  @click="emit('sort', column.sort)"
                >
                  {{ column.label }}
                  <!-- The arrow is decorative: aria-sort on the cell already
                       states the order, and reading "up arrow" adds nothing. -->
                  <svg
                    class="size-3 shrink-0 transition-opacity"
                    :class="
                      column.sort === sortKey ? 'opacity-100' : 'opacity-0 group-hover:opacity-40'
                    "
                    viewBox="0 0 12 12"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.6"
                    aria-hidden="true"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      :d="
                        column.sort === sortKey && sortDirection === 'asc'
                          ? 'M6 9.5V2.5M3 5.5L6 2.5l3 3'
                          : 'M6 2.5v7M3 6.5l3 3 3-3'
                      "
                    />
                  </svg>
                </button>
                <template v-else>{{ column.label }}</template>
              </slot>
            </th>
          </tr>
        </thead>

        <!-- Placeholder rows carry no information; keep them out of the
             accessibility tree rather than exposing six empty rows. -->
        <tbody v-if="loading" aria-hidden="true">
          <tr v-for="row in 6" :key="row" class="border-b border-line/60 last:border-0">
            <td v-for="(_, index) in columns" :key="index" class="px-4 py-3.5">
              <div
                class="h-4 animate-pulse rounded bg-line"
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

    <div class="md:hidden" :aria-busy="loading">
      <ul v-if="loading" class="divide-y divide-line/60" aria-hidden="true">
        <li v-for="row in 5" :key="row" class="space-y-2 px-4 py-3.5">
          <div class="h-4 w-2/5 animate-pulse rounded bg-line" />
          <div class="h-3 w-3/5 animate-pulse rounded bg-canvas" />
        </li>
      </ul>

      <ul v-else-if="!error && !empty" class="divide-y divide-line/60">
        <slot name="cards" />
      </ul>
    </div>

    <div v-if="!loading && error" class="px-4 py-12 text-center">
      <p class="text-sm text-ink-soft" role="alert">{{ error }}</p>
      <BaseButton variant="secondary" class="mt-4" @click="emit('retry')">Retry</BaseButton>
    </div>

    <div v-else-if="!loading && empty" class="px-4 py-12">
      <slot name="empty" />
    </div>
  </div>
</template>
