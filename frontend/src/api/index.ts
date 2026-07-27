import { http } from './client'
import type {
  AppConfig,
  DashboardData,
  Institution,
  Merchant,
  MerchantDetail,
  MerchantStatus,
  Paginated,
  ReportSummary,
  Settlement,
  SettlementBatch,
  SettlementRunResult,
  Transaction,
  User,
} from './types'

export type SortDirection = 'asc' | 'desc'

/** Columns each listing may be ordered by. Mirrors the server's whitelist. */
export type MerchantSort = 'registered' | 'business' | 'contact' | 'status' | 'balance'
export type TransactionSort = 'date' | 'reference' | 'gross' | 'fee' | 'net' | 'status'
export type SettlementSort = 'date' | 'reference' | 'batch' | 'amount'

export interface SortFilters<TSort extends string> {
  sort?: TSort
  direction?: SortDirection
}

export interface MerchantFilters extends SortFilters<MerchantSort> {
  search?: string
  status?: MerchantStatus | ''
  page?: number
  /** Backend clamps this to 100. */
  per_page?: number
}

export interface TransactionFilters extends SortFilters<TransactionSort> {
  search?: string
  status?: string
  merchant_id?: number
  from?: string
  to?: string
  page?: number
}

export interface DateRangeFilters extends SortFilters<SettlementSort> {
  from?: string
  to?: string
  page?: number
  per_page?: number
  /** Narrows to a single settlement run. */
  batch?: string
}

function query(params: object): Record<string, unknown> {
  return Object.fromEntries(
    Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== ''),
  )
}

export const api = {
  async login(email: string, password: string): Promise<User> {
    const { data } = await http.post<{ data: User }>('/api/login', { email, password })
    return data.data
  },

  async logout(): Promise<void> {
    await http.post('/api/logout')
  },

  async user(): Promise<User> {
    const { data } = await http.get<User>('/api/user')
    return data
  },

  async dashboard(): Promise<DashboardData> {
    const { data } = await http.get<{ data: DashboardData }>('/api/dashboard')
    return data.data
  },

  async institutions(): Promise<Institution[]> {
    const { data } = await http.get<{ data: Institution[] }>('/api/institutions')
    return data.data
  },

  async merchants(filters: MerchantFilters = {}): Promise<Paginated<Merchant>> {
    const { data } = await http.get<Paginated<Merchant>>('/api/merchants', {
      params: query(filters),
    })
    return data
  },

  async merchant(id: number): Promise<MerchantDetail> {
    const { data } = await http.get<{ data: MerchantDetail }>(`/api/merchants/${id}`)
    return data.data
  },

  async createMerchant(payload: {
    name: string
    email: string
    business_name: string
    institution_id: number | null
    account_number: string
  }): Promise<Merchant> {
    const { data } = await http.post<{ data: Merchant }>('/api/merchants', payload)
    return data.data
  },

  /** Activate or deactivate several merchants in one request. */
  async setMerchantStatuses(
    ids: number[],
    status: MerchantStatus,
  ): Promise<{ updated: number; status: MerchantStatus }> {
    const { data } = await http.patch<{ data: { updated: number; status: MerchantStatus } }>(
      '/api/merchants/status',
      { ids, status },
    )
    return data.data
  },

  async setMerchantStatus(id: number, status: MerchantStatus): Promise<Merchant> {
    const { data } = await http.patch<{ data: Merchant }>(`/api/merchants/${id}/status`, { status })
    return data.data
  },

  async transactions(filters: TransactionFilters = {}): Promise<Paginated<Transaction>> {
    const { data } = await http.get<Paginated<Transaction>>('/api/transactions', {
      params: query(filters),
    })
    return data
  },

  async createPayment(payload: {
    merchant_id: number | null
    amount: number
    reference: string
  }): Promise<Transaction> {
    const { data } = await http.post<{ data: Transaction }>('/api/payments', payload)
    return data.data
  },

  /** One transaction by reference — what a shared link carries. */
  async transaction(reference: string): Promise<Transaction> {
    const { data } = await http.get<{ data: Transaction }>(
      `/api/transactions/${encodeURIComponent(reference)}`,
    )
    return data.data
  },

  async processTransaction(id: number): Promise<Transaction> {
    const { data } = await http.post<{ data: Transaction }>(`/api/transactions/${id}/process`)
    return data.data
  },

  async settlements(filters: DateRangeFilters = {}): Promise<Paginated<Settlement>> {
    const { data } = await http.get<Paginated<Settlement>>('/api/settlements', {
      params: query(filters),
    })
    return data
  },

  /** Settlement history as runs. Each row is one sweep, with its own total. */
  async settlementBatches(filters: DateRangeFilters = {}): Promise<Paginated<SettlementBatch>> {
    const { data } = await http.get<Paginated<SettlementBatch>>('/api/settlements/batches', {
      params: query(filters),
    })
    return data
  },

  async runSettlement(): Promise<SettlementRunResult> {
    const { data } = await http.post<{ data: SettlementRunResult }>('/api/settlements/run')
    return data.data
  },

  async reportSummary(filters: DateRangeFilters = {}): Promise<ReportSummary> {
    const { data } = await http.get<{ data: ReportSummary }>('/api/reports/summary', {
      params: query(filters),
    })
    return data.data
  },

  /**
   * Server-owned constants. Cached for the life of the page: the fee rate is
   * deployment configuration, not data, so re-fetching it per modal open would
   * be a request that can never return anything new.
   */
  async config(): Promise<AppConfig> {
    configPromise ??= http
      .get<{ data: AppConfig }>('/api/config')
      .then((response) => response.data.data)
      .catch((error: unknown) => {
        configPromise = null // a failed fetch must not be cached as the answer
        throw error
      })

    return configPromise
  },
}

let configPromise: Promise<AppConfig> | null = null

/** Drops the cached config. Exists for tests, which must not share state. */
export function resetConfigCache(): void {
  configPromise = null
}
