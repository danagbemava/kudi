<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import RunSettlementButton from '../components/settlements/RunSettlementButton.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import FilterBar from '../components/ui/FilterBar.vue'
import PaginationBar from '../components/ui/PaginationBar.vue'
import { useDashboardStore } from '../stores/dashboard'
import { useSettlementsStore } from '../stores/settlements'
import { formatDate, formatDateTime } from '../utils/dates'
import { formatMoney } from '../utils/money'

const settlements = useSettlementsStore()
const dashboard = useDashboardStore()

const from = ref('')
const to = ref('')
const page = ref(1)

function load(): void {
  settlements.fetch({ from: from.value, to: to.value, page: page.value })
}

onMounted(() => {
  load()
  dashboard.fetch()
})

watch([from, to], () => {
  page.value = 1
  load()
})

watch(page, load)

const outstanding = computed(() => dashboard.data?.totals.wallet_balance ?? 0)

const activeFilterCount = computed(() => [from.value, to.value].filter(Boolean).length)

function clearFilters(): void {
  from.value = ''
  to.value = ''
}

/**
 * Which runs are open. A settlement run is the unit of work here, so history
 * leads with the run and its total; the merchants it paid are the detail
 * underneath, fetched only when someone asks for them.
 */
const expanded = ref<Set<string>>(new Set())

function toggle(batchRef: string): void {
  const next = new Set(expanded.value)

  if (next.has(batchRef)) {
    next.delete(batchRef)
  } else {
    next.add(batchRef)
    void settlements.loadMembers(batchRef)
  }

  expanded.value = next
}
</script>

<template>
  <div>
    <!-- The float this page exists to clear. It leads, so it takes the one
         figure per screen that carries the brand colour. -->
    <div class="mb-5 flex flex-wrap items-end justify-between gap-4 border-b border-line pb-5">
      <div>
        <p class="label-eyebrow text-muted">Outstanding wallet balances</p>
        <p class="font-display mt-1 text-3xl font-semibold text-accent tabular-nums">
          {{ dashboard.loading ? '…' : formatMoney(outstanding) }}
        </p>
        <p class="mt-1 text-sm text-muted">
          {{
            outstanding === 0
              ? 'Nothing to settle — every wallet is already cleared.'
              : 'moved to settlement records when the daily settlement runs'
          }}
        </p>
      </div>
      <RunSettlementButton @settled="load" />
    </div>

    <FilterBar :active-count="activeFilterCount" @clear="clearFilters">
      <template #filters>
        <!-- From/To are one control: they wrap as a unit and share a row
             on small screens instead of being split apart. -->
        <div class="flex basis-full items-center gap-2 sm:basis-auto">
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
    </FilterBar>

    <div class="panel">
      <p class="sr-only" role="status" aria-live="polite">
        {{
          settlements.loading
            ? ''
            : `${settlements.meta?.total ?? 0} settlement ${settlements.meta?.total === 1 ? 'run' : 'runs'}`
        }}
      </p>

      <div v-if="settlements.loading" class="divide-y divide-line/60" aria-hidden="true">
        <div v-for="row in 5" :key="row" class="flex items-center justify-between px-4 py-4">
          <div class="space-y-2">
            <div class="h-4 w-44 animate-pulse rounded bg-line" />
            <div class="h-3 w-28 animate-pulse rounded bg-canvas" />
          </div>
          <div class="h-5 w-28 animate-pulse rounded bg-line" />
        </div>
      </div>

      <div v-else-if="settlements.error" class="px-4 py-12 text-center">
        <p class="text-sm text-ink-soft" role="alert">{{ settlements.error }}</p>
      </div>

      <div v-else-if="settlements.batches.length === 0" class="px-4 py-12">
        <EmptyState
          icon="wallet"
          title="No settlement runs found"
          :message="
            from || to
              ? 'No runs in this date range. Widen the range to see earlier batches.'
              : 'Run the daily settlement to pay out merchant balances.'
          "
        />
      </div>

      <ul v-else class="divide-y divide-line/60">
        <li v-for="batch in settlements.batches" :key="batch.batch_ref">
          <!--
            The run is the row. Its reference used to repeat on every one of a
            dozen merchant lines while the figure the operator came for — what
            the run actually moved — was not on the screen at all.
          -->
          <h3>
            <button
              type="button"
              class="flex w-full items-center gap-3 px-4 py-4 text-left transition-colors hover:bg-canvas focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-accent"
              :aria-expanded="expanded.has(batch.batch_ref)"
              :aria-controls="`batch-${batch.batch_ref}`"
              @click="toggle(batch.batch_ref)"
            >
              <svg
                class="size-4 shrink-0 text-muted transition-transform"
                :class="expanded.has(batch.batch_ref) ? 'rotate-90' : ''"
                viewBox="0 0 16 16"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                aria-hidden="true"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 3.5L10.5 8 6 12.5" />
              </svg>

              <span class="min-w-0 flex-1">
                <span class="block font-medium text-ink">
                  {{ formatDate(batch.settled_at) }}
                  <span class="text-muted">
                    · {{ batch.merchant_count }}
                    {{ batch.merchant_count === 1 ? 'merchant' : 'merchants' }}
                  </span>
                </span>
                <span class="mt-0.5 block truncate font-mono text-xs text-muted">
                  {{ batch.batch_ref }}
                </span>
              </span>

              <span class="font-display shrink-0 text-base font-semibold text-ink tabular-nums">
                {{ formatMoney(batch.total_amount) }}
              </span>
            </button>
          </h3>

          <div
            v-show="expanded.has(batch.batch_ref)"
            :id="`batch-${batch.batch_ref}`"
            class="border-t border-line-soft bg-canvas/40"
          >
            <p
              v-if="settlements.membersLoading[batch.batch_ref]"
              class="px-4 py-4 pl-11 text-sm text-muted"
            >
              Loading the merchants in this run…
            </p>

            <table v-else class="w-full text-left text-sm">
              <caption class="sr-only">
                Merchants paid in run
                {{
                  batch.batch_ref
                }}
              </caption>
              <thead>
                <tr class="text-xs tracking-wide text-muted uppercase">
                  <th scope="col" class="py-2 pr-4 pl-11 font-semibold">Merchant</th>
                  <th scope="col" class="px-4 py-2 font-semibold">Reference</th>
                  <th scope="col" class="px-4 py-2 text-right font-semibold">Amount</th>
                  <th scope="col" class="px-4 py-2 text-right font-semibold">Settled at</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-line/50">
                <tr v-for="member in settlements.members[batch.batch_ref] ?? []" :key="member.id">
                  <td class="py-2.5 pr-4 pl-11">
                    <RouterLink
                      v-if="member.merchant"
                      :to="`/merchants/${member.merchant.id}`"
                      class="font-medium text-ink hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"
                    >
                      {{ member.merchant.business_name }}
                    </RouterLink>
                  </td>
                  <td class="px-4 py-2.5 font-mono text-xs text-muted">{{ member.reference }}</td>
                  <td class="px-4 py-2.5 text-right font-medium text-ink tabular-nums">
                    {{ formatMoney(member.amount) }}
                  </td>
                  <td class="px-4 py-2.5 text-right whitespace-nowrap text-muted tabular-nums">
                    {{ formatDateTime(member.settled_at) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </li>
      </ul>
    </div>

    <PaginationBar v-if="settlements.meta" :meta="settlements.meta" @page="page = $event" />
  </div>
</template>
