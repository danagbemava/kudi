<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { toApiError } from '../api/client'
import BaseButton from '../components/ui/BaseButton.vue'
import ConfirmDialog from '../components/ui/ConfirmDialog.vue'
import DataTable from '../components/ui/DataTable.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import PaginationBar from '../components/ui/PaginationBar.vue'
import { useToast } from '../composables/useToast'
import { useDashboardStore } from '../stores/dashboard'
import { useSettlementsStore } from '../stores/settlements'
import { formatDateTime } from '../utils/dates'
import { formatMoney } from '../utils/money'

const settlements = useSettlementsStore()
const dashboard = useDashboardStore()
const toast = useToast()

const from = ref('')
const to = ref('')
const page = ref(1)
const confirmOpen = ref(false)

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

async function runSettlement(): Promise<void> {
  try {
    const result = await settlements.run()
    confirmOpen.value = false
    toast.success(
      `Settled ${formatMoney(result.total_amount)} to ${result.merchant_count} ` +
        `${result.merchant_count === 1 ? 'merchant' : 'merchants'} (${result.batch_ref}).`,
    )
    dashboard.fetch()
  } catch (error) {
    confirmOpen.value = false
    toast.error(toApiError(error).message)
  }
}

const columns = ['Reference', 'Batch', 'Merchant', 'Amount', 'Settled at']
</script>

<template>
  <div>
    <!-- Outstanding balance banner -->
    <div
      class="mb-5 flex flex-wrap items-center justify-between gap-4 overflow-hidden rounded-xl bg-ink text-white"
    >
      <div class="flex flex-1 basis-full flex-col">
        <div class="kente-thread" aria-hidden="true" />
        <div class="flex flex-wrap items-center justify-between gap-4 p-5">
          <div>
            <p class="text-xs font-medium tracking-wide text-slate-400 uppercase">
              Outstanding wallet balances
            </p>
            <p class="font-display mt-1 text-2xl font-semibold">
              {{ dashboard.loading ? '…' : formatMoney(outstanding) }}
            </p>
            <p class="mt-0.5 text-sm text-slate-400">
              {{
                outstanding === 0
                  ? 'Nothing to settle — every wallet is already cleared.'
                  : 'moved to settlement records when the daily settlement runs'
              }}
            </p>
          </div>
          <BaseButton
            :disabled="dashboard.loading || outstanding === 0"
            :loading="settlements.running"
            @click="confirmOpen = true"
          >
            Run settlement
          </BaseButton>
        </div>
      </div>
    </div>

    <div class="sticky top-16 z-20 -mx-4 mb-4 flex flex-wrap items-center gap-3 border-b border-line bg-canvas px-4 py-3 sm:static sm:mx-0 sm:border-0 sm:px-0 sm:py-0">
      <!-- From/To are one control: they wrap as a unit and share a row
           on small screens instead of being split apart. -->
      <div class="flex basis-full items-center gap-2 sm:basis-auto">
        <label class="flex flex-1 flex-col gap-1 text-sm text-slate-500 sm:flex-none sm:flex-row sm:items-center sm:gap-1.5">
          From
          <input
            v-model="from"
            type="date"
            :max="to || undefined"
            class="rounded-lg border border-field bg-white px-2.5 py-2 w-full min-w-0 sm:w-auto text-sm text-ink hover:border-slate-300 focus:outline-2 focus:outline-offset-1 focus:outline-primary-600"
          />
        </label>
        <label class="flex flex-1 flex-col gap-1 text-sm text-slate-500 sm:flex-none sm:flex-row sm:items-center sm:gap-1.5">
          To
          <input
            v-model="to"
            type="date"
            :min="from || undefined"
            class="rounded-lg border border-field bg-white px-2.5 py-2 w-full min-w-0 sm:w-auto text-sm text-ink hover:border-slate-300 focus:outline-2 focus:outline-offset-1 focus:outline-primary-600"
          />
        </label>
      </div>
    </div>

    <DataTable
      :columns="columns"
      label="Settlements"
      :count="settlements.items.length"
      :loading="settlements.loading"
      :error="settlements.error"
      :empty="settlements.items.length === 0"
      @retry="load"
    >
      <tr
        v-for="settlement in settlements.items"
        :key="settlement.id"
        class="transition-colors hover:bg-slate-50/70"
      >
        <td class="font-mono px-4 py-3 text-xs text-slate-600">{{ settlement.reference }}</td>
        <td class="font-mono px-4 py-3 text-xs text-slate-500">{{ settlement.batch_ref }}</td>
        <td class="px-4 py-3">
          <RouterLink
            v-if="settlement.merchant"
            :to="`/merchants/${settlement.merchant.id}`"
            class="font-medium text-ink hover:text-primary-600 focus-visible:outline-2 focus-visible:outline-primary-600"
          >
            {{ settlement.merchant.business_name }}
          </RouterLink>
        </td>
        <td class="font-display px-4 py-3 font-semibold text-ink">
          {{ formatMoney(settlement.amount) }}
        </td>
        <td class="px-4 py-3 whitespace-nowrap text-slate-500">
          {{ formatDateTime(settlement.settled_at) }}
        </td>
      </tr>

      <template #cards>
        <li v-for="settlement in settlements.items" :key="settlement.id" class="px-4 py-3">
          <div class="flex items-start justify-between gap-3">
            <RouterLink
              v-if="settlement.merchant"
              :to="`/merchants/${settlement.merchant.id}`"
              class="min-w-0 flex-1 text-sm font-medium text-ink hover:text-primary-600 focus-visible:outline-2 focus-visible:outline-primary-600"
            >
              {{ settlement.merchant.business_name }}
            </RouterLink>
            <p class="font-display text-base font-semibold text-ink">
              {{ formatMoney(settlement.amount) }}
            </p>
          </div>

          <p class="font-mono mt-0.5 truncate text-xs text-slate-500">
            {{ settlement.reference }} · {{ settlement.batch_ref }}
          </p>
          <p class="mt-1 text-xs text-slate-500">{{ formatDateTime(settlement.settled_at) }}</p>
        </li>
      </template>

      <template #empty>
        <EmptyState
          title="No settlements found"
          :message="
            from || to
              ? 'No settlements in this date range. Widen the range to see earlier batches.'
              : 'Run the daily settlement to pay out merchant balances.'
          "
        />
      </template>
    </DataTable>

    <PaginationBar v-if="settlements.meta" :meta="settlements.meta" @page="page = $event" />

    <ConfirmDialog
      :open="confirmOpen"
      title="Run daily settlement"
      confirm-label="Settle balances"
      :loading="settlements.running"
      @close="confirmOpen = false"
      @confirm="runSettlement"
    >
      This moves <strong>{{ formatMoney(outstanding) }}</strong> in outstanding wallet balances to
      settlement records and resets each settled wallet to zero. Pending payments are not affected.
    </ConfirmDialog>
  </div>
</template>
