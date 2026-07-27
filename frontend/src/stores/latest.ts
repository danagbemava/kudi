/**
 * Guards a store against out-of-order responses.
 *
 * Changing a status filter and then a date fires two requests, and the network
 * is free to answer them in either order. Without a guard the slower, older
 * response wins simply by arriving last, and the table ends up showing results
 * for filters that are no longer on screen — with no error and nothing spinning
 * to suggest anything went wrong.
 *
 * Each request takes a ticket; only the newest ticket may write to the store.
 */
export function createLatestGuard() {
  let latest = 0

  return {
    /** Claim the newest ticket, invalidating every request already in flight. */
    next: (): number => ++latest,
    isCurrent: (ticket: number): boolean => ticket === latest,
  }
}
