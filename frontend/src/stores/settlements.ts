import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api, type DateRangeFilters } from '../api'
import { toApiError } from '../api/client'
import type { Settlement, SettlementRunResult } from '../api/types'

export const useSettlementsStore = defineStore('settlements', () => {
  const items = ref<Settlement[]>([])
  const meta = ref<{ current_page: number; last_page: number; total: number } | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const running = ref(false)

  async function fetch(filters: DateRangeFilters = {}): Promise<void> {
    loading.value = true
    error.value = null
    try {
      const page = await api.settlements(filters)
      items.value = page.data
      meta.value = page.meta
    } catch (e) {
      error.value = toApiError(e).message
    } finally {
      loading.value = false
    }
  }

  /** Runs the daily settlement and refreshes the list on success. */
  async function run(): Promise<SettlementRunResult> {
    running.value = true
    try {
      const result = await api.runSettlement()
      await fetch()
      return result
    } finally {
      running.value = false
    }
  }

  return { items, meta, loading, error, running, fetch, run }
})
