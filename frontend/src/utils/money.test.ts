import { describe, expect, it } from 'vitest'
import { feeFor, formatMoney, parseAmount } from './money'

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
})

describe('formatMoney', () => {
  it('formats pesewas as cedis', () => {
    // Different ICU builds render the GHS symbol differently; assert the digits.
    expect(formatMoney(123_456)).toContain('1,234.56')
  })
})
