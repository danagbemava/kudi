<script setup lang="ts">
// Badges carry a uniform min-width so a money column sitting to their left
// stays aligned whether the label reads "pending" or "successful".
type Status = 'pending' | 'successful' | 'failed' | 'active' | 'inactive'

defineProps<{ status: Status }>()

const tones: Record<Status, { chip: string; dot: string }> = {
  successful: { chip: 'bg-good-wash text-good ring-good/25', dot: 'bg-good' },
  active: { chip: 'bg-good-wash text-good ring-good/25', dot: 'bg-good' },
  pending: { chip: 'bg-warn-wash text-warn ring-warn/35', dot: 'bg-warn' },
  failed: { chip: 'bg-bad-wash text-bad ring-bad/25', dot: 'bg-bad' },
  inactive: { chip: 'bg-canvas text-ink-soft ring-field/20', dot: 'bg-field' },
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
