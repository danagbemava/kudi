<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useId } from 'vue'
import type { MerchantSort, SortDirection } from '../api'
import type { Merchant, MerchantStatus } from '../api/types'
import BulkStatusBar from '../components/merchants/BulkStatusBar.vue'
import MerchantFormModal from '../components/merchants/MerchantFormModal.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import DataTable, { type Column } from '../components/ui/DataTable.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import FilterBar from '../components/ui/FilterBar.vue'
import PaginationBar from '../components/ui/PaginationBar.vue'
import StatusBadge from '../components/ui/StatusBadge.vue'
import { useMerchantsStore } from '../stores/merchants'
import { formatMoney } from '../utils/money'

const merchants = useMerchantsStore()

const search = ref('')
const status = ref<MerchantStatus | ''>('')
const page = ref(1)
const formOpen = ref(false)
const sort = ref<MerchantSort>('registered')
const direction = ref<SortDirection>('desc')

let searchTimer: ReturnType<typeof setTimeout> | undefined

function load(): void {
  merchants.fetch({
    search: search.value,
    status: status.value,
    page: page.value,
    sort: sort.value,
    direction: direction.value,
  })
}

/** Clicking the active column flips it; a new column starts descending. */
function sortBy(key: string): void {
  if (sort.value === key) {
    direction.value = direction.value === 'asc' ? 'desc' : 'asc'
  } else {
    sort.value = key as MerchantSort
    direction.value = key === 'business' || key === 'contact' ? 'asc' : 'desc'
  }

  page.value = 1
  load()
}

onMounted(load)

watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    page.value = 1
    load()
  }, 300)
})

watch(status, () => {
  page.value = 1
  load()
})

watch(page, load)

function onCreated(merchant: Merchant): void {
  void merchant
  page.value = 1
  load()
}

const selected = ref<number[]>([])
const selectAllId = useId()

// Selection is dropped whenever the visible set changes: keeping ids the user
// can no longer see selected would make the bulk count a lie.
watch(
  () => merchants.items,
  () => (selected.value = []),
)

const allVisibleSelected = computed(
  () => merchants.items.length > 0 && selected.value.length === merchants.items.length,
)

function toggleAll(checked: boolean): void {
  selected.value = checked ? merchants.items.map((m) => m.id) : []
}

function toggle(id: number, checked: boolean): void {
  selected.value = checked ? [...selected.value, id] : selected.value.filter((s) => s !== id)
}

function afterBulk(): void {
  selected.value = []
  load()
}

const columns: Column[] = [
  { label: '' },
  { label: 'Business', sort: 'business' },
  { label: 'Contact', sort: 'contact' },
  { label: 'Institution' },
  { label: 'Wallet balance', align: 'right', sort: 'balance' },
  { label: 'Status', sort: 'status' },
]

// Search sits outside the collapsible panel, so it is not counted here.
const activeFilterCount = computed(() => (status.value ? 1 : 0))

function clearFilters(): void {
  status.value = ''
}
</script>

<template>
  <div>
    <FilterBar :active-count="activeFilterCount" @clear="clearFilters">
      <template #search>
        <div class="relative basis-full sm:max-w-xs sm:flex-1 sm:basis-auto">
          <svg
            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            aria-hidden="true"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"
            />
          </svg>
          <input
            v-model="search"
            type="search"
            placeholder="Search merchants…"
            aria-label="Search merchants"
            class="w-full rounded-lg border border-field bg-surface py-2 pr-3 pl-9 text-sm placeholder:text-muted hover:border-line focus:outline-2 focus:outline-offset-1 focus:outline-accent"
          />
        </div>
      </template>

      <template #filters>
        <select
          v-model="status"
          aria-label="Filter by status"
          class="select-field rounded-lg border border-field bg-surface py-2 pl-3 text-sm hover:border-line focus:outline-2 focus:outline-offset-1 focus:outline-accent"
        >
          <option value="">All statuses</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </template>

      <template #action>
        <BaseButton @click="formOpen = true">Register merchant</BaseButton>
      </template>
    </FilterBar>

    <BulkStatusBar :selected="selected" @done="afterBulk" @clear="selected = []" />

    <DataTable
      :columns="columns"
      label="Merchants"
      :count="merchants.items.length"
      :loading="merchants.loading"
      :error="merchants.error"
      :empty="merchants.items.length === 0"
      :sort-key="sort"
      :sort-direction="direction"
      @retry="load"
      @sort="sortBy"
    >
      <template #header-0>
        <input
          :id="selectAllId"
          type="checkbox"
          class="size-4 rounded border-field text-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
          :checked="allVisibleSelected"
          aria-label="Select all merchants on this page"
          @change="toggleAll(($event.target as HTMLInputElement).checked)"
        />
      </template>

      <tr
        v-for="merchant in merchants.items"
        :key="merchant.id"
        class="transition-colors hover:bg-canvas/70"
        :class="selected.includes(merchant.id) && 'bg-good-wash/60'"
      >
        <td class="py-3 pl-4">
          <input
            type="checkbox"
            class="size-4 rounded border-field text-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
            :checked="selected.includes(merchant.id)"
            :aria-label="`Select ${merchant.business_name}`"
            @change="toggle(merchant.id, ($event.target as HTMLInputElement).checked)"
          />
        </td>
        <td class="px-4 py-3">
          <RouterLink
            :to="`/merchants/${merchant.id}`"
            class="font-medium text-ink hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"
          >
            {{ merchant.business_name }}
          </RouterLink>
        </td>
        <td class="px-4 py-3">
          <p class="text-ink">{{ merchant.name }}</p>
          <p class="text-xs text-muted">{{ merchant.email }}</p>
        </td>
        <td class="px-4 py-3 text-ink-soft">{{ merchant.institution?.name }}</td>
        <!-- Right-aligned so decimal points line up down the column. -->
        <td class="font-display px-4 py-3 text-right font-semibold text-ink tabular-nums">
          {{ formatMoney(merchant.wallet_balance ?? 0) }}
        </td>
        <td class="px-4 py-3"><StatusBadge :status="merchant.status" /></td>
      </tr>

      <template #cards>
        <li
          v-for="merchant in merchants.items"
          :key="merchant.id"
          class="relative px-4 py-3 transition-colors focus-within:bg-canvas hover:bg-canvas"
        >
          <div class="flex items-start justify-between gap-3">
            <RouterLink
              :to="`/merchants/${merchant.id}`"
              class="min-w-0 flex-1 text-sm font-medium text-ink after:absolute after:inset-0 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"
            >
              {{ merchant.business_name }}
            </RouterLink>
            <StatusBadge :status="merchant.status" />
          </div>

          <p class="mt-0.5 truncate text-xs text-muted">
            {{ merchant.name }} · {{ merchant.email }}
          </p>

          <div class="mt-2 flex items-baseline justify-between gap-3">
            <p class="font-display text-base font-semibold text-ink">
              {{ formatMoney(merchant.wallet_balance ?? 0) }}
            </p>
            <p class="truncate text-xs text-muted">{{ merchant.institution?.name }}</p>
          </div>
        </li>
      </template>

      <template #empty>
        <EmptyState
          :icon="search || status ? 'search' : 'merchants'"
          title="No merchants found"
          :message="
            search || status
              ? 'No merchants match the current filters. Adjust the search or status filter.'
              : 'Register your first merchant to start accepting payments.'
          "
        >
          <template #action>
            <BaseButton v-if="!search && !status" @click="formOpen = true">
              Register merchant
            </BaseButton>
          </template>
        </EmptyState>
      </template>
    </DataTable>

    <PaginationBar v-if="merchants.meta" :meta="merchants.meta" @page="page = $event" />

    <MerchantFormModal :open="formOpen" @close="formOpen = false" @created="onCreated" />
  </div>
</template>
