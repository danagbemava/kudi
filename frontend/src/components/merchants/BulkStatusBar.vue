<script setup lang="ts">
import { ref } from 'vue'
import { api } from '../../api'
import { toApiError } from '../../api/client'
import type { MerchantStatus } from '../../api/types'
import { useToast } from '../../composables/useToast'
import BaseButton from '../ui/BaseButton.vue'
import ConfirmDialog from '../ui/ConfirmDialog.vue'

const props = defineProps<{ selected: number[] }>()
const emit = defineEmits<{ done: []; clear: [] }>()

const toast = useToast()
const pending = ref<MerchantStatus | null>(null)
const working = ref(false)

async function apply(): Promise<void> {
  if (!pending.value) return
  working.value = true

  try {
    const result = await api.setMerchantStatuses(props.selected, pending.value)
    const verb = pending.value === 'active' ? 'activated' : 'deactivated'

    // Reports what actually changed: selecting merchants that already hold the
    // target status is common, and claiming otherwise would be untrue.
    toast.success(
      result.updated === 0
        ? `No changes — every merchant selected was already ${pending.value}.`
        : `${result.updated} ${result.updated === 1 ? 'merchant' : 'merchants'} ${verb}.`,
    )
    emit('done')
  } catch (error) {
    toast.error(toApiError(error).message)
  } finally {
    working.value = false
    pending.value = null
  }
}
</script>

<template>
  <div
    v-if="selected.length > 0"
    class="mb-4 flex flex-wrap items-center gap-3 rounded-xl border border-good/25 bg-good-wash px-4 py-3"
    role="status"
  >
    <p class="text-sm font-medium text-ink">
      {{ selected.length }} {{ selected.length === 1 ? 'merchant' : 'merchants' }} selected
    </p>

    <div class="ml-auto flex items-center gap-2">
      <BaseButton variant="secondary" @click="pending = 'active'">Activate</BaseButton>
      <BaseButton variant="danger" @click="pending = 'inactive'">Deactivate</BaseButton>
      <BaseButton variant="ghost" @click="emit('clear')">Clear</BaseButton>
    </div>

    <ConfirmDialog
      :open="pending !== null"
      :title="pending === 'active' ? 'Activate merchants' : 'Deactivate merchants'"
      :confirm-label="pending === 'active' ? 'Activate' : 'Deactivate'"
      :tone="pending === 'active' ? 'primary' : 'danger'"
      :loading="working"
      @close="pending = null"
      @confirm="apply"
    >
      <template v-if="pending === 'inactive'">
        <strong>{{ selected.length }}</strong>
        {{ selected.length === 1 ? 'merchant' : 'merchants' }} will stop accepting new payments.
        Balances they already hold are kept and will still be settled.
      </template>
      <template v-else>
        <strong>{{ selected.length }}</strong>
        {{ selected.length === 1 ? 'merchant' : 'merchants' }} will be able to accept payments
        again.
      </template>
    </ConfirmDialog>
  </div>
</template>
