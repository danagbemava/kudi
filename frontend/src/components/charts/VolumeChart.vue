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

const props = defineProps<{
  series: { date: string; volume: number }[]
}>()

const labels = computed(() =>
  props.series.map((point) =>
    new Date(point.date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }),
  ),
)

const data = computed(() => ({
  labels: labels.value,
  datasets: [
    {
      data: props.series.map((point) => point.volume),
      borderColor: '#4f46e5',
      borderWidth: 2,
      pointRadius: 0,
      pointHoverRadius: 4,
      pointHoverBackgroundColor: '#4f46e5',
      tension: 0.35,
      fill: true,
      backgroundColor: (context: { chart: Chart }) => {
        const { ctx, chartArea } = context.chart
        if (!chartArea) return 'rgba(79, 70, 229, 0.08)'
        const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom)
        gradient.addColorStop(0, 'rgba(79, 70, 229, 0.18)')
        gradient.addColorStop(1, 'rgba(79, 70, 229, 0)')
        return gradient
      },
    },
  ],
}))

const options = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index' as const, intersect: false },
  plugins: {
    tooltip: {
      backgroundColor: '#0f172a',
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
      ticks: { maxTicksLimit: 6, color: '#94a3b8', font: { size: 11 } },
    },
    y: {
      border: { display: false },
      grid: { color: '#f1f5f9' },
      ticks: {
        maxTicksLimit: 5,
        color: '#94a3b8',
        font: { size: 11 },
        callback: (value: string | number) => formatMoneyCompact(Number(value)),
      },
    },
  },
}
</script>

<template>
  <div class="h-64">
    <Line :data="data" :options="options" aria-label="Daily payment volume, last 30 days" />
  </div>
</template>
