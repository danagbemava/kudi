<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, useId, watch } from 'vue'

const props = withDefaults(
  defineProps<{
    open: boolean
    title: string
    wide?: boolean
  }>(),
  { wide: false },
)

const emit = defineEmits<{ close: [] }>()

const panel = ref<HTMLElement | null>(null)
const titleId = useId()
const bodyId = useId()
let previouslyFocused: HTMLElement | null = null

watch(
  () => props.open,
  async (open) => {
    if (open) {
      previouslyFocused = document.activeElement as HTMLElement
      // Listen on window, not the overlay: a button that disables itself while
      // loading drops focus to <body>, and a handler bound to the overlay would
      // then never fire — silently killing both Escape and the focus trap.
      window.addEventListener('keydown', onKeydown)
      await nextTick()
      focusFirst()
    } else {
      window.removeEventListener('keydown', onKeydown)
      previouslyFocused?.focus()
    }
  },
  // immediate, so a dialog that mounts already-open still gets its key handler.
  { immediate: true },
)

onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown))

function focusFirst(): void {
  const target = panel.value?.querySelector<HTMLElement>(
    'input, select, textarea, button:not([data-modal-close])',
  )
  ;(target ?? panel.value)?.focus()
}

/** Minimal focus trap: Tab cycles inside the dialog. */
function onKeydown(event: KeyboardEvent): void {
  if (event.key === 'Escape') {
    emit('close')
    return
  }

  if (event.key !== 'Tab' || !panel.value) return

  const focusables = [
    ...panel.value.querySelectorAll<HTMLElement>(
      'input, select, textarea, button, a[href], [tabindex]:not([tabindex="-1"])',
    ),
  ].filter((el) => !el.hasAttribute('disabled'))

  // Focus may have been dropped to <body> (e.g. by a button disabling itself),
  // in which case Tab must be pulled back into the dialog rather than ignored.
  if (!panel.value.contains(document.activeElement)) {
    event.preventDefault()
    focusables[0]?.focus()
    return
  }

  if (focusables.length === 0) return

  const first = focusables[0]!
  const last = focusables[focusables.length - 1]!

  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault()
    last.focus()
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault()
    first.focus()
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0"
      leave-active-class="transition duration-100 ease-in"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-end justify-center bg-ink/40 p-4 backdrop-blur-[2px] sm:items-center"
        @click.self="emit('close')"
      >
        <div
          ref="panel"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="titleId"
          :aria-describedby="bodyId"
          tabindex="-1"
          :class="[
            'w-full rounded-2xl bg-white shadow-xl outline-none',
            wide ? 'max-w-2xl' : 'max-w-md',
          ]"
        >
          <div class="flex items-center justify-between border-b border-line px-5 py-4">
            <h2 :id="titleId" class="font-display text-base font-semibold text-ink">{{ title }}</h2>
            <button
              type="button"
              data-modal-close
              class="rounded-md p-1 text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-ink focus-visible:outline-2 focus-visible:outline-forest"
              aria-label="Close dialog"
              @click="emit('close')"
            >
              <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path
                  d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"
                />
              </svg>
            </button>
          </div>
          <div :id="bodyId" class="px-5 py-4">
            <slot />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
