import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '../api'
import { toApiError } from '../api/client'
import type { DashboardData } from '../api/types'
import { createLatestGuard } from './latest'

export const useDashboardStore = defineStore('dashboard', () => {
  const data = ref<DashboardData | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const guard = createLatestGuard()

  async function fetch(): Promise<void> {
    const ticket = guard.next()
    loading.value = true
    error.value = null
    try {
      const loaded = await api.dashboard()
      if (!guard.isCurrent(ticket)) return
      data.value = loaded
    } catch (e) {
      if (!guard.isCurrent(ticket)) return
      error.value = toApiError(e).message
    } finally {
      if (guard.isCurrent(ticket)) loading.value = false
    }
  }

  return { data, loading, error, fetch }
})
