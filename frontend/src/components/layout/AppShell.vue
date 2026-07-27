<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePageTitle } from '../../composables/usePageTitle'
import { useToast } from '../../composables/useToast'
import { useAuthStore } from '../../stores/auth'
import SideNav from './SideNav.vue'
import { setSoundEnabled, soundEnabled } from '../../utils/sound'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const toast = useToast()

const drawerOpen = ref(false)
const announcement = ref('')

const { override: pageTitle } = usePageTitle()
const heading = computed(() => pageTitle.value ?? (route.meta.title as string | undefined) ?? '')

watch(
  heading,
  (title) => {
    if (title) announcement.value = `${title} page loaded`
  },
  { immediate: true },
)

const menuButton = ref<HTMLButtonElement | null>(null)
const drawerPanel = ref<HTMLElement | null>(null)

function onDrawerKeydown(event: KeyboardEvent): void {
  if (event.key === 'Escape') drawerOpen.value = false
}

// The drawer covers the page on small screens, so it needs the same keyboard
// contract as a dialog: Escape closes it, focus moves in, focus comes back, and
// the page behind is unreachable. The drawer is teleported to <body>, so marking
// the app root inert traps focus without hand-rolling a Tab cycle.
watch(drawerOpen, async (open) => {
  const appRoot = document.getElementById('app')

  if (open) {
    window.addEventListener('keydown', onDrawerKeydown)
    appRoot?.setAttribute('inert', '')
    await nextTick()
    drawerPanel.value?.querySelector('a')?.focus()
  } else {
    window.removeEventListener('keydown', onDrawerKeydown)
    appRoot?.removeAttribute('inert')
    menuButton.value?.focus()
  }
})

// A session can lapse while the tab is in the background. Re-checking on
// return catches it there and then, rather than on the user's next click.
function onVisibilityChange(): void {
  if (document.visibilityState === 'visible') auth.revalidate()
}

document.addEventListener('visibilitychange', onVisibilityChange)

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onDrawerKeydown)
  document.removeEventListener('visibilitychange', onVisibilityChange)
  document.getElementById('app')?.removeAttribute('inert')
})

async function logout(): Promise<void> {
  try {
    await auth.logout()
    router.push({ name: 'login' })
  } catch {
    toast.error('Sign out failed. Please retry.')
  }
}
</script>

<template>
  <div class="min-h-screen lg:flex">
    <a
      href="#main-content"
      class="sr-only rounded-lg bg-surface px-4 py-2 text-sm font-medium text-ink shadow-lg outline-2 outline-accent focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-[70]"
    >
      Skip to main content
    </a>

    <!-- Route changes don't move focus or reload the document, so announce the
         new page for screen reader users. -->
    <p class="sr-only" role="status" aria-live="polite">{{ announcement }}</p>

    <!-- Desktop sidebar -->
    <aside class="hidden w-60 shrink-0 lg:block">
      <div class="fixed inset-y-0 left-0 w-60">
        <SideNav />
      </div>
    </aside>

    <!-- Mobile drawer -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-150 ease-out"
        enter-from-class="opacity-0"
        leave-active-class="transition duration-100 ease-in"
        leave-to-class="opacity-0"
      >
        <div
          v-if="drawerOpen"
          class="fixed inset-0 z-40 lg:hidden"
          role="dialog"
          aria-modal="true"
          aria-label="Navigation menu"
        >
          <div class="absolute inset-0 bg-ink/50" @click="drawerOpen = false" />
          <div ref="drawerPanel" class="absolute inset-y-0 left-0 w-64 shadow-xl">
            <SideNav nav-id="main-nav" @navigate="drawerOpen = false" />
            <button
              type="button"
              class="absolute top-4 right-3 rounded-md p-1.5 text-muted transition-colors hover:bg-canvas hover:text-ink focus-visible:outline-2 focus-visible:outline-accent"
              aria-label="Close navigation menu"
              @click="drawerOpen = false"
            >
              <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path
                  d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"
                />
              </svg>
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>

    <div class="min-w-0 flex-1">
      <!-- The rule spans the full width, but the header's contents sit in the
           same centred, max-width, equally-padded container as <main> — without
           it the title drifts out of line with the page at ≥lg. -->
      <header class="sticky top-0 z-30 border-b border-line bg-surface/90 backdrop-blur">
        <div class="mx-auto max-w-[100rem] px-4 sm:px-6 lg:px-8">
          <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-3">
              <button
                ref="menuButton"
                type="button"
                class="rounded-lg p-2 text-muted transition-colors hover:bg-canvas hover:text-ink focus-visible:outline-2 focus-visible:outline-accent lg:hidden"
                aria-label="Open navigation menu"
                aria-controls="main-nav"
                :aria-expanded="drawerOpen"
                @click="drawerOpen = true"
              >
                <svg
                  class="size-5"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.8"
                  aria-hidden="true"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"
                  />
                </svg>
              </button>
              <h1 class="font-display truncate text-lg font-semibold text-ink">
                {{ heading }}
              </h1>
            </div>

            <div class="flex items-center gap-3">
              <span class="hidden text-sm text-muted sm:block">{{ auth.user?.email }}</span>
              <button
                type="button"
                class="rounded-lg p-2 text-muted transition-colors hover:bg-canvas hover:text-ink focus-visible:outline-2 focus-visible:outline-accent"
                :aria-pressed="!soundEnabled"
                :aria-label="
                  soundEnabled ? 'Mute notification sounds' : 'Unmute notification sounds'
                "
                @click="setSoundEnabled(!soundEnabled)"
              >
                <svg
                  class="size-5"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.7"
                  aria-hidden="true"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M11 5L6 9H3v6h3l5 4V5z" />
                  <path
                    v-if="soundEnabled"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M15.5 8.5a5 5 0 010 7M18 6a8.5 8.5 0 010 12"
                  />
                  <path
                    v-else
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M16 9l5 6m0-6l-5 6"
                  />
                </svg>
              </button>
              <div
                class="flex size-8 items-center justify-center rounded-full border border-line bg-canvas text-sm font-semibold text-ink-soft"
                aria-hidden="true"
              >
                {{ auth.user?.name?.charAt(0) ?? '?' }}
              </div>
              <button
                type="button"
                class="rounded-lg px-2.5 py-1.5 text-sm font-medium text-ink-soft transition-colors hover:bg-canvas hover:text-ink focus-visible:outline-2 focus-visible:outline-accent"
                @click="logout"
              >
                Sign out
              </button>
            </div>
          </div>
        </div>
      </header>

      <!-- Routes are lazy-loaded, so the view is briefly empty while a chunk
           loads. Reserving the viewport height keeps the page from collapsing
           and reflowing in that gap. -->
      <main
        id="main-content"
        tabindex="-1"
        class="mx-auto min-h-[calc(100vh-4rem)] max-w-[100rem] px-4 py-6 outline-none sm:px-6 lg:px-8"
      >
        <RouterView />
      </main>
    </div>
  </div>
</template>
