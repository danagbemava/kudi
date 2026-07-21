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
      <span v-if="required" class="text-clay" aria-hidden="true">*</span>
    </label>
    <div class="relative">
      <select
        :id="id"
        v-model="model"
        :required="required"
        :aria-invalid="!!error"
        :aria-describedby="error ? `${id}-error` : undefined"
        :class="[
          'w-full appearance-none rounded-lg border bg-white py-2 pr-9 pl-3 text-sm text-ink transition-colors',
          'focus:outline-2 focus:outline-offset-1',
          error
            ? 'border-clay focus:outline-clay'
            : 'border-field focus:outline-forest hover:border-zinc-500',
        ]"
      >
        <slot />
      </select>
      <!-- appearance-none removes the native arrow, so draw the affordance back. -->
      <svg
        class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-zinc-500"
        viewBox="0 0 20 20"
        fill="currentColor"
        aria-hidden="true"
      >
        <path
          fill-rule="evenodd"
          d="M5.22 8.22a.75.75 0 011.06 0L10 11.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 9.28a.75.75 0 010-1.06z"
          clip-rule="evenodd"
        />
      </svg>
    </div>
    <p v-if="error" :id="`${id}-error`" class="mt-1.5 text-sm text-clay" role="alert">
      {{ error }}
    </p>
  </div>
</template>
