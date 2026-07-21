import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api, type TransactionFilters } from '../api'
import { toApiError } from '../api/client'
import type { Transaction } from '../api/types'

export const useTransactionsStore = defineStore('transactions', () => {
  const items = ref<Transaction[]>([])
  const meta = ref<{ current_page: number; last_page: number; total: number } | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const lastFilters = ref<TransactionFilters>({})

  async function fetch(filters: TransactionFilters = lastFilters.value): Promise<void> {
    lastFilters.value = filters
    loading.value = true
    error.value = null
    try {
      const page = await api.transactions(filters)
      items.value = page.data
      meta.value = page.meta
    } catch (e) {
      error.value = toApiError(e).message
    } finally {
      loading.value = false
    }
  }

  /**
   * Simulates a customer payment: records the pending transaction, then
   * after a short "gateway" delay asks the backend to resolve it. Returns
   * the resolved transaction.
   */
  async function simulatePayment(payload: {
    merchant_id: number | null
    amount: number
    reference: string
  }): Promise<Transaction> {
    const pending = await api.createPayment(payload)
    items.value = [pending, ...items.value]

    await new Promise((resolve) => setTimeout(resolve, 1800))

    const resolved = await api.processTransaction(pending.id)
    items.value = items.value.map((t) => (t.id === resolved.id ? resolved : t))
    return resolved
  }

  return { items, meta, loading, error, fetch, simulatePayment }
})
