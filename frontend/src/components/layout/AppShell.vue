<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from '../../composables/useToast'
import { useAuthStore } from '../../stores/auth'
import SideNav from './SideNav.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const toast = useToast()

const drawerOpen = ref(false)

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
        <div v-if="drawerOpen" class="fixed inset-0 z-40 lg:hidden">
          <div class="absolute inset-0 bg-ink/50" @click="drawerOpen = false" />
          <div class="absolute inset-y-0 left-0 w-64 shadow-xl">
            <SideNav @navigate="drawerOpen = false" />
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
            type="button"
            class="rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-ink focus-visible:outline-2 focus-visible:outline-primary-600 lg:hidden"
            aria-label="Open navigation menu"
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

      <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <RouterView />
      </main>
    </div>
  </div>
</template>
