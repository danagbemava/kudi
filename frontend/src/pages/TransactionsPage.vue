<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PaymentSimulatorModal from '../components/transactions/PaymentSimulatorModal.vue'
import TransactionDetailModal from '../components/transactions/TransactionDetailModal.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import DataTable from '../components/ui/DataTable.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import FilterBar from '../components/ui/FilterBar.vue'
import PaginationBar from '../components/ui/PaginationBar.vue'
import StatusBadge from '../components/ui/StatusBadge.vue'
import { toApiError } from '../api/client'
import type { SortDirection, TransactionSort } from '../api'
import type { Column } from '../components/ui/DataTable.vue'
import type { Transaction } from '../api/types'
import { useToast } from '../composables/useToast'
import { useTransactionsStore } from '../stores/transactions'
import { formatDateTime } from '../utils/dates'
import { formatMoney } from '../utils/money'

const transactions = useTransactionsStore()
const toast = useToast()
const route = useRoute()
const router = useRouter()

/**
 * The open transaction lives in the URL rather than in local state, so a
 * specific payment can be linked to or reloaded, and the browser's back button
 * closes the panel instead of leaving the page.
 *
 * It is resolved through the store rather than read straight out of the loaded
 * list: the list is one page, so matching against it alone opens nothing for
 * any transaction past the first page.
 */
const selected = ref<Transaction | null>(null)

watch(
  () => route.query.tx,
  async (reference) => {
    if (typeof reference !== 'string' || !reference) {
      selected.value = null
      return
    }

    const found = await transactions.findByReference(reference)
    if (found) {
      selected.value = found
      return
    }

    // A stale or mistyped link should say so and tidy the URL, rather than
    // leaving the page looking like the click failed.
    selected.value = null
    toast.info(`No transaction found with reference ${reference}.`)
    closeDetail()
  },
  { immediate: true },
)

function openDetail(transaction: Transaction): void {
  router.push({ query: { ...route.query, tx: transaction.reference } })
}

function closeDetail(): void {
  const query = { ...route.query }
  delete query.tx
  router.push({ query })
}

// Which row is mid-check, so its own button can show progress without
// disabling every other pending row on the page.
const checking = ref<number | null>(null)

async function checkStatus(transaction: Transaction): Promise<void> {
  checking.value = transaction.id
  try {
    const resolved = await transactions.resolvePending(transaction.id)
    if (resolved.status === 'successful') {
      toast.success(`${resolved.reference} succeeded — the merchant's wallet has been credited.`)
    } else if (resolved.status === 'failed') {
      // A declined payment is bad news and has to look like it. The tone is a
      // visual register, not a claim about the request: this call succeeded,
      // the payment did not.
      toast.error(`${resolved.reference} failed at the gateway. No money moved.`)
    } else {
      // Genuinely neutral — nothing has happened yet.
      toast.info(`${resolved.reference} is still pending at the gateway.`)
    }
  } catch (e) {
    toast.error(toApiError(e).message)
  } finally {
    checking.value = null
  }
}

const search = ref('')
const status = ref('')
const from = ref('')
const to = ref('')
const page = ref(1)
const simulatorOpen = ref(false)
const sort = ref<TransactionSort>('date')
const direction = ref<SortDirection>('desc')

let searchTimer: ReturnType<typeof setTimeout> | undefined

function load(): void {
  transactions.fetch({
    search: search.value,
    status: status.value,
    from: from.value,
    to: to.value,
    page: page.value,
    sort: sort.value,
    direction: direction.value,
  })
}

/**
 * Clicking the active column flips it; clicking a new one starts descending,
 * which for a ledger is what you almost always want first — the largest
 * payment, the most recent day.
 */
function sortBy(key: string): void {
  if (sort.value === key) {
    direction.value = direction.value === 'asc' ? 'desc' : 'asc'
  } else {
    sort.value = key as TransactionSort
    direction.value = 'desc'
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

watch([status, from, to], () => {
  page.value = 1
  load()
})

watch(page, load)

const columns: Column[] = [
  { label: 'Reference', sort: 'reference' },
  { label: 'Merchant' },
  { label: 'Gross', align: 'right', sort: 'gross' },
  { label: 'Fee', align: 'right', sort: 'fee' },
  { label: 'Net', align: 'right', sort: 'net' },
  { label: 'Status', sort: 'status' },
  { label: 'Date', align: 'right', sort: 'date' },
]
const hasFilters = () => Boolean(search.value || status.value || from.value || to.value)

// Search sits outside the collapsible panel, so it is not counted here.
const activeFilterCount = computed(
  () => [status.value, from.value, to.value].filter(Boolean).length,
)

function clearFilters(): void {
  status.value = ''
  from.value = ''
  to.value = ''
}
</script>

<template>
  <div>
    <FilterBar :active-count="activeFilterCount" @clear="clearFilters">
      <template #search>
        <div class="relative basis-full sm:max-w-56 sm:flex-1 sm:basis-auto">
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
            placeholder="Search reference…"
            aria-label="Search by payment reference"
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
          <option value="successful">Successful</option>
          <option value="pending">Pending</option>
          <option value="failed">Failed</option>
        </select>
        <!-- From/To are one control, so they wrap as a unit and share a full row
             on small screens rather than being split across two. -->
        <div class="order-last flex basis-full items-center gap-2 sm:order-none sm:basis-auto">
          <label
            class="flex flex-1 flex-col gap-1 text-sm text-muted sm:flex-none sm:flex-row sm:items-center sm:gap-1.5"
          >
            From
            <input
              v-model="from"
              type="date"
              :max="to || undefined"
              class="w-full min-w-0 rounded-lg border border-field bg-surface px-2.5 py-2 text-sm text-ink hover:border-line focus:outline-2 focus:outline-offset-1 focus:outline-accent sm:w-auto"
            />
          </label>
          <label
            class="flex flex-1 flex-col gap-1 text-sm text-muted sm:flex-none sm:flex-row sm:items-center sm:gap-1.5"
          >
            To
            <input
              v-model="to"
              type="date"
              :min="from || undefined"
              class="w-full min-w-0 rounded-lg border border-field bg-surface px-2.5 py-2 text-sm text-ink hover:border-line focus:outline-2 focus:outline-offset-1 focus:outline-accent sm:w-auto"
            />
          </label>
        </div>
      </template>

      <template #action>
        <BaseButton @click="simulatorOpen = true">Simulate payment</BaseButton>
      </template>
    </FilterBar>

    <DataTable
      :columns="columns"
      label="Transactions"
      :count="transactions.items.length"
      :loading="transactions.loading"
      :error="transactions.error"
      :empty="transactions.items.length === 0"
      :sort-key="sort"
      :sort-direction="direction"
      @retry="load"
      @sort="sortBy"
    >
      <tr
        v-for="transaction in transactions.items"
        :key="transaction.id"
        class="relative transition-colors hover:bg-canvas/70"
      >
        <!-- The reference is the control: its ::after covers the row, so the
             whole row opens the detail while the accessible name stays the
             reference rather than a concatenation of every cell. -->
        <td class="px-4 py-3">
          <button
            type="button"
            class="font-mono text-xs text-ink-soft after:absolute after:inset-0 hover:text-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
            :aria-label="`View details for ${transaction.reference}`"
            @click="openDetail(transaction)"
          >
            {{ transaction.reference }}
          </button>
        </td>
        <td class="px-4 py-3">
          <RouterLink
            v-if="transaction.merchant"
            :to="`/merchants/${transaction.merchant.id}`"
            class="relative z-10 font-medium text-ink hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"
          >
            {{ transaction.merchant.business_name }}
          </RouterLink>
        </td>
        <!-- Right-aligned so the decimal points line up down the column and
             magnitude is legible without reading each figure. -->
        <td class="font-display px-4 py-3 text-right font-semibold text-ink tabular-nums">
          {{ formatMoney(transaction.gross_amount) }}
        </td>
        <td class="px-4 py-3 text-right text-muted tabular-nums">
          {{ formatMoney(transaction.fee_amount) }}
        </td>
        <td class="px-4 py-3 text-right text-ink-soft tabular-nums">
          {{ formatMoney(transaction.net_amount) }}
        </td>
        <!-- The recovery sits next to the state it recovers from, rather than in
             an actions column that would be empty on almost every row. -->
        <td class="px-4 py-3">
          <div class="flex items-center gap-2">
            <StatusBadge :status="transaction.status" />
            <button
              v-if="transaction.status === 'pending'"
              type="button"
              class="relative z-10 text-xs font-medium text-accent underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:cursor-not-allowed disabled:text-muted disabled:no-underline"
              :disabled="checking === transaction.id"
              @click="checkStatus(transaction)"
            >
              {{ checking === transaction.id ? 'Checking…' : 'Check status' }}
            </button>
          </div>
        </td>
        <td class="px-4 py-3 text-right whitespace-nowrap text-muted tabular-nums">
          {{ formatDateTime(transaction.created_at) }}
        </td>
      </tr>

      <template #cards>
        <li
          v-for="transaction in transactions.items"
          :key="transaction.id"
          class="relative px-4 py-3 transition-colors focus-within:bg-canvas hover:bg-canvas"
        >
          <div class="flex items-start justify-between gap-3">
            <button
              type="button"
              class="min-w-0 flex-1 text-left text-sm font-medium text-ink after:absolute after:inset-0 hover:text-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
              :aria-label="`View details for ${transaction.reference}`"
              @click="openDetail(transaction)"
            >
              {{ transaction.merchant?.business_name ?? transaction.reference }}
            </button>
            <StatusBadge :status="transaction.status" />
          </div>

          <p class="mt-0.5 truncate font-mono text-xs text-muted">
            {{ transaction.reference }} · {{ formatDateTime(transaction.created_at) }}
          </p>

          <div class="mt-2 flex items-baseline justify-between gap-3">
            <p class="font-display text-base font-semibold text-ink">
              {{ formatMoney(transaction.gross_amount) }}
            </p>
            <p class="text-xs text-muted">
              fee {{ formatMoney(transaction.fee_amount) }} · net
              {{ formatMoney(transaction.net_amount) }}
            </p>
          </div>

          <!-- The whole card is a link via an ::after overlay, so this has to be
               lifted above it to stay clickable. -->
          <button
            v-if="transaction.status === 'pending'"
            type="button"
            class="relative z-10 mt-2 min-h-11 text-sm font-medium text-accent underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:cursor-not-allowed disabled:text-muted disabled:no-underline"
            :disabled="checking === transaction.id"
            @click="checkStatus(transaction)"
          >
            {{ checking === transaction.id ? 'Checking…' : 'Check status' }}
          </button>
        </li>
      </template>

      <template #empty>
        <EmptyState
          :icon="hasFilters() ? 'search' : 'transactions'"
          title="No transactions found"
          :message="
            hasFilters()
              ? 'Nothing matches the current filters. Widen the date range or clear the search.'
              : 'Simulate a customer payment to create the first transaction.'
          "
        >
          <template #action>
            <BaseButton v-if="!hasFilters()" @click="simulatorOpen = true">
              Simulate payment
            </BaseButton>
          </template>
        </EmptyState>
      </template>
    </DataTable>

    <PaginationBar v-if="transactions.meta" :meta="transactions.meta" @page="page = $event" />

    <TransactionDetailModal v-if="selected" :transaction="selected" @close="closeDetail" />

    <PaymentSimulatorModal :open="simulatorOpen" @close="simulatorOpen = false" @completed="load" />
  </div>
</template>
