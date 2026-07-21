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

/**
 * How many merchants get their own strip before the tail is grouped. Past
 * this the strips are a few pixels wide — impossible to aim at, and each one
 * would add a tab stop to the dashboard for no gain.
 */
const NAMED_LIMIT = 8

const strips = computed(() => {
  if (props.total <= 0) return []

  const held = props.merchants
    .filter((m) => m.wallet_balance > 0)
    .sort((a, b) => b.wallet_balance - a.wallet_balance)

  const named = held.slice(0, NAMED_LIMIT)
  const rest = held.slice(NAMED_LIMIT)

  const entries = named.map((m) => ({
    key: `m${m.id}`,
    to: `/merchants/${m.id}`,
    name: m.business_name,
    amount: m.wallet_balance,
  }))

  if (rest.length > 0) {
    entries.push({
      key: 'rest',
      to: '/merchants',
      name: `${rest.length} other merchants`,
      amount: rest.reduce((sum, m) => sum + m.wallet_balance, 0),
    })
  }

  return entries.map((entry, index) => ({
    ...entry,
    share: (entry.amount / props.total) * 100,
    colour: WEAVE[index % WEAVE.length]!,
    // The card clips its overflow, so tooltips on the end strips anchor to
    // that edge rather than centring, where they would be cut off.
    anchor:
      index === 0
        ? 'sm:right-auto sm:left-0 sm:translate-x-0'
        : index === entries.length - 1
          ? 'sm:right-0 sm:left-auto sm:translate-x-0'
          : 'sm:right-auto sm:left-1/2 sm:-translate-x-1/2',
  }))
})

const leaders = computed(() => strips.value.slice(0, 3))

function label(strip: { name: string; amount: number; share: number }): string {
  return `${strip.name}, ${formatMoney(strip.amount)}, ${Math.round(strip.share)}% of the float`
}
</script>

<template>
  <div v-if="strips.length > 0" class="mt-5">
    <div class="relative flex h-8 gap-px overflow-visible rounded-md">
      <RouterLink
        v-for="strip in strips"
        :key="strip.key"
        :to="strip.to"
        :aria-label="label(strip)"
        :class="[
          strip.colour,
          'group transition-opacity hover:opacity-85 sm:relative',
          'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white',
          'first:rounded-l-md last:rounded-r-md',
        ]"
        :style="{ width: `${strip.share}%` }"
      >
        <!-- Shown on focus as well as hover, so the band is inspectable
             without a pointer. -->
        <span
          :class="[
            'pointer-events-none absolute right-0 bottom-full left-0 z-10 mb-2 hidden',
            'rounded-md bg-ink px-2.5 py-1.5 text-center text-xs text-white shadow-lg ring-1 ring-white/15',
            'sm:w-max sm:text-left sm:whitespace-nowrap',
            'group-hover:block group-focus-visible:block',
            strip.anchor,
          ]"
          role="tooltip"
        >
          <span class="font-medium">{{ strip.name }}</span>
          <span class="text-zinc-300"> · {{ formatMoney(strip.amount) }}</span>
          <span class="text-zinc-400"> · {{ Math.round(strip.share) }}%</span>
        </span>
      </RouterLink>
    </div>

    <!-- The named leaders double as the touch-friendly reading of the band,
         where there is no hover and the strips are only a few pixels wide. -->
    <p class="mt-2 text-xs text-zinc-300">
      <template v-for="(strip, index) in leaders" :key="strip.key">
        <span v-if="index > 0" aria-hidden="true"> · </span>
        {{ strip.name }} {{ Math.round(strip.share) }}%
      </template>
      <span v-if="strips.length > leaders.length" class="text-zinc-400">
        · +{{ strips.length - leaders.length }} more
      </span>
    </p>
  </div>
</template>
