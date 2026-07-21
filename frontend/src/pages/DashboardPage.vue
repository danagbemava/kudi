<script setup lang="ts">
import { onMounted } from 'vue'
import VolumeChart from '../components/charts/VolumeChart.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import EmptyState from '../components/ui/EmptyState.vue'
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
      <p class="text-sm text-slate-600" role="alert">{{ dashboard.error }}</p>
      <BaseButton variant="secondary" class="mt-4" @click="dashboard.fetch()">Retry</BaseButton>
    </div>

    <template v-else>
      <!-- Balance hero + stat cards -->
      <div class="grid gap-4 lg:grid-cols-4">
        <div class="overflow-hidden rounded-xl bg-ink text-white lg:col-span-1">
          <div class="kente-thread" aria-hidden="true" />
          <div class="p-5">
            <p class="text-xs font-medium tracking-wide text-slate-400 uppercase">
              Merchant balances
            </p>
            <div v-if="dashboard.loading" class="mt-2 h-8 w-36 animate-pulse rounded bg-white/20" />
            <p v-else class="font-display mt-1.5 text-2xl font-semibold">
              {{ formatMoney(dashboard.data?.totals.wallet_balance ?? 0) }}
            </p>
            <p class="mt-1 text-sm text-slate-400">
              across {{ dashboard.data?.totals.merchant_count ?? '—' }} merchants
            </p>
          </div>
        </div>

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
        <div v-if="dashboard.loading" class="h-64 animate-pulse rounded-lg bg-slate-100" />
        <VolumeChart v-else-if="dashboard.data" :series="dashboard.data.volume_series" />
      </section>

      <!-- Recent transactions -->
      <section class="mt-6">
        <div class="mb-3 flex items-center justify-between">
          <h2 class="font-display text-sm font-semibold text-ink">Recent transactions</h2>
          <RouterLink
            to="/transactions"
            class="text-sm font-medium text-primary-600 hover:text-primary-700 focus-visible:outline-2 focus-visible:outline-primary-600"
          >
            View all
          </RouterLink>
        </div>

        <div class="overflow-hidden rounded-xl border border-line bg-white">
          <div v-if="dashboard.loading" class="divide-y divide-line/60">
            <div v-for="row in 5" :key="row" class="flex items-center justify-between px-4 py-3.5">
              <div class="h-4 w-40 animate-pulse rounded bg-slate-200" />
              <div class="h-4 w-24 animate-pulse rounded bg-slate-200" />
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
              class="flex items-center gap-4 px-4 py-3"
            >
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-ink">
                  {{ transaction.merchant?.business_name }}
                </p>
                <p class="font-mono truncate text-xs text-slate-500">
                  {{ transaction.reference }} · {{ formatDateTime(transaction.created_at) }}
                </p>
              </div>
              <p class="font-display text-sm font-semibold text-ink">
                {{ formatMoney(transaction.gross_amount) }}
              </p>
              <StatusBadge :status="transaction.status" />
            </li>
          </ul>
        </div>
      </section>
    </template>
  </div>
</template>
