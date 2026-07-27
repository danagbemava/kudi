import { afterEach, describe, expect, it } from 'vitest'
import { feeFor, feePercent, formatMoney, parseAmount, setFeeBasisPoints } from './money'

afterEach(() => setFeeBasisPoints(150))

describe('parseAmount', () => {
  it('parses whole cedis into pesewas', () => {
    expect(parseAmount('150')).toBe(15_000)
  })

  it('parses decimals, including a single digit', () => {
    expect(parseAmount('150.75')).toBe(15_075)
    expect(parseAmount('150.5')).toBe(15_050)
  })

  it('accepts thousands separators', () => {
    expect(parseAmount('1,250.00')).toBe(125_000)
  })

  it('rejects malformed input', () => {
    expect(parseAmount('')).toBeNull()
    expect(parseAmount('abc')).toBeNull()
    expect(parseAmount('12.345')).toBeNull()
    expect(parseAmount('-5')).toBeNull()
  })
})

describe('feeFor', () => {
  it('matches the backend rule: 1.5% rounded half-up', () => {
    expect(feeFor(10_000)).toBe(150) // GH₵100 → GH₵1.50
    expect(feeFor(100)).toBe(2) // 1.5 pesewas rounds up
    expect(feeFor(99)).toBe(1) // 1.485 rounds down
    expect(feeFor(0)).toBe(0)
  })

  /**
   * The rate is deployment configuration (KUDI_FEE_BASIS_POINTS) and the
   * backend reads it from config. A second hardcoded copy here meant the
   * simulator's live preview and the actual charge agreed only at the default.
   */
  it('follows the rate the server reports', () => {
    setFeeBasisPoints(275)

    expect(feeFor(10_000)).toBe(275)
    expect(feePercent()).toBe(2.75)
  })

  it('keeps half-up rounding at any rate', () => {
    setFeeBasisPoints(333)

    // 10_000 * 333 = 3_330_000; +5_000 = 3_335_000; / 10_000 = 333.5 → 333
    expect(feeFor(10_000)).toBe(333)
    // 101 * 333 = 33_633; +5_000 = 38_633; / 10_000 = 3.86 → 3
    expect(feeFor(101)).toBe(3)
  })

  it('ignores a nonsensical rate rather than charging it', () => {
    setFeeBasisPoints(150)
    setFeeBasisPoints(Number.NaN)
    expect(feePercent()).toBe(1.5)

    setFeeBasisPoints(-10)
    expect(feePercent()).toBe(1.5)
  })
})

describe('formatMoney', () => {
  it('formats pesewas as cedis', () => {
    // Different ICU builds render the GHS symbol differently; assert the digits.
    expect(formatMoney(123_456)).toContain('1,234.56')
  })
})
