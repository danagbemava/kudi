<script setup lang="ts">
import BaseButton from './BaseButton.vue'

withDefaults(
  defineProps<{
    /** Column headers; also drives skeleton column count. */
    columns: string[]
    loading?: boolean
    error?: string | null
    empty?: boolean
  }>(),
  { loading: false, error: null, empty: false },
)

const emit = defineEmits<{ retry: [] }>()
</script>

<template>
  <div class="overflow-hidden rounded-xl border border-line bg-white" :aria-busy="loading">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead>
          <tr class="border-b border-line bg-slate-50/80">
            <th
              v-for="column in columns"
              :key="column"
              scope="col"
              class="px-4 py-3 text-xs font-semibold tracking-wide text-slate-500 uppercase"
            >
              {{ column }}
            </th>
          </tr>
        </thead>

        <tbody v-if="loading">
          <tr v-for="row in 6" :key="row" class="border-b border-line/60 last:border-0">
            <td v-for="(_, index) in columns" :key="index" class="px-4 py-3.5">
              <div
                class="h-4 animate-pulse rounded bg-slate-200"
                :style="{ width: `${55 + ((row * 7 + index * 13) % 40)}%` }"
              />
            </td>
          </tr>
        </tbody>

        <tbody v-else-if="!error && !empty" class="divide-y divide-line/60">
          <slot />
        </tbody>
      </table>
    </div>

    <div v-if="!loading && error" class="px-4 py-12 text-center">
      <p class="text-sm text-slate-600">{{ error }}</p>
      <BaseButton variant="secondary" class="mt-4" @click="emit('retry')">Retry</BaseButton>
    </div>

    <div v-else-if="!loading && empty" class="px-4 py-12">
      <slot name="empty" />
    </div>
  </div>
</template>
