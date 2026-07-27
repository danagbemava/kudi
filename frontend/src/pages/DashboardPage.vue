<script setup lang="ts">
import { onMounted } from 'vue'
import VolumeChart from '../components/charts/VolumeChart.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import SettlementBand from '../components/dashboard/SettlementBand.vue'
import RunSettlementButton from '../components/settlements/RunSettlementButton.vue'
import StatCard from '../components/ui/StatCard.vue'
import StatusBadge from '../components/ui/StatusBadge.vue'
import { useDashboardStore } from '../stores/dashboard'
import { formatDate, formatDateTime, relativeDay } from '../utils/dates'
import { feePercent, formatMoney } from '../utils/money'

const dashboard = useDashboardStore()

onMounted(() => {
  dashboard.fetch()
})
</script>

<template>
  <div>
    <!-- Error state -->
    <div v-if="dashboard.error" class="panel px-6 py-16 text-center">
      <p class="text-sm text-ink-soft" role="alert">{{ dashboard.error }}</p>
      <BaseButton variant="secondary" class="mt-4" @click="dashboard.fetch()">Retry</BaseButton>
    </div>

    <template v-else>
      <!-- The float waiting to be settled, and whose it is. This is the number
           the operator acts on, so it leads and the rest supports it. -->
      <section class="panel p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <p class="label-eyebrow text-muted">Waiting to settle</p>
            <!-- The one figure on the screen that carries the brand colour. -->
            <div v-if="dashboard.loading" class="mt-2 h-10 w-56 animate-pulse rounded bg-line" />
            <p v-else class="font-display mt-1 text-4xl font-semibold text-accent sm:text-5xl">
              {{ formatMoney(dashboard.data?.totals.wallet_balance ?? 0) }}
            </p>
            <p class="mt-1.5 text-sm text-muted">
              <template v-if="dashboard.data && dashboard.data.totals.wallet_balance === 0">
                Every wallet is cleared — nothing is waiting to settle.
              </template>
              <template v-else>
                <!-- holder_count, not unsettled.length: the list is capped, so
                     its length is the cap rather than the number of merchants
                     actually owed money. -->
                held across {{ dashboard.data?.unsettled_meta.holder_count ?? '—' }} of
                {{ dashboard.data?.totals.merchant_count ?? '—' }} merchants
              </template>
            </p>
          </div>

          <div class="flex shrink-0 items-center gap-3">
            <RouterLink
              to="/settlements"
              class="text-sm font-medium text-ink-soft underline-offset-4 hover:text-ink hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
            >
              Settlement history
            </RouterLink>
            <RunSettlementButton @settled="dashboard.fetch()" />
          </div>
        </div>

        <SettlementBand
          v-if="!dashboard.loading && dashboard.data"
          :merchants="dashboard.data.unsettled"
          :total="dashboard.data.totals.wallet_balance"
          :remainder-count="dashboard.data.unsettled_meta.remainder_count"
          :remainder-amount="dashboard.data.unsettled_meta.remainder_amount"
        />

        <p v-if="!dashboard.loading" class="mt-4 border-t border-line-soft pt-3 text-xs text-muted">
          <template v-if="dashboard.data?.last_settlement">
            Last run
            <span class="font-medium text-ink-soft">
              {{ relativeDay(dashboard.data.last_settlement.settled_at) }}
            </span>
            ({{ formatDate(dashboard.data.last_settlement.settled_at) }}) —
            {{ formatMoney(dashboard.data.last_settlement.amount) }} to
            {{ dashboard.data.last_settlement.merchant_count }}
            {{ dashboard.data.last_settlement.merchant_count === 1 ? 'merchant' : 'merchants' }}
          </template>
          <template v-else> No settlement has been run yet. </template>
        </p>
      </section>

      <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard
          label="Payment volume"
          :value="formatMoney(dashboard.data?.totals.payment_volume ?? 0)"
          sub="successful payments, all time"
          :loading="dashboard.loading"
        />
        <StatCard
          label="Total settled"
          :value="formatMoney(dashboard.data?.totals.settled ?? 0)"
          sub="paid out to merchants"
          :loading="dashboard.loading"
        />
        <StatCard
          label="In flight"
          :value="formatMoney(dashboard.data?.totals.pending_amount ?? 0)"
          :sub="`${dashboard.data?.totals.pending_count ?? 0} pending — not yet in a wallet`"
          :loading="dashboard.loading"
        />
        <StatCard
          label="Platform fees"
          :value="formatMoney(dashboard.data?.totals.platform_fees ?? 0)"
          :sub="`${feePercent()}% of successful payments`"
          :loading="dashboard.loading"
        />
      </div>

      <!--
        Chart and ledger share a row from 1280px.

        This used to wait for 1536px, on the grounds that a 3fr/2fr split at
        1280 leaves the ledger around 460px and truncates the reference. But
        1440 and 1366 are the two commonest laptop widths, so the effect was
        that almost nobody ever saw the two-column layout and everybody scrolled
        past a full-width chart to reach the ledger. Narrowing the ledger's
        share to 5fr/3fr keeps it near 480px at 1280 and gives it more than that
        at every width above — the reference fits, and the row is reachable on
        the screens people actually use.
      -->
      <div class="mt-6 grid items-start gap-x-8 gap-y-6 xl:grid-cols-[minmax(0,5fr)_minmax(0,3fr)]">
        <!-- Volume chart -->
        <section class="panel">
          <div class="panel-head">
            <h2 class="font-display text-sm font-semibold text-ink">
              Payment volume — last 30 days
            </h2>
          </div>
          <div class="p-5">
            <div v-if="dashboard.loading" class="h-64 animate-pulse rounded-lg bg-canvas xl:h-80" />
            <VolumeChart v-else-if="dashboard.data" :series="dashboard.data.volume_series" />
          </div>
        </section>

        <!-- Recent transactions -->
        <section class="panel">
          <div class="panel-head">
            <h2 class="font-display text-sm font-semibold text-ink">Recent transactions</h2>
            <RouterLink
              to="/transactions"
              class="text-sm font-medium text-accent hover:text-accent/80 focus-visible:outline-2 focus-visible:outline-accent"
            >
              View all
            </RouterLink>
          </div>

          <div>
            <div v-if="dashboard.loading" class="divide-y divide-line/60">
              <div
                v-for="row in 5"
                :key="row"
                class="flex items-center justify-between px-4 py-3.5"
              >
                <div class="h-4 w-40 animate-pulse rounded bg-line" />
                <div class="h-4 w-24 animate-pulse rounded bg-line" />
              </div>
            </div>

            <div
              v-else-if="(dashboard.data?.recent_transactions.length ?? 0) === 0"
              class="px-4 py-12"
            >
              <EmptyState
                :level="3"
                icon="transactions"
                title="No transactions yet"
                message="Simulate a customer payment to see it appear here."
              >
                <template #action>
                  <RouterLink to="/transactions">
                    <BaseButton variant="secondary">Go to transactions</BaseButton>
                  </RouterLink>
                </template>
              </EmptyState>
            </div>

            <ul v-else class="divide-y divide-line/60">
              <li
                v-for="transaction in dashboard.data?.recent_transactions"
                :key="transaction.id"
                class="px-4 py-3"
              >
                <!--
                  Two full-width rows rather than one row of four columns.
                  Sharing a line, the amount and the badge left the reference
                  and timestamp about 220px in this column and the date was
                  always cut off mid-word. Stacked, each line gets the panel's
                  whole width and nothing truncates.
                -->
                <div class="flex items-baseline justify-between gap-3">
                  <p class="min-w-0 truncate text-sm font-medium text-ink">
                    {{ transaction.merchant?.business_name }}
                  </p>
                  <p class="font-display shrink-0 text-sm font-semibold text-ink tabular-nums">
                    {{ formatMoney(transaction.gross_amount) }}
                  </p>
                </div>

                <div class="mt-0.5 flex items-center justify-between gap-3">
                  <p class="min-w-0 truncate font-mono text-xs text-muted">
                    {{ transaction.reference }} · {{ formatDateTime(transaction.created_at) }}
                  </p>
                  <StatusBadge :status="transaction.status" class="shrink-0" />
                </div>
              </li>
            </ul>
          </div>
        </section>
      </div>
    </template>
  </div>
</template>
