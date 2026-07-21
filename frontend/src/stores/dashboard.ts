import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '../api'
import { toApiError } from '../api/client'
import type { DashboardData } from '../api/types'

export const useDashboardStore = defineStore('dashboard', () => {
  const data = ref<DashboardData | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetch(): Promise<void> {
    loading.value = true
    error.value = null
    try {
      data.value = await api.dashboard()
    } catch (e) {
      error.value = toApiError(e).message
    } finally {
      loading.value = false
    }
  }

  return { data, loading, error, fetch }
})
