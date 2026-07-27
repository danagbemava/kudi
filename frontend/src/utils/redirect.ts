/**
 * Where to land after signing in, given the `redirect` query parameter.
 *
 * The value arrives from the URL, so it is untrusted. It has to resolve to a
 * path inside this app: a bare `??` fallback misses the empty string, a
 * repeated parameter arrives as an array rather than a string, and a leading
 * `//` is protocol-relative and names another origin entirely.
 */
export function safeRedirect(value: unknown, fallback = '/'): string {
  const target = Array.isArray(value) ? value[0] : value

  if (typeof target !== 'string') return fallback
  if (!target.startsWith('/') || target.startsWith('//')) return fallback

  return target
}
