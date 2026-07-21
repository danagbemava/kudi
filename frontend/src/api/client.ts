import axios, { AxiosError } from 'axios'
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
  if (error instanceof AxiosError) {
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
