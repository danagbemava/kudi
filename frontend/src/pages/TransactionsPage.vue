<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import PaymentSimulatorModal from '../components/transactions/PaymentSimulatorModal.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import DataTable from '../components/ui/DataTable.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import FilterBar from '../components/ui/FilterBar.vue'
import PaginationBar from '../components/ui/PaginationBar.vue'
import StatusBadge from '../components/ui/StatusBadge.vue'
import { useTransactionsStore } from '../stores/transactions'
import { formatDateTime } from '../utils/dates'
import { formatMoney } from '../utils/money'

const transactions = useTransactionsStore()

const search = ref('')
const status = ref('')
const from = ref('')
const to = ref('')
const page = ref(1)
const simulatorOpen = ref(false)

let searchTimer: ReturnType<typeof setTimeout> | undefined

function load(): void {
  transactions.fetch({
    search: search.value,
    status: status.value,
    from: from.value,
    to: to.value,
    page: page.value,
  })
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

const columns = ['Reference', 'Merchant', 'Gross', 'Fee', 'Net', 'Status', 'Date']
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
            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400"
            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
          </svg>
          <input
            v-model="search"
            type="search"
            placeholder="Search reference…"
            aria-label="Search by payment reference"
            class="w-full rounded-lg border border-field bg-white py-2 pr-3 pl-9 text-sm placeholder:text-slate-500 hover:border-slate-300 focus:outline-2 focus:outline-offset-1 focus:outline-primary-600"
          />
        </div>
      </template>

      <template #filters>
        <select
          v-model="status"
          aria-label="Filter by status"
          class="rounded-lg border border-field bg-white px-3 py-2 text-sm hover:border-slate-300 focus:outline-2 focus:outline-offset-1 focus:outline-primary-600"
        >
          <option value="">All statuses</option>
          <option value="successful">Successful</option>
          <option value="pending">Pending</option>
          <option value="failed">Failed</option>
        </select>
        <!-- From/To are one control, so they wrap as a unit and share a full row
             on small screens rather than being split across two. -->
        <div class="order-last flex basis-full items-center gap-2 sm:order-none sm:basis-auto">
          <label class="flex flex-1 flex-col gap-1 text-sm text-slate-500 sm:flex-none sm:flex-row sm:items-center sm:gap-1.5">
            From
            <input
              v-model="from"
              type="date"
              :max="to || undefined"
              class="w-full min-w-0 rounded-lg border border-field bg-white px-2.5 py-2 text-sm text-ink hover:border-slate-300 focus:outline-2 focus:outline-offset-1 focus:outline-primary-600 sm:w-auto"
            />
          </label>
          <label class="flex flex-1 flex-col gap-1 text-sm text-slate-500 sm:flex-none sm:flex-row sm:items-center sm:gap-1.5">
            To
            <input
              v-model="to"
              type="date"
              :min="from || undefined"
              class="w-full min-w-0 rounded-lg border border-field bg-white px-2.5 py-2 text-sm text-ink hover:border-slate-300 focus:outline-2 focus:outline-offset-1 focus:outline-primary-600 sm:w-auto"
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
      @retry="load"
    >
      <tr
        v-for="transaction in transactions.items"
        :key="transaction.id"
        class="transition-colors hover:bg-slate-50/70"
      >
        <td class="font-mono px-4 py-3 text-xs text-slate-600">{{ transaction.reference }}</td>
        <td class="px-4 py-3">
          <RouterLink
            v-if="transaction.merchant"
            :to="`/merchants/${transaction.merchant.id}`"
            class="font-medium text-ink hover:text-primary-600 focus-visible:outline-2 focus-visible:outline-primary-600"
          >
            {{ transaction.merchant.business_name }}
          </RouterLink>
        </td>
        <td class="font-display px-4 py-3 font-semibold text-ink">
          {{ formatMoney(transaction.gross_amount) }}
        </td>
        <td class="px-4 py-3 text-slate-500">{{ formatMoney(transaction.fee_amount) }}</td>
        <td class="px-4 py-3 text-slate-600">{{ formatMoney(transaction.net_amount) }}</td>
        <td class="px-4 py-3"><StatusBadge :status="transaction.status" /></td>
        <td class="px-4 py-3 whitespace-nowrap text-slate-500">
          {{ formatDateTime(transaction.created_at) }}
        </td>
      </tr>

      <template #cards>
        <li v-for="transaction in transactions.items" :key="transaction.id" class="px-4 py-3">
          <div class="flex items-start justify-between gap-3">
            <RouterLink
              v-if="transaction.merchant"
              :to="`/merchants/${transaction.merchant.id}`"
              class="min-w-0 flex-1 text-sm font-medium text-ink hover:text-primary-600 focus-visible:outline-2 focus-visible:outline-primary-600"
            >
              {{ transaction.merchant.business_name }}
            </RouterLink>
            <StatusBadge :status="transaction.status" />
          </div>

          <p class="font-mono mt-0.5 truncate text-xs text-slate-500">
            {{ transaction.reference }} · {{ formatDateTime(transaction.created_at) }}
          </p>

          <div class="mt-2 flex items-baseline justify-between gap-3">
            <p class="font-display text-base font-semibold text-ink">
              {{ formatMoney(transaction.gross_amount) }}
            </p>
            <p class="text-xs text-slate-500">
              fee {{ formatMoney(transaction.fee_amount) }} · net
              {{ formatMoney(transaction.net_amount) }}
            </p>
          </div>
        </li>
      </template>

      <template #empty>
        <EmptyState
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

    <PaymentSimulatorModal :open="simulatorOpen" @close="simulatorOpen = false" @completed="load" />
  </div>
</template>
