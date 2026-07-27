<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { toApiError } from '../api/client'
import BaseButton from '../components/ui/BaseButton.vue'
import ConfirmDialog from '../components/ui/ConfirmDialog.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import StatCard from '../components/ui/StatCard.vue'
import StatusBadge from '../components/ui/StatusBadge.vue'
import { useOwnedPageTitle } from '../composables/usePageTitle'
import { useToast } from '../composables/useToast'
import { useMerchantsStore } from '../stores/merchants'
import { formatDate, formatDateTime } from '../utils/dates'
import { formatMoney } from '../utils/money'

const route = useRoute()
const merchants = useMerchantsStore()
const toast = useToast()
const pageTitle = useOwnedPageTitle()

const merchantId = computed(() => Number(route.params.id))
const merchant = computed(() => merchants.detail)

const confirmOpen = ref(false)
const toggling = ref(false)

/** Reset per merchant, so navigating between them never carries a reveal over. */
const accountVisible = ref(false)

/**
 * Last four digits only. Enough to check a payout against a statement, which
 * is what the number is used for here, without putting the whole thing on a
 * screen that sits open all day.
 */
function maskAccount(account: string): string {
  if (account.length <= 4) return '•'.repeat(account.length)

  return '•'.repeat(account.length - 4) + account.slice(-4)
}

function load(): void {
  merchants.fetchDetail(merchantId.value)
}

onMounted(load)
watch(merchantId, () => {
  accountVisible.value = false
  load()
})
watch(
  () => merchant.value?.business_name,
  (name) => pageTitle.set(name ?? null),
)

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
    <nav class="mb-4 text-sm text-muted" aria-label="Breadcrumb">
      <RouterLink
        to="/merchants"
        class="hover:text-ink focus-visible:outline-2 focus-visible:outline-accent"
      >
        Merchants
      </RouterLink>
      <span class="mx-2" aria-hidden="true">/</span>
      <span class="text-ink">{{ merchant?.business_name ?? '…' }}</span>
    </nav>

    <!-- Loading skeleton -->
    <div v-if="merchants.detailLoading" class="space-y-4">
      <div class="h-20 animate-pulse rounded-xl bg-line" />
      <div class="grid gap-4 lg:grid-cols-4">
        <div v-for="i in 4" :key="i" class="h-28 animate-pulse rounded-xl bg-canvas" />
      </div>
      <div class="h-64 animate-pulse rounded-xl bg-canvas" />
    </div>

    <!-- Error -->
    <div v-else-if="merchants.detailError" class="panel px-6 py-16 text-center">
      <p class="text-sm text-ink-soft" role="alert">{{ merchants.detailError }}</p>
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
          <p class="mt-1 text-sm text-muted">{{ merchant.name }} · {{ merchant.email }}</p>
          <p class="mt-0.5 text-sm text-muted">
            {{ merchant.institution?.name }} ·
            <!--
              Masked by default. This is the single most sensitive field in the
              product and the page it lives on is one an operator leaves open
              all day, in an office, on a screen other people walk past. It is
              needed rarely — to check a payout against a bank — so it is one
              deliberate click away rather than always on display.
            -->
            <span class="font-mono">{{
              accountVisible ? merchant.account_number : maskAccount(merchant.account_number)
            }}</span>
            <button
              type="button"
              class="ml-1.5 font-medium text-accent underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
              :aria-pressed="accountVisible"
              :aria-label="
                accountVisible ? 'Hide the account number' : 'Show the full account number'
              "
              @click="accountVisible = !accountVisible"
            >
              {{ accountVisible ? 'Hide' : 'Show' }}
            </button>
            <span class="ml-1.5">· registered {{ formatDate(merchant.created_at) }}</span>
          </p>
        </div>
        <!-- Kept apart from the identity block: a control that stops a
             merchant taking payments should not sit flush against their name
             as if it were part of the heading. -->
        <div
          class="flex items-center gap-3 rounded-lg border border-line bg-surface px-3 py-2 sm:px-4"
        >
          <p class="hidden text-xs text-muted sm:block">
            {{ deactivating ? 'Accepting payments' : 'Not accepting payments' }}
          </p>
          <BaseButton :variant="deactivating ? 'danger' : 'primary'" @click="confirmOpen = true">
            {{ deactivating ? 'Deactivate' : 'Activate' }}
          </BaseButton>
        </div>
      </div>

      <!-- Stats -->
      <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- The merchant's own float: the figure this page leads on, so it
             takes the brand colour that the other three stats don't. -->
        <div>
          <p class="text-xs font-medium tracking-wide text-muted uppercase">Wallet balance</p>
          <p class="font-display mt-1.5 text-2xl font-semibold text-accent">
            {{ formatMoney(merchant.wallet_balance ?? 0) }}
          </p>
          <p class="mt-1 text-sm text-muted">cleared at next settlement</p>
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
          <div class="panel">
            <div class="panel-head">
              <h3 class="font-display text-sm font-semibold text-ink">Recent transactions</h3>
            </div>
            <div v-if="merchant.recent_transactions.length === 0" class="px-4 py-10">
              <EmptyState
                :level="4"
                icon="transactions"
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
                  <p class="truncate font-mono text-xs text-muted">
                    {{ transaction.reference }}
                  </p>
                  <p class="text-xs text-muted">{{ formatDateTime(transaction.created_at) }}</p>
                </div>
                <div class="text-right">
                  <p class="font-display text-sm font-semibold text-ink">
                    {{ formatMoney(transaction.gross_amount) }}
                  </p>
                  <p class="text-xs text-muted">net {{ formatMoney(transaction.net_amount) }}</p>
                </div>
                <StatusBadge :status="transaction.status" />
              </li>
            </ul>
          </div>
        </section>

        <section>
          <div class="panel">
            <div class="panel-head">
              <h3 class="font-display text-sm font-semibold text-ink">Recent settlements</h3>
            </div>
            <div v-if="merchant.recent_settlements.length === 0" class="px-4 py-10">
              <EmptyState
                :level="4"
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
                  <p class="truncate font-mono text-xs text-muted">{{ settlement.reference }}</p>
                  <p class="text-xs text-muted">{{ formatDate(settlement.settled_at) }}</p>
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
          <strong>{{ merchant.business_name }}</strong> will stop accepting new payments. The
          current wallet balance of
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
