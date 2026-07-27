import { setActivePinia, createPinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createLatestGuard } from './latest'

describe('createLatestGuard', () => {
  it('only treats the newest ticket as current', () => {
    const guard = createLatestGuard()

    const first = guard.next()
    const second = guard.next()

    expect(guard.isCurrent(first)).toBe(false)
    expect(guard.isCurrent(second)).toBe(true)
  })
})

/**
 * The failure this prevents: change a filter, change another, and the network
 * answers the first request last. Without a guard the older response wins by
 * arriving later and the table shows results for filters no longer on screen —
 * silently, with nothing to suggest anything is wrong.
 */
describe('transactions store, out-of-order responses', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.resetModules()
  })

  it('ignores a slow response that a newer request has superseded', async () => {
    const resolvers: ((value: unknown) => void)[] = []

    vi.doMock('../api', () => ({
      api: {
        transactions: vi.fn(
          () => new Promise((resolve) => resolvers.push(resolve as (v: unknown) => void)),
        ),
      },
    }))

    const { useTransactionsStore } = await import('./transactions')
    const store = useTransactionsStore()

    const stale = store.fetch({ status: 'pending' })
    const fresh = store.fetch({ status: 'failed' })

    const page = (reference: string) => ({
      data: [{ id: 1, reference }],
      meta: { current_page: 1, last_page: 1, per_page: 15, total: 1, from: 1, to: 1 },
    })

    // Newest answers first, then the one it replaced.
    resolvers[1]!(page('FRESH'))
    await fresh

    resolvers[0]!(page('STALE'))
    await stale

    expect(store.items[0]!.reference).toBe('FRESH')
    expect(store.loading).toBe(false)
  })
})
