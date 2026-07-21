import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api, type MerchantFilters } from '../api'
import { toApiError } from '../api/client'
import type { Institution, Merchant, MerchantDetail, MerchantStatus } from '../api/types'

export const useMerchantsStore = defineStore('merchants', () => {
  const items = ref<Merchant[]>([])
  const meta = ref<{ current_page: number; last_page: number; total: number } | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const detail = ref<MerchantDetail | null>(null)
  const detailLoading = ref(false)
  const detailError = ref<string | null>(null)

  const institutions = ref<Institution[]>([])

  async function fetch(filters: MerchantFilters = {}): Promise<void> {
    loading.value = true
    error.value = null
    try {
      const page = await api.merchants(filters)
      items.value = page.data
      meta.value = page.meta
    } catch (e) {
      error.value = toApiError(e).message
    } finally {
      loading.value = false
    }
  }

  async function fetchDetail(id: number): Promise<void> {
    detailLoading.value = true
    detailError.value = null
    try {
      detail.value = await api.merchant(id)
    } catch (e) {
      detail.value = null
      detailError.value = toApiError(e).message
    } finally {
      detailLoading.value = false
    }
  }

  async function fetchInstitutions(): Promise<void> {
    if (institutions.value.length === 0) {
      institutions.value = await api.institutions()
    }
  }

  async function setStatus(id: number, status: MerchantStatus): Promise<Merchant> {
    const updated = await api.setMerchantStatus(id, status)

    items.value = items.value.map((m) => (m.id === id ? { ...m, status: updated.status } : m))
    if (detail.value?.id === id) {
      detail.value = { ...detail.value, status: updated.status }
    }

    return updated
  }

  return {
    items,
    meta,
    loading,
    error,
    detail,
    detailLoading,
    detailError,
    institutions,
    fetch,
    fetchDetail,
    fetchInstitutions,
    setStatus,
  }
})
