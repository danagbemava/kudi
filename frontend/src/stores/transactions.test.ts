import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import type { Transaction } from '../api/types'

vi.mock('../api', () => ({
  api: {
    createPayment: vi.fn(),
    processTransaction: vi.fn(),
    transactions: vi.fn(),
  },
}))

import { api } from '../api'
import { useTransactionsStore } from './transactions'

const pendingTx: Transaction = {
  id: 1,
  reference: 'PAY-TEST',
  gross_amount: 10_000,
  fee_amount: 150,
  net_amount: 9_850,
  status: 'pending',
  created_at: new Date().toISOString(),
}

describe('transactions store — simulatePayment', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.useFakeTimers()
  })

  it('inserts the pending transaction immediately, then swaps in the resolved one', async () => {
    vi.mocked(api.createPayment).mockResolvedValue(pendingTx)
    vi.mocked(api.processTransaction).mockResolvedValue({
      ...pendingTx,
      status: 'successful',
    })

    const store = useTransactionsStore()
    const promise = store.simulatePayment({
      merchant_id: 1,
      amount: 10_000,
      reference: 'PAY-TEST',
    })

    // Pending shows up before the gateway delay elapses.
    await vi.waitFor(() => expect(store.items[0]?.status).toBe('pending'))

    await vi.runAllTimersAsync()
    const resolved = await promise

    expect(resolved.status).toBe('successful')
    expect(store.items[0]?.status).toBe('successful')
    expect(api.processTransaction).toHaveBeenCalledWith(1)
  })
})
