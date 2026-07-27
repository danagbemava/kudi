<script setup lang="ts">
import { useId } from 'vue'

withDefaults(
  defineProps<{
    label: string
    type?: string
    placeholder?: string
    error?: string
    hint?: string
    /** Surfaces the numeric keypad on touch devices for digit-only fields. */
    inputmode?: 'numeric' | 'decimal' | 'tel' | 'email'
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
      <span v-if="required" class="text-bad" aria-hidden="true">*</span>
    </label>
    <div class="relative">
      <span
        v-if="prefix"
        class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-muted"
      >
        {{ prefix }}
      </span>
      <input
        :id="id"
        v-model="model"
        :type="type"
        :inputmode="inputmode"
        :placeholder="placeholder"
        :required="required"
        :aria-invalid="!!error"
        :aria-describedby="error ? `${id}-error` : hint ? `${id}-hint` : undefined"
        :class="[
          'w-full rounded-lg border bg-surface px-3 py-2 text-sm text-ink placeholder:text-muted',
          'transition-colors focus:outline-2 focus:outline-offset-1',
          prefix ? 'pl-12' : '',
          error
            ? 'border-bad focus:outline-bad'
            : 'border-field hover:border-field focus:outline-accent',
        ]"
      />
    </div>
    <p v-if="error" :id="`${id}-error`" class="mt-1.5 text-sm text-bad" role="alert">
      {{ error }}
    </p>
    <p v-else-if="hint" :id="`${id}-hint`" class="mt-1.5 text-sm text-muted">{{ hint }}</p>
  </div>
</template>
