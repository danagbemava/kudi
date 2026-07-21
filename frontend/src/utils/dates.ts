const dateFormatter = new Intl.DateTimeFormat('en-GB', {
  day: 'numeric',
  month: 'short',
  year: 'numeric',
})

const dateTimeFormatter = new Intl.DateTimeFormat('en-GB', {
  day: 'numeric',
  month: 'short',
  hour: '2-digit',
  minute: '2-digit',
})

export function formatDate(iso: string): string {
  return dateFormatter.format(new Date(iso))
}

export function formatDateTime(iso: string): string {
  return dateTimeFormatter.format(new Date(iso))
}

/**
 * How long ago, in the terms someone running a daily task thinks in. "8 days
 * ago" answers "is a run overdue?"; "13 Jul 2026" makes them work it out.
 */
export function relativeDay(iso: string): string {
  const startOfDay = (d: Date) => new Date(d.getFullYear(), d.getMonth(), d.getDate())
  const days = Math.round(
    (startOfDay(new Date()).getTime() - startOfDay(new Date(iso)).getTime()) / 86_400_000,
  )

  if (days <= 0) return 'today'
  if (days === 1) return 'yesterday'
  if (days < 31) return `${days} days ago`

  return formatDate(iso)
}
