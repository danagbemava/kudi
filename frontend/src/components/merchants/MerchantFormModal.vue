<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { api } from '../../api'
import { ApiValidationError, toApiError } from '../../api/client'
import type { Merchant } from '../../api/types'
import { useToast } from '../../composables/useToast'
import { useMerchantsStore } from '../../stores/merchants'
import BaseButton from '../ui/BaseButton.vue'
import BaseInput from '../ui/BaseInput.vue'
import BaseModal from '../ui/BaseModal.vue'
import BaseSelect from '../ui/BaseSelect.vue'

const props = defineProps<{ open: boolean }>()
const emit = defineEmits<{ close: []; created: [merchant: Merchant] }>()

const merchants = useMerchantsStore()
const toast = useToast()

const form = ref({
  name: '',
  email: '',
  business_name: '',
  institution_id: null as number | null,
  account_number: '',
})
const errors = ref<Record<string, string | undefined>>({})
const submitting = ref(false)

watch(
  () => props.open,
  (open) => {
    if (open) {
      form.value = {
        name: '',
        email: '',
        business_name: '',
        institution_id: null,
        account_number: '',
      }
      errors.value = {}
      merchants
        .fetchInstitutions()
        .catch(() => toast.error('Could not load bank list. Reopen to retry.'))
    }
  },
)

const banks = computed(() => merchants.institutions.filter((i) => i.type === 'bank'))
const mobileMoney = computed(() => merchants.institutions.filter((i) => i.type === 'mobile_money'))

const selectedInstitution = computed(() =>
  merchants.institutions.find((i) => i.id === form.value.institution_id),
)

/**
 * The rule the server enforces, stated before it is broken rather than after.
 * Mirrored from App\Support\AccountNumber — the server stays the authority,
 * this just stops people finding out by being rejected.
 */
const accountHint = computed(() => {
  if (!selectedInstitution.value) return 'Choose an institution first.'
  return selectedInstitution.value.type === 'mobile_money'
    ? 'The 10-digit mobile money number, starting 0. For example 024 123 4567.'
    : 'Digits only, between 8 and 20 of them.'
})

const accountPlaceholder = computed(() =>
  selectedInstitution.value?.type === 'mobile_money' ? '024 123 4567' : '1033662013',
)

const accountLabel = computed(() =>
  selectedInstitution.value?.type === 'mobile_money' ? 'Mobile money number' : 'Account number',
)

async function submit(): Promise<void> {
  submitting.value = true
  errors.value = {}

  try {
    const merchant = await api.createMerchant(form.value)
    toast.success(`${merchant.business_name} registered.`)
    emit('created', merchant)
    emit('close')
  } catch (error) {
    const normalized = toApiError(error)
    if (normalized instanceof ApiValidationError) {
      errors.value = Object.fromEntries(
        Object.keys(form.value).map((key) => [key, normalized.first(key)]),
      )
    } else {
      toast.error(normalized.message)
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <BaseModal :open="open" title="Register merchant" @close="emit('close')">
    <form class="space-y-4" novalidate @submit.prevent="submit">
      <BaseInput
        v-model="form.business_name"
        label="Business name"
        placeholder="Makola Textiles"
        required
        :error="errors.business_name"
      />
      <div class="grid gap-4 sm:grid-cols-2">
        <BaseInput
          v-model="form.name"
          label="Contact name"
          placeholder="Kwame Mensah"
          required
          :error="errors.name"
        />
        <BaseInput
          v-model="form.email"
          label="Email"
          type="email"
          placeholder="kwame@makola.gh"
          required
          :error="errors.email"
        />
      </div>
      <BaseSelect
        v-model="form.institution_id"
        label="Settlement institution"
        required
        :error="errors.institution_id"
      >
        <option :value="null" disabled>Choose a bank or mobile money provider…</option>
        <optgroup label="Banks">
          <option v-for="bank in banks" :key="bank.id" :value="bank.id">{{ bank.name }}</option>
        </optgroup>
        <optgroup label="Mobile money">
          <option v-for="provider in mobileMoney" :key="provider.id" :value="provider.id">
            {{ provider.name }}
          </option>
        </optgroup>
      </BaseSelect>
      <BaseInput
        v-model="form.account_number"
        :label="accountLabel"
        :placeholder="accountPlaceholder"
        inputmode="numeric"
        autocomplete="off"
        required
        :error="errors.account_number"
        :hint="accountHint"
      />

      <div class="flex justify-end gap-2 pt-1">
        <BaseButton variant="secondary" :disabled="submitting" @click="emit('close')">
          Cancel
        </BaseButton>
        <BaseButton type="submit" :loading="submitting">Register merchant</BaseButton>
      </div>
    </form>
  </BaseModal>
</template>
