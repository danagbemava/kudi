<script setup lang="ts">
// Badges carry a uniform min-width so a money column sitting to their left
// stays aligned whether the label reads "pending" or "successful".
type Status = 'pending' | 'successful' | 'failed' | 'active' | 'inactive'

defineProps<{ status: Status }>()

const tones: Record<Status, { chip: string; dot: string }> = {
  successful: { chip: 'bg-forest-wash text-forest ring-forest/25', dot: 'bg-forest' },
  active: { chip: 'bg-forest-wash text-forest ring-forest/25', dot: 'bg-forest' },
  pending: { chip: 'bg-gold-wash text-ink-soft ring-gold/40', dot: 'bg-gold' },
  failed: { chip: 'bg-clay-wash text-clay ring-clay/25', dot: 'bg-clay' },
  inactive: { chip: 'bg-zinc-100 text-zinc-600 ring-zinc-500/20', dot: 'bg-zinc-400' },
}
</script>

<template>
  <span
    :class="[
      'inline-flex min-w-[6.25rem] items-center justify-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset',
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
