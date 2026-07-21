<script setup lang="ts">
import { computed } from 'vue'
import { ref } from 'vue'
import { toApiError } from '../../api/client'
import { useToast } from '../../composables/useToast'
import { useDashboardStore } from '../../stores/dashboard'
import { useSettlementsStore } from '../../stores/settlements'
import { formatMoney } from '../../utils/money'
import BaseButton from '../ui/BaseButton.vue'
import ConfirmDialog from '../ui/ConfirmDialog.vue'

/**
 * The settlement run, with its confirmation and safeguards, as one component —
 * so wherever the action is offered it behaves identically. Previously the
 * dashboard showed a button labelled "Run settlement" that only navigated
 * here, and stayed enabled with nothing to settle.
 */
const emit = defineEmits<{ settled: [] }>()

const dashboard = useDashboardStore()
const settlements = useSettlementsStore()
const toast = useToast()

const confirmOpen = ref(false)

const outstanding = computed(() => dashboard.data?.totals.wallet_balance ?? 0)
const holders = computed(() => dashboard.data?.unsettled.length ?? 0)
const nothingToSettle = computed(() => !dashboard.loading && outstanding.value === 0)

async function run(): Promise<void> {
  try {
    const result = await settlements.run()
    confirmOpen.value = false
    toast.success(
      `Settled ${formatMoney(result.total_amount)} to ${result.merchant_count} ` +
        `${result.merchant_count === 1 ? 'merchant' : 'merchants'} (${result.batch_ref}).`,
    )
    dashboard.fetch()
    emit('settled')
  } catch (error) {
    confirmOpen.value = false
    toast.error(toApiError(error).message)
  }
}
</script>

<template>
  <BaseButton
    :disabled="dashboard.loading || nothingToSettle"
    :loading="settlements.running"
    @click="confirmOpen = true"
  >
    Run settlement
  </BaseButton>

  <ConfirmDialog
    :open="confirmOpen"
    title="Run daily settlement"
    confirm-label="Settle balances"
    :loading="settlements.running"
    @close="confirmOpen = false"
    @confirm="run"
  >
    This moves <strong>{{ formatMoney(outstanding) }}</strong> from {{ holders }}
    {{ holders === 1 ? 'merchant wallet' : 'merchant wallets' }} into settlement records and clears
    those balances. Pending payments are not affected.
  </ConfirmDialog>
</template>
