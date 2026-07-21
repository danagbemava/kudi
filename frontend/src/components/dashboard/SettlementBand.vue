<script setup lang="ts">
import { computed } from 'vue'
import { formatMoney } from '../../utils/money'

export interface FloatHolder {
  id: number
  business_name: string
  wallet_balance: number
}

const props = defineProps<{
  merchants: FloatHolder[]
  total: number
}>()

/**
 * Kente is woven as narrow strips of banded cloth, sewn edge to edge — the
 * width of each strip is structural, not ornamental. Here each strip is one
 * merchant's share of the money waiting to be settled, so the weave is the
 * data: you can see at a glance whose float the pool is made of, which the
 * total alone never tells you.
 */
const WEAVE = ['bg-gold', 'bg-forest', 'bg-clay', 'bg-ink-soft', 'bg-gold-deep']

const strips = computed(() => {
  if (props.total <= 0) return []

  return props.merchants
    .filter((m) => m.wallet_balance > 0)
    .sort((a, b) => b.wallet_balance - a.wallet_balance)
    .map((m, index) => ({
      id: m.id,
      name: m.business_name,
      amount: m.wallet_balance,
      share: (m.wallet_balance / props.total) * 100,
      colour: WEAVE[index % WEAVE.length]!,
    }))
})

const leaders = computed(() => strips.value.slice(0, 3))
</script>

<template>
  <div v-if="strips.length > 0" class="mt-5">
    <div
      class="flex h-8 gap-px overflow-hidden rounded-md"
      role="img"
      :aria-label="`Unsettled float across ${strips.length} merchants. Largest: ${leaders
        .map((s) => `${s.name} ${Math.round(s.share)} percent`)
        .join(', ')}.`"
    >
      <div
        v-for="strip in strips"
        :key="strip.id"
        :class="[strip.colour, 'group relative transition-opacity hover:opacity-80']"
        :style="{ width: `${strip.share}%` }"
      >
        <span
          class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1.5 hidden -translate-x-1/2 rounded-md bg-ink px-2 py-1 text-xs whitespace-nowrap text-white group-hover:block"
        >
          {{ strip.name }} · {{ formatMoney(strip.amount) }}
        </span>
      </div>
    </div>

    <p class="mt-2 text-xs text-zinc-300">
      <template v-for="(strip, index) in leaders" :key="strip.id">
        <span v-if="index > 0" aria-hidden="true"> · </span>
        {{ strip.name }} {{ Math.round(strip.share) }}%
      </template>
      <span v-if="strips.length > leaders.length" class="text-zinc-400">
        · +{{ strips.length - leaders.length }} more
      </span>
    </p>
  </div>
</template>
