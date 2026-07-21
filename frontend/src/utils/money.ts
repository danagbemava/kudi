/**
 * Single source of truth for currency display. Amounts are integer minor
 * units (pesewas) end to end; only this module converts for humans.
 */
const CURRENCY = 'GHS'
const LOCALE = 'en-GH'

// ISO code rather than the ₵ symbol: Archivo draws U+20B5 as a C with a
// stubby tick that reads as "GHC" at display sizes, and money is the whole
// content of this product. GHS is also what an operations team says out loud.
const formatter = new Intl.NumberFormat(LOCALE, {
  style: 'currency',
  currency: CURRENCY,
  currencyDisplay: 'code',
})

const compactFormatter = new Intl.NumberFormat(LOCALE, {
  style: 'currency',
  currency: CURRENCY,
  currencyDisplay: 'code',
  notation: 'compact',
  maximumFractionDigits: 1,
})

export function formatMoney(minorUnits: number): string {
  return formatter.format(minorUnits / 100)
}

export function formatMoneyCompact(minorUnits: number): string {
  return compactFormatter.format(minorUnits / 100)
}

/** Parses a human amount ("150.75") into minor units, or null if invalid. */
export function parseAmount(input: string): number | null {
  const trimmed = input.trim().replace(/,/g, '')
  if (!/^\d+(\.\d{1,2})?$/.test(trimmed)) return null

  const [whole, cents = ''] = trimmed.split('.')
  return Number(whole) * 100 + Number(cents.padEnd(2, '0') || 0)
}

/** 1.5% fee in minor units, half-up — mirrors backend Money::fee. */
export function feeFor(minorUnits: number): number {
  return Math.floor((minorUnits * 150 + 5000) / 10000)
}
