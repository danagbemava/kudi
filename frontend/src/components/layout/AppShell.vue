<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from '../../composables/useToast'
import { useAuthStore } from '../../stores/auth'
import SideNav from './SideNav.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const toast = useToast()

const drawerOpen = ref(false)
const announcement = ref('')

watch(
  () => route.meta.title,
  (title) => {
    if (title) announcement.value = `${title as string} page loaded`
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

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onDrawerKeydown)
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
      class="sr-only rounded-lg bg-white px-4 py-2 text-sm font-medium text-ink shadow-lg outline-2 outline-primary-600 focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-[70]"
    >
      Skip to main content
    </a>

    <!-- Route changes don't move focus or reload the document, so announce the
         new page for screen reader users. -->
    <p class="sr-only" role="status" aria-live="polite">{{ announcement }}</p>

    <!-- Desktop sidebar -->
    <aside class="hidden w-60 shrink-0 lg:block">
      <div class="fixed inset-y-0 w-60">
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
              class="absolute top-4 right-3 rounded-md p-1.5 text-slate-400 transition-colors hover:bg-white/10 hover:text-white focus-visible:outline-2 focus-visible:outline-primary-500"
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
      <header
        class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-line bg-white/90 px-4 backdrop-blur sm:px-6"
      >
        <div class="flex items-center gap-3">
          <button
            ref="menuButton"
            type="button"
            class="rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-ink focus-visible:outline-2 focus-visible:outline-primary-600 lg:hidden"
            aria-label="Open navigation menu"
            aria-controls="main-nav"
            :aria-expanded="drawerOpen"
            @click="drawerOpen = true"
          >
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
          </button>
          <h1 class="font-display text-lg font-semibold text-ink">
            {{ route.meta.title }}
          </h1>
        </div>

        <div class="flex items-center gap-3">
          <span class="hidden text-sm text-slate-500 sm:block">{{ auth.user?.email }}</span>
          <div
            class="flex size-8 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold text-primary-700"
            aria-hidden="true"
          >
            {{ auth.user?.name?.charAt(0) ?? '?' }}
          </div>
          <button
            type="button"
            class="rounded-lg px-2.5 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-ink focus-visible:outline-2 focus-visible:outline-primary-600"
            @click="logout"
          >
            Sign out
          </button>
        </div>
      </header>

      <!-- Routes are lazy-loaded, so the view is briefly empty while a chunk
           loads. Reserving the viewport height keeps the page from collapsing
           and reflowing in that gap. -->
      <main
        id="main-content"
        tabindex="-1"
        class="mx-auto min-h-[calc(100vh-4rem)] max-w-7xl px-4 py-6 outline-none sm:px-6 lg:px-8"
      >
        <RouterView />
      </main>
    </div>
  </div>
</template>
