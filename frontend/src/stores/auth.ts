import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '../api'
import { syncServerConfig } from '../api/bootstrap'
import { isUnauthorized, notifySessionExpired } from '../api/client'
import type { User } from '../api/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const checked = ref(false)

  /** Resolves the session once per app load; guards await this. */
  async function check(): Promise<boolean> {
    if (checked.value) return user.value !== null

    try {
      user.value = await api.user()
    } catch {
      user.value = null
    }

    checked.value = true
    return user.value !== null
  }

  async function login(email: string, password: string): Promise<void> {
    user.value = await api.login(email, password)
    checked.value = true

    // Server-owned constants live behind auth, so this is the first moment
    // they can be read.
    syncServerConfig()
  }

  async function logout(): Promise<void> {
    await api.logout()
    clear()
  }

  /**
   * Drops the local session without calling the server — for when the server
   * has already told us the session is gone. `checked` stays true so the
   * router guard doesn't fire a pointless request on the way to login.
   */
  function clear(): void {
    user.value = null
    checked.value = true
  }

  /**
   * Re-checks the session against the server. Used when the tab regains
   * focus, so a session that expired while the tab sat in the background is
   * caught on return rather than on the next click.
   */
  async function revalidate(): Promise<void> {
    if (!user.value) return

    try {
      user.value = await api.user()
    } catch (error) {
      // /api/user is a probe URL, so the interceptor deliberately ignores its
      // 401 — a guest hitting the router guard must not be "signed out". Here
      // we know a session existed, so an expiry is real and handled directly.
      if (isUnauthorized(error)) notifySessionExpired()
    }
  }

  return { user, checked, check, login, logout, clear, revalidate }
})
