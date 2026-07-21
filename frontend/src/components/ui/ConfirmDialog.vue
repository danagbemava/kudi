<script setup lang="ts">
import BaseButton from './BaseButton.vue'
import BaseModal from './BaseModal.vue'

withDefaults(
  defineProps<{
    open: boolean
    title: string
    confirmLabel: string
    tone?: 'primary' | 'danger'
    loading?: boolean
  }>(),
  { tone: 'primary', loading: false },
)

const emit = defineEmits<{ close: []; confirm: [] }>()
</script>

<template>
  <BaseModal :open="open" :title="title" @close="emit('close')">
    <div class="text-sm text-zinc-600">
      <slot />
    </div>
    <div class="mt-5 flex justify-end gap-2">
      <BaseButton variant="secondary" :disabled="loading" @click="emit('close')">
        Cancel
      </BaseButton>
      <BaseButton
        :variant="tone === 'danger' ? 'danger' : 'primary'"
        :loading="loading"
        @click="emit('confirm')"
      >
        {{ confirmLabel }}
      </BaseButton>
    </div>
  </BaseModal>
</template>
