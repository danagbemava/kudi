import { describe, expect, it } from 'vitest'
import { shouldSignOut } from './client'

describe('shouldSignOut', () => {
  it('signs out on a 401 from a normal request', () => {
    expect(shouldSignOut(401, '/api/dashboard')).toBe(true)
    expect(shouldSignOut(401, '/api/settlements/run')).toBe(true)
  })

  it('ignores the session probe, which 401s for every signed-out visitor', () => {
    // Without this the router guard's check would "expire" a guest's session
    // on first load and bounce them between the guard and the login page.
    expect(shouldSignOut(401, '/api/user')).toBe(false)
  })

  it('ignores a failed sign-in attempt', () => {
    expect(shouldSignOut(401, '/api/login')).toBe(false)
  })

  it('leaves every other status alone', () => {
    for (const status of [200, 403, 404, 422, 500, undefined]) {
      expect(shouldSignOut(status, '/api/dashboard')).toBe(false)
    }
  })
})
