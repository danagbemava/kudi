<script setup lang="ts">
import { computed } from 'vue'
import { formatMoney } from '../../utils/money'

import type { FloatHolder } from '../../api/types'

const props = withDefaults(
  defineProps<{
    merchants: FloatHolder[]
    total: number
    /**
     * Holders the server did not name, because it caps how many it returns.
     * Folded into the tail band so the strips still divide the whole float —
     * without this they quietly stop summing to 100% past the cap.
     */
    remainderCount?: number
    remainderAmount?: number
  }>(),
  { remainderCount: 0, remainderAmount: 0 },
)

/**
 * Each strip is one merchant's share of the money waiting to settle, so the
 * band is data rather than ornament: you can see at a glance whose float the
 * pool is made of, which the total alone never tells you.
 *
 * The strips run one interpolated violet ramp rather than a set of distinct
 * hues. Separate hues imply separate categories, and these are all the same
 * quantity; a ramp also can't wrap back round to its darkest end when a long
 * merchant list overruns the palette.
 */
const RAMP_FROM = [18, 1, 61]
const RAMP_TO = [176, 169, 205]

function stripColour(index: number, count: number): string {
  const t = count > 1 ? index / (count - 1) : 0
  const channels = RAMP_FROM.map((from, i) => Math.round(from + (RAMP_TO[i]! - from) * t))
  return `rgb(${channels.join(' ')})`
}

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

  // The tail is everything this component chose not to name, plus everything
  // the server had already left out of the list it sent.
  const tailCount = rest.length + props.remainderCount
  const tailAmount = rest.reduce((sum, m) => sum + m.wallet_balance, 0) + props.remainderAmount

  if (tailCount > 0) {
    entries.push({
      key: 'rest',
      to: '/merchants',
      name: `${tailCount} other merchant${tailCount === 1 ? '' : 's'}`,
      amount: tailAmount,
    })
  }

  return entries.map((entry, index) => ({
    ...entry,
    share: (entry.amount / props.total) * 100,
    colour: stripColour(index, entries.length),
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
    <div class="relative flex h-6 gap-px overflow-visible rounded-md">
      <RouterLink
        v-for="strip in strips"
        :key="strip.key"
        :to="strip.to"
        :aria-label="label(strip)"
        :class="[
          'group transition-opacity hover:opacity-85 sm:relative',
          'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent',
          'first:rounded-l-md last:rounded-r-md',
        ]"
        :style="{ width: `${strip.share}%`, background: strip.colour }"
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
          <!-- The tooltip sits on ink, so its secondary text lightens rather
               than using the muted token, which is tuned for white. -->
          <span class="text-white/70"> · {{ formatMoney(strip.amount) }}</span>
          <span class="text-white/70"> · {{ Math.round(strip.share) }}%</span>
        </span>
      </RouterLink>
    </div>

    <!-- The named leaders double as the touch-friendly reading of the band,
         where there is no hover and the strips are only a few pixels wide. -->
    <p class="mt-2 text-xs text-muted">
      <template v-for="(strip, index) in leaders" :key="strip.key">
        <span v-if="index > 0" aria-hidden="true"> · </span>
        {{ strip.name }} {{ Math.round(strip.share) }}%
      </template>
      <span v-if="strips.length > leaders.length" class="text-muted">
        · +{{ strips.length - leaders.length }} more
      </span>
    </p>
  </div>
</template>
