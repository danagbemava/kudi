import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import type { Transaction } from '../api/types'

vi.mock('../api', () => ({
  api: {
    createPayment: vi.fn(),
    processTransaction: vi.fn(),
    transactions: vi.fn(),
    transaction: vi.fn(),
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

// A payment only resolves because the tab that created it stayed open long
// enough to make the follow-up call. Close it early — or look at a transaction
// that was seeded pending — and the record is stranded with no way out. The
// operator needs to be able to ask the gateway again.
describe('transactions store — resolvePending', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('re-asks the gateway and swaps the row in place', async () => {
    vi.mocked(api.processTransaction).mockResolvedValue({
      ...pendingTx,
      status: 'successful',
    })

    const store = useTransactionsStore()
    store.items = [{ ...pendingTx, id: 9, reference: 'PAY-OTHER' }, pendingTx]

    const resolved = await store.resolvePending(1)

    expect(api.processTransaction).toHaveBeenCalledWith(1)
    expect(resolved.status).toBe('successful')
    // Only the targeted row changes, and it keeps its position in the ledger.
    expect(store.items.map((t) => t.id)).toEqual([9, 1])
    expect(store.items[1]?.status).toBe('successful')
    expect(store.items[0]?.status).toBe('pending')
  })

  it('surfaces a failure instead of silently leaving the row pending', async () => {
    vi.mocked(api.processTransaction).mockRejectedValue(new Error('gateway down'))

    const store = useTransactionsStore()
    store.items = [pendingTx]

    await expect(store.resolvePending(1)).rejects.toThrow()
    expect(store.items[0]?.status).toBe('pending')
  })
})

// A link to a transaction has to open it wherever it falls in the ledger. The
// list only ever holds one page, so resolving against it silently fails for
// every transaction past the first page — which is most of them.
describe('transactions store — findByReference', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('uses the loaded page when the transaction is already there', async () => {
    const store = useTransactionsStore()
    store.items = [pendingTx]

    const found = await store.findByReference('PAY-TEST')

    expect(found).toEqual(pendingTx)
    expect(api.transaction).not.toHaveBeenCalled()
  })

  it('fetches it when it is on another page', async () => {
    const offPage = { ...pendingTx, id: 99, reference: 'PAY-FAR' }
    vi.mocked(api.transaction).mockResolvedValue(offPage)

    const store = useTransactionsStore()
    store.items = [pendingTx]

    const found = await store.findByReference('PAY-FAR')

    expect(api.transaction).toHaveBeenCalledWith('PAY-FAR')
    expect(found).toEqual(offPage)
  })

  it('returns null for a reference that does not exist', async () => {
    vi.mocked(api.transaction).mockRejectedValue(new Error('404'))

    const store = useTransactionsStore()

    await expect(store.findByReference('PAY-NOPE')).resolves.toBeNull()
  })
})
