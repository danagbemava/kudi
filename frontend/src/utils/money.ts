/**
 * Single source of truth for currency display. Amounts are integer minor
 * units (pesewas) end to end; only this module converts for humans.
 */
const CURRENCY = 'GHS'
const LOCALE = 'en-GH'

// ISO code rather than the ₵ symbol: Geist draws U+20B5 as a C with a stubby
// tick that reads as "GHC" at display sizes, and money is the whole content of
// this product. GHS is also what an operations team says out loud.
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

/**
 * The rate the backend charges, in basis points. Read from /api/config at
 * start-up; 150 is only the value used before that answer arrives.
 *
 * This is deliberately not a constant. The rate is deployment configuration
 * (`KUDI_FEE_BASIS_POINTS`), and hardcoding a second copy of it here meant the
 * simulator went on quoting 1.5% while the backend charged something else —
 * the two agreed only at the default.
 */
let feeBasisPoints = 150

export function setFeeBasisPoints(basisPoints: number): void {
  if (Number.isFinite(basisPoints) && basisPoints >= 0) feeBasisPoints = Math.round(basisPoints)
}

export function getFeeBasisPoints(): number {
  return feeBasisPoints
}

/** The fee as a percentage, for labelling. `1.5` for 150 basis points. */
export function feePercent(): number {
  return feeBasisPoints / 100
}

/**
 * Processing fee in minor units, half-up — mirrors backend `Money::fee`.
 * Adding half the divisor before truncating is integer half-up rounding.
 */
export function feeFor(minorUnits: number): number {
  return Math.floor((minorUnits * feeBasisPoints + 5000) / 10000)
}
