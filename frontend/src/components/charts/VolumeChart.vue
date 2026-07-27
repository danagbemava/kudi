<script setup lang="ts">
import {
  CategoryScale,
  Chart,
  Filler,
  LinearScale,
  LineElement,
  PointElement,
  Tooltip,
} from 'chart.js'
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import { formatMoney, formatMoneyCompact } from '../../utils/money'

Chart.register(CategoryScale, LinearScale, PointElement, LineElement, Filler, Tooltip)

const props = withDefaults(
  defineProps<{
    series: { date: string; volume: number; fees: number }[]
    /** Which figure to plot. Volume on the dashboard, fees on reports. */
    metric?: 'volume' | 'fees'
    /** Names the series in the text alternative. */
    label?: string
  }>(),
  { metric: 'volume', label: 'payment volume' },
)

const values = computed(() => props.series.map((point) => point[props.metric]))

const labels = computed(() =>
  props.series.map((point) =>
    new Date(point.date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }),
  ),
)

const summary = computed(() => {
  const points = props.series
  if (points.length === 0) return `Daily ${props.label} chart. No data available.`

  const total = values.value.reduce((sum, v) => sum + v, 0)
  const peakIndex = values.value.indexOf(Math.max(...values.value))
  const peak = { date: points[peakIndex]!.date, value: values.value[peakIndex]! }

  return (
    `Daily ${props.label} over ${points.length} days. ` +
    `Total ${formatMoney(total)}. ` +
    `Busiest day ${new Date(peak.date).toLocaleDateString('en-GB', {
      day: 'numeric',
      month: 'long',
    })} at ${formatMoney(peak.value)}.`
  )
})

const data = computed(() => ({
  labels: labels.value,
  datasets: [
    {
      data: values.value,
      borderColor: '#12013D',
      borderWidth: 2,
      pointRadius: 0,
      pointHoverRadius: 4,
      pointHoverBackgroundColor: '#12013D',
      // Datasets are clipped to the plot area by default, and the first and
      // last points sit exactly on that boundary — so their hover dot lost its
      // outer half. The layout padding below reserves the room this needs.
      // `as const` because Chart.js types this as `number | false | ChartArea`,
      // and a widened `boolean` does not satisfy it.
      clip: false as const,
      tension: 0.35,
      // Monotone, not the default cubic. Plain cubic smoothing overshoots
      // between points: a quiet day next to a busy one gets a curve that dips
      // below the quiet day's actual figure, inventing a lower value than any
      // day recorded. On money that is not a stylistic choice.
      cubicInterpolationMode: 'monotone' as const,
      fill: true,
      backgroundColor: (context: { chart: Chart }) => {
        const { ctx, chartArea } = context.chart
        if (!chartArea) return 'rgba(18, 1, 61, 0.06)'
        const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom)
        gradient.addColorStop(0, 'rgba(18, 1, 61, 0.12)')
        gradient.addColorStop(1, 'rgba(18, 1, 61, 0)')
        return gradient
      },
    },
  ],
}))

const options = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index' as const, intersect: false },
  // Room for a hover dot on the edge points to render whole, plus the stroke
  // width of a peak that touches the top of the plot area. 4px radius + 1px of
  // the 2px line, rounded up.
  layout: { padding: { left: 6, right: 6, top: 8 } },
  plugins: {
    tooltip: {
      backgroundColor: '#1A1A1F',
      padding: 10,
      displayColors: false,
      callbacks: {
        label: (item: { raw: unknown }) => formatMoney(item.raw as number),
      },
    },
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: { maxTicksLimit: 6, color: '#6E6D78', font: { size: 11 } },
    },
    y: {
      border: { display: false },
      grid: { color: '#F1F1EE' },
      ticks: {
        maxTicksLimit: 5,
        color: '#6E6D78',
        font: { size: 11 },
        callback: (value: string | number) => formatMoneyCompact(Number(value)),
      },
    },
  },
}
</script>

<template>
  <div>
    <div class="h-64 xl:h-80">
      <Line :data="data" :options="options" role="img" :aria-label="`${label} chart`" />
    </div>
    <!-- A canvas exposes nothing to assistive tech, so restate the series as
         text. Screen reader users get the totals and the peak day. -->
    <p class="sr-only">{{ summary }}</p>
  </div>
</template>
