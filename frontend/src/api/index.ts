import { ensureCsrf, http } from './client'
import type {
  DashboardData,
  Institution,
  Merchant,
  MerchantDetail,
  MerchantStatus,
  Paginated,
  ReportSummary,
  Settlement,
  SettlementRunResult,
  Transaction,
  User,
} from './types'

export interface MerchantFilters {
  search?: string
  status?: MerchantStatus | ''
  page?: number
  /** Backend clamps this to 100. */
  per_page?: number
}

export interface TransactionFilters {
  search?: string
  status?: string
  merchant_id?: number
  from?: string
  to?: string
  page?: number
}

export interface DateRangeFilters {
  from?: string
  to?: string
  page?: number
}

function query(params: object): Record<string, unknown> {
  return Object.fromEntries(
    Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== ''),
  )
}

export const api = {
  async login(email: string, password: string): Promise<User> {
    await ensureCsrf()
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
    await ensureCsrf()
    const { data } = await http.post<{ data: Merchant }>('/api/merchants', payload)
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
    await ensureCsrf()
    const { data } = await http.post<{ data: Transaction }>('/api/payments', payload)
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

  async runSettlement(): Promise<SettlementRunResult> {
    await ensureCsrf()
    const { data } = await http.post<{ data: SettlementRunResult }>('/api/settlements/run')
    return data.data
  },

  async reportSummary(filters: DateRangeFilters = {}): Promise<ReportSummary> {
    const { data } = await http.get<{ data: ReportSummary }>('/api/reports/summary', {
      params: query(filters),
    })
    return data.data
  },
}
