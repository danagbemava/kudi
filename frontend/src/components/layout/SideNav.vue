<script setup lang="ts">
import { useRoute } from 'vue-router'
import KudiLogo from '../ui/KudiLogo.vue'

const route = useRoute()

// SideNav renders twice (desktop aside + mobile drawer), so the id is passed in
// by whichever instance the menu button actually controls — hard-coding it here
// would put a duplicate id in the DOM.
defineProps<{ navId?: string }>()

const emit = defineEmits<{ navigate: [] }>()

const ACTIVE = 'bg-white/10 text-white'

/**
 * Highlighting is computed from the path rather than left to RouterLink's
 * active-class. Detail routes like /merchants/:id are registered as siblings
 * of /merchants, not children, and RouterLink derives activeness from matched
 * route records — so it does not treat the section link as active on a detail
 * page and the nav loses its place.
 */
function isActive(item: { to: string; exact?: boolean }): boolean {
  if (item.exact) return route.path === item.to
  return route.path === item.to || route.path.startsWith(`${item.to}/`)
}

const items = [
  {
    name: 'Dashboard',
    to: '/',
    // Only the dashboard matches exactly; every other path starts with "/",
    // so prefix matching would keep it permanently highlighted.
    exact: true,
    icon: 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z',
  },
  {
    name: 'Merchants',
    to: '/merchants',
    icon: 'M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z',
  },
  {
    name: 'Transactions',
    to: '/transactions',
    icon: 'M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5',
  },
  {
    name: 'Settlements',
    to: '/settlements',
    icon: 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z',
  },
  {
    name: 'Reports',
    to: '/reports',
    icon: 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
  },
]
</script>

<template>
  <div class="flex h-full flex-col bg-ink">
    <div class="flex h-16 items-center px-5">
      <KudiLogo light />
    </div>

    <nav :id="navId" class="flex-1 space-y-1 px-3 py-4" aria-label="Main navigation">
      <RouterLink
        v-for="item in items"
        :key="item.to"
        :to="item.to"
        :class="[
          'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500',
          isActive(item) ? ACTIVE : 'text-slate-300 hover:bg-white/5 hover:text-white',
        ]"
        :aria-current="isActive(item) ? 'page' : undefined"
        @click="emit('navigate')"
      >
        <svg
          class="size-5 text-slate-400 transition-colors group-hover:text-slate-200"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.6"
          aria-hidden="true"
        >
          <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
        </svg>
        {{ item.name }}
      </RouterLink>
    </nav>

    <div class="border-t border-white/10 px-5 py-4">
      <p class="text-[11px] leading-relaxed text-slate-300">
        Merchant Wallet &amp; Settlement<br />
        <span class="text-slate-400">Demo environment</span>
      </p>
    </div>
  </div>
</template>
