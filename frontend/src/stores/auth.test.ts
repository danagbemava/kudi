import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('../api', () => ({
  api: { user: vi.fn(), login: vi.fn(), logout: vi.fn() },
}))

const notifySessionExpired = vi.fn()
vi.mock('../api/client', async (importOriginal) => ({
  ...(await importOriginal<typeof import('../api/client')>()),
  notifySessionExpired: () => notifySessionExpired(),
}))

import { api } from '../api'
import { useAuthStore } from './auth'

const user = { id: 1, name: 'Kudi Admin', email: 'admin@kudi.test' }

function unauthorized() {
  return Object.assign(new Error('Unauthenticated.'), {
    isAxiosError: true,
    response: { status: 401 },
  })
}

describe('auth store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    notifySessionExpired.mockClear()
  })

  it('clear() drops the user without calling the server', () => {
    const auth = useAuthStore()
    auth.user = user

    auth.clear()

    expect(auth.user).toBeNull()
    expect(api.logout).not.toHaveBeenCalled()
    // checked stays true so the router guard heads straight to login rather
    // than firing another request it already knows the answer to.
    expect(auth.checked).toBe(true)
  })

  it('revalidate() does nothing when nobody is signed in', async () => {
    const auth = useAuthStore()

    await auth.revalidate()

    expect(api.user).not.toHaveBeenCalled()
  })

  it('revalidate() refreshes the user while the session is alive', async () => {
    vi.mocked(api.user).mockResolvedValue(user)
    const auth = useAuthStore()
    auth.user = { ...user, name: 'Stale' }

    await auth.revalidate()

    expect(auth.user.name).toBe('Kudi Admin')
    expect(notifySessionExpired).not.toHaveBeenCalled()
  })

  it('revalidate() reports an expired session', async () => {
    // /api/user is a probe URL the interceptor ignores, so this path has to
    // raise the expiry itself — otherwise a session that lapsed while the tab
    // was in the background would go unnoticed until the next click.
    vi.mocked(api.user).mockRejectedValue(unauthorized())
    const auth = useAuthStore()
    auth.user = user

    await auth.revalidate()

    expect(notifySessionExpired).toHaveBeenCalledTimes(1)
  })
})
