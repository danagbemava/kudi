<script setup lang="ts">
import { computed } from 'vue'
import BaseModal from '../ui/BaseModal.vue'
import StatusBadge from '../ui/StatusBadge.vue'
import type { Transaction } from '../../api/types'
import { formatDateTime } from '../../utils/dates'
import { formatMoney } from '../../utils/money'

const props = defineProps<{ transaction: Transaction }>()
defineEmits<{ close: [] }>()

/**
 * The fee is stored, not derived, so a historic transaction keeps the rate it
 * was charged at even if the platform rate changes. Showing the effective
 * percentage lets an operator confirm what was actually applied to this one.
 */
const effectiveRate = computed(() => {
  if (props.transaction.gross_amount === 0) return null
  return ((props.transaction.fee_amount / props.transaction.gross_amount) * 100).toFixed(2)
})

// What the status means for the money, rather than restating the status word.
const meaning = computed(() => {
  switch (props.transaction.status) {
    case 'successful':
      return 'The net amount was credited to the merchant wallet and will be paid out on the next settlement run.'
    case 'pending':
      return 'Awaiting a result from the gateway. Nothing has reached the merchant wallet yet.'
    case 'failed':
      return 'The gateway declined this payment. No money moved and no fee was taken.'
    default:
      return ''
  }
})
</script>

<template>
  <BaseModal open :title="transaction.reference" @close="$emit('close')">
    <div class="space-y-5">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <StatusBadge :status="transaction.status" />
        <p class="font-mono text-xs text-muted">#{{ transaction.id }}</p>
      </div>

      <p class="text-sm text-ink-soft">{{ meaning }}</p>

      <!-- The money, in the order it is applied: what the customer paid, what
           the platform took, what the merchant is owed. -->
      <dl class="panel divide-y divide-line-soft">
        <div class="flex items-baseline justify-between gap-4 px-4 py-3">
          <dt class="text-sm text-muted">Gross amount</dt>
          <dd class="font-display text-sm font-semibold text-ink">
            {{ formatMoney(transaction.gross_amount) }}
          </dd>
        </div>
        <div class="flex items-baseline justify-between gap-4 px-4 py-3">
          <dt class="text-sm text-muted">
            Processing fee
            <span v-if="effectiveRate" class="text-muted">({{ effectiveRate }}%)</span>
          </dt>
          <dd class="font-display text-sm font-semibold text-ink">
            &minus;{{ formatMoney(transaction.fee_amount) }}
          </dd>
        </div>
        <div class="flex items-baseline justify-between gap-4 bg-canvas px-4 py-3">
          <dt class="text-sm font-medium text-ink">Net to merchant</dt>
          <dd class="font-display text-base font-semibold text-accent">
            {{ formatMoney(transaction.net_amount) }}
          </dd>
        </div>
      </dl>

      <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
        <div v-if="transaction.merchant">
          <dt class="text-xs font-medium tracking-wide text-muted uppercase">Merchant</dt>
          <dd class="mt-1 text-sm text-ink">
            <RouterLink
              :to="`/merchants/${transaction.merchant.id}`"
              class="font-medium hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"
            >
              {{ transaction.merchant.business_name }}
            </RouterLink>
            <span class="block text-xs text-muted">{{ transaction.merchant.email }}</span>
          </dd>
        </div>
        <div v-if="transaction.merchant?.institution">
          <dt class="text-xs font-medium tracking-wide text-muted uppercase">Payout account</dt>
          <dd class="mt-1 text-sm text-ink">
            {{ transaction.merchant.institution.name }}
            <span class="block font-mono text-xs text-muted">
              {{ transaction.merchant.account_number }}
            </span>
          </dd>
        </div>
        <div>
          <dt class="text-xs font-medium tracking-wide text-muted uppercase">Created</dt>
          <dd class="mt-1 text-sm text-ink">{{ formatDateTime(transaction.created_at) }}</dd>
        </div>
        <div>
          <dt class="text-xs font-medium tracking-wide text-muted uppercase">Resolved</dt>
          <dd class="mt-1 text-sm text-ink">
            <template v-if="transaction.processed_at">
              {{ formatDateTime(transaction.processed_at) }}
            </template>
            <span v-else class="text-muted">Not yet resolved</span>
          </dd>
        </div>
      </dl>
    </div>
  </BaseModal>
</template>
