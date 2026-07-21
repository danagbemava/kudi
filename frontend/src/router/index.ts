import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('../pages/LoginPage.vue'),
      meta: { public: true, title: 'Sign in' },
    },
    {
      path: '/',
      component: () => import('../components/layout/AppShell.vue'),
      children: [
        {
          path: '',
          name: 'dashboard',
          component: () => import('../pages/DashboardPage.vue'),
          meta: { title: 'Dashboard' },
        },
        {
          path: 'merchants',
          name: 'merchants',
          component: () => import('../pages/MerchantsPage.vue'),
          meta: { title: 'Merchants' },
        },
        {
          path: 'merchants/:id',
          name: 'merchant-detail',
          component: () => import('../pages/MerchantDetailPage.vue'),
          meta: { title: 'Merchant' },
        },
        {
          path: 'transactions',
          name: 'transactions',
          component: () => import('../pages/TransactionsPage.vue'),
          meta: { title: 'Transactions' },
        },
        {
          path: 'settlements',
          name: 'settlements',
          component: () => import('../pages/SettlementsPage.vue'),
          meta: { title: 'Settlements' },
        },
        {
          path: 'reports',
          name: 'reports',
          component: () => import('../pages/ReportsPage.vue'),
          meta: { title: 'Reports' },
        },
      ],
    },
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  const authenticated = await auth.check()

  if (!to.meta.public && !authenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.name === 'login' && authenticated) {
    return { name: 'dashboard' }
  }
})

router.afterEach((to) => {
  document.title = to.meta.title ? `${to.meta.title as string} · Kudi` : 'Kudi'
})

export default router
