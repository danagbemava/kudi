import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { relativeDay } from './dates'

function at(now: string, then: string) {
  vi.setSystemTime(new Date(now))
  return relativeDay(then)
}

afterEach(() => vi.useRealTimers())

describe('relativeDay', () => {
  beforeEach(() => vi.useFakeTimers())

  it('calls the same calendar day today, whatever the clock says', () => {
    // 09:00 "now" against an 18:00 settlement is still today, not -1 days.
    expect(at('2026-07-21T09:00:00', '2026-07-21T18:00:00')).toBe('today')
    expect(at('2026-07-21T23:30:00', '2026-07-21T00:05:00')).toBe('today')
  })

  it('counts whole days, not elapsed hours', () => {
    // Two hours apart, but across midnight — a person calls that yesterday.
    expect(at('2026-07-21T00:30:00', '2026-07-20T22:30:00')).toBe('yesterday')
  })

  it('counts days across a month boundary', () => {
    expect(at('2026-07-02T10:00:00', '2026-06-30T18:00:00')).toBe('2 days ago')
  })

  it('falls back to a date once the count stops being useful', () => {
    expect(at('2026-07-21T10:00:00', '2026-05-01T18:00:00')).toContain('May')
  })

  it('never reports a future settlement as days ago', () => {
    expect(at('2026-07-21T10:00:00', '2026-07-22T10:00:00')).toBe('today')
  })
})
