import axios, { type AxiosError } from 'axios'
import type { ValidationErrors } from './types'

export const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? '',
  withCredentials: true,
  withXSRFToken: true,
  headers: { Accept: 'application/json' },
})

let csrfReady = false

/** Sanctum SPA flow: fetch the CSRF cookie once before mutating requests. */
export async function ensureCsrf(): Promise<void> {
  if (!csrfReady) {
    await http.get('/sanctum/csrf-cookie')
    csrfReady = true
  }
}

/**
 * Requests that are *expected* to 401 for a signed-out visitor. Treating these
 * as an expiry would bounce a guest between the router guard and the login
 * page forever.
 */
const SESSION_PROBES = ['/api/user', '/api/login']

export function shouldSignOut(status: number | undefined, url = ''): boolean {
  if (status !== 401) return false

  return !SESSION_PROBES.some((probe) => url.endsWith(probe))
}

let onExpired: (() => void) | null = null

/**
 * Registered once at start-up. Kept as a callback rather than importing the
 * router and auth store here, which would be a cycle: store → api → client.
 */
export function onSessionExpired(handler: () => void): void {
  onExpired = handler
}

/**
 * Exported so a deliberate session re-check can reuse the same path — its
 * request is a probe URL, so the interceptor will not fire for it.
 */
export function notifySessionExpired(): void {
  csrfReady = false // the CSRF token died with the session
  onExpired?.()
}

export function isUnauthorized(error: unknown): boolean {
  return axios.isAxiosError(error) && error.response?.status === 401
}

http.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    if (shouldSignOut(error.response?.status, error.config?.url)) notifySessionExpired()

    return Promise.reject(error)
  },
)

export class ApiValidationError extends Error {
  fields: Record<string, string[]>

  constructor(message: string, fields: Record<string, string[]>) {
    super(message)
    this.fields = fields
  }

  first(field: string): string | undefined {
    return this.fields[field]?.[0]
  }
}

/**
 * Normalizes axios failures: 422s become ApiValidationError with per-field
 * messages; everything else becomes an Error with a human-readable message.
 */
export function toApiError(error: unknown): Error {
  if (axios.isAxiosError(error)) {
    const status = error.response?.status

    if (status === 422) {
      const data = error.response?.data as ValidationErrors
      return new ApiValidationError(data.message, data.errors ?? {})
    }

    if (status === 401) return new Error('Your session has expired. Sign in again.')

    const message = (error.response?.data as { message?: string } | undefined)?.message
    if (message) return new Error(message)

    if (!error.response)
      return new Error('Could not reach the server. Check your connection and retry.')
  }

  return new Error('Something went wrong. Please retry.')
}
