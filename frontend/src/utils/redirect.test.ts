import { describe, expect, it } from 'vitest'
import { safeRedirect } from './redirect'

describe('safeRedirect', () => {
  it('keeps an in-app path', () => {
    expect(safeRedirect('/merchants/4')).toBe('/merchants/4')
    expect(safeRedirect('/transactions?tx=PAY-ABC')).toBe('/transactions?tx=PAY-ABC')
  })

  it('falls back when the parameter is absent or empty', () => {
    // `?? '/'` did not catch this: an empty string is neither null nor
    // undefined, and router.push('') does not go anywhere useful.
    expect(safeRedirect('')).toBe('/')
    expect(safeRedirect(undefined)).toBe('/')
    expect(safeRedirect(null)).toBe('/')
  })

  it('refuses anything pointing off this origin', () => {
    // `//evil.test` is protocol-relative: the browser reads it as a host, not
    // as a path on this site.
    expect(safeRedirect('//evil.test/steal')).toBe('/')
    expect(safeRedirect('https://evil.test')).toBe('/')
    expect(safeRedirect('javascript:alert(1)')).toBe('/')
  })

  it('takes the first value when the parameter repeats', () => {
    // ?redirect=/a&redirect=/b arrives as an array, which would otherwise be
    // cast to the string "/a,/b".
    expect(safeRedirect(['/merchants', '/reports'])).toBe('/merchants')
  })

  it('rejects a relative path, which could resolve anywhere', () => {
    expect(safeRedirect('merchants')).toBe('/')
  })
})
