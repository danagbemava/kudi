<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { api } from '../api'
import { toApiError } from '../api/client'
import type { ReportSummary } from '../api/types'
import VolumeChart from '../components/charts/VolumeChart.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import StatCard from '../components/ui/StatCard.vue'
import { formatDate } from '../utils/dates'
import { feePercent, formatMoney } from '../utils/money'

const summary = ref<ReportSummary | null>(null)
const loading = ref(false)
const error = ref<string | null>(null)

const from = ref('')
const to = ref('')

async function load(): Promise<void> {
  loading.value = true
  error.value = null
  try {
    summary.value = await api.reportSummary({ from: from.value, to: to.value })
  } catch (e) {
    error.value = toApiError(e).message
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch([from, to], load)

function setRange(days: number | null): void {
  if (days === null) {
    from.value = ''
    to.value = ''
    return
  }

  const now = new Date()
  const start = new Date(now)
  // Inclusive of both ends, so "Last 7 days" spans 7 days counting today —
  // subtracting a full 7 would return 8 points.
  start.setDate(start.getDate() - (days - 1))
  from.value = toLocalDate(start)
  to.value = toLocalDate(now)
}

/**
 * A calendar date in the viewer's own timezone.
 *
 * toISOString() converts to UTC first, so west of Greenwich an evening "today"
 * becomes tomorrow's date and the whole range slides a day. Ghana is UTC+0 and
 * would never have noticed, which is exactly why it is worth not relying on.
 */
function toLocalDate(date: Date): string {
  const month = `${date.getMonth() + 1}`.padStart(2, '0')
  const day = `${date.getDate()}`.padStart(2, '0')

  return `${date.getFullYear()}-${month}-${day}`
}

/** Plain-language statement of the span the chart is drawing. */
const chartPeriod = computed(() => {
  const window = summary.value?.series_window
  if (!window) return ''

  return `${formatDate(window.from)} – ${formatDate(window.to)}`
})

const counts = computed(() => summary.value?.transaction_counts)
const totalCount = computed(() =>
  counts.value ? counts.value.successful + counts.value.pending + counts.value.failed : 0,
)

function share(status: 'successful' | 'pending' | 'failed'): number {
  if (!counts.value || totalCount.value === 0) return 0
  return Math.round((counts.value[status] / totalCount.value) * 100)
}

// The bar and the legend read from one list, so a segment can never end up
// with a different colour or share than the entry describing it.
const segments = computed(() =>
  (
    [
      { label: 'Successful', key: 'successful', fill: 'bg-good' },
      { label: 'Pending', key: 'pending', fill: 'bg-warn' },
      { label: 'Failed', key: 'failed', fill: 'bg-bad' },
    ] as const
  ).map((segment) => ({
    ...segment,
    count: counts.value?.[segment.key] ?? 0,
    share: share(segment.key),
  })),
)

/**
 * The bar keeps the legend's order — successful, pending, failed.
 *
 * It used to sort by magnitude, on the reasoning that bar and legend could link
 * by colour instead of by position. But the three status tones are all dark
 * (each was picked to carry text on white) and sit around 1.2:1 from each
 * other, which is the reason the segments need a structural gap in the first
 * place. Asking the reader to match segment to legend entry by colour, using
 * colours documented as nearly indistinguishable, put the whole weight of the
 * reading on the one channel that cannot carry it. Shared order means position
 * does the work and colour only confirms it.
 *
 * Only outcomes that actually occurred get a band. An outcome with no
 * transactions would otherwise still draw its 3px minimum and claim a sliver of
 * the total that does not exist. The filter is on the count, not the share: a
 * single transaction in a large period rounds to 0% while being entirely real,
 * and that one does deserve its sliver.
 */
const barSegments = computed(() => segments.value.filter((segment) => segment.count > 0))

const presets = [
  { label: 'Last 7 days', days: 7 },
  { label: 'Last 30 days', days: 30 },
  { label: 'All time', days: null },
]

const activePreset = ref('All time')

function selectPreset(preset: { label: string; days: number | null }): void {
  activePreset.value = preset.label
  setRange(preset.days)
}
</script>

<template>
  <div>
    <div class="mb-5 flex flex-wrap items-center gap-3">
      <div
        class="flex rounded-lg border border-line bg-surface p-0.5"
        role="group"
        aria-label="Date range presets"
      >
        <button
          v-for="preset in presets"
          :key="preset.label"
          type="button"
          :class="[
            'rounded-md px-3 py-1.5 text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-accent',
            activePreset === preset.label ? 'bg-ink text-white' : 'text-ink-soft hover:text-ink',
          ]"
          :aria-pressed="activePreset === preset.label"
          @click="selectPreset(preset)"
        >
          {{ preset.label }}
        </button>
      </div>

      <!-- From/To are one control: they wrap as a unit and share a row
           on small screens instead of being split apart. -->
      <div class="flex basis-full items-center gap-2 sm:basis-auto">
        <label
          class="flex flex-1 flex-col gap-1 text-sm text-muted sm:flex-none sm:flex-row sm:items-center sm:gap-1.5"
        >
          From
          <input
            v-model="from"
            type="date"
            :max="to || undefined"
            class="w-full min-w-0 rounded-lg border border-field bg-surface px-2.5 py-2 text-sm text-ink hover:border-line focus:outline-2 focus:outline-offset-1 focus:outline-accent sm:w-auto"
            @change="activePreset = ''"
          />
        </label>
        <label
          class="flex flex-1 flex-col gap-1 text-sm text-muted sm:flex-none sm:flex-row sm:items-center sm:gap-1.5"
        >
          To
          <input
            v-model="to"
            type="date"
            :min="from || undefined"
            class="w-full min-w-0 rounded-lg border border-field bg-surface px-2.5 py-2 text-sm text-ink hover:border-line focus:outline-2 focus:outline-offset-1 focus:outline-accent sm:w-auto"
            @change="activePreset = ''"
          />
        </label>
      </div>
    </div>

    <div v-if="error" class="panel px-6 py-16 text-center">
      <p class="text-sm text-ink-soft" role="alert">{{ error }}</p>
      <BaseButton variant="secondary" class="mt-4" @click="load">Retry</BaseButton>
    </div>

    <template v-else>
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <StatCard
          label="Platform fees earned"
          :value="formatMoney(summary?.platform_fees ?? 0)"
          :sub="`${feePercent()}% of successful payments`"
          :loading="loading"
        />
        <StatCard
          label="Successful volume"
          :value="formatMoney(summary?.successful_volume ?? 0)"
          sub="gross payments processed"
          :loading="loading"
        />
        <StatCard
          label="Total settled"
          :value="formatMoney(summary?.total_settled ?? 0)"
          sub="paid out to merchants"
          :loading="loading"
        />
      </div>

      <!-- Fees over the selected period: the totals say how much, this says
           whether it is growing. -->
      <section class="panel mt-6">
        <div class="panel-head">
          <h2 class="font-display text-sm font-semibold text-ink">Platform fees earned</h2>
          <!--
            The window the line actually covers, which is not always the one
            the totals above cover: the server caps a series at 180 days, and
            "All time" charts from the first payment. Unlabelled, the card and
            the chart could describe different periods and nothing said so.
          -->
          <p v-if="chartPeriod" class="text-xs text-muted">
            {{ chartPeriod }}
            <span v-if="summary?.series_window.truncated" class="text-warn">
              · trimmed to the most recent {{ summary.series_window.days }} days
            </span>
          </p>
        </div>
        <div class="p-5">
          <div v-if="loading" class="h-56 animate-pulse rounded-lg bg-canvas" />
          <VolumeChart
            v-else-if="summary && summary.series.length > 0"
            :series="summary.series"
            metric="fees"
            label="platform fees"
          />
          <p v-else class="text-sm text-muted">
            No payments in this period, so there are no fees to chart.
          </p>
        </div>
      </section>

      <!-- Status distribution -->
      <section class="panel mt-6">
        <div class="panel-head">
          <h2 class="font-display text-sm font-semibold text-ink">Transaction outcomes</h2>
        </div>
        <div class="p-5">
          <div v-if="loading" class="h-3 animate-pulse rounded-full bg-line" />
          <template v-else-if="counts && totalCount > 0">
            <!--
              The three status tones are all dark, because each was chosen to
              carry text on white. Side by side that puts adjacent segments at
              about 1.2:1 and the bar reads as one continuous block. Spreading
              their lightness cannot fix it either: pushing them 3:1 apart from
              each other drops the lightest below 3:1 against the page. So the
              segments are separated structurally, by a gap, they run in the
              same order as the legend below so position identifies them, and
              the legend names each one — colour is never the only signal.
            -->
            <div
              class="flex h-3 gap-0.5"
              role="img"
              :aria-label="
                barSegments.map((s) => `${s.share}% ${s.label.toLowerCase()}`).join(', ')
              "
            >
              <div
                v-for="segment in barSegments"
                :key="segment.label"
                class="min-w-[3px] rounded-full"
                :class="segment.fill"
                :style="{ width: `${segment.share}%` }"
              />
            </div>

            <!--
              Each count sits directly under the status it belongs to. Pushed to
              the far end of its column it ends up nearer the next status's name
              than its own, and reads as that one's number.
            -->
            <dl class="mt-5 grid gap-5 sm:grid-cols-3">
              <div v-for="segment in segments" :key="segment.label" class="flex gap-2.5">
                <span
                  class="mt-1.5 size-2.5 shrink-0 rounded-full"
                  :class="segment.fill"
                  aria-hidden="true"
                />
                <div>
                  <dt class="text-sm text-muted">{{ segment.label }}</dt>
                  <dd class="font-display mt-0.5 text-lg font-semibold text-ink">
                    {{ segment.count }}
                    <span class="text-sm font-normal text-muted">({{ segment.share }}%)</span>
                  </dd>
                </div>
              </div>
            </dl>
          </template>
          <p v-else class="text-sm text-muted">
            No transactions in this period. Widen the date range to see outcomes.
          </p>
        </div>
      </section>

      <!-- Deep links -->
      <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <RouterLink
          to="/transactions"
          class="group rounded-lg border border-line p-5 transition-colors hover:border-violet focus-visible:outline-2 focus-visible:outline-accent"
        >
          <h3 class="font-display text-sm font-semibold text-ink group-hover:text-accent">
            Transaction history →
          </h3>
          <p class="mt-1 text-sm text-muted">Full ledger with search, status and date filters.</p>
        </RouterLink>
        <RouterLink
          to="/settlements"
          class="group rounded-lg border border-line p-5 transition-colors hover:border-violet focus-visible:outline-2 focus-visible:outline-accent"
        >
          <h3 class="font-display text-sm font-semibold text-ink group-hover:text-accent">
            Settlement history →
          </h3>
          <p class="mt-1 text-sm text-muted">Every payout batch with references and dates.</p>
        </RouterLink>
      </div>
    </template>
  </div>
</template>
