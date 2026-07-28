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
  /** When the gateway resolved it; null while still pending. */
  processed_at?: string | null
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

/**
 * One settlement run, summarised. Aggregated server-side so the total is the
 * whole batch and not just the part of it that happened to land on this page.
 */
export interface SettlementBatch {
  batch_ref: string
  settled_at: string
  total_amount: number
  merchant_count: number
}

export interface DashboardData {
  totals: {
    wallet_balance: number
    payment_volume: number
    platform_fees: number
    settled: number
    merchant_count: number
    pending_count: number
    /** Gross value of payments still in flight — what customers were charged. */
    pending_amount: number
    /**
     * What those payments would actually add to the float once they resolve,
     * net of the fee. This is the figure comparable with `wallet_balance`.
     */
    pending_net_amount: number
  }
  volume_series: DaySeriesPoint[]
  recent_transactions: Transaction[]
  /** Merchants holding float, largest first — drives the settlement band. */
  unsettled: FloatHolder[]
  /**
   * Only the largest holders are named above. This describes everyone else, so
   * the band's strips still divide by the whole float and the merchant count is
   * the real one rather than the cap.
   */
  unsettled_meta: {
    holder_count: number
    remainder_count: number
    remainder_amount: number
  }
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

/** A merchant holding money that has not been settled yet. */
export interface FloatHolder {
  id: number
  business_name: string
  wallet_balance: number
}

/**
 * The span a series actually covers, which is not always the one asked for —
 * a range longer than the server's cap is trimmed to the most recent slice.
 * The reports screen puts totals directly above the chart, so it has to be able
 * to say which period the line is describing.
 */
export interface SeriesWindow {
  from: string
  to: string
  days: number
  truncated: boolean
}

export interface ReportSummary {
  platform_fees: number
  successful_volume: number
  total_settled: number
  transaction_counts: Record<TransactionStatus, number>
  /** Daily volume and fees across the charted window. */
  series: DaySeriesPoint[]
  series_window: SeriesWindow
}

/** Server-owned constants the interface has to mirror exactly. */
export interface AppConfig {
  fee_basis_points: number
  currency: string
}

export interface User {
  id: number
  name: string
  email: string
}

/** Laravel's paginator meta block, identical on every paginated endpoint. */
export interface PageMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
}

export interface Paginated<T> {
  data: T[]
  meta: PageMeta
}

/** Laravel 422 payload, mapped onto forms field-by-field. */
export interface ValidationErrors {
  message: string
  errors: Record<string, string[]>
}
