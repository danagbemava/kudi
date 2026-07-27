import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api, type TransactionFilters } from '../api'
import { toApiError } from '../api/client'
import type { PageMeta, Transaction } from '../api/types'
import { createLatestGuard } from './latest'

export const useTransactionsStore = defineStore('transactions', () => {
  const items = ref<Transaction[]>([])
  const meta = ref<PageMeta | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const lastFilters = ref<TransactionFilters>({})
  const guard = createLatestGuard()

  async function fetch(filters: TransactionFilters = lastFilters.value): Promise<void> {
    lastFilters.value = filters
    const ticket = guard.next()
    loading.value = true
    error.value = null
    try {
      const page = await api.transactions(filters)
      if (!guard.isCurrent(ticket)) return
      items.value = page.data
      meta.value = page.meta
    } catch (e) {
      if (!guard.isCurrent(ticket)) return
      error.value = toApiError(e).message
    } finally {
      // A superseded request must not clear the spinner the newer one raised.
      if (guard.isCurrent(ticket)) loading.value = false
    }
  }

  /**
   * Resolves a transaction from its reference, for a link that points at one.
   *
   * The loaded list only ever holds a single page, so matching against it alone
   * works for page one and silently does nothing for every transaction after
   * it. Falling back to a fetch is what makes a shared link work at all.
   *
   * Returns null rather than throwing on a reference that does not exist: the
   * caller is reacting to a URL, which may simply be stale or mistyped.
   */
  async function findByReference(reference: string): Promise<Transaction | null> {
    const loaded = items.value.find((t) => t.reference === reference)
    if (loaded) return loaded

    try {
      return await api.transaction(reference)
    } catch {
      return null
    }
  }

  /**
   * Asks the gateway to resolve a transaction that is still pending, and swaps
   * the answer into the ledger in place.
   *
   * This exists because a payment otherwise only resolves as a side effect of
   * the tab that created it staying open for the callback. Navigate away inside
   * that window and the record is stranded pending forever, with no way for an
   * operator to move it — which is also true of any transaction that was
   * pending before this session started.
   *
   * Errors are rethrown rather than stored: the caller is a button, and the
   * person who pressed it needs to be told it failed.
   */
  async function resolvePending(id: number): Promise<Transaction> {
    const resolved = await api.processTransaction(id)
    items.value = items.value.map((t) => (t.id === resolved.id ? resolved : t))
    return resolved
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

    return resolvePending(pending.id)
  }

  return { items, meta, loading, error, fetch, simulatePayment, resolvePending, findByReference }
})
