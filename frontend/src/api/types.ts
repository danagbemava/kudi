export type MerchantStatus = 'active' | 'inactive'
export type TransactionStatus = 'pending' | 'successful' | 'failed'
export type InstitutionType = 'bank' | 'mobile_money'

export interface Institution {
  id: number
  name: string
  code: string
  type: InstitutionType
}

export interface Merchant {
  id: number
  name: string
  email: string
  business_name: string
  account_number: string
  institution?: Institution
  status: MerchantStatus
  wallet_balance?: number
  created_at: string
}

export interface MerchantDetail extends Merchant {
  recent_transactions: Transaction[]
  recent_settlements: Settlement[]
  stats: {
    total_settled: number
    transaction_count: number
    successful_volume: number
  }
}

export interface Transaction {
  id: number
  reference: string
  merchant?: Merchant
  gross_amount: number
  fee_amount: number
  net_amount: number
  status: TransactionStatus
  created_at: string
}

export interface Settlement {
  id: number
  reference: string
  batch_ref: string
  merchant?: Merchant
  amount: number
  settled_at: string
}

export interface SettlementRunResult {
  batch_ref: string
  merchant_count: number
  total_amount: number
}

export interface DashboardData {
  totals: {
    wallet_balance: number
    payment_volume: number
    platform_fees: number
    settled: number
    merchant_count: number
    pending_count: number
    /** Gross value of payments still in flight. */
    pending_amount: number
  }
  volume_series: DaySeriesPoint[]
  recent_transactions: Transaction[]
  /** Merchants holding float, largest first — drives the settlement band. */
  unsettled: { id: number; business_name: string; wallet_balance: number }[]
  /** The most recent settlement run, or null if none has happened. */
  last_settlement: {
    batch_ref: string
    settled_at: string
    amount: number
    merchant_count: number
  } | null
}

export interface DaySeriesPoint {
  date: string
  volume: number
  fees: number
}

export interface ReportSummary {
  platform_fees: number
  successful_volume: number
  total_settled: number
  transaction_counts: Record<TransactionStatus, number>
  /** Daily volume and fees across the selected range. */
  series: DaySeriesPoint[]
}

export interface User {
  id: number
  name: string
  email: string
}

export interface Paginated<T> {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
  }
}

/** Laravel 422 payload, mapped onto forms field-by-field. */
export interface ValidationErrors {
  message: string
  errors: Record<string, string[]>
}
