<script setup lang="ts">
import { useId } from 'vue'

withDefaults(
  defineProps<{
    label: string
    type?: string
    placeholder?: string
    error?: string
    hint?: string
    required?: boolean
    prefix?: string
  }>(),
  { type: 'text' },
)

const model = defineModel<string>({ default: '' })
const id = useId()
</script>

<template>
  <div>
    <label :for="id" class="mb-1.5 block text-sm font-medium text-ink">
      {{ label }}
      <span v-if="required" class="text-red-600" aria-hidden="true">*</span>
    </label>
    <div class="relative">
      <span
        v-if="prefix"
        class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-slate-500"
      >
        {{ prefix }}
      </span>
      <input
        :id="id"
        v-model="model"
        :type="type"
        :placeholder="placeholder"
        :required="required"
        :aria-invalid="!!error"
        :aria-describedby="error ? `${id}-error` : hint ? `${id}-hint` : undefined"
        :class="[
          'w-full rounded-lg border bg-white px-3 py-2 text-sm text-ink placeholder:text-slate-500',
          'focus:outline-2 focus:outline-offset-1 transition-colors',
          prefix ? 'pl-12' : '',
          error
            ? 'border-red-400 focus:outline-red-500'
            : 'border-field focus:outline-primary-600 hover:border-slate-500',
        ]"
      />
    </div>
    <p v-if="error" :id="`${id}-error`" class="mt-1.5 text-sm text-red-600" role="alert">
      {{ error }}
    </p>
    <p v-else-if="hint" :id="`${id}-hint`" class="mt-1.5 text-sm text-slate-500">{{ hint }}</p>
  </div>
</template>
