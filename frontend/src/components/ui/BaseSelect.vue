<script setup lang="ts">
import { useId } from 'vue'

defineProps<{
  label: string
  error?: string
  required?: boolean
}>()

const model = defineModel<string | number | null>({ default: '' })
const id = useId()
</script>

<template>
  <div>
    <label :for="id" class="mb-1.5 block text-sm font-medium text-ink">
      {{ label }}
      <span v-if="required" class="text-red-500" aria-hidden="true">*</span>
    </label>
    <select
      :id="id"
      v-model="model"
      :required="required"
      :aria-invalid="!!error"
      :aria-describedby="error ? `${id}-error` : undefined"
      :class="[
        'w-full appearance-none rounded-lg border bg-white px-3 py-2 text-sm text-ink transition-colors',
        'focus:outline-2 focus:outline-offset-1',
        error
          ? 'border-red-400 focus:outline-red-500'
          : 'border-line focus:outline-primary-600 hover:border-slate-300',
      ]"
    >
      <slot />
    </select>
    <p v-if="error" :id="`${id}-error`" class="mt-1.5 text-sm text-red-600" role="alert">
      {{ error }}
    </p>
  </div>
</template>
