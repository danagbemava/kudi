<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { ApiValidationError, toApiError } from '../../api/client'
import type { Transaction } from '../../api/types'
import { useToast } from '../../composables/useToast'
import { useMerchantsStore } from '../../stores/merchants'
import { useTransactionsStore } from '../../stores/transactions'
import { feeFor, formatMoney, parseAmount } from '../../utils/money'
import BaseButton from '../ui/BaseButton.vue'
import BaseInput from '../ui/BaseInput.vue'
import BaseModal from '../ui/BaseModal.vue'
import BaseSelect from '../ui/BaseSelect.vue'
import StatusBadge from '../ui/StatusBadge.vue'

const props = defineProps<{ open: boolean }>()
const emit = defineEmits<{ close: []; completed: [] }>()

const merchants = useMerchantsStore()
const transactions = useTransactionsStore()
const toast = useToast()

const merchantId = ref<number | null>(null)
const amountInput = ref('')
const reference = ref('')
const errors = ref<Record<string, string | undefined>>({})

/** idle → submitting (gateway wait) → done */
const phase = ref<'idle' | 'submitting' | 'done'>('idle')
const result = ref<Transaction | null>(null)

watch(
  () => props.open,
  (open) => {
    if (open) {
      merchantId.value = null
      amountInput.value = ''
      reference.value = generateReference()
      errors.value = {}
      phase.value = 'idle'
      result.value = null
      merchants.fetch({ status: 'active' }).catch(() => {})
    }
  },
)

function generateReference(): string {
  const random = Array.from({ length: 8 }, () =>
    'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'.charAt(Math.floor(Math.random() * 32)),
  ).join('')
  return `PAY-${random}`
}

const activeMerchants = computed(() => merchants.items.filter((m) => m.status === 'active'))

const amountMinor = computed(() => parseAmount(amountInput.value))
const fee = computed(() => (amountMinor.value !== null ? feeFor(amountMinor.value) : null))
const net = computed(() =>
  amountMinor.value !== null && fee.value !== null ? amountMinor.value - fee.value : null,
)

const amountError = computed(() => {
  if (amountInput.value === '' || amountMinor.value !== null) return errors.value.amount
  return 'Enter an amount like 150 or 150.75'
})

async function submit(): Promise<void> {
  errors.value = {}

  if (amountMinor.value === null) {
    errors.value.amount = 'Enter an amount like 150 or 150.75'
    return
  }

  phase.value = 'submitting'

  try {
    result.value = await transactions.simulatePayment({
      merchant_id: merchantId.value,
      amount: amountMinor.value,
      reference: reference.value,
    })
    phase.value = 'done'
    emit('completed')

    if (result.value.status === 'successful') {
      toast.success(`Payment settled to wallet — ${formatMoney(result.value.net_amount)} credited.`)
    } else {
      toast.error('The gateway declined this payment. No wallet credit was made.')
    }
  } catch (error) {
    phase.value = 'idle'
    const normalized = toApiError(error)
    if (normalized instanceof ApiValidationError) {
      errors.value = {
        merchant_id: normalized.first('merchant_id'),
        amount: normalized.first('amount'),
        reference: normalized.first('reference'),
      }
    } else {
      toast.error(normalized.message)
    }
  }
}
</script>

<template>
  <BaseModal :open="open" title="Simulate customer payment" @close="phase !== 'submitting' && emit('close')">
    <!-- Result view -->
    <div v-if="phase === 'done' && result" class="text-center">
      <StatusBadge :status="result.status" class="mx-auto" />
      <p class="font-display mt-3 text-2xl font-semibold text-ink">
        {{ formatMoney(result.gross_amount) }}
      </p>
      <p class="font-mono mt-1 text-xs text-slate-500">{{ result.reference }}</p>

      <dl class="mt-4 space-y-1.5 rounded-lg bg-slate-50 p-4 text-sm">
        <div class="flex justify-between">
          <dt class="text-slate-500">Gross amount</dt>
          <dd class="font-medium text-ink">{{ formatMoney(result.gross_amount) }}</dd>
        </div>
        <div class="flex justify-between">
          <dt class="text-slate-500">Processing fee (1.5%)</dt>
          <dd class="font-medium text-ink">− {{ formatMoney(result.fee_amount) }}</dd>
        </div>
        <div class="flex justify-between border-t border-line pt-1.5">
          <dt class="font-medium text-ink">
            {{ result.status === 'successful' ? 'Credited to wallet' : 'Would have credited' }}
          </dt>
          <dd class="font-display font-semibold" :class="result.status === 'successful' ? 'text-green-600' : 'text-slate-400 line-through'">
            {{ formatMoney(result.net_amount) }}
          </dd>
        </div>
      </dl>

      <div class="mt-5 flex justify-center gap-2">
        <BaseButton variant="secondary" @click="emit('close')">Done</BaseButton>
        <BaseButton @click="phase = 'idle'; amountInput = ''; reference = generateReference()">
          Simulate another
        </BaseButton>
      </div>
    </div>

    <!-- Form view -->
    <form v-else class="space-y-4" novalidate @submit.prevent="submit">
      <BaseSelect
        v-model="merchantId"
        label="Merchant"
        required
        :error="errors.merchant_id"
      >
        <option :value="null" disabled>Choose an active merchant…</option>
        <option v-for="merchant in activeMerchants" :key="merchant.id" :value="merchant.id">
          {{ merchant.business_name }}
        </option>
      </BaseSelect>

      <BaseInput
        v-model="amountInput"
        label="Amount"
        prefix="GH₵"
        placeholder="150.00"
        required
        :error="amountError"
      />

      <BaseInput
        v-model="reference"
        label="Payment reference"
        required
        :error="errors.reference"
        hint="Prefilled with a unique reference — edit if you need a specific one."
      />

      <!-- Live fee preview -->
      <dl
        class="space-y-1.5 rounded-lg bg-slate-50 p-4 text-sm"
        aria-live="polite"
      >
        <div class="flex justify-between">
          <dt class="text-slate-500">Gross amount</dt>
          <dd class="font-medium text-ink">
            {{ amountMinor !== null ? formatMoney(amountMinor) : '—' }}
          </dd>
        </div>
        <div class="flex justify-between">
          <dt class="text-slate-500">Processing fee (1.5%)</dt>
          <dd class="font-medium text-ink">{{ fee !== null ? `− ${formatMoney(fee)}` : '—' }}</dd>
        </div>
        <div class="flex justify-between border-t border-line pt-1.5">
          <dt class="font-medium text-ink">Net wallet credit</dt>
          <dd class="font-display font-semibold text-primary-600">
            {{ net !== null ? formatMoney(net) : '—' }}
          </dd>
        </div>
      </dl>

      <div class="flex items-center justify-end gap-2 pt-1">
        <p v-if="phase === 'submitting'" class="mr-auto flex items-center gap-2 text-sm text-slate-500">
          <span class="size-1.5 animate-pulse rounded-full bg-amber-500" aria-hidden="true" />
          Waiting for gateway…
        </p>
        <BaseButton variant="secondary" :disabled="phase === 'submitting'" @click="emit('close')">
          Cancel
        </BaseButton>
        <BaseButton type="submit" :loading="phase === 'submitting'">Send payment</BaseButton>
      </div>
    </form>
  </BaseModal>
</template>
