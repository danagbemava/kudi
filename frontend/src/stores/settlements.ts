import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api, type DateRangeFilters } from '../api'
import { toApiError } from '../api/client'
import type { PageMeta, Settlement, SettlementBatch, SettlementRunResult } from '../api/types'
import { createLatestGuard } from './latest'

export const useSettlementsStore = defineStore('settlements', () => {
  /** History as runs. One row per sweep, with the whole run's total. */
  const batches = ref<SettlementBatch[]>([])
  const meta = ref<PageMeta | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const running = ref(false)

  /**
   * Members of an expanded run, keyed by batch reference. Fetched on demand:
   * a page of runs covers far more records than anyone opens at once.
   */
  const members = ref<Record<string, Settlement[]>>({})
  const membersLoading = ref<Record<string, boolean>>({})

  const guard = createLatestGuard()
  const lastFilters = ref<DateRangeFilters>({})

  async function fetch(filters: DateRangeFilters = lastFilters.value): Promise<void> {
    lastFilters.value = filters
    const ticket = guard.next()
    loading.value = true
    error.value = null
    try {
      const page = await api.settlementBatches(filters)
      if (!guard.isCurrent(ticket)) return
      batches.value = page.data
      meta.value = page.meta
      // Anything expanded describes the previous result set.
      members.value = {}
    } catch (e) {
      if (!guard.isCurrent(ticket)) return
      error.value = toApiError(e).message
    } finally {
      if (guard.isCurrent(ticket)) loading.value = false
    }
  }

  /**
   * Load the merchants paid in one run. Cached, so collapsing and re-expanding
   * a batch does not re-fetch it.
   *
   * per_page is the server maximum: a run has one record per merchant holding
   * float, and paginating inside an expanded row would be a worse experience
   * than the flat table this replaced.
   */
  async function loadMembers(batchRef: string): Promise<void> {
    if (members.value[batchRef] || membersLoading.value[batchRef]) return

    membersLoading.value = { ...membersLoading.value, [batchRef]: true }
    try {
      const page = await api.settlements({ batch: batchRef, per_page: 100 })
      members.value = { ...members.value, [batchRef]: page.data }
    } catch (e) {
      error.value = toApiError(e).message
    } finally {
      const remaining = { ...membersLoading.value }
      delete remaining[batchRef]
      membersLoading.value = remaining
    }
  }

  /** Runs the daily settlement and refreshes the history on success. */
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

  return {
    batches,
    meta,
    loading,
    error,
    running,
    members,
    membersLoading,
    fetch,
    loadMembers,
    run,
  }
})
