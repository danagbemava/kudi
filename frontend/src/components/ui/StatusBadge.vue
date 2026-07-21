<script setup lang="ts">
const props = defineProps<{
  status: 'pending' | 'successful' | 'failed' | 'active' | 'inactive'
}>()

const tones: Record<typeof props.status, { chip: string; dot: string }> = {
  successful: { chip: 'bg-green-50 text-green-700 ring-green-600/20', dot: 'bg-green-500' },
  active: { chip: 'bg-green-50 text-green-700 ring-green-600/20', dot: 'bg-green-500' },
  pending: { chip: 'bg-amber-50 text-amber-700 ring-amber-600/20', dot: 'bg-amber-500' },
  failed: { chip: 'bg-red-50 text-red-700 ring-red-600/20', dot: 'bg-red-500' },
  inactive: { chip: 'bg-slate-100 text-slate-600 ring-slate-500/20', dot: 'bg-slate-400' },
}
</script>

<template>
  <span
    :class="[
      'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset',
      tones[status].chip,
    ]"
  >
    <span
      :class="['size-1.5 rounded-full', tones[status].dot, status === 'pending' && 'animate-pulse']"
      aria-hidden="true"
    />
    {{ status }}
  </span>
</template>
