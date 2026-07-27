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
      <span v-if="required" class="text-bad" aria-hidden="true">*</span>
    </label>
    <!-- The chevron comes from .select-field, shared with every other dropdown,
         so this no longer needs a positioned wrapper of its own. -->
    <select
      :id="id"
      v-model="model"
      :required="required"
      :aria-invalid="!!error"
      :aria-describedby="error ? `${id}-error` : undefined"
      :class="[
        'select-field w-full rounded-lg border bg-surface py-2 pl-3 text-sm text-ink transition-colors',
        'focus:outline-2 focus:outline-offset-1',
        error
          ? 'border-bad focus:outline-bad'
          : 'border-field hover:border-field focus:outline-accent',
      ]"
    >
      <slot />
    </select>
    <p v-if="error" :id="`${id}-error`" class="mt-1.5 text-sm text-bad" role="alert">
      {{ error }}
    </p>
  </div>
</template>
