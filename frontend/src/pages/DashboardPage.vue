<script setup lang="ts">
import { onMounted } from 'vue'
import VolumeChart from '../components/charts/VolumeChart.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import SettlementBand from '../components/dashboard/SettlementBand.vue'
import StatCard from '../components/ui/StatCard.vue'
import StatusBadge from '../components/ui/StatusBadge.vue'
import { useDashboardStore } from '../stores/dashboard'
import { formatDateTime } from '../utils/dates'
import { formatMoney } from '../utils/money'

const dashboard = useDashboardStore()

onMounted(() => {
  dashboard.fetch()
})
</script>

<template>
  <div>
    <!-- Error state -->
    <div
      v-if="dashboard.error"
      class="rounded-xl border border-line bg-white px-6 py-16 text-center"
    >
      <p class="text-sm text-zinc-600" role="alert">{{ dashboard.error }}</p>
      <BaseButton variant="secondary" class="mt-4" @click="dashboard.fetch()">Retry</BaseButton>
    </div>

    <template v-else>
      <!-- The float waiting to be settled, and whose it is. This is the number
           the operator acts on, so it leads and the rest supports it. -->
      <section class="overflow-hidden rounded-xl bg-ink p-5 text-white sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <p class="label-eyebrow text-gold">Waiting to settle</p>
            <div v-if="dashboard.loading" class="mt-2 h-10 w-56 animate-pulse rounded bg-white/15" />
            <p v-else class="font-display mt-1 text-4xl font-semibold sm:text-5xl">
              {{ formatMoney(dashboard.data?.totals.wallet_balance ?? 0) }}
            </p>
            <p class="mt-1.5 text-sm text-zinc-300">
              held across {{ dashboard.data?.unsettled.length ?? '—' }} of
              {{ dashboard.data?.totals.merchant_count ?? '—' }} merchants
            </p>
          </div>

          <RouterLink to="/settlements" class="shrink-0">
            <BaseButton>Run settlement</BaseButton>
          </RouterLink>
        </div>

        <SettlementBand
          v-if="!dashboard.loading && dashboard.data"
          :merchants="dashboard.data.unsettled"
          :total="dashboard.data.totals.wallet_balance"
        />
      </section>

      <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
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
          label="Platform fees"
          :value="formatMoney(dashboard.data?.totals.platform_fees ?? 0)"
          :sub="`1.5% per payment · ${dashboard.data?.totals.pending_count ?? 0} pending`"
          :loading="dashboard.loading"
        />
      </div>

      <!-- Volume chart -->
      <section class="mt-6 rounded-xl border border-line bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="font-display text-sm font-semibold text-ink">Payment volume — last 30 days</h2>
        </div>
        <div v-if="dashboard.loading" class="h-64 animate-pulse rounded-lg bg-zinc-100" />
        <VolumeChart v-else-if="dashboard.data" :series="dashboard.data.volume_series" />
      </section>

      <!-- Recent transactions -->
      <section class="mt-6">
        <div class="mb-3 flex items-center justify-between">
          <h2 class="font-display text-sm font-semibold text-ink">Recent transactions</h2>
          <RouterLink
            to="/transactions"
            class="text-sm font-medium text-forest hover:text-forest-deep focus-visible:outline-2 focus-visible:outline-forest"
          >
            View all
          </RouterLink>
        </div>

        <div class="overflow-hidden rounded-xl border border-line bg-white">
          <div v-if="dashboard.loading" class="divide-y divide-line/60">
            <div v-for="row in 5" :key="row" class="flex items-center justify-between px-4 py-3.5">
              <div class="h-4 w-40 animate-pulse rounded bg-zinc-200" />
              <div class="h-4 w-24 animate-pulse rounded bg-zinc-200" />
            </div>
          </div>

          <div
            v-else-if="(dashboard.data?.recent_transactions.length ?? 0) === 0"
            class="px-4 py-12"
          >
            <EmptyState
              :level="3"
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
              class="flex flex-wrap items-center gap-x-4 gap-y-1.5 px-4 py-3 sm:flex-nowrap"
            >
              <!-- On a phone the amount and badge take a second line, so the
                   merchant name gets the full width instead of truncating to
                   an unrecognisable stub. -->
              <div class="min-w-0 basis-full sm:flex-1 sm:basis-auto">
                <p class="truncate text-sm font-medium text-ink">
                  {{ transaction.merchant?.business_name }}
                </p>
                <p class="font-mono truncate text-xs text-zinc-500">
                  {{ transaction.reference }} · {{ formatDateTime(transaction.created_at) }}
                </p>
              </div>
              <p class="font-display text-sm font-semibold text-ink">
                {{ formatMoney(transaction.gross_amount) }}
              </p>
              <StatusBadge :status="transaction.status" class="ml-auto sm:ml-0" />
            </li>
          </ul>
        </div>
      </section>
    </template>
  </div>
</template>
