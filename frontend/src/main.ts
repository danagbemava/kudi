import './style.css'

import { createPinia } from 'pinia'
import { createApp } from 'vue'
import { onSessionExpired } from './api/client'
import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/auth'

const app = createApp(App).use(createPinia()).use(router)

/**
 * A session can lapse while the tab sits idle. When the server tells us it
 * has, drop the local session and send the user to sign in — keeping the page
 * they were on so they return to it, and saying why rather than leaving them
 * on a screen where every action silently fails.
 */
onSessionExpired(() => {
  const auth = useAuthStore()
  if (!auth.user) return // already signed out; nothing to announce

  const from = router.currentRoute.value
  auth.clear()

  router.push({
    name: 'login',
    query: {
      ...(from.name === 'login' ? {} : { redirect: from.fullPath }),
      reason: 'expired',
    },
  })
})

app.mount('#app')
