<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { api } from '../api'
import { toApiError } from '../api/client'
import type { ReportSummary } from '../api/types'
import BaseButton from '../components/ui/BaseButton.vue'
import StatCard from '../components/ui/StatCard.vue'
import { formatMoney } from '../utils/money'

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
  start.setDate(start.getDate() - days)
  from.value = start.toISOString().slice(0, 10)
  to.value = now.toISOString().slice(0, 10)
}

const counts = computed(() => summary.value?.transaction_counts)
const totalCount = computed(() =>
  counts.value ? counts.value.successful + counts.value.pending + counts.value.failed : 0,
)

function share(status: 'successful' | 'pending' | 'failed'): number {
  if (!counts.value || totalCount.value === 0) return 0
  return Math.round((counts.value[status] / totalCount.value) * 100)
}

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
        class="flex rounded-lg border border-line bg-white p-0.5"
        role="group"
        aria-label="Date range presets"
      >
        <button
          v-for="preset in presets"
          :key="preset.label"
          type="button"
          :class="[
            'rounded-md px-3 py-1.5 text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-forest',
            activePreset === preset.label ? 'bg-ink text-white' : 'text-zinc-600 hover:text-ink',
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
          class="flex flex-1 flex-col gap-1 text-sm text-zinc-500 sm:flex-none sm:flex-row sm:items-center sm:gap-1.5"
        >
          From
          <input
            v-model="from"
            type="date"
            :max="to || undefined"
            class="w-full min-w-0 rounded-lg border border-field bg-white px-2.5 py-2 text-sm text-ink hover:border-zinc-300 focus:outline-2 focus:outline-offset-1 focus:outline-forest sm:w-auto"
            @change="activePreset = ''"
          />
        </label>
        <label
          class="flex flex-1 flex-col gap-1 text-sm text-zinc-500 sm:flex-none sm:flex-row sm:items-center sm:gap-1.5"
        >
          To
          <input
            v-model="to"
            type="date"
            :min="from || undefined"
            class="w-full min-w-0 rounded-lg border border-field bg-white px-2.5 py-2 text-sm text-ink hover:border-zinc-300 focus:outline-2 focus:outline-offset-1 focus:outline-forest sm:w-auto"
            @change="activePreset = ''"
          />
        </label>
      </div>
    </div>

    <div v-if="error" class="rounded-xl border border-line bg-white px-6 py-16 text-center">
      <p class="text-sm text-zinc-600" role="alert">{{ error }}</p>
      <BaseButton variant="secondary" class="mt-4" @click="load">Retry</BaseButton>
    </div>

    <template v-else>
      <div class="grid gap-4 sm:grid-cols-3">
        <StatCard
          label="Platform fees earned"
          :value="formatMoney(summary?.platform_fees ?? 0)"
          sub="1.5% of successful payments"
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

      <!-- Status distribution -->
      <section class="mt-6 rounded-xl border border-line bg-white p-5">
        <h2 class="font-display text-sm font-semibold text-ink">Transaction outcomes</h2>

        <div v-if="loading" class="mt-4 h-3 animate-pulse rounded-full bg-zinc-200" />
        <template v-else-if="counts && totalCount > 0">
          <div
            class="mt-4 flex h-3 overflow-hidden rounded-full"
            role="img"
            :aria-label="`${share('successful')}% successful, ${share('pending')}% pending, ${share('failed')}% failed`"
          >
            <div class="bg-forest" :style="{ width: `${share('successful')}%` }" />
            <div class="bg-gold" :style="{ width: `${share('pending')}%` }" />
            <div class="bg-clay" :style="{ width: `${share('failed')}%` }" />
          </div>
          <dl class="mt-4 grid gap-3 sm:grid-cols-3">
            <div class="flex items-center gap-2.5">
              <span class="size-2.5 rounded-full bg-forest" aria-hidden="true" />
              <dt class="text-sm text-zinc-500">Successful</dt>
              <dd class="ml-auto text-sm font-semibold text-ink">
                {{ counts.successful }}
                <span class="font-normal text-zinc-500">({{ share('successful') }}%)</span>
              </dd>
            </div>
            <div class="flex items-center gap-2.5">
              <span class="size-2.5 rounded-full bg-gold" aria-hidden="true" />
              <dt class="text-sm text-zinc-500">Pending</dt>
              <dd class="ml-auto text-sm font-semibold text-ink">
                {{ counts.pending }}
                <span class="font-normal text-zinc-500">({{ share('pending') }}%)</span>
              </dd>
            </div>
            <div class="flex items-center gap-2.5">
              <span class="size-2.5 rounded-full bg-clay" aria-hidden="true" />
              <dt class="text-sm text-zinc-500">Failed</dt>
              <dd class="ml-auto text-sm font-semibold text-ink">
                {{ counts.failed }}
                <span class="font-normal text-zinc-500">({{ share('failed') }}%)</span>
              </dd>
            </div>
          </dl>
        </template>
        <p v-else class="mt-4 text-sm text-zinc-500">
          No transactions in this period. Widen the date range to see outcomes.
        </p>
      </section>

      <!-- Deep links -->
      <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <RouterLink
          to="/transactions"
          class="group rounded-xl border border-line bg-white p-5 transition-colors hover:border-forest focus-visible:outline-2 focus-visible:outline-forest"
        >
          <h3 class="font-display text-sm font-semibold text-ink group-hover:text-forest-deep">
            Transaction history →
          </h3>
          <p class="mt-1 text-sm text-zinc-500">
            Full ledger with search, status and date filters.
          </p>
        </RouterLink>
        <RouterLink
          to="/settlements"
          class="group rounded-xl border border-line bg-white p-5 transition-colors hover:border-forest focus-visible:outline-2 focus-visible:outline-forest"
        >
          <h3 class="font-display text-sm font-semibold text-ink group-hover:text-forest-deep">
            Settlement history →
          </h3>
          <p class="mt-1 text-sm text-zinc-500">Every payout batch with references and dates.</p>
        </RouterLink>
      </div>
    </template>
  </div>
</template>
