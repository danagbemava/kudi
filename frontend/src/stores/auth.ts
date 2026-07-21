import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '../api'
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
  }

  async function logout(): Promise<void> {
    await api.logout()
    user.value = null
  }

  return { user, checked, check, login, logout }
})
