<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { toApiError } from '../api/client'
import BaseButton from '../components/ui/BaseButton.vue'
import ConfirmDialog from '../components/ui/ConfirmDialog.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import StatCard from '../components/ui/StatCard.vue'
import StatusBadge from '../components/ui/StatusBadge.vue'
import { useToast } from '../composables/useToast'
import { useMerchantsStore } from '../stores/merchants'
import { formatDate, formatDateTime } from '../utils/dates'
import { formatMoney } from '../utils/money'

const route = useRoute()
const merchants = useMerchantsStore()
const toast = useToast()

const merchantId = computed(() => Number(route.params.id))
const merchant = computed(() => merchants.detail)

const confirmOpen = ref(false)
const toggling = ref(false)

function load(): void {
  merchants.fetchDetail(merchantId.value)
}

onMounted(load)
watch(merchantId, load)

const deactivating = computed(() => merchant.value?.status === 'active')

async function toggleStatus(): Promise<void> {
  if (!merchant.value) return
  toggling.value = true

  try {
    const next = deactivating.value ? 'inactive' : 'active'
    await merchants.setStatus(merchant.value.id, next)
    toast.success(
      next === 'inactive'
        ? `${merchant.value.business_name} deactivated. New payments are blocked.`
        : `${merchant.value.business_name} activated.`,
    )
    confirmOpen.value = false
  } catch (error) {
    toast.error(toApiError(error).message)
  } finally {
    toggling.value = false
  }
}
</script>

<template>
  <div>
    <nav class="mb-4 text-sm text-slate-500" aria-label="Breadcrumb">
      <RouterLink
        to="/merchants"
        class="hover:text-ink focus-visible:outline-2 focus-visible:outline-primary-600"
      >
        Merchants
      </RouterLink>
      <span class="mx-2" aria-hidden="true">/</span>
      <span class="text-ink">{{ merchant?.business_name ?? '…' }}</span>
    </nav>

    <!-- Loading skeleton -->
    <div v-if="merchants.detailLoading" class="space-y-4">
      <div class="h-20 animate-pulse rounded-xl bg-slate-200" />
      <div class="grid gap-4 lg:grid-cols-4">
        <div v-for="i in 4" :key="i" class="h-28 animate-pulse rounded-xl bg-slate-100" />
      </div>
      <div class="h-64 animate-pulse rounded-xl bg-slate-100" />
    </div>

    <!-- Error -->
    <div
      v-else-if="merchants.detailError"
      class="rounded-xl border border-line bg-white px-6 py-16 text-center"
    >
      <p class="text-sm text-slate-600">{{ merchants.detailError }}</p>
      <BaseButton variant="secondary" class="mt-4" @click="load">Retry</BaseButton>
    </div>

    <template v-else-if="merchant">
      <!-- Header -->
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <div class="flex items-center gap-3">
            <h2 class="font-display text-xl font-semibold text-ink">
              {{ merchant.business_name }}
            </h2>
            <StatusBadge :status="merchant.status" />
          </div>
          <p class="mt-1 text-sm text-slate-500">
            {{ merchant.name }} · {{ merchant.email }}
          </p>
          <p class="mt-0.5 text-sm text-slate-500">
            {{ merchant.institution?.name }} ·
            <span class="font-mono">{{ merchant.account_number }}</span>
            · registered {{ formatDate(merchant.created_at) }}
          </p>
        </div>
        <BaseButton
          :variant="deactivating ? 'danger' : 'primary'"
          @click="confirmOpen = true"
        >
          {{ deactivating ? 'Deactivate' : 'Activate' }}
        </BaseButton>
      </div>

      <!-- Stats -->
      <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="overflow-hidden rounded-xl bg-ink text-white">
          <div class="kente-thread" aria-hidden="true" />
          <div class="p-5">
            <p class="text-xs font-medium tracking-wide text-slate-400 uppercase">Wallet balance</p>
            <p class="font-display mt-1.5 text-2xl font-semibold">
              {{ formatMoney(merchant.wallet_balance ?? 0) }}
            </p>
            <p class="mt-1 text-sm text-slate-400">cleared at next settlement</p>
          </div>
        </div>
        <StatCard
          label="Total settled"
          :value="formatMoney(merchant.stats.total_settled)"
          sub="paid out to date"
        />
        <StatCard
          label="Successful volume"
          :value="formatMoney(merchant.stats.successful_volume)"
          sub="gross, all time"
        />
        <StatCard
          label="Transactions"
          :value="String(merchant.stats.transaction_count)"
          sub="all statuses"
        />
      </div>

      <!-- Transactions + settlements -->
      <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section>
          <h3 class="font-display mb-3 text-sm font-semibold text-ink">Recent transactions</h3>
          <div class="overflow-hidden rounded-xl border border-line bg-white">
            <div v-if="merchant.recent_transactions.length === 0" class="px-4 py-10">
              <EmptyState
                title="No transactions"
                message="Payments to this merchant will appear here."
              />
            </div>
            <ul v-else class="divide-y divide-line/60">
              <li
                v-for="transaction in merchant.recent_transactions"
                :key="transaction.id"
                class="flex items-center gap-3 px-4 py-3"
              >
                <div class="min-w-0 flex-1">
                  <p class="font-mono truncate text-xs text-slate-500">{{ transaction.reference }}</p>
                  <p class="text-xs text-slate-500">{{ formatDateTime(transaction.created_at) }}</p>
                </div>
                <div class="text-right">
                  <p class="font-display text-sm font-semibold text-ink">
                    {{ formatMoney(transaction.gross_amount) }}
                  </p>
                  <p class="text-xs text-slate-500">net {{ formatMoney(transaction.net_amount) }}</p>
                </div>
                <StatusBadge :status="transaction.status" />
              </li>
            </ul>
          </div>
        </section>

        <section>
          <h3 class="font-display mb-3 text-sm font-semibold text-ink">Recent settlements</h3>
          <div class="overflow-hidden rounded-xl border border-line bg-white">
            <div v-if="merchant.recent_settlements.length === 0" class="px-4 py-10">
              <EmptyState
                title="No settlements yet"
                message="Run a settlement to pay out this merchant's balance."
              />
            </div>
            <ul v-else class="divide-y divide-line/60">
              <li
                v-for="settlement in merchant.recent_settlements"
                :key="settlement.id"
                class="flex items-center gap-3 px-4 py-3"
              >
                <div class="min-w-0 flex-1">
                  <p class="font-mono truncate text-xs text-slate-500">{{ settlement.reference }}</p>
                  <p class="text-xs text-slate-500">{{ formatDate(settlement.settled_at) }}</p>
                </div>
                <p class="font-display text-sm font-semibold text-ink">
                  {{ formatMoney(settlement.amount) }}
                </p>
              </li>
            </ul>
          </div>
        </section>
      </div>

      <ConfirmDialog
        :open="confirmOpen"
        :title="deactivating ? 'Deactivate merchant' : 'Activate merchant'"
        :confirm-label="deactivating ? 'Deactivate' : 'Activate'"
        :tone="deactivating ? 'danger' : 'primary'"
        :loading="toggling"
        @close="confirmOpen = false"
        @confirm="toggleStatus"
      >
        <template v-if="deactivating">
          <strong>{{ merchant.business_name }}</strong> will stop accepting new payments.
          The current wallet balance of
          <strong>{{ formatMoney(merchant.wallet_balance ?? 0) }}</strong>
          is kept and will still be settled.
        </template>
        <template v-else>
          <strong>{{ merchant.business_name }}</strong> will be able to accept payments again.
        </template>
      </ConfirmDialog>
    </template>
  </div>
</template>
